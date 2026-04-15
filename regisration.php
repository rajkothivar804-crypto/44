<?php 
include 'hader.php';
$pageTitle = 'Register - Cafe Menu';
$pageStyles = <<<CSS
body {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 120px);
            padding: 40px 20px;
        }
        .register-container {
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(67, 46, 26, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #5c4033;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .register-header p {
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
        .btn-register {
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
        .btn-register:hover {
            background-color: #5c4033;
            color: white;
            text-decoration: none;
        }
        .password-requirements {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            font-size: 12px;
            color: #666;
        }
        .requirement {
            margin: 5px 0;
        }
        .requirement.valid {
            color: #28a745;
        }
        .requirement.invalid {
            color: #dc3545;
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
CSS;

include_once 'database/DB.php';
include_once 'otp_mailer.php';

$alert_message = '';
$alert_type = '';

if(isset($_POST['submit'])){
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

 
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if (!$check_stmt) {
            $alert_message = "Database error: " . mysqli_error($conn);
            $alert_type = "danger";
        } else {
            mysqli_stmt_bind_param($check_stmt, 's', $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $alert_message = "Email already registered!";
                $alert_type = "warning";
            } else {
                // Hash password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Use prepared statement to prevent SQL injection
                $insert_sql = "INSERT INTO users (fullname, email, phone, password, address) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                
                if (!$insert_stmt) {
                    $alert_message = "Database error: " . mysqli_error($conn);
                    $alert_type = "danger";
                } else {
                    mysqli_stmt_bind_param($insert_stmt, 'sssss', $fullname, $email, $phone, $hashed_password, $address);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        // Send registration confirmation email
                        $emailResult = sendRegistrationEmail($email, $fullname);
                        
                        $alert_message = "Registration successful! A confirmation email has been sent to your email address. You will be redirected shortly...";
                        $alert_type = "success";
                        // Redirect after 3 seconds
                        echo "<script>
                            setTimeout(function(){
                                window.location.href = 'index.php';
                            }, 3000);
                        </script>";
                    } else {
                        $alert_message = "Error: " . mysqli_error($conn);
                        $alert_type = "danger";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            }
            mysqli_stmt_close($check_stmt);
        }
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Cafe Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/form-validation.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 40px);
            padding: 40px 20px;
        }
        .register-container {
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(67, 46, 26, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #5c4033;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .register-header p {
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
        .btn-register {
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
        .btn-register:hover {
            background-color: #5c4033;
            color: white;
            text-decoration: none;
        }
   
        .password-requirements {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            font-size: 12px;
            color: #666;
        }
        .requirement {
            margin: 5px 0;
        }
        .requirement.valid {
            color: #28a745;
        }
        .requirement.invalid {
            color: #dc3545;
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
    </style>
</head>
<body>
    <div class="register-page">
        <div class="register-container">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> Register</h1>
                <p>Join us and start ordering delicious cafe items</p>
            </div>
            
            <?php if(!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <strong><?php echo ($alert_type === 'success' && strpos($alert_message, 'successful') === false) ? 'Success!' : (($alert_type === 'warning') ? 'Warning!' : (($alert_type === 'danger') ? 'Error!' : '')); ?></strong> <?php echo $alert_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <form id="registerForm" method="post" action="">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" class="form-control" id="fullname" placeholder="Enter your full name">
                    <div class="error-message" id="fullnameError"></div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email">
                    <div class="error-message" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" id="phone" placeholder="Enter your phone number">
                    <div class="error-message" id="phoneError"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Create a password">
                    <div class="error-message" id="passwordError"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" name="confirmPassword" class="form-control" id="confirmPassword" placeholder="Confirm your password">
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter your delivery address"></textarea>
                    <div class="error-message" id="addressError"></div>
                </div>
                
                <div class="form-check" style="margin: 20px 0;">
                    <input class="form-check-input" type="checkbox" id="agreeTerms">
                    <label class="form-check-label" for="agreeTerms" style="color: #666; font-weight: 400;">
                        I agree to the terms and conditions
                    </label>
                </div>
                
                <button type="submit" class="btn-register" name="submit">Create Account</button>
            </form>
            
        </div>
    </div>
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Real-time validation
            $('#fullname').on('blur', function() {
                validateFullname();
            });
            
            $('#email').on('blur', function() {
                validateEmail();
            });
            
            $('#phone').on('blur', function() {
                validatePhone();
            });
            
            $('#password').on('blur', function() {
                validatePassword();
            });
            
            $('#confirmPassword').on('blur', function() {
                validateConfirmPassword();
            });
            
            $('#address').on('blur', function() {
                validateAddress();
            });
            
            // Form submission validation
            $('#registerForm').on('submit', function(e) {
                let isValid = true;
                
                isValid &= validateFullname();
                isValid &= validateEmail();
                isValid &= validatePhone();
                isValid &= validatePassword();
                isValid &= validateConfirmPassword();
                isValid &= validateAddress();
                isValid &= validateTerms();
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please correct the errors in the form.');
                }
            });
            
            function validateFullname() {
                const fullname = $('#fullname').val().trim();
                if (fullname === '') {
                    showError('fullnameError', 'Full name is required.');
                    return false;
                } else if (fullname.length < 2) {
                    showError('fullnameError', 'Full name must be at least 2 characters.');
                    return false;
                } else {
                    hideError('fullnameError');
                    return true;
                }
            }
            
            function validateEmail() {
                const email = $('#email').val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email === '') {
                    showError('emailError', 'Email is required.');
                    return false;
                } else if (!emailRegex.test(email)) {
                    showError('emailError', 'Please enter a valid email address.');
                    return false;
                } else {
                    hideError('emailError');
                    return true;
                }
            }
            
            function validatePhone() {
                const phone = $('#phone').val().trim();
                const phoneRegex = /^\d{10,15}$/;
                if (phone === '') {
                    showError('phoneError', 'Phone number is required.');
                    return false;
                } else if (!phoneRegex.test(phone.replace(/\D/g, ''))) {
                    showError('phoneError', 'Please enter a valid phone number (10-15 digits).');
                    return false;
                } else {
                    hideError('phoneError');
                    return true;
                }
            }
            
            function validatePassword() {
                const password = $('#password').val();
                if (password === '') {
                    showError('passwordError', 'Password is required.');
                    return false;
                } else if (password.length < 6) {
                    showError('passwordError', 'Password must be at least 6 characters.');
                    return false;
                } else {
                    hideError('passwordError');
                    return true;
                }
            }
            
            function validateConfirmPassword() {
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();
                if (confirmPassword === '') {
                    showError('confirmPasswordError', 'Please confirm your password.');
                    return false;
                } else if (password !== confirmPassword) {
                    showError('confirmPasswordError', 'Passwords do not match.');
                    return false;
                } else {
                    hideError('confirmPasswordError');
                    return true;
                }
            }
            
            function validateAddress() {
                const address = $('#address').val().trim();
                if (address === '') {
                    showError('addressError', 'Address is required.');
                    return false;
                } else if (address.length < 10) {
                    showError('addressError', 'Address must be at least 10 characters.');
                    return false;
                } else {
                    hideError('addressError');
                    return true;
                }
            }
            
            function validateTerms() {
                if (!$('#agreeTerms').is(':checked')) {
                    alert('You must agree to the terms and conditions.');
                    return false;
                }
                return true;
            }
            
            function showError(errorId, message) {
                $('#' + errorId).text(message).addClass('show');
                $('#' + errorId.replace('Error', '')).addClass('is-invalid');
            }
            
            function hideError(errorId) {
                $('#' + errorId).removeClass('show');
                $('#' + errorId.replace('Error', '')).removeClass('is-invalid');
            }
        });
    </script>
<?php include 'footer.php'; ?>
</body>
</html>



