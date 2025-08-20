<?php

if (!function_exists('getScoreCategory')) {
    /**
     * Get the CSS class for a quiz score.
     *
     * @param float|int $score
     * @return string
     */
    function getScoreCategory($score): string
    {
        if ($score >= 90) {
            return 'score-excellent';
        } elseif ($score >= 75) {
            return 'score-good';
        } elseif ($score >= 60) {
            return 'score-average';
        }
        return 'score-poor';
    }
}
