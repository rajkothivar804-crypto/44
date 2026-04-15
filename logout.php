<?php
session_start();

// Clear remember me cookie
if (isset($_COOKIE['user_email'])) {
    @setcookie('user_email', '', time() - 3600);
    @setcookie('user_email', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Don't redirect immediately - show a logout page instead
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Cafe4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .logout-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            padding: 60px 40px;
            max-width: 500px;
            text-align: center;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
            color: white;
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
        }

        .logout-title {
            color: #5c4033;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .logout-message {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .logout-notification {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            color: #155724;
            font-size: 14px;
        }

        .logout-notification i {
            margin-right: 10px;
            color: #28a745;
        }

        .countdown-timer {
            background: rgba(139, 69, 19, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            color: #5c4033;
        }

        .countdown-timer p {
            font-size: 14px;
            margin-bottom: 10px;
            color: #888;
        }

        .countdown-number {
            font-size: 48px;
            font-weight: 700;
            color: #8b4513;
            margin: 10px 0;
        }

        .btn-group-logout {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-home {
            flex: 1;
            background-color: #8b4513;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-home:hover {
            background-color: #5c4033;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
        }

        .btn-login {
            flex: 1;
            background-color: transparent;
            border: 2px solid #8b4513;
            color: #8b4513;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-login:hover {
            background-color: #8b4513;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .logout-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .logout-container {
                padding: 40px 25px;
            }

            .logout-title {
                font-size: 24px;
            }

            .btn-group-logout {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>

        <h1 class="logout-title">Logged Out Successfully!</h1>

        <div class="logout-notification">
            <i class="fas fa-check-circle"></i>
            <span>You have been successfully logged out from your account.</span>
        </div>

        <p class="logout-message">
            Thank you for visiting Cafe4! We look forward to seeing you again soon. Your session has been securely closed.
        </p>

        <div class="countdown-timer">
            <p>Redirecting in...</p>
            <div class="countdown-number" id="countdown">5</div>
        </div>

        <div class="btn-group-logout">
            <a href="index.php" class="btn-home">
                <i class="fas fa-home" style="margin-right: 8px;"></i> Go to Home
            </a>
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Login Again
            </a>
        </div>

        <div class="logout-footer">
            <p>© 2024 Cafe4 - All rights reserved | <a href="contact.php" style="color: #8b4513; text-decoration: none;">Contact Support</a></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Countdown timer
        let countdownValue = 5;
        const countdownElement = document.getElementById('countdown');

        const countdownInterval = setInterval(function() {
            countdownValue--;
            countdownElement.textContent = countdownValue;

            if (countdownValue <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'index.php';
            }
        }, 1000);

        // Allow user to cancel redirect by clicking buttons
        document.querySelector('.btn-home').addEventListener('click', function(e) {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });

        document.querySelector('.btn-login').addEventListener('click', function(e) {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });
    </script>
</body>
</html>
