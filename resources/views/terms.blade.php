<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <title>Terms of Service - MedCapsule</title>
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
            <h1>Terms of Service</h1>
            <p>Last Updated: April 2024</p>
            
            <p>Welcome to MedCapsule. By accessing or using our platform, you agree to comply with and be bound by the following terms and conditions.</p>

            <h2>1. Account Registration</h2>
            <p>To use MedCapsule, you must register for an account. You agree to provide accurate, current, and complete information during the registration process. You are responsible for safeguarding your password and for any activities or actions under your account.</p>
            <p>Specifically, users must provide a valid Egyptian mobile number for verification and communication purposes.</p>

            <h2>2. Intellectual Property</h2>
            <p>All content on MedCapsule, including but not limited to quiz questions, explanations, videos, images, and software, is the property of MedCapsule and is protected by intellectual property laws. You may not reproduce, distribute, or create derivative works from this content without express written permission.</p>

            <h2>3. User Conduct</h2>
            <p>You agree not to use the platform for any unlawful purpose or in any way that interrupts, damages, or impairs the service. Prohibited activities include attempting to gain unauthorized access to our systems or harvesting data from the platform.</p>

            <h2>4. Subscription and Payments</h2>
            <p>Certain features of MedCapsule may require payment. All fees are non-refundable unless otherwise stated. We reserve the right to change our pricing at any time.</p>

            <h2>5. Limitation of Liability</h2>
            <p>MedCapsule provides educational materials "as is" without any warranties. While we strive for accuracy, we do not guarantee that the content is error-free or that the platform will be available at all times.</p>

            <h2>6. Changes to Terms</h2>
            <p>We may modify these terms at any time. Your continued use of the platform after such changes constitutes your acceptance of the new terms.</p>

            <div class="mt-5 pt-4 border-top text-center text-muted small">
                &copy; {{ date('Y') }} MedCapsule. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
