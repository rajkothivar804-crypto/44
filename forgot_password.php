<?php
include 'hader.php';
include 'database/DB.php';
include 'otp_mailer.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$alert_message = '';
$alert_type = '';
$step = 'email';
$reset_email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';

// Handle sending OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $reset_email = isset($_POST['forgot_email']) ? trim($_POST['forgot_email']) : '';

    if (empty($reset_email)) {
        $alert_message = 'Email is required!';
        $alert_type = 'danger';
    } elseif (!filter_var($reset_email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = 'Invalid email format!';
        $alert_type = 'danger';
    } else {
        $sql = 'SELECT id FROM users WHERE email = ?';
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $reset_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) === 1) {
                $otp = sprintf('%06d', mt_rand(0, 999999));

                $delete_sql = 'DELETE FROM password_reset_otp WHERE email = ? AND used = 0';
                $delete_stmt = mysqli_prepare($conn, $delete_sql);
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, 's', $reset_email);
                    mysqli_stmt_execute($delete_stmt);
                    mysqli_stmt_close($delete_stmt);
                }

                $insert_sql = 'INSERT INTO password_reset_otp (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))';
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                if ($insert_stmt) {
                    mysqli_stmt_bind_param($insert_stmt, 'ss', $reset_email, $otp);
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $emailResult = sendOTPEmail($reset_email, $otp);
                        if ($emailResult['success']) {
                            $_SESSION['reset_email'] = $reset_email;
                            $step = 'otp';
                            $alert_message = $emailResult['message'];
                            $alert_type = 'success';
                        } else {
                            // dev fallback
                            $_SESSION['reset_email'] = $reset_email;
                            $step = 'otp';
                            $alert_message = $emailResult['message'] . ' For testing: OTP is: ' . $otp;
                            $alert_type = 'warning';
                        }
                    } else {
                        $alert_message = 'Failed to generate OTP. Please try again.';
                        $alert_type = 'danger';
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    $alert_message = 'Database error: ' . mysqli_error($conn);
                    $alert_type = 'danger';
                }
            } else {
                // avoid enumeration
                $_SESSION['reset_email'] = $reset_email;
                $step = 'otp';
                $alert_message = 'If an account with this email exists, an OTP has been sent.';
                $alert_type = 'success';
            }
            mysqli_stmt_close($stmt);
        } else {
            $alert_message = 'Database error: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $reset_email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    $entered_otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    if (empty($reset_email)) {
        $alert_message = 'Session expired. Please request a new OTP.';
        $alert_type = 'danger';
        $step = 'email';
    } elseif (empty($entered_otp)) {
        $alert_message = 'Please enter the OTP!';
        $alert_type = 'danger';
        $step = 'otp';
    } else {
        $sql = 'SELECT id FROM password_reset_otp WHERE email = ? AND otp = ? AND expires_at >= NOW() AND used = 0';
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $reset_email, $entered_otp);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) === 1) {
                $update_sql = 'UPDATE password_reset_otp SET used = 1 WHERE email = ? AND otp = ?';
                $update_stmt = mysqli_prepare($conn, $update_sql);
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, 'ss', $reset_email, $entered_otp);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
                $_SESSION['otp_verified'] = true;
                $step = 'reset';
                $alert_message = 'OTP verified successfully! Set your new password now.';
                $alert_type = 'success';
            } else {
                $alert_message = 'OTP invalid or expired. Please request a new OTP.';
                $alert_type = 'danger';
                $step = 'otp';
            }
            mysqli_stmt_close($stmt);
        } else {
            $alert_message = 'Database error: ' . mysqli_error($conn);
            $alert_type = 'danger';
            $step = 'otp';
        }
    }
}

// Handle password reset after OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $reset_email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($reset_email) || !isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        $alert_message = 'OTP verification required. Please start again.';
        $alert_type = 'danger';
        $step = 'email';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $alert_message = 'Please enter and confirm the new password.';
        $alert_type = 'danger';
        $step = 'reset';
    } elseif ($new_password !== $confirm_password) {
        $alert_message = 'Passwords do not match!';
        $alert_type = 'danger';
        $step = 'reset';
    } elseif (strlen($new_password) < 6) {
        $alert_message = 'Password must be at least 6 characters long.';
        $alert_type = 'danger';
        $step = 'reset';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = 'UPDATE users SET password = ? WHERE email = ?';
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'ss', $hashed_password, $reset_email);
            if (mysqli_stmt_execute($update_stmt)) {
                unset($_SESSION['reset_email']);
                unset($_SESSION['otp_verified']);
                $alert_message = 'Password reset successfully. You can now login.';
                $alert_type = 'success';
                $step = 'completed';
            } else {
                $alert_message = 'Failed to update password. Please try again.';
                $alert_type = 'danger';
                $step = 'reset';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $alert_message = 'Database error: ' . mysqli_error($conn);
            $alert_type = 'danger';
            $step = 'reset';
        }
    }
}

// Render HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Cafe Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%); padding-top: 40px;">
<
<div class="container" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="card-title text-center">Forgot Password</h3>
            <?php if (!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?>" role="alert"><?= htmlspecialchars($alert_message); ?></div>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
                <form method="post" action="forgot_password.php">
                    <div class="form-group">
                        <label for="forgot_email">Email address</label>
                        <input type="email" class="form-control" id="forgot_email" name="forgot_email" value="<?= htmlspecialchars($reset_email); ?>">
                    </div>
                    <button type="submit" name="send_otp" class="btn btn-primary btn-block">Send OTP</button>
                    <a href="login.php" class="btn btn-link btn-block">Back to login</a>
                </form>
            <?php elseif ($step === 'otp'): ?>
                <p>OTP was sent to <strong><?= htmlspecialchars($reset_email); ?></strong>.</p>
                <form method="post" action="forgot_password.php">
                    <div class="form-group">
                        <label for="otp">Enter OTP</label>
                        <input type="text" class="form-control" id="otp" name="otp" autofocus>
                    </div>
                    <button type="submit" name="verify_otp" class="btn btn-primary btn-block">Verify OTP</button>
                    <a href="forgot_password.php" class="btn btn-link btn-block">Resend OTP / change email</a>
                </form>
            <?php elseif ($step === 'reset'): ?>
                <p>Set your new password for <strong><?= htmlspecialchars($reset_email); ?></strong>.</p>
                <form method="post" action="forgot_password.php">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-success btn-block">Reset Password</button>
                </form>
            <?php elseif ($step === 'completed'): ?>
                <div class="text-center">
                    <p>Password reset complete.</p>
                    <a href="login.php" class="btn btn-primary">Go to login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Email form validation
    const emailForm = document.querySelector('form[action*="forgot_password.php"]');
    if (emailForm && emailForm.querySelector('input[name="forgot_email"]')) {
        const emailInput = emailForm.querySelector('input[name="forgot_email"]');
        const submitBtn = emailForm.querySelector('button[name="send_otp"]');
        
        function validateEmail() {
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email === '') {
                showError(emailInput, 'Email is required');
                return false;
            } else if (!emailRegex.test(email)) {
                showError(emailInput, 'Please enter a valid email address');
                return false;
            } else {
                clearError(emailInput);
                return true;
            }
        }
        
        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', function() {
            if (emailInput.classList.contains('is-invalid')) {
                validateEmail();
            }
        });
        
        emailForm.addEventListener('submit', function(e) {
            if (!validateEmail()) {
                e.preventDefault();
            }
        });
    }
    
    // OTP form validation
    const otpForm = document.querySelector('form');
    if (otpForm && otpForm.querySelector('input[name="otp"]')) {
        const otpInput = otpForm.querySelector('input[name="otp"]');
        const submitBtn = otpForm.querySelector('button[name="verify_otp"]');
        
        function validateOTP() {
            const otp = otpInput.value.trim();
            const otpRegex = /^[0-9]{6}$/;
            
            if (otp === '') {
                showError(otpInput, 'Please enter the 6-digit OTP');
                return false;
            } else if (!otpRegex.test(otp)) {
                showError(otpInput, 'OTP must be exactly 6 digits');
                return false;
            } else {
                clearError(otpInput);
                return true;
            }
        }
        
        otpInput.addEventListener('blur', validateOTP);
        otpInput.addEventListener('input', function() {
            if (otpInput.classList.contains('is-invalid')) {
                validateOTP();
            }
        });
        
        otpForm.addEventListener('submit', function(e) {
            if (!validateOTP()) {
                e.preventDefault();
            }
        });
    }
    
    // Password reset form validation
    const passwordForm = document.querySelector('form');
    if (passwordForm && passwordForm.querySelector('input[name="new_password"]')) {
        const newPasswordInput = passwordForm.querySelector('input[name="new_password"]');
        const confirmPasswordInput = passwordForm.querySelector('input[name="confirm_password"]');
        const submitBtn = passwordForm.querySelector('button[name="reset_password"]');
        
        function validatePassword() {
            const password = newPasswordInput.value;
            
            if (password === '') {
                showError(newPasswordInput, 'Password is required');
                return false;
            } else if (password.length < 6) {
                showError(newPasswordInput, 'Password must be at least 6 characters');
                return false;
            } else {
                clearError(newPasswordInput);
                return true;
            }
        }
        
        function validateConfirmPassword() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword === '') {
                showError(confirmPasswordInput, 'Please confirm your password');
                return false;
            } else if (password !== confirmPassword) {
                showError(confirmPasswordInput, 'Passwords do not match');
                return false;
            } else {
                clearError(confirmPasswordInput);
                return true;
            }
        }
        
        newPasswordInput.addEventListener('blur', validatePassword);
        newPasswordInput.addEventListener('input', function() {
            if (newPasswordInput.classList.contains('is-invalid')) {
                validatePassword();
            }
            // Also validate confirm password if it's filled
            if (confirmPasswordInput.value !== '') {
                validateConfirmPassword();
            }
        });
        
        confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
        confirmPasswordInput.addEventListener('input', function() {
            if (confirmPasswordInput.classList.contains('is-invalid')) {
                validateConfirmPassword();
            }
        });
        
        passwordForm.addEventListener('submit', function(e) {
            const passwordValid = validatePassword();
            const confirmValid = validateConfirmPassword();
            
            if (!passwordValid || !confirmValid) {
                e.preventDefault();
            }
        });
    }
    
    // Helper functions
    function showError(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        
        let errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            input.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }
    
    function clearError(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        
        const errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
});
</script>
</body>
</html>