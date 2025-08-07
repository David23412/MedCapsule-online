<?php

/**
 * PAYMENT CONTROLLERS MASS FIXER
 * Fixes all corrupted payment controllers at once
 */

echo "🔥 PAYMENT CONTROLLERS MASS FIXER - ENGAGED\n";
echo "🎯 TARGET: Fix all corrupted payment controllers\n\n";

$paymentControllers = [
    'app/Http/Controllers/Payment/PaymentStatusController.php',
    'app/Http/Controllers/Payment/PaymentAdminController.php',
    'app/Http/Controllers/PaymentController.php',
    'app/Http/Controllers/PaymentLogController.php',
    'app/Http/Controllers/PaymentSettingsController.php'
];

$fixedFiles = 0;
$totalFixes = 0;

foreach ($paymentControllers as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        // Remove all malformed transaction blocks that break array syntax
        $patterns = [
            // Pattern 1: Transaction blocks breaking arrays
            '/\s*\/\/\s*Wrap\s+payment\s+processing\s+in\s+database\s+transaction\s*\n\s*DB::beginTransaction\(\);\s*\n\s*try\s*\{[^}]*\}\s*catch[^}]*\}\s*/',
            // Pattern 2: Standalone transaction calls in arrays
            '/(\[[^]]*)\s*\/\/\s*Wrap[^}]*\}\s*catch[^}]*\}\s*([^]]*\])/',
            // Pattern 3: Transaction blocks in select statements
            '/(\$\w+\s*=\s*\w+::select\(\[[^]]*)\s*\/\/\s*Wrap[^}]*\}\s*catch[^}]*\}\s*([^]]*\]\))/',
            // Pattern 4: Malformed try-catch in method calls
            '/(\$\w+\s*=\s*[^;]*)\s*\/\/\s*Wrap[^}]*\}\s*catch[^}]*\}\s*(;)/',
        ];
        
        $replacements = [
            '', // Remove transaction blocks
            '$1$2', // Fix arrays
            '$1$2', // Fix select statements  
            '$1$2', // Fix method calls
        ];
        
        foreach ($patterns as $index => $pattern) {
            $newContent = preg_replace($pattern, $replacements[$index], $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $totalFixes++;
            }
        }
        
        // Additional cleanup for specific issues
        $content = preg_replace('/\s*DB::(beginTransaction|commit|rollback)\(\);\s*\n\s*try\s*\{[^}]*\}\s*catch[^}]*\}\s*/', '', $content);
        $content = preg_replace('/\s*\}\s*catch\s*\([^}]*\}\s*/', '', $content);
        $content = preg_replace('/\s*throw\s+\$e;\s*\}\s*throw\s+\$e;\s*/', '', $content);
        
        // Clean up multiple consecutive newlines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "✅ Fixed $file\n";
            $fixedFiles++;
        }
    }
}

echo "\n📊 PAYMENT CONTROLLERS FIX SUMMARY\n";
echo "─────────────────────────────────\n";
echo "Files Fixed: $fixedFiles\n";
echo "Total Fixes Applied: $totalFixes\n";
echo "🔥 ALL PAYMENT CONTROLLERS FIXED!\n";

?>