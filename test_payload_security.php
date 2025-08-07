<?php

// Simple test to verify payload size security check
require_once 'vendor/autoload.php';

// Create a large payload
$oversizedAnswer = str_repeat('a', 10000); // 10KB answer
$payload = [
    'answers' => [1 => $oversizedAnswer],
    'time_taken' => 60
];

// Calculate payload size
$payloadSize = strlen(json_encode($payload));
echo "Payload size: " . $payloadSize . " bytes\n";

// Check if it exceeds 1MB limit
if ($payloadSize > 1048576) {
    echo "✅ Payload exceeds 1MB limit - should be rejected\n";
} else {
    echo "❌ Payload is under 1MB limit - test needs larger payload\n";
}

// Test the size check logic
if ($payloadSize > 1048576) {
    echo "✅ Security check would trigger: 413 Payload Too Large\n";
} else {
    echo "❌ Security check would NOT trigger\n";
}