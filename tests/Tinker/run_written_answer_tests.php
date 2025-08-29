<?php

/**
 * Interactive Runner for Written Answer Grading Tests
 *
 * Usage:
 * 1. php artisan tinker
 * 2. require 'tests/Tinker/run_written_answer_tests.php';
 * 3. run_written_answer_tests();
 */

if (!function_exists('run_written_answer_tests')) {
    function run_written_answer_tests() {
        $tests = new Tests\Tinker\WrittenAnswerGradingTests();
        return $tests->runAllTests();
    }
}

if (!function_exists('quick_answer_test')) {
    function quick_answer_test() {
        $tests = new Tests\Tinker\WrittenAnswerGradingTests();
        $tests->runQuickTest();
    }
}

if (!function_exists('test_custom_answer')) {
    function test_custom_answer(string $submitted, string $correct, float $threshold = 0.85) {
        $tests = new Tests\Tinker\WrittenAnswerGradingTests();
        return $tests->testCustomAnswer($submitted, $correct, $threshold);
    }
}

echo "\n🎯 Written Answer Grading Test Runner Loaded!\n";
echo "==============================================\n\n";
echo "Available functions:\n";
echo "• run_written_answer_tests()     - Run all comprehensive tests\n";
echo "• quick_answer_test()           - Run a quick test\n";
echo "• test_custom_answer(\$submitted, \$correct, \$threshold = 0.85) - Test specific answers\n\n";
echo "Example usage:\n";
echo "test_custom_answer('Your answer here', 'Correct answer here', 0.8);\n\n";
