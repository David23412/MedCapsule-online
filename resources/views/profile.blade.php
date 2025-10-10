<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>
    <!-- RUTHLESS: Resource hints for faster loading -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- RUTHLESS: Async loading for better performance -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <style>
        /* Profile page zoom fix - 80% scale */
        html {
            font-size: 12.8px; /* 80% of 16px */
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            zoom: 0.80;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(135deg, #007bff, #00bcd4);
            padding: 20px;
            color: white;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 8px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .profile-header {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 50%, #f8f9fa 100%);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow:
                0 8px 32px rgba(0,0,0,0.08),
                0 2px 8px rgba(0,0,0,0.04),
                inset 0 1px 0 rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.06);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .profile-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255,255,255,0.1) 0%,
                rgba(255,255,255,0.2) 50%,
                rgba(255,255,255,0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-header:hover::after {
            opacity: 1;
        }

        .welcome-text {
            flex-grow: 1;
            position: relative;
            z-index: 2;
        }

        .welcome-text h1 {
            font-size: 2.4rem;
            margin-bottom: 12px;
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.15;
            color: #007bff;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .welcome-text p {
            color: #546e7a;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
            letter-spacing: -0.01em;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.6s ease forwards 0.4s;
        }

        .welcome-text p i {
            color: #1e88e5;
            margin-right: 8px;
            animation: iconPulse 2s ease infinite;
        }

        .wave-emoji {
            display: inline-block;
            font-size: 2rem;
            margin-left: 5px;
            transform-origin: center center;
            animation: wave 2.5s ease-in-out infinite;
            position: relative;
            top: -2px;
            transition: transform 0.3s ease;
            cursor: pointer;
            /* Ensure crisp rendering */
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            backface-visibility: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .wave-emoji:hover {
            animation: quickWave 0.5s ease;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes wave {
            0% { transform: rotate(0deg); }
            5% { transform: rotate(15deg); }
            10% { transform: rotate(-8deg); }
            15% { transform: rotate(14deg); }
            20% { transform: rotate(-4deg); }
            25% { transform: rotate(10deg); }
            30% { transform: rotate(0deg); }
            100% { transform: rotate(0deg); }
        }

        @keyframes quickWave {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            50% { transform: rotate(-8deg); }
            75% { transform: rotate(14deg); }
            100% { transform: rotate(0deg); }
        }

        @keyframes iconPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Clean Accent Line with Subtle Glow */
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                #007bff 20%,
                #007bff 80%,
                transparent 100%
            );
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.4);
            z-index: 1;
            animation: accentGlow 3s ease-in-out infinite;
        }

        @keyframes accentGlow {
            0%, 100% {
                box-shadow: 0 0 15px rgba(0, 123, 255, 0.4);
                opacity: 0.8;
            }
            50% {
                box-shadow: 0 0 25px rgba(0, 123, 255, 0.6);
                opacity: 1;
            }
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #e9ecef;
            position: relative;
            overflow: hidden;
            border: 3px solid #007bff;
            cursor: pointer;
            z-index: 1;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
        }

        .profile-picture:hover {
            border-color: #0056b3;
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
            transform: scale(1.05);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
        }

        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            z-index: 2;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .profile-picture:hover .upload-overlay {
            opacity: 1;
        }

        .upload-overlay i {
            color: white;
            font-size: 1.5rem;
            pointer-events: none;
        }

        .profile-picture:active {
            border-color: #0056b3;
            transform: scale(0.98);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            /* RUTHLESS GPU OPTIMIZATION - SAME VISUAL EFFECT */
            will-change: transform;
            transform: translate3d(0, 0, 0);
        }

        .stats-card:hover {
            transform: translate3d(0, -5px, 0);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 600;
            color: #007bff;
            margin: 10px 0;
        }

        .streak-card {
            background: linear-gradient(135deg, #00d2ff, #3a7bd5);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .accuracy-card {
            background: linear-gradient(135deg, #FF7043, #FF5252);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .study-time-card {
            background: linear-gradient(135deg, #FF9A9E, #FAD0C4);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .accuracy-card .stats-value,
        .streak-card .stats-value,
        .study-time-card .stats-value {
            color: white;
        }

        .accuracy-card .text-muted,
        .streak-card .text-muted,
        .study-time-card .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        @keyframes pulse-glow {
            0% {
                transform: scale(1);
                opacity: 1;
                text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            }
            50% {
                transform: scale(1.1);
                opacity: 0.9;
                text-shadow: 0 0 20px rgba(255, 255, 255, 0.8),
                            0 0 30px rgba(255, 255, 255, 0.6);
            }
            100% {
                transform: scale(1);
                opacity: 1;
                text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            }
        }

        @keyframes rotate-clock {
            0% {
                transform: rotate(0deg);
            }
            20% {
                transform: rotate(15deg);
            }
            40% {
                transform: rotate(-15deg);
            }
            60% {
                transform: rotate(7deg);
            }
            80% {
                transform: rotate(-7deg);
            }
            100% {
                transform: rotate(0deg);
            }
        }

        .pulsing-icon {
            display: inline-block;
            animation: pulse-glow 2s infinite;
        }

        .clock-icon {
            display: inline-block;
            animation: rotate-clock 3s ease-in-out infinite;
            transform-origin: center;
        }

        .badge-streak {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
        }

        .course-card {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
            background-color: white;
            border: 1px solid rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .course-image {
            position: relative;
            height: 9rem;
            width: 100%;
            overflow: hidden;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
        }

        .course-image img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .course-card:hover .course-image img {
            transform: scale(1.05);
        }

        .course-category {
            position: absolute;
            left: 0.75rem;
            top: 0.75rem;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            backdrop-filter: blur(4px);
        }

        .accuracy-card {
            background: linear-gradient(135deg, #FF8C69, #FF6B6B);
            color: white;
        }

        .accuracy-card .stats-value {
            color: white;
        }

        .accuracy-card .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        @keyframes pulse-glow {
            0% {
                transform: scale(1);
                opacity: 1;
                text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            }
            50% {
                transform: scale(1.1);
                opacity: 0.9;
                text-shadow: 0 0 20px rgba(255, 255, 255, 0.8),
                            0 0 30px rgba(255, 255, 255, 0.6);
            }
            100% {
                transform: scale(1);
                opacity: 1;
                text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            }
        }

        .pulsing-icon {
            display: inline-block;
            animation: pulse-glow 2s infinite;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
            padding: 20px 10px;
        }

        /* Custom tooltip styling */
        .custom-tooltip {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 10px 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
        }

        .review-section {
            position: relative;
        }

        /* Topics Progress card glow effect */
        .col-md-6:first-child .review-section {
            animation: softGlowingOutline 3s ease-in-out infinite;
        }

        @keyframes softGlowingOutline {
            0%, 100% {
                box-shadow: 0 0 3px rgba(220, 53, 69, 0.2),
                           0 0 7px rgba(220, 53, 69, 0.1),
                           inset 0 0 3px rgba(220, 53, 69, 0.1);
            }
            50% {
                box-shadow: 0 0 5px rgba(220, 53, 69, 0.3),
                           0 0 10px rgba(220, 53, 69, 0.2),
                           inset 0 0 5px rgba(220, 53, 69, 0.15);
            }
        }

        .review-section h5 i {
            color: #dc3545;
        }

        @keyframes pulse-warning {
            0%, 100% {
                filter: none;
            }
            50% {
                filter: none;
            }
        }

        .topic-review-item {
            border-left: 4px solid;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .topic-review-item:hover {
            transform: translateX(5px);
        }

        /* Critical topics (0-25%) */
        .topic-critical {
            border-left-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
        }

        /* Struggling topics (26-49%) */
        .topic-struggling {
            border-left-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.05);
        }

        /* Improving topics (50-74%) */
        .topic-improving {
            border-left-color: #17a2b8;
            background-color: rgba(23, 162, 184, 0.05);
        }

        /* Good topics (75-99%) */
        .topic-good {
            border-left-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }

        .progress-bar-critical {
            background-color: #dc3545 !important;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
            background-image: none !important;
        }

        .progress-bar-struggling {
            background-color: #ffc107 !important;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
            background-image: none !important;
        }

        .progress-bar-improving {
            background-color: #17a2b8 !important;
            box-shadow: 0 0 10px rgba(23, 162, 184, 0.5);
            background-image: none !important;
        }

        .progress-bar-good {
            background-color: #28a745 !important;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
            background-image: none !important;
        }

        /* Leaderboard Styles */
        .leaderboard-section {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 2rem 0;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
            position: relative;
            overflow: hidden;
        }

        .leaderboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .leaderboard-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4a148c;
        }

        .leaderboard-title i {
            color: #FFD700;
            font-size: 1.75rem;
            animation: glowingTrophy 2s infinite;
        }

        @keyframes glowingTrophy {
            0%, 100% { text-shadow: 0 0 5px rgba(255, 215, 0, 0.5); }
            50% { text-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 0 0 30px rgba(255, 215, 0, 0.6); }
        }

        .leaderboard-item {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(74, 20, 140, 0.1);
            position: relative;
            overflow: hidden;
            /* RUTHLESS GPU OPTIMIZATION - SAME VISUAL EFFECT */
            will-change: transform;
            transform: translate3d(0, 0, 0);
        }

        .progress {
            background-color: rgba(74, 20, 140, 0.05);
            overflow: hidden;
            height: 2px;
        }

        .progress .progress-bar {
            background: linear-gradient(90deg, #7e57c2, #b388ff);
            box-shadow: 0 0 10px rgba(126, 87, 194, 0.3);
            transition: width 0.3s ease;
        }

        .leaderboard-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 20, 140, 0.15);
            border-color: rgba(74, 20, 140, 0.2);
        }

        .rank-badge {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffa000);
            color: #000;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #A0A0A0);
            color: #000;
            box-shadow: 0 2px 8px rgba(192, 192, 192, 0.3);
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #A0522D);
            color: #fff;
            box-shadow: 0 2px 8px rgba(205, 127, 50, 0.3);
        }

        .user-info {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .user-name {
            font-weight: 600;
            color: #4a148c;
            font-size: 1.1rem;
        }

        .user-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .stat-value {
            font-weight: 600;
            color: #4a148c;
        }

        .xp-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #4a148c, #7b1fa2);
            opacity: 0.7;
            transition: width 0.3s ease;
        }

        .level-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #0d47a1, #2196f3);
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3),
                       inset 0 0 15px rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .level-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: translateX(-100%);
            animation: shimmerBadge 2s infinite;
        }

        .level-badge::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            right: -50%;
            bottom: -50%;
            background: radial-gradient(
                circle,
                rgba(33, 150, 243, 0.4) 0%,
                rgba(33, 150, 243, 0) 70%
            );
            animation: pulseBadge 2s infinite;
        }

        .stat-value.xp {
            font-weight: 600;
            font-size: 1.1rem;
            color: #00bcd4;
            text-shadow: 0 0 10px rgba(0, 188, 212, 0.3);
        }

        @keyframes shimmerBadge {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes pulseBadge {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }

        .burning-text {
            font-size: 0.85rem;
            color: #4a90e2;
            position: relative;
            display: inline-block;
        }

        @keyframes burn {
            0%, 100% {
                text-shadow: 
                    0 0 3px #4a90e2,
                    0 0 5px #4a90e2,
                    0 0 7px #4a90e2,
                    0 0 10px #4a90e2;
            }
            50% {
                text-shadow: 
                    0 0 5px #64b5f6,
                    0 0 7px #64b5f6,
                    0 0 10px #64b5f6,
                    0 0 15px #64b5f6;
            }
        }

        @keyframes flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .burning-text::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #4a90e2, #64b5f6);
            border-radius: 4px;
            z-index: -1;
            opacity: 0.2;
            animation: flicker 2s infinite;
        }

        .burning-text.level-1 {
            animation: burn 2s infinite;
        }

        .level-indicator {
            font-size: 0.85rem;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .level-indicator.rookie {
            color: #4a90e2;
            background: linear-gradient(45deg, rgba(74, 144, 226, 0.1), rgba(100, 181, 246, 0.1));
            animation: babyBlueBurn 3s infinite;
            position: relative;
        }

        @keyframes babyBlueBurn {
            0%, 100% {
                box-shadow: 
                    0 0 5px #4a90e2,
                    0 0 10px #4a90e2,
                    0 0 15px rgba(74, 144, 226, 0.5);
                text-shadow: 
                    0 0 5px #4a90e2,
                    0 0 8px #4a90e2;
            }
            50% {
                box-shadow: 
                    0 0 8px #64b5f6,
                    0 0 15px #64b5f6,
                    0 0 20px rgba(100, 181, 246, 0.7);
                text-shadow: 
                    0 0 8px #64b5f6,
                    0 0 12px #64b5f6;
            }
        }

        /* Level Text Style */
        .rookie-level {
            display: inline-block;
            color: #4a90e2;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .leaderboard-card {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .leaderboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0),
                rgba(255, 255, 255, 0.2),
                rgba(255, 255, 255, 0)
            );
        }

        .xp-counter {
            transition: color 0.3s ease;
            position: relative;
        }

        .xp-counter::after {
            content: '+';
            position: absolute;
            top: -8px;
            right: -8px;
            color: #7e57c2;
            font-size: 0.8rem;
            opacity: 0;
            transform: translateY(10px);
            animation: xpPlus 2s ease-out infinite;
        }

        @keyframes xpPlus {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }
            20% {
                opacity: 1;
                transform: translateY(0);
            }
            80% {
                opacity: 1;
                transform: translateY(-15px);
            }
            100% {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        .xp-counter.updating {
            animation: xpPulse 0.5s ease;
        }

        @keyframes xpPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); color: #b388ff; }
            100% { transform: scale(1); }
        }

        @keyframes chartFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes dataPointPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes lineDrawIn {
            from { stroke-dashoffset: 1000; }
            to { stroke-dashoffset: 0; }
        }

        @keyframes areaFadeIn {
            from { opacity: 0; }
            to { opacity: 0.2; }
        }

        .chart-container {
            animation: chartFadeIn 0.8s ease-out;
            position: relative;
            margin: 2rem 0;
        }

        .chart-container canvas {
            transition: all 0.3s ease;
        }

        .chart-container:hover canvas {
            transform: scale(1.01);
        }

        /* ====== Leaderboard Visual Enhancements ====== */

        /* Animate subtle gradient background for leaderboard container */
        .leaderboard-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(270deg, #ffe6f7, #e6f7ff, #e6ffe6, #fffbe6, #ffe6f7);
            background-size: 1000% 1000%;
            animation: gradientFlow 20s ease infinite;
            opacity: 0.15;
            z-index: 0;
            border-radius: 16px;
            pointer-events: none;
        }

        /* Keep content above the background */
        .leaderboard-section > * {
            position: relative;
            z-index: 1;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Add glow to top 3 rank badges */
        .rank-1, .rank-2, .rank-3 {
            position: relative;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5), 0 0 25px rgba(255, 215, 0, 0.3);
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 10px rgba(255, 215, 0, 0.4), 0 0 20px rgba(255, 215, 0, 0.2);
            }
            50% {
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.7), 0 0 30px rgba(255, 215, 0, 0.4);
            }
        }

        /* Add shimmer effect to level badges */
        .level-badge::after {
            content: '';
            position: absolute;
            top: 0; left: -75%;
            width: 50%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.6) 50%, rgba(255,255,255,0.2) 100%);
            transform: skewX(-20deg);
            animation: shimmer 2.5s infinite;
            z-index: 1;
        }

        @keyframes shimmer {
            0% { left: -75%; }
            100% { left: 125%; }
        }

        /* Enhance hover on leaderboard items */
        .leaderboard-item:hover {
            /* transform removed to reduce distraction */
            box-shadow: 0 6px 16px rgba(74, 20, 140, 0.25);
            border-color: rgba(74, 20, 140, 0.3);
        }

        /* Add subtle glow to XP counters */
        .xp-counter {
            text-shadow: 0 0 5px rgba(126, 87, 194, 0.4);
            transition: transform 0.3s ease, text-shadow 0.3s ease;
        }

        .xp-counter:hover {
            transform: scale(1.1);
            text-shadow: 0 0 10px rgba(126, 87, 194, 0.6);
        }

        /* Sparkle effect on top 3 leaderboard items */
        .leaderboard-item:nth-child(-n+3)::after {
            content: '✨';
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 1.2rem;
            animation: sparkle 1.5s infinite alternate;
            pointer-events: none;
        }

        @keyframes sparkle {
            0% { transform: rotate(0deg) scale(1); opacity: 0.7; }
            50% { transform: rotate(20deg) scale(1.3); opacity: 1; }
            100% { transform: rotate(-20deg) scale(1); opacity: 0.7; }
        }

        /* Slightly enhance progress bar glow */
        .leaderboard-item .progress-bar {
            box-shadow: 0 0 8px rgba(126, 87, 194, 0.5);
        }

        /* Make level badge text pop more */
        .level-badge {
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
    /* ====== Medal icon enhancements ====== */
    .review-section h5 i.fa-medal {
        animation: medalPulse 2s infinite;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
    }

    .review-section h5 i.fa-medal:hover {
        transform: rotate(-10deg) scale(1.2);
        filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8));
    }

    @keyframes medalPulse {
        0%, 100% {
            filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
        }
        50% {
            filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.8));
        }
    }

    /* ====== Rank emoji enhancements ====== */
    .rank-emoji {
        display: inline-block;
        animation: emojiBounce 2s infinite;
        transition: transform 0.3s ease, filter 0.3s ease;
        filter: drop-shadow(0 0 3px rgba(126, 87, 194, 0.4));
    }

    .rank-emoji:hover {
        transform: scale(1.3) rotate(10deg);
        filter: drop-shadow(0 0 8px rgba(126, 87, 194, 0.7));
    }

    @keyframes emojiBounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    .xp-info-card {
        background: linear-gradient(135deg, #673ab7, #9c27b0);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: 0 4px 10px rgba(103, 58, 183, 0.3);
        animation: fadeInRight 0.5s ease;
        position: relative;
        overflow: hidden;
        min-width: 110px;
    }

    .xp-info-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, 
            rgba(255,255,255,0.1) 0%,
            rgba(255,255,255,0.2) 50%,
            rgba(255,255,255,0.1) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .xp-info-card:hover::before {
        opacity: 1;
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .study-time-breakdown {
        margin-top: 10px;
        font-size: 0.9rem;
    }
    
    .breakdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 5px 0;
        padding: 4px 8px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .breakdown-item span {
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .study-time-card .text-muted {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    /* Fix for weak topic practice button - exact size 81px x 26px */
    .topic-review-item .btn-sm {
        width: 81px !important;
        height: 26px !important;
        padding: 2px 8px !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 4px !important;
        white-space: nowrap !important;
    }

    .topic-review-item .btn-sm i {
    font-size: 10px !important;
    margin-left: 4px !important;
    margin-right: 0 !important;
    }

        /* Study Goals Card Styling */
        .study-goals-card {
            background: white;
            border: none;
        }

        /* Purple slash decoration */
        .goal-decoration {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 33.33%;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(167, 139, 250, 0.12) 100%);
            clip-path: polygon(20% 0%, 100% 0%, 100% 100%, 0% 100%);
            pointer-events: none;
            z-index: 0;
        }

        .study-goals-card > * {
            position: relative;
            z-index: 1;
        }

        .goal-settings-btn {
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .goal-settings-btn:hover {
            transform: translateY(-1px);
        }

        .goal-achieved-badge {
            animation: celebrationPulse 1.5s ease-in-out infinite;
        }

        @keyframes celebrationPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes sparkleFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(180deg); }
        }

        /* Confetti effect */
        .confetti-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            overflow: hidden;
        }

        @keyframes confettiFall {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(400px) rotate(720deg);
                opacity: 0;
            }
        }

</style>
    </style>
</head>
<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="{{ route('home') }}" class="text-decoration-none">
            <h4 class="mb-4" style="color: white; font-weight: 700;">MedCapsule</h4>
        </a>
        <nav class="nav flex-column">
            <a class="nav-link" href="{{ route('home') }}">
                <i class="fas fa-home"></i> Home
            </a>
            <a class="nav-link" href="{{ route('courses.index') }}">
                <i class="fas fa-book"></i> My Courses
            </a>
            <a class="nav-link active" href="{{ route('profile') }}">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="{{ route('review.index') }}">
                <i class="fas fa-redo"></i> Review Mistakes
            </a>
            @if(auth()->user()->role === 'admin')
            <a class="nav-link" href="{{ route('admin.user-course') }}">
                <i class="fas fa-users-cog"></i> Manage Access
            </a>
            <a class="nav-link" href="{{ route('add-topic.form') }}">
                <i class="fas fa-plus-circle"></i> Add Topic
            </a>
            <a class="nav-link" href="{{ route('add-question.form') }}">
                <i class="fas fa-question-circle"></i> Add Question
            </a>
            <a class="nav-link" href="{{ route('add-course.form') }}">
                <i class="fas fa-graduation-cap"></i> Add Course
            </a>
            @endif
            <form method="POST" action="{{ secure_url(route('logout', [], false)) }}" class="mt-auto">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header animate-fadeInUp">
            <div class="profile-picture" id="profilePictureContainer" style="cursor: pointer;" title="Click to upload new profile picture">
                @php
                    $profileImageUrl = auth()->user()->getProfileImageUrl();
                @endphp
                <img src="{{ $profileImageUrl }}"
                     alt="Profile Picture"
                     id="profileImage"
                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&size=200&background=007bff&color=ffffff'">
                <div class="upload-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            <form action="{{ secure_url(route('profile.update-picture', [], false)) }}" method="POST" enctype="multipart/form-data" id="profileForm" style="display: none;">
                @csrf
                <input type="file" 
                       name="profile_picture" 
                       id="profilePictureInput" 
                       accept="image/*"
                       style="display: none;">
            </form>
            <div class="welcome-text">
                <h1>Welcome back, {{ auth()->user()->name }}! <span class="wave-emoji" id="waveEmoji">👋</span></h1>
                <div class="d-flex align-items-center gap-3">
                    <p><i class="fas fa-calendar-alt"></i> Member since {{ auth()->user()->created_at->format('F Y') }}</p>
                    
                    @if($userRank)
                        @if($userRank <= 10)
                        <p><i class="fas fa-trophy" style="color: #FFD700;"></i> Rank #{{ $userRank }} on leaderboard</p>
                        @else
                        <p>
                            <i class="fas fa-trophy" style="color: #FFD700;"></i> Rank #{{ $userRank }} 
                            <span style="margin-left: 5px; font-size: 0.9rem; color: #546e7a;">
                                <i class="fas fa-bolt" style="color: #7e57c2;"></i> {{ auth()->user()->xp ?? 0 }} XP
                            </span>
                        </p>
                        @endif
                    @else
                    <p>
                        <i class="fas fa-award" style="color: #64b5f6;"></i> 
                        <span style="margin-left: 5px; font-size: 0.9rem;">Not yet ranked</span>
                        @if(auth()->user()->xp > 0)
                        <span style="margin-left: 5px; font-size: 0.9rem; color: #546e7a;">
                            <i class="fas fa-bolt" style="color: #7e57c2;"></i> {{ auth()->user()->xp }} XP
                        </span>
                        @endif
                    </p>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card streak-card animate-fadeInUp" style="animation-delay: 0.1s">
                    <h5><i class="fas fa-fire me-2 pulsing-icon"></i>Study Streak</h5>
                    <div class="stats-value">{{ $studyStreak }}</div>
                    <span class="badge-streak">
                        <i class="fas fa-fire"></i> day{{ $studyStreak != 1 ? 's' : '' }} in a row
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card accuracy-card animate-fadeInUp" style="animation-delay: 0.2s">
                    <h5><i class="fas fa-bullseye me-2 pulsing-icon"></i>Accuracy Rate</h5>
                    <div class="stats-value">{{ number_format($accuracyRate, 1) }}%</div>
                    <small class="text-muted">Based on {{ $totalQuestions }} attempts</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card study-time-card animate-fadeInUp" style="animation-delay: 0.3s">
                    <h5><i class="fas fa-clock me-2 clock-icon"></i>Study Time</h5>
                    <div class="stats-value">{{ $formattedStudyTimes['total'] }}</div>
                    <div class="study-time-breakdown">
                        <div class="breakdown-item">
                            <small>Quiz Mode</small>
                            <span>{{ $formattedStudyTimes['quiz'] }}</span>
                        </div>
                        <div class="breakdown-item">
                            <small>Tutor Mode</small>
                            <span>{{ $formattedStudyTimes['tutor'] }}</span>
                        </div>
                        <div class="breakdown-item">
                            <small>Random Quizzes</small>
                            <span>{{ $formattedStudyTimes['random_quiz'] }}</span>
                        </div>
                        <div class="breakdown-item">
                            <small>Mistake Review</small>
                            <span>{{ $formattedStudyTimes['mistake_review'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.4s">
                    <h5><i class="fas fa-question-circle me-2"></i>Total Questions</h5>
                    <div class="stats-value">{{ $totalQuestions }}</div>
                    <small class="text-muted">Questions attempted</small>
                </div>
            </div>
        </div>

        <!-- Study Goals Widget -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card study-goals-card animate-fadeInUp" style="animation-delay: 0.5s; position: relative; overflow: hidden;">
                    <!-- Purple slash decoration -->
                    <div class="goal-decoration"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0" style="font-weight: 600; font-size: 1.3rem; color: #0f172a; letter-spacing: -0.01em;">
                            This Week's Goal
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="openStudyPlanner()" style="font-size: 0.85rem; padding: 6px 16px;">
                                <i class="fas fa-calendar-check me-1"></i>Study Planner
                            </button>
                            <button class="btn btn-sm btn-outline-primary goal-settings-btn" onclick="openGoalModal()" style="font-size: 0.85rem; padding: 6px 16px;">
                                Edit Goal
                            </button>
                        </div>
                    </div>
                    
                    @php
                        // Calculate this week's progress
                        $weekStart = now()->startOfWeek();
                        $weekEnd = now()->endOfWeek();
                        $weeklyQuestions = \App\Models\QuizAttempt::where('user_id', auth()->id())
                            ->whereBetween('created_at', [$weekStart, $weekEnd])
                            ->sum('total_questions');
                        
                        // Get user's goal (default 50)
                        $weeklyGoal = auth()->user()->weekly_goal ?? 50;
                        $progressPercent = min(100, ($weeklyQuestions / $weeklyGoal) * 100);
                        
                        // Determine color based on progress
                        $progressColor = $progressPercent >= 100 ? '#10b981' : 
                                        ($progressPercent >= 70 ? '#3b82f6' : 
                                        ($progressPercent >= 40 ? '#f59e0b' : '#ef4444'));
                    @endphp
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-baseline gap-3 mb-3">
                                <span style="font-size: 3rem; font-weight: 800; color: {{ $progressColor }}; line-height: 1;">{{ $weeklyQuestions }}</span>
                                <span style="font-size: 1.5rem; font-weight: 600; color: #64748b;">/ {{ $weeklyGoal }}</span>
                            </div>
                            
                            <div class="progress" style="height: 10px; border-radius: 10px; background: #e2e8f0; margin-bottom: 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.08);">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ $progressPercent }}%; 
                                            background: linear-gradient(90deg, {{ $progressColor }}, {{ $progressColor }}dd);
                                            border-radius: 10px;
                                            transition: width 0.6s ease;
                                            box-shadow: 0 2px 4px {{ $progressColor }}40;">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="color: #64748b; font-size: 0.9rem;">{{ $weekStart->format('M d') }} - {{ $weekEnd->format('M d') }}</span>
                                <span style="font-weight: 700; font-size: 1.1rem; color: {{ $progressColor }};">{{ number_format($progressPercent, 0) }}%</span>
                            </div>
                        </div>
                        
                        @if($progressPercent >= 100)
                            <div class="ms-4" style="font-size: 3.5rem; animation: celebrationPulse 1.5s ease-in-out infinite;">
                                🏆
                            </div>
                        @endif
                    </div>
                    
                    @if($progressPercent >= 100)
                        <div class="confetti-container"></div>
                    @endif
                </div>
            </div>
        </div>


        <!-- Charts Row -->
        <div class="row mt-4">
            <!-- Performance Over Time -->
            <div class="col-md-8">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.6s">
                    <h5>Performance Over Time</h5>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Study Distribution -->
            <div class="col-md-4">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.7s">
                    <h5>Performance Ratio</h5>
                    <div class="chart-container">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Areas to Review -->
        <div class="row mt-4">
            <!-- Areas to Review -->
            <div class="col-md-6">
                <div class="stats-card review-section animate-fadeInUp" style="animation-delay: 0.7s">
                    <h5><i class="fas fa-list-check me-2"></i>Topics Progress</h5>
                    @if($weakTopics->isEmpty())
                        <p class="text-muted">Start practicing to see your topic progress!</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($weakTopics as $topic)
                            @php
                                $grade = $topic->userProgress->first()->percentage_grade;
                                $urgencyClass = match(true) {
                                    $grade <= 25 => 'topic-critical',
                                    $grade <= 49 => 'topic-struggling',
                                    $grade <= 74 => 'topic-improving',
                                    default => 'topic-good'
                                };
                                $progressClass = match(true) {
                                    $grade <= 25 => 'progress-bar-critical',
                                    $grade <= 49 => 'progress-bar-struggling',
                                    $grade <= 74 => 'progress-bar-improving',
                                    default => 'progress-bar-good'
                                };
                                $buttonClass = match(true) {
                                    $grade <= 25 => 'btn-outline-danger',
                                    $grade <= 49 => 'btn-outline-warning',
                                    $grade <= 74 => 'btn-outline-info',
                                    default => 'btn-outline-success'
                                };
                                $icon = match(true) {
                                    $grade <= 25 => '<i class="fas fa-exclamation-circle text-danger me-1"></i>',
                                    $grade <= 49 => '<i class="fas fa-exclamation-triangle text-warning me-1"></i>',
                                    $grade <= 74 => '<i class="fas fa-info-circle text-info me-1"></i>',
                                    default => '<i class="fas fa-check-circle text-success me-1"></i>'
                                };
                            @endphp
                            <li class="list-group-item topic-review-item {{ $urgencyClass }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            {!! $icon !!}
                                            {{ $topic->name }}
                                        </h6>
                                        <small class="text-muted">Course: {{ $topic->course->name }}</small>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <div class="progress-bar {{ $progressClass }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $grade }}%">
                                            </div>
                                        </div>
                                        <small class="text-muted">Grade: {{ number_format($grade, 1) }}%</small>
                                    </div>
                                    <a href="{{ secure_url(route('quiz.start', ['course' => $topic->course->id, 'topic' => $topic->id], false)) }}" 
                                       class="btn btn-sm {{ $buttonClass }}">
                                        Practice <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="col-md-6">
                <div class="stats-card review-section animate-fadeInUp" style="animation-delay: 0.7s">
                    <h5><i class="fas fa-medal me-2" style="color: #FFD700;"></i>Leaderboard (Top 10)</h5>
                    @if($leaderboard->isEmpty())
                        <p class="text-muted">Start practicing to join the leaderboard!</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($leaderboard as $index => $user)
                            <li class="list-group-item topic-review-item" style="border-bottom: 1px solid rgba(0,0,0,0.05); {{ $user->id === auth()->id() ? 'background-color: rgba(126, 87, 194, 0.08); border-left-color: #7e57c2;' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rank-emoji text-muted" style="font-size: 0.9rem; min-width: 24px;">
                                            @if($index === 0)
                                                🏆
                                            @elseif($index === 1)
                                                🥈
                                            @elseif($index === 2)
                                                🥉
                                            @else
                                                #{{ $index + 1 }}
                                            @endif
                                        </span>
                                        <span>
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                            <small class="text-primary" style="font-size: 0.8rem; font-weight: 500; margin-left: 3px;">(You)</small>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="xp-counter" style="font-size: 0.9rem; color: #7e57c2; font-weight: 600;" data-value="{{ $user->xp }}">
                                            {{ number_format($user->xp) }} XP
                                        </span>
                                        <span style="color: #7e57c2; font-weight: 600; font-size: 0.85rem;">Level {{ $user->level }}</span>
                                    </div>
                                </div>
                                <div class="progress mt-1" style="height: 2px;">
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: {{ ($user->xp % 1000) / 10 }}%; background: linear-gradient(90deg, #7e57c2, #b388ff); box-shadow: 0 0 10px rgba(126, 87, 194, 0.3);">
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        
                        @if($userRank > 10)
                        <div class="text-center mt-3">
                            <p class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-arrow-up me-1"></i>
                                @php
                                    // Get the XP of the 10th ranked user
                                    $tenthPlaceXp = count($leaderboard) >= 10 ? $leaderboard[9]->xp : 0;
                                    $xpNeeded = max(0, $tenthPlaceXp - auth()->user()->xp);
                                @endphp
                                You need {{ $xpNeeded }} more XP to reach the top 10!
                            </p>
                        </div>
                        @elseif(!$userRank)
                        <div class="text-center mt-3">
                            <p class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                Start practicing to earn XP and join the leaderboard!
                            </p>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store chart instance globally
        let performanceChart;
        let distributionChart;

        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');

        // Get the chart data
        const chartData = @json($chartData);

        // Sort data by timestamp for proper line connection
        const sortedChartData = chartData.sort((a, b) => a.x - b.x);

        // Create dynamic gradient based on performance
        const createDynamicGradient = (ctx, performance) => {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            if (performance >= 90) {
                gradient.addColorStop(0, 'rgba(40, 167, 69, 0.4)');   // Green for excellent
                gradient.addColorStop(1, 'rgba(40, 167, 69, 0.0)');
            } else if (performance >= 70) {
                gradient.addColorStop(0, 'rgba(23, 162, 184, 0.4)'); // Blue for good
                gradient.addColorStop(1, 'rgba(23, 162, 184, 0.0)');
            } else if (performance >= 50) {
                gradient.addColorStop(0, 'rgba(255, 193, 7, 0.4)');  // Yellow for average
                gradient.addColorStop(1, 'rgba(255, 193, 7, 0.0)');
            } else {
                gradient.addColorStop(0, 'rgba(220, 53, 69, 0.4)');  // Red for needs improvement
                gradient.addColorStop(1, 'rgba(220, 53, 69, 0.0)');
            }
            return gradient;
        };

        // Create gradient line color based on performance ranges
        const createGradientLineColor = (ctx, dataPoints) => {
            if (dataPoints.length < 2) {
                // Single point or no data - use performance-based color
                const performance = dataPoints.length > 0 ? dataPoints[0].y : 0;
                return performance >= 90 ? '#10B981' :
                       performance >= 50 ? '#FFD700' : '#EF4444';
            }

            const gradient = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);
            dataPoints.forEach((point, index) => {
                const position = dataPoints.length > 1 ? index / (dataPoints.length - 1) : 0;
                let color;

                if (point.y >= 90) {
                    color = '#10B981';  // Green
                } else if (point.y >= 50) {
                    color = '#FFD700';  // Yellow
                } else {
                    color = '#EF4444';  // Red
                }

                gradient.addColorStop(position, color);
            });

            return gradient;
        };

        // Create clear, distinct color zones under the line
        const createSegmentedFill = (ctx, dataPoints) => {
            const gradient = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);

            // Create sharp color transitions at performance boundaries
            let lastColorStop = 0;

            dataPoints.forEach((point, index) => {
                const position = dataPoints.length > 1 ? index / (dataPoints.length - 1) : 0;

                // Determine solid color for this performance range
                let zoneColor;
                if (point.y >= 90) {
                    zoneColor = 'rgba(16, 185, 129, 0.3)';   // Solid green zone
                } else if (point.y >= 50) {
                    zoneColor = 'rgba(255, 215, 0, 0.3)';  // Solid yellow zone (50-89%)
                } else {
                    zoneColor = 'rgba(239, 68, 68, 0.3)';   // Solid red zone
                }

                // Add sharp color stops to create distinct zones
                if (index === 0) {
                    gradient.addColorStop(0, zoneColor);
                } else {
                    // Create a very narrow transition zone
                    gradient.addColorStop(position - 0.001, zoneColor);
                    gradient.addColorStop(position, zoneColor);
                }
            });

            return gradient;
        };

        // Initialize performance chart
        performanceChart = new Chart(performanceCtx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Quiz Performance',
                    data: sortedChartData,
                    borderColor: createGradientLineColor(performanceCtx, sortedChartData),
                    backgroundColor: createSegmentedFill(performanceCtx, sortedChartData),
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: (context) => {
                        const value = context.raw?.y || 0;
                        return value >= 90 ? '#10B981' :  // Emerald Green
                               value >= 50 ? '#FFD700' : '#EF4444'; // Yellow : Bright Red
                    },
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: (context) => {
                        // Make recent points larger
                        const dataLength = context.chart.data.datasets[0].data.length;
                        const index = context.dataIndex;
                        const recency = (index + 1) / dataLength;
                        return Math.max(4, Math.min(8, 4 + (recency * 4)));
                    },
                    pointHoverRadius: 12,
                    pointHoverBorderWidth: 4,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: (context) => {
                        const value = context.raw?.y || 0;
                        return value >= 90 ? '#10B981' :  // Emerald Green
                               value >= 50 ? '#FFD700' : '#EF4444'; // Yellow : Bright Red
                    },
                    pointStyle: 'circle',
                    pointShadowBlur: 10,
                    pointHoverShadowBlur: 20,
                    pointShadowColor: 'rgba(58, 123, 213, 0.5)',
                    pointHoverShadowColor: 'rgba(58, 123, 213, 0.8)',
                    transition: {
                        duration: 300
                    },
                    hoverBorderJoinStyle: 'round',
                    hoverBackgroundColor: '#fff',
                    hoverBorderColor: '#3a7bd5',
                    pointHoverStyle: {
                        shadowColor: 'rgba(58, 123, 213, 0.8)',
                        shadowBlur: 20,
                        shadowOffsetX: 0,
                        shadowOffsetY: 5
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest',
                    axis: 'xy'
                },
                layout: {
                    padding: {
                        top: 5,
                        bottom: 5,
                        left: 5,
                        right: 5
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'nearest',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: 'rgba(58, 123, 213, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12,
                        boxPadding: 6,
                        titleFont: {
                            family: "'Poppins', sans-serif",
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: "'Poppins', sans-serif",
                            size: 12
                        },
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const attempt = context.raw;
                                return [
                                    `Topic: ${attempt.topic}`,
                                    `Accuracy: ${attempt.y}%`,
                                    `Date: ${attempt.date}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            offset: true
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                family: "'Poppins', sans-serif",
                                size: 11
                            },
                            color: '#666',
                            padding: 5
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        type: 'time',
                        time: {
                            unit: 'hour',
                            displayFormats: {
                                hour: 'MMM d, h:mm a'
                            },
                            tooltipFormat: 'MMM d, yyyy h:mm a'
                        },
                        grid: {
                            display: false,
                            offset: true
                        },
                        ticks: {
                            maxRotation: 45,
                            font: {
                                family: "'Poppins', sans-serif",
                                size: 11
                            },
                            color: '#666',
                            padding: 5
                        },
                        border: {
                            display: false
                        }
                    }
                },
                elements: {
                    point: {
                        hitRadius: 25,
                        hoverRadius: 8,
                        hoverBorderWidth: 3,
                        borderWidth: 2,
                        radius: 5,
                        backgroundColor: '#3a7bd5',
                        borderColor: '#fff',
                        hoverBackgroundColor: '#fff',
                        hoverBorderColor: '#3a7bd5'
                    },
                    line: {
                        tension: 0.4,
                        borderWidth: 3,
                        borderCapStyle: 'round',
                        borderJoinStyle: 'round',
                        capBezierPoints: true,
                        fill: true
                    }
                },
                hover: {
                    mode: 'nearest',
                    intersect: false,
                    animationDuration: 300
                },
                transitions: {
                    active: {
                        animation: {
                            duration: 300,
                            easing: 'easeOutQuart'
                        }
                    }
                }
            }
        });

        // Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        const correctCount = {{ auth()->user()->correct_answers_count }};
        const incorrectCount = {{ $totalQuestions - auth()->user()->correct_answers_count }};
        const totalCount = {{ $totalQuestions }};
        
        distributionChart = new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    `Correct (${Math.round(correctCount/totalCount * 100)}%)`,
                    `Incorrect (${Math.round(incorrectCount/totalCount * 100)}%)`
                ],
                datasets: [{
                    data: [correctCount, incorrectCount],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Profile picture upload handling - wrapped in DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {

            // Profile picture upload
            const profilePicInput = document.getElementById('profilePictureInput');
            if (profilePicInput) {
                document.getElementById('profilePictureContainer').addEventListener('click', function() {
                    profilePicInput.click();
                });

                profilePicInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('profile_picture', file);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    fetch(document.getElementById('profileForm').action, {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json())
                      .then(data => {
                          if (data.new_url) {
                              document.getElementById('profileImage').src = data.new_url + '?t=' + Date.now();
                          }
                          if (data.message) alert(data.message);
                      });
                });
            }
        });

        // Confetti animation for goal achievement
        function createConfetti() {
            const container = document.querySelector('.confetti-container');
            if (!container) return;
            
            const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: absolute;
                    width: 8px;
                    height: 8px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    left: ${Math.random() * 100}%;
                    top: -10px;
                    border-radius: 50%;
                    animation: confettiFall ${2 + Math.random() * 3}s linear infinite;
                    animation-delay: ${Math.random() * 2}s;
                    opacity: 0.8;
                `;
                container.appendChild(confetti);
            }
        }

        // Initialize confetti if goal is achieved
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.confetti-container')) {
                createConfetti();
            }
        });

        // Study Goals Modal Functions
        function openGoalModal() {
            const currentGoal = {{ auth()->user()->weekly_goal ?? 50 }};
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal fade show" id="goalModal" style="display: block; background: rgba(0,0,0,0.6);" onclick="if(event.target === this) closeGoalModal()">
                    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                            <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding: 1.5rem;">
                                <h5 class="modal-title" style="font-weight: 700; font-size: 1.3rem; color: #1e293b;">
                                    Set Weekly Goal
                                </h5>
                                <button type="button" class="btn-close" onclick="closeGoalModal()"></button>
                            </div>
                            <div class="modal-body" style="padding: 2rem;">
                                <div class="mb-4">
                                    <label class="form-label" style="font-weight: 600; color: #475569; font-size: 0.95rem;">Questions per week</label>
                                    <input type="number" class="form-control" id="goalInput" value="${currentGoal}" 
                                           min="10" max="500" step="5"
                                           style="font-size: 1.5rem; padding: 1rem; border: 2px solid #cbd5e1; border-radius: 10px; text-align: center; font-weight: 700;">
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="setQuickGoal(25)" style="padding: 0.75rem; font-weight: 600;">25 questions</button>
                                    <button class="btn btn-outline-primary" onclick="setQuickGoal(50)" style="padding: 0.75rem; font-weight: 600;">50 questions</button>
                                    <button class="btn btn-outline-primary" onclick="setQuickGoal(100)" style="padding: 0.75rem; font-weight: 600;">100 questions</button>
                                    <button class="btn btn-outline-primary" onclick="setQuickGoal(150)" style="padding: 0.75rem; font-weight: 600;">150 questions</button>
                                </div>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1.5rem; gap: 10px;">
                                <button type="button" class="btn btn-secondary" onclick="closeGoalModal()" style="flex: 1; padding: 0.75rem; font-weight: 600;">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveGoal()" style="flex: 1; padding: 0.75rem; font-weight: 600; background: #007bff; border: none;">
                                    Save Goal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function closeGoalModal() {
            const modal = document.getElementById('goalModal');
            if (modal) {
                modal.closest('.modal').remove();
            }
        }

        function setQuickGoal(value) {
            document.getElementById('goalInput').value = value;
        }

        function saveGoal() {
            const goal = parseInt(document.getElementById('goalInput').value);
            
            if (goal < 10 || goal > 500) {
                alert('Please enter a goal between 10 and 500 questions');
                return;
            }

            // Save via AJAX
            fetch('{{ route("profile.update-goal") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ weekly_goal: goal })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeGoalModal();
                    location.reload(); // Refresh to show new goal
                } else {
                    alert('Failed to save goal. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error saving goal:', error);
                alert('Failed to save goal. Please try again.');
            });
        }

        // ==================== SMOOTH STUDY PLANNER - SINGLE VIEW ====================
        let plannerDate = new Date();
        let plans = {};
        let selectedDate = null;
        let weakTopicsCache = [];
        let saveTimeout = null; // For debounced saving
        
        // Ensure showSuccessMessage is available
        if (typeof showSuccessMessage === 'undefined') {
            window.showSuccessMessage = function(message) {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 15px 20px;
                    border-radius: 5px;
                    z-index: 9999;
                    font-family: 'Poppins', sans-serif;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    font-size: 14px;
                    max-width: 300px;
                `;
                notification.textContent = message;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            };
        }

        async function openStudyPlanner() {
            await loadPlans();
            showPlannerModal();
        }

        async function loadPlans() {
            const month = plannerDate.toISOString().slice(0, 7);
            try {
                const res = await fetch(`/study-planner/month?month=${month}`);
                const data = await res.json();
                if (data.success) {
                    plans = {};
                    data.plans.forEach(p => {
                        const d = p.plan_date.split('T')[0];
                        plans[d] = {
                            daily_goal: p.daily_goal || 50,
                            notes: p.notes || '',
                            is_exam_day: p.is_exam_day === 1 || p.is_exam_day === true || p.is_exam_day === '1',
                            is_committed: p.is_committed === 1 || p.is_committed === true || p.is_committed === '1',
                            tasks: []
                        };
                        if (data.progress[d]) {
                            plans[d].completed = data.progress[d];
                            plans[d].done = p.daily_goal > 0 && data.progress[d] >= p.daily_goal;
                        }
                        if (p.tasks) {
                            plans[d].tasks = typeof p.tasks === 'string' ? JSON.parse(p.tasks) : p.tasks;
                        }
                    });
                }
            } catch (e) {
                console.error('Error loading plans:', e);
            }
        }

        function showPlannerModal() {
            const y = plannerDate.getFullYear();
            const m = plannerDate.getMonth();
            const first = new Date(y, m, 1).getDay();
            const days = new Date(y, m + 1, 0).getDate();
            const today = new Date();

            let cal = '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;margin-top:1rem;">';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => cal += `<div style="text-align:center;font-weight:600;color:#6b7280;padding:8px 0;">${d}</div>`);

            for (let i = 0; i < first; i++) cal += '<div></div>';

            for (let day = 1; day <= days; day++) {
                const date = new Date(y, m, day);
                const ds = `${y}-${String(m + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isToday = date.toDateString() === today.toDateString();
                const p = plans[ds];

                let bg = '#ffffff', border = '#e5e7eb', hoverBorder = '#3b82f6';
                const taskCount = p?.tasks?.length || 0;

                if (p?.is_exam_day) {
                    bg = '#fee2e2';
                    border = '#ef4444';
                    hoverBorder = '#dc2626'; // Darker red on hover
                } else if (isToday) {
                    bg = '#eff6ff';
                    border = '#3b82f6';
                    hoverBorder = '#2563eb'; // Darker blue on hover
                }

                cal += `<div onclick="selectDay('${ds}')" style="min-height:80px;border:2px solid ${border};border-radius:8px;padding:8px;cursor:pointer;background:${bg};transition:all 0.2s;" onmouseover="this.style.borderColor='${hoverBorder}';this.style.transform='scale(1.02)'" onmouseout="this.style.borderColor='${border}';this.style.transform='scale(1)'">
                    <div style="font-weight:600;color:#374151;margin-bottom:4px;">${day}</div>
                    ${taskCount > 0 ? `<div style="font-size:0.8rem;color:#6b7280;">${taskCount} tasks</div>` : ''}
                </div>`;
            }
            cal += '</div>';

            const modal = `<div class="modal fade show" id="plannerModal" style="display:block;background:rgba(0,0,0,0.4);">
                <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:900px;max-height:80vh;">
                    <div class="modal-content" style="border-radius:12px;">
                        <div class="modal-header">
                            <h5 class="modal-title" style="font-weight:600;">Study Planner</h5>
                            <button type="button" class="btn-close" onclick="closePlanner()"></button>
                        </div>
                        <div class="modal-body" style="padding:0;max-height:calc(80vh - 80px);overflow:hidden;">
                            <div style="display:flex;">
                                <div style="flex:1;padding:1.5rem;border-right:1px solid #e5e7eb;overflow-y:auto;max-height:calc(80vh - 80px);">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="prevMonth()"><i class="fas fa-chevron-left"></i></button>
                                        <span style="font-weight:600;">${plannerDate.toLocaleDateString('en-US', {month:'long',year:'numeric'})}</span>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
                                    </div>
                                    ${cal}
                                </div>
                                <div id="detailsPanel" style="width:350px;padding:1.5rem;background:#f8f9fa;display:flex;align-items:center;justify-content:center;max-height:calc(80vh - 80px);overflow-y:auto;">
                                    <div style="text-align:center;color:#6c757d;">
                                        <i class="fas fa-calendar-day" style="font-size:2rem;margin-bottom:1rem;opacity:0.5;"></i>
                                        <p>Select a day to manage tasks</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modal);
        }

        function selectDay(ds) {
            selectedDate = ds;
            const p = plans[ds] || { tasks: [], daily_goal: 50, notes: '', is_exam_day: false, is_committed: false };
            if (!plans[ds]) {
                plans[ds] = p;
            }
            
            // Fix: Parse date correctly to avoid timezone offset issues
            const [year, month, day] = ds.split('-').map(Number);
            const dateObj = new Date(year, month - 1, day);
            const tasks = p.tasks || [];
            const completedCount = tasks.filter(t => t.completed).length;
            const totalCount = tasks.length;
            
            let tasksHtml = '';
            tasks.forEach((task, idx) => {
                tasksHtml += `<div style="display:flex;align-items:center;gap:8px;padding:8px;background:white;border-radius:6px;margin-bottom:6px;border:1px solid ${task.completed ? '#10b981' : '#e5e7eb'};">
                    <input type="checkbox" ${task.completed ? 'checked' : ''} onchange="toggleTask(${idx})" style="width:18px;height:18px;cursor:pointer;accent-color:#10b981;">
                    <input type="text" value="${task.text}" onchange="updateTaskText(${idx}, this.value)" style="flex:1;border:none;background:transparent;font-size:0.9rem;color:#374151;outline:none;${task.completed ? 'text-decoration:line-through;color:#9ca3af;' : ''}" placeholder="Task...">
                    <button onclick="removeTask(${idx})" style="background:none;border:none;color:#9ca3af;cursor:pointer;padding:4px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
            });
            
            const detailsHtml = `
                <div style="width:100%;height:100%;">
                    <div style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #e5e7eb;">
                        <div style="font-weight:600;font-size:1.1rem;color:#111827;margin-bottom:0.5rem;">${dateObj.toLocaleDateString('en-US', {weekday:'short',month:'short',day:'numeric',year:'numeric'})}</div>
                        ${totalCount > 0 ? `<div style="font-size:0.85rem;color:#6b7280;">${completedCount} of ${totalCount} completed</div>` : '<div style="font-size:0.85rem;color:#9ca3af;">No tasks</div>'}
                    </div>
                    
                    <!-- Quick Add: Weak Topics -->
                    <div id="quickAddTopics_${ds}" style="margin-bottom:1rem;"></div>
                    
                    <div style="margin-bottom:1rem;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                            <label style="font-weight:600;color:#111827;font-size:0.95rem;margin:0;">Tasks</label>
                            <button onclick="addTask('${ds}')" style="background:#3b82f6;color:white;border:none;border-radius:6px;padding:6px 14px;font-size:0.85rem;cursor:pointer;font-weight:500;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        <div id="tasksList_${ds}" style="max-height:280px;overflow-y:auto;padding-right:4px;">
                            ${tasksHtml || '<div style="text-align:center;padding:2rem 1rem;color:#d1d5db;font-size:0.85rem;"><i class="fas fa-tasks" style="font-size:2rem;margin-bottom:0.5rem;opacity:0.3;display:block;"></i>Click Add to create a task</div>'}
                        </div>
                    </div>
                    
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;transition:all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                            <input type="checkbox" id="examToggle_${ds}" ${p.is_exam_day ? 'checked' : ''} onchange="updateExamDayAndRefresh('${ds}');" style="width:18px;height:18px;cursor:pointer;accent-color:#ef4444;">
                            <div style="flex:1;">
                                <div style="font-size:0.9rem;color:#991b1b;font-weight:600;">Mark as Exam Day</div>
                                <div style="font-size:0.75rem;color:#b91c1c;margin-top:2px;">Highlights day with red glow</div>
                            </div>
                        </label>
                    </div>
                    
                    <button onclick="savePlan('${ds}')" style="width:100%;background:#10b981;color:white;border:none;border-radius:6px;padding:12px;font-weight:600;cursor:pointer;font-size:0.95rem;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                        <i class="fas fa-check"></i> Save Plan
                    </button>
                </div>
            `;
            
            document.getElementById('detailsPanel').innerHTML = detailsHtml;
            
            // Load quick add buttons for weak topics
            loadQuickAddTopics(ds);
        }

        // Load quick add buttons for weak topics
        async function loadQuickAddTopics(ds) {
            // Early check for planner modal existence - prevent errors from stale modal references
            if (!document.getElementById('plannerModal')) {
                console.log('Planner modal not found, skipping weak topics load');
                return;
            }

            try {
                // Check cache first, but ensure it's an array
                if (!Array.isArray(weakTopicsCache) || weakTopicsCache.length === 0) {
                    try {
                        const res = await fetch('/study-planner/weak-topics');
                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                        }
                        const data = await res.json();
                        if (data.success && Array.isArray(data.weak_topics)) {
                            weakTopicsCache = data.weak_topics;
                        } else {
                            // If no weak topics data, clear cache to indicate API not available
                            weakTopicsCache = null;
                        }
                    } catch (error) {
                        console.warn('Failed to load weak topics:', error.message);
                        console.error('Error loading weak topics:', error);
                        weakTopicsCache = null;
                        const errorElement = document.getElementById(`quickAddTopics_${ds}`);
                        if (errorElement) {
                            errorElement.innerHTML = `
                                <div style="background:linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);border-radius:8px;padding:12px;border:2px solid #ef4444;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <i class="fas fa-exclamation-triangle" style="color:#dc2626;font-size:1rem;"></i>
                                        <span style="font-size:0.9rem;font-weight:700;color:#b91c1c;">Weak topics unavailable</span>
                                    </div>
                                    <div style="font-size:0.75rem;color:#dc2626;margin-top:4px;">Unable to load weak topics. Please try again later.</div>
                                </div>
                            `;
                        } else {
                            console.warn(`Error element quickAddTopics_${ds} not found for error display`);
                        }
                        return;
                    }
                }

                // Safety check - if cache is null or empty array, don't show anything
                if (weakTopicsCache === null || !Array.isArray(weakTopicsCache) || weakTopicsCache.length === 0) {
                    document.getElementById(`quickAddTopics_${ds}`).innerHTML = '';
                    return;
                }

                // Filter out topics already in tasks with safe property access
                const p = plans[ds];
                const existingTaskTexts = (p && p.tasks) ? p.tasks.map(t => (t && t.text ? t.text.toLowerCase() : '')).filter(text => text) : [];
                const availableTopics = weakTopicsCache.filter(topic => {
                    // Safely check if topic exists and has required properties
                    if (!topic || typeof topic !== 'object') return false;

                    // Safely get topic name
                    const topicName = topic.name || topic.topic_name || '';
                    if (!topicName || typeof topicName !== 'string') return false;

                    const topicLower = topicName.toLowerCase();
                    const topicWithStudyPrefix = `study ${topicLower}`;

                    // Check if this topic is already in existing tasks
                    return !existingTaskTexts.includes(topicWithStudyPrefix) &&
                           !existingTaskTexts.includes(topicLower);
                }).slice(0, 5); // Show top 5

                // Check if container element exists before setting content
                const containerElement = document.getElementById(`quickAddTopics_${ds}`);
                if (!containerElement) {
                    console.warn(`Container element quickAddTopics_${ds} not found`);
                    return;
                }

                if (availableTopics.length > 0) {
                    let quickAddHtml = `
                        <div style="background:linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);border-radius:8px;padding:12px;border:2px solid #fbbf24;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <i class="fas fa-bolt" style="color:#b45309;font-size:1rem;"></i>
                                    <span style="font-size:0.9rem;font-weight:700;color:#78350f;">Quick Add: Weak Topics</span>
                                </div>
                                <span style="font-size:0.7rem;color:#92400e;background:rgba(255,255,255,0.7);padding:2px 8px;border-radius:10px;font-weight:600;">Click to add</span>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;">`;

                    availableTopics.forEach(topic => {
                        // Safely get topic properties
                        const topicName = topic.name || topic.topic_name || 'Unknown Topic';
                        const avgGrade = topic.avg_grade || topic.grade || topic.percentage || 0;
                        const grade = Math.round(Number(avgGrade));

                        if (isNaN(grade)) return; // Skip invalid grades

                        const urgencyColor = grade < 40 ? '#dc2626' : grade < 55 ? '#ea580c' : '#f59e0b';

                        // Escape single quotes for JavaScript
                        const escapedTopicName = topicName.replace(/'/g, "\\'").replace(/"/g, '\\"');

                        quickAddHtml += `
                            <button onclick="addWeakTopicTask('${ds}', '${escapedTopicName}', ${grade})"
                                style="background:white;border:2px solid ${urgencyColor};border-radius:20px;padding:6px 12px;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;gap:6px;box-shadow:0 2px 4px rgba(0,0,0,0.1);"
                                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';this.style.background='${urgencyColor}';this.style.color='white'"
                                onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';this.style.background='white';this.style.color='#374151'">
                                <i class="fas fa-plus-circle" style="font-size:0.85rem;"></i>
                                <span style="font-size:0.8rem;font-weight:600;">${topicName}</span>
                                <span style="font-size:0.7rem;background:${urgencyColor};color:white;padding:2px 6px;border-radius:8px;font-weight:700;">${grade}%</span>
                            </button>`;
                    });

                    quickAddHtml += `</div></div>`;
                    if (containerElement) {
                        containerElement.innerHTML = quickAddHtml;
                    } else {
                        console.warn(`Could not set quick add content for element quickAddTopics_${ds} - element not found`);
                    }
                } else {
                    if (containerElement) {
                        containerElement.innerHTML = '';
                    }
                }
            } catch (e) {
                console.error('Error loading weak topics:', e);
                // Ensure we don't show any content on error
                const elementId = `quickAddTopics_${ds}`;
                const element = document.getElementById(elementId);
                if (element) {
                    element.innerHTML = '';
                } else {
                    console.warn(`Could not clear error content for element ${elementId} - element not found`);
                }
            }
        }

        // Add weak topic as task with one click
        function addWeakTopicTask(ds, topicName, grade) {
            const p = plans[ds];
            if (!p.tasks) p.tasks = [];
            p.tasks.push({ 
                text: `Study ${topicName}`, 
                completed: false 
            });
            selectDay(ds);
            refreshCalendar();
            showSuccessMessage(`✓ Added "${topicName}" to your plan!`);
        }

        function closePlanner() {
            document.getElementById('plannerModal')?.remove();
        }

        function addTask(ds) {
            const p = plans[ds];
            if (!p.tasks) p.tasks = [];
            p.tasks.push({ text: '', completed: false });
            selectDay(ds);
            refreshCalendar();
            setTimeout(() => {
                const inputs = document.querySelectorAll(`#tasksList_${ds} input[type="text"]`);
                inputs[inputs.length - 1]?.focus();
            }, 100);
        }

        async function refreshCalendar() {
            // Force reload plans from server to sync with backend changes
            await loadPlans();
            // Re-render the calendar to show updated tasks
            closePlanner();
            showPlannerModal();
            if (selectedDate) {
                selectDay(selectedDate);
            }
        }

        function toggleTask(idx) {
            const p = plans[selectedDate];
            if (p && p.tasks && p.tasks[idx]) {
                p.tasks[idx].completed = !p.tasks[idx].completed;
                selectDay(selectedDate);
                refreshCalendar();
            }
        }

        function updateTaskText(idx, text) {
            const p = plans[selectedDate];
            if (p && p.tasks && p.tasks[idx]) {
                p.tasks[idx].text = text;
                refreshCalendar();
            }
        }

        function removeTask(idx) {
            const p = plans[selectedDate];
            if (p && p.tasks) {
                p.tasks.splice(idx, 1);
                selectDay(selectedDate);
                // Auto-save silently
                savePlan(selectedDate, true);
            }
        }

        function updateExamDay(ds) {
            plans[ds].is_exam_day = document.getElementById(`examToggle_${ds}`).checked;
        }

        function updateExamDayAndRefresh(ds) {
            plans[ds].is_exam_day = document.getElementById(`examToggle_${ds}`).checked;
            // Auto-save silently
            savePlan(ds, true);
        }

        // Debounced save function - collects changes and saves after a delay
        function savePlan(ds, silent = false) {
            // Clear any existing timeout
            if (saveTimeout) {
                clearTimeout(saveTimeout);
            }

            // Set new timeout for debounced save
            saveTimeout = setTimeout(async () => {
                try {
                    const p = plans[ds];
                    if (!p) {
                        console.error('No plan data for date:', ds);
                        return;
                    }

                    const res = await fetch('/study-planner/save', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            plan_date: ds,
                            daily_goal: p.daily_goal || 50,
                            notes: p.notes || '',
                            topics: [],
                            is_committed: p.is_committed || false,
                            is_exam_day: p.is_exam_day || false,
                            tasks: JSON.stringify(p.tasks || [])
                        })
                    });

                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }

                    const data = await res.json();
            if (data.success) {
                if (!silent) {
                    showSuccessMessage('✓ Plan saved!');
                    await loadPlans();
                    closePlanner();
                    openStudyPlanner();
                } else {
                    // For silent saves, update the local plan data without closing modal
                    await loadPlans();
                }
            } else {
                throw new Error(data.message || 'Failed to save plan');
            }
                } catch (e) {
                    console.error('Error saving plan:', e);
                    if (!silent) {
                        alert('Failed to save. Please try again.');
                    }
                } finally {
                    saveTimeout = null;
                }
            }, 300); // 300ms debounce delay
        }

        async function deletePlan(ds) {
            if (!confirm('Delete this plan?')) return;
            try {
                const res = await fetch('/study-planner/delete', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify({plan_date: ds})
                });
                if ((await res.json()).success) {
                    delete plans[ds];
                    showSuccessMessage('✓ Plan deleted!');
                    closePlanner();
                    openStudyPlanner();
                }
            } catch (e) {
                alert('Failed to delete. Please try again.');
            }
        }

        function prevMonth() { plannerDate.setMonth(plannerDate.getMonth() - 1); closePlanner(); openStudyPlanner(); }
        function nextMonth() { plannerDate.setMonth(plannerDate.getMonth() + 1); closePlanner(); openStudyPlanner(); }

    </script>

</body>
</html>
