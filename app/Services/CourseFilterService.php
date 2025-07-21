<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourseFilterService
{
    /**
     * Academic year mapping for display and filtering
     */
    const ACADEMIC_YEARS = [
        'year-1-sem-1' => 'Year 1 - First Semester',
        'year-1-sem-2' => 'Year 1 - Second Semester',
        'year-2-sem-1' => 'Year 2 - First Semester',
        'year-2-sem-2' => 'Year 2 - Second Semester',
        'year-3-sem-1' => 'Year 3 - First Semester',
        'year-3-sem-2' => 'Year 3 - Second Semester',
        'year-4-sem-1' => 'Year 4 - First Semester',
        'year-4-sem-2' => 'Year 4 - Second Semester',
        'year-5-sem-1' => 'Year 5 - First Semester',
        'year-5-sem-2' => 'Year 5 - Second Semester',
        'year-6-sem-1' => 'Year 6 - First Semester',
        'year-6-sem-2' => 'Year 6 - Second Semester',
        'sat-prep' => 'SAT Preparation',
        'est-prep' => 'EST Preparation',
        'act-prep' => 'ACT Preparation'
    ];

    /**
     * Get all available academic years from courses
     */
    public static function getAvailableAcademicYears(): Collection
    {
        return Course::select('academic_year')
            ->distinct()
            ->whereNotNull('academic_year')
            ->pluck('academic_year')
            ->map(function ($year) {
                return [
                    'value' => $year,
                    'label' => self::ACADEMIC_YEARS[$year] ?? $year,
                    'short_label' => self::getShortLabel($year)
                ];
            })
            ->sortBy('value');
    }

    /**
     * Filter courses by academic year
     */
    public static function filterByAcademicYear(Builder $query, string $academicYear): Builder
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Get courses grouped by academic year
     */
    public static function getCoursesGroupedByYear(): Collection
    {
        return Course::select('id', 'name', 'academic_year', 'color', 'title_color', 'image')
            ->whereNotNull('academic_year')
            ->get()
            ->groupBy('academic_year')
            ->map(function ($courses, $year) {
                return [
                    'year' => $year,
                    'label' => self::ACADEMIC_YEARS[$year] ?? $year,
                    'short_label' => self::getShortLabel($year),
                    'courses' => $courses
                ];
            })
            ->sortBy('year');
    }

    /**
     * Get short label for academic year (for pills/badges)
     */
    public static function getShortLabel(string $academicYear): string
    {
        $shortLabels = [
            'year-1-sem-1' => 'Y1S1',
            'year-1-sem-2' => 'Y1S2',
            'year-2-sem-1' => 'Y2S1',
            'year-2-sem-2' => 'Y2S2',
            'year-3-sem-1' => 'Y3S1',
            'year-3-sem-2' => 'Y3S2',
            'year-4-sem-1' => 'Y4S1',
            'year-4-sem-2' => 'Y4S2',
            'year-5-sem-1' => 'Y5S1',
            'year-5-sem-2' => 'Y5S2',
            'year-6-sem-1' => 'Y6S1',
            'year-6-sem-2' => 'Y6S2',
            'sat-prep' => 'SAT',
            'est-prep' => 'EST',
            'act-prep' => 'ACT'
        ];

        return $shortLabels[$academicYear] ?? strtoupper($academicYear);
    }

    /**
     * Get full label for academic year
     */
    public static function getFullLabel(string $academicYear): string
    {
        return self::ACADEMIC_YEARS[$academicYear] ?? $academicYear;
    }

    /**
     * Validate academic year value
     */
    public static function isValidAcademicYear(string $academicYear): bool
    {
        return array_key_exists($academicYear, self::ACADEMIC_YEARS);
    }

    /**
     * Get academic year categories for filtering UI
     */
    public static function getAcademicYearCategories(): array
    {
        return [
            'medical' => [
                'label' => 'Medical School',
                'years' => [
                    'year-1-sem-1', 'year-1-sem-2',
                    'year-2-sem-1', 'year-2-sem-2',
                    'year-3-sem-1', 'year-3-sem-2',
                    'year-4-sem-1', 'year-4-sem-2',
                    'year-5-sem-1', 'year-5-sem-2',
                    'year-6-sem-1', 'year-6-sem-2'
                ]
            ],
            'prep' => [
                'label' => 'Test Preparation',
                'years' => ['sat-prep', 'est-prep', 'act-prep']
            ]
        ];
    }
}