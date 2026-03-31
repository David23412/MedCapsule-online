<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileServeController extends Controller
{
    /**
     * Serve a profile picture from storage.
     */
    public function profilePicture(string $path): BinaryFileResponse
    {
        return $this->serveFirstExisting([
            storage_path('app/private/uploads/profile-pictures'),
            storage_path('app/uploads/profile-pictures'),
        ], $path);
    }

    /**
     * Serve a course image from storage.
     */
    public function courseImage(string $path): BinaryFileResponse
    {
        return $this->serveFirstExisting([
            storage_path('app/public/course-images'),
            storage_path('app/uploads/course-images'),
        ], $path);
    }

    /**
     * Serve a question image from storage.
     */
    public function questionImage(string $path): BinaryFileResponse
    {
        return $this->serveFile(storage_path('app/public/questions'), $path);
    }

    /**
     * Serve a file from the given base directory.
     */
    private function serveFile(string $basePath, string $path, array $headers = []): BinaryFileResponse
    {
        $baseDirectory = realpath($basePath);
        abort_if($baseDirectory === false, 404);

        $relativePath = ltrim($path, '/');
        $requestedPath = realpath($baseDirectory . DIRECTORY_SEPARATOR . $relativePath);

        abort_if($requestedPath === false || !is_file($requestedPath), 404);
        abort_if(
            $requestedPath !== $baseDirectory && !str_starts_with($requestedPath, $baseDirectory . DIRECTORY_SEPARATOR),
            404
        );

        $mimeType = mime_content_type($requestedPath) ?: 'application/octet-stream';

        return response()->file($requestedPath, array_merge([
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ], $headers));
    }

    /**
     * Try multiple base paths and serve the first file found.
     */
    private function serveFirstExisting(array $basePaths, string $path, array $headers = []): BinaryFileResponse
    {
        foreach ($basePaths as $basePath) {
            $baseDirectory = realpath($basePath);
            if ($baseDirectory === false) {
                continue;
            }

            $relativePath = ltrim($path, '/');
            $requestedPath = realpath($baseDirectory . DIRECTORY_SEPARATOR . $relativePath);

            if ($requestedPath === false || !is_file($requestedPath)) {
                continue;
            }

            if ($requestedPath !== $baseDirectory && !str_starts_with($requestedPath, $baseDirectory . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $mimeType = mime_content_type($requestedPath) ?: 'application/octet-stream';

            return response()->file($requestedPath, array_merge([
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=31536000',
            ], $headers));
        }

        abort(404);
    }
}
