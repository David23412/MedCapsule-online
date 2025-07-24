<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
            zoom: 0.73; /* Add zoom out for better overall view */
        }

        .navbar {
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,249,250,0.95) 70%, rgba(248,249,250,0.8) 100%);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: none;
            padding: 0.4rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 2rem;
            color: #007bff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
            margin-left: -60px;
        }

        .navbar-brand:hover {
            color: #007bff;
            transform: scale(1.05);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .nav-link {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .nav-link:hover {
            color: #007bff;
        }

        /* Dropdown fixes */
        .dropdown-menu {
            display: none;
            position: absolute;
            background: white;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 200px;
            z-index: 1000;
            pointer-events: auto;
        }

        .dropdown-menu-end {
            transform: translateX(-400px) !important;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            clear: both;
            text-align: inherit;
            white-space: nowrap;
            background: none;
            border: none;
            border-radius: 8px;
            color: #212529;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            z-index: 2;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196f3;
            transform: translateX(5px);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            pointer-events: none;
        }

        .dropdown-divider {
            height: 0;
            margin: 0.5rem 0;
            overflow: hidden;
            border-top: 1px solid #e9ecef;
            opacity: 0.1;
            pointer-events: none;
        }

        /* Fix dropdown button style */
        form {
            margin: 0;
            padding: 0;
        }

        .dropdown-item[type="submit"] {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font: inherit;
            margin: 0;
            padding: 0.5rem 1rem;
            color: inherit;
            position: relative;
            z-index: 2;
        }

        .nav-item.dropdown {
            position: relative;
        }

        .nav-item.dropdown .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .nav-item.dropdown .badge {
            padding: 0.35em 0.65em;
            pointer-events: none;
        }

        /* Ensure dropdown items are clickable */
        .dropdown-menu li {
            position: relative;
            margin: 0;
            padding: 0;
        }

        .dropdown-menu li a,
        .dropdown-menu li button {
            outline: none;
            position: relative;
            z-index: 2;
        }

        /* Remove any pointer events from icons to prevent interference */
        .dropdown-menu li a i,
        .dropdown-menu li button i {
            pointer-events: none;
        }

        /* GOLDEN STANDARD Streak Indicator */
        .streak-indicator {
            background: #f9f9f9;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin: 10px auto;
            padding: 5px;
            transition: all 0.3s ease;
            width: 80px;
            height: 80px;
            position: relative;
            margin: 1rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            backdrop-filter: blur(12px);
            box-shadow: 0 0 25px rgba(25, 118, 210, 0.25);
        }

        .streak-indicator:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 
                0 12px 40px rgba(33, 150, 243, 0.25),
                0 4px 12px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .streak-circle {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .streak-circle svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
            filter: drop-shadow(0 0 8px rgba(51, 154, 240, 0.4));
        }

        .streak-circle circle {
            fill: none;
            stroke-width: 3;
            stroke-linecap: round;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .streak-bg {
            stroke: rgba(33, 150, 243, 0.15);
        }

        .streak-progress {
            stroke: #2196f3;
            stroke-dasharray: 251.2;
            stroke-dashoffset: 251.2;
            transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 0 6px rgba(33, 150, 243, 0.6));
        }

        .streak-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }

        .streak-value {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1565c0, #2196f3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            line-height: 1;
            margin-bottom: 0.2rem;
            text-shadow: 0 0 25px rgba(33, 150, 243, 0.6);
            animation: glowPulse 2s infinite;
        }

        .streak-label {
            font-size: 0.55rem;
            background: linear-gradient(135deg, #1565c0, #2196f3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.95;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: glowPulse 2s infinite 0.5s;
            margin-left: 2px;
        }

        @keyframes valueGlow {
            0%, 100% { 
                filter: drop-shadow(0 2px 4px rgba(33, 150, 243, 0.3));
                transform: scale(1);
            }
            50% { 
                filter: drop-shadow(0 4px 8px rgba(33, 150, 243, 0.5));
                transform: scale(1.02);
            }
        }

        @keyframes glowPulse {
            0% { 
                filter: brightness(1) drop-shadow(0 0 10px rgba(25, 118, 210, 0.5));
                transform: scale(1);
            }
            50% { 
                filter: brightness(1.4) drop-shadow(0 0 15px rgba(25, 118, 210, 0.7));
                transform: scale(1.05);
            }
            100% { 
                filter: brightness(1) drop-shadow(0 0 10px rgba(25, 118, 210, 0.5));
                transform: scale(1);
            }
        }

        .streak-fire {
            position: absolute;
            bottom: -2px;
            right: -2px;
            color: #ff6b6b;
            animation: flame 1.5s infinite;
            font-size: 1rem;
        }

        @keyframes flame {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.2) rotate(5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        /* Match notification bell to reference image */
        .clean-notification-bell {
            width: 50px !important;
            height: 50px !important;
        }

        .clean-notification-bell i {
            font-size: 25px !important;
        }



        .hero-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 6rem 2rem 3rem; /* Further reduced padding */
            text-align: center;
            position: relative;
            margin-bottom: 2rem; /* Reduced margin */
            overflow: hidden;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            opacity: 0.6; /* Reduced opacity for subtler background */
        }

        .floating-element {
            position: absolute;
            background: linear-gradient(45deg, rgba(33, 150, 243, 0.15), rgba(33, 150, 243, 0.05));
            border-radius: 50%;
            filter: blur(3px);
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(100px, 50px) rotate(90deg); }
            50% { transform: translate(50px, 100px) rotate(180deg); }
            75% { transform: translate(-50px, 50px) rotate(270deg); }
        }

        .hero-title {
            font-size: 1.75rem; /* Smaller base size */
            font-weight: 600;
            color: #2196f3;
            margin-bottom: 0.75rem;
            position: relative;
            letter-spacing: -0.01em;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        @supports (-webkit-touch-callout: none) {
            /* Safari-specific styles */
            .hero-title {
                font-size: 1.5rem !important; /* Force smaller size in Safari */
                max-height: 2.5rem; /* Limit height */
                line-height: 1.2;
            }
            .hero-section {
                padding: 5rem 2rem 2.5rem; /* Even more compact in Safari */
            }
        }

        .courses-grid {
            display: flex;
            gap: 2rem; /* Increased gap between cards */
            padding: 1.5rem 3rem; /* Increased padding */
            margin: 1rem 3rem 2rem; /* Added bottom margin */
            max-width: 100%;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            position: relative;
            scroll-snap-type: x mandatory;
            padding-bottom: 20px; /* Reduced from 30px */
            -webkit-overflow-scrolling: touch;
        }

        .courses-grid::-webkit-scrollbar {
            display: none;
        }

        .scroll-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px; /* Increased size */
            height: 48px; /* Increased size */
            background: rgba(255, 255, 255, 0.98);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .scroll-arrow:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-50%) scale(1.05);
            opacity: 1;
        }

        .courses-grid:hover .scroll-arrow {
            opacity: 0.8;
        }

        .scroll-left {
            left: 20px; /* Increased distance from edge */
        }

        .scroll-right {
            right: 20px; /* Increased distance from edge */
        }

        .scroll-arrow i {
            color: #2196f3;
            font-size: 1.4rem; /* Increased size */
        }

        .course-card {
            flex: 0 0 420px; /* Increased from 400px */
            scroll-snap-align: start;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            width: 420px; /* Increased from 400px */
            margin-bottom: 3rem;
            transform: translateY(0);
            position: relative;
            /* Dynamic border system using CSS custom properties */
            border: 2px solid var(--course-color, #3b82f6);
            border-left: 8px solid var(--course-color, #3b82f6);
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border: 2px solid var(--course-color, #3b82f6);
            border-radius: 18px; /* Slightly larger than card border-radius */
            z-index: -1;
            opacity: 0.3;
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-15px); /* Subtle upward lift on hover */
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .course-image {
            position: relative;
            height: 520px; /* Increased from 500px to match wider cards */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .info-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: help;
            z-index: 3;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-icon i {
            font-size: 12px;
            color: #007bff;
        }

        .info-tooltip {
            position: absolute;
            top: 40px;
            right: 0;
            width: 220px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.85rem;
            color: #444;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: all 0.2s ease;
            line-height: 1.4;
            z-index: 4;
            pointer-events: none;
        }

        .info-icon:hover {
            background: white;
            transform: scale(1.05);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .info-icon:hover .info-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .course-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            background: none;
            z-index: 2;
        }

        .course-title {
            font-size: 2.6rem; /* Increased from 2.4rem */
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.3; /* Increased line height */
            font-family: 'Inter', 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            z-index: 2;
            mix-blend-mode: normal;
            margin-bottom: 1rem; /* Added margin */
        }

        /* Remove all hardcoded course title color styles */
        .course-card.anatomy .course-title,
        .course-card.histology .course-title,
        .course-card.physiology .course-title,
        .course-card.biochemistry .course-title {
            color: inherit !important;
        }

        .course-info {
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            background: white;
        }

        .course-stats-row {
            display: flex;
            align-items: center;
            margin-top: 12px;
        }

        .topics-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #777;
            font-weight: 400;
            margin-right: 16px;
        }

        .topics-badge i {
            font-size: 1rem;
            color: #2196f3;
        }

        .enrolled-users-preview {
            display: flex;
            align-items: center;
            margin-left: 4px;
        }

        .course-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
            font-size: 0.875rem;
        }

        .stat-item i {
            color: #2196f3;
        }

        .enrolled-users {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid white;
            background: #e5e7eb;
            margin-left: -8px;
            position: relative;
            background-size: cover;
            background-position: center;
        }

        .user-avatar:first-child {
            margin-left: 0;
        }

        .more-users {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid white;
            background: #2196f3;
            margin-left: -8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .progress-container {
            margin-top: 3rem; /* Add space above progress bar */
            padding-top: 1rem; /* Add padding above progress bar */
            width: 100%;
            padding: 1px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 2px;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #34D399, #059669);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .progress-percentage {
            display: none;
        }

        .action-btn {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            cursor: pointer;
            background: none;
        }

        .action-btn.continue {
            background: #16a34a;
            color: white;
        }

        .action-btn.continue:hover {
            background: #15803d;
            transform: translateY(-2px);
        }

        .action-btn.continue i {
            color: white;
        }

        .action-btn.enroll {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .action-btn.enroll:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        .action-btn.enroll i {
            color: #9ca3af;
        }

        /* Green styling for free courses */
        .action-btn.enroll.free {
            background: #16a34a;
            color: white;
            border: 1px solid #16a34a;
        }

        .action-btn.enroll.free:hover {
            background: #15803d;
            transform: translateY(-2px);
        }

        .action-btn.enroll.free i {
            color: white;
        }

        .action-btn.enroll[disabled] {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
            cursor: not-allowed;
            transform: none;
            font-weight: 500;
        }

        .action-btn.enroll[disabled]:hover {
            background: #fff7ed;
            transform: none;
        }

        .action-btn.enroll[disabled] i {
            color: #ea580c;
        }

        .unlock-alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            text-align: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 400px;
            width: 90%;
        }

        .unlock-alert.show {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        .unlock-alert h3 {
            color: #2196f3;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .unlock-alert p {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .contact-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1rem 0;
        }

        .phone-number, .email {
            font-size: 1.1rem;
            color: #34A853;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .email {
            color: #2196f3;
        }

        .backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .floating-capsule {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 240px;
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.2), rgba(33, 150, 243, 0.05));
            border-radius: 60px;
            filter: blur(2px);
            animation: floatCapsule 8s infinite ease-in-out;
            z-index: 0;
        }

        @keyframes floatCapsule {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            25% { transform: translate(-45%, -55%) rotate(5deg); }
            75% { transform: translate(-55%, -45%) rotate(-5deg); }
        }

        .course-progress {
            display: none;
        }

        .enrolled-text {
            display: none;
        }

        /* Dynamic course card styling - colors set via CSS custom properties */
        .course-title {
            color: var(--course-title-color, #FFFFFF) !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Milestone Progress Bar */
        .milestone-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            max-width: 95vw;
            background: white;
            border-radius: 10px;
            padding: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .milestone-container:hover {
            transform: translateX(-50%) translateY(-2px);
        }

        .milestone-stats {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
            color: #1f2937;
            font-weight: 600;
        }

        .correct-count {
            font-weight: 700;
            color: #4f46e5;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .correct-count.pulse {
            transform: scale(1.1);
            color: #6366f1;
        }

        .goal-count {
            font-weight: 700;
            font-size: 1.05rem;
        }

        .milestone-progress {
            position: relative;
            height: 20px;
            background: #f3f4f6;
            border-radius: 10px;
            overflow: hidden;
            transition: height 0.3s ease;
        }

        .milestone-container:hover .milestone-progress {
            height: 22px;
        }

        .liquid-progress {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            border-radius: 10px;
        }

        .liquid-fill {
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #6366f1);
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .liquid-wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: 100%;
            animation: wave 4s linear infinite;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transform: translateX(-50%);
        }

        .liquid-wave:nth-child(2) {
            animation-delay: 0.5s;
            opacity: 0.3;
        }

        .liquid-wave:nth-child(3) {
            animation-delay: 1s;
            opacity: 0.2;
        }

        @keyframes wave {
            0% { transform: translateX(-50%) rotate(0deg); }
            100% { transform: translateX(-50%) rotate(360deg); }
        }

        .milestone-marks {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .milestone-mark {
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateX(-50%) scale(0.9);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .milestone-mark:hover {
            transform: translateX(-50%) scale(1.1);
        }

        .milestone-mark i {
            font-size: 0.65rem;
            color: #d1d5db;
            transition: all 0.3s ease;
            opacity: 0;
            transform: scale(0);
        }

        .milestone-mark.active {
            background: #10b981;
            border-color: #059669;
            transform: translateX(-50%) scale(1);
        }

        .milestone-mark.active i {
            color: white;
            opacity: 1;
            transform: scale(1);
        }

        /* Confetti Animation */
        @keyframes confetti-fall {
            0% { transform: translateY(0) rotate(0deg) scale(0); opacity: 0; }
            10% { transform: translateY(-20px) rotate(45deg) scale(1); opacity: 1; }
            50% { transform: translateY(20px) rotate(180deg) scale(0.8); opacity: 1; }
            100% { transform: translateY(60px) rotate(360deg) scale(0.2); opacity: 0; }
        }

        .confetti {
            position: absolute;
            width: 8px;
            height: 8px;
            pointer-events: none;
            animation: confetti-fall 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        /* Fireworks Animation */
        @keyframes firework-explode {
            0% { transform: scale(0); opacity: 0; }
            25% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        .fireworks {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0) 0%, rgba(99, 102, 241, 0) 40%, rgba(99, 102, 241, 0.3) 60%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            z-index: -1;
            animation: firework-explode 1s ease-out forwards;
            pointer-events: none;
        }

        /* Add this to make the progress bar glow when leveling up */
        .liquid-fill.lava {
            background: linear-gradient(90deg, #f97316, #ef4444);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.6);
        }

        /* Add celebration styles for the milestone container */
        .milestone-container.celebrate {
            box-shadow: 0 0 30px rgba(79, 70, 229, 0.6);
        }

        .profile-circle {
            width: 45px; /* Match reference image size */
            height: 45px; /* Match reference image size */
            background: #2196f3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem; /* Proportional font */
            transition: transform 0.2s ease;
            background-size: cover;
            background-position: center;
            text-decoration: none;
            margin-left: 0;
        }

        .profile-circle.no-image {
            background: #2196f3;
        }

        .profile-circle:hover {
            transform: scale(1.1);
            color: white;
            text-decoration: none;
        }

        /* Add these styles for the enrolled users preview */
        .enrolled-users-preview {
            display: flex;
            align-items: center;
            margin-left: 4px;
        }

        .profile-circle-small {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #00d4ff);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: -8px;
            border: 2px solid white;
            background-size: cover;
            background-position: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-circle-small.more {
            background: #f0f0f0;
            color: #666;
        }

        .topics-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #777;
            font-weight: 400;
            margin-right: 16px;
        }

        .topics-badge i {
            font-size: 1rem;
            color: #2196f3;
        }

        .position-relative {
            padding: 0 1rem;
        }

        /* Semester Pills Styles */
        .semester-pills {
            display: flex;
            gap: 0.75rem;
            margin: 0 2rem 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .semester-dropdown {
            padding: 0.4rem 1rem;
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid rgba(0, 123, 255, 0.2);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23007bff' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            padding-right: 2rem;
            min-width: 200px;
        }

        /* Ensure the default "All Courses" option is visible and dropdown shows text properly */
        .semester-dropdown option:first-child {
            font-weight: 600;
            color: #007bff;
        }

        /* Make sure dropdown text is always visible */
        .semester-dropdown {
            text-align: left;
            text-indent: 0.5rem;
        }

        /* Ensure dropdown shows selected text properly */
        .semester-dropdown:invalid {
            color: #007bff;
        }

        .semester-dropdown:hover {
            background-color: rgba(0, 123, 255, 0.2);
            color: #0056b3;
            transform: translateY(-1px);
            border-color: rgba(0, 123, 255, 0.3);
        }

        .semester-dropdown:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            border-color: #007bff;
        }

        /* Keep existing styles for reference but they won't be used */
        .semester-pill {
            /* Existing code kept for reference */
            display: none;
        }

        .semester-pill:hover {
            /* Existing code kept for reference */
        }

        .semester-pill.active {
            /* Existing code kept for reference */
        }

        .semester-pill.active:hover {
            /* Existing code kept for reference */
        }

        /* Notification Bell Styles - Exact same as home page */
        .notification-bell button {
            background: transparent;
            border: none;
            outline: none;
            position: relative;
            transition: all 0.3s ease;
            width: 60px; /* MUCH bigger to match reference */
            height: 60px; /* MUCH bigger to match reference */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-top: -1px; /* Better alignment with profile circle */
        }

        .notification-bell i {
            color: #007bff;
            font-size: 2.2rem; /* MUCH bigger icon to match reference */
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .notification-bell button:hover {
            background: rgba(0, 123, 255, 0.08);
        }

        .notification-bell button:active i {
            transform: scale(0.9);
            transition: transform 0.15s ease;
        }

        .notification-bell .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.7rem;
            padding: 0.15em 0.35em;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f44336;
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(244, 67, 54, 0.5);
            animation: notificationPulse 2s infinite;
            z-index: 10;
        }

        @keyframes notificationPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 2px 5px rgba(244, 67, 54, 0.5);
            }
            50% {
                transform: scale(1.1); 
                box-shadow: 0 2px 8px rgba(244, 67, 54, 0.7);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 2px 5px rgba(244, 67, 54, 0.5);
            }
        }
        
        .notification-dropdown {
            position: absolute;
            top: 155%;
            right: -90px;
            width: 380px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 420px;
            transform-origin: top right;
        }

        .notification-header {
            border-bottom: 1px solid rgba(33, 150, 243, 0.1);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }
        
        .notification-header h6 {
            margin: 0;
            font-weight: 600;
            color: #343a40;
            font-size: 1rem;
        }

        .mark-all-read {
            background: none;
            border: none;
            color: #007bff;
            font-size: 0.8rem;
            cursor: pointer;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .mark-all-read:hover {
            background-color: #e6f2ff;
            transform: translateY(-1px);
        }

        .notification-list {
            overflow-y: auto;
            flex: 1;
            scrollbar-width: thin;
            scrollbar-color: #e0e0e0 #f5f5f5;
            max-height: 330px;
            padding: 0.5rem 0;
        }

        .notification-list::-webkit-scrollbar {
            width: 3px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 10px;
        }

        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f3f5;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
            opacity: 0;
            animation: appear-in 0.5s forwards;
            animation-delay: var(--appear-delay, 0s);
            background-color: #ffffff;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        @keyframes appear-in {
            0% { 
                opacity: 0; 
                transform: translateY(10px);
            }
            100% { 
                opacity: 1; 
                transform: translateY(0);
            }
        }

        .notification-item-appear {
            animation: appear-in 0.5s forwards;
            animation-delay: var(--appear-delay, 0s);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }

        .notification-unread {
            border-left: 3px solid #2196f3;
            background-color: #ffffff;
        }

        .notification-unread:hover {
            background-color: #ffffff;
        }

        /* Urgent notification styling */
        .notification-urgent {
            position: relative;
        }

        .notification-urgent:not(.notification-unread)::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #ffcc00;
        }

        .notification-urgent.notification-unread {
            background-color: #ffffff;
            border-left: 3px solid #dc3545;
        }

        .notification-urgent.notification-unread:hover {
            background-color: #ffffff;
        }

        @keyframes icon-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .icon-pulse {
            animation: icon-pulse 1.5s infinite;
        }

        .notification-icon {
            font-size: 1.2rem;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.04);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .notification-item:hover .notification-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #343a40;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 0.95rem;
        }

        .notification-time {
            color: #6c757d;
            font-size: 0.75rem;
            white-space: nowrap;
            margin-left: 0.5rem;
            font-weight: normal;
        }

        .notification-message {
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
            color: #495057;
            line-height: 1.4;
        }

        .notification-action-hint {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.3rem;
            animation: fade-in-up 1s ease infinite alternate;
        }

        @keyframes fade-in-up {
            0% { opacity: 0.7; transform: translateY(3px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .empty-notification {
            padding: 2.5rem 1.5rem;
            text-align: center;
            color: #6c757d;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            background-color: #ffffff;
            border-radius: 8px;
        }

        @keyframes bellRing {
            0%, 100% { transform: rotate(0); }
            10%, 30%, 50%, 70% { transform: rotate(10deg); }
            20%, 40%, 60% { transform: rotate(-10deg); }
            80% { transform: rotate(5deg); }
            90% { transform: rotate(-5deg); }
        }

        .notification-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #6c757d;
        }

        .loading-spinner {
            width: 2rem;
            height: 2rem;
            border: 3px solid rgba(0, 123, 255, 0.2);
            border-top-color: #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Bundle Purchase Button Styles */
        .bundle-purchase-btn {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.25);
            margin-left: auto;
        }
        
        .bundle-purchase-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(79, 70, 229, 0.3);
            background: linear-gradient(135deg, #4338ca, #4f46e5);
        }
        
        .bundle-purchase-btn i {
            margin-right: 0.5rem;
        }
        
        /* Bundle Modal Styles */
        .bundle-modal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .bundle-modal .modal-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .bundle-modal .modal-title {
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .bundle-modal .modal-body {
            padding: 1.5rem;
        }
        
        .course-selection {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .course-checkbox {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .course-checkbox:hover {
            background: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .course-checkbox.selected {
            border-color: #4f46e5;
            background: rgba(79, 70, 229, 0.05);
        }
        
        .course-checkbox input {
            margin-right: 0.8rem;
            width: 18px;
            height: 18px;
            accent-color: #4f46e5;
        }
        
        .bundle-pricing {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .price-row.total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .discount {
            color: #10b981;
            font-weight: 500;
        }
        
        .bundle-btn {
            width: 100%;
            background: #4f46e5;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .bundle-btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }
        
        .bundle-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Add this CSS to the existing styles section */
        .action-btn.enroll:disabled {
            background: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .action-btn.enroll:disabled:hover {
            transform: none;
            background: #f8f9fa;
        }

        .action-btn.enroll:disabled i {
            color: #6c757d;
        }

        /* Add profile circle styles */
        .nav-item {
            display: flex;
            align-items: center;
            margin-left: 1rem;
        }



        .navbar-nav {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center" style="margin-right: -120px !important;"
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Sign Up</a>
                        </li>
                    @else
                        <li class="nav-item dropdown me-1">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                {{ Auth::user()->name }}
                                @if(Auth::user()->role === 'admin')
                                    <span class="badge bg-warning ms-1">Admin</span>
                                @elseif(Auth::user()->role === 'tutor')
                                    <span class="badge bg-info ms-1">Tutor</span>
                                @else
                                    <span class="badge bg-primary ms-1">Student</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="d-flex justify-content-center">
                                    <div class="streak-indicator" data-streak="{{ Auth::user()->study_streak_days ?? 0 }}">
                                        <div class="streak-circle">
                                            <svg viewBox="0 0 100 100">
                                                <circle class="streak-bg" cx="50" cy="50" r="40"></circle>
                                                <circle class="streak-progress" cx="50" cy="50" r="40"></circle>
                                            </svg>
                                            <div class="streak-content">
                                                <div class="streak-value">{{ Auth::user()->study_streak_days ?? 0 }}</div>
                                                <div class="streak-label">Streak</div>
                                            </div>
                                            @if((Auth::user()->study_streak_days ?? 0) > 0)
                                                <div class="streak-fire">
                                                    <i class="fas fa-fire"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('courses.index') }}">
                                        <i class="fas fa-book me-2"></i>My Courses
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('review.index') }}">
                                        <i class="fas fa-exclamation-circle me-2"></i>My Mistakes
                                    </a>
                                </li>
                                @if(Auth::user()->role === 'admin')
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">
                                        <i class="fas fa-crown me-2 text-warning"></i>Admin Controls
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assignments.index') }}">
                                            <i class="fas fa-clipboard-list me-2"></i>Assignment Management
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assignments.create') }}">
                                            <i class="fas fa-plus me-2"></i>Create Assignment
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('topic-locking.index') }}">
                                            <i class="fas fa-lock me-2"></i>Topic Management
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('tutor.dashboard') }}">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Tutor Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.user-course') }}">
                                            <i class="fas fa-users-cog me-2"></i>Manage Access
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('add-topic.form') }}">
                                            <i class="fas fa-plus-circle me-2"></i>Add Topic
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('add-question.form') }}">
                                            <i class="fas fa-question-circle me-2"></i>Add Question
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('add-course.form') }}">
                                            <i class="fas fa-graduation-cap me-2"></i>Add Course
                                        </a>
                                    </li>
                                @endif
                                @if(Auth::user()->role === 'tutor')
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('tutor.dashboard') }}">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Tutor Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assignments.index') }}">
                                            <i class="fas fa-clipboard-list me-2"></i>My Assignments
                                        </a>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile') }}">
                                        <i class="fas fa-user me-2"></i>Profile
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ secure_url(route('logout', [], false)) }}" class="dropdown-item p-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item w-100 text-danger">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        <!-- Notification Bell -->
                        <li class="nav-item me-1">
                            @include('components.notification-bell')
                        </li>
                        
                        <li class="nav-item me-5">
                            <a href="{{ route('profile') }}" class="nav-link">
                                <div class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');' : '' }}">
                                    @if(!Auth::user()->profile_picture_url)
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="floating-elements">
            @for($i = 0; $i < 5; $i++)
                <div class="floating-element" style="
                    width: {{ rand(100, 300) }}px;
                    height: {{ rand(100, 300) }}px;
                    top: {{ rand(-50, 100) }}%;
                    left: {{ rand(-50, 100) }}%;
                    animation-delay: -{{ $i * 5 }}s;
                "></div>
            @endfor
        </div>
        <div class="floating-capsule"></div>
        <h1 class="hero-title">Medical Courses</h1>
    </section>

    <div class="position-relative">
        <div class="semester-pills">
            <select class="semester-dropdown" id="semesterDropdown" onchange="filterCoursesByAcademicYear()">
                <option value="">All Courses</option>
                @php
                    $availableYears = $courses->pluck('academic_year')->filter()->unique()->sort();
                    $medicalYears = $availableYears->filter(function($year) {
                        return str_starts_with($year, 'year-');
                    });
                    $testPrep = $availableYears->filter(function($year) {
                        return in_array($year, ['sat-prep', 'est-prep', 'act-prep']);
                    });
                    
                    $yearLabels = [
                        'year-1-sem-1' => 'Year 1 - First Semester',
                        'year-1-sem-2' => 'Year 1 - Second Semester',
                        'year-2-sem-1' => 'Year 2 - First Semester',
                        'year-2-sem-2' => 'Year 2 - Second Semester',
                        'year-3-sem-1' => 'Year 3 - First Semester',
                        'year-3-sem-2' => 'Year 3 - Second Semester',
                        'year-4-sem-1' => 'Year 4 - First Semester',
                        'year-4-sem-2' => 'Year 4 - Second Semester',
                        'year-5-sem-1' => 'Year 5 - First Semester',
                        'year-5-sem-2' => 'Year 5 - Second Semester',
                        'year-6-sem-1' => 'Year 6 - First Semester',
                        'year-6-sem-2' => 'Year 6 - Second Semester',
                        'sat-prep' => 'SAT Preparation',
                        'est-prep' => 'EST Preparation',
                        'act-prep' => 'ACT Preparation'
                    ];
                @endphp
                
                @if($medicalYears->isNotEmpty())
                    <optgroup label="Medical School Years">
                        @foreach($medicalYears as $year)
                            <option value="{{ $year }}">{{ $yearLabels[$year] ?? $year }}</option>
                        @endforeach
                    </optgroup>
                @endif
                
                @if($testPrep->isNotEmpty())
                    <optgroup label="Standardized Tests">
                        @foreach($testPrep as $year)
                            <option value="{{ $year }}">{{ $yearLabels[$year] ?? $year }}</option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
            
            @guest
            @else
                <button class="bundle-purchase-btn ms-auto" id="bundlePurchaseBtn">
                    <i class="fas fa-shopping-cart me-2"></i>Bundle Purchase
                </button>
            @endguest
        </div>

        <button class="scroll-arrow scroll-left" onclick="scrollCourses('left')">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="courses-grid">
            @foreach ($courses as $course)
                <div class="course-card" 
                     style="--course-color: {{ $course->color }}; --course-title-color: {{ $course->title_color }}"
                     data-academic-year="{{ $course->academic_year }}"
                     data-semester="{{ $course->semester }}">
                    <div class="course-image" style="background-image: url('{{ $course->image_url }}')" onerror="this.style.backgroundImage = 'linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%)'">
                        <div class="info-icon">
                            <i class="fas fa-info"></i>
                            <div class="info-tooltip">
                                {{ $course->description }}
                            </div>
                        </div>
                        <div class="course-overlay">
                            <h3 class="course-title">{{ $course->name }}</h3>
                        </div>
                    </div>
                    <div class="course-info">
                        <div class="course-stats-row">
                            <span class="topics-badge">
                                <i class="fas fa-book-open me-1"></i>
                                {{ $course->topics_count }} Topics
                            </span>
                            <div class="enrolled-users-preview">
                                @foreach($course->enrolled_preview as $user)
                                    <div class="profile-circle-small" 
                                         @if($user['profile_picture_url'])
                                             style="background-image: url('{{ asset($user['profile_picture_url']) }}')"
                                         @endif
                                         title="{{ $user['name'] }}">
                                        @if(!$user['profile_picture_url'])
                                            {{ $user['initial'] }}
                                        @endif
                                    </div>
                                @endforeach
                                @if($course->has_more)
                                    <div class="profile-circle-small more" title="More enrolled users">
                                        +{{ $course->enrolled_users_count - 3 }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $course->completion_percentage }}%">
                                    <span class="progress-percentage">{{ number_format($course->completion_percentage, 0) }}%</span>
                                </div>
                            </div>
                        </div>
                        @if (auth()->check())
                            @if (Auth::user()->role === 'admin')
                                <a href="{{ route('topics.forCourse', $course->id) }}" class="action-btn continue">
                                    <i class="fas fa-play"></i>
                                    Continue Learning
                                </a>
                            @elseif ($course->is_enrolled)
                                <a href="{{ route('topics.forCourse', $course->id) }}" class="action-btn continue">
                                    <i class="fas fa-play"></i>
                                    Continue Learning
                                </a>
                            @elseif (!$course->price || $course->price <= 0)
                                <a href="{{ route('topics.forCourse', $course->id) }}" class="action-btn enroll free">
                                    <i class="fas fa-play"></i>
                                    Start Learning (Free)
                                </a>
                            @else
                                <button type="button" class="action-btn enroll show-payment-modal" id="paymentBtn_{{ $course->id }}" data-course-id="{{ $course->id }}" @if($course->has_pending_payment) disabled @endif>
                                    @if($course->has_pending_payment)
                                        <i class="fas fa-clock"></i>
                                        <span style="color: #c2410c;">Payment Pending</span>
                                    @else
                                        <i class="fas fa-unlock"></i>
                                        <span>Pay to Access</span>
                                    @endif
                                </button>
                            @endif
                        @else
                            @if (!$course->price || $course->price <= 0)
                                <a href="{{ route('login') }}" class="action-btn enroll free">
                                    <i class="fas fa-play"></i>
                                    Sign in to Start (Free)
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="action-btn enroll">
                                    <i class="fas fa-lock"></i>
                                    Sign in to Access
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <button class="scroll-arrow scroll-right" onclick="scrollCourses('right')">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <div class="backdrop" id="backdrop" onclick="hideUnlockInstructions()"></div>
    <div class="unlock-alert" id="unlockAlert">
        <h3>Course Access Required</h3>
        <p>This course requires administrator approval. Please contact your institution's administrator to request access to this course.</p>
        <div class="contact-info">
            <div class="email mt-2">
                <i class="fas fa-envelope"></i>
                <a href="mailto:admin@medcapsule.com">admin@medcapsule.com</a>
            </div>
        </div>
        <p class="mt-3 small text-muted">Once approved, you will receive an email notification and can start learning immediately.</p>
        <button class="btn btn-primary mt-3" onclick="hideUnlockInstructions()">Close</button>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal content will be loaded dynamically -->
                <div class="modal-body text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading payment options...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Milestone Progress -->
    <div class="milestone-container">
        <div class="milestone-stats">
            <span class="correct-count" id="correctCount">{{ Auth::user()->correct_answers_count ?? 0 }}</span>
            <span class="separator">/</span>
            <span class="goal-count" id="goalCount">100</span>
            <span class="questions-text">questions solved</span>
        </div>
        <div class="milestone-progress">
            <div class="liquid-progress">
                <div class="liquid-fill" id="progressFill" style="width: {{ min(((Auth::user()->correct_answers_count ?? 0) % 100), 100) }}%">
                    <div class="liquid-wave"></div>
                    <div class="liquid-wave"></div>
                    <div class="liquid-wave"></div>
                </div>
            </div>
            <div class="milestone-marks">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="milestone-mark {{ (Auth::user()->correct_answers_count ?? 0) >= ($i * 20) ? 'active' : '' }}" 
                         style="left: {{ $i * 20 }}%" 
                         id="mark-{{ $i * 20 }}">
                        <i class="fas fa-check"></i>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Bundle Purchase Modal -->
    <div class="modal fade bundle-modal" id="bundleModal" tabindex="-1" aria-labelledby="bundleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bundleModalLabel">
                        <i class="fas fa-shopping-cart me-2"></i>Purchase Course Bundle
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Select multiple courses to purchase as a bundle and save money with our discounted pricing.</p>
                    
                    <div class="course-selection">
                        @foreach ($courses as $course)
                            @if (!$course->is_enrolled)
                                <label class="course-checkbox" for="course-{{ $course->id }}">
                                    <input type="checkbox" id="course-{{ $course->id }}" class="bundle-course-checkbox" 
                                           data-course-id="{{ $course->id }}" 
                                           data-course-name="{{ $course->name }}" 
                                           data-course-price="{{ $course->price ?? 200 }}">
                                    <div>
                                        <strong>{{ $course->name }}</strong>
                                        <div class="text-muted">Individual Price: {{ $course->formatted_price ?? '200 EGP' }}</div>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    
                    <div class="bundle-pricing">
                        <h6 class="mb-3">Bundle Summary</h6>
                        <div class="price-row">
                            <span>Original Price:</span>
                            <span id="originalPrice">0 EGP</span>
                        </div>
                        <div class="price-row">
                            <span>Bundle Discount:</span>
                            <span class="discount" id="bundleDiscount">0 EGP</span>
                        </div>
                        <div class="price-row total">
                            <span>Total:</span>
                            <span id="totalPrice">0 EGP</span>
                        </div>
                    </div>
                    
                    <button id="proceedBundlePayment" class="bundle-btn" disabled>
                        Proceed to Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script>
        // Same notification bell logic from the home page
        function notificationBell() {
            return {
                notifications: [],
                isOpen: false,
                unreadCount: 0,
                loading: false,
                lastFetched: 0,
                notificationSound: new Audio('/sounds/notification-sound.mp3'),
                prevUnreadCount: 0, // Track previous unread count to detect changes
                
                init() {
                    // Preload the notification sound
                    this.notificationSound.load();
                    
                    // Set user interaction flag - ensures sound can play in browsers
                    this.setUserInteractionFlag();
                    
                    // Store initial unread count
                    this.fetchNotifications().then(() => {
                        this.prevUnreadCount = this.unreadCount;
                    });
                    
                    // Set up a polling mechanism to check for new notifications periodically
                    // Use a more efficient approach by checking less frequently when the tab is not active
                    let pollInterval = 30000; // Check every 30 seconds (increased frequency)
                    
                    const checkForNewNotifications = () => {
                        // Only fetch if the tab is visible and it's been at least 60 seconds since the last fetch
                        if (document.visibilityState === 'visible' && Date.now() - this.lastFetched >= 60000) {
                            this.fetchNotifications();
                        }
                        setTimeout(checkForNewNotifications, pollInterval);
                    };
                    
                    // Adjust poll interval based on tab visibility
                    document.addEventListener('visibilitychange', () => {
                        pollInterval = document.visibilityState === 'visible' ? 30000 : 180000; // 30 sec when visible, 3 minutes when hidden
                    });
                    
                    setTimeout(checkForNewNotifications, pollInterval);
                    
                    // Play notification sound on new notifications
                    this.$watch('unreadCount', (newCount, oldCount) => {
                        // Only play sound if count increases
                        if (newCount > oldCount && oldCount !== 0) {
                            this.playNotificationSound();
                            
                            // Add bell animation
                            const bellIcon = this.$el.querySelector('i.fa-bell');
                            if (bellIcon) {
                                bellIcon.style.animation = 'none';
                                // Force reflow
                                void bellIcon.offsetWidth;
                                bellIcon.style.animation = 'bellRing 1s';
                            }
                        }
                    });
                },
                
                // Track user interaction to allow audio playback
                setUserInteractionFlag() {
                    window.addEventListener('click', () => {
                        if (!window.hasUserInteraction) {
                            window.hasUserInteraction = true;
                        }
                    }, { once: true });
                },
                
                playNotificationSound() {
                    if (window.hasUserInteraction) {
                        try {
                            this.notificationSound.volume = 0.5; // Set volume to 50%
                            this.notificationSound.play().catch(e => console.log('Sound play error:', e));
                        } catch (e) {
                            console.log('Error playing notification sound:', e);
                        }
                    }
                },
                
                getTypeIcon(type) {
                    switch(type) {
                        case 'achievement':
                        case 'correct_answers_milestone':
                            return 'fas fa-award text-warning';
                        case 'streak':
                        case 'study_streak':
                            return 'fas fa-fire text-danger';
                        case 'milestone':
                            return 'fas fa-trophy text-success';
                        case 'topic_mastery':
                            return 'fas fa-graduation-cap text-success';
                        case 'review_mistakes':
                            return 'fas fa-redo text-primary';
                        case 'weak_topics':
                        case 'weak_topic_added':
                            return 'fas fa-exclamation-triangle text-danger';
                        case 'leaderboard_rank':
                            return 'fas fa-crown text-warning';
                        case 'performance':
                            return 'fas fa-chart-line text-info';
                        case 'quiz_completed':
                            return 'fas fa-check-circle text-success';
                        case 'course_welcome':
                            return 'fas fa-book text-primary';
                        case 'course_progress':
                            return 'fas fa-running text-info';
                        case 'review_reminder':
                            return 'fas fa-history text-secondary';
                        case 'info':
                        default:
                            return 'fas fa-bell text-primary';
                    }
                },
                
                fetchNotifications() {
                    if (this.loading) return Promise.resolve();
                    
                    this.loading = true;
                    this.lastFetched = Date.now();
                    
                    // Add a timestamp to bust cache
                    const url = `/notifications?t=${new Date().getTime()}`;
                    
                    return fetch(url, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        credentials: 'same-origin' // Include cookies for authentication
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Check if there are new notifications by comparing counts
                        const oldUnreadCount = this.unreadCount;
                        
                        // Store all notifications but we'll only display up to 5 in the UI
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                        
                        // If first load, store initial count
                        if (this.prevUnreadCount === 0) {
                            this.prevUnreadCount = this.unreadCount;
                        }
                        
                        return data;
                    })
                    .catch(error => console.error('Error fetching notifications:', error))
                    .finally(() => {
                        this.loading = false;
                    });
                },
                
                toggleNotifications() {
                    this.isOpen = !this.isOpen;
                    
                    // If opening notifications panel, make sure we have the latest
                    if (this.isOpen) {
                        // If it's been more than 30 seconds since last fetch, get fresh data
                        if (Date.now() - this.lastFetched >= 30000) {
                            this.fetchNotifications();
                        }
                    }
                },
                
                markAllAsRead() {
                    if (this.unreadCount === 0) return;
                    
                    fetch('/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.notifications.forEach(notification => {
                                notification.is_read = true;
                            });
                            this.unreadCount = 0;
                        }
                    })
                    .catch(error => console.error('Error marking notifications as read:', error));
                }
            };
        }
        
        // Initialize variables to track correct answers and goal level
        let correctAnswers = {{ Auth::user()->correct_answers_count ?? 0 }};
        let goalLevel = Math.floor(correctAnswers / 100) + 1;
        let currentGoal = goalLevel * 100;

        // Update the initial display
        document.getElementById('goalCount').textContent = currentGoal;
        document.getElementById('correctCount').textContent = correctAnswers;

        function updateProgressFill() {
            const progressFill = document.querySelector('.liquid-fill');
            const progress = (correctAnswers % 100) / 100 * 100;
            progressFill.style.width = `${progress}%`;

            const marks = document.querySelectorAll('.milestone-mark');
            marks.forEach((mark, index) => {
                const milestone = (index + 1) * 20;
                const isActive = (correctAnswers % 100) >= milestone;
                mark.classList.toggle('active', isActive);
            });
        }

        function levelUpGoal() {
            const progressFill = document.querySelector('.liquid-fill');
            const goalCountEl = document.getElementById('goalCount');
            const correctCountEl = document.getElementById('correctCount');
            const milestoneContainer = document.querySelector('.milestone-container');

            goalLevel++;
            currentGoal = goalLevel * 100;

            progressFill.style.transition = 'width 0.7s cubic-bezier(0.4, 1.6, 0.6, 1)';
            progressFill.style.width = '100%';

            progressFill.classList.add('lava');
            milestoneContainer.classList.add('celebrate');

            // These functions might be missing in your code
            try {
                if (typeof addCelebrationEffect === 'function') {
                    addCelebrationEffect();
                }
                if (typeof createFireworks === 'function') {
                    createFireworks();
                }
            } catch (e) {
                console.warn('Celebration effects not available', e);
            }

            setTimeout(() => {
                correctCountEl.textContent = correctAnswers;
                goalCountEl.textContent = currentGoal;

                progressFill.style.transition = 'none';
                progressFill.style.width = '0';
                progressFill.classList.remove('lava');
                milestoneContainer.classList.remove('celebrate');

                void progressFill.offsetWidth;

                progressFill.style.transition = 'width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
                updateProgressFill();

                document.querySelectorAll('.milestone-mark').forEach(mark => mark.classList.remove('active'));

                try {
                    if (typeof addCelebrationEffect === 'function') {
                        setTimeout(() => {
                            addCelebrationEffect();
                        }, 1000);
                    }
                } catch (e) {
                    console.warn('Celebration effect not available', e);
                }
            }, 1500);
        }

        function updateProgress(newProgress) {
            correctAnswers = newProgress;
            const correctCount = document.getElementById('correctCount');
            correctCount.textContent = correctAnswers;

            if (correctAnswers >= currentGoal) {
                levelUpGoal();
            } else {
                updateProgressFill();
            }

            correctCount.classList.add('pulse');
            setTimeout(() => correctCount.classList.remove('pulse'), 300);
        }

        // Initialize dropdowns with error suppression
        document.addEventListener('DOMContentLoaded', function() {
            try {
                var dropdowns = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                dropdowns.map(function (dropdownToggle) {
                    return new bootstrap.Dropdown(dropdownToggle);
                });
            } catch (e) {
                console.warn('Dropdown init error suppressed:', e);
            }

            // Initialize with the full correct answers count, not the modulo
            updateProgress(correctAnswers);

            // Remove the complex card animation and replace with simple hover
            document.querySelectorAll('.course-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-8px)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
        });

        window.addEventListener('load', function() {
            // Initialize with the full correct answers count, not the modulo
            updateProgress(correctAnswers);
        });

        document.addEventListener('progressUpdate', function(e) {
            updateProgress(e.detail.progress);
        });

        window.addEventListener('userProgressUpdated', function(event) {
            if (event.detail && event.detail.correctAnswers !== undefined) {
                updateProgress(event.detail.correctAnswers);
                
                $('#correctCount').text(event.detail.correctAnswers);
            }
        });

        // Initialize goal modal (if exists)
        try {
            const goalModal = new bootstrap.Modal(document.getElementById('goalModal'));
            window.openGoalModal = function() {
                goalModal.show();
            }
        } catch (e) {
            // Modal not present, ignore
        }

        // Celebration Effects
        function addCelebrationEffect() {
            const container = document.querySelector('.milestone-container');
            const colors = ['#4f46e5', '#6366f1', '#8b5cf6', '#d946ef', '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b', '#10b981'];
            
            for (let i = 0; i < 30; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = `${Math.random() * 100}%`;
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = `${0.5 + Math.random() * 0.5}s`;
                confetti.style.animationDelay = `${Math.random() * 0.2}s`;
                container.appendChild(confetti);
                
                // Remove confetti after animation completes
                setTimeout(() => {
                    confetti.remove();
                }, 2000);
            }
        }
        
        function createFireworks() {
            const milestone = document.querySelector('.milestone-container');
            const fireworks = document.createElement('div');
            fireworks.className = 'fireworks';
            milestone.appendChild(fireworks);
            
            setTimeout(() => {
                fireworks.remove();
            }, 1500);
        }
    </script>
    <script>
    function showUnlockInstructions() {
        document.getElementById('backdrop').classList.add('show');
        document.getElementById('unlockAlert').classList.add('show');
    }

    function hideUnlockInstructions() {
        document.getElementById('backdrop').classList.remove('show');
        document.getElementById('unlockAlert').classList.remove('show');
    }
    </script>
    <script>
    function scrollCourses(direction) {
        const container = document.querySelector('.courses-grid');
        const cardWidth = 340;
        const gap = 24;
        const scrollAmount = cardWidth + gap;
        
        const currentScroll = container.scrollLeft;
        const targetScroll = direction === 'left' 
            ? Math.max(0, currentScroll - scrollAmount)
            : currentScroll + scrollAmount;
        
        container.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });
    }

    // Update scroll arrows visibility
    function updateScrollArrows(container) {
        const leftArrow = document.querySelector('.scroll-left');
        const rightArrow = document.querySelector('.scroll-right');
        
        // Show/hide arrows with smooth fade
        leftArrow.style.opacity = container.scrollLeft > 0 ? '1' : '0';
        rightArrow.style.opacity = 
            (container.scrollLeft + container.offsetWidth) < container.scrollWidth 
                ? '1' : '0';
    }

    // Add scroll event listener for arrow visibility
    const coursesGrid = document.querySelector('.courses-grid');
    coursesGrid.addEventListener('scroll', () => {
        updateScrollArrows(coursesGrid);
    });

    // Initial arrow visibility
    document.addEventListener('DOMContentLoaded', () => {
        updateScrollArrows(document.querySelector('.courses-grid'));
    });
    </script>
    <script>
        // Enhanced semester dropdown functionality with precise scrolling
        document.addEventListener('DOMContentLoaded', () => {
            const semesterDropdown = document.getElementById('semesterDropdown');
            
            if (semesterDropdown) {
                // Set the first option as selected by default
                semesterDropdown.value = "Year 1- First semester";
                
                // Filter courses on page load to show only first semester
                filterCoursesForSemester("Year 1- First semester");
                
                semesterDropdown.addEventListener('change', () => {
                    const selectedSemester = semesterDropdown.value;
                    filterCoursesForSemester(selectedSemester);
                });
            }
            
            // Function to filter courses based on selected semester
            function filterCoursesForSemester(selectedSemester) {
                const coursesGrid = document.querySelector('.courses-grid');
                const courseCards = Array.from(document.querySelectorAll('.course-card'));

                // Find the first course card of the selected semester
                const targetCard = courseCards.find(card => 
                    card.getAttribute('data-semester') === selectedSemester
                );

                if (targetCard) {
                    // Calculate the precise scroll position
                    const cardWidth = 340; // Fixed card width
                    const gap = 24; // Gap between cards (1.5rem = 24px)
                    const containerPadding = 16; // Container padding (1rem = 16px)
                    
                    const cardIndex = courseCards.indexOf(targetCard);
                    const scrollPosition = cardIndex * (cardWidth + gap);
                
                    // Smooth scroll with enhanced easing
                    coursesGrid.scrollTo({
                        left: scrollPosition,
                        behavior: 'smooth'
                    });

                    // Update scroll arrows visibility
                    updateScrollArrows(coursesGrid);
                }
            }
        });
    </script>
    <script>
        // Payment Modal Initialization 
        try {
            // Initialize payment modal
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            
            // Add click event to all payment buttons
            document.querySelectorAll('.show-payment-modal').forEach(button => {
                button.addEventListener('click', function() {
                    const courseId = this.getAttribute('data-course-id');
                    
                    // Show modal with loading state
                    paymentModal.show();
                    
                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Fetch payment options
                    fetch(`/payments/${courseId}/options`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update modal content
                            document.querySelector('#paymentModal .modal-content').innerHTML = data.html;
                            
                            // Initialize the payment process button after modal content is loaded
                            const proceedButton = document.getElementById('proceedToPayment');
                            if (proceedButton) {
                                initializePaymentProcess(proceedButton, courseId, csrfToken);
                            }
                        } else {
                            // Show error message
                            alert(data.message);
                            
                            // Redirect if needed
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                paymentModal.hide();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        paymentModal.hide();
                    });
                });
            });
            
            // Function to wait for a function to become available
            function waitForFunction(functionName, maxWaitTime = 5000, interval = 100) {
                return new Promise((resolve, reject) => {
                    if (typeof window[functionName] === 'function') {
                        resolve(window[functionName]);
                        return;
                    }
                    
                    const startTime = Date.now();
                    const intervalId = setInterval(() => {
                        if (typeof window[functionName] === 'function') {
                            clearInterval(intervalId);
                            resolve(window[functionName]);
                            return;
                        }
                        
                        if (Date.now() - startTime > maxWaitTime) {
                            clearInterval(intervalId);
                            reject(new Error(`Function ${functionName} not available after ${maxWaitTime}ms`));
                        }
                    }, interval);
                });
            }
            
            // Function to initialize payment process
            function initializePaymentProcess(proceedButton, courseId, csrfToken) {
                const paymentForm = document.getElementById('paymentMethodForm');
                
                proceedButton.addEventListener('click', function() {
                    // Show loading state
                    proceedButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    proceedButton.disabled = true;
                    
                    // Make AJAX request to get payment options directly
                    fetch(`/payments/${courseId}/options`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Payment options response:', data);
                        
                        if (data.success) {
                            // Replace modal content with payment methods
                            document.querySelector('#paymentModal .modal-content').innerHTML = data.html;
                        } else {
                            // Show error message
                            console.error('Payment processing failed:', data.message);
                            alert(data.message || 'Payment processing failed. Please try again.');
                            
                            // Reset button state
                            proceedButton.innerHTML = '<i class="fas fa-lock-open me-2"></i>Proceed to Payment';
                            proceedButton.disabled = false;
                            
                            // Redirect if needed
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error processing payment:', error);
                        alert('An error occurred while processing your payment. Please try again later.');
                        
                        // Reset button state
                        proceedButton.innerHTML = '<i class="fas fa-lock-open me-2"></i>Proceed to Payment';
                        proceedButton.disabled = false;
                    });
                });
            }
        } catch (e) {
            console.warn('Payment modal initialization error:', e);
        }
    </script>
    
    <!-- Bundle Purchase Feature Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for pending payments and update buttons
            function updatePaymentButtonsForPendingPayments() {
                // Update buttons for any pending payments from localStorage
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('payment_pending_')) {
                        const courseId = key.replace('payment_pending_', '');
                        const button = document.querySelector(`#paymentBtn_${courseId}`);
                        if (button) {
                            button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c;">Payment Pending</span>';
                            button.classList.remove('btn-primary', 'show-payment-modal');
                            button.classList.add('btn-warning');
                            button.disabled = true;
                            button.style.cursor = 'default';
                        }
                    }
                });
            }

            // Run the check when page loads
            updatePaymentButtonsForPendingPayments();

            // Initialize the bundle modal
            const bundleModal = new bootstrap.Modal(document.getElementById('bundleModal'));
            
            // Bundle button click event
            const bundlePurchaseBtn = document.getElementById('bundlePurchaseBtn');
            if (bundlePurchaseBtn) {
                bundlePurchaseBtn.addEventListener('click', function() {
                    bundleModal.show();
                });
            }
            
            // Course selection and pricing calculations
            const courseCheckboxes = document.querySelectorAll('.bundle-course-checkbox');
            const proceedButton = document.getElementById('proceedBundlePayment');
            const originalPriceElement = document.getElementById('originalPrice');
            const bundleDiscountElement = document.getElementById('bundleDiscount');
            const totalPriceElement = document.getElementById('totalPrice');
            
            // Initialize course selection tracking
            let selectedCourses = [];
            let originalPrice = 0;
            let discountAmount = 0;
            let totalPrice = 0;
            
            // Add event listeners to checkboxes
            courseCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBundlePricing);
            });
            
            function updateBundlePricing() {
                // Reset selected courses array
                selectedCourses = [];
                
                // Get all checked courses
                courseCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedCourses.push({
                            id: checkbox.dataset.courseId,
                            name: checkbox.dataset.courseName,
                            price: parseFloat(checkbox.dataset.coursePrice)
                        });
                        
                        // Update the parent label
                        checkbox.closest('.course-checkbox').classList.add('selected');
                    } else {
                        checkbox.closest('.course-checkbox').classList.remove('selected');
                    }
                });
                
                // Calculate pricing
                originalPrice = selectedCourses.reduce((total, course) => total + course.price, 0);
                
                // Apply discount based on number of courses
                // 2 courses: 50 EGP discount (e.g., 400 -> 350)
                // 4 courses: 100 EGP discount (e.g., 600 -> 500)
                if (selectedCourses.length === 2) {
                    discountAmount = 50;
                } else if (selectedCourses.length === 3) {
                    discountAmount = 75;
                } else if (selectedCourses.length >= 4) {
                    discountAmount = 100;
                } else {
                    discountAmount = 0;
                }
                
                totalPrice = originalPrice - discountAmount;
                
                // Update display
                originalPriceElement.textContent = `${originalPrice} EGP`;
                bundleDiscountElement.textContent = `- ${discountAmount} EGP`;
                totalPriceElement.textContent = `${totalPrice} EGP`;
                
                // Enable/disable proceed button
                proceedButton.disabled = selectedCourses.length < 2;
            }
            
            // Handle proceed to payment button
            if (proceedButton) {
                proceedButton.addEventListener('click', function() {
                    // Show loading state
                    proceedButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    proceedButton.disabled = true;
                    
                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Prepare data for bundle purchase
                    const bundleData = {
                        courses: selectedCourses.map(course => course.id),
                        original_price: originalPrice,
                        discount_amount: discountAmount,
                        total_price: totalPrice
                    };
                    
                    // Make AJAX request to get bundle payment options instead of direct processing
                    fetch('/payments/bundle/options', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(bundleData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Bundle payment options response:', data);
                        
                        if (data.success) {
                            // Hide bundle modal
                            bundleModal.hide();
                            
                            // Show the payment modal with options from your existing system
                            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                            
                            // Update the payment modal content with the bundle payment options
                            const modalContent = document.querySelector('#paymentModal .modal-content');
                            modalContent.innerHTML = data.html;
                            
                            // Show the payment modal
                            paymentModal.show();
                        } else {
                            // Show error message
                            console.error('Bundle payment options failed:', data.message);
                            alert(data.message || 'Failed to load bundle payment options. Please try again.');
                            
                            // Reset button state
                            proceedButton.innerHTML = 'Proceed to Payment';
                            proceedButton.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading bundle payment options:', error);
                        alert('An error occurred while loading payment options. Please try again later.');
                        
                        // Reset button state
                        proceedButton.innerHTML = 'Proceed to Payment';
                        proceedButton.disabled = selectedCourses.length < 2;
                    });
                });
            }
        });

        // Academic Year Filtering Function
        function filterCoursesByAcademicYear() {
            const selectedYear = document.getElementById('semesterDropdown').value;
            const courseCards = document.querySelectorAll('.course-card');
            
            courseCards.forEach(card => {
                const cardAcademicYear = card.getAttribute('data-academic-year');
                
                if (selectedYear === '' || cardAcademicYear === selectedYear) {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                } else {
                    card.style.display = 'none';
                    card.style.opacity = '0';
                }
            });
            
            // Update scroll arrows visibility after filtering
            updateScrollArrows();
        }

        // Update scroll arrows based on filtered content
        function updateScrollArrows() {
            const coursesGrid = document.querySelector('.courses-grid');
            const visibleCards = document.querySelectorAll('.course-card[style*="display: block"], .course-card:not([style*="display: none"])');
            
            if (visibleCards.length <= 3) {
                document.querySelector('.scroll-left').style.display = 'none';
                document.querySelector('.scroll-right').style.display = 'none';
            } else {
                document.querySelector('.scroll-left').style.display = 'flex';
                document.querySelector('.scroll-right').style.display = 'flex';
            }
        }

        // Initialize filtering on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure all course cards are visible by default
            const courseCards = document.querySelectorAll('.course-card');
            courseCards.forEach(card => {
                card.style.display = 'block';
                card.style.opacity = '1';
            });
            
            // Set dropdown to show "All Courses" by default
            const dropdown = document.getElementById('semesterDropdown');
            if (dropdown) {
                dropdown.value = '';
            }
            
            updateScrollArrows();
        });
    </script>
</body>
</html>
<!-- No
tification System JavaScript -->
<script src="{{ asset('js/notification-system.js') }}"></script>
</body>
</html>