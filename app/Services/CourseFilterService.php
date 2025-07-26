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
        'YEAR 1 - SEMESTER 1' => 'YEAR 1 - SEMESTER 1',
        'YEAR 1 - SEMESTER 2' => 'YEAR 1 - SEMESTER 2',
        'YEAR 2 - SEMESTER 1' => 'YEAR 2 - SEMESTER 1',
        'YEAR 2 - SEMESTER 2' => 'YEAR 2 - SEMESTER 2',
        'YEAR 3 - SEMESTER 1' => 'YEAR 3 - SEMESTER 1',
        'YEAR 3 - SEMESTER 2' => 'YEAR 3 - SEMESTER 2',
        'YEAR 4 - SEMESTER 1' => 'YEAR 4 - SEMESTER 1',
        'YEAR 4 - SEMESTER 2' => 'YEAR 4 - SEMESTER 2',
        'YEAR 5 - SEMESTER 1' => 'YEAR 5 - SEMESTER 1',
        'YEAR 5 - SEMESTER 2' => 'YEAR 5 - SEMESTER 2',
        'YEAR 6 - SEMESTER 1' => 'YEAR 6 - SEMESTER 1',
        'YEAR 6 - SEMESTER 2' => 'YEAR 6 - SEMESTER 2',
        'SAT' => 'SAT',
        'EST' => 'EST',
        'ACT' => 'ACT'
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
            'YEAR 1 - SEMESTER 1' => 'Y1S1',
            'YEAR 1 - SEMESTER 2' => 'Y1S2',
            'YEAR 2 - SEMESTER 1' => 'Y2S1',
            'YEAR 2 - SEMESTER 2' => 'Y2S2',
            'YEAR 3 - SEMESTER 1' => 'Y3S1',
            'YEAR 3 - SEMESTER 2' => 'Y3S2',
            'YEAR 4 - SEMESTER 1' => 'Y4S1',
            'YEAR 4 - SEMESTER 2' => 'Y4S2',
            'YEAR 5 - SEMESTER 1' => 'Y5S1',
            'YEAR 5 - SEMESTER 2' => 'Y5S2',
            'YEAR 6 - SEMESTER 1' => 'Y6S1',
            'YEAR 6 - SEMESTER 2' => 'Y6S2',
            'SAT' => 'SAT',
            'EST' => 'EST',
            'ACT' => 'ACT'
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
                    'YEAR 1 - SEMESTER 1', 'YEAR 1 - SEMESTER 2',
                    'YEAR 2 - SEMESTER 1', 'YEAR 2 - SEMESTER 2',
                    'YEAR 3 - SEMESTER 1', 'YEAR 3 - SEMESTER 2',
                    'YEAR 4 - SEMESTER 1', 'YEAR 4 - SEMESTER 2',
                    'YEAR 5 - SEMESTER 1', 'YEAR 5 - SEMESTER 2',
                    'YEAR 6 - SEMESTER 1', 'YEAR 6 - SEMESTER 2'
                ]
            ],
            'prep' => [
                'label' => 'Test Preparation',
                'years' => ['SAT', 'EST', 'ACT']
            ]
        ];
    }
}