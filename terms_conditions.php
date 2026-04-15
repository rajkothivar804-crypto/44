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
    <title>Terms & Conditions - Cafe Delicious</title>
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

        /* ===== HIGHLIGHT BOX ===== */
        .highlight {
            background-color: #fff3cd;
            padding: 8px 12px;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .highlight:hover {
            background-color: #ffe69c;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
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

    <!-- Terms & Conditions Header -->
    <div class="policy-header">
        <div class="container">
            <h1><i class="fas fa-file-contract"></i> Terms & Conditions</h1>
            <p>Please read these terms carefully.</p>
        </div>
    </div>

    <!-- Terms & Conditions Content -->
    <div class="policy-container">
        <div class="back-btn">
            <a href="home.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>

        <div class="last-updated">
            <strong><i class="fas fa-calendar-alt"></i> Last Updated:</strong> April 14, 2026
        </div>

        <!-- Section 1 -->
        <div class="policy-section">
            <h2>1. Acceptance of Terms</h2>
            <p>
                By accessing and using this website and our services, you accept and agree to be bound by the terms and provision 
                of this agreement. If you do not agree to abide by the above, please do not use this service. Cafe Delicious reserves 
                the right to make changes to these Terms & Conditions at any time without notice.
            </p>
        </div>

        <!-- Section 2 -->
        <div class="policy-section">
            <h2>2. Use License</h2>
            <p>
                Permission is granted to temporarily download one copy of the materials (information or software) on Cafe Delicious's 
                website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, 
                and under this license you may NOT:
            </p>
            <ul>
                <li>Modify or copy the materials</li>
                <li>Use the materials for any commercial purpose or for any public display</li>
                <li>Attempt to decompile or reverse engineer any software contained on the website</li>
                <li>Remove any copyright or other proprietary notations from the materials</li>
                <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
                <li>Violate any applicable laws or regulations</li>
                <li>Harass, abuse, or threaten any person using this service</li>
            </ul>
        </div>

        <!-- Section 3 -->
        <div class="policy-section">
            <h2>3. User Accounts</h2>
            <h3>3.1 Account Creation</h3>
            <p>
                To use certain features of our website, you may need to create an account. You agree to provide accurate, 
                current, and complete information during registration and to update this information as necessary.
            </p>

            <h3>3.2 Account Responsibility</h3>
            <p>
                You are responsible for maintaining the confidentiality of your account information and password. You agree 
                to accept responsibility for all activities that occur under your account. You must notify us immediately of 
                any unauthorized use of your account.
            </p>

            <h3>3.3 Age Requirement</h3>
            <p>
                By creating an account, you represent that you are at least 18 years of age or have parental consent. 
                We reserve the right to terminate accounts of users who do not meet this requirement.
            </p>
        </div>

        <!-- Section 4 -->
        <div class="policy-section">
            <h2>4. Orders and Payments</h2>
            <h3>4.1 Order Acceptance</h3>
            <p>
                Cafe Delicious reserves the right to refuse or cancel any order at any time for any reason, including if 
                an item is out of stock or if there are issues with payment verification.
            </p>

            <h3>4.2 Payment Terms</h3>
            <ul>
                <li>Payment must be made in full at the time of order placement</li>
                <li>We accept various payment methods as displayed on our website</li>
                <li>All prices are subject to applicable taxes and delivery charges</li>
                <li>Refunds will be processed according to the refund policy</li>
            </ul>

            <h3>4.3 Delivery Terms</h3>
            <ul>
                <li>Delivery times are estimates and not guaranteed</li>
                <li>Delivery is subject to availability in your area</li>
                <li>You must provide a valid delivery address</li>
                <li>The customer is responsible for ensuring someone is available to receive the order</li>
            </ul>
        </div>

        <!-- Section 5 -->
        <div class="policy-section">
            <h2>5. Product Quality and Disclaimers</h2>
            <p>
                We make every effort to ensure product accuracy, but we do not guarantee that product descriptions, pricing, 
                images, or other content on our website is accurate or error-free.
            </p>
            <p>
                <span class="highlight">All products are sold "as is" without any warranty.</span> We are not responsible for 
                allergic reactions or adverse effects from consuming our products. Please inform us of any allergies when placing 
                your order.
            </p>
        </div>

        <!-- Section 6 -->
        <div class="policy-section">
            <h2>6. Limitation of Liability</h2>
            <p>
                In no event shall Cafe Delicious, its owners, employees, or affiliates be liable for any indirect, incidental, 
                special, consequential, or punitive damages resulting from:
            </p>
            <ul>
                <li>Use or inability to use our services</li>
                <li>Unauthorized access to or disruption of data transmission</li>
                <li>Product quality or defects</li>
                <li>Third-party actions or omissions</li>
            </ul>
            <p>
                Our total liability to you shall not exceed the amount paid by you for the product or service in question.
            </p>
        </div>

        <!-- Section 7 -->
        <div class="policy-section">
            <h2>7. User Conduct</h2>
            <p>You agree NOT to:</p>
            <ul>
                <li>Use the website for any illegal or unauthorized purpose</li>
                <li>Violate any laws in your jurisdiction</li>
                <li>Harass, abuse, threaten, or defame anyone</li>
                <li>Post or transmit any spam, viruses, or malicious code</li>
                <li>Engage in unauthorized access or data theft</li>
                <li>Collect or track personal information about others</li>
                <li>Post content that infringes intellectual property rights</li>
                <li>Engage in fraudulent or deceptive practices</li>
            </ul>
        </div>

        <!-- Section 8 -->
        <div class="policy-section">
            <h2>8. Refund Policy</h2>
            <p>
                Refunds for orders must be requested within 24 hours of order placement if the order has not been prepared. 
                Once an order has been prepared or dispatched, refunds are not available, except in cases of quality issues 
                or errors on our part.
            </p>
            <p>
                Please contact us with documentation of the issue, and we will evaluate your request accordingly. 
                Approved refunds will be processed within 5-7 business days.
            </p>
        </div>

        <!-- Section 9 -->
        <div class="policy-section">
            <h2>9. Intellectual Property Rights</h2>
            <p>
                All content on this website, including text, graphics, logos, images, product names, and software, is the 
                property of Cafe Delicious or its content suppliers and is protected by international copyright laws.
            </p>
            <p>
                You may not reproduce, distribute, transmit, modify, or create derivative works of any content without our 
                prior written permission.
            </p>
        </div>

        <!-- Section 10 -->
        <div class="policy-section">
            <h2>10. Disclaimer of Warranties</h2>
            <p>
                <span class="highlight">The website and all materials on it are provided on an "as is" basis.</span> Cafe Delicious 
                makes no representations or warranties of any kind, either express or implied, including but not limited to implied 
                warranties of merchantability, fitness for a particular purpose, or non-infringement.
            </p>
        </div>

        <!-- Section 11 -->
        <div class="policy-section">
            <h2>11. Third-Party Links</h2>
            <p>
                Our website may contain links to third-party websites. Cafe Delicious is not responsible for the content, 
                accuracy, or practices of third-party sites. Your use of third-party websites is at your own risk and subject 
                to their terms and conditions.
            </p>
        </div>

        <!-- Section 12 -->
        <div class="policy-section">
            <h2>12. Termination of Use</h2>
            <p>
                Cafe Delicious reserves the right to terminate or suspend your access to the website and services at any time, 
                for any reason, including if you violate these Terms & Conditions or engage in conduct that is disruptive or harmful.
            </p>
        </div>

        <!-- Section 13 -->
        <div class="policy-section">
            <h2>13. Governing Law and Jurisdiction</h2>
            <p>
                These Terms & Conditions are governed by and construed in accordance with the laws of the Republic of India, 
                without regard to its conflicts of law principles. The Courts of India shall have exclusive jurisdiction over 
                any disputes arising from these terms. Both parties agree to submit to the exclusive jurisdiction of the courts 
                located in India.
            </p>
        </div>

        <!-- Section 14 -->
        <div class="policy-section">
            <h2>14. Complaint Resolution</h2>
            <h3>14.1 Grievance Redressal</h3>
            <p>
                We are committed to resolving complaints promptly. If you have a complaint, please email us with full details 
                and we will respond within 48 hours with our resolution or escalation procedure.
            </p>

            <h3>14.2 Escalation</h3>
            <p>
                If your complaint is not resolved, you may escalate it to our customer service team. We will provide you with 
                tracking information and a timeline for resolution.
            </p>
        </div>

        <!-- Section 15 -->
        <div class="policy-section">
            <h2>15. Indemnification</h2>
            <p>
                You agree to indemnify and hold harmless Cafe Delicious, its owners, employees, and agents from any claims, 
                damages, losses, or expenses (including legal fees) arising from your use of the website, violation of these 
                terms, or infringement of any third-party rights.
            </p>
        </div>

        <!-- Section 16 -->
        <div class="policy-section">
            <h2>16. Entire Agreement</h2>
            <p>
                These Terms & Conditions, along with our Privacy Policy, constitute the entire agreement between you and 
                Cafe Delicious regarding your use of the website. If any provision is found to be unenforceable, the remaining 
                provisions will continue in effect.
            </p>
        </div>

        <!-- Section 17 -->
        <div class="policy-section">
            <h2>17. Contact Information</h2>
            <p>
                For questions, concerns, or complaints regarding these Terms & Conditions, please contact us:
            </p>
            <p>
                <strong>Cafe Delicious</strong><br>
                Email: <a href="mailto:support@cafedelicious.com">support@cafedelicious.com</a><br>
                Phone: <a href="tel:+919876543210">+91 (90000) 12345</a><br>
                Address: [Your Address], [City], [State] - [PIN Code], India<br>
                <br>
                <strong>Complaint/Grievance Officer:</strong><br>
                Email: <a href="mailto:complaints@cafedelicious.com">complaints@cafedelicious.com</a><br>
                Response Time: Within 48 hours
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
