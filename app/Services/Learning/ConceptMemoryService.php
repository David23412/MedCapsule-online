<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConceptMemoryService
{
    public function syncFailure(Mistake $mistake): void
    {
        // Question concept is sourced directly from the question/topic model.
    }

    public function syncReview(Mistake $mistake, int $rating, string $mode, ?int $responseSeconds = null, ?string $confidence = null): void
    {
        // Question concept is sourced directly from the question/topic model.
    }

    public function resolveConceptsForQuestion(Question $question): Collection
    {
        $concepts = $question->concepts;
        if ($concepts->isNotEmpty()) {
            return $concepts;
        }

        if (!$question->relationLoaded('topic')) {
            $question->loadMissing('topic');
        }

        if (!$question->topic) {
            return collect();
        }

        $name = trim((string) $question->topic->name);
        $slug = Str::slug($name);

        return collect([
            (object) [
                'id' => abs(crc32($slug)),
                'name' => $name,
                'slug' => $slug,
            ],
        ]);
    }
}
