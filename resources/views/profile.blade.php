<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ auth()->user()->name }}'s Profile - MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
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
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.08);
            transition: all 0.3s ease;
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
            margin-bottom: 8px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            background: linear-gradient(135deg, #2b4c8c 0%, #1e88e5 50%, #039be5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-size: 200% auto;
            animation: gradientShift 8s ease infinite;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 12px;
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
            transform-origin: 70% 70%;
            animation: wave 2.5s ease infinite;
            position: relative;
            top: -2px;
            transition: transform 0.3s ease;
            cursor: pointer;
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

        /* Garland Wire Effect */
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -5%;
            right: -5%;
            height: 2px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.5) 50%,
                transparent 100%
            );
            filter: drop-shadow(0 0 2px rgba(255, 255, 255, 0.8));
            z-index: 1;
        }

        /* Christmas Lights Effect */
        @keyframes lightTwinkle {
            0%, 100% { opacity: 1; transform: scale(1); filter: brightness(1); }
            50% { opacity: 0.7; transform: scale(0.8); filter: brightness(0.8); }
        }

        @keyframes wireSwing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(2px); }
        }

        .garland {
            position: absolute;
            top: -5px;
            left: 0;
            right: 0;
            height: 15px;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            animation: wireSwing 3s ease-in-out infinite;
            z-index: 2;
        }

        .light {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 15px;
            animation: lightTwinkle 1.5s ease-in-out infinite;
            box-shadow: 0 0 10px currentColor;
        }

        .light:nth-child(5n+1) { color: #ff4136; animation-delay: 0s; }    /* Red */
        .light:nth-child(5n+2) { color: #2ecc40; animation-delay: 0.3s; }  /* Green */
        .light:nth-child(5n+3) { color: #ffdc00; animation-delay: 0.6s; }  /* Gold */
        .light:nth-child(5n+4) { color: #39cccc; animation-delay: 0.9s; }  /* Blue */
        .light:nth-child(5n+5) { color: #ff6f61; animation-delay: 1.2s; }  /* Coral */

        /* Wire Curve Effect */
        .wire-curve {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            z-index: 1;
            overflow: hidden;
        }

        .wire-curve::after {
            content: '';
            position: absolute;
            top: -15px;
            left: -5%;
            right: -5%;
            height: 20px;
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
            animation: wireSwing 3s ease-in-out infinite;
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

        /* Remove all the fancy animations and transitions */
        .profile-picture:active {
            border-color: #0056b3;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
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
        }

        .accuracy-card {
            background: linear-gradient(135deg, #FF7043, #FF5252);
            color: white;
        }

        .study-time-card {
            background: linear-gradient(135deg, #FF9A9E, #FAD0C4);
            color: white;
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

</style>
    </style>
</head>
<body>
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
            @if(auth()->user()->is_admin)
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
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">
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
            <div class="garland">
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
                <div class="light"></div>
            </div>
            <div class="wire-curve"></div>
            <div class="profile-picture">
                <img src="{{ auth()->user()->profile_picture_url ? asset(auth()->user()->profile_picture_url) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" 
                     alt="Profile Picture" id="profileImage">
                <div class="upload-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            <form action="{{ route('profile.update-picture') }}" method="POST" enctype="multipart/form-data" id="profileForm" style="display: none;">
                @csrf
                <input type="file" 
                       name="profile_picture" 
                       id="profilePictureInput" 
                       accept="image/*"
                       style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0;">
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
                            <small>Topic Quizzes</small>
                            <span>{{ $formattedStudyTimes['quiz'] }}</span>
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

        <!-- Charts Row -->
        <div class="row mt-4">
            <!-- Performance Over Time -->
            <div class="col-md-8">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.5s">
                    <h5>Performance Over Time</h5>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Study Distribution -->
            <div class="col-md-4">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.6s">
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
                                    <a href="{{ route('quiz.start', ['course' => $topic->course->id, 'topic' => $topic->id]) }}" 
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
                                            0 XP
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
        const gradient = performanceCtx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(0, 123, 255, 0.4)');
        gradient.addColorStop(1, 'rgba(0, 123, 255, 0.0)');

        // Get the chart data
        const chartData = @json($chartData);

        // Initialize performance chart
        performanceChart = new Chart(performanceCtx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Quiz Performance',
                    data: chartData,
                    borderColor: '#3a7bd5',
                    backgroundColor: gradient,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3a7bd5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#3a7bd5',
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Listen for quiz completion events
        document.addEventListener('DOMContentLoaded', function() {
            // WebSocket or Server-Sent Events connection would go here
            
            // For now, listen for the quiz completion event
            window.addEventListener('quizCompleted', function(event) {
                console.log('Quiz completed event received:', event.detail);
                const quizData = event.detail;
                
                // Add new data point with smooth animation
                const newDataPoint = {
                    x: Date.now(),
                    y: quizData.percentage_grade,
                    topic: quizData.topic_name || 'Random Quiz',
                    date: new Date().toLocaleString()
                };
                
                performanceChart.data.datasets[0].data.push(newDataPoint);
                
                // Animate the new point with a smooth transition
                const lastPoint = performanceChart.data.datasets[0].data.length - 1;
                const originalColors = Array(lastPoint).fill('#3a7bd5');
                
                performanceChart.data.datasets[0].pointBackgroundColor = [...originalColors, '#00d4ff'];
                performanceChart.data.datasets[0].pointBorderColor = [...Array(lastPoint).fill('#fff'), '#fff'];
                performanceChart.data.datasets[0].pointHoverBackgroundColor = [...Array(lastPoint).fill('#fff'), '#fff'];
                performanceChart.data.datasets[0].pointHoverBorderColor = [...Array(lastPoint).fill('#3a7bd5'), '#00d4ff'];
                
                // Update with smooth animation
                performanceChart.update('active');
                
                // Reset point colors after animation
                setTimeout(() => {
                    performanceChart.data.datasets[0].pointBackgroundColor = '#3a7bd5';
                    performanceChart.data.datasets[0].pointBorderColor = '#fff';
                    performanceChart.data.datasets[0].pointHoverBackgroundColor = '#fff';
                    performanceChart.data.datasets[0].pointHoverBorderColor = '#3a7bd5';
                    performanceChart.update('none');
                }, 2000);

                // Update distribution chart
                const currentCorrect = distributionChart.data.datasets[0].data[0];
                const currentIncorrect = distributionChart.data.datasets[0].data[1];
                
                // Add the new correct and incorrect answers
                const newCorrect = currentCorrect + quizData.correct_answers;
                const newIncorrect = currentIncorrect + (quizData.total_questions - quizData.correct_answers);
                const newTotal = newCorrect + newIncorrect;
                
                distributionChart.data.datasets[0].data = [newCorrect, newIncorrect];
                distributionChart.data.labels = [
                    `Correct (${Math.round(newCorrect/newTotal * 100)}%)`,
                    `Incorrect (${Math.round(newIncorrect/newTotal * 100)}%)`
                ];
                
                distributionChart.update('show');

                // Update stats cards if they exist
                const accuracyValue = document.querySelector('.accuracy-card .stats-value');
                if (accuracyValue) {
                    const newAccuracy = Math.round((newCorrect / newTotal) * 100);
                    accuracyValue.textContent = `${newAccuracy}%`;
                }

                const questionsValue = document.querySelector('.stats-card:last-child .stats-value');
                if (questionsValue) {
                    questionsValue.textContent = newTotal;
                }
            });
        });

        // Profile picture upload handling
        document.addEventListener('DOMContentLoaded', function() {
            const profilePictureInput = document.getElementById('profilePictureInput');
            const profileImage = document.getElementById('profileImage');
            const profileForm = document.getElementById('profileForm');
            const profilePicture = document.querySelector('.profile-picture');

            if (!profilePictureInput || !profileImage || !profileForm || !profilePicture) {
                console.error('One or more elements not found:', {
                    profilePictureInput: !!profilePictureInput,
                    profileImage: !!profileImage,
                    profileForm: !!profileForm,
                    profilePicture: !!profilePicture
                });
                return;
            }

            // Remove any existing click handlers
            profilePicture.replaceWith(profilePicture.cloneNode(true));
            const newProfilePicture = document.querySelector('.profile-picture');
            
            // Add the click handler
            newProfilePicture.addEventListener('click', function(e) {
                console.log('Profile picture clicked - triggering file input');
                e.preventDefault();
                e.stopPropagation();
                
                // Create and trigger a new click event on the file input
                const clickEvent = new MouseEvent('click', {
                    view: window,
                    bubbles: true,
                    cancelable: true
                });
                profilePictureInput.dispatchEvent(clickEvent);
            });

            profilePictureInput.addEventListener('click', function(e) {
                console.log('File input clicked');
            });

            profilePictureInput.addEventListener('change', function(e) {
                console.log('File input changed:', e.target.files);
                const file = e.target.files[0];
                if (file) {
                    console.log('File selected:', file.name, file.type, file.size);
                    
                    // Validate file type and size
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    const maxSize = 2048 * 1024; // 2MB

                    if (!validTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPEG, PNG, or GIF)');
                        this.value = '';
                        return;
                    }

                    if (file.size > maxSize) {
                        alert('File size must be less than 2MB');
                        this.value = '';
                        return;
                    }

                    // Show preview and submit immediately
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        console.log('File read successfully');
                        profileImage.src = e.target.result;
                        console.log('Submitting form...');
                        profileForm.submit();
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        // XP counter animation
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.xp-counter');
            
            counters.forEach(counter => {
                const targetValue = parseInt(counter.dataset.value);
                let currentValue = 0;
                const duration = 1500; // 1.5 seconds
                const steps = 60;
                const increment = targetValue / steps;
                const stepDuration = duration / steps;
                
                function updateCounter() {
                    if (currentValue < targetValue) {
                        currentValue = Math.min(currentValue + increment, targetValue);
                        counter.textContent = Math.round(currentValue).toLocaleString() + ' XP';
                        counter.classList.add('updating');
                        
                        setTimeout(() => {
                            counter.classList.remove('updating');
                        }, 500);

                        if (currentValue < targetValue) {
                            setTimeout(updateCounter, stepDuration);
                        }
                    }
                }
                
                // Start the counter animation when the element comes into view
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                observer.observe(counter);
            });
        });

        // Wave emoji interaction
        document.getElementById('waveEmoji').addEventListener('click', function() {
            this.style.animation = 'none';
            this.offsetHeight; // Trigger reflow
            this.style.animation = 'quickWave 0.5s ease';
            
            // Reset to original animation after quick wave
            setTimeout(() => {
                this.style.animation = 'wave 2.5s ease infinite';
            }, 500);
        });

        // Trigger notification refresh if a new notification was created
        @if(session('notification_created'))
        window.dispatchEvent(new CustomEvent('weakTopicAdded', {
            detail: {
                type: '{{ session('notification_type') }}',
                count: {{ session('notification_count') }}
            }
        }));
        @endif
    </script>
</body>
</html>

