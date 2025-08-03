<!DOCTYPE html>
<html lang="en">
<head>
    <!-- 🚀 OPTIMIZED: Minimal critical functions only -->
    <script>
        window.startPerformanceTracking=window.startPerformanceTracking||function(){return{startTime:Date.now()}};
        window.endPerformanceTracking=window.endPerformanceTracking||function(){return 0};
        window.loadNotifications=window.loadNotifications||function(){};
        window.initializeNotificationSystem=window.initializeNotificationSystem||function(){};
        window.performanceMetrics=window.performanceMetrics||{loadStartTime:Date.now(),retryAttempts:0,networkErrors:0};
    </script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Disable source maps -->
    <meta name="devtools-disable-sourcemaps" content="true">
    <title>MedCapsule</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <!-- 🚀 OPTIMIZED: Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    

    
    <!-- 🚀 CRITICAL CSS: Instant page render -->
    <style>
        body{font-family:'Poppins',sans-serif;background-color:#f8f9fa;zoom:.72;margin:0;padding:0;line-height:1.6;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
        .navbar{background:rgba(255,255,255,.95);padding:1rem 2rem;box-shadow:0 2px 4px rgba(0,0,0,.1);position:fixed;top:0;left:0;right:0;z-index:1000;backdrop-filter:blur(10px)}
        .navbar-brand{color:#007bff;font-weight:700;font-size:1.5rem;text-decoration:none;display:flex;align-items:center;transition:transform .3s ease}
        .course-header{background:linear-gradient(135deg,#0072ff,#00c6ff);color:white;padding:4rem 3rem 4rem;padding-top:6rem;text-align:center;margin-bottom:2rem;position:relative;overflow:hidden;min-height:280px;display:flex;align-items:center;justify-content:center}
        .topics-container{max-width:1100px;margin:0 auto;padding:2rem}
        .topic-card{background:white;border-radius:16px;padding:2rem 2.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.08);transition:all .3s ease;animation:slideIn .5s ease-out forwards;opacity:0;transform:translateY(20px);position:relative;overflow:hidden;border:1px solid rgba(0,0,0,.04)}
        @keyframes slideIn{to{opacity:1;transform:translateY(0)}}
        .case-filters{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;margin-bottom:2rem}
        .case-filter{padding:.4rem 1.2rem;border-radius:50px;font-size:.85rem;font-weight:500;cursor:pointer;transition:all .2s ease;border:1px solid #e9ecef;background:white;color:#6c757d;display:flex;align-items:center;gap:.5rem;box-shadow:0 2px 4px rgba(0,0,0,.05)}
        .case-filter:hover{transform:translateY(-1px);box-shadow:0 3px 6px rgba(0,0,0,.08);border-color:#dee2e6}
        .case-filter.active{background:#0d6efd;color:white;border-color:#0d6efd}
        .case-filter[data-filter="cases"] i,.case-filter[data-filter="cases"] span:not(.count),.case-filter[data-filter="cases"] .count{color:#ff8f00}
        .case-filter[data-filter="practical"] i,.case-filter[data-filter="practical"] span:not(.count),.case-filter[data-filter="practical"] .count{color:#7b1fa2}
        .case-filter[data-filter="quiz"] i,.case-filter[data-filter="quiz"] span:not(.count),.case-filter[data-filter="quiz"] .count{color:#2e7d32}
        .case-filter[data-filter="awaly"],.case-filter[data-filter="homework"]{color:#ff8f00}
        .case-filter[data-filter="agamlo"],.case-filter[data-filter="lab"]{color:#7b1fa2}
        .case-filter[data-filter="easy"],.case-filter[data-filter="reading"]{color:#2e7d32}
        .case-filter.active i,.case-filter.active span{color:white!important}
        .case-filter .count{background:rgba(0,0,0,.05);padding:.15rem .5rem;border-radius:50px;font-size:.75rem;min-width:1.2rem;text-align:center}
        .case-filter.active .count{background:rgba(255,255,255,.2);color:white!important}
        .random-link,.mistakes-link{text-decoration:none;font-weight:500;display:flex;align-items:center;gap:.5rem;padding:.4rem 1.2rem;border-radius:50px;transition:all .3s ease}
        .random-link{color:#fd7e14;background:#fff8e1;border:1px solid #ffd54f;order:98!important}
        .mistakes-link{color:#dc3545;background:#ffebee;border:1px solid #ef9a9a;order:99!important}
        .random-link:hover{background:#ffe082;color:#f57c00;transform:translateY(-1px)}
        .mistakes-link:hover{background:#ffcdd2;color:#c62828;transform:translateY(-1px)}
        .assignment-circle{position:relative;width:40px;height:40px;background:#fff5f5!important;border:1px solid #fed7d7!important;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .3s ease;animation:assignmentPulse 3s infinite}
        .assignment-circle i{color:#c53030!important;font-size:1rem}
        .assignment-circle.active{background:#c53030!important;border-color:#c53030!important}
        .assignment-circle.active i{color:white!important}
        .assignment-glow-count{position:absolute;top:-8px;right:-8px;background:rgba(197,48,48,.1)!important;color:#c53030!important;font-size:.7rem;font-weight:700;min-width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center}
        @keyframes assignmentPulse{0%,100%{box-shadow:0 2px 4px rgba(0,0,0,.05),0 0 0 0 rgba(197,48,48,.3)}50%{box-shadow:0 2px 4px rgba(0,0,0,.05),0 0 0 4px rgba(197,48,48,.1)}}
        
        /* SIMPLE RED LOCK - ONLY COLOR CHANGE AND SLIGHTLY BIGGER SIZE */
        .assignment-locked-icon i { 
            color: #dc3545 !important; 
            font-size: 1.3rem !important;
        }
        
        /* ZERO-TRANSITION BUTTON OPTIMIZATIONS */
        .start-quiz-btn[data-processing="true"] {
            pointer-events: none;
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* REGULAR TOPIC LOCK STYLING - WHITE BUTTON WITH WHITE OUTLINE */
        .topic-locked-btn {
            background: white !important;
            color: #dc3545 !important;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 2px solid white !important;
            cursor: not-allowed;
            pointer-events: none;
            margin-top: 1rem;
            min-width: 140px;
            min-height: 48px;
        }

        .topic-locked-btn i {
            color: #dc3545 !important;
            font-size: 1.2rem !important;
        }

        .topic-locked-btn:hover {
            background: white !important;
            color: #dc3545 !important;
            transform: none !important;
        }
    </style>
    <style>
        body{font-family:'Poppins',sans-serif;background-color:#f8f9fa;zoom:.72;margin:0;padding:0;line-height:1.6;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
        .modal-backdrop{background:rgba(0,0,0,.5)!important;position:fixed!important;top:0!important;left:0!important;width:100%!important;height:100%!important;z-index:1040!important;opacity:1!important}

        .navbar{background:rgba(255,255,255,.95);padding:1rem 2rem;box-shadow:0 2px 4px rgba(0,0,0,.1);position:fixed;top:0;left:0;right:0;z-index:1000;backdrop-filter:blur(10px)}
        .navbar-brand{color:#007bff;font-weight:700;font-size:1.5rem;text-decoration:none;display:flex;align-items:center;transition:transform .3s ease}
        .navbar-brand:hover{color:#0056b3;transform:translateY(-1px)}
        @keyframes float-particle{0%,100%{transform:translate(0,0) rotate(0deg)}25%{transform:translate(10px,-10px) rotate(5deg)}50%{transform:translate(0,-15px) rotate(0deg)}75%{transform:translate(-10px,-10px) rotate(-5deg)}}
        @keyframes shimmer{0%{background-position:-1000px 0}100%{background-position:1000px 0}}
        .course-header{background:linear-gradient(135deg,#0072ff,#00c6ff);color:white;padding:4rem 3rem 4rem;padding-top:6rem;text-align:center;margin-bottom:2rem;position:relative;overflow:hidden;min-height:280px;display:flex;align-items:center;justify-content:center}
        .course-header::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);background-size:1000px 100%;animation:shimmer 8s linear infinite}
        .particle{position:absolute;width:3px;height:3px;background:rgba(255,255,255,.3);border-radius:50%;pointer-events:none;filter:blur(.5px)}
        .particle:nth-child(1){top:15%;left:10%;animation:float-particle 6s infinite}
        .particle:nth-child(2){top:25%;right:15%;animation:float-particle 7s infinite .5s}
        .particle:nth-child(3){top:35%;left:25%;animation:float-particle 8s infinite 1s}
        .particle:nth-child(4){top:20%;right:25%;animation:float-particle 7.5s infinite 1.5s}
        .particle:nth-child(5){top:30%;left:35%;animation:float-particle 6.5s infinite 2s}
        .particle:nth-child(6){top:40%;right:35%;animation:float-particle 7.2s infinite 2.5s}
        .particle:nth-child(7){top:25%;left:45%;animation:float-particle 6.8s infinite 3s}
        .particle:nth-child(8){top:35%;right:45%;animation:float-particle 7.8s infinite 3.5s}

        .topics-container{max-width:1100px;margin:0 auto;padding:2rem}
        .topic-card{background:white;border-radius:16px;padding:2rem 2.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.08);transition:all .3s ease;animation:slideIn .5s ease-out forwards;opacity:0;transform:translateY(20px);position:relative;overflow:hidden;border:1px solid rgba(0,0,0,.04)}
        .topic-card[data-has-assignment="true"]{display:none!important}
        .assignments-filter-active .topic-card[data-has-assignment="true"]{display:block!important;opacity:1!important;transform:translateY(0)!important}
        .assignments-filter-active .topic-card.topic-locked[data-has-assignment="true"]{display:block!important;opacity:1!important;transform:translateY(0) scale(1)!important}
        .assignments-filter-active .topic-card.topic-locked[data-has-assignment="true"] *{opacity:1!important;visibility:visible!important}
        
        /* Safari-specific sleek adjustments */
        @media screen and (-webkit-min-device-pixel-ratio:0) {
            .topic-card {
                padding: 1.5rem 2.5rem;
                margin-bottom: 1rem;
                border-radius: 18px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            }
            
            .assignment-status-bar {
                margin: -1.5rem -2.5rem 1rem -2.5rem;
                padding: 0.35rem 2.5rem;
                width: calc(100% + 5rem);
                border-radius: 18px 18px 0 0;
                background: #fff3cd;
                border-bottom: 1px solid #ffeaa7;
                color: #856404;
                font-size: 0.75rem;
                font-weight: 500;
            }
            
            .assignment-status-bar.assignment-active {
                background: #fff3cd;
                color: #856404;
            }
            
            .assignment-status-bar.assignment-overdue {
                background: #f8d7da;
                color: #721c24;
                border-bottom-color: #f5c6cb;
            }
            
            .assignment-status-bar.assignment-completed {
                background: #d4edda;
                color: #155724;
                border-bottom-color: #c3e6cb;
            }
            
            .assignment-info-minimal {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.7rem;
            }
            
            .topics-container {
                max-width: 1400px;
                padding: 1.5rem 3rem;
            }
            
            .topic-info {
                margin-bottom: 1rem;
            }
            
            .progress-container {
                margin-top: 1rem;
            }
            
            .topic-actions {
                margin-top: 0.75rem;
            }
            
            /* Source Selection Modal - slightly wider for Safari */
            #sourceSelectionModal .modal-dialog {
                max-width: 540px;
            }
            
            #sourceSelectionModal .modal-body {
                padding: 1.75rem;
            }
            
            /* Navbar positioning fix for Safari */
            .navbar .container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
            
            .navbar-brand {
                margin-right: auto;
            }
            

        }

        .topic-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: rgba(0, 123, 255, 0.1);
        }

        /* Assignment Card Styling */
        .assignment-card {
            border-left: 4px solid #ffc107;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        }

        .assignment-card:hover {
            border-left-color: #ff9800;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
        }

        .assignment-card .topic-name i {
            color: #ffc107;
        }

        .assignment-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .assignment-description i {
            color: #007bff;
            font-size: 0.8rem;
        }

        .case-type-badge.assignment {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
        }

        /* Assignment Card Enhancements */
        .assignment-card {
            position: relative;
            overflow: hidden;
        }

        .assignment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ffc107, #ff9800);
            z-index: 1;
        }

        .assignment-card.assignment-completed {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
        }

        .assignment-card.assignment-completed::before {
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .assignment-card.assignment-overdue {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }

        .assignment-card.assignment-overdue::before {
            background: linear-gradient(90deg, #dc3545, #e74c3c);
        }

        .assignment-start-btn {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            border: none;
            color: white;
            font-weight: 600;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .assignment-start-btn:hover {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
        }

        .assignment-start-btn:active {
            background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);
        }

        .topic-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .topic-main {
            flex: 1;
            padding-right: 1.5rem;
        }

        .topic-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            line-height: 1.3;
        }

        .topic-name i {
            color: #007bff;
            font-size: 1rem;
        }

        .topic-description {
            display: none;
        }

        .topic-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .progress-container {
            flex: 1;
            margin-top: 1.25rem;
        }

        .progress {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 0.6rem;
        }

        .progress-bar {
            height: 100%;
            border-radius: 5px;
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(90deg, #007bff, #00bcd4);
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(255,255,255,0) 0%, 
                rgba(255,255,255,0.3) 50%, 
                rgba(255,255,255,0) 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .start-quiz-btn {
            background: #007bff;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            margin-top: 1rem;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .start-quiz-btn:hover {
            background: #0056b3;
            color: white;
        }

        .start-quiz-btn:active {
            background: #004085;
        }

        .grade-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .grade-mastered {
            background-color: #e3fcef;
            color: #00a854;
        }

        .grade-good {
            background-color: #fff7e6;
            color: #fa8c16;
        }

        .grade-in-progress {
            background-color: #e6f7ff;
            color: #1890ff;
        }

        .grade-bad {
            background-color: #fff1f0;
            color: #f5222d;
        }

        .grade-none {
            background-color: #f5f5f5;
            color: #8c8c8c;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .topic-card:nth-child(1) { animation-delay: 0.1s; }
        .topic-card:nth-child(2) { animation-delay: 0.2s; }
        .topic-card:nth-child(3) { animation-delay: 0.3s; }
        .topic-card:nth-child(4) { animation-delay: 0.4s; }
        .topic-card:nth-child(5) { animation-delay: 0.5s; }

        .nav-link {
            color: #495057;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-circle {
            width: 36px;
            height: 36px;
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
            text-decoration: none;
        }

        .profile-circle.no-image {
            background: #2196f3;
        }

        .profile-circle:hover {
            transform: scale(1.1);
            color: white;
            text-decoration: none;
        }

        .mistakes-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            background: #ffebee;
            border: 1px solid #ef9a9a;
            transition: all 0.3s ease;
        }

        .mistakes-link:hover {
            background: #ffcdd2;
            color: #c62828;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(198, 40, 40, 0.1);
        }

        .course-progress {
            background: rgba(255, 255, 255, 0.15);
            padding: 1.25rem 1.75rem;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            gap: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            max-width: 400px;
            margin: 0;
        }

        .course-progress:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .progress-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .progress-circle::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        .progress-circle::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.2);
            animation: pulseRing 2s infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes pulseRing {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.3;
            }
            100% {
                transform: scale(1);
                opacity: 0.6;
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .course-progress:hover .progress-circle {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.15);
        }

        .progress-text {
            text-align: left;
            transition: all 0.3s ease;
        }

        .progress-text-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: white;
        }

        .progress-text-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .course-progress:hover .progress-text-subtitle {
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .last-attempt {
            font-size: 0.9rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .last-attempt i {
            color: #007bff;
        }

        .topic-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .topic-card[data-case-type] {
            transition: all 0.3s ease;
        }

        .topic-card.hidden {
            display: none;
        }

        .case-type-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .case-type-badge.quiz {
            background: #e8f5e9;  
            color: #2e7d32;      
        }

        .case-type-badge.cases {
            background: #fff8e1;  
            color: #ff8f00;      
        }

        .case-type-badge.practical {
            background: #f3e5f5;  
            color: #7b1fa2;      
        }

        /* Custom course badge colors - matching medical course colors */
        .case-type-badge.awaly {
            background: #fff3e0;  
            color: #ff8f00;      /* Orange - same as Cases */
        }

        .case-type-badge.agamlo {
            background: #f3e5f5;  
            color: #7b1fa2;      /* Purple - same as Practical */
        }

        .case-type-badge.easy {
            background: #e8f5e9;  
            color: #2e7d32;      /* Green - same as Quiz */
        }

        .case-type-badge i {
            font-size: 0.8rem;
        }

        .random-link {
            color: #fd7e14;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            background: #fff8e1;
            border: 1px solid #ffd54f;
            transition: all 0.3s ease;
        }

        .random-link:hover {
            background: #ffe082;
            color: #f57c00;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(255, 152, 0, 0.1);
        }

        /* Random Quiz Modal Styles */
        #randomQuizModal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            background: white;
        }

        #randomQuizModal .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        #randomQuizModal .modal-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        #randomQuizModal .modal-icon-wrapper {
            width: 40px;
            height: 40px;
            background: #fff8e1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #randomQuizModal .modal-icon-wrapper i {
            color: #ffc107;
            font-size: 1.2rem;
        }

        #randomQuizModal .modal-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.25rem;
        }

        #randomQuizModal .modal-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 400;
        }

        #randomQuizModal .modal-body {
            padding: 1.5rem;
        }

        #randomQuizModal .setting-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        #randomQuizModal .setting-header i {
            font-size: 1.1rem;
            color: #ffc107;
        }

        #randomQuizModal .setting-header .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
        }

        #randomQuizModal .courses-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        #randomQuizModal .custom-checkbox {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #randomQuizModal .custom-checkbox:hover {
            background: rgba(255, 193, 7, 0.05);
        }

        #randomQuizModal .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.2em;
            border: 2px solid #dee2e6;
            transition: all 0.2s ease;
        }

        #randomQuizModal .form-check-input:checked {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        #randomQuizModal .form-check-label {
            color: #495057;
            font-size: 0.95rem;
            padding-left: 0.5rem;
        }

        #randomQuizModal .custom-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: #495057;
            transition: all 0.2s ease;
        }

        #randomQuizModal .custom-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.1);
        }

        #randomQuizModal .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        #randomQuizModal .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #2c3e50;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #randomQuizModal .btn-warning:hover {
            background-color: #ffca2c;
            border-color: #ffca2c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
        }

        #randomQuizModal .btn-outline-secondary {
            border: 2px solid #dee2e6;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #randomQuizModal .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #495057;
            transform: translateY(-1px);
        }

        #randomQuizModal .btn-close {
            opacity: 0.5;
            transition: all 0.2s ease;
        }

        #randomQuizModal .btn-close:hover {
            opacity: 1;
        }

        /* Custom scrollbar for courses container */
        #randomQuizModal .courses-container::-webkit-scrollbar {
            width: 6px;
        }

        #randomQuizModal .courses-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #randomQuizModal .courses-container::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
        }

        #randomQuizModal .courses-container::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }

        /* Sources container styles */
        .sources-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.5rem;
        }

        /* Source selection styles - minimalistic design */
        #sourceSelectionModal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            background: white;
        }

        #sourceSelectionModal .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
        }

        #sourceSelectionModal .modal-icon-wrapper {
            width: 40px;
            height: 40px;
            background: #f0f7ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
        }

        #sourceSelectionModal .modal-icon-wrapper i {
            color: #007bff;
            font-size: 1.2rem;
        }

        #sourceSelectionModal .modal-body {
            padding: 1.5rem;
        }

        #sourceSelectionModal .sources-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 0;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar {
            width: 6px;
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }

        #sourceSelectionModal .custom-checkbox {
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 0.75rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: flex-start;
        }

        #sourceSelectionModal .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 4px;
        }

        /* Fix checkbox alignment */
        #sourceSelectionModal .form-check-input {
            margin-top: 0.2rem;
            width: 1.2rem;
            height: 1.2rem;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }

        #sourceSelectionModal .custom-checkbox:last-child {
            margin-bottom: 0;
        }

        #sourceSelectionModal .custom-checkbox:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        #sourceSelectionModal .form-check-input {
            margin-top: 0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            position: relative;
            flex-shrink: 0;
        }

        #sourceSelectionModal .form-check-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding-left: 0;
        }

        #sourceSelectionModal .text-muted {
            font-size: 0.95rem;
            font-weight: 500;
            min-width: 30px;
            text-align: right;
        }

        #sourceSelectionModal .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #sourceSelectionModal .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        #sourceSelectionModal .source-info {
            font-size: 0.9rem;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        
        /* Source completion status indicators */
        .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 5px; /* Increased from 4px */
        }
        
        .source-status.completed {
            background-color: #28a745;
        }
        
        .source-status.in-progress {
            background-color: #ffc107;
        }
        
        .source-status.not-started {
            background-color: #dee2e6;
        }
        
        .source-completion-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            font-weight: 600;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .source-completion-badge.completed {
            background-color: #d4edda;
            color: #28a745;
        }
        
        .source-completion-badge.in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .source-progress-bar {
            height: 5px;
            width: 100%;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
            position: relative;
        }
        
        .source-progress-value {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00bcd4);
            border-radius: 2px;
            transition: width 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .source-info-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .source-info-title i {
            color: #007bff;
        }
        
        .source-info-text {
            margin-bottom: 0;
            line-height: 1.4;
        }

        #sourceSelectionModal .d-flex.align-items-center {
            min-height: 28px; /* Ensure consistent height for the source name and badge */
        }

        #sourceSelectionModal .form-check-label > div {
            flex: 1;
            padding-right: 10px;
        }

        #sourceSelectionModal .form-check-label span:first-child {
            font-weight: 500;
            font-size: 1rem;
        }

        /* Source card styles */
        .source-card {
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            background: transparent;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .source-card:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .source-card.selected {
            background-color: rgba(0, 123, 255, 0.03);
        }

        .source-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: #1a202c;
        }

        .source-status-text {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .source-status-text.completed {
            color: #28a745;
        }

        .source-status-text.in-progress {
            color: #ffc107;
        }

        .source-status-text.failed {
            color: #dc3545;
        }

        .source-status-text.not-started {
            color: #6c757d;
        }

        .source-progress-bar {
            height: 4px;
            width: 100%;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 0.5rem 0;
            position: relative;
        }

        .source-progress-value {
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
        }

        .source-progress-value.completed {
            background: linear-gradient(90deg, #007bff, #00bcd4);
        }

        .source-progress-value.in-progress {
            background: linear-gradient(90deg, #007bff, #00bcd4);
        }

        .source-progress-value.failed {
            background: linear-gradient(90deg, #dc3545, #ff6b6b);
        }

        .source-stats {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .questions-count {
            font-size: 0.8rem;
            color: #64748b;
        }

        .source-info-wrapper {
            width: 100%;
        }

        .form-check-input {
            margin-top: 0;
            cursor: pointer;
        }

        .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 3px;
        }

        .source-status.completed {
            background-color: #28a745;
        }

        .source-status.in-progress {
            background-color: #ffc107;
        }

        .source-status.failed {
            background-color: #dc3545;
        }

        .source-status.not-started {
            background-color: #e9ecef;
        }

        /* Mode Selection Styles */
        .mode-selection {
            display: none;
        }
        
        /* Simple mode toggle styles */
        .form-check-inline {
            margin-right: 1rem;
        }
        
        .form-check-inline .form-check-input {
            margin-right: 0.5rem;
        }
        
        .form-check-inline .form-check-label {
            color: #495057;
            font-size: 0.9rem;
        }
        
        .form-check-input:checked ~ .form-check-label {
            color: #007bff;
            font-weight: 500;
        }

        /* Assignment Badge Styles */
        .assignment-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 1rem;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .assignment-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 3s infinite;
        }

        .assignment-badge.assignment-active {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border: 2px solid #ff8c42;
        }

        .assignment-badge.assignment-overdue {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #dc3545;
            animation: urgentPulse 1s infinite;
        }

        .assignment-badge.assignment-completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: 2px solid #28a745;
        }

        .assignment-countdown {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
            padding: 0.2rem 0.6rem;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        @keyframes urgentPulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(220,53,69,0.3);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 4px 16px rgba(220,53,69,0.5);
            }
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Assignment Filter Specific Styling */
        .assignment-filter {
            background: #fff5f5 !important;
            border-color: #fed7d7 !important;
            color: #c53030 !important;
            animation: assignmentPulse 3s infinite;
        }

        .assignment-filter:hover {
            background: #fed7d7 !important;
            border-color: #fc8181 !important;
            color: #9b2c2c !important;
        }

        .assignment-filter.active {
            background: #c53030 !important;
            border-color: #c53030 !important;
            color: white !important;
        }

        .assignment-filter .assignment-count {
            background: rgba(197, 48, 48, 0.1) !important;
            color: #c53030 !important;
        }

        .assignment-filter.active .assignment-count {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }

        @keyframes assignmentPulse {
            0%, 100% { 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05), 0 0 0 0 rgba(197, 48, 48, 0.3);
            }
            50% { 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05), 0 0 0 4px rgba(197, 48, 48, 0.1);
            }
        }

        /* Premium Assignment Status Banner */
        .assignment-status-banner {
            margin: -2rem -2rem 0 -2rem;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            background: transparent;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
        }

        .assignment-status-banner.assignment-active {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.08) 0%, rgba(255, 193, 7, 0.05) 100%);
            border-top: 2px solid #ff9800;
        }

        .assignment-status-banner.assignment-overdue {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.08) 0%, rgba(255, 87, 34, 0.05) 100%);
            border-top: 2px solid #f44336;
            animation: premiumUrgent 4s ease-in-out infinite;
        }

        .assignment-status-banner.assignment-completed {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.08) 0%, rgba(139, 195, 74, 0.05) 100%);
            border-top: 2px solid #4caf50;
        }

        .assignment-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: #374151;
            padding: 1rem 2rem;
            flex: 1;
        }

        .assignment-info i {
            font-size: 1rem;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .assignment-info.overdue {
            color: #dc2626;
        }

        .assignment-info.overdue i {
            color: #dc2626;
            opacity: 1;
        }

        .assignment-info.completed {
            color: #059669;
        }

        .assignment-info.completed i {
            color: #059669;
            opacity: 1;
        }

        .assignment-countdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            color: #374151;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(10px);
            margin-right: 2rem;
            transition: all 0.3s ease;
        }

        .assignment-countdown:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .countdown-display {
            letter-spacing: 0.5px;
            font-variant-numeric: tabular-nums;
        }

        .overdue-text, .completed-text {
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(10px);
            margin-right: 2rem;
        }

        .overdue-text {
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.2);
        }

        .completed-text {
            color: #059669;
            border-color: rgba(5, 150, 105, 0.2);
        }

        /* Premium Locked Topic Styling */
        .topic-locked {
            position: relative;
            transform: scale(0.98);
            transition: all 0.3s ease;
        }

        .topic-locked::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.7) 0%, rgba(248, 250, 252, 0.8) 100%);
            backdrop-filter: blur(2px) saturate(0.8);
            border-radius: 12px;
            z-index: 1;
            pointer-events: none;
        }



        .topic-locked .start-quiz-btn {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
            color: #64748b !important;
            cursor: not-allowed;
            pointer-events: none;
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
        }

        @keyframes premiumUrgent {
            0%, 100% { 
                border-top-color: #f44336;
                background: linear-gradient(135deg, rgba(244, 67, 54, 0.08) 0%, rgba(255, 87, 34, 0.05) 100%);
            }
            50% { 
                border-top-color: #ff5722;
                background: linear-gradient(135deg, rgba(255, 87, 34, 0.12) 0%, rgba(244, 67, 54, 0.08) 100%);
            }
        }

        /* Premium Countdown Urgency */
        .countdown-urgent {
            background: rgba(255, 243, 224, 0.95) !important;
            border-color: rgba(255, 152, 0, 0.3) !important;
            color: #ea580c !important;
            box-shadow: 0 0 0 1px rgba(255, 152, 0, 0.1), 0 4px 12px rgba(255, 152, 0, 0.15) !important;
        }

        .countdown-urgent .countdown-display {
            animation: premiumPulse 2s ease-in-out infinite;
        }

        @keyframes premiumPulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.8;
                transform: scale(1.02);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .assignment-status-banner {
                margin: -1.5rem -1.5rem 0 -1.5rem;
            }
            
            .assignment-status-bar {
                margin: -1.5rem -1.5rem 1rem -1.5rem;
                padding: 0.5rem 1.5rem;
                width: calc(100% + 3rem);
                border-radius: 18px 18px 0 0;
            }
            
            .assignment-info {
                padding: 0.75rem 1.5rem;
                font-size: 0.8rem;
            }
            
            .assignment-countdown,
            .overdue-text,
            .completed-text {
                margin-right: 1.5rem;
                padding: 0.4rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .assignment-countdown-minimal {
                margin-right: 0;
                padding: 0.3rem 0.6rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .assignment-status-bar {
                margin: -1.5rem -1.5rem 1rem -1.5rem;
                padding: 0.5rem 1.5rem;
                width: calc(100% + 3rem);
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .assignment-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .assignment-countdown,
            .overdue-text,
            .completed-text {
                margin-right: 0;
                margin-top: 0.5rem;
            }
            
            .assignment-countdown-minimal {
                margin-right: 0;
                margin-top: 0.5rem;
            }
        }

        /* Minimal Assignment Status Bar - Only at top */
        .assignment-status-bar {
            margin: -2rem -2.5rem 1.5rem -2.5rem;
            padding: 1.1rem 2.5rem 0.7rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
            position: relative;
            width: calc(100% + 5rem);
            left: 0;
            box-sizing: border-box;
            min-height: 56px;
        }

        .assignment-status-bar.assignment-active {
            background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%);
            color: #e65100;
            border-bottom: 1px solid #ffcc02;
        }

        .assignment-status-bar.assignment-overdue {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border-bottom: 1px solid #f44336;
            animation: subtleUrgent 3s infinite;
        }

        .assignment-status-bar.assignment-completed {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #2e7d32;
            border-bottom: 1px solid #4caf50;
        }

        .assignment-info-minimal {
            display: flex;
            align-items: center;
            gap: 1rem;
            line-height: 1.2;
        }

        .assignment-info-minimal i {
            font-size: 1rem;
            opacity: 0.8;
            margin-right: 0.25rem;
        }

        .assignment-info-minimal span {
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .assignment-countdown-minimal {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.7);
            padding: 0.2rem 0.7rem;
            border-radius: 18px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 600;
            font-size: 0.75rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .countdown-display {
            letter-spacing: 0.3px;
            font-variant-numeric: tabular-nums;
        }

        /* Assignment status bar content alignment fixes */
        .assignment-status-bar .assignment-info-minimal span {
            white-space: nowrap;
        }

        .assignment-status-bar .assignment-countdown-minimal,
        .assignment-status-bar .overdue-text,
        .assignment-status-bar .completed-text {
            white-space: nowrap;
        }

        /* Minimal Locked Topic Styling */
        .topic-locked {
            opacity: 0.5;
            position: relative;
        }

        .topic-locked::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(241, 243, 244, 0.7);
            backdrop-filter: blur(1px);
            pointer-events: none;
            z-index: 2;
            border-radius: 12px;
        }

        .topic-locked .start-quiz-btn {
            background: #f1f3f4 !important;
            color: #9ca3af !important;
            cursor: not-allowed;
            pointer-events: none;
            border: 2px solid #e5e7eb !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 180px !important;
            min-height: 48px !important;
        }

        .topic-locked .start-quiz-btn .lock-icon {
            color: #9ca3af !important;
            font-size: 1.2em !important;
            margin: 0 !important;
        }

        .topic-locked .start-quiz-btn span {
            display: none !important;
        }

        .topic-locked .start-quiz-btn .fa-arrow-right {
            display: none !important;
        }

        @keyframes subtleUrgent {
            0%, 100% { 
                border-bottom-color: #f44336;
            }
            50% { 
                border-bottom-color: #ff5722;
            }
        }

        /* Countdown urgency styling */
        .countdown-urgent {
            background: rgba(255, 152, 0, 0.2) !important;
            border-color: rgba(255, 152, 0, 0.3) !important;
            color: #e65100 !important;
        }

        .countdown-urgent .countdown-display {
            animation: subtlePulse 2s infinite;
        }

        @keyframes subtlePulse {
            0%, 100% { 
                opacity: 1;
            }
            50% { 
                opacity: 0.7;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('courses.index') }}" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Courses
                </a>
                
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user()->profile_picture_url)
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <!-- Random Quiz Modal -->
    <div class="modal fade" id="randomQuizModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-random"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Random Quiz Mode</h5>
                            <p class="modal-subtitle mb-0">Customize your quiz settings</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('quiz.random.show') }}" method="GET" id="randomQuizForm">
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-book-open text-warning"></i>
                                <label class="form-label mb-2">Select Courses</label>
                            </div>
                            <div class="courses-container">
                                @php
                                    $user = auth()->user();
                                    if ($user->role === 'admin') {
                                        $availableCourses = \App\Models\Course::orderBy('name')->get();
                                    } else {
                                        // Get courses the user is enrolled in through the pivot table
                                        $availableCourses = \App\Models\Course::whereHas('users', function($query) use ($user) {
                                            $query->where('user_id', $user->id);
                                        })->orderBy('name')->get();
                                        
                                        // If no enrolled courses found, get all courses (fallback)
                                        if ($availableCourses->isEmpty()) {
                                            $availableCourses = \App\Models\Course::orderBy('name')->get();
                                        }
                                    }
                                @endphp
                                @if($availableCourses->count() > 0)
                                    @foreach($availableCourses as $availableCourse)
                                        <div class="form-check custom-checkbox">
                                            <input class="form-check-input" type="checkbox" 
                                                name="selected_courses[]" 
                                                value="{{ $availableCourse->id }}" 
                                                id="course{{ $availableCourse->id }}"
                                                checked>
                                            <label class="form-check-label" for="course{{ $availableCourse->id }}">
                                                {{ $availableCourse->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-exclamation-circle mb-2"></i>
                                        <p class="mb-0">No courses available. Please contact your administrator.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-list-ol text-warning"></i>
                                <label for="questionLimit" class="form-label mb-2">Number of Questions</label>
                            </div>
                            <select class="form-select custom-select" id="questionLimit" name="question_limit" required>
                                <option value="">Select number of questions</option>
                                <option value="5">5 Questions</option>
                                <option value="10">10 Questions</option>
                                <option value="15">15 Questions</option>
                                <option value="20">20 Questions</option>
                                <option value="30">30 Questions</option>
                                <option value="50">50 Questions</option>
                                <option value="100">100 Questions</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-clock text-warning"></i>
                                <label for="timeLimit" class="form-label mb-2">Time Limit (minutes)</label>
                            </div>
                            <select class="form-select custom-select" id="timeLimit" name="duration_minutes" required>
                                <option value="">Select time limit</option>
                                <option value="0.167">10 Seconds (Testing)</option>
                                <option value="5">5 Minutes</option>
                                <option value="10">10 Minutes</option>
                                <option value="15">15 Minutes</option>
                                <option value="20">20 Minutes</option>
                                <option value="30">30 Minutes</option>
                                <option value="45">45 Minutes</option>
                                <option value="60">60 Minutes</option>
                                <option value="90">90 Minutes</option>
                                <option value="120">2 Hours</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" form="randomQuizForm" class="btn btn-warning">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Source Selection Modal -->
    <div class="modal fade" id="sourceSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Select Question Sources</h5>
                            <p class="mb-0 text-muted small">Choose which sources to include in your quiz</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <form action="" method="GET" id="sourceSelectionForm">
                        <input type="hidden" name="course_id" id="source_course_id">
                        <input type="hidden" name="topic_id" id="source_topic_id">
                        
                        <div class="sources-container mb-3">
                            <!-- Source cards will be dynamically inserted here -->
                        </div>
                        
                        <!-- Simplified Mode Selection -->
                        <div class="d-flex align-items-center gap-3 mb-3 px-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="quiz_mode" id="quizMode" value="quiz" checked>
                                <label class="form-check-label" for="quizMode">Quiz Mode</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="quiz_mode" id="tutorMode" value="tutor">
                                <label class="form-check-label" for="tutorMode">Tutor Mode</label>
                            </div>
                        </div>
                        
                        <div class="source-info">
                            <div class="source-info-title">
                                <i class="fas fa-info-circle"></i>
                                <span>Tip</span>
                            </div>
                            <p class="source-info-text">
                                Complete all sources to master this topic. Progress is tracked for each source.
                            </p>
                        </div>
                        

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" form="sourceSelectionForm" class="btn btn-primary" id="startSourceQuizBtn">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Header -->
    <header class="course-header">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="course-progress">
            <div class="progress-circle">
                @php
                    $masteredCount = 0;
                    foreach($topics as $topic) {
                        $userProgress = $topic->userProgress->first();
                        if ($userProgress && $userProgress->percentage_grade >= 80) {
                            $masteredCount++;
                        }
                    }
                @endphp
                {{ $masteredCount }}/{{ $topics->count() }}
            </div>
            <div class="progress-text">
                <div class="progress-text-title">Course Progress</div>
                <div class="progress-text-subtitle">Topics Mastered</div>
            </div>
        </div>
    </header>


    
    <!-- Case Filters -->






    <div class="case-filters" data-course-type="{{ $course->type }}" data-topics="{{ $topics->toJson() }}">
        @if($course->type === 'custom')
            @php
                $categories = $topics->pluck('case_type')->unique()->filter()->values();
            @endphp
            
            {{-- Always show "All" pill for navigation --}}
            <div class="case-filter active" data-filter="all">
                <i class="fas fa-th-large"></i>
                All
                <span class="count">{{ $topics->count() }}</span>
            </div>
            
            @foreach($categories as $category)
                @php
                    $categoryCount = $topics->where('case_type', $category)->count();
                    // Define icons for custom categories
                    $categoryIcons = [
                        'homework' => 'fas fa-pencil-alt',
                        'lab' => 'fas fa-microscope',
                        'reading' => 'fas fa-book-open',
                        'project' => 'fas fa-project-diagram',
                        'assignment' => 'fas fa-tasks',
                        'exercise' => 'fas fa-dumbbell',
                        'tutorial' => 'fas fa-play-circle',
                        'lecture' => 'fas fa-chalkboard-teacher',
                        'exam' => 'fas fa-clipboard-check',
                        'test' => 'fas fa-file-alt',
                        'review' => 'fas fa-eye',
                        'practice' => 'fas fa-repeat',
                        'study' => 'fas fa-graduation-cap',
                        'notes' => 'fas fa-sticky-note',
                        'video' => 'fas fa-video',
                        'audio' => 'fas fa-volume-up',
                        'discussion' => 'fas fa-comments',
                        'forum' => 'fas fa-comment-dots',
                        'workshop' => 'fas fa-tools',
                        'seminar' => 'fas fa-users'
                    ];
                    $icon = $categoryIcons[strtolower($category)] ?? 'fas fa-tag';
                @endphp
                
                <div class="case-filter{{ $categories->count() == 0 ? ' active' : '' }}" data-filter="{{ strtolower($category) }}">
                    <i class="{{ $icon }}"></i>
                    {{ ucfirst($category) }}
                    <span class="count">{{ $categoryCount }}</span>
                </div>
            @endforeach
        @else
            <!-- ========== MEDICAL COURSE CATEGORIZATION ========== -->
            @php
                $casesCount = $topics->where('case_type', 'cases')->count();
                $practicalCount = $topics->where('case_type', 'practical')->count();
                $quizCount = $topics->where('case_type', 'quiz')->count();
            @endphp
            
            {{-- Always show "All" pill for navigation --}}
            <div class="case-filter active" data-filter="all">
                <i class="fas fa-th-large"></i>
                All
                <span class="count">{{ $topics->count() }}</span>
            </div>
            
            @if($casesCount > 0)
                <div class="case-filter" data-filter="cases">
                    <i class="fas fa-file-alt"></i>
                    Cases
                    <span class="count">{{ $casesCount }}</span>
                </div>
            @endif
            
            @if($practicalCount > 0)
                <div class="case-filter" data-filter="practical">
                    <i class="fas fa-flask"></i>
                    Practical
                    <span class="count">{{ $practicalCount }}</span>
                </div>
            @endif
            
            @if($quizCount > 0)
                <div class="case-filter" data-filter="quiz">
                    <i class="fas fa-question-circle"></i>
                    Quiz
                    <span class="count">{{ $quizCount }}</span>
                </div>
            @endif
        @endif
        

        
        <!-- Fixed position pills that are always present -->
        <a href="#" class="random-link" data-bs-toggle="modal" data-bs-target="#randomQuizModal" style="order: 98;">
            <i class="fas fa-random"></i>
            Random
        </a>
        <a href="{{ route('review.index') }}" class="mistakes-link" style="order: 99;">
            <i class="fas fa-times-circle"></i>
            Review Mistakes
        </a>
        
        {{-- Assignment Icon in Red Circle - Only show if there are assignments --}}
        @if(isset($assignments) && (is_array($assignments) ? count($assignments) : $assignments->count()) > 0)
            <div class="assignment-icon-circle" data-filter="assignments" style="order: 100;">
                <div class="assignment-circle">
                    <i class="fas fa-tasks"></i>
                    <span class="assignment-glow-count">{{ is_array($assignments) ? count($assignments) : $assignments->count() }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Topics List -->
    <div class="topics-container">
        {{-- 🚀 LETHAL ASSIGNMENT OPTIMIZATION: Pre-calculated data --}}
        @if(isset($assignments) && count($assignments) > 0)
            @php
                // 🚀 SURGICAL OPTIMIZATION: Pre-calculate ALL data ONCE
                $assignmentData = $assignments->mapWithKeys(function($assignment) use ($topics) {
                    $topic = $topics->firstWhere('id', $assignment->topic_id);
                    if (!$topic) return [];
                    
                    return [$assignment->id => [
                        'topic' => $topic,
                        'progress' => $assignment->user_progress['percentage'] ?? 0,
                        'status' => $assignment->display_status ?? $assignment->getCurrentStatusAttribute(),
                        'quizUrl' => secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id, 'assignment_id' => $assignment->id], false)),
                        'isLocked' => in_array($assignment->status, ['locked', 'expired', 'completed']) || ($assignment->status === 'overdue'),
                        'lockReason' => $assignment->getLockReason() ?? 'Assignment is locked'
                    ]];
                });
            @endphp
            
            @foreach($assignments as $assignment)
                @php 
                    $data = $assignmentData[$assignment->id] ?? null;
                    if (!$data) continue;
                    
                    // SIMPLE: Just check if user has attempted this assignment
                    $isLocked = $assignment->isLockedForUser(auth()->id());
                @endphp
                
                <div class="topic-card assignment-{{ $assignment->isCompletedByUser(auth()->id()) ? 'completed' : ($assignment->isLockedForUser(auth()->id()) || $data['status'] === 'overdue' ? 'overdue' : $data['status']) }}" 
                     data-case-type="assignment" 
                     data-topic-id="{{ $data['topic']->id }}"
                     data-assignment-id="{{ $assignment->id }}"
                     data-has-assignment="true" 
                     data-assignment-deadline="{{ $assignment->deadline }}" 
                     data-assignment-status="{{ $data['status'] }}">
                    
                    {{-- Assignment Status Bar --}}
                    <div class="assignment-status-bar assignment-{{ $assignment->isCompletedByUser(auth()->id()) ? 'completed' : ($assignment->isLockedForUser(auth()->id()) || $data['status'] === 'overdue' ? 'overdue' : $data['status']) }}">
                        <div class="assignment-info-minimal">
                            @if($data['status'] === 'active' && !$assignment->isLockedForUser(auth()->id()))
                                <span>Assignment Due:</span>
                                <div class="assignment-countdown-minimal" data-deadline="{{ $assignment->deadline }}">
                                    <span class="countdown-display">{{ \Carbon\Carbon::parse($assignment->deadline)->diffForHumans() }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="topic-info">
                        <div class="topic-main">
                            <h3 class="topic-name">
                                <i class="fas fa-tasks"></i>
                                {{ $assignment->title }}
                            </h3>
                        </div>
                    </div>

                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $data['progress'] }}%"></div>
                        </div>
                        <div class="progress-label">
                            <span>Assignment Progress</span>
                            <span>{{ number_format($data['progress'], 1) }}%</span>
                        </div>
                    </div>

                    <div class="topic-actions">
                        <div class="stat-item">
                            @if($assignment->isCompletedByUser(auth()->id()))
                                <i class="fas fa-check-circle text-success"></i>Assignment Completed
                            @elseif($assignment->isLockedForUser(auth()->id()) || $data['status'] === 'overdue')
                                <i class="fas fa-times text-danger"></i>Assignment Overdue
                            @else
                                <i class="fas fa-play text-primary"></i>Start Assignment
                            @endif
                        </div>
                        
                        @if($isLocked || $data['status'] === 'overdue')
                            {{-- RED LOCK for locked/overdue assignments --}}
                            <div class="assignment-locked-icon" title="Assignment not available">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
                            <a href="javascript:void(0)" class="start-quiz-btn assignment-start-btn" data-topic-id="{{ $data['topic']->id }}" data-course-id="{{ $data['topic']->course_id }}" data-quiz-url="{{ $data['quizUrl'] }}">
                                <span>Start Assignment</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                    {{-- Assignment cards don't need case type badges --}}
                </div>
            @endforeach
        @endif

        {{-- Regular Topic Cards Section --}}
        @if($topics->count() > 0)
            @foreach($topics as $topic)
                @php
                    // Check if topic is locked (you can customize this logic based on your requirements)
                    $isLocked = isset($topic->is_locked) ? $topic->is_locked : false;
                    // Alternative: Check if topic has a locked status
                    // $isLocked = $topic->status === 'locked';
                @endphp
                
                <div class="topic-card" 
                     data-case-type="{{ $topic->case_type }}" 
                     data-topic-id="{{ $topic->id }}"
                     data-has-assignment="false">
                    
                    {{-- Regular topics don't have assignment status bars --}}
                    
                    <div class="topic-info">
                        <div class="topic-main">
                            <h3 class="topic-name">
                                <i class="fas fa-book-open"></i>
                                {{ $topic->name }}
                            </h3>
                            
                            @if($topic->last_attempt_date)
                                <div class="last-attempt">
                                    <i class="far fa-clock"></i>
                                    Last attempt: {{ \Carbon\Carbon::parse($topic->last_attempt_date)->diffForHumans() }}
                                </div>
                            @endif
                        </div>
                        
                        @php
                            $userProgress = $topic->userProgress->first();
                            $percentageGrade = $userProgress ? $userProgress->percentage_grade : null;
                        @endphp
                        @if($percentageGrade !== null)
                            <div class="grade-badge 
                                {{ $percentageGrade >= 100 ? 'grade-mastered' : 
                                   ($percentageGrade >= 66 ? 'grade-good' : 
                                   ($percentageGrade >= 33 ? 'grade-in-progress' : 'grade-bad')) }}">
                                <i class="fas {{ $percentageGrade >= 100 ? 'fa-star' : 
                                               ($percentageGrade >= 66 ? 'fa-check' : 
                                               ($percentageGrade >= 33 ? 'fa-sync-alt' : 'fa-times')) }}"></i>
                                {{ $percentageGrade >= 100 ? 'Mastered' : 
                                   ($percentageGrade >= 66 ? 'Good' : 
                                   ($percentageGrade >= 33 ? 'In Progress' : 'Just Started')) }}
                            </div>
                        @else
                            <div class="grade-badge grade-none">
                                <i class="fas fa-hourglass-start"></i>
                                Not Started
                            </div>
                        @endif
                    </div>

                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, $percentageGrade ?? 0) }}%"></div>
                        </div>
                        <div class="progress-label">
                            <span>Progress</span>
                            <span>{{ $percentageGrade ? number_format(min(100, $percentageGrade), 1) : 0 }}%</span>
                        </div>
                    </div>

                    <div class="topic-actions">
                        <div class="stat-item">
                            @if($percentageGrade >= 100)
                                <i class="fas fa-check-circle text-success"></i>
                                Mastered
                            @elseif($percentageGrade >= 66)
                                <i class="fas fa-check text-success"></i>
                                Good
                            @elseif($percentageGrade >= 33)
                                <i class="fas fa-sync-alt text-info"></i>
                                Making progress
                            @elseif($percentageGrade !== null)
                                <i class="fas fa-rocket text-primary"></i>
                                Just getting started
                            @else
                                <i class="fas fa-flag text-primary"></i>
                                Ready to start
                            @endif
                        </div>
                        
                        @if($isLocked)
                            {{-- Show white button with red lock for locked topics --}}
                            <div class="topic-locked-btn" title="Topic is locked">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
                            @php
                                $quizUrl = secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id], false));
                            @endphp
                            <a href="javascript:void(0)" class="start-quiz-btn" data-topic-id="{{ $topic->id }}" data-course-id="{{ $topic->course_id }}" data-quiz-url="{{ route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id]) }}">
                                @if($percentageGrade !== null)
                                    <span>Retake Quiz</span>
                                @else
                                    <span>Start Quiz</span>
                                @endif
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                    <div class="case-type-badge {{ $topic->case_type }}">{{ $topic->case_type }}</div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No Topics Available</h3>
                <p>Topics for this course will be added soon.</p>
            </div>
        @endif
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer crossorigin="anonymous"></script>
    
    <!-- Topic-specific JavaScript files -->
    <!-- Removed asset URL -->
    

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize external TopicFilters if available
            if (typeof TopicFilters !== 'undefined') {
                TopicFilters.init();
            }
            
            // 🚀 CACHED DOM ELEMENTS - DECLARE ONCE AT TOP
            const topicsContainer = document.querySelector('.topics-container');
            const caseFilters = document.querySelectorAll('.case-filter');
            const topicCards = document.querySelectorAll('.topic-card');
            const sourceModal = document.getElementById('sourceSelectionModal');
            const sourcesContainer = document.querySelector('.sources-container');
            const quizButtons = document.querySelectorAll('.start-quiz-btn');
            const sourceTopicId = document.getElementById('source_topic_id');
            const sourceCourseId = document.getElementById('source_course_id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const sourceSelectionForm = document.getElementById('sourceSelectionForm');
            const randomQuizForm = document.getElementById('randomQuizForm');
            const randomQuizModal = document.getElementById('randomQuizModal');
            const assignmentIconCircle = document.querySelector('.assignment-icon-circle');
            const assignmentCircle = document.querySelector('.assignment-circle');
            const questionLimit = document.getElementById('questionLimit');
            const timeLimit = document.getElementById('timeLimit');
            const courseCheckboxes = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]');
            const modals = document.querySelectorAll('.modal');
            
            // 🚨 CRITICAL FIX: Assignment icon click handler with null safety
            if (assignmentIconCircle && assignmentCircle) {
                assignmentIconCircle.addEventListener('click', function() {
                    // Remove active class from all filters
                    caseFilters.forEach(f => f.classList.remove('active'));
                    
                    // Add active class to assignment circle
                    const circleElement = this.querySelector('.assignment-circle');
                    if (circleElement) {
                        circleElement.classList.add('active');
                    }
                    
                    // Get the topics container
                    if (topicsContainer) {
                        topicsContainer.classList.add('assignments-filter-active');
                    }
                    
                    // Filter topic cards to show only assignments
                    topicCards.forEach(card => {
                        const hasAssignment = card.dataset.hasAssignment === 'true';
                        
                        if (hasAssignment) {
                            card.style.display = 'block';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Regular case filter functionality
            caseFilters.forEach(filter => {
                filter.addEventListener('click', function() {
                    // Remove active class from all filters
                    caseFilters.forEach(f => f.classList.remove('active'));
                    
                    // Remove active class from assignment icon
                    if (assignmentCircle) {
                        assignmentCircle.classList.remove('active');
                    }
                    
                    // Add active class to clicked filter
                    this.classList.add('active');
                    
                    // Get the filter type
                    const filterType = this.dataset.filter || this.textContent.trim().toLowerCase();
                    
                    // Add/remove assignments filter class
                    if (filterType === 'assignments') {
                        topicsContainer.classList.add('assignments-filter-active');
                    } else {
                        topicsContainer.classList.remove('assignments-filter-active');
                    }
                    
                    // Filter topic cards
                    topicCards.forEach(card => {
                        const caseType = card.dataset.caseType;
                        const hasAssignment = card.dataset.hasAssignment === 'true';
                        
                        let shouldShow = false;
                        
                        if (filterType === 'assignments') {
                            // Only show assignment cards
                            shouldShow = hasAssignment;
                        } else if (filterType === 'all') {
                            // Show only regular topics, NOT assignment cards
                            shouldShow = !hasAssignment;
                        } else {
                            // Dynamic filtering based on case type (exclude assignment cards)
                            shouldShow = !hasAssignment && caseType && caseType.toLowerCase() === filterType.toLowerCase();
                        }
                        
                        if (shouldShow) {
                            card.style.display = 'block';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // 🚀 LETHAL COUNTDOWN OPTIMIZATION: Intersection Observer + Smart Intervals
            const countdownObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const countdown = entry.target;
                    if (entry.isIntersecting) {
                        startCountdown(countdown);
                    } else {
                        pauseCountdown(countdown);
                    }
                });
            }, { threshold: 0.1 });

            const activeCountdowns = new Map();
            
            function startCountdown(countdown) {
                if (activeCountdowns.has(countdown)) return;
                
                const updateInterval = setInterval(() => {
                    updateSingleCountdown(countdown);
                }, getUpdateFrequency(countdown));
                
                activeCountdowns.set(countdown, updateInterval);
                updateSingleCountdown(countdown); // Initial update
            }
            
            function pauseCountdown(countdown) {
                const interval = activeCountdowns.get(countdown);
                if (interval) {
                    clearInterval(interval);
                    activeCountdowns.delete(countdown);
                }
            }
            
            function getUpdateFrequency(countdown) {
                const deadline = new Date(countdown.dataset.deadline);
                const timeLeft = deadline - new Date();
                
                if (timeLeft > 3600000) return 60000; // >1 hour: update every minute
                if (timeLeft > 300000) return 10000;  // >5 min: update every 10 seconds
                return 1000; // <5 min: update every second
            }
            
            function updateSingleCountdown(countdown) {
                const deadline = new Date(countdown.dataset.deadline);
                const now = new Date();
                const timeLeft = deadline - now;
                
                if (timeLeft <= 0) {
                    handleOverdueAssignment(countdown);
                    pauseCountdown(countdown);
                    return;
                }
                
                const days = Math.floor(timeLeft / 86400000);
                const hours = Math.floor((timeLeft % 86400000) / 3600000);
                const minutes = Math.floor((timeLeft % 3600000) / 60000);
                const seconds = Math.floor((timeLeft % 60000) / 1000);
                
                let countdownText = '';
                if (days > 0) {
                    countdownText = `${days}d ${hours}h ${minutes}m`;
                } else if (hours > 0) {
                    countdownText = `${hours}h ${minutes}m ${seconds}s`;
                } else if (minutes > 0) {
                    countdownText = `${minutes}m ${seconds}s`;
                } else {
                    countdownText = `${seconds}s`;
                    countdown.classList.add('countdown-urgent');
                }
                
                const display = countdown.querySelector('.countdown-display');
                if (display) display.textContent = countdownText;
                
                if (timeLeft < 3600000) {
                    countdown.classList.add('countdown-urgent');
                    const banner = countdown.closest('.assignment-status-banner');
                    if (banner) banner.style.animation = 'urgentBanner 1s infinite';
                }
            }
            
            function handleOverdueAssignment(countdown) {
                const topicCard = countdown.closest('.topic-card');
                if (!topicCard) return;
                
                topicCard.classList.add('topic-locked');
                
                const banner = topicCard.querySelector('.assignment-status-banner');
                if (banner) {
                    banner.className = 'assignment-status-banner assignment-overdue';
                    banner.innerHTML = `<div class="assignment-info overdue"><i class="fas fa-exclamation-triangle"></i><span class="assignment-label">ASSIGNMENT OVERDUE</span><span class="overdue-text">Topic Locked</span></div>`;
                }
                
                const startBtn = topicCard.querySelector('.start-quiz-btn');
                if (startBtn) {
                    Object.assign(startBtn.style, {
                        pointerEvents: 'none',
                        background: '#f1f3f4',
                        color: '#9ca3af',
                        border: '2px solid #e5e7eb',
                        opacity: '0.6',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    });
                    startBtn.innerHTML = '<i class="fas fa-lock lock-icon" style="color: #9ca3af; font-size: 1.2em; margin: 0;"></i>';
                }
            }
            
            // Initialize countdown observers
            document.querySelectorAll('.assignment-countdown-minimal').forEach(countdown => {
                countdownObserver.observe(countdown);
            });

            // Handle source progress updates from quiz completion
            document.addEventListener('sourceProgressUpdated', function(e) {
                const { topicId, progress, availableSources, allSourcesMastered, assignmentId } = e.detail;
                
                // Update source progress bars in the modal
                if (progress) {
                    updateSourcesContainer({
                        progress: progress,
                        availableSources: availableSources
                    });
                }
                
                // Update topic mastery status - FIXED: Only update the correct card type
                let targetCard = null;
                
                if (assignmentId) {
                    // This is an assignment completion - find the specific assignment card
                    targetCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);

                } else {
                    // This is regular quiz completion - find the regular topic card (not assignment cards)
                    targetCard = document.querySelector(`[data-topic-id="${topicId}"][data-has-assignment="false"]`);

                }
                
                if (targetCard) {
                    const gradeBadge = targetCard.querySelector('.grade-badge');
                    if (gradeBadge) {
                        gradeBadge.className = `grade-badge ${allSourcesMastered ? 'grade-mastered' : 'grade-good'}`;
                        gradeBadge.innerHTML = allSourcesMastered ? 
                            '<i class="fas fa-star"></i> Mastered' : 
                            '<i class="fas fa-check"></i> Good';
                    }
                    
                    // Update assignment progress bar if this is an assignment completion
                    if (assignmentId) {
                        console.log('🎯 Assignment completion detected, updating progress for assignment:', assignmentId);
                        updateAssignmentProgress(assignmentId, targetCard);
                    }
                }
            });

            // 🚀 OPTIMIZED: Lazy modal initialization
            let sourceSelectionModal;
            function getModal() {
                if (!sourceSelectionModal && sourceModal) {
                    sourceSelectionModal = new bootstrap.Modal(sourceModal);
                }
                return sourceSelectionModal;
            }
            
            // Handle mode change
            document.querySelectorAll('input[name="quiz_mode"]').forEach(radio => {
                radio.addEventListener('change', async function() {
                    const topicId = sourceTopicId.value;
                    if (!topicId) return;
                    
                    sourcesContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                    try {
                        const response = await fetch(`/topics/${topicId}/source-progress?mode=${this.value}`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin'
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        updateSourcesContainer(data);
                    } catch (error) {
                        sourcesContainer.innerHTML = '<div class="alert alert-danger">Error loading sources. Please try again.</div>';
                    }
                });
            });

            // Helper function to update assignment progress bar
            async function updateAssignmentProgress(assignmentId, targetCard) {
                console.log('🔄 Updating assignment progress for:', assignmentId);
                
                try {
                    // Fetch the latest assignment progress from the server
                    const response = await fetch(`/api/assignments/${assignmentId}/progress`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    
                    console.log('📡 API Response status:', response.status);
                    
                    if (response.ok) {
                        const data = await response.json();
                        console.log('📊 Progress data received:', data);
                        
                        // Update the progress bar
                        const progressBar = targetCard.querySelector('.progress .progress-bar');
                        const progressLabel = targetCard.querySelector('.progress-label span:last-child');
                        
                        console.log('🎯 Found elements:', {
                            progressBar: !!progressBar,
                            progressLabel: !!progressLabel,
                            targetCard: !!targetCard
                        });
                        
                        if (progressBar && data.progress !== undefined) {
                            progressBar.style.width = `${data.progress}%`;
                            console.log('📊 Updated progress bar width to:', `${data.progress}%`);
                        }
                        
                        if (progressLabel && data.progress !== undefined) {
                            progressLabel.textContent = `${data.progress.toFixed(1)}%`;
                            console.log('📊 Updated progress label to:', `${data.progress.toFixed(1)}%`);
                        }
                        
                        console.log('✅ Assignment progress updated:', {
                            assignmentId: assignmentId,
                            progress: data.progress
                        });
                    } else {
                        console.error('❌ API response not ok:', response.status, response.statusText);
                    }
                } catch (error) {
                    console.error('❌ Failed to update assignment progress:', error);
                }
            }

            // Helper function to update sources container
            function updateSourcesContainer(data) {
                        sourcesContainer.innerHTML = '';
                        
                        if (!data.availableSources || data.availableSources.length === 0) {
                            sourcesContainer.innerHTML = '<div class="alert alert-info">No questions available for this topic.</div>';
                            return;
                        }
                        
                        data.availableSources.forEach(source => {
                            const progress = data.progress[source] || { completed: 0, total: 0, percentage: 0 };
                            const card = document.createElement('div');
                            card.className = 'source-card';
                            card.setAttribute('data-source', source);

                            // Add selected class if there are questions
                    if (progress.total_questions_available > 0) {
                                card.classList.add('selected');
                            }

                    // Determine status class based on percentage
                            const statusClass = progress.percentage >= 90 ? 'completed' : 
                                      progress.percentage >= 50 ? 'in-progress' :
                                      progress.percentage > 0 ? 'failed' :
                                              'not-started';

                            card.innerHTML = `
                                <div class="source-status ${statusClass}"></div>
                                <div class="source-content">
                                    <div class="source-info-wrapper">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <input type="checkbox" name="sources[]" value="${source}" 
                                               class="form-check-input" ${progress.total_questions_available > 0 ? 'checked' : ''}>
                                                <span class="ms-2 source-name">${source}</span>
                                            </div>
                                    <div class="source-status-text ${statusClass}">
                                                ${progress.percentage >= 90 ? 
                                                    '<i class="fas fa-check-circle me-1"></i>Completed' : 
                                                    progress.percentage >= 50 ? 
                                                    '<i class="fas fa-clock me-1"></i>In Progress' :
                                                    progress.percentage > 0 ?
                                                    '<i class="fas fa-times-circle me-1"></i>Failed' :
                                                    '<i class="fas fa-circle me-1"></i>Not Started'}
                                            </div>
                                        </div>
                                        <div class="source-progress-bar">
                                    <div class="source-progress-value ${statusClass}" 
                                                 style="width: ${progress.percentage}%;">
                                            </div>
                                        </div>
                                        <div class="source-stats">
                                            <span class="questions-count">
                                        <i class="fas fa-book-open me-1"></i>${progress.completed}/${progress.total_questions_available} Questions
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;

                            sourcesContainer.appendChild(card);

                            // Add click handler for the entire card
                            card.addEventListener('click', function(e) {
                                if (e.target.type === 'checkbox') return;
                                const checkbox = this.querySelector('input[type="checkbox"]');
                                checkbox.checked = !checkbox.checked;
                                this.classList.toggle('selected', checkbox.checked);
                                
                                // Ensure at least one source is selected
                                const anyChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                                if (!anyChecked) {
                                    checkbox.checked = true;
                                    this.classList.add('selected');
                                }
                            });
                        });
            }
            


            // ROBUST: Handle quiz button clicks with multiple prevention methods

            
            // 🚀 ULTRA-FAST: Optimized quiz button handler - single click, instant response
            document.addEventListener('click', function(e) {
                // Use closest() to handle clicks on child elements (icons, spans)
                const button = e.target.closest('.start-quiz-btn');
                if (!button) return;
                
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // Prevent double-clicks
                if (button.dataset.processing === 'true') return;
                button.dataset.processing = 'true';
                
                const topicId = button.dataset.topicId;
                const courseId = button.dataset.courseId;
                const originalHref = button.dataset.quizUrl;
                
                // Instant visual feedback
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                button.style.pointerEvents = 'none';
                
                // Check if assignments filter is active - direct redirect
                const isAssignmentsFilterActive = topicsContainer?.classList.contains('assignments-filter-active');
                
                if (isAssignmentsFilterActive) {
                    window.location.href = originalHref;
                    return;
                }
                
                // For regular topics, check if modal is needed
                if (!topicId || !courseId) {
                    window.location.href = originalHref;
                    return;
                }
                
                // Quick source check - if only one source, skip modal
                fetch(`/topics/${topicId}/source-progress?mode=quiz`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const availableSources = data.availableSources || [];
                    
                    // If only one source or no sources, redirect directly
                    if (availableSources.length <= 1) {
                        window.location.href = originalHref;
                        return;
                    }
                    
                    // Multiple sources - show modal
                    const modal = getModal();
                    if (!modal) {
                        window.location.href = originalHref;
                        return;
                    }
                    
                    // Reset button
                    button.innerHTML = originalContent;
                    button.style.pointerEvents = '';
                    button.dataset.processing = 'false';
                    
                    // Set form values
                    sourceTopicId.value = topicId;
                    sourceCourseId.value = courseId;
                    sourceSelectionForm.action = originalHref;
                    
                    // Update sources immediately
                    if (typeof updateSourcesContainer === 'function') {
                        updateSourcesContainer(data);
                    }
                    
                    // Show modal
                    sourceSelectionModal.show();
                })
                .catch(error => {
                    // On error, redirect directly
                    window.location.href = originalHref;
                });
            });

            // Form submission
            sourceSelectionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const checkedSources = this.querySelectorAll('input[name="sources[]"]:checked');
                if (checkedSources.length === 0) {
                    alert('Please select at least one source to start the quiz.');
                    return;
                }

                const selectedMode = this.querySelector('input[name="quiz_mode"]:checked').value;
                const courseId = this.querySelector('#source_course_id').value;
                const topicId = this.querySelector('#source_topic_id').value;
                
                const selectedSources = Array.from(checkedSources).map(cb => cb.value);
                const sourceParams = selectedSources.map(s => `sources[]=${s}`).join('&');
                
                const baseUrl = selectedMode === 'tutor' 
                    ? `/quiz/${courseId}/${topicId}/tutor`
                    : `/quiz/${courseId}/${topicId}`;
                    
                window.location.href = `${baseUrl}?${sourceParams}`;
            });

            // Random Quiz Modal Validation
            if (randomQuizForm) {
                randomQuizForm.addEventListener('submit', function(e) {
                    // Check if at least one course is selected
                    const selectedCourses = this.querySelectorAll('input[name="selected_courses[]"]:checked');
                    
                    if (selectedCourses.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one course to start the random quiz.');
                        return false;
                    }
                    
                    // Check if question count is selected
                    if (!questionLimit.value) {
                        e.preventDefault();
                        alert('Please select the number of questions for your quiz.');
                        questionLimit.focus();
                        return false;
                    }
                    
                    // Check if time limit is selected
                    if (!timeLimit.value) {
                        e.preventDefault();
                        alert('Please select a time limit for your quiz.');
                        timeLimit.focus();
                        return false;
                    }
                    
                    // Show loading state on submit button
                    const submitBtn = document.querySelector('button[form="randomQuizForm"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Starting Quiz...';
                    }
                    
                    return true;
                });
            }

            // Random Quiz Modal - Course Selection Handling (STABLE VERSION)
            let isSubmitting = false; // Prevent button state changes during submission
            
            courseCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (isSubmitting) return; // Don't change button during submission
                    
                    const checkedCourses = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]:checked');
                    const submitBtn = document.querySelector('button[form="randomQuizForm"]');
                    
                    if (submitBtn && !submitBtn.disabled) { // Only update if not already disabled
                        if (checkedCourses.length === 0) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Select Courses';
                        } else {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-play me-2"></i>Start Quiz';
                        }
                    }
                });
            });
            
            // Prevent button state changes during form submission
            if (randomQuizForm) {
                randomQuizForm.addEventListener('submit', function() {
                    isSubmitting = true;
                });
            }
            
            // Reset button state when modal is opened
            if (randomQuizModal) {
                randomQuizModal.addEventListener('shown.bs.modal', function() {
                    isSubmitting = false; // Reset submission state
                    const submitBtn = document.querySelector('button[form="randomQuizForm"]');
                    const checkedCourses = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]:checked');
                    
                    if (submitBtn) {
                        if (checkedCourses.length === 0) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Select Courses';
                        } else {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-play me-2"></i>Start Quiz';
                        }
                    }
                });
            }
            

        });

        // Initialize topic filters
        if (typeof TopicFilters !== 'undefined') {
            TopicFilters.init();
        }

        // Microsoft Edge modal backdrop fix
        function isEdge() {
            return /Edge|Edg/.test(navigator.userAgent);
        }

        if (isEdge()) {
            // Fix Edge modal backdrop rendering issue
            document.addEventListener('shown.bs.modal', function(e) {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    // Force repaint in Edge
                    backdrop.style.display = 'none';
                    backdrop.offsetHeight; // Trigger reflow
                    backdrop.style.display = 'block';
                    backdrop.style.background = 'rgba(0, 0, 0, 0.5)';
                    backdrop.style.position = 'fixed';
                    backdrop.style.top = '0';
                    backdrop.style.left = '0';
                    backdrop.style.width = '100%';
                    backdrop.style.height = '100%';
                    backdrop.style.zIndex = '1040';
                }
            });

            // Additional fix for modal show event
            modals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function() {
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.style.background = 'rgba(0, 0, 0, 0.5)';
                            backdrop.style.opacity = '1';
                        }
                    }, 50);
                });
            });
        }

        // REMOVED: showQuizModal function - using proper event handler instead
        // The quiz buttons now use the proper event handler that shows the source selection modal
    </script>
    
    <!-- Topic Filters JavaScript -->
    <!-- Removed asset URL -->
    <!-- Duplicate DOMContentLoaded removed - functionality already handled in main script -->
    <!-- External JavaScript file removed - all functionality consolidated inline -->
    
    <!-- 🚀 PERFORMANCE: Minimal inline JavaScript for critical functionality -->
    <script>
        // Critical path JavaScript only
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize performance tracking
            if (window.performance && window.performance.mark) {
                window.performance.mark('topics-page-start');
            }
            
            // Apply saved filter state from URL or sessionStorage
            const urlParams = new URLSearchParams(window.location.search);
            const savedFilter = urlParams.get('filter') || sessionStorage.getItem('topicsFilter') || 'all';
            
            if (savedFilter !== 'all') {
                document.body.classList.add(`${savedFilter}-filter-active`);
            }
        });
    </script>
</body>
</html>