<?php

return [
    'shadow_mode_enabled' => (bool) env('WRITTEN_GRADING_SHADOW_MODE', false),
    'grader_version' => (string) env('WRITTEN_GRADING_VERSION', 'v2.0'),
    'grader_primary' => (string) env('WRITTEN_GRADING_PRIMARY', 'new'),
    'legacy_correct_disagreement_threshold' => (float) env('WRITTEN_GRADING_LEGACY_CORRECT_DISAGREEMENT_THRESHOLD', 0.03),
    'pass_threshold' => (float) env('WRITTEN_GRADING_PASS_THRESHOLD', 0.72),
    'required_concept_threshold' => (float) env('WRITTEN_GRADING_REQUIRED_CONCEPT_THRESHOLD', 0.74),
    'required_concept_weight' => (float) env('WRITTEN_GRADING_REQUIRED_CONCEPT_WEIGHT', 0.70),
    'optional_concept_weight' => (float) env('WRITTEN_GRADING_OPTIONAL_CONCEPT_WEIGHT', 0.20),
    'order_overlap_weight' => (float) env('WRITTEN_GRADING_ORDER_OVERLAP_WEIGHT', 0.10),
    'enumeration_coverage_threshold' => (float) env('WRITTEN_GRADING_ENUMERATION_THRESHOLD', 0.70),
    'comparison_side_threshold' => (float) env('WRITTEN_GRADING_COMPARISON_SIDE_THRESHOLD', 0.70),
    'typo_similarity_threshold' => (float) env('WRITTEN_GRADING_TYPO_SIMILARITY_THRESHOLD', 0.80),
    'short_answer_exact_word_limit' => (int) env('WRITTEN_GRADING_SHORT_ANSWER_WORD_LIMIT', 3),
    'short_concept_group_token_limit' => (int) env('WRITTEN_GRADING_SHORT_CONCEPT_GROUP_TOKEN_LIMIT', 6),
    'enumeration_min_average_item_words' => (float) env('WRITTEN_GRADING_ENUMERATION_MIN_AVG_ITEM_WORDS', 3.0),
];
