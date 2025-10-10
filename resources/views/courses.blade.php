<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>

    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MedCapsule">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#667eea">
    <meta name="msapplication-TileColor" content="#667eea">
    <meta name="msapplication-TileImage" content="/images/icon-144x144.png">
    <meta name="application-name" content="MedCapsule">
    <meta name="description" content="The ultimate medical education platform for students and professionals. Learn, practice, and excel in medical knowledge.">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/images/icon-512x512.png">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <!-- RUTHLESS: Resource hints for faster loading -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- SUPER MINIMAL CRITICAL CSS -->
    <style>
        /* ESSENTIAL FOUC PREVENTION + PERFORMANCE */
        .clean-notification-dropdown{display:none!important}
        .clean-notification-dropdown.show{display:flex!important}
        .navbar{min-height:76px!important}
        .course-card{opacity:1!important;transform:none!important;contain:layout style paint}
        .course-image{background-color:#ffffff!important;opacity:1!important;will-change:auto}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif!important}
        /* INSTANT PERFORMANCE BOOST */
        *{box-sizing:border-box}
        .courses-grid{contain:layout style;transform:translateZ(0)}
        .course-card{backface-visibility:hidden}
    </style>
    
    <!-- SUPER FAST IMAGE PRELOADING - ONLY FIRST 2 VISIBLE IMAGES -->
    @foreach ($courses->take(2) as $course)
        @php
            $imageUrl = $course->image_url;
            // AGGRESSIVE image optimization
            if (str_contains($imageUrl, 'unsplash.com')) {
                $imageUrl = str_replace('?', '?w=420&h=580&fit=crop&auto=format,compress&q=60&fm=webp&', $imageUrl);
            } elseif (str_contains($imageUrl, 'picsum.photos')) {
                $imageUrl = str_replace('picsum.photos/', 'picsum.photos/420/580/', $imageUrl) . '?blur=1';
            }
        @endphp
        <link rel="preload" as="image" href="{{ $imageUrl }}" fetchpriority="high">
    @endforeach
    
    <!-- SUPER AGGRESSIVE RESOURCE HINTS -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    
    <!-- ONLY PRECONNECT TO ACTIVE IMAGE DOMAINS -->
    @if($courses->isNotEmpty())
        @php
            $imageDomains = $courses->pluck('image_url')->map(function($url) {
                return parse_url($url, PHP_URL_HOST);
            })->unique()->take(2); // Only first 2 domains
        @endphp
        @foreach($imageDomains as $domain)
            <link rel="preconnect" href="https://{{ $domain }}" crossorigin>
        @endforeach
    @endif

    <!-- SUPER LIGHTNING SPEED CSS LOADING - FIXED ONLOAD HANDLER -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="preload" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>
    
    <!-- INSTANT FONT LOADING - SYSTEM FONTS FIRST -->
    <style>
        /* INSTANT SYSTEM FONTS - ZERO DELAY */
        body, .navbar-brand, .course-title, .preloader-brand {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }
    </style>
    
    <!-- LOAD CUSTOM FONTS IN BACKGROUND - FIXED ONLOAD HANDLER -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="preload" as="style" onload="if(this&&document.body){this.onload=null;this.rel='stylesheet';document.body.style.fontFamily='Poppins, -apple-system, BlinkMacSystemFont, sans-serif';}">

    <!-- BOLD IMPACTFUL FONT FOR MEDICAL COURSES HEADER -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="preload" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">

    <!-- FONT AWESOME - ASYNC LOAD - FIXED ONLOAD HANDLER -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></noscript>

    <style>
        /* Success animation for payment completion */
        @keyframes successPulse {
            0% { transform: scale(1); background-color: inherit; }
            50% { transform: scale(1.05); background-color: #d4edda; }
            100% { transform: scale(1); background-color: inherit; }
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
            zoom: 0.73; /* Add zoom out for better overall view */
        }

        .navbar {
            background: #ffffff;
            border: none;
            box-shadow: none;
            padding: 0.3rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        /* Smooth transition overlay between navbar and content */
        .navbar::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0));
            pointer-events: none;
            z-index: 999;
        }

        .navbar .container {
            max-width: 100%;
            padding-left: 2.5rem;
            padding-right: 2.5rem;
        }

        .navbar-nav {
            gap: 0.75rem;
        }

        .navbar-nav.ms-auto {
            margin-left: auto !important;
            margin-right: 1.25rem; /* Moved slightly more to the right */
        }

        .nav-item.dropdown {
            position: relative;
        }

        .nav-item {
            display: flex;
            align-items: center;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            margin-left: 1.75rem; /* Moved slightly more to the right */
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
            right: 0 !important;
            left: auto !important;
            transform: none !important;
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

        /* Logout button specific red hover styling */
        .logout-btn:hover,
        .logout-btn:focus {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            transform: translateX(5px);
            border-color: rgba(220, 53, 69, 0.2) !important;
        }

        .logout-btn:hover i,
        .logout-btn:focus i {
            color: #dc3545 !important;
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
            background: white;
            padding: 5rem 2rem 2rem;
            text-align: center;
            position: relative;
            margin-bottom: 1rem;
            overflow: hidden;
            border: none;
            -webkit-text-size-adjust: 100%;
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
            /* RUTHLESS GPU OPTIMIZATION - SAME VISUAL EFFECT */
            will-change: transform, opacity;
            transform: translate3d(0, 0, 0);
        }

        @keyframes float {
            0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
            25% { transform: translate3d(100px, 50px, 0) rotate(90deg); }
            50% { transform: translate3d(50px, 100px, 0) rotate(180deg); }
            75% { transform: translate3d(-50px, 50px, 0) rotate(270deg); }
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #3579F6;
            margin-bottom: 1rem;
            margin-top: 1.25rem;
            text-align: center;
            position: relative;
            z-index: 10;
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
            gap: 2rem;
            padding: 1.5rem 3rem;
            margin: 1rem 3rem 2rem;
            max-width: 100%;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            position: relative;
            scroll-snap-type: x mandatory;
            padding-bottom: 20px;
            -webkit-overflow-scrolling: touch;
            /* CRITICAL PERFORMANCE OPTIMIZATIONS */
            contain: layout style;
            will-change: scroll-position;
            /* PREVENT LAYOUT SHIFTS */
            min-height: 700px;
        }

        .courses-grid::-webkit-scrollbar {
            display: none;
        }

        .scroll-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            opacity: 0.7;
        }

        .scroll-arrow:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transform: translateY(-50%) scale(1.1);
            opacity: 1;
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
            flex: 0 0 435px;
            scroll-snap-align: start;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 435px;
            margin-bottom: 3rem;
            position: relative;
            border: 2px solid var(--course-color, #3b82f6);
            border-left: 8px solid var(--course-color, #3b82f6);
            opacity: 1;
            will-change: transform;
            contain: layout style paint;
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
            transform: translateY(-8px) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .course-image {
            position: relative;
            height: 590px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            /* LIGHTNING FAST LOADING - NO TRANSITIONS */
            background-color: #f8f9fa;
            opacity: 1;
            transform: scale(1);
            /* CRITICAL PERFORMANCE OPTIMIZATIONS */
            will-change: auto;
            contain: layout style paint;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        .course-image.loading {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            position: relative;
            overflow: hidden;
        }

        .course-image.loading::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255, 255, 255, 0.4) 50%, 
                transparent 100%);
            animation: shimmer 1s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .course-image.error {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .course-image.error::after {
            content: '📚';
            font-size: 4rem;
            opacity: 0.3;
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
            padding: 0.8rem; /* Reduced from 1.2rem */
            display: flex;
            flex-direction: column;
            gap: 0.5rem; /* Reduced from 0.8rem */
            background: white;
        }

        .course-stats-row {
            display: flex;
            align-items: center;
            margin-top: 6px; /* Reduced from 12px */
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
            margin-left: 8px;
            min-height: 36px;
            flex-wrap: nowrap;
            overflow: visible;
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
            margin-top: 1rem; /* Reduced from 3rem */
            padding-top: 0.5rem; /* Reduced from 1rem */
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

        /* 🌟 MEDICAL ANIMATION SYSTEM - DNA, MOLECULES & ICONS 🌟 */

        /* Enhanced DNA Double Helix Animation - ULTRA FAST */
        .enhanced-dna-helix {
            position: absolute;
            top: 15%;
            left: 8%;
            width: 100px;
            height: 150px;
            z-index: 1;
            animation: dnaHelixRotate 8s linear infinite;
            transform-style: preserve-3d;
            will-change: transform;
        }

        .dna-strand-left {
            position: absolute;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, #1e40af, #2563eb, #3b82f6);
            border-radius: 2px;
            left: 15px;
            filter: drop-shadow(0 0 15px rgba(220, 38, 38, 0.9)) drop-shadow(0 0 25px rgba(248, 113, 113, 0.7));
            animation: dnaGlow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .dna-strand-right {
            position: absolute;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, #dc2626, #ef4444, #f87171);
            border-radius: 2px;
            right: 5px;
            filter: drop-shadow(0 0 15px rgba(220, 38, 38, 0.8)) drop-shadow(0 0 25px rgba(248, 113, 113, 0.6));
            animation: dnaGlow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite 1.5s;
        }

        .dna-bond {
            position: absolute;
            width: 25px;
            height: 2px;
            background: linear-gradient(90deg, rgba(30, 64, 175, 0.6), rgba(220, 38, 38, 0.6));
            border-radius: 1px;
            left: 18px;
            transform-origin: center;
            animation: dnaBondPulse 5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            box-shadow: 0 0 8px rgba(30, 64, 175, 0.3);
        }

        @keyframes dnaHelixRotate {
            0% {
                transform: rotateY(0deg) scale(1);
            }
            25% {
                transform: rotateY(90deg) scale(1.01);
            }
            50% {
                transform: rotateY(180deg) scale(1.02);
            }
            75% {
                transform: rotateY(270deg) scale(1.01);
            }
            100% {
                transform: rotateY(360deg) scale(1);
            }
        }

        @keyframes dnaGlow {
            0%, 100% {
                filter: drop-shadow(0 0 12px rgba(74, 144, 226, 0.4));
                transform: scaleY(1);
                opacity: 0.8;
            }
            25% {
                filter: drop-shadow(0 0 15px rgba(74, 144, 226, 0.5));
                transform: scaleY(1.005);
                opacity: 0.9;
            }
            50% {
                filter: drop-shadow(0 0 20px rgba(74, 144, 226, 0.6));
                transform: scaleY(1.01);
                opacity: 1;
            }
            75% {
                filter: drop-shadow(0 0 15px rgba(74, 144, 226, 0.5));
                transform: scaleY(1.005);
                opacity: 0.9;
            }
        }

        @keyframes dnaBondPulse {
            0%, 100% {
                opacity: 0.5;
                transform: scaleX(0.95);
                filter: brightness(0.9);
            }
            50% {
                opacity: 0.8;
                transform: scaleX(1.05);
                filter: brightness(1.1);
            }
        }

        /* Floating Molecules (positioned away from title) */
        .molecule {
            position: absolute;
            animation: moleculeFloat 15s ease-in-out infinite;
            z-index: 1;
        }

        .molecule.glucose {
            top: 65%;
            right: 20%;
            width: 60px;
            height: 40px;
            animation-delay: 0s;
        }

        .molecule.water {
            top: 75%;
            left: 15%;
            width: 40px;
            height: 30px;
            animation-delay: -7s;
        }

        .atom {
            position: absolute;
            border-radius: 50%;
            animation: atomPulse 2s ease-in-out infinite;
        }

        .atom.hydrogen {
            width: 6px;
            height: 6px;
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }

        .atom.oxygen {
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .atom.carbon {
            width: 7px;
            height: 7px;
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .bond {
            position: absolute;
            height: 1px;
            background: rgba(255, 255, 255, 0.6);
            transform-origin: center;
        }

        @keyframes moleculeFloat {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }
            25% {
                transform: translateY(-10px) rotate(90deg);
                opacity: 0.9;
            }
            50% {
                transform: translateY(-5px) rotate(180deg);
                opacity: 0.8;
            }
            75% {
                transform: translateY(-15px) rotate(270deg);
                opacity: 0.95;
            }
        }

        @keyframes atomPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Ultra-Compact Capsule-Shaped Pills - Enhanced & Calmer */
        .ultra-capsule {
            position: absolute;
            z-index: 0;
            opacity: 0.6;
            animation: freeFloat 42s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
            display: flex;
            flex-direction: column;
            align-items: center;
            filter: drop-shadow(0 0 6px rgba(74, 144, 226, 0.15));
            transform-style: preserve-3d;
        }

        .capsule-head {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.5), rgba(16, 185, 129, 0.4));
            border: 1px solid rgba(74, 144, 226, 0.6);
            border-radius: 50%;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.3);
        }

        .capsule-body {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.4), rgba(16, 185, 129, 0.3));
            border: 1px solid rgba(74, 144, 226, 0.5);
            border-top: none;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.2);
        }

        .ultra-capsule.capsule-large {
            top: 25%;
            right: 30%;
            animation-duration: 32s;
            animation-delay: 0s;
            transform: scale(1.3) translateY(8px);
        }

        .ultra-capsule.capsule-large .capsule-head {
            width: 24px;
            height: 12px;
            background: linear-gradient(135deg, rgba(50, 205, 50, 0.6), rgba(0, 255, 0, 0.5));
            border-color: rgba(50, 205, 50, 0.7);
        }

        .ultra-capsule.capsule-large .capsule-body {
            width: 24px;
            height: 28px;
            border-radius: 0 0 12px 12px;
            background: linear-gradient(135deg, rgba(50, 205, 50, 0.5), rgba(0, 255, 0, 0.4));
            border-color: rgba(50, 205, 50, 0.6);
        }

        .ultra-capsule.capsule-medium {
            top: 60%;
            left: 25%;
            animation-duration: 32s;
            animation-delay: -12s;
            transform: scale(1.5);
        }

        .ultra-capsule.capsule-medium .capsule-head {
            width: 26px;
            height: 14px;
            background: linear-gradient(135deg, rgba(255, 165, 0, 0.6), rgba(255, 140, 0, 0.5));
            border-color: rgba(255, 165, 0, 0.7);
        }

        .ultra-capsule.capsule-medium .capsule-body {
            width: 26px;
            height: 28px;
            border-radius: 0 0 13px 13px;
            background: linear-gradient(135deg, rgba(255, 165, 0, 0.5), rgba(255, 140, 0, 0.4));
            border-color: rgba(255, 165, 0, 0.6);
        }

        .ultra-capsule.capsule-small {
            top: 40%;
            right: 15%;
            animation-duration: 42s;
            animation-delay: -25s;
        }

        .ultra-capsule.capsule-small .capsule-head {
            width: 12px;
            height: 6px;
        }

        .ultra-capsule.capsule-small .capsule-body {
            width: 12px;
            height: 14px;
            border-radius: 0 0 6px 6px;
        }

        .ultra-capsule.capsule-variant1 {
            top: 70%;
            right: 40%;
            animation-duration: 28s;
            animation-delay: -6s;
        }

        .ultra-capsule.capsule-variant1 .capsule-head {
            width: 18px;
            height: 9px;
            background: linear-gradient(135deg, rgba(139, 124, 246, 0.5), rgba(196, 181, 253, 0.4));
            border-color: rgba(139, 124, 246, 0.6);
        }

        .ultra-capsule.capsule-variant1 .capsule-body {
            width: 18px;
            height: 21px;
            border-radius: 0 0 9px 9px;
            background: linear-gradient(135deg, rgba(139, 124, 246, 0.4), rgba(196, 181, 253, 0.3));
            border-color: rgba(139, 124, 246, 0.5);
        }

        .ultra-capsule.capsule-variant2 {
            top: 35%;
            left: 40%;
            animation-duration: 36s;
            animation-delay: -18s;
        }

        .ultra-capsule.capsule-variant2 .capsule-head {
            width: 16px;
            height: 8px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.5), rgba(52, 211, 153, 0.4));
            border-color: rgba(16, 185, 129, 0.6);
        }

        .ultra-capsule.capsule-variant2 .capsule-body {
            width: 16px;
            height: 20px;
            border-radius: 0 0 8px 8px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.4), rgba(52, 211, 153, 0.3));
            border-color: rgba(16, 185, 129, 0.5);
        }

        @keyframes freeFloat {
            0% {
                transform: translate3d(0, 0, 0) rotate(0deg) scale(0.9);
                opacity: 0.5;
                filter: brightness(0.9);
            }
            10% {
                transform: translate3d(8px, -5px, 0) rotate(36deg) scale(0.95);
                opacity: 0.6;
                filter: brightness(0.95);
            }
            20% {
                transform: translate3d(15px, -10px, 0) rotate(72deg) scale(1.05);
                opacity: 0.7;
                filter: brightness(1.0);
            }
            30% {
                transform: translate3d(5px, -15px, 0) rotate(108deg) scale(1.1);
                opacity: 0.8;
                filter: brightness(1.05);
            }
            40% {
                transform: translate3d(-8px, -20px, 0) rotate(144deg) scale(1.0);
                opacity: 0.75;
                filter: brightness(1.0);
            }
            50% {
                transform: translate3d(-15px, -15px, 0) rotate(180deg) scale(0.95);
                opacity: 0.7;
                filter: brightness(0.95);
            }
            60% {
                transform: translate3d(-5px, -10px, 0) rotate(216deg) scale(0.9);
                opacity: 0.65;
                filter: brightness(0.9);
            }
            70% {
                transform: translate3d(10px, -8px, 0) rotate(252deg) scale(1.02);
                opacity: 0.75;
                filter: brightness(1.0);
            }
            80% {
                transform: translate3d(18px, -3px, 0) rotate(288deg) scale(1.08);
                opacity: 0.8;
                filter: brightness(1.05);
            }
            90% {
                transform: translate3d(12px, 2px, 0) rotate(324deg) scale(1.0);
                opacity: 0.7;
                filter: brightness(1.0);
            }
            100% {
                transform: translate3d(0, 0, 0) rotate(360deg) scale(0.9);
                opacity: 0.5;
                filter: brightness(0.9);
            }
        }

        /* EPIC RED HEARTBEAT WAVE - ULTRA SMOOTH & OPTIMIZED */
        .heartbeat-wave {
            position: absolute;
            z-index: 2;
            opacity: 0.9;
            will-change: transform, opacity;
            animation: heartbeatFloat 15s ease-in-out infinite;
        }

        .heartbeat-wave-primary {
            top: 45%;
            right: 25%;
            width: 120px;
            height: 50px;
            animation-delay: 0s;
            transform: scale(0.8);
            opacity: 0.7;
        }

        .heartbeat-svg {
            width: 100%;
            height: 100%;
            animation: heartbeatPulse 1.5s ease-in-out infinite;
            transform-origin: center;
            will-change: transform;
        }

        @keyframes heartbeatFloat {
            0%, 100% {
                transform: translate(0px, 0px) rotate(0deg) scale(0.9);
            }
            12% {
                transform: translate(3px, -4px) rotate(1deg) scale(0.95);
            }
            25% {
                transform: translate(6px, -8px) rotate(2deg) scale(1.0);
            }
            37% {
                transform: translate(2px, -6px) rotate(0deg) scale(1.05);
            }
            50% {
                transform: translate(-2px, -4px) rotate(-1deg) scale(1.1);
            }
            62% {
                transform: translate(-4px, -8px) rotate(-2deg) scale(1.05);
            }
            75% {
                transform: translate(-1px, -12px) rotate(1deg) scale(1.0);
            }
            87% {
                transform: translate(1px, -6px) rotate(0.5deg) scale(0.95);
            }
        }

        @keyframes heartbeatPulse {
            0%, 100% {
                transform: scale(1);
                stroke-dasharray: 120 0;
                stroke-dashoffset: 0;
            }
            25% {
                transform: scale(1.05);
                stroke-dasharray: 120 0;
                stroke-dashoffset: 0;
            }
            50% {
                transform: scale(1.1);
                stroke-dasharray: 120 0;
                stroke-dashoffset: 0;
            }
            75% {
                transform: scale(1.02);
                stroke-dasharray: 120 0;
                stroke-dashoffset: 0;
            }
        }

        /* Enhanced glow effect for medical authority */
        .heartbeat-wave-primary .heartbeat-svg {
            filter: drop-shadow(0 0 12px rgba(220, 38, 38, 0.6)) drop-shadow(0 0 20px rgba(220, 38, 38, 0.3));
        }

        .heartbeat-wave-secondary .heartbeat-svg,
        .heartbeat-wave-tertiary .heartbeat-svg {
            filter: drop-shadow(0 0 8px rgba(220, 38, 38, 0.4)) drop-shadow(0 0 15px rgba(220, 38, 38, 0.2));
        }

        /* 🔥 LIVE MOVING HEARTBEAT WAVE - REAL ECG MONITOR EFFECT 🔥 */
        .heartbeat-path {
            stroke-dasharray: 400;
            stroke-dashoffset: 400;
            animation: moveHeartbeatWave 3s linear infinite;
            will-change: stroke-dashoffset;
        }

        /* Continuous wave movement from left to right */
        @keyframes moveHeartbeatWave {
            0% {
                stroke-dashoffset: 400;
                opacity: 0;
                transform: translateX(-50px);
            }
            10% {
                opacity: 1;
                transform: translateX(-25px);
            }
            90% {
                opacity: 1;
                transform: translateX(25px);
            }
            100% {
                stroke-dashoffset: -400;
                opacity: 0;
                transform: translateX(50px);
            }
        }

        /* Stagger the animations for layered effect */
        .heartbeat-wave-primary .heartbeat-path {
            animation: moveHeartbeatWave 2.8s linear infinite;
            animation-delay: 0s;
        }

        .heartbeat-wave-secondary .heartbeat-path {
            animation: moveHeartbeatWave 2.8s linear infinite;
            animation-delay: -0.7s;
        }

        .heartbeat-wave-tertiary .heartbeat-path {
            animation: moveHeartbeatWave 2.8s linear infinite;
            animation-delay: -1.4s;
        }

        /* Ultra-bright pulsing effect for medical intensity */
        .heartbeat-wave-primary .heartbeat-svg {
            animation: heartbeatGlow 2s ease-in-out infinite alternate;
        }

        @keyframes heartbeatGlow {
            0% {
                filter: drop-shadow(0 0 15px rgba(255, 0, 0, 0.8)) drop-shadow(0 0 25px rgba(255, 0, 0, 0.4));
                transform: scale(1);
            }
            100% {
                filter: drop-shadow(0 0 20px rgba(255, 0, 0, 1)) drop-shadow(0 0 35px rgba(255, 0, 0, 0.6));
                transform: scale(1.02);
            }
        }

        /* Removed old floating capsule - replaced with ultra-compact capsules */

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
            padding: 0.4rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            /* RUTHLESS GPU OPTIMIZATION - SAME VISUAL EFFECT */
            will-change: transform;
        }

        .milestone-container:hover {
            transform: translate3d(-50%, -2px, 0);
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
            /* RUTHLESS GPU OPTIMIZATION - SAME VISUAL EFFECT */
            will-change: transform;
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
            0% { transform: translate3d(-50%, 0, 0) rotate(0deg); }
            100% { transform: translate3d(-50%, 0, 0) rotate(360deg); }
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
            width: 22px;
            height: 22px;
            background: white;
            border: 3px solid #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateX(-11px) scale(0.9);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
            z-index: 3;
        }

        .milestone-mark:hover {
            transform: translateX(-50%) scale(1.15);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
        }

        .milestone-mark i {
            font-size: 0.75rem;
            color: #3b82f6;
            transition: all 0.3s ease;
            opacity: 1;
            transform: scale(1);
            font-weight: 600;
        }

        .milestone-mark.active {
            background: #10b981;
            border-color: #059669;
            transform: translateX(-50%) scale(1);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
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

        /* Profile image styles - handles background-image approach */
        .profile-circle {
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        }

        /* Profile image styles for img tags (if used) */
        .profile-circle .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        /* Enhanced profile circle for better image display */
        .profile-circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        /* Fallback styles for profile circles */
        .profile-circle.no-image {
            background: linear-gradient(135deg, #007bff, #00d4ff) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: white !important;
            font-weight: 600 !important;
        }

        .profile-circle.no-image {
            background: linear-gradient(135deg, #007bff, #00d4ff);
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
            margin-left: 8px;
            min-height: 36px; /* Ensure space is always reserved */
            flex-wrap: nowrap;
            overflow: visible;
        }

        .profile-circle-small {
            width: 32px; /* Increased size for better visibility */
            height: 32px; /* Increased size for better visibility */
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #00d4ff);
            color: white;
            font-size: 0.8rem; /* Slightly larger font */
            font-weight: 700; /* Bolder font */
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: -8px; /* Overlap for stacking effect */
            border: 3px solid white; /* Thicker border for better definition */
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15); /* Enhanced shadow */
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
            flex-shrink: 0; /* Prevent shrinking */
        }

        .profile-circle-small:first-child {
            margin-left: 0; /* First circle has no overlap */
        }

        .profile-circle-small:hover {
            transform: scale(1.1) translateY(-2px);
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .profile-circle-small.more {
            background: #6c757d;
            color: white;
            font-weight: 700;
        }

        .profile-circle-small.more:hover {
            background: #5a6268;
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
            font-size: 1rem;
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
            background: rgba(30, 64, 175, 0.08);
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
        
        /* All notification dropdown styles removed - using component styles for consistency */
        }

        /* notification-item styles removed - using component styles */
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
        

        
        /* Modal Styles - Fixed for Edge compatibility */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 1040 !important;
            transform: none !important;
            will-change: auto !important;
            transition: none !important;
        }

        .modal-backdrop.show {
            opacity: 1 !important;
        }

        .modal-backdrop.fade {
            opacity: 0 !important;
            transition: none !important;
        }

        .modal-backdrop.fade.show {
            opacity: 1 !important;
            transition: none !important;
        }

        /* Edge-specific modal backdrop override */
        @supports (-ms-ime-align: auto) {
            .modal-backdrop,
            .modal-backdrop.show,
            .modal-backdrop.fade.show {
                background: rgba(0, 0, 0, 0.5) !important;
                opacity: 1 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100% !important;
                height: 100% !important;
                z-index: 1040 !important;
                backdrop-filter: none !important;
                filter: none !important;
                transform: none !important;
                transition: none !important;
            }
        }

        .modal-content {
            z-index: 1041 !important;
            position: relative;
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

        /* 🧬 BIOLOGICAL CELL - Clean & Simple */
        .biological-cell {
            position: absolute;
            top: 50%;
            left: 30%;
            width: 75px;
            height: 75px;
            z-index: 1;
            animation: cellFloat 25s ease-in-out infinite;
            transform-style: preserve-3d;
        }

        .cell-membrane {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid rgba(59, 130, 246, 0.6);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 70%);
            box-shadow:
                0 0 10px rgba(59, 130, 246, 0.3),
                inset 0 0 20px rgba(59, 130, 246, 0.1);
            animation: cellMembranePulse 8s ease-in-out infinite;
        }

        .cell-interior {
            position: absolute;
            top: 8px;
            left: 8px;
            width: calc(100% - 16px);
            height: calc(100% - 16px);
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.6));
        }

        .cell-nucleus {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.4), rgba(139, 92, 246, 0.3));
            border: 1px solid rgba(99, 102, 241, 0.5);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 8px rgba(99, 102, 241, 0.4);
            animation: nucleusPulse 6s ease-in-out infinite;
        }

        .chromosome {
            position: absolute;
            background: linear-gradient(45deg, rgba(220, 38, 38, 0.6), rgba(239, 68, 68, 0.4));
            border-radius: 2px;
            box-shadow: 0 0 4px rgba(220, 38, 38, 0.3);
            animation: chromosomeFloat 12s ease-in-out infinite;
        }

        .chromosome-1 {
            width: 18px;
            height: 3px;
            top: 25%;
            left: 20%;
            transform: rotate(45deg);
            animation-delay: 0s;
        }

        .chromosome-2 {
            width: 15px;
            height: 3px;
            top: 35%;
            right: 25%;
            transform: rotate(-30deg);
            animation-delay: -3s;
        }

        .chromosome-3 {
            width: 12px;
            height: 3px;
            bottom: 30%;
            left: 30%;
            transform: rotate(60deg);
            animation-delay: -6s;
        }

        .chromosome-4 {
            width: 16px;
            height: 3px;
            bottom: 25%;
            right: 20%;
            transform: rotate(-45deg);
            animation-delay: -9s;
        }

        @keyframes cellFloat {
            0% {
                transform: translate(0px, 0px) rotate(0deg) scale(1);
                opacity: 0.7;
            }
            15% {
                transform: translate(8px, -12px) rotate(45deg) scale(1.08);
                opacity: 0.9;
            }
            30% {
                transform: translate(15px, -8px) rotate(90deg) scale(0.95);
                opacity: 0.8;
            }
            45% {
                transform: translate(-5px, -15px) rotate(135deg) scale(1.05);
                opacity: 0.75;
            }
            60% {
                transform: translate(-12px, 5px) rotate(180deg) scale(0.92);
                opacity: 0.8;
            }
            75% {
                transform: translate(10px, 8px) rotate(270deg) scale(1.03);
                opacity: 0.85;
            }
            90% {
                transform: translate(5px, 3px) rotate(315deg) scale(0.98);
                opacity: 0.9;
            }
            100% {
                transform: translate(0px, 0px) rotate(360deg) scale(1);
                opacity: 0.7;
            }
        }

        @keyframes cellMembranePulse {
            0%, 100% {
                border-color: rgba(59, 130, 246, 0.6);
                box-shadow:
                    0 0 10px rgba(59, 130, 246, 0.3),
                    inset 0 0 20px rgba(59, 130, 246, 0.1);
            }
            50% {
                border-color: rgba(59, 130, 246, 0.8);
                box-shadow:
                    0 0 15px rgba(59, 130, 246, 0.5),
                    inset 0 0 25px rgba(59, 130, 246, 0.15);
            }
        }

        @keyframes nucleusPulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                box-shadow: 0 0 8px rgba(99, 102, 241, 0.4);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.1);
                box-shadow: 0 0 12px rgba(99, 102, 241, 0.6);
            }
        }

        @keyframes chromosomeFloat {
            0%, 100% {
                transform: rotate(0deg) scale(1);
                opacity: 0.6;
            }
            25% {
                transform: rotate(5deg) scale(1.05);
                opacity: 0.8;
            }
            50% {
                transform: rotate(-3deg) scale(0.95);
                opacity: 0.7;
            }
            75% {
                transform: rotate(2deg) scale(1.02);
                opacity: 0.9;
            }
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

        /* 🦠 MICRO CELL - TINY ORGANISM UNDER HEARTBEAT 🌟 */
        .micro-cell {
            position: absolute;
            top: 65%;
            right: 35%;
            width: 35px;
            height: 35px;
            z-index: 3;
            animation: microFloat 18s ease-in-out infinite;
            transform-style: preserve-3d;
            will-change: transform, opacity;
        }

        .micro-membrane {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 1px solid rgba(59, 130, 246, 0.7);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 60%);
            box-shadow:
                0 0 6px rgba(59, 130, 246, 0.4),
                inset 0 0 8px rgba(59, 130, 246, 0.15);
            animation: microMembranePulse 10s ease-in-out infinite;
        }

        .micro-interior {
            position: absolute;
            top: 5px;
            left: 5px;
            width: calc(100% - 10px);
            height: calc(100% - 10px);
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.9), rgba(241, 245, 249, 0.7));
        }

        .micro-nucleus {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.5), rgba(139, 92, 246, 0.4));
            border: 1px solid rgba(99, 102, 241, 0.6);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 4px rgba(99, 102, 241, 0.5);
            animation: microNucleusPulse 8s ease-in-out infinite;
        }

        .micro-chromosome {
            position: absolute;
            background: linear-gradient(45deg, rgba(220, 38, 38, 0.7), rgba(239, 68, 68, 0.5));
            border-radius: 1px;
            box-shadow: 0 0 3px rgba(220, 38, 38, 0.4);
            animation: microChromosomeFloat 15s ease-in-out infinite;
        }

        .micro-chromosome-1 {
            width: 8px;
            height: 1.5px;
            top: 28%;
            left: 18%;
            transform: rotate(45deg);
            animation-delay: 0s;
        }

        .micro-chromosome-2 {
            width: 7px;
            height: 1.5px;
            top: 35%;
            right: 20%;
            transform: rotate(-35deg);
            animation-delay: -5s;
        }

        @keyframes microFloat {
            0%, 100% {
                transform: translate(0px, 0px) rotate(0deg) scale(1);
                opacity: 0.8;
            }
            10% {
                transform: translate(4px, -6px) rotate(36deg) scale(1.08);
                opacity: 0.95;
            }
            20% {
                transform: translate(8px, -2px) rotate(72deg) scale(0.95);
                opacity: 0.9;
            }
            30% {
                transform: translate(2px, 4px) rotate(108deg) scale(1.05);
                opacity: 0.85;
            }
            40% {
                transform: translate(-6px, 6px) rotate(144deg) scale(0.92);
                opacity: 0.9;
            }
            50% {
                transform: translate(-8px, -3px) rotate(180deg) scale(1.02);
                opacity: 0.95;
            }
            60% {
                transform: translate(3px, 8px) rotate(216deg) scale(1.1);
                opacity: 0.88;
            }
            70% {
                transform: translate(7px, 3px) rotate(252deg) scale(0.98);
                opacity: 0.92;
            }
            80% {
                transform: translate(-3px, -7px) rotate(288deg) scale(1.03);
                opacity: 0.87;
            }
            90% {
                transform: translate(-5px, 2px) rotate(324deg) scale(0.97);
                opacity: 0.93;
            }
        }

        @keyframes microMembranePulse {
            0%, 100% {
                border-color: rgba(59, 130, 246, 0.7);
                box-shadow:
                    0 0 6px rgba(59, 130, 246, 0.4),
                    inset 0 0 8px rgba(59, 130, 246, 0.15);
            }
            50% {
                border-color: rgba(59, 130, 246, 0.9);
                box-shadow:
                    0 0 10px rgba(59, 130, 246, 0.6),
                    inset 0 0 12px rgba(59, 130, 246, 0.2);
            }
        }

        @keyframes microNucleusPulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                box-shadow: 0 0 4px rgba(99, 102, 241, 0.5);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.15);
                box-shadow: 0 0 6px rgba(99, 102, 241, 0.7);
            }
        }

        @keyframes microChromosomeFloat {
            0%, 100% {
                transform: rotate(0deg) scale(1);
                opacity: 0.7;
            }
            25% {
                transform: rotate(3deg) scale(1.02);
                opacity: 0.9;
            }
            50% {
                transform: rotate(-2deg) scale(0.98);
                opacity: 0.8;
            }
            75% {
                transform: rotate(1deg) scale(1.01);
                opacity: 1;
            }
        }

        /* 🍎 APPLE-STYLE MINIMAL CONTACT BUTTON */
        .floating-contact {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .contact-toggle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            color: #2196f3;
            border: 1.5px solid rgba(33, 150, 243, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .contact-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12), 0 4px 6px rgba(0, 0, 0, 0.06);
            border-color: rgba(33, 150, 243, 0.4);
        }

        .contact-toggle:active {
            transform: scale(0.98);
        }

        .contact-options {
            position: absolute;
            bottom: 68px;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.9) translateY(10px);
            transform-origin: bottom right;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            min-width: 200px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .contact-options.show {
            opacity: 1;
            visibility: visible;
            transform: scale(1) translateY(0);
            animation: springIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes springIn {
            0% {
                transform: scale(0.8) translateY(10px);
                opacity: 0;
            }
            50% {
                transform: scale(1.02) translateY(-2px);
            }
            100% {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .contact-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #1d1d1f;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }

        .contact-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.04);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .contact-option:hover::before {
            opacity: 1;
        }

        .contact-option:active {
            transform: scale(0.98);
        }

        .contact-option i {
            font-size: 20px;
            width: 20px;
            text-align: center;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .contact-option:hover i {
            transform: scale(1.1);
        }

        .contact-option.telegram {
            color: #0088cc;
        }

        .contact-option.telegram i {
            animation: gentlePulse 2s ease-in-out infinite;
        }

        .contact-option.whatsapp {
            color: #25D366;
        }

        .contact-option.whatsapp i {
            animation: gentlePulse 2s ease-in-out infinite 0.5s;
        }

        @keyframes gentlePulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(0.95);
            }
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .floating-contact {
                bottom: 20px;
                right: 20px;
            }

            .contact-toggle {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
        }

        /* 🔥 EPIC SCROLL REVEAL */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* 🔥 ENHANCED 3D CARD EFFECTS */
        .course-card {
            transform-style: preserve-3d;
        }

        .course-card:hover {
            transform: translate3d(0, -8px, 0);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* 🔥 SMOOTH PAGE LOAD */
        body {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* 🔥 NAVBAR SCROLL EFFECT */
        .navbar.scrolled {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
        }

        /* 🔥 HERO TITLE ANIMATION */
        .hero-title {
            animation: slideInDown 0.8s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 🔥 SCROLL PROGRESS BAR */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196f3, #1976d2);
            z-index: 9999;
            transition: width 0.1s ease;
        }
    </style>
</head>
<body>
    <!-- 🔥 SCROLL PROGRESS BAR -->
    <div class="scroll-progress" id="scrollProgress"></div>
    
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
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
                                @if(Auth::user()->isAdmin())
                                    <span class="badge bg-warning ms-1">Admin</span>
                                @elseif(Auth::user()->isTutor())
                                    <span class="badge bg-info ms-1">Tutor</span>
                                @else
                                    <span class="badge bg-primary ms-1">Student</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="d-flex justify-content-center">
                                    <a href="{{ route('profile') }}" class="streak-indicator-link" style="text-decoration: none;">
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
                                    </a>
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
                                @if(Auth::user()->isAdmin())
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
                                @if(Auth::user()->isTutor())
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
                                    <li>
                                        <a class="dropdown-item" href="{{ route('topic-locking.index') }}">
                                            <i class="fas fa-lock me-2"></i>Topic Management
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
                                        <button type="submit" class="dropdown-item w-100 text-danger logout-btn">
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
                            @php
                                $profileImageUrl = Auth::user()->getProfileImageUrl();
                            @endphp
                            <a href="{{ route('profile') }}" class="profile-circle">
                                <img src="{{ $profileImageUrl }}"
                                     alt="Profile Picture"
                                     class="profile-image"
                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&size=200&background=007bff&color=ffffff'">
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <!-- 🌟 ENHANCED MEDICAL ANIMATION ELEMENTS 🌟 -->

        <!-- Enhanced DNA Double Helix -->
        <div class="enhanced-dna-helix">
            <div class="dna-strand-left"></div>
            <div class="dna-strand-right"></div>
            <!-- DNA connecting bonds -->
            @for($i = 0; $i < 8; $i++)
                <div class="dna-bond" style="top: {{ $i * 15 + 10 }}px; transform: rotate({{ $i * 45 }}deg);"></div>
            @endfor
        </div>

        <!-- Floating Molecules (positioned away from title) -->
        <div class="molecule glucose">
            <div class="atom carbon" style="top: 10px; left: 15px;"></div>
            <div class="atom carbon" style="top: 5px; left: 35px;"></div>
            <div class="atom oxygen" style="top: 25px; left: 10px;"></div>
            <div class="atom oxygen" style="top: 20px; left: 40px;"></div>
            <div class="atom hydrogen" style="top: 15px; left: 5px;"></div>
            <div class="bond" style="top: 15px; left: 20px; width: 15px; transform: rotate(45deg);"></div>
        </div>

        <div class="molecule water">
            <div class="atom oxygen" style="top: 12px; left: 17px;"></div>
            <div class="atom hydrogen" style="top: 5px; left: 10px;"></div>
            <div class="atom hydrogen" style="top: 5px; left: 25px;"></div>
            <div class="bond" style="top: 8px; left: 12px; width: 8px; transform: rotate(-45deg);"></div>
            <div class="bond" style="top: 8px; left: 22px; width: 8px; transform: rotate(45deg);"></div>
        </div>

        <!-- Ultra-Compact Capsule-Shaped Pills -->
        <div class="ultra-capsule capsule-large">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-medium">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-small">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-variant1">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-variant2">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>

        <!-- 🧬 BIOLOGICAL CELL - Clean & Simple -->
        <div class="biological-cell">
            <!-- Cell Membrane -->
            <div class="cell-membrane"></div>

            <!-- Cell Interior -->
            <div class="cell-interior">
                <!-- Nucleus -->
                <div class="cell-nucleus"></div>

                <!-- Scattered Chromosomes -->
                <div class="chromosome chromosome-1"></div>
                <div class="chromosome chromosome-2"></div>
                <div class="chromosome chromosome-3"></div>
                <div class="chromosome chromosome-4"></div>
            </div>
        </div>

        <!-- 🔥 SINGLE RED HEARTBEAT WAVE - LIVE ECG MONITOR 🔥 -->
        <div class="heartbeat-wave heartbeat-wave-primary">
            <svg viewBox="0 0 200 60" class="heartbeat-svg">
                <defs>
                    <linearGradient id="heartbeatGradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#ff0000;stop-opacity:1" />
                        <stop offset="50%" style="stop-color:#ff4444;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#ff0000;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <path d="M0,30 L30,30 L32,10 L34,50 L36,30 L50,30 L52,20 L54,40 L56,30 L200,30"
                      stroke="url(#heartbeatGradient1)" stroke-width="3" fill="none" stroke-linecap="round"
                      class="heartbeat-path"/>
            </svg>
        </div>

        <!-- 🦠 TINY CELL UNDER HEARTBEAT - MICRO ORGANISM 🌟 -->
        <div class="micro-cell">
            <!-- Tiny Cell Membrane -->
            <div class="micro-membrane"></div>

            <!-- Tiny Cell Interior -->
            <div class="micro-interior">
                <!-- Tiny Nucleus -->
                <div class="micro-nucleus"></div>

                <!-- Tiny Chromosomes -->
                <div class="micro-chromosome micro-chromosome-1"></div>
                <div class="micro-chromosome micro-chromosome-2"></div>
            </div>
        </div>

        <h1 class="hero-title">Pick Your Course</h1>
    </section>

    <div class="position-relative">
        <div class="semester-pills">
            <select class="semester-dropdown" id="semesterDropdown" onchange="filterCoursesByAcademicYear()">
                <option value="">All Courses</option>
                @php
                    // Get unique academic years directly from courses table
                    $academicYears = $courses->pluck('academic_year')->filter()->unique()->sort();

                    // Debug: Show academic years in console
                    $debugYears = $courses->pluck('academic_year')->toArray();
                @endphp

                @if($academicYears->isNotEmpty())
                    <optgroup label="Academic Years">
                        @foreach($academicYears as $year)
                            @php
                                // Format the year to be more readable
                                $readableYear = ucwords(strtolower(str_replace('_', ' ', $year)));
                                $readableYear = str_replace(['Year', 'Semester'], ['Year', 'Semester'], $readableYear);
                            @endphp
                            <option value="{{ $year }}" {{ ($selectedYear ?? '') == $year ? 'selected' : '' }}>
                                {{ $readableYear }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            </select>

            {{-- Debug Script to show available academic years --}}
            <script>
                console.log('🔍 DEBUG: Available Academic Years:', @json($debugYears));
                console.log('📋 DEBUG: Filtered Academic Years:', @json($academicYears->toArray()));
                console.log('📊 DEBUG: Total Courses:', @json($courses->count()));
            </script>
        </div>
            
            @guest
            @else
            @endguest
        </div>

        <!-- Scroll Arrows -->
        <button class="scroll-arrow scroll-left" onclick="scrollCourses('left')">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="courses-grid">
{{-- 💀 N+1 FIX NEEDED: Eager load 'topics_count' in controller --}}
            @foreach ($courses as $course)
                <div class="course-card" 
                     style="--course-color: {{ $course->color }}; --course-title-color: {{ $course->title_color }}"
                     data-academic-year="{{ $course->academic_year }}"
                     data-course-id="{{ $course->id }}"
                     data-semester="{{ $course->semester }}">
                    @php
                        $optimizedImageUrl = $course->image_url;
                        // Apply same optimization as preload
                        if (str_contains($optimizedImageUrl, 'unsplash.com')) {
                            $optimizedImageUrl = str_replace('?', '?w=420&h=580&fit=crop&auto=format,compress&q=75&', $optimizedImageUrl);
                        } elseif (str_contains($optimizedImageUrl, 'picsum.photos')) {
                            $optimizedImageUrl = str_replace('picsum.photos/', 'picsum.photos/420/580/', $optimizedImageUrl);
                        }
                    @endphp
                    <div class="course-image"
                         style="background-image: url('{{ $course->image_url }}');"
                         data-bg-image="{{ $course->image_url }}"
                         data-course-name="{{ $course->name }}">
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
                                             style="background-image: url('{{ \App\Models\User::getProfileImageUrlFor((object)$user) }}'); background-size: cover; background-position: center; background-repeat: no-repeat;"
                                         @else
                                             style="background: linear-gradient(135deg, #007bff, #00d4ff);"
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
                            @if (Auth::user()->isEnrolledInCourse($course->id))
                                <!-- User is enrolled - show continue button -->
                                <a href="{{ route('topics.forCourse', $course->id) }}" class="action-btn continue">
                                    <i class="fas fa-play"></i>
                                    Continue Learning
                                </a>
                            @elseif ($course->hasUserPendingPayment(Auth::user()->id))
                                <!-- User has pending payment - show pending button -->
                                <button type="button" class="action-btn enroll" id="paymentBtn_{{ $course->id }}" disabled style="background: #fff7ed; border-color: #fed7aa; color: #ea580c; cursor: not-allowed;">
                                    <i class="fas fa-clock" style="color: #ea580c;"></i>
                                    <span style="color: #ea580c; font-weight: bold;">Payment Pending</span>
                                </button>
                            @else
                                <!-- User is not enrolled and no pending payment - show payment button -->
                                <button type="button" class="action-btn enroll show-payment-modal" id="paymentBtn_{{ $course->id }}" data-course-id="{{ $course->id }}">
                                    <i class="fas fa-unlock"></i>
                                    <span>Unlock</span>
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

    <!-- Milestone Progress - BACKEND OPTIMIZED -->
    @php
        // RUTHLESS: Cache calculations to avoid repeated database queries
        $totalCorrectAnswers = Auth::user()->getTotalCorrectAnswers();
        $progressPercentage = min(($totalCorrectAnswers % 100), 100);
    @endphp
    
    <div class="milestone-container">
        <div class="milestone-stats">
            <span class="correct-count" id="correctCount">{{ $totalCorrectAnswers }}</span>
            <span class="separator">/</span>
            <span class="goal-count" id="goalCount">100</span>
            <span class="questions-text">questions solved</span>
        </div>
        <div class="milestone-progress">
            <div class="liquid-progress">
                <div class="liquid-fill" id="progressFill" style="width: {{ $progressPercentage }}%">
                    <div class="liquid-wave"></div>
                    <div class="liquid-wave"></div>
                    <div class="liquid-wave"></div>
                </div>
            </div>
            <div class="milestone-marks">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="milestone-mark {{ $totalCorrectAnswers >= ($i * 20) ? 'active' : '' }}" 
                         style="left: {{ $i * 20 }}%" 
                         id="mark-{{ $i * 20 }}">
                        <i class="fas fa-check"></i>
                    </div>
                @endfor
            </div>
        </div>
    </div>



    <!-- CRITICAL BOOTSTRAP LOADING FIX - MOVED TO BOTTOM -->
    
    <!-- SUPER LIGHTNING SPEED PERFORMANCE SYSTEM -->
    <script>
        // IMMEDIATE EXECUTION - ZERO DELAYS
        (function() {
            // MINIMAL CRITICAL CSS INJECTION
            const style = document.createElement('style');
            style.textContent = `
                .clean-notification-dropdown { display: none !important; }
                .clean-notification-dropdown.show { display: flex !important; }
                .course-image { opacity: 1 !important; background-color: #f8f9fa; }
                .course-card { opacity: 1 !important; transform: none !important; }
                /* INSTANT SKELETON LOADING */
                .course-image:not([style*="background-image"]) {
                    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                    background-size: 200% 100%;
                    animation: shimmer 1.5s infinite;
                }
                @keyframes shimmer {
                    0% { background-position: -200% 0; }
                    100% { background-position: 200% 0; }
                }
            `;
            document.head.appendChild(style);
        })();

        // SUPER FAST IMAGE LOADING - BATCH PROCESSING
        document.addEventListener('DOMContentLoaded', function() {
            const courseImages = document.querySelectorAll('.course-image[data-bg-image]');
            const imageQueue = Array.from(courseImages);
            let loadedCount = 0;
            
            // LOAD FIRST 2 IMAGES IMMEDIATELY (ABOVE FOLD)
            const immediateImages = imageQueue.slice(0, 2);
            const lazyImages = imageQueue.slice(2);
            
            // IMMEDIATE LOADING FOR VISIBLE IMAGES
            immediateImages.forEach(imageElement => {
                loadImage(imageElement);
            });
            
            // LAZY LOADING FOR REST
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadImage(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '100px' });
            
            lazyImages.forEach(img => observer.observe(img));
            
            function loadImage(imageElement) {
                const imageUrl = imageElement.dataset.bgImage;
                
                // OPTIMIZE IMAGE URL FOR SPEED
                let optimizedUrl = imageUrl;
                if (imageUrl.includes('unsplash.com')) {
                    optimizedUrl = imageUrl.replace('?', '?w=420&h=580&fit=crop&auto=format,compress&q=60&fm=webp&');
                } else if (imageUrl.includes('picsum.photos')) {
                    optimizedUrl = imageUrl.replace('picsum.photos/', 'picsum.photos/420/580/');
                }
                
                const img = new Image();
                img.onload = () => {
                    imageElement.style.backgroundImage = `url('${optimizedUrl}')`;
                    imageElement.style.animation = 'none';
                    loadedCount++;
                    
                    if (loadedCount === 1) {
                        console.log('⚡ FIRST IMAGE LOADED - PAGE FEELS FAST!');
                    }
                };
                img.onerror = () => {
                    imageElement.style.backgroundImage = 'linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%)';
                    imageElement.style.animation = 'none';
                };
                img.src = optimizedUrl;
            }
        });
    </script>
    
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
        let correctAnswers = {{ Auth::user()->getTotalCorrectAnswers() }};
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

        // Add polling mechanism to update progress bar when correct answers change
        function pollUserProgress() {
            // Skip polling if we're on a development/tunneled URL
            if (window.location.hostname.includes('ngrok') ||
                window.location.hostname.includes('localhost') ||
                window.location.hostname.includes('127.0.0.1')) {
                console.log('Skipping progress polling on development environment');
                return;
            }

            fetch('/profile/progress', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.correct_answers_count !== undefined && data.correct_answers_count !== correctAnswers) {
                    updateProgress(data.correct_answers_count);
                    document.getElementById('correctCount').textContent = data.correct_answers_count;
                }
            })
            .catch(error => {
                console.log('Progress polling error (will retry):', error.message);
                // Don't spam console, just log once
                if (!window.progressPollingErrorLogged) {
                    console.warn('Progress polling disabled due to network issues. This is normal in development.');
                    window.progressPollingErrorLogged = true;
                }
            });
        }

        // Poll every 5 seconds for progress updates
        setInterval(pollUserProgress, 5000);

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
    // RUTHLESS: Optimized scroll function with debouncing
    let scrollTimeout;
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

    // RUTHLESS: Debounced scroll arrow updates
    function updateScrollArrows(container = null) {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            const coursesGrid = container || document.querySelector('.courses-grid');
            const leftArrow = document.querySelector('.scroll-left');
            const rightArrow = document.querySelector('.scroll-right');
            
            if (!coursesGrid || !leftArrow || !rightArrow) return;
            
            // Check if there are enough visible cards to need scrolling
            const visibleCards = document.querySelectorAll('.course-card[style*="display: block"], .course-card:not([style*="display: none"])');
            const needsScrolling = visibleCards.length > 3 && coursesGrid.scrollWidth > coursesGrid.offsetWidth;
            
            // PERFORMANCE: Batch DOM operations
            requestAnimationFrame(() => {
                if (!needsScrolling) {
                    leftArrow.style.display = 'none';
                    rightArrow.style.display = 'none';
                } else {
                    leftArrow.style.display = 'flex';
                    rightArrow.style.display = 'flex';
                    leftArrow.style.opacity = coursesGrid.scrollLeft > 0 ? '1' : '0.5';
                    rightArrow.style.opacity = 
                        (coursesGrid.scrollLeft + coursesGrid.offsetWidth) < coursesGrid.scrollWidth 
                            ? '1' : '0.5';
                }
            });
        }, 16); // ~60fps
    }

    // Add optimized scroll event listener
    const coursesGrid = document.querySelector('.courses-grid');
    coursesGrid.addEventListener('scroll', () => {
        updateScrollArrows(coursesGrid);
    }, { passive: true }); // Passive listener for better performance

    // Initial arrow visibility
    document.addEventListener('DOMContentLoaded', () => {
        updateScrollArrows(document.querySelector('.courses-grid'));
    });
    </script>
    <script>
        // FIXED: Consolidated dropdown functionality
        document.addEventListener('DOMContentLoaded', () => {
            const semesterDropdown = document.getElementById('semesterDropdown');
            
            if (semesterDropdown) {
                // Ensure "All Courses" is selected
                semesterDropdown.value = "";
                
                // Trigger filter to show all courses by default
                setTimeout(() => {
                    filterCoursesByAcademicYear();
                }, 100);
            }
        });
    </script>
    <script>
        // Payment Modal Initialization 
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Wait for Bootstrap to be available
                if (typeof bootstrap === 'undefined') {
                    console.warn('Bootstrap not loaded yet, waiting...');
                    setTimeout(() => {
                        initializePaymentModal();
                    }, 1000);
                    return;
                }
                
                initializePaymentModal();
            } catch (e) {
                console.warn('Payment modal initialization error:', e);
            }
        });
        
        function initializePaymentModal() {
            // Safety check for Bootstrap
            if (typeof bootstrap === 'undefined') {
                console.warn('Bootstrap not loaded yet, retrying...');
                setTimeout(initializePaymentModal, 100);
                return;
            }

            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));

            document.querySelectorAll('.show-payment-modal').forEach(button => {
                button.addEventListener('click', function() {
                    const courseId = this.dataset.courseId;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // Fetch payment options and show modal
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
                            document.querySelector('#paymentModal .modal-content').innerHTML = data.html;
                            paymentModal.show();

                            const paymentForm = document.getElementById('coursePaymentForm');
                            paymentForm.addEventListener('submit', function(e) {
                                e.preventDefault();

                                const formData = new FormData(this);
                                const submitBtn = this.querySelector('#submitBtn');
                                submitBtn.disabled = true;
                                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                                fetch(`/payments/${courseId}/process`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken,
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    },
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        paymentModal.hide();
                                        const originalButton = document.getElementById(`paymentBtn_${courseId}`);
                                        originalButton.innerHTML = '<i class="fas fa-clock" style="color: #ff8c00;"></i> <span style="color: #ff8c00; font-weight: bold;">Payment Pending</span>';
                                        originalButton.disabled = true;
                                        originalButton.style.animation = 'successPulse 1s ease-in-out';
                                        originalButton.style.backgroundColor = '#fff7ed'; // Light orange background
                                        originalButton.style.borderColor = '#fed7aa'; // Orange border
                                        originalButton.style.color = '#ea580c'; // Darker orange text
                                        originalButton.querySelector('i').style.color = '#ea580c'; // Darker orange icon
                                        originalButton.querySelector('span').style.color = '#ea580c'; // Darker orange text
                                    } else {
                                        alert(data.message);
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = 'Submit Payment';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred. Please try again.');
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = 'Submit Payment';
                                });
                            });
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            });
        }
    </script>
    
    <!-- Payment Status Management Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 🔥 AGGRESSIVE FUNCTION TO CHECK AND UPDATE PENDING PAYMENT BUTTONS 🔥
            function updatePaymentButtonsForPendingPayments() {
                console.log('🔥 AGGRESSIVE PENDING CHECK: Starting localStorage scan...');
                
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('payment_pending_')) {
                        const courseId = key.replace('payment_pending_', '');
                        console.log(`📋 Found pending payment for course: ${courseId}`);
                        
                        try {
                            // Try to parse as JSON (new format)
                            const pendingData = JSON.parse(localStorage.getItem(key));
                            const timeDiff = Date.now() - pendingData.timestamp;
                            
                            // Clear old pending states (older than 24 hours)
                            if (timeDiff > 24 * 60 * 60 * 1000) {
                                console.log(`🗑️ Clearing old pending state for course: ${courseId}`);
                                localStorage.removeItem(key);
                                return;
                            }
                            
                            console.log(`⏰ Pending state age: ${Math.round(timeDiff / 1000 / 60)} minutes`);
                            
                        } catch (e) {
                            // Handle old format (just 'true' string)
                            console.log(`📝 Converting old format pending state for course: ${courseId}`);
                        }
                        
                        const button = document.querySelector(`#paymentBtn_${courseId}`);
                        
                        if (button) {
                            console.log(`🎯 Found button for course: ${courseId}`);
                            
                            // Check if the server-side state shows the user is already enrolled or has pending payment
                            const isAlreadyPending = button.hasAttribute('disabled');
                            const buttonText = button.textContent.trim();
                            
                            console.log(`Button state - disabled: ${isAlreadyPending}, text: "${buttonText}"`);
                            
                            // If server says user is enrolled, clear localStorage
                            if (buttonText.includes('Access Course') || buttonText.includes('Continue Learning') || button.href) {
                                console.log(`🗑️ User already enrolled, clearing localStorage for course: ${courseId}`);
                                localStorage.removeItem(key);
                                return;
                            }
                            
                            // If server already shows pending, keep it but don't override
                            if (buttonText.includes('Payment Pending')) {
                                console.log(`✅ Server already shows pending state for course: ${courseId}`);
                                return;
                            }
                            
                            // If not already pending on server side, update button
                            if (!isAlreadyPending) {
                                console.log(`⏳ Updating button to pending state for course: ${courseId}`);
                                button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c; margin-left: 8px; font-weight: bold;">Payment Pending</span>';
                                button.classList.remove('btn-primary', 'show-payment-modal', 'enroll');
                                button.classList.add('btn-warning');
                                button.disabled = true;
                                button.style.cursor = 'default';
                                button.style.pointerEvents = 'none';
                                button.style.opacity = '0.8';
                            } else {
                                console.log(`✅ Button already in pending state for course: ${courseId}`);
                            }
                        } else {
                            console.log(`❌ Button not found for course: ${courseId} - may be on different page`);
                        }
                    }
                });
                
                console.log('🏁 AGGRESSIVE PENDING CHECK: Complete');
            }

            // Run the check when page loads
            console.log('🚀 Page loaded - checking for pending payments...');
            console.log('Current user ID from auth:', '{{ auth()->id() ?? "NOT_LOGGED_IN" }}');
            
            // Debug: Check what the server rendered
            console.log('🔍 Debugging server-rendered buttons:');
            document.querySelectorAll('[id^="paymentBtn_"]').forEach(button => {
                const courseId = button.id.replace('paymentBtn_', '');
                const pending = button.getAttribute('data-debug-pending');
                const enrolled = button.getAttribute('data-debug-enrolled');
                const price = button.getAttribute('data-debug-price');
                const disabled = button.disabled;
                const text = button.textContent.trim();
                
                console.log(`Course ${courseId}:`, {
                    pending: pending,
                    enrolled: enrolled,
                    price: price,
                    disabled: disabled,
                    text: text
                });
            });
            
            updatePaymentButtonsForPendingPayments();
            
            // Start real-time payment status checking
            startPaymentStatusPolling();

            // Real-time payment status checking - ULTRA FAST UPDATES
            function startPaymentStatusPolling() {
                // Check every 2 seconds for payment completions
                setInterval(() => {
                    checkPaymentCompletions();
                }, 2000);
            }

            // Check if any pending payments are now completed
            function checkPaymentCompletions() {
                const pendingPayments = [];
                
                // Find all courses with pending payments in localStorage
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('payment_pending_')) {
                        const courseId = key.replace('payment_pending_', '');
                        pendingPayments.push(courseId);
                    }
                });

                if (pendingPayments.length === 0) return;

                // Check server for completion status
                fetch('/api/check-payment-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        course_ids: pendingPayments
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.completed_courses) {
                        data.completed_courses.forEach(courseId => {
                            updateButtonToAccessCourse(courseId);
                            localStorage.removeItem(`payment_pending_${courseId}`);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                });
            }

            // Update button to "Access Course" when payment is completed
            function updateButtonToAccessCourse(courseId) {
                const button = document.querySelector(`#paymentBtn_${courseId}`);
                if (button) {
                    // Convert button to link
                    const newLink = document.createElement('a');
                    newLink.href = `/topics/${courseId}`;
                    newLink.className = 'action-btn enroll enrolled';
                    newLink.innerHTML = '<i class="fas fa-play"></i> <span>Access Course</span>';
                    
                    // Replace button with link
                    button.parentNode.replaceChild(newLink, button);
                    
                    // Add success animation
                    newLink.style.animation = 'successPulse 0.8s ease-in-out';
                    
                    // Show success notification
                    showPaymentCompletedNotification(courseId);
                }
            }

            // Show payment completed notification
            function showPaymentCompletedNotification(courseId) {
                const notification = document.createElement('div');
                notification.className = 'position-fixed top-0 end-0 p-3';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    <div class="toast show" role="alert">
                        <div class="toast-header bg-success text-white">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong class="me-auto">Payment Approved!</strong>
                        </div>
                        <div class="toast-body">
                            Your payment has been approved. You can now access the course!
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                // Auto remove after 5 seconds
                setTimeout(() => notification.remove(), 5000);
            }

            // INSTANT ENROLLMENT UPDATE: Disabled to prevent console errors
            // The main enrollment functionality works fine without real-time polling
            function checkEnrollmentStatus() {
                // Disabled - enrollment updates happen on page refresh
                return;
            }

            // Update course enrollment status instantly
            function updateCourseEnrollmentStatus(courseId, isEnrolled) {
                const courseCard = document.querySelector(`[data-course-id="${courseId}"]`);
                if (!courseCard) return;

                const actionButton = courseCard.querySelector('.action-btn');
                if (!actionButton) return;

                if (isEnrolled) {
                    // Convert to "Start Learning" button
                    if (actionButton.tagName === 'BUTTON') {
                        const newLink = document.createElement('a');
                        newLink.href = `/topics/${courseId}`;
                        newLink.className = 'action-btn continue';
                        newLink.innerHTML = '<i class="fas fa-play"></i> Start Learning';
                        actionButton.parentNode.replaceChild(newLink, actionButton);
                        
                        // Add success animation
                        newLink.style.animation = 'successPulse 0.8s ease-in-out';
                    }
                } else {
                    // Convert to "Pay to Access" button
                    if (actionButton.tagName === 'A') {
                        const newButton = document.createElement('button');
                        newButton.className = 'action-btn enroll';
                        newButton.id = `paymentBtn_${courseId}`;
                        newButton.onclick = () => showPaymentModal(courseId);
                        newButton.innerHTML = '<i class="fas fa-unlock"></i> <span>Unlock</span>';
                        actionButton.parentNode.replaceChild(newButton, actionButton);
                    }
                }
            }

            // Real-time enrollment checking disabled to prevent console errors
            // Enrollment updates happen on page refresh which is sufficient
            @auth
            // setInterval(checkEnrollmentStatus, 2000); // Disabled
            @endauth


        });

        // RUTHLESS: Optimized Academic Year Filtering Function - GLOBAL SCOPE
        let filterTimeout;
        window.filterCoursesByAcademicYear = function filterCoursesByAcademicYear() {
            // PERFORMANCE: Debounce rapid filter changes
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                const selectedYear = document.getElementById('semesterDropdown').value;
                const courseCards = document.querySelectorAll('.course-card');
                
                // PERFORMANCE: Batch DOM operations
                requestAnimationFrame(() => {
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
                });
            }, 100); // Debounce for 100ms
        }



        // RUTHLESS: Optimized initialization on page load
        document.addEventListener('DOMContentLoaded', function() {
            // PERFORMANCE: Batch DOM operations
            requestAnimationFrame(() => {
                // Apply filter on page load if there's a selected year
                const dropdown = document.getElementById('semesterDropdown');
                if (dropdown.value) {
                    filterCoursesByAcademicYear();
                } else {
                    // Ensure all course cards are visible by default
                    const courseCards = document.querySelectorAll('.course-card');
                    courseCards.forEach(card => {
                        card.style.display = 'block';
                        card.style.opacity = '1';
                    });
                }

                updateScrollArrows();
            });
        });

        // Load critical data immediately
        window.MedCapsule = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            baseUrl: window.location.origin,
            profileRoute: '{{ route("profile") }}',
            user: @json(auth()->user() ? ['id' => auth()->id(), 'name' => auth()->user()->name] : null),
            performance: {
                startTime: performance.now(),
                domReady: false
            }
        };
    </script>

    
    <!-- PERFORMANCE MONITORING & BOTTLENECK DETECTION -->
    <script>
        // COMPREHENSIVE PERFORMANCE TESTING SYSTEM
        (function() {
            const perfData = {
                startTime: performance.now(),
                domStart: null,
                domEnd: null,
                imagesLoaded: 0,
                totalImages: 0,
                scriptsLoaded: 0,
                totalScripts: 0,
                cssLoaded: 0,
                totalCSS: 0,
                bottlenecks: []
            };

            // MARK PERFORMANCE MILESTONES
            function markMilestone(name, time = performance.now()) {
                const duration = time - perfData.startTime;
                console.log(`⚡ PERF: ${name} - ${Math.round(duration)}ms`);
                
                if (duration > 500) {
                    perfData.bottlenecks.push({ name, duration });
                }
                
                if (window.performance && window.performance.mark) {
                    window.performance.mark(name);
                }
            }

            // DOM LOADING MEASUREMENT
            if (document.readyState === 'loading') {
                perfData.domStart = performance.now();
                document.addEventListener('DOMContentLoaded', () => {
                    perfData.domEnd = performance.now();
                    markMilestone('DOM_READY', perfData.domEnd);
                });
            } else {
                markMilestone('DOM_ALREADY_READY');
            }

            // MEASURE RESOURCE LOADING
            window.addEventListener('load', () => {
                markMilestone('WINDOW_LOAD');
                
                // COUNT RESOURCES
                perfData.totalImages = document.querySelectorAll('img, [style*="background-image"], [data-bg-image]').length;
                perfData.totalScripts = document.querySelectorAll('script[src]').length;
                perfData.totalCSS = document.querySelectorAll('link[rel="stylesheet"]').length;
                
                // FINAL PERFORMANCE REPORT
                setTimeout(() => {
                    generatePerformanceReport();
                }, 100);
            });

            // TRACK IMAGE LOADING
            function trackImageLoading() {
                const images = document.querySelectorAll('img');
                images.forEach(img => {
                    if (img.complete) {
                        perfData.imagesLoaded++;
                    } else {
                        img.addEventListener('load', () => {
                            perfData.imagesLoaded++;
                            if (perfData.imagesLoaded === images.length) {
                                markMilestone('ALL_IMAGES_LOADED');
                            }
                        });
                        img.addEventListener('error', () => {
                            perfData.imagesLoaded++;
                        });
                    }
                });
            }

            // GENERATE COMPREHENSIVE PERFORMANCE REPORT
            function generatePerformanceReport() {
                const totalTime = performance.now() - perfData.startTime;
                
                console.log(`
🚀 COURSES PAGE PERFORMANCE REPORT
=====================================
📊 TOTAL LOAD TIME: ${Math.round(totalTime)}ms
📄 DOM Ready: ${Math.round(perfData.domEnd - perfData.startTime)}ms
🖼️  Images: ${perfData.imagesLoaded}/${perfData.totalImages}
📜 Scripts: ${perfData.totalScripts}
🎨 CSS Files: ${perfData.totalCSS}

🐌 BOTTLENECKS (>500ms):
${perfData.bottlenecks.map(b => `   - ${b.name}: ${Math.round(b.duration)}ms`).join('\n') || '   None detected!'}

💡 OPTIMIZATION SUGGESTIONS:
${generateOptimizationSuggestions(totalTime)}
                `);

                // SEND PERFORMANCE DATA TO CONSOLE FOR ANALYSIS
                window.PERF_DATA = perfData;
                window.PERF_DATA.totalTime = totalTime;
            }

            function generateOptimizationSuggestions(totalTime) {
                const suggestions = [];
                
                if (totalTime > 2000) {
                    suggestions.push('   🔥 CRITICAL: Page takes >2s - needs immediate optimization');
                }
                if (perfData.totalImages > 10) {
                    suggestions.push('   📸 Consider lazy loading for images');
                }
                if (perfData.totalScripts > 5) {
                    suggestions.push('   📜 Bundle/minify JavaScript files');
                }
                if (perfData.bottlenecks.length > 0) {
                    suggestions.push('   ⚠️  Address bottlenecks listed above');
                }
                
                if (suggestions.length === 0) {
                    suggestions.push('   ✅ Performance looks good!');
                }
                
                return suggestions.join('\n');
            }

            // TRACK RESOURCE LOADING IN REAL-TIME
            document.addEventListener('DOMContentLoaded', () => {
                trackImageLoading();
                
                // MEASURE SPECIFIC COMPONENTS
                setTimeout(() => {
                    const navbar = document.querySelector('.navbar');
                    const coursesGrid = document.querySelector('.courses-grid');
                    const notificationBell = document.querySelector('.clean-notification-bell');
                    
                    if (navbar) markMilestone('NAVBAR_RENDERED');
                    if (coursesGrid) markMilestone('COURSES_GRID_RENDERED');
                    if (notificationBell) markMilestone('NOTIFICATION_BELL_RENDERED');
                }, 10);
            });

            // EXPOSE PERFORMANCE TESTING FUNCTIONS
            window.runPerformanceTest = function() {
                console.log('🧪 Running performance test...');
                const start = performance.now();
                location.reload();
            };

            window.analyzeBottlenecks = function() {
                if (window.PERF_DATA) {
                    console.log('🔍 BOTTLENECK ANALYSIS:', window.PERF_DATA);
                    return window.PERF_DATA;
                } else {
                    console.log('❌ No performance data available. Reload the page first.');
                }
            };

        })();
    </script>

    <!-- SERVICE WORKER FOR LIGHTNING SPEED -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('🚀 Service Worker registered for lightning-fast caching');

                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        console.log('🔄 Service Worker update found');
                    });
                })
                .catch(error => {
                    console.warn('Service Worker registration failed:', error);
                    // Don't let service worker errors break the page
                });
        } else {
            console.log('Service Worker not supported in this browser');
        }
    </script>

    <!-- BOOTSTRAP SCRIPTS - MOVED TO BOTTOM TO AVOID CSP ISSUES -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SIMPLE PROFILE PICTURE FALLBACK - JUST SHOW INITIALS -->
    <script>
        // Simple fallback: Just show user initials if profile picture fails to load
        document.addEventListener('DOMContentLoaded', function() {
            // Handle all profile pictures with simple fallback
            const profileCircles = document.querySelectorAll('.profile-circle, .profile-circle-small');

            profileCircles.forEach(circle => {
                // Add error handler to the profile circle
                circle.addEventListener('error', function() {
                    // Simple fallback - just show initials
                    this.style.backgroundImage = 'none';
                    this.classList.add('no-image');
                    this.style.background = 'linear-gradient(135deg, #007bff, #00d4ff)';
                });

                // If no image URL is set, ensure initials are visible
                const computedStyle = window.getComputedStyle(circle);
                if (!computedStyle.backgroundImage || computedStyle.backgroundImage === 'none') {
                    circle.classList.add('no-image');
                    circle.style.background = 'linear-gradient(135deg, #007bff, #00d4ff)';
                }
            });
        });
    </script>

    <!-- Floating Contact Button -->
    <div class="floating-contact">
        <div class="contact-options" id="contactOptions">
            <a href="https://t.me/Medcapsule1" target="_blank" class="contact-option telegram">
                <i class="fab fa-telegram"></i>
                <span>Join Telegram</span>
            </a>
            <a href="https://wa.me/YOUR_WHATSAPP_NUMBER" target="_blank" class="contact-option whatsapp">
                <i class="fab fa-whatsapp"></i>
                <span>Join WhatsApp</span>
            </a>
        </div>
            </div>

    <script>
        // 🔥 EPIC ENHANCEMENTS JAVASCRIPT
        document.addEventListener('DOMContentLoaded', function() {
            // 🔥 SCROLL PROGRESS BAR
            const scrollProgress = document.getElementById('scrollProgress');
            window.addEventListener('scroll', () => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const progress = (scrollTop / scrollHeight) * 100;
                scrollProgress.style.width = progress + '%';
            });

            // 🔥 SCROLL REVEAL ANIMATION
            const revealElements = document.querySelectorAll('.reveal');
            const revealOnScroll = () => {
                revealElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.classList.add('active');
                    }
                });
            };
            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll();

            // 🔥 NAVBAR SCROLL EFFECT
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // 🔥 SMOOTH SCROLL
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#' && document.querySelector(href)) {
                        e.preventDefault();
                        document.querySelector(href).scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Contact button toggle
            const contactToggle = document.getElementById('contactToggle');
            const contactOptions = document.getElementById('contactOptions');
            
            if (contactToggle && contactOptions) {
                contactToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    contactOptions.classList.toggle('show');
                });
                
                // Close when clicking outside
                document.addEventListener('click', function(e) {
                    if (!contactToggle.contains(e.target) && !contactOptions.contains(e.target)) {
                        contactOptions.classList.remove('show');
                    }
                });
            }
        });
    </script>

</body>
</html>
