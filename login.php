<?php 
// Buffer output to allow redirects after includes
ob_start();

include 'hader.php'; 
include 'database/DB.php';
include 'otp_mailer.php'; // Include PHPMailer OTP function

// Start session immediately to track OTP flow across reloads
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$alert_message = '';
$alert_type = '';
$email_value = '';

// Check for remember me cookie
$remember_checked = '';
$email_value = '';
if (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email']) && filter_var($_COOKIE['user_email'], FILTER_VALIDATE_EMAIL)) {
    $email_value = $_COOKIE['user_email'];
    $remember_checked = 'checked';
}

// Clean up expired OTPs (run this occasionally)
$cleanup_sql = "DELETE FROM password_reset_otp WHERE expires_at < NOW()";
mysqli_query($conn, $cleanup_sql);

// Handle login form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])){
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $remember_me = isset($_POST['rememberMe']) ? true : false;
    $email_value = $email;

    // Validation
    if (empty($email)) {
        $alert_message = "Email is required!";
        $alert_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = "Invalid email format!";
        $alert_type = "danger";
    } elseif (empty($password)) {
        $alert_message = "Password is required!";
        $alert_type = "danger";
    } else {
        // Query database for user
        $sql = "SELECT id, fullname, email, password FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $alert_message = "Database error: " . mysqli_error($conn);
            $alert_type = "danger";
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                
                // Verify password using password_verify()
                if (password_verify($password, $user['password'])) {
                    // Password is correct - set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['fullname'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    
                    // Remember me functionality
                    if ($remember_me) {
                        $cookie_expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        // For local development, use simpler cookie settings
                        $cookie_set = @setcookie('user_email', $email, $cookie_expiry);
                        
                        if (!$cookie_set) {
                            // If that fails, try with explicit parameters
                            $cookie_set = @setcookie('user_email', $email, $cookie_expiry, '/', '', false, false);
                        }
                        
                        // Don't log failures in production to avoid log spam
                        // The login still works even if cookie setting fails
                    }
                    
                    // Redirect immediately after successful login
                    header('Location: home.php');
                    exit();
                } else {
                    // Password is incorrect
                    $alert_message = "Invalid email or password!";
                    $alert_type = "danger";
                }
            } else {
                // Email not found
                $alert_message = "Invalid email or password!";
                $alert_type = "danger";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle reset password form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])){
    $reset_email = isset($_POST['reset_email']) ? trim($_POST['reset_email']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Validation
    if (empty($reset_email)) {
        $alert_message = "Email is required for password reset!";
        $alert_type = "danger";
    } elseif (!filter_var($reset_email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = "Invalid email format!";
        $alert_type = "danger";
    } elseif (empty($new_password)) {
        $alert_message = "New password is required!";
        $alert_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $alert_message = "Password must be at least 6 characters long!";
        $alert_type = "danger";
    } elseif (empty($confirm_password)) {
        $alert_message = "Please confirm your new password!";
        $alert_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $alert_message = "Passwords do not match!";
        $alert_type = "danger";
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $alert_message = "Database error: " . mysqli_error($conn);
            $alert_type = "danger";
        } else {
            mysqli_stmt_bind_param($stmt, 's', $reset_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                // Email exists, update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                
                if (!$update_stmt) {
                    $alert_message = "Database error: " . mysqli_error($conn);
                    $alert_type = "danger";
                } else {
                    mysqli_stmt_bind_param($update_stmt, 'ss', $hashed_password, $reset_email);
                    if (mysqli_stmt_execute($update_stmt)) {
                        $alert_message = "Password reset successful! You can now login with your new password.";
                        $alert_type = "success";
                    } else {
                        $alert_message = "Failed to reset password. Please try again.";
                        $alert_type = "danger";
                    }
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                // Email not found
                $alert_message = "No account found with this email address!";
                $alert_type = "danger";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle forgot password OTP request
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])){
    $forgot_email = isset($_POST['forgot_email']) ? trim($_POST['forgot_email']) : '';
    
    if (empty($forgot_email)) {
        $alert_message = "Email is required!";
        $alert_type = "danger";
    } elseif (!filter_var($forgot_email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = "Invalid email format!";
        $alert_type = "danger";
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $alert_message = "Database error: " . mysqli_error($conn);
            $alert_type = "danger";
        } else {
            mysqli_stmt_bind_param($stmt, 's', $forgot_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                // Generate OTP
                $otp = sprintf("%06d", mt_rand(1, 999999));

                // Delete any existing unused OTPs for this email
                $delete_sql = "DELETE FROM password_reset_otp WHERE email = ? AND used = 0";
                $delete_stmt = mysqli_prepare($conn, $delete_sql);
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, 's', $forgot_email);
                    mysqli_stmt_execute($delete_stmt);
                    mysqli_stmt_close($delete_stmt);
                }

                // Insert new OTP - use DB time (avoid PHP/MySQL timezone issues)
                $insert_sql = "INSERT INTO password_reset_otp (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);

                if (!$insert_stmt) {
                    $alert_message = "Database error: " . mysqli_error($conn);
                    $alert_type = "danger";
                } else {
                    mysqli_stmt_bind_param($insert_stmt, 'ss', $forgot_email, $otp);
                    if (mysqli_stmt_execute($insert_stmt)) {
                        // Send OTP via PHPMailer
                        $emailResult = sendOTPEmail($forgot_email, $otp);

                        if ($emailResult['success']) {
                            $_SESSION['reset_email'] = $forgot_email;
                            $alert_message = $emailResult['message'];
                            $alert_type = "success";
                        } else {
                            // If email fails, show OTP for development/testing
                            $alert_message = $emailResult['message'] . " For testing: Your OTP is: " . $otp;
                            $alert_type = "warning";
                            $_SESSION['reset_email'] = $forgot_email;
                        }
                    } else {
                        $alert_message = "Failed to generate OTP. Please try again.";
                        $alert_type = "danger";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            } else {
                // Email not found - still show success message to prevent email enumeration
                $alert_message = "If an account with this email exists, an OTP has been sent.";
                $alert_type = "success";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle OTP verification
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])){
    $entered_otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    $reset_email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    
    if (empty($entered_otp)) {
        $alert_message = "Please enter the OTP!";
        $alert_type = "danger";
    } elseif (empty($reset_email)) {
        $alert_message = "Session expired. Please request OTP again.";
        $alert_type = "danger";
    } else {
        // Verify OTP
        $sql = "SELECT id FROM password_reset_otp WHERE email = ? AND otp = ? AND expires_at >= NOW() AND used = 0";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $alert_message = "Database error: " . mysqli_error($conn);
            $alert_type = "danger";
        } else {
            mysqli_stmt_bind_param($stmt, 'ss', $reset_email, $entered_otp);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                // OTP is valid, mark as used and allow password reset
                $update_sql = "UPDATE password_reset_otp SET used = 1 WHERE email = ? AND otp = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, 'ss', $reset_email, $entered_otp);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
                
                $_SESSION['otp_verified'] = true;
                $alert_message = "OTP verified successfully! You can now reset your password.";
                $alert_type = "success";
            } else {
                $alert_message = "OTP invalid or expired. Please request a new OTP and try again.";
                $alert_type = "danger";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle password reset after OTP verification
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_otp'])){
    $new_password = isset($_POST['new_password_otp']) ? trim($_POST['new_password_otp']) : '';
    $confirm_password = isset($_POST['confirm_password_otp']) ? trim($_POST['confirm_password_otp']) : '';
    $reset_email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        $alert_message = "Please verify OTP first!";
        $alert_type = "danger";
    } elseif (empty($reset_email)) {
        $alert_message = "Session expired. Please start over.";
        $alert_type = "danger";
    } elseif (empty($new_password)) {
        $alert_message = "New password is required!";
        $alert_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $alert_message = "Password must be at least 6 characters long!";
        $alert_type = "danger";
    } elseif (empty($confirm_password)) {
        $alert_message = "Please confirm your new password!";
        $alert_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $alert_message = "Passwords do not match!";
        $alert_type = "danger";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE email = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        
        if (!$update_stmt) {
            $alert_message = "Database error: " . mysqli_error($conn);
            $alert_type = "danger";
        } else {
            mysqli_stmt_bind_param($update_stmt, 'ss', $hashed_password, $reset_email);
            if (mysqli_stmt_execute($update_stmt)) {
                // Clear session
                unset($_SESSION['reset_email']);
                unset($_SESSION['otp_verified']);

                // Ensure user is not blocked in future by OTP state
                if (isset($_SESSION['password_change_otp_sent'])) {
                    unset($_SESSION['password_change_otp_sent']);
                }

                $alert_message = "Password reset successful! You can now login with your new password.";
                $alert_type = "success";
            } else {
                $alert_message = "Failed to reset password. Please try again.";
                $alert_type = "danger";
            }
            mysqli_stmt_close($update_stmt);
        }
    }
}

$postOtpSteps = (isset($_SESSION['reset_email']) || isset($_SESSION['otp_verified']));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cafe Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/form-validation.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
          
            /* display: flex;
            align-items: center; */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

             .container{
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 60px auto 20px;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(27, 26, 26, 0.1);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #5c4033;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #888;
            font-size: 14px;
        }
        .form-group label {
            color: #5c4033;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #8b4513;
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }
        .btn-login {
            background-color: #8b4513;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 5px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        .btn-login:hover {
            background-color: #5c4033;
            color: white;
            text-decoration: none;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .login-footer a {
            color: #8b4513;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 12px;
        }
        .divider::before,
        .divider::after {
            content: '';
            display: inline-block;
            width: 40%;
            height: 1px;
            background-color: #ddd;
            vertical-align: middle;
            margin: 0 5px;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .form-control.is-valid {
            border-color: #28a745;
        }
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        .error-message.show {
            display: block;
        }
        .success-message {
            color: #28a745;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        .success-message.show {
            display: block;
        }
        /* Forgot Password Modal Styling */
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            border: none;
            border-radius: 10px 10px 0 0;
        }
        .modal-header .close {
            color: white;
            opacity: 0.7;
        }
        .modal-header .close:hover {
            opacity: 1;
        }
        .modal-title {
            color: white;
            font-weight: 700;
        }
        .btn-reset-password {
            background-color: #8b4513;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reset-password:hover {
            background-color: #5c4033;
            color: white;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .form-control.is-valid {
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1><i class="fas fa-user-circle"></i> Login</h1>
                <p>Welcome back to our cafe</p>
            </div>
            
            <?php if(!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <strong><?php echo ($alert_type === 'success') ? 'Success!' : 'Error!'; ?></strong> <?php echo $alert_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="post" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email_value); ?>">
                    <div class="error-message" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password">
                    <div class="error-message" id="passwordError"></div>
                </div>
                
                <div class="form-check" style="margin: 20px 0;">
                    <input class="form-check-input" type="checkbox" name="rememberMe" id="rememberMe" <?php echo $remember_checked; ?>>
                    <label class="form-check-label" for="rememberMe" style="color: #666; font-weight: 400;">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" name="login_submit" class="btn-login">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="regisration.php">Register here</a></p>
                <p style="margin-top: 10px;"><a href="forgot_password.php">Forgot password?</a></p>
            </div>
        </div>
    </div>
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel"><i class="fas fa-key"></i> Reset Password</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Enter Email -->
                    <div id="emailStep">
                        <p>Enter your email address and we'll send you an OTP to reset your password.</p>
                        <form id="sendOtpForm" method="post" action="">
                            <div class="form-group">
                                <label for="forgotEmail">Email Address</label>
                                <input type="email" class="form-control" id="forgotEmail" name="forgot_email" placeholder="Enter your email address" required>
                                <div class="error-message" id="forgotEmailError"></div>
                            </div>
                            <button type="submit" name="send_otp" class="btn btn-reset-password">Send OTP</button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

<?php ob_end_flush(); ?>
<?php include 'footer.php'; ?>