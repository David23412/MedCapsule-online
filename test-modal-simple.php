<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing Source Modal Behavior\n";
echo "===============================\n\n";

echo "📋 Expected Behavior for Regular Topic Cards:\n";
echo "1. Click 'Start Quiz' button\n";
echo "2. JavaScript prevents default navigation\n";
echo "3. Checks data-has-assignment attribute\n";
echo "4. If 'false' or undefined → Show source selection modal\n";
echo "5. User selects sources and clicks 'Start Quiz' in modal\n";
echo "6. Form submits with selected sources\n\n";

echo "🔍 Debug Steps:\n";
echo "1. Open browser console\n";
echo "2. Click on a regular topic 'Start Quiz' button\n";
echo "3. Check console logs:\n";
echo "   - 'Setting up quiz button handlers...'\n";
echo "   - 'Found X quiz buttons'\n";
echo "   - 'Modal element found: true'\n";
echo "   - '✅ Modal initialized successfully'\n";
echo "   - 'Regular topic X: Showing source selection modal'\n";
echo "   - 'About to show modal...'\n";
echo "   - 'Modal show() called'\n\n";

echo "🚨 If modal doesn't appear, check:\n";
echo "1. Bootstrap CSS/JS loaded correctly\n";
echo "2. No CSS conflicts hiding the modal\n";
echo "3. No JavaScript errors in console\n";
echo "4. Modal backdrop and content are visible in DOM\n\n";

echo "🎯 Quick Test:\n";
echo "Run this in browser console on the topics page:\n";
echo "```javascript\n";
echo "// Test modal manually\n";
echo "const modal = new bootstrap.Modal(document.getElementById('sourceSelectionModal'));\n";
echo "modal.show();\n";
echo "```\n\n";

echo "If manual test works, the issue is in the click handler logic.\n";
echo "If manual test fails, the issue is with Bootstrap or CSS.\n";

?>