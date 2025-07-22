<!DOCTYPE html>
<html>
<head>
    <title>Skipped Badge Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .test-card { 
            border: 2px solid #ccc; 
            padding: 20px; 
            margin: 20px 0; 
            position: relative;
            min-height: 200px;
        }
        .info-boxes {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
        }
        .info-box {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 15px;
            background: white;
            font-size: 12px;
        }
        .skipped-badge {
            background: rgba(135, 206, 235, 0.2) !important;
            border: 2px solid rgba(135, 206, 235, 0.6) !important;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>
    <h1>🔍 Skipped Badge Test Page</h1>
    
    <div class="test-card">
        <h3>Test 1: Static Skipped Badge (Should Always Show)</h3>
        <p>This badge should always be visible:</p>
        <div class="info-boxes">
            <div class="info-box skipped-badge">
                <i class="fas fa-forward" style="color: #4a90e2;"></i>
                <span style="color: #2c5aa0; font-weight: 600;">SKIPPED</span>
            </div>
            <div class="info-box">
                <span>Date: Jul 22, 2025</span>
            </div>
        </div>
    </div>

    <?php
    // Include Laravel bootstrap
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    use App\Models\Mistake;
    
    // Get actual skipped mistakes
    $mistakes = Mistake::where('user_id', 11)->where('skipped', true)->take(3)->get();
    ?>

    @foreach($mistakes as $index => $mistake)
    <div class="test-card">
        <h3>Test {{ $index + 2 }}: Real Data - Question {{ $mistake->question_id }}</h3>
        <p><strong>Skipped Value:</strong> {{ var_export($mistake->skipped, true) }} ({{ gettype($mistake->skipped) }})</p>
        <p><strong>Condition Test:</strong> @if($mistake->skipped) ✅ PASSES @else ❌ FAILS @endif</p>
        
        <div class="info-boxes">
            @if($mistake->skipped)
            <div class="info-box skipped-badge">
                <i class="fas fa-forward" style="color: #4a90e2;"></i>
                <span style="color: #2c5aa0; font-weight: 600;">SKIPPED</span>
            </div>
            @endif
            <div class="info-box">
                <span>Question: {{ $mistake->question_id }}</span>
            </div>
        </div>
    </div>
    @endforeach

    <div class="test-card">
        <h3>Debug Info</h3>
        <p><strong>Total skipped mistakes in DB:</strong> {{ Mistake::where('skipped', true)->count() }}</p>
        <p><strong>User 11 skipped mistakes:</strong> {{ Mistake::where('user_id', 11)->where('skipped', true)->count() }}</p>
        <p><strong>Current time:</strong> {{ now() }}</p>
    </div>

    <script>
        console.log('🔍 Skipped badge test page loaded');
        console.log('Looking for .skipped-badge elements:', document.querySelectorAll('.skipped-badge').length);
        
        // Highlight all skipped badges
        document.querySelectorAll('.skipped-badge').forEach((badge, index) => {
            badge.style.border = '3px solid red';
            badge.style.boxShadow = '0 0 10px red';
            console.log(`Found skipped badge ${index + 1}:`, badge);
        });
    </script>
</body>
</html>