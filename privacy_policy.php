<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'database/DB.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Cafe Delicious</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: linear-gradient(135deg, #f9f7f4 0%, #faf8f5 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        /* ===== HEADER STYLING ===== */
        .policy-header {
            background: linear-gradient(135deg, #3e2723 0%, #5c4033 50%, #6d4c41 100%);
            color: #f5f5dc;
            padding: 120px 20px;
            text-align: center;
            border-bottom: 5px solid #d2691e;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(62, 39, 35, 0.3);
        }

        .policy-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .policy-header h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.8s ease-out;
            position: relative;
            z-index: 1;
        }

        .policy-header h1 i {
            margin-right: 15px;
            color: #ffd700;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .policy-header p {
            font-size: 20px;
            color: #ffd700;
            font-weight: 500;
            animation: fadeIn 1s ease-out 0.3s both;
            position: relative;
            z-index: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* ===== CONTAINER STYLING ===== */
        .policy-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 0 20px;
        }

        /* ===== BACK BUTTON ===== */
        .back-btn {
            margin-bottom: 40px;
            display: inline-block;
        }

        .back-btn a {
            color: #d2691e;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            background: rgba(210, 105, 30, 0.1);
            transition: all 0.3s ease;
            border: 2px solid #d2691e;
        }

        .back-btn a:hover {
            color: white;
            background: #d2691e;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(210, 105, 30, 0.3);
        }

        /* ===== LAST UPDATED ===== */
        .last-updated {
            background: linear-gradient(135deg, #f0e6d2 0%, #ede0c8 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 40px;
            color: #5c4033;
            font-size: 14px;
            border-left: 5px solid #d2691e;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            animation: slideInLeft 0.6s ease-out;
            font-weight: 500;
        }

        .last-updated i {
            color: #d2691e;
            margin-right: 8px;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ===== POLICY SECTIONS ===== */
        .policy-section {
            background: white;
            padding: 35px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #d2691e;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .policy-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(210, 105, 30, 0.03) 0%, transparent 100%);
            pointer-events: none;
        }

        .policy-section:hover {
            box-shadow: 0 8px 24px rgba(62, 39, 35, 0.15);
            transform: translateY(-5px);
            border-left-color: #ffd700;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== HEADINGS ===== */
        .policy-section h2 {
            color: #3e2723;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0e6d2;
            position: relative;
            z-index: 1;
        }

        .policy-section h2::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, #d2691e 0%, #ffd700 100%);
            border-radius: 2px;
        }

        .policy-section h3 {
            color: #5c4033;
            font-size: 17px;
            font-weight: 600;
            margin-top: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .policy-section h3::before {
            content: '▸';
            color: #d2691e;
            font-size: 20px;
        }

        /* ===== PARAGRAPHS & TEXT ===== */
        .policy-section p {
            color: #444;
            line-height: 1.85;
            margin-bottom: 15px;
            font-size: 15px;
            position: relative;
            z-index: 1;
        }

        .policy-section p a {
            color: #d2691e;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .policy-section p a:hover {
            color: #ffd700;
            border-bottom-color: #ffd700;
        }

        /* ===== LISTS ===== */
        .policy-section ul {
            margin-left: 30px;
            margin-bottom: 15px;
            list-style: none;
            position: relative;
            z-index: 1;
        }

        .policy-section li {
            margin-bottom: 12px;
            color: #444;
            line-height: 1.75;
            font-size: 15px;
            position: relative;
            padding-left: 8px;
            transition: all 0.2s ease;
        }

        .policy-section li:hover {
            color: #d2691e;
            transform: translateX(5px);
        }

        .policy-section li::before {
            content: '✓';
            color: #d2691e;
            font-weight: bold;
            display: inline-block;
            width: 20px;
            text-align: center;
            margin-right: 10px;
            margin-left: -28px;
        }

        /* ===== STRONG TEXT ===== */
        .policy-section strong {
            color: #3e2723;
            font-weight: 700;
            background: rgba(210, 105, 30, 0.05);
            padding: 2px 6px;
            border-radius: 3px;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .policy-header {
                padding: 80px 20px;
            }

            .policy-header h1 {
                font-size: 40px;
                letter-spacing: 1px;
            }

            .policy-header h1 i {
                display: block;
                margin-bottom: 10px;
            }

            .policy-header p {
                font-size: 16px;
            }

            .policy-section {
                padding: 25px;
                margin-bottom: 20px;
            }

            .policy-section h2 {
                font-size: 20px;
                margin-bottom: 15px;
            }

            .policy-section h3 {
                font-size: 15px;
            }

            .policy-section p,
            .policy-section li {
                font-size: 14px;
            }

            .policy-container {
                margin: 30px auto;
            }
        }

        @media (max-width: 480px) {
            .policy-header {
                padding: 60px 15px;
                border-bottom-width: 3px;
            }

            .policy-header h1 {
                font-size: 32px;
            }

            .policy-section {
                padding: 20px;
                margin-bottom: 15px;
            }

            .back-btn a {
                font-size: 14px;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'hader.php'; ?>

    <!-- Privacy Policy Header -->
    <div class="policy-header">
        <div class="container">
            <h1><i class="fas fa-lock"></i> Privacy Policy</h1>
            <p>Your privacy is important to us.</p>
        </div>
    </div>

    <!-- Privacy Policy Content -->
    <div class="policy-container">
        <div class="back-btn">
            <a href="home.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>

        <div class="last-updated">
            <strong><i class="fas fa-calendar-alt"></i> Last Updated:</strong> April 14, 2026
        </div>

        <!-- Section 1 -->
        <div class="policy-section">
            <h2>1. Introduction</h2>
            <p>
                Welcome to Cafe Delicious ("Company," "we," "us," "our," or "the Cafe"). We are committed to protecting your privacy and 
                ensuring you have a positive experience on our website and while using our services. This Privacy Policy explains our 
                online information practices and applies to the information we collect online, offline, or when you use our website and services.
            </p>
        </div>

        <!-- Section 2 -->
        <div class="policy-section">
            <h2>2. Information We Collect</h2>
            <h3>2.1 Information You Provide Directly</h3>
            <ul>
                <li><strong>Account Information:</strong> Name, email address, phone number, password, delivery address</li>
                <li><strong>Order Information:</strong> Items ordered, delivery preferences, special instructions</li>
                <li><strong>Payment Information:</strong> Credit card details, billing address (processed securely)</li>
                <li><strong>Communication:</strong> Messages, inquiries, feedback, reviews, and ratings</li>
                <li><strong>Profile Information:</strong> Profile picture, preferences, dietary restrictions</li>
            </ul>

            <h3>2.2 Information Collected Automatically</h3>
            <ul>
                <li><strong>Browsing Information:</strong> Pages visited, time spent, links clicked, referring website</li>
                <li><strong>Device Information:</strong> Device type, browser type, IP address, operating system</li>
                <li><strong>Location Information:</strong> General location for delivery purposes (if permitted)</li>
                <li><strong>Cookies and Similar Technologies:</strong> We use cookies to enhance your experience</li>
            </ul>
        </div>

        <!-- Section 3 -->
        <div class="policy-section">
            <h2>3. How We Use Your Information</h2>
            <p>We use the information we collect for the following purposes:</p>
            <ul>
                <li>Processing and fulfilling your orders</li>
                <li>Managing your account and allowing login</li>
                <li>Processing payments and preventing fraud</li>
                <li>Sending order updates and delivery notifications</li>
                <li>Providing customer support and responding to inquiries</li>
                <li>Personalizing your experience and showing relevant content</li>
                <li>Conducting surveys, contests, and promotional activities</li>
                <li>Improving our website, services, and user experience</li>
                <li>Complying with legal obligations</li>
                <li>Sending marketing communications (with your consent)</li>
            </ul>
        </div>

        <!-- Section 4 -->
        <div class="policy-section">
            <h2>4. Cookies Policy</h2>
            <h3>4.1 What are Cookies?</h3>
            <p>
                Cookies are small text files stored on your device that help us recognize you and improve your browsing experience. 
                They allow us to remember your preferences, login information, and shopping cart items.
            </p>

            <h3>4.2 Types of Cookies We Use</h3>
            <ul>
                <li><strong>Essential Cookies:</strong> Required for basic website functionality (login, payment processing)</li>
                <li><strong>Performance Cookies:</strong> Help us understand how you use our website and improve performance</li>
                <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                <li><strong>Marketing Cookies:</strong> Track your interests to show relevant advertisements</li>
            </ul>

            <h3>4.3 Managing Cookies</h3>
            <p>
                You can disable cookies through your browser settings or opt-out of specific cookie types. However, disabling 
                cookies may limit website functionality. We use cookies from Google Analytics to understand user behavior.
            </p>
        </div>

        <!-- Section 5 -->
        <div class="policy-section">
            <h2>5. Third-Party Services</h2>
            <p>
                We use third-party services to enhance our website functionality. These include:
            </p>
            <ul>
                <li><strong>Google Analytics:</strong> To analyze website traffic and user behavior patterns</li>
                <li><strong>Payment Gateways:</strong> Secure payment processing platforms</li>
                <li><strong>Email Services:</strong> To send order confirmations, updates, and promotional content</li>
                <li><strong>Rating and Review Platforms:</strong> To collect and manage customer feedback</li>
                <li><strong>Advertising Networks:</strong> To display personalized advertisements based on your interests</li>
            </ul>
            <p>
                These third parties maintain their own privacy policies and are not covered by this Privacy Policy. 
                We encourage you to review their policies before providing personal information.
            </p>
        </div>

        <!-- Section 6 -->
        <div class="policy-section">
            <h2>6. Data Protection and Security</h2>
            <p>
                We implement appropriate technical and organizational measures to protect your personal information against 
                unauthorized access, alteration, disclosure, or destruction. We use industry-standard encryption (SSL/TLS) for 
                data transmission and secure servers for data storage. We comply with applicable data protection laws in India.
            </p>
            <p>
                However, no method of transmission over the Internet or electronic storage is 100% secure. While we strive to 
                protect your information, we cannot guarantee absolute security. You use our services at your own risk.
            </p>
        </div>

        <!-- Section 7 -->
        <div class="policy-section">
            <h2>7. Sharing of Information</h2>
            <p>
                We do not sell, rent, or lease your personal information to third parties. However, we may share your information 
                in the following circumstances:
            </p>
            <ul>
                <li><strong>Service Providers:</strong> Delivery partners, payment processors, email providers</li>
                <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
                <li><strong>Business Transactions:</strong> In case of merger or acquisition</li>
                <li><strong>With Your Consent:</strong> When you explicitly authorize us to share your information</li>
            </ul>
        </div>

        <!-- Section 9 -->
        <div class="policy-section">
            <h2>9. Children's Privacy</h2>
            <p>
                Our services are not intended for children under the age of 13. We do not knowingly collect personal information 
                from children. If we become aware that we have collected information from a child under 13, we will delete it immediately. 
                Parents or guardians who believe their child has provided us with personal information should contact us at the 
                provided contact information.
            </p>
        </div>

        <!-- Section 10 -->
        <div class="policy-section">
            <h2>10. Your Privacy Rights</h2>
            <h3>10.1 Access to Your Information</h3>
            <p>You have the right to request and obtain a copy of your personal information that we hold.</p>

            <h3>10.2 Correction and Updates</h3>
            <p>You can access, review, and correct your personal information at any time through your account settings.</p>

            <h3>10.3 Deletion Rights</h3>
            <p>
                You may request deletion of your account and associated personal data. However, we may retain information 
                necessary for legal compliance, tax purposes, and order history.
            </p>

            <h3>10.4 Marketing Communications</h3>
            <p>
                You can opt-out of promotional emails and SMS by clicking the "Unsubscribe" link in our communications 
                or adjusting notification preferences in your account settings.
            </p>

            <h3>10.5 Data Portability</h3>
            <p>You can request your personal information in a structured, commonly used format, which we will provide within 30 days.</p>
        </div>

        <!-- Section 11 -->
        <div class="policy-section">
            <h2>11. Third-Party Links</h2>
            <p>
                Our website may contain links to third-party websites. We are not responsible for the privacy practices of 
                these external sites. We encourage you to review their privacy policies before providing any personal information.
            </p>
        </div>

        <!-- Section 12 -->
        <div class="policy-section">
            <h2>12. Changes to This Privacy Policy</h2>
            <p>
                We may update this Privacy Policy from time to time to reflect changes in our practices, technology, legal requirements, 
                or for other operational reasons. We will notify you of any material changes by posting the updated policy on our website 
                and updating the "Last Updated" date at the top. Your continued use of our services after any changes constitutes your 
                acceptance of the updated policy.
            </p>
        </div>

        <!-- Section 13 -->
        <div class="policy-section">
            <h2>13. Grievance Officer</h2>
            <p>
                In accordance with Indian data protection practices, we have appointed a Grievance Officer to address your concerns 
                regarding data privacy and personal information handling. Please reach out to us using the contact information below.
            </p>
        </div>

        <!-- Section 14 -->
        <div class="policy-section">
            <h2>14. Contact Us</h2>
            <p>
                If you have questions about this Privacy Policy, our privacy practices, or wish to exercise your privacy rights, 
                please contact us at:
            </p>
            <p>
                <strong>Cafe Delicious</strong><br>
                Email: <a href="mailto:privacy@cafedelicious.com">privacy@cafedelicious.com</a><br>
                Phone: <a href="tel:+919876543210">+91 (90000) 12345</a><br>
                Address: [Your Address], [City], [State] - [PIN Code], India<br>
                <br>
                <strong>Grievance Officer:</strong><br>
                Email: <a href="mailto:grievance@cafedelicious.com">grievance@cafedelicious.com</a>
            </p>
        </div>

        <div class="back-btn" style="margin-top: 40px;">
            <a href="home.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
