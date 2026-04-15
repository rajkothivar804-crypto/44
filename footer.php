<?php
// Include database connection
if (!isset($conn)) {
    include 'database/DB.php';
}

// Fetch cafe settings from database
if (!function_exists('get_cafe_settings')) {
    function get_cafe_settings() {
        global $conn;
        
        $settings = [
            'address' => '123 Coffee Street, City, State 12345',
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@cafedelicious.com',
            'website' => 'www.cafedelicious.com',
            'hours' => [
                ['day' => 'Mon - Fri', 'hours' => '7:00 AM - 8:00 PM'],
                ['day' => 'Saturday', 'hours' => '8:00 AM - 9:00 PM'],
                ['day' => 'Sunday', 'hours' => '9:00 AM - 7:00 PM']
            ],
            'about_text' => 'Premium coffee experience since 2015. We serve freshly roasted beans and create memories one cup at a time.',
            'social_facebook' => '#',
            'social_instagram' => '#',
            'social_twitter' => '#',
            'social_linkedin' => '#',
            'newsletter_text' => 'Get updates on new menu items and special offers!',
            'footer_bottom_text' => '&copy; 2025 Cafe Delicious. All Rights Reserved. | <a href="privacy_policy.php" style="color: #ffd700; text-decoration: none;">Privacy Policy</a> | <a href="terms_conditions.php" style="color: #ffd700; text-decoration: none;">Terms & Conditions</a>',
            'payment_methods' => '💳,₹,💰'
        ];
        
        // Check if cafe_settings table exists
        $check_table = "SHOW TABLES LIKE 'cafe_settings'";
        $result = mysqli_query($conn, $check_table);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $settings_sql = "SELECT setting_key, setting_value FROM cafe_settings WHERE setting_key IN ('address', 'phone', 'email', 'website', 'hours')";
            $settings_result = mysqli_query($conn, $settings_sql);
            
            if ($settings_result && mysqli_num_rows($settings_result) > 0) {
                while ($row = mysqli_fetch_assoc($settings_result)) {
                    if ($row['setting_key'] === 'hours') {
                        $decoded_hours = json_decode($row['setting_value'], true);
                        if (is_array($decoded_hours) && !empty($decoded_hours)) {
                            $settings['hours'] = $decoded_hours;
                        }
                    } else {
                        $settings[$row['setting_key']] = $row['setting_value'];
                    }
                }
            }
        }
        
        return $settings;
    }
}

// Get settings if connection exists
$cafe_settings = [];
if (isset($conn)) {
    $cafe_settings = get_cafe_settings();
}

function sanitize_footer($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Delicious - Footer</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== FOOTER STYLING ========== */

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #3e2723 0%, #5c4033 100%);
            color: #f5f5dc;
            padding: 60px 0 0;
            margin-top: 60px;
            border-top: 4px solid #d2691e;
        }

        /* Footer Content */
        .footer-content {
            padding: 50px 0 30px;
        }

        .footer-section h5 {
            color: #ffd700;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h5::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 3px;
            background: #d2691e;
            bottom: 0;
            left: 0;
            border-radius: 2px;
        }

        /* About Section */
        .about-footer {
            margin-bottom: 20px;
        }

        .about-footer p {
            color: #e8dcc8;
            line-height: 1.8;
            font-size: 14px;
        }

        /* Social Links */
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: #8b4513;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 16px;
        }

        .social-links a:hover {
            background: #ffd700;
            color: #3e2723;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        /* Links List */
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #e8dcc8;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .footer-links a::before {
            content: '▸ ';
            color: #d2691e;
            margin-right: 8px;
        }

        .footer-links a:hover {
            color: #ffd700;
            padding-left: 5px;
        }

        /* Contact Info */
        .contact-info {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .contact-info li {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
            color: #e8dcc8;
        }

        .contact-info i {
            color: #ffd700;
            margin-top: 2px;
        }

        /* Newsletter */
        .newsletter-form {
            margin-top: 20px;
        }

        .newsletter-input-group {
            display: flex;
            gap: 0;
            margin-bottom: 10px;
        }

        .newsletter-input-group input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #d2691e;
            background: #f5f5dc;
            color: #5c4033;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
        }

        .newsletter-input-group input::placeholder {
            color: #999;
        }

        .newsletter-input-group input:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .newsletter-input-group button {
            padding: 12px 20px;
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .newsletter-input-group button:hover {
            background: linear-gradient(135deg, #d2691e, #8b4513);
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }

        .newsletter-text {
            font-size: 12px;
            color: #e8dcc8;
            margin-top: 8px;
        }

        /* Hours */
        .hours-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hours-list li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #e8dcc8;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(139, 69, 19, 0.2);
        }

        .hours-list li:last-child {
            border-bottom: none;
        }

        .day {
            color: #ffd700;
            font-weight: 600;
        }

        .time {
            color: #d2691e;
            font-weight: 600;
        }

        /* Footer Bottom */
        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px 0;
            border-top: 1px solid rgba(255, 215, 0, 0.1);
            text-align: center;
            color: #e8dcc8;
            font-size: 14px;
        }

        .footer-bottom p {
            margin: 0;
        }

        /* Payment Icons */
        .payment-methods {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
        }

        .payment-icon {
            width: 35px;
            height: 25px;
            background: #f5f5dc;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #5c4033;
            font-weight: 700;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 999;
            display: none;
        }

        .back-to-top:hover {
            background: linear-gradient(135deg, #d2691e, #8b4513);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .back-to-top.show {
            display: flex;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .footer-content {
                padding: 40px 0 20px;
            }

            .footer-section h5 {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .newsletter-input-group {
                flex-direction: column;
            }

            .newsletter-input-group input {
                border-radius: 5px;
            }

            .newsletter-input-group button {
                border-radius: 5px;
            }

            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

<div class="main-content">
    <!-- Your page content goes here -->
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="row">
                <!-- About Section -->
                <div class="col-md-3 col-sm-6 footer-section">
                    <h5>☕ About Cafe Delicious</h5>
                    <div class="about-footer">
                        <p><?php echo sanitize_footer($cafe_settings['about_text'] ?? 'Premium coffee experience since 2015. We serve freshly roasted beans and create memories one cup at a time.'); ?></p>
                        <div class="social-links">
                            <a href="<?php echo sanitize_footer($cafe_settings['social_facebook'] ?? '#'); ?>" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="<?php echo sanitize_footer($cafe_settings['social_instagram'] ?? '#'); ?>" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="<?php echo sanitize_footer($cafe_settings['social_twitter'] ?? '#'); ?>" title="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="<?php echo sanitize_footer($cafe_settings['social_linkedin'] ?? '#'); ?>" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-md-3 col-sm-6 footer-section">
                    <h5>🔗 Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="home.php">Home</a></li>
                        <li><a href="menu.php">Menu</a></li>
                        <li><a href="order.php">Orders</a></li>
                        <li><a href="wishlist.php">Wishlist</a></li>
                        <li><a href="payment.php">Payment</a></li>
                        <li><a href="rating.php">Rating</a></li>
                        
                        <li><a href="logout.php">log-out</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="about_us.php">About Us</a></li>
                        <li><a href="product_review.php">product Review</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="regisration.php">Register</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-md-3 col-sm-6 footer-section">
                    <h5>📞 Contact Us</h5>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo sanitize_footer($cafe_settings['address'] ?? '123 Coffee Street, City, State 12345'); ?></span>
                        </li>
                        <li>
                            <i class="fas fa-phone-alt"></i>
                            <span><?php echo sanitize_footer($cafe_settings['phone'] ?? '+1 (555) 123-4567'); ?></span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><?php echo sanitize_footer($cafe_settings['email'] ?? 'info@cafedelicious.com'); ?></span>
                        </li>
                        <li>
                            <i class="fas fa-globe"></i>
                            <span><?php echo sanitize_footer($cafe_settings['website'] ?? 'www.cafedelicious.com'); ?></span>
                        </li>
                    </ul>
                    <br>
                    <br>
                    

                    <!-- Rating & Review -->
                    <div class="rating-review" style="margin-top: 25px;">
                        <h5 style="margin-top: 0;">⭐ Rate & Review</h5>
                        <div class="d-flex gap-2 mt-2">
                            <a href="rating.php" class="btn btn-warning btn-sm" style="flex: 1;">Rate Us</a>
                            <a href="review.php" class="btn btn-outline-warning btn-sm" style="flex: 1;">Leave Review</a>
                        </div>
                        <p class="newsletter-text" style="margin-top: 10px;">We appreciate your feedback! Your rating helps us serve you better.</p>
                    </div>
                </div>

                <!-- Hours & Newsletter -->
                <div class="col-md-3 col-sm-6 footer-section">
                    <h5>⏰ Opening Hours</h5>
                    <ul class="hours-list">
                        <?php
                        $hours = $cafe_settings['hours'] ?? [
                            ['day' => 'Mon - Fri', 'hours' => '7:00 AM - 8:00 PM'],
                            ['day' => 'Saturday', 'hours' => '8:00 AM - 9:00 PM'],
                            ['day' => 'Sunday', 'hours' => '9:00 AM - 7:00 PM']
                        ];
                        if (is_array($hours)) {
                            foreach ($hours as $hour) {
                                echo '<li>';
                                echo '<span class="day">' . sanitize_footer($hour['day'] ?? '') . ':</span>';
                                echo '<span class="time">' . sanitize_footer($hour['hours'] ?? '' ) . '</span>';
                                echo '</li>';
                            }
                        }
                        ?>
                    </ul>
                     <br>
                    <br>

                    <!-- Newsletter -->
                    <div style="margin-top: 30px;">
                        <h5 style="margin-top: 0;">📧 Newsletter</h5>
                        <div class="newsletter-form">
                            <div class="newsletter-input-group">
                                <input type="email" placeholder="Enter your email">
                                <button type="button">Subscribe</button>
                            </div>
                            <p class="newsletter-text"><?php echo sanitize_footer($cafe_settings['newsletter_text'] ?? 'Get updates on new menu items and special offers!'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <p><?php echo $cafe_settings['footer_bottom_text'] ?? '&copy; 2025 Cafe Delicious. All Rights Reserved. | <a href="#" style="color: #ffd700; text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: #ffd700; text-decoration: none;">Terms & Conditions</a>'; ?></p>
                    <div style="margin-top: 15px;">
                        <p style="margin-bottom: 10px;">We Accept:</p>
                        <div class="payment-methods">
                            <?php
                            $payment_methods = explode(',', $cafe_settings['payment_methods'] ?? '💳,₹,💰');
                            foreach ($payment_methods as $method) {
                                echo '<div class="payment-icon">' . sanitize_footer(trim($method)) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<div class="back-to-top" onclick="scrollToTop()">
    <i class="fas fa-chevron-up"></i>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>



</body>
</html>