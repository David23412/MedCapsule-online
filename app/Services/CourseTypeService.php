<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\Cache;

class CourseTypeService
{
    /**
     * Get categorizations for a course
     *
     * @param Course $course
     * @return array
     */
    public function getCategorizations(Course $course): array
    {
        $cacheKey = "course_{$course->id}_categorizations_v{$course->cache_version}";
        
        return Cache::remember($cacheKey, 3600, function () use ($course) {
            if ($course->isMedicalCourse()) {
                return $this->getMedicalCategorizations($course);
            } else {
                return $this->getCustomCategorizations($course);
            }
        });
    }
    
    /**
     * Get medical course categorizations
     *
     * @param Course $course
     * @return array
     */
    protected function getMedicalCategorizations(Course $course): array
    {
        $topics = $course->topics;
        
        $categorizations = [
            'quiz' => $topics->where('case_type', 'quiz')->count(),
            'cases' => $topics->where('case_type', 'cases')->count(),
            'practical' => $topics->where('case_type', 'practical')->count(),
        ];
        
        // Filter out empty categories
        return array_filter($categorizations, function ($count) {
            return $count > 0;
        });
    }
    
    /**
     * Get custom course categorizations
     *
     * @param Course $course
     * @return array
     */
    protected function getCustomCategorizations(Course $course): array
    {
        $topics = $course->topics;
        $categories = $topics->pluck('case_type')->unique()->filter()->values();
        
        $categorizations = [];
        foreach ($categories as $category) {
            $categorizations[$category] = $topics->where('case_type', $category)->count();
        }
        
        return $categorizations;
    }
}