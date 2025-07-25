<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 HTTP 503 QUIZ SUBMISSION ERROR INVESTIGATION\n";
echo "===============================================\n\n";

echo "🔍 ERROR DETAILS:\n";
echo "=================\n";
echo "Status: HTTP 503 (Service Unavailable)\n";
echo "Endpoint: /grade\n";
echo "Context: Quiz submission after contamination fix\n";
echo "Impact: Users cannot complete quizzes\n\n";

echo "🎯 POTENTIAL CAUSES:\n";
echo "====================\n";
echo "1. ❓ Database transaction timeout in GradingService\n";
echo "2. ❓ QuizAttempt::getTopicProgress() performance issue\n";
echo "3. ❓ Memory exhaustion during progress calculation\n";
echo "4. ❓ Infinite loop or deadlock in updated code\n";
echo "5. ❓ Missing database indexes causing slow queries\n\n";

echo "🔍 CHECKING RECENT CHANGES:\n";
echo "===========================\n";
echo "Recent fix applied to QuizAttempt::getTopicProgress():\n";
echo "- Added: ->where('quiz_type', '!=', 'assignment')\n";
echo "- This could be causing performance issues!\n\n";

echo "🚨 IMMEDIATE INVESTIGATION TARGETS:\n";
echo "===================================\n";
echo "1. Check Laravel logs for 503 error details\n";
echo "2. Test QuizAttempt::getTopicProgress() performance\n";
echo "3. Verify database query performance\n";
echo "4. Check for memory/timeout issues\n";
echo "5. Test quiz grading with simple data\n\n";

echo "🔧 EMERGENCY RESPONSE PLAN:\n";
echo "============================\n";
echo "1. Identify exact cause of 503 error\n";
echo "2. Optimize the contamination fix if needed\n";
echo "3. Add database indexes if required\n";
echo "4. Implement fallback error handling\n";
echo "5. Test quiz submission thoroughly\n\n";

echo "✅ INVESTIGATION READY - PROCEEDING TO DIAGNOSE 503 ERROR\n";