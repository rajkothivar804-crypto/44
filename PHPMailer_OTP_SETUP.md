# PHPMailer OTP Email Setup Guide

This guide will help you configure PHPMailer to send OTP emails for the forgot password feature.

## 📧 **Gmail SMTP Configuration**

### Step 1: Enable 2-Factor Authentication
1. Go to your Google Account: https://myaccount.google.com/
2. Navigate to **Security** in the left sidebar
3. Under "Signing in to Google", click **2-Step Verification**
4. Follow the steps to enable 2FA

### Step 2: Generate App Password
1. After enabling 2FA, go back to **Security**
2. Under "Signing in to Google", click **App passwords**
3. Select **Mail** and **Other (custom name)**
4. Enter "Cafe Menu OTP" as the custom name
5. Click **Generate**
6. **Copy the 16-character password** (you won't see it again!)

### Step 3: Configure Email Settings
Edit the `email_config.php` file with your details:

```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'your-gmail@gmail.com',     // ← Your Gmail address
    'smtp_password' => 'abcd-efgh-ijkl-mnop',       // ← The 16-char app password
    'from_email' => 'noreply@cafemenu.com',
    'from_name' => 'Cafe Menu',
];
?>
```

## 🔧 **Other Email Providers**

### Outlook/Hotmail
```php
'smtp_host' => 'smtp-mail.outlook.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'your-email@outlook.com',
'smtp_password' => 'your-password',
```

### Yahoo Mail
```php
'smtp_host' => 'smtp.mail.yahoo.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'your-email@yahoo.com',
'smtp_password' => 'your-app-password', // Generate app password in Yahoo settings
```

### Custom SMTP Server
```php
'smtp_host' => 'your-smtp-server.com',
'smtp_port' => 587, // or 465 for SSL
'smtp_secure' => 'tls', // or 'ssl'
'smtp_username' => 'your-username',
'smtp_password' => 'your-password',
```

## 🧪 **Testing the Setup**

1. **Update Configuration**: Edit `email_config.php` with your email details
2. **Test the Function**: Create a test file to verify email sending:

```php
<?php
include 'otp_mailer.php';

// Test email sending
$result = sendOTPEmail('test@example.com', '123456');
echo $result['message'];
?>
```

3. **Check Forgot Password**: Try the forgot password feature on your login page

## 📧 **Email Template Features**

The OTP email includes:
- ✅ **Professional HTML design** with your cafe branding
- ✅ **Large, clear OTP display** for easy reading
- ✅ **Security warnings** and expiration notices
- ✅ **Plain text fallback** for email clients that don't support HTML
- ✅ **Responsive design** that works on mobile devices

## 🔒 **Security Features**

- **OTP Expiration**: Codes expire after 10 minutes
- **One-time Use**: Each OTP can only be used once
- **Secure Storage**: OTPs are hashed and stored securely
- **Rate Limiting**: Previous unused OTPs are cleared when requesting new ones

## 🚨 **Troubleshooting**

### "SMTP connect() failed" Error
- Check your internet connection
- Verify SMTP settings are correct
- Make sure your email provider allows SMTP access
- For Gmail, ensure you're using an App Password, not your regular password

### "Authentication failed" Error
- Double-check your email address and password
- For Gmail, make sure you're using an App Password
- Try generating a new App Password

### Emails going to spam
- Add your sending email to contacts/address book
- Check your email provider's spam settings
- Consider using a custom domain email instead of Gmail

### Still having issues?
- Check the PHPMailer error message for specific details
- Verify your hosting provider allows SMTP connections
- Contact your email provider's support

## 📁 **File Structure**

```
c:\laragon\www\NN\
├── login.php              # Main login page with forgot password
├── otp_mailer.php         # PHPMailer OTP sending function
├── email_config.php       # Email configuration settings
├── php mailer\           # PHPMailer library files
│   ├── PHPMailer.php
│   ├── SMTP.php
│   └── Exception.php
└── database\
    └── db.sql            # Database schema with OTP table
```

## 🎯 **Next Steps**

1. Configure your email settings in `email_config.php`
2. Test the forgot password feature
3. Customize the email template if needed
4. Monitor email delivery and adjust settings as necessary

The system is now ready to send professional OTP emails for password recovery!