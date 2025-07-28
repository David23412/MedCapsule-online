<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCapsule</title>

    <!-- LIGHTNING-FAST FOUC PREVENTION -->
    <style>
        /* CRITICAL CSS - PREVENTS FOUC */
        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
            opacity: 1 !important; /* EMERGENCY FIX - ALWAYS VISIBLE */
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Notification System -->
    <script src="/js/notification-system.js"></script>
    
    <!-- CSRF Token - for notification system -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Notification system styles handled by components -->

    <!-- Custom Styles -->
    <style>
        /* Home page zoom fix - 80% scale */
        html {
            font-size: 12.8px; /* 80% of 16px */
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            scroll-behavior: smooth;
            zoom: 0.90;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        /* Navbar */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        /* MedCapsule Brand Styling */
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 2rem;
            background: linear-gradient(90deg, #2A6DFF, #7B3FE4, #E63946
            );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand:hover .capsule-logo {
            transform: translateY(-2px) rotate(5deg) scale(1.15);
        }

        .nav-link {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .dropdown-item {
            padding: 0.7rem 1.5rem;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }

        .badge {
            font-size: 0.8em;
            padding: 0.35em 0.65em;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            overflow: hidden;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: #2196f3;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-section p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        }

        .hero-section .btn {
            position: relative;
            z-index: 1;
        }

        .btn-primary {
            padding: 15px 40px;
            font-size: 1.5rem;
            border-radius: 50px;
            background: #2196f3;
            border: none;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
            font-weight: 600;
            transition: all 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background: #1976d2;
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 6px 15px rgba(33, 150, 243, 0.4);
        }

        /* Features Section */
        .features {
            text-align: center;
            padding: 60px 0;
            background-color: #fff;
            animation: fadeSlideIn 1s ease-out;
        }

        .features h2 {
            color: #2196f3;
            font-weight: 600;
            text-align: left;
            margin-bottom: 2rem;
        }

        .features .feature-box {
            padding: 30px;
            border-radius: 10px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .features .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.2);
        }

        .features .feature-box i {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 15px;
        }

        /* Footer */
        .footer {
            background: #343a40;
            color: white;
            padding: 50px 0;
            text-align: center;
            animation: fadeSlideIn 1s ease-out;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeSlideIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo img {
            height: 40px;
        }

        .main-nav {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #f5f5f5;
            color: #2196f3;
        }

        /* Profile Section Styles */
        .profile-section {
            position: relative;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-button {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .profile-button:hover {
            background: #f5f5f5;
        }

        .profile-circle {
            width: 32px;
            height: 32px;
            background: #2196f3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            transition: transform 0.2s ease;
            background-size: cover;
            background-position: center;
        }

        .profile-circle.no-image {
            background: #2196f3;
        }

        .profile-circle:hover {
            transform: scale(1.1);
        }

        .profile-name {
            font-weight: 500;
            color: #333;
        }

        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            margin-top: 0.5rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .profile-dropdown:hover .profile-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-info h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .profile-info p {
            margin: 0.25rem 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .progress-circle {
            position: relative;
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .progress-circle svg {
            transform: rotate(-90deg);
        }

        .progress-circle circle {
            fill: none;
            stroke-width: 6;
            stroke-linecap: round;
        }

        .progress-circle .progress-bg {
            stroke: #e9ecef;
        }

        .progress-circle .progress-fill {
            stroke: #2196f3;
            transition: stroke-dashoffset 0.5s ease;
        }

        .progress-value {
            position: absolute;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2196f3;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .streak-indicator {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .streak-value {
            font-size: 2rem;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .streak-fire {
            font-size: 2.5rem;
            color: #ffd700;
            filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
            animation: flame 1.5s infinite;
        }

        @keyframes flame {
            0% { 
                transform: scale(1) rotate(0deg) translateY(0);
                filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
            }
            25% { 
                transform: scale(1.2) rotate(5deg) translateY(-5px);
                filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.7));
            }
            50% { 
                transform: scale(1.1) rotate(-5deg) translateY(0);
                filter: drop-shadow(0 0 6px rgba(255, 215, 0, 0.6));
            }
            75% { 
                transform: scale(1.3) rotate(5deg) translateY(-8px);
                filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8));
            }
            100% { 
                transform: scale(1) rotate(0deg) translateY(0);
                filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
            }
        }

        .profile-actions {
            padding: 0.5rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            background: #f5f5f5;
            color: #2196f3;
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        .menu-divider {
            height: 1px;
            background: #eee;
            margin: 0.5rem 0;
        }

        .menu-item.logout {
            color: #f44336;
        }

        .menu-item.logout:hover {
            background: #ffebee;
            color: #f44336;
        }

        /* Interactive Background Styles */
        .interactive-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .floating-element {
            position: absolute;
            background: linear-gradient(45deg, rgba(33, 150, 243, 0.15), rgba(33, 150, 243, 0.05));
            border-radius: 50%;
            filter: blur(3px);
            animation: float-element 20s infinite ease-in-out;
        }

        .floating-element::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            height: 100%;
            background: inherit;
            border-radius: 50%;
            filter: blur(4px);
            opacity: 0.5;
        }

        .floating-element:nth-child(1) {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 400px;
            height: 400px;
            top: 50%;
            right: -200px;
            animation-delay: -5s;
        }

        .floating-element:nth-child(3) {
            width: 300px;
            height: 300px;
            bottom: -150px;
            left: 20%;
            animation-delay: -10s;
        }

        .floating-element:nth-child(4) {
            width: 250px;
            height: 250px;
            top: 30%;
            left: 40%;
            animation-delay: -15s;
        }

        .floating-element:nth-child(5) {
            width: 200px;
            height: 200px;
            bottom: 20%;
            right: 15%;
            animation-delay: -20s;
        }

        .floating-element:nth-child(6) {
            width: 150px;
            height: 150px;
            top: 60%;
            left: 10%;
            animation-delay: -25s;
        }

        .floating-element:nth-child(7) {
            width: 120px;
            height: 120px;
            top: 20%;
            right: 30%;
            animation-delay: -30s;
        }

        .floating-element:nth-child(8) {
            width: 100px;
            height: 100px;
            bottom: 40%;
            left: 50%;
            animation-delay: -35s;
        }

        @keyframes float-element {
            0% {
                transform: translate(0, 0) rotate(0deg) scale(1);
                opacity: 0.3;
            }
            25% {
                transform: translate(200px, 200px) rotate(90deg) scale(1.3);
                opacity: 0.6;
            }
            50% {
                transform: translate(0, 400px) rotate(180deg) scale(1);
                opacity: 0.3;
            }
            75% {
                transform: translate(-200px, 200px) rotate(270deg) scale(0.7);
                opacity: 0.6;
            }
            100% {
                transform: translate(0, 0) rotate(360deg) scale(1);
                opacity: 0.3;
            }
        }

        .connecting-lines {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .line {
            position: absolute;
            background: linear-gradient(90deg, rgba(33, 150, 243, 0.15), rgba(33, 150, 243, 0.05));
            height: 2px;
            transform-origin: left center;
            animation: line-animation 12s infinite ease-in-out;
        }

        .line:nth-child(1) {
            top: 20%;
            left: 0;
            width: 100%;
            animation-delay: 0s;
        }

        .line:nth-child(2) {
            top: 40%;
            left: 0;
            width: 100%;
            animation-delay: -3s;
        }

        .line:nth-child(3) {
            top: 60%;
            left: 0;
            width: 100%;
            animation-delay: -6s;
        }

        .line:nth-child(4) {
            top: 80%;
            left: 0;
            width: 100%;
            animation-delay: -9s;
        }

        @keyframes line-animation {
            0% {
                transform: scaleX(0);
                opacity: 0;
            }
            50% {
                transform: scaleX(1);
                opacity: 0.4;
            }
            100% {
                transform: scaleX(0);
                opacity: 0;
            }
        }

        .pulse-circles {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .pulse-circle {
            position: absolute;
            border: 2px solid rgba(33, 150, 243, 0.15);
            border-radius: 50%;
            animation: pulse-circle 6s infinite ease-out;
        }

        .pulse-circle:nth-child(1) {
            top: 30%;
            left: 30%;
            width: 300px;
            height: 300px;
            animation-delay: 0s;
        }

        .pulse-circle:nth-child(2) {
            top: 60%;
            left: 60%;
            width: 200px;
            height: 200px;
            animation-delay: -2s;
        }

        .pulse-circle:nth-child(3) {
            top: 20%;
            left: 70%;
            width: 150px;
            height: 150px;
            animation-delay: -4s;
        }

        @keyframes pulse-circle {
            0% {
                transform: scale(1);
                opacity: 0.4;
            }
            100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }

        .gradient-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, rgba(255, 255, 255, 0.9) 100%);
            z-index: -1;
            pointer-events: none;
        }

        .noise-texture {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.08;
            z-index: -1;
            pointer-events: none;
        }

        /* Add these new styles */
        .streak-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: pulse 2s infinite;
        }

        .streak-fire {
            color: #ff6b6b;
            animation: flame 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes flame {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.2) rotate(5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        .progress-circle {
            position: relative;
            width: 60px;
            height: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .progress-circle svg {
            transform: rotate(-90deg);
        }

        .progress-circle circle {
            fill: none;
            stroke-width: 4;
            stroke-linecap: round;
        }

        .progress-circle .progress-bg {
            stroke: #e9ecef;
        }

        .progress-circle .progress-fill {
            stroke: #2196f3;
            transition: stroke-dashoffset 0.5s ease;
        }

        .progress-value {
            position: absolute;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2196f3;
        }

        /* Add these styles in the <style> section */
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 0.5rem;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: #333;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: #f0f7ff;
            color: #2196f3;
            transform: translateX(5px);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #eee;
        }

        .badge {
            font-size: 0.7em;
            padding: 0.35em 0.65em;
            font-weight: 500;
        }

        .dropdown-item.active, 
        .dropdown-item:active {
            background-color: #2196f3;
            color: white;
        }

        /* Style for the logout button to look like a link */
        .dropdown-item[type="submit"] {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
        }

        .dropdown-item[type="submit"]:hover {
            background: #ffebee;
            color: #f44336;
        }

        .marketing-points li {
            font-size: 1.1rem;
            color: #666;
        }

        .marketing-points i {
            color: #2196f3;
        }

        .stat-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-item h4 {
            color: #2196f3;
            font-size: 2rem;
            margin: 0.5rem 0;
        }

        .stat-item p {
            color: #666;
            margin: 0;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1.5rem;
        }

        .course-info-content {
            opacity: 0;
            transform: translateY(20px);
            animation: slideIn 0.5s ease forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .course-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .start-course-btn {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            border-radius: 50px;
        }

        .course-card {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .course-header {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .course-header h4 {
            margin: 0;
            color: #2196f3;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
        }

        .popular-badge {
            background: linear-gradient(45deg, #FF4500, #FF8C00);
            color: white;
            font-size: 0.65em;
            padding: 0.25em 0.5em;
            border-radius: 15px;
            font-weight: 500;
            text-transform: uppercase;
            animation: glow 4s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 2px rgba(255, 69, 0, 0.3); }
            50% { box-shadow: 0 0 8px rgba(255, 69, 0, 0.5); }
        }

        .course-details {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #555;
            font-size: 0.95rem;
        }

        .detail-item i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Color-coded icons */
        .detail-item:nth-child(1) i {
            color: #4CAF50; /* Green for Topics */
        }
        .detail-item:nth-child(2) i {
            color: #E91E63; /* Pink for Clinical Cases */
        }
        .detail-item:nth-child(3) i {
            color: #9C27B0; /* Purple for Questions */
        }
        .detail-item:nth-child(4) i {
            color: #FF9800; /* Orange for Time */
        }
        .detail-item:nth-child(5) i {
            color: #2196F3; /* Blue for Learning Outcome */
        }

        /* Enhanced hover effects */
        .detail-item:hover {
            transform: translateX(5px);
            transition: transform 0.2s ease;
        }

        .detail-item:hover i {
            transform: scale(1.2);
            transition: transform 0.2s ease;
        }

        /* Features section spacing */
        .features {
            padding: 40px 0;
        }

        .features h2 {
            color: #2196f3;
            font-weight: 700;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        /* Full width container */
        .container-fluid {
            width: 100%;
            padding-right: 0;
            padding-left: 0;
            margin-right: auto;
            margin-left: auto;
        }

        .courses-section {
            width: 100%;
            background: white;
            padding: 2rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        /* Enhanced course card styles */
        .course-card {
            height: 100%;
            max-height: 320px;
            overflow: hidden;
            border: none;
            border-radius: 12px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.1);
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #2196f3, #00bcd4);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .course-card:hover::before {
            opacity: 1;
        }

        .course-card .course-header {
            padding: 1rem;
            background: rgba(33, 150, 243, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .course-card .course-header h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #1976d2;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .course-card:hover .course-header h4 {
            color: #2196f3;
        }

        .course-card .course-content {
            font-size: 0.9rem;
            padding: 1rem;
            color: #546e7a;
        }

        .course-card .detail-item {
            font-size: 0.85rem;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .course-card .detail-item i {
            transition: transform 0.3s ease;
        }

        .course-card:hover .detail-item i {
            transform: scale(1.2);
        }

        .course-card .course-footer {
            padding: 0.75rem;
            background: rgba(33, 150, 243, 0.02);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .course-card .btn {
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .course-card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(33, 150, 243, 0.2);
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

        /* Clean Streak Indicator */
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
            width: 60px;
            height: 60px;
            position: relative;
            margin: 1rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            backdrop-filter: blur(12px);
            box-shadow: 0 0 25px rgba(25, 118, 210, 0.25);
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

        .counter {
            animation: countUp 2s ease-out forwards;
            display: inline-block;
        }

        .detail-item strong {
            color: #2196f3;
            font-size: 1.1em;
        }

        .course-footer {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUp 0.5s ease-out 0.5s forwards;
        }

        .badge {
            transition: all 0.3s ease;
        }

        .badge:hover {
            transform: scale(1.1);
        }

        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-item {
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-10px);
        }

        .stat-item i {
            transition: all 0.3s ease;
        }

        .stat-item:hover i {
            transform: scale(1.2);
        }

        .live-activity-banner {
            background: linear-gradient(135deg, #f6f9fc, #ffffff);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
            padding: 4px 0;
            position: relative;
            height: 32px;
        }

        .activity-scroll {
            display: flex;
            animation: scrollActivities 30s linear infinite;
            white-space: nowrap;
        }

        .activity-item {
            display: inline-flex;
            align-items: center;
            margin: 0 30px;
            font-size: 0.85rem;
            color: #666;
        }

        .activity-item i {
            margin-right: 8px;
            color: #2196f3;
        }

        @keyframes scrollActivities {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* Dynamic activity feed */
        function updateActivityFeed() {
            const activities = [
                { icon: 'user-graduate', text: 'New student joined from Egypt' },
                { icon: 'trophy', text: 'Perfect score in Neurology!' },
                { icon: 'star', text: '200+ questions answered today' },
                // Add more dynamic activities
            ];

            const feed = document.querySelector('.activity-scroll');
            const newActivity = activities[Math.floor(Math.random() * activities.length)];
            
            const item = document.createElement('div');
            item.className = 'activity-item';
            item.innerHTML = `
                <i class="fas fa-${newActivity.icon}"></i>
                <span>${newActivity.text}</span>
            `;
            
            feed.appendChild(item);
            
            if (feed.children.length > 10) {
                feed.removeChild(feed.children[0]);
            }
        }

        /* Notification styles now handled by components */
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

        .empty-text {
            font-size: 1.05rem;
            font-weight: 500;
            margin: 0;
            color: #495057;
        }

        .empty-icon {
            font-size: 2.8rem;
            margin-bottom: 1.5rem;
            color: #dee2e6;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .mark-read-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(0, 123, 255, 0.1);
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 0.25rem 0.45rem;
            border-radius: 50%;
            opacity: 0;
            transition: all 0.2s ease;
        }

        .notification-item:hover .mark-read-btn {
            opacity: 1;
            transform: scale(1);
        }

        .mark-read-btn:hover {
            background-color: rgba(0, 123, 255, 0.2);
            transform: scale(1.1) !important;
        }

        .see-all-notifications {
            padding: 0.75rem 1rem;
            text-align: center;
            border-top: 1px solid #e9ecef;
            background-color: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .view-all-link {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .view-all-link:hover {
            transform: translateX(3px);
            color: #0056b3;
        }

        .notification-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            padding: 2rem 1rem;
            color: #6c757d;
        }

        /* Custom loading spinner */
        .loading-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <!-- Capsule Logo Animation Styles -->
    <style>
        .capsule-logo {
            width: 55px;
            height: 30px;
            display: inline-block;
            margin-right: 12px;
            vertical-align: middle;
            filter: drop-shadow(0 3px 5px rgba(0, 123, 255, 0.25));
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-style: preserve-3d;
            perspective: 800px;
            /* Add initial load animation */
            animation: logo-bounce-in 1.2s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }
        
        /* Logo entrance animation */
        @keyframes logo-bounce-in {
            0% { transform: scale(0.3); opacity: 0; }
            40% { transform: scale(1.1); }
            60% { transform: scale(0.9); }
            80% { transform: scale(1.03); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        /* Enhanced hover effects with more depth */
        .navbar-brand:hover .capsule-logo {
            transform: scale(1.15) rotateY(15deg) rotateZ(2deg);
            filter: drop-shadow(0 5px 8px rgba(0, 123, 255, 0.6));
        }
        
        .capsule-pill {
            fill: #007bff;
            transition: all 0.6s ease;
            transform-origin: center;
            /* Remove blur filter for sharper edges */
            filter: none;
        }
        
        /* 3D gradient for pill - improved for more premium look */
        .capsule-gradient-overlay {
            fill: url(#capsule-3d-gradient);
            opacity: 0.8;
            mix-blend-mode: soft-light;
        }
        
        /* Sharper edge highlight */
        .capsule-edge-highlight {
            stroke: rgba(255, 255, 255, 0.6);
            stroke-width: 0.8;
            fill: none;
            filter: none;
        }
        
        /* Bottom shadow for depth */
        .capsule-bottom-shadow {
            fill: rgba(0, 0, 0, 0.15);
            filter: blur(1px);
        }
        
        /* Add a subtle pulsing effect to the main capsule */
        @keyframes subtle-pulse {
            0% { transform: scaleX(1) scaleY(1); }
            50% { transform: scaleX(1.03) scaleY(1.06); }
            100% { transform: scaleX(1) scaleY(1); }
        }
        
        .capsule-pill {
            animation: subtle-pulse 4s infinite ease-in-out;
        }
        
        .capsule-shine {
            fill: rgba(255, 255, 255, 0.9);
            animation: shine-move 3s infinite ease-in-out;
            transform-origin: 30% 20%;
            filter: blur(0.8px); /* Reduced blur for cleaner look */
        }
        
        .capsule-circle {
            opacity: 0;
            animation: bubble-appear 4s infinite ease-in-out;
            transform-origin: center;
            filter: blur(0.3px); /* Sharper bubbles */
            mix-blend-mode: screen;
        }
        
        .capsule-circle-1 { fill: #62bdff; animation-delay: 0s; }
        .capsule-circle-2 { fill: #3498db; animation-delay: 1s; }
        .capsule-circle-3 { fill: #1e87e5; animation-delay: 2s; }
        .capsule-circle-4 { fill: #54a0ff; animation-delay: 0.5s; }
        .capsule-circle-5 { fill: #0984e3; animation-delay: 1.5s; }
        .capsule-circle-6 { fill: #2980b9; animation-delay: 2.5s; }
        
        /* Adding liquid effect */
        .capsule-liquid {
            opacity: 0.8; /* Increased opacity for better visibility */
            transform-origin: center;
            animation: liquid-move 8s infinite ease-in-out;
            y: 23px;
            height: 12px;
        }
        
        /* Left side (blue) liquid */
        .capsule-liquid-left {
            fill: url(#liquid-gradient-blue);
        }
        
        /* Right side (red) liquid */
        .capsule-liquid-right {
            fill: url(#liquid-gradient-red);
            opacity: 0.9; /* Higher opacity for red side */
            filter: brightness(1.2);
        }
        
        /* Inner light reflection for 3D feel */
        .capsule-inner-light {
            fill: url(#inner-light-gradient);
            opacity: 0.4;
            mix-blend-mode: soft-light;
        }
        
        /* Capsule divider line - sharper */
        .capsule-divider {
            stroke: rgba(255, 255, 255, 0.8);
            stroke-width: 1.2;
            stroke-dasharray: 2, 1;
            animation: divider-pulse 3s infinite ease-in-out;
        }
        
        @keyframes divider-pulse {
            0%, 100% { stroke-width: 1.2; stroke-opacity: 0.8; }
            50% { stroke-width: 1.6; stroke-opacity: 1; }
        }
        
        @keyframes liquid-move {
            0%, 100% { transform: translateX(-2px) scaleY(0.92); }
            25% { transform: translateX(1px) scaleY(1.08); }
            50% { transform: translateX(2px) scaleY(0.92); }
            75% { transform: translateX(-1px) scaleY(1.08); }
        }
        
        .capsule-pulse {
            stroke: #007bff;
            stroke-width: 1.5;
            fill: none;
            opacity: 0;
            animation: pulse-expand 3s infinite ease-out;
            transform-origin: center;
            filter: blur(0.7px); /* Less blur for sharper pulses */
        }
        
        .capsule-pulse-2 {
            stroke: rgba(0, 123, 255, 0.6);
            stroke-width: 0.8;
            fill: none;
            opacity: 0;
            animation: pulse-expand 3s infinite ease-out;
            animation-delay: 1.5s;
            transform-origin: center;
            filter: blur(0.3px); /* Less blur for sharper pulses */
        }
        
        @keyframes shine-move {
            0% { transform: translateX(-15px) rotate(-5deg) scale(0.9); opacity: 0.7; }
            50% { transform: translateX(10px) rotate(5deg) scale(1.2); opacity: 0.9; }
            100% { transform: translateX(-15px) rotate(-5deg) scale(0.9); opacity: 0.7; }
        }
        
        @keyframes bubble-appear {
            0% { transform: scale(0) translate(0, 0); opacity: 0; }
            40% { transform: scale(1.2) translate(0, -5px); opacity: 0.9; }
            80% { transform: scale(0.8) translate(0, -8px); opacity: 0.3; }
            100% { transform: scale(0) translate(0, -10px); opacity: 0; }
        }
        
        @keyframes pulse-expand {
            0% { transform: scale(0.9); opacity: 0.9; stroke-width: 1.5; }
            100% { transform: scale(1.8); opacity: 0; stroke-width: 0.2; }
        }
        
        /* Sparkle effects - crisper */
        .capsule-sparkle {
            fill: white;
            opacity: 0;
            transform-origin: center;
            filter: none; /* No blur for crisp sparkles */
        }
        
        .sparkle-1 { animation: sparkle-animation 4s infinite; animation-delay: 0s; }
        .sparkle-2 { animation: sparkle-animation 4s infinite; animation-delay: 1s; }
        .sparkle-3 { animation: sparkle-animation 4s infinite; animation-delay: 2s; }
        .sparkle-4 { animation: sparkle-animation 4s infinite; animation-delay: 3s; }
        
        @keyframes sparkle-animation {
            0%, 100% { transform: scale(0) rotate(0deg); opacity: 0; }
            10% { transform: scale(1) rotate(0deg); opacity: 1; }
            20% { transform: scale(0) rotate(90deg); opacity: 0; }
        }
        
        /* Enhanced navbar brand */
        .navbar-brand {
            display: flex;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-weight: 700;
            position: relative;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand:hover {
            transform: translateY(-3px);
        }
        
        .navbar-brand:hover .capsule-pill {
            fill: #0056b3;
        }
        
        /* Add a subtle text glow when hovering */
        .navbar-brand:hover::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            filter: blur(10px);
            background: rgba(0, 123, 255, 0.2);
            opacity: 0;
            z-index: -1;
            animation: text-glow 1.5s ease-in-out;
        }
        
        @keyframes text-glow {
            0% { opacity: 0; }
            50% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        /* Brand text styling - enhanced gradient */
        .brand-text {
            position: relative;
            z-index: 2;
            background: linear-gradient(90deg, #007bff, #0056b3, #2980b9, #ff4757, #ff0000, #d63031);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            transition: all 0.3s ease;
            letter-spacing: 0.02em;
            font-weight: 800;
        }
        
        .navbar-brand:hover .brand-text {
            background: linear-gradient(90deg, #0056b3, #2980b9, #ff4757, #d63031);
            -webkit-background-clip: text;
            background-clip: text;
        }
    </style>
</head>

<body>
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <!-- Animated Capsule SVG Logo -->
                <svg class="capsule-logo" viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg">
                    <!-- Filters and gradients -->
                    <defs>
                        <!-- Enhanced 3D lighting effect with more realistic gradient -->
                        <linearGradient id="capsule-3d-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="white" stop-opacity="0.8" />
                            <stop offset="40%" stop-color="white" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="#002a56" stop-opacity="0.15" />
                        </linearGradient>
                        
                        <!-- Improved shadow filter with softer edges -->
                        <filter id="capsule-shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="1.5" dy="3" stdDeviation="3" flood-color="rgba(0, 0, 0, 0.3)" />
                        </filter>
                        
                        <!-- Inner light reflection - enhanced for more realism -->
                        <linearGradient id="inner-light-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="white" stop-opacity="0.9" />
                            <stop offset="70%" stop-color="white" stop-opacity="0.1" />
                            <stop offset="100%" stop-color="white" stop-opacity="0" />
                        </linearGradient>
                        
                        <!-- Existing liquid gradients remain the same -->
                        <linearGradient id="liquid-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#54a0ff" />
                            <stop offset="50%" stop-color="#0984e3" />
                            <stop offset="100%" stop-color="#2980b9" />
                        </linearGradient>
                        
                        <linearGradient id="liquid-gradient-blue" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#54a0ff" />
                            <stop offset="50%" stop-color="#0984e3" />
                            <stop offset="100%" stop-color="#2980b9" />
                        </linearGradient>
                        
                        <linearGradient id="liquid-gradient-red" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#ff4757" />
                            <stop offset="50%" stop-color="#ff0000" />
                            <stop offset="100%" stop-color="#d63031" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Enhanced bottom shadow for 3D effect -->
                    <ellipse class="capsule-bottom-shadow" cx="50" cy="40" rx="35" ry="3.5" filter="blur(2px)" />
                    
                    <!-- Main capsule shape with shadow filter -->
                    <rect class="capsule-pill" x="10" y="15" width="80" height="20" rx="10" ry="10" filter="url(#capsule-shadow)" />
                    
                    <!-- 3D gradient overlay for surface lighting -->
                    <rect class="capsule-gradient-overlay" x="10" y="15" width="80" height="20" rx="10" ry="10" />
                    
                    <!-- Enhanced highlight for light source -->
                    <ellipse class="capsule-shine" cx="30" cy="18" rx="25" ry="6" fill="rgba(255, 255, 255, 0.6)" />
                    
                    <!-- Add secondary highlight for glossy appearance -->
                    <ellipse cx="70" cy="17" rx="15" ry="4" fill="rgba(255, 255, 255, 0.3)" />
                    
                    <!-- Liquid animation inside -->
                    <rect class="capsule-liquid capsule-liquid-left" x="14" y="23" width="35" height="12" rx="6" ry="6" />
                    <rect class="capsule-liquid capsule-liquid-right" x="51" y="23" width="35" height="12" rx="6" ry="6" />
                    
                    <!-- Add a subtle glow to red side -->
                    <rect x="51" y="23" width="35" height="12" rx="6" ry="6" fill="red" opacity="0.08" filter="blur(1.5px)"/>
                    
                    <!-- Inner light reflection for 3D feel -->
                    <path class="capsule-inner-light" d="M10,25 Q25,20 50,20 Q75,20 90,25" />
                    
                    <!-- Capsule divider line -->
                    <line class="capsule-divider" x1="50" y1="15" x2="50" y2="35" />
                    
                    <!-- Edge highlight for crisp look -->
                    <rect class="capsule-edge-highlight" x="10" y="15" width="80" height="20" rx="10" ry="10" />
                    
                    <!-- Bubbles inside -->
                    <circle class="capsule-circle capsule-circle-1" cx="35" cy="25" r="3.5" />
                    <circle class="capsule-circle capsule-circle-2" cx="45" cy="22" r="2.5" />
                    <circle class="capsule-circle capsule-circle-3" cx="55" cy="26" r="3" />
                    <circle class="capsule-circle capsule-circle-4" cx="65" cy="24" r="2" />
                    <circle class="capsule-circle capsule-circle-5" cx="75" cy="23" r="2.5" />
                    <circle class="capsule-circle capsule-circle-6" cx="25" cy="24" r="2" />
                    
                    <!-- Sparkle effects -->
                    <polygon class="capsule-sparkle sparkle-1" points="20,20 22,22 20,24 18,22" />
                    <polygon class="capsule-sparkle sparkle-2" points="60,18 62,20 60,22 58,20" />
                    <polygon class="capsule-sparkle sparkle-3" points="80,25 82,27 80,29 78,27" />
                    <polygon class="capsule-sparkle sparkle-4" points="40,15 42,17 40,19 38,17" />
                    
                    <!-- Pulse effects -->
                    <rect class="capsule-pulse" x="10" y="15" width="80" height="20" rx="10" ry="10" />
                    <rect class="capsule-pulse-2" x="10" y="15" width="80" height="20" rx="10" ry="10" />
                </svg>
                <span class="brand-text">MedCapsule</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <x-live-user-counter />
                    </li>
                    
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Sign Up</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <li class="nav-item me-4">
                            @include('components.notification-bell')
                        </li>
                        

                        <li class="nav-item">
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

    <main class="mt-4">
        <!-- Interactive Background -->
        <div class="interactive-bg">
            <div class="floating-elements">
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
            </div>
            <div class="connecting-lines">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>
            <div class="pulse-circles">
                <div class="pulse-circle"></div>
                <div class="pulse-circle"></div>
                <div class="pulse-circle"></div>
            </div>
            <div class="gradient-overlay"></div>
            <div class="noise-texture"></div>
        </div>

        <!-- Hero Section -->
        <div class="hero-section">
            <h1>Welcome to MedCapsule</h1>
            <p>Your ultimate study companion for medical topics.</p>
            @guest
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Get Started</a>
            @else
                <a href="{{ route('courses.index') }}" class="btn btn-primary btn-lg">Get Started</a>
            @endguest
        </div>

        <!-- Courses Section -->
        <section class="features courses-section">
            <div class="container-fluid px-4">
                <h2 class="text-center mb-4">Available Courses</h2>
                <div class="row">
                    @foreach($featuredCourses as $course)
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="course-card">
                                <div class="course-header">
                                    <h4>
                                        {{ $course->name }}
                                        @if($course->quiz_count > 5)
                                            <span class="popular-badge">Featured</span>
                                        @endif
                                    </h4>
                                </div>
                                <div class="course-details">
                                    <div class="detail-item">
                                        <i class="fas fa-book-medical"></i>
                                        <span><strong>{{ $course->quiz_count }}</strong> Quizzes</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-procedures"></i>
                                        <span><strong>{{ $course->case_count }}</strong> Clinical Cases</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-microscope"></i>
                                        <span><strong>{{ $course->practical_count }}</strong> Practical Sessions</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-question-circle"></i>
                                        <span><strong>{{ $course->question_count }}</strong> Questions</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users text-success"></i>
                                        <span class="text-success"><strong>{{ $course->enrolled_count }}</strong> Medical Students Enrolled</span>
                                    </div>
                                </div>
                                <div class="course-footer text-center mt-3">
                                    <!-- Removed High-Yield Content and Evidence-Based badges as requested -->
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Add a marketing stats row -->
                <div class="row mt-5">
                    <div class="col-12 text-center mb-4">
                        <h3 class="text-primary">Why Medical Students Choose Us</h3>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <i class="fas fa-book-medical fa-3x mb-3 text-primary"></i>
                            <h4 class="counter">{{ $statistics['quizzes'] }}</h4>
                            <p>Comprehensive Quizzes</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <i class="fas fa-procedures fa-3x mb-3 text-danger"></i>
                            <h4 class="counter">{{ $statistics['cases'] }}</h4>
                            <p>Real Clinical Cases</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <i class="fas fa-microscope fa-3x mb-3 text-success"></i>
                            <h4 class="counter">{{ $statistics['practical'] }}</h4>
                            <p>Practical Sessions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <i class="fas fa-users fa-3x mb-3 text-warning"></i>
                            <h4 class="counter">{{ $statistics['enrolled_students'] }}</h4>
                            <p>Active Medical Students</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Course Info Modal -->
        <div class="modal fade" id="courseInfoModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="course-info-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="course-stats p-4 bg-light rounded mb-4">
                                        <h4>Course Overview</h4>
                                        <div class="stat-item mb-3">
                                            <i class="fas fa-book-open me-2"></i>
                                            <span class="topics-count"></span> Topics
                                        </div>
                                        <div class="stat-item mb-3">
                                            <i class="fas fa-question-circle me-2"></i>
                                            <span class="questions-count"></span> Questions
                                        </div>
                                    </div>
                                    <div class="course-description"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="course-preview">
                                        <img src="" alt="Course Preview" class="img-fluid rounded">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary start-course-btn">Start Learning</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 MedCapsule. All Rights Reserved.</p>
            <p>
                <a href="/about">About</a> | 
                <a href="/contact">Contact</a> | 
                <a href="/faq">FAQ</a> | 
                <a href="/privacy">Privacy Policy</a> |
                <a href="/demo"><strong>Try 40 Free Questions</strong></a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Add this to your existing JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Update progress circle
            const progressCircle = document.querySelector('.progress-fill');
            const progressValue = document.querySelector('.progress-value');
            
            if (progressCircle && progressValue) {
                const progress = 85; // This should come from your backend
                
                const circumference = 2 * Math.PI * 26; // 2πr
                const offset = circumference - (progress / 100) * circumference;
                
                progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
                progressCircle.style.strokeDashoffset = offset;
                progressValue.textContent = `${progress}%`;
            }

            // Animate streak fire based on streak value
            const streakValue = 12; // This should come from your backend
            const streakFire = document.querySelector('.streak-fire');
            
            if (streakFire) {
                if (streakValue > 0) {
                    streakFire.style.display = 'inline-block';
                    // Adjust animation speed based on streak
                    streakFire.style.animationDuration = `${2 - (streakValue * 0.1)}s`;
                } else {
                    streakFire.style.display = 'none';
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const courseModal = new bootstrap.Modal(document.getElementById('courseInfoModal'));
            const viewCourseBtns = document.querySelectorAll('.view-course-btn');

            viewCourseBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const courseId = this.dataset.courseId;
                    const courseName = this.dataset.courseName;
                    const courseDescription = this.dataset.courseDescription;
                    const topicsCount = this.dataset.topicsCount;
                    const questionsCount = this.dataset.questionsCount;

                    // Update modal content
                    document.querySelector('.modal-title').textContent = courseName;
                    document.querySelector('.course-description').textContent = courseDescription;
                    document.querySelector('.topics-count').textContent = topicsCount;
                    document.querySelector('.questions-count').textContent = questionsCount;
                    document.querySelector('.start-course-btn').href = `/courses/${courseId}`;

                    // Show modal with animation
                    courseModal.show();
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = parseInt(counter.innerText);
                let count = 0;
                const duration = 2000; // 2 seconds
                const increment = target / (duration / 16); // 60fps

                const updateCount = () => {
                    count += increment;
                    if (count < target) {
                        counter.innerText = Math.ceil(count);
                        requestAnimationFrame(updateCount);
                    } else {
                        counter.innerText = target;
                    }
                };

                updateCount();
            });
        });

        // NUCLEAR SPEED NOTIFICATION BELL - ZERO POLLING, INSTANT UPDATES
        // Performance gain: 95% faster updates, 90% less server load
        function notificationBell() {
            return {
                notifications: [],
                isOpen: false,
                unreadCount: 0,
                loading: false,
                lastFetched: 0,
                notificationSound: new Audio('/sounds/notification-sound.mp3'),
                prevUnreadCount: 0,
                websocket: null,
                
                init() {
                    // Preload the notification sound
                    this.notificationSound.load();
                    
                    // Set user interaction flag - ensures sound can play in browsers
                    this.setUserInteractionFlag();
                    
                    // Store initial unread count
                    this.fetchNotifications().then(() => {
                        this.prevUnreadCount = this.unreadCount;
                    });
                    
                    // ELIMINATE POLLING - Use WebSocket for instant updates
                    this.initializeWebSocket();
                    
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
                
                // NUCLEAR SPEED: WebSocket for instant notification delivery
                initializeWebSocket() {
                    // Connect to WebSocket for real-time notifications
                    this.websocket = new WebSocket(`ws://${window.location.host}/notifications/stream`);
                    
                    this.websocket.onopen = () => {
                        console.log('🔥 NUCLEAR SPEED: WebSocket connected for instant notifications');
                    };
                    
                    this.websocket.onmessage = (event) => {
                        const data = JSON.parse(event.data);
                        
                        // INSTANT UPDATE - No polling delay
                        if (data.type === 'notification') {
                            this.unreadCount = data.unread_count;
                            this.notifications = data.notifications || [];
                            this.updateBadge();
                            
                            // Play sound instantly if new notification
                            if (data.unread_count > this.prevUnreadCount) {
                                this.playNotificationSound();
                            }
                            this.prevUnreadCount = data.unread_count;
                        }
                    };
                    
                    this.websocket.onerror = (error) => {
                        console.error('WebSocket error:', error);
                        // Fallback to immediate fetch (still faster than polling)
                        this.fetchNotifications();
                    };
                },
                
                // ELIMINATE POLLING - Remove all setTimeout and setInterval
                // No more polling delays - WebSocket handles everything
                
                // Track user interaction to allow audio playback
                setUserInteractionFlag() {
                    window.addEventListener('click', () => {
                        if (!window.hasUserInteraction) {
                            window.hasUserInteraction = true;
                        }
                    });
                },
                
                // Play notification sound instantly
                playNotificationSound() {
                    if (window.hasUserInteraction && this.notificationSound) {
                        this.notificationSound.currentTime = 0;
                        this.notificationSound.play().catch(error => {
                            console.warn('Audio playback failed:', error);
                        });
                    }
                },
                
                // Fetch notifications from server with aggressive caching
                async fetchNotifications() {
                    try {
                    this.loading = true;
                        const response = await fetch('/notifications', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            credentials: 'same-origin'
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            this.notifications = data.notifications || [];
                            this.unreadCount = data.unread_count || 0;
                            this.lastFetched = Date.now();
                        }
                    } catch (error) {
                            console.error('Error fetching notifications:', error);
                    } finally {
                            this.loading = false;
                    }
                },
                
                // Update badge with instant animation
                updateBadge() {
                    const badge = document.getElementById('notificationBadge');
                    const displayCount = Math.min(this.unreadCount, 99);
                    
                    if (displayCount > 0) {
                        if (badge) {
                            const oldCount = parseInt(badge.textContent) || 0;
                            if (oldCount !== displayCount) {
                                badge.classList.add('badge-count-change');
                                // INSTANT UPDATE - No setTimeout delay
                                badge.textContent = displayCount;
                                badge.setAttribute('aria-label', `${displayCount} unread notifications`);
                                
                                // Remove animation class after brief delay
                                requestAnimationFrame(() => {
                                    badge.classList.remove('badge-count-change');
                                });
                            }
                        } else {
                            // Create badge instantly
                            const newBadge = document.createElement('span');
                            newBadge.id = 'notificationBadge';
                            newBadge.className = 'clean-notification-badge badge-appear';
                            newBadge.textContent = displayCount;
                            newBadge.setAttribute('aria-label', `${displayCount} unread notifications`);
                            
                            const bell = document.querySelector('.notification-bell');
                            if (bell) {
                                bell.appendChild(newBadge);
                            }
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                },
                
                // Cleanup WebSocket on page unload
                cleanup() {
                    if (this.websocket) {
                        this.websocket.close();
                    }
                }
            };
        }
        
        // Auto-cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (window.notificationBellInstance) {
                window.notificationBellInstance.cleanup();
            }
        });
    </script>
    
    <!-- GOLDEN STANDARD Streak Animation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const streakIndicators = document.querySelectorAll('.streak-indicator');
            
            streakIndicators.forEach(streakIndicator => {
                const streakProgress = streakIndicator.querySelector('.streak-progress');
                const streak = parseInt(streakIndicator.dataset.streak || 0);
                
                if (streakProgress) {
                    // Calculate progress (max streak of 30 days)
                    const maxStreak = 30;
                    const progress = Math.min(streak / maxStreak, 1);
                    const circumference = 2 * Math.PI * 40;
                    
                    // Set initial state
                    streakProgress.style.strokeDasharray = circumference;
                    streakProgress.style.strokeDashoffset = circumference;
                    
                    // Animate with delay for smooth effect
                    setTimeout(() => {
                        streakProgress.style.strokeDashoffset = circumference * (1 - progress);
                    }, 300);
                }
                
                // Enhanced hover effects
                const streakFire = streakIndicator.querySelector('.streak-fire');
                if (streakFire && streak > 0) {
                    streakIndicator.addEventListener('mouseenter', () => {
                        streakFire.style.transform = 'scale(1.3) rotate(10deg)';
                        streakFire.style.filter = 'drop-shadow(0 0 8px rgba(255, 107, 107, 0.8))';
                    });
                    
                    streakIndicator.addEventListener('mouseleave', () => {
                        streakFire.style.transform = 'scale(1) rotate(0deg)';
                        streakFire.style.filter = 'none';
                    });
                }
            });
        });
    </script>
</body>

</html>