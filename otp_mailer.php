<?php
// Include PHPMailer classes
require 'php mailer/PHPMailer.php';
require 'php mailer/SMTP.php';
require 'php mailer/Exception.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load email configuration
$config = include 'email_config.php';

// Function to send OTP via email using PHPMailer
function sendOTPEmail($recipientEmail, $otp) {
    global $config;

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host       = $config['smtp_host'];            // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                             // Enable SMTP authentication
        $mail->Username   = $config['smtp_username'];        // SMTP username
        $mail->Password   = $config['smtp_password'];        // SMTP password
        $mail->SMTPSecure = $config['smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $config['smtp_port'];            // TCP port to connect to

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($recipientEmail);                   // Add recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Password Reset OTP - Cafe Menu';

        // HTML email body
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    .header { text-align: center; color: #8b4513; margin-bottom: 30px; }
                    .otp-box { background-color: #f8f9fa; border: 2px solid #8b4513; border-radius: 5px; padding: 20px; text-align: center; margin: 20px 0; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #8b4513; letter-spacing: 5px; font-family: 'Courier New', monospace; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
                    .warning { color: #dc3545; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔐 Password Reset</h1>
                        <p>Cafe Menu - Secure Password Recovery</p>
                    </div>

                    <p>Hello,</p>
                    <p>You have requested to reset your password. Please use the following One-Time Password (OTP) to complete the process:</p>

                    <div class='otp-box'>
                        <div class='otp-code'>{$otp}</div>
                    </div>

                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This OTP will expire in <strong>10 minutes</strong></li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this password reset, please ignore this email</li>
                    </ul>

                    <p>For security reasons, this OTP can only be used once and will become invalid after the time limit.</p>

                    <div class='footer'>
                        <p><span class='warning'>Security Notice:</span> If you suspect any unauthorized activity on your account, please contact our support team immediately.</p>
                        <p>Best regards,<br>Cafe Menu Team</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        // Plain text alternative
        $mail->AltBody = "Your OTP for password reset is: {$otp}\n\nThis OTP will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nCafe Menu Team";

        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully to your email!'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => "Failed to send OTP email. Mailer Error: {$mail->ErrorInfo}"];
    }
}

// Function to send registration confirmation email
function sendRegistrationEmail($recipientEmail, $fullname) {
    global $config;

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $config['smtp_port'];

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($recipientEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Cafe Menu - Registration Confirmed!';

        // HTML email body
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    .header { text-align: center; color: #8b4513; margin-bottom: 30px; }
                    .welcome-box { background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%); border: 2px solid #8b4513; border-radius: 5px; padding: 20px; text-align: center; margin: 20px 0; }
                    .success-msg { font-size: 24px; font-weight: bold; color: #28a745; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>☕ Welcome to Cafe Menu!</h1>
                    </div>

                    <p>Hello <strong>{$fullname}</strong>,</p>
                    <p>Thank you for registering with us! We're excited to have you as part of our cafe community.</p>

                    <div class='welcome-box'>
                        <div class='success-msg'>✓ Registration Successful!</div>
                    </div>

                    <p><strong>Your account has been created successfully.</strong></p>
                    <p>You can now:</p>
                    <ul>
                        <li>Browse our delicious menu</li>
                        <li>Place orders online</li>
                        <li>Track your orders</li>
                        <li>Save your favorites</li>
                        <li>Manage your profile</li>
                    </ul>

                    <p><strong>Getting Started:</strong> Log in to your account and start exploring our menu. If you need any assistance, feel free to contact our support team.</p>

                    <div class='footer'>
                        <p>Best regards,<br>Cafe Menu Team</p>
                        <p>© 2025 Cafe Delicious. All Rights Reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        // Plain text alternative
        $mail->AltBody = "Welcome {$fullname}!\n\nYour registration to Cafe Menu was successful!\n\nYou can now browse our menu and place orders.\n\nBest regards,\nCafe Menu Team";

        $mail->send();
        return ['success' => true, 'message' => 'Registration confirmation email sent!'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => "Failed to send registration email. Mailer Error: {$mail->ErrorInfo}"];
    }
}

function sendOrderEmail($recipientEmail, $recipientName, $subject, $bodyHtml, $altBody = '') {
    global $config;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $config['smtp_port'];

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($recipientEmail, $recipientName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $altBody ?: strip_tags(str_replace(['<br>', '<br/>', '<br />', '<p>', '</p>'], ["\n", "\n", "\n", "", "\n"], $bodyHtml));

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Failed to send email. Mailer Error: {$mail->ErrorInfo}"];
    }
}
?>