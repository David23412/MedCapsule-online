<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <title>Privacy Policy - MedCapsule</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #334155;
            line-height: 1.6;
            background-color: #f8fafc;
        }
        .policy-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        h1 { color: #1e293b; font-weight: 700; margin-bottom: 30px; }
        h2 { color: #334155; font-weight: 600; margin-top: 30px; margin-bottom: 15px; font-size: 1.5rem; }
        p { margin-bottom: 15px; }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="policy-container">
            <a href="{{ route('register') }}" class="back-link">← Back to Registration</a>
            <h1>Privacy Policy</h1>
            <p>Last Updated: April 2024</p>

            <p>This Privacy Policy explains how MedCapsule collects, uses, and protects your personal information when you use our platform.</p>

            <h2>1. Information We Collect</h2>
            <p>When you register, we collect the following personal information:</p>
            <ul>
                <li>Your full name</li>
                <li>Email address</li>
                <li>University name</li>
                <li>Mobile number (11-digit Egyptian mobile number)</li>
                <li>Device information (IP address, browser type)</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use your information for the following purposes:</p>
            <ul>
                <li>To create and manage your account.</li>
                <li>To provide you with personalized study content and track your progress.</li>
                <li>To communicate with you regarding your account and our services.</li>
                <li>To improve the platform and develop new features.</li>
                <li>To ensure the security of our platform and prevent unauthorized access.</li>
            </ul>

            <h2>3. Data Protection</h2>
            <p>We implement a variety of security measures to maintain the safety of your personal information. Your password is encrypted and never stored in plain text. We do not sell, trade, or otherwise transfer your personally identifiable information to outside parties.</p>

            <h2>4. Cookies</h2>
            <p>We use cookies to enhance your experience on MedCapsule. Cookies are small files stored on your device that help us understand your preferences and improve our services.</p>

            <h2>5. Third-Party Links</h2>
            <p>Occasionally, at our discretion, we may include or offer third-party products or services on our website. These third-party sites have separate and independent privacy policies. We, therefore, have no responsibility or liability for the content and activities of these linked sites.</p>

            <h2>6. Your Rights</h2>
            <p>You have the right to access, update, or delete your personal information. If you wish to exercise these rights, please contact us at support@medcapsule.com.</p>

            <h2>7. Contact Us</h2>
            <p>If you have any questions regarding this Privacy Policy, you may contact us at support@medcapsule.com.</p>

            <div class="mt-5 pt-4 border-top text-center text-muted small">
                &copy; {{ date('Y') }} MedCapsule. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
