<?php
// Test script for PHPMailer OTP functionality
include 'otp_mailer.php';

echo "Testing PHPMailer OTP Email System\n";
echo "==================================\n\n";

// Test with a sample OTP
$testEmail = 'akothivar063@rku.ac.in'; // Replace with your actual email for testing
$testOTP = '123456';

echo "Sending test OTP email to: {$testEmail}\n";
echo "OTP Code: {$testOTP}\n\n";

$result = sendOTPEmail($testEmail, $testOTP);

if ($result['success']) {
    echo "✅ SUCCESS: " . $result['message'] . "\n";
    echo "Check your email inbox (and spam folder) for the OTP email.\n";
} else {
    echo "❌ FAILED: " . $result['message'] . "\n";
    echo "\nTroubleshooting tips:\n";
    echo "1. Check your email_config.php settings\n";
    echo "2. For Gmail, make sure you're using an App Password\n";
    echo "3. Verify your internet connection\n";
    echo "4. Check if your email provider blocks SMTP\n";
}

echo "\nIf email fails, the OTP will be displayed in the login page for testing.\n";
?>