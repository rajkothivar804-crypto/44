<?php
session_start();


// Check if already logged in as admin
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

include '../database/DB.php';

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password!';
    } else {
        // Query database for admin user
        $stmt = $conn->prepare("SELECT id, fullname, email, password FROM users WHERE (email = ? OR fullname = ?) AND role = 'admin'");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            // Verify password (assuming passwords are hashed)
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin'] = $admin['fullname'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password!';
            }
        } else {
            $error = 'Invalid username or password!';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cafe4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 50%, #5c4033 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Left Side - Branding */
        .login-brand {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            min-height: 500px;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .brand-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .brand-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .brand-features {
            text-align: left;
            opacity: 0.8;
        }

        .brand-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            list-style: none;
        }

        .brand-features i {
            font-size: 18px;
        }

        /* Right Side - Form */
        .login-form {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
        }

        .login-form h2 {
            color: #5c4033;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .login-form p {
            color: #999;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group label {
            color: #5c4033;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #8b4513;
            font-size: 16px;
        }

        .form-control {
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: #8b4513;
            background: white;
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.15);
        }

        .form-check {
            margin-bottom: 20px;
        }

        .form-check-input {
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #8b4513;
            border-color: #8b4513;
        }

        .form-check-label {
            color: #666;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 0;
        }

        .btn-login {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            border: none;
            color: white;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 69, 19, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            border-left: 4px solid #dc3545;
            color: #721c24;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            border: none;
        }

        .alert-error i {
            margin-right: 8px;
            color: #dc3545;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 12px;
        }

        .demo-credentials {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #856404;
            font-size: 13px;
        }

        .demo-credentials strong {
            display: block;
            margin-bottom: 8px;
            color: #5c4033;
        }

        /* Form Validation Styling */
        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath fill='%23dc3545' d='M8 4L4 8M4 4l4 4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .form-control.is-valid {
            border-color: #28a745;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-valid:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #dc3545;
            animation: slideDownError 0.3s ease;
        }

        @keyframes slideDownError {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-brand {
                min-height: 300px;
                padding: 40px 30px;
            }

            .brand-title {
                font-size: 28px;
            }

            .login-form {
                padding: 40px 30px;
            }

            .login-form h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Brand Section -->
        <div class="login-brand">
            <div class="brand-icon">
                <i class="fas fa-mug-hot"></i>
            </div>
            <h1 class="brand-title">Cafe</h1>
            <p class="brand-subtitle">Admin Management System</p>
            <ul class="brand-features">
                <li><i class="fas fa-check-circle"></i> Dashboard Overview</li>
                <li><i class="fas fa-check-circle"></i> Manage Products</li>
                <li><i class="fas fa-check-circle"></i> Track Orders</li>
                <li><i class="fas fa-check-circle"></i> User Management</li>
            </ul>
        </div>

        <!-- Login Form Section -->
        <div class="login-form">
            <h2>Admin Login</h2>
            <p>Sign in to access your admin dashboard</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="demo-credentials">
                <strong>Admin Login:</strong>
                Use your admin account credentials registered in the system.
            </div>

            <form method="POST" id="loginForm" >
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password">
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
                </button>
            </form>

            <div class="form-footer">
                <p>© 2024 Cafe4 Admin Panel. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <script>
        $(document).ready(function() {
            // Form validation
            $("#loginForm").validate({
                rules: {
                    username: {
                        required: true,
                        minlength: 3,
                        pattern: /^[a-zA-Z0-9_-]+$/
                    },
                    password: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    username: {
                        required: "Username is required",
                        minlength: "Username must be at least 3 characters",
                        pattern: "Username can only contain letters, numbers, hyphens, and underscores"
                    },
                    password: {
                        required: "Password is required",
                        minlength: "Password must be at least 6 characters"
                    }
                },
                errorClass: "is-invalid",
                validClass: "is-valid",
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid').removeClass('is-valid');
                },
                unhighlight: function(element) {
                    $(element).addClass('is-valid').removeClass('is-invalid');
                },
                submitHandler: function(form) {
                    form.submit();
                }
            });

            // Add valid class styling
            $.validator.setDefaults({
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                }
            });
        });
    </script>
