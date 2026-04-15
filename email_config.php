<?php
// Email Configuration for PHPMailer
// Update these settings with your email provider details

return [
    // SMTP Server Settings
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587, // 587 for TLS, 465 for SSL
    'smtp_secure' => 'tls', // 'tls' or 'ssl'

    // Authentication
    'smtp_username' => 'akothivar063@rku.ac.in', // Your email address
    'smtp_password' => 'joqwywpxrwvsrzlt', // Your Gmail App Password

    // Email Settings
    'from_email' => 'noreply@cafemenu.com',
    'from_name' => 'Cafe Menu',

    // For Gmail: Use App Passwords instead of regular password
    // 1. Go to Google Account settings
    // 2. Enable 2-Factor Authentication
    // 3. Generate an App Password: https://myaccount.google.com/apppasswords
    // 4. Use the 16-character app password above
];
?>