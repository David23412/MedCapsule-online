<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdempotentStudySubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Study-Request');
        if ($key === null) {
            return $next($request);
        }
        abort_unless(Str::isUuid($key), 422, 'Invalid study request identifier.');
        $userId = $request->user()->id;
        $fingerprint = hash('sha256', $request->path() . ':' . json_encode($request->json()->all(), JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($request, $next, $key, $userId, $fingerprint) {
            // The unique row also serializes simultaneous retries of this submission.
            DB::table('study_submissions')->insertOrIgnore([
                'user_id' => $userId, 'request_key' => $key,
                'fingerprint' => $fingerprint, 'created_at' => now(),
            ]);
            $submission = DB::table('study_submissions')->where('user_id', $userId)
                ->where('request_key', $key)->lockForUpdate()->first();
            abort_unless($submission && hash_equals($submission->fingerprint, $fingerprint), 409, 'This request identifier was already used for a different answer.');
            if ($submission->response !== null) {
                return response($submission->response, 200)->header('Content-Type', 'application/json');
            }

            $response = $next($request);
            if ($response->getStatusCode() === 200) {
                DB::table('study_submissions')->where('id', $submission->id)->update(['response' => $response->getContent()]);
            } else {
                DB::table('study_submissions')->where('id', $submission->id)->delete();
            }
            return $response;
        }, 3);
    }
}
