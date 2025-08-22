<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking mistakes table structure...\n";

try {
    $columns = \Illuminate\Support\Facades\DB::select('DESCRIBE mistakes');
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field}: {$column->Type} " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
