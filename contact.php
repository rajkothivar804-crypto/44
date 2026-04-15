<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

include 'hader.php';
include 'database/DB.php';

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Create settings table if it doesn't exist - with proper error handling
$table_exists = false;
$check_table = "SHOW TABLES LIKE 'cafe_settings'";
$check_result = mysqli_query($conn, $check_table);

if ($check_result && mysqli_num_rows($check_result) > 0) {
    // Table exists, verify it has the correct columns
    $check_columns = "SHOW COLUMNS FROM cafe_settings LIKE 'setting_key'";
    $col_result = mysqli_query($conn, $check_columns);
    
    if ($col_result && mysqli_num_rows($col_result) > 0) {
        $table_exists = true;
    } else {
        // Table exists but missing columns - drop and recreate
        mysqli_query($conn, "DROP TABLE IF EXISTS cafe_settings");
    }
}

// Create table if it doesn't exist or was dropped
if (!$table_exists) {
    $create_settings_table = "CREATE TABLE cafe_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $result = mysqli_query($conn, $create_settings_table);
    if (!$result) {
        error_log("Error creating cafe_settings table: " . mysqli_error($conn));
    }
}

// Create contacts table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New', 'Read', 'Replied') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at DESC)
)";
mysqli_query($conn, $create_table);

// Fetch settings from database
$settings = [];
$count_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'cafe_settings' AND TABLE_SCHEMA = DATABASE()";
$count_check = mysqli_query($conn, $count_sql);
$table_check = mysqli_fetch_assoc($count_check);

if ($table_check['cnt'] > 0) {
    $settings_sql = "SELECT setting_key, setting_value FROM cafe_settings";
    $settings_result = mysqli_query($conn, $settings_sql);
    
    if ($settings_result) {
        while ($row = mysqli_fetch_assoc($settings_result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Default values if not set in database
$cafe_address = $settings['address'] ?? '123 Coffee Street, Downtown District<br>City, State 12345';
$cafe_phone = $settings['phone'] ?? '+1 (555) 123-4567';
$cafe_email = $settings['email'] ?? 'info@cafe4.com';
$cafe_website = $settings['website'] ?? 'www.cafe4.com';
$cafe_hours = $settings['hours'] ?? json_encode([
    ['day' => 'Mon - Fri', 'hours' => '7:00 AM - 8:00 PM'],
    ['day' => 'Saturday', 'hours' => '8:00 AM - 9:00 PM'],
    ['day' => 'Sunday', 'hours' => '9:00 AM - 7:00 PM']
]);

// Parse hours if it's JSON
$hours_array = json_decode($cafe_hours, true) ?? [
    ['day' => 'Mon - Fri', 'hours' => '7:00 AM - 8:00 PM'],
    ['day' => 'Saturday', 'hours' => '8:00 AM - 9:00 PM'],
    ['day' => 'Sunday', 'hours' => '9:00 AM - 7:00 PM']
];

$message = '';
$alert_type = '';
$user_id = intval($_SESSION['user_id']);

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message_text = sanitize_input($_POST['message'] ?? '');

    // Validation
    if (empty($name)) {
        $message = '❌ Please enter your name.';
        $alert_type = 'danger';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '❌ Please enter a valid email address.';
        $alert_type = 'danger';
    } elseif (empty($subject)) {
        $message = '❌ Please select a subject.';
        $alert_type = 'danger';
    } elseif (empty($message_text) || strlen($message_text) < 10) {
        $message = '❌ Message must be at least 10 characters long.';
        $alert_type = 'danger';
    } else {
        // Insert message into database
        $insert_sql = "INSERT INTO contact_messages (user_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if (!$stmt) {
            $message = '❌ Error: ' . mysqli_error($conn);
            $alert_type = 'danger';
        } else {
            mysqli_stmt_bind_param($stmt, 'isssss', $user_id, $name, $email, $phone, $subject, $message_text);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = '✅ Thank you! Your message has been sent. We\'ll get back to you soon!';
                $alert_type = 'success';
            } else {
                $message = '❌ Error submitting message: ' . mysqli_stmt_error($stmt);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get total messages count
$count_sql = "SELECT COUNT(*) as total FROM contact_messages WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_sql);
$message_count = 0;

if ($count_result && mysqli_num_rows($count_result) > 0) {
    $count_data = mysqli_fetch_assoc($count_result);
    $message_count = $count_data['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Contact Us - Cafe</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link rel="stylesheet" href="css/form-validation.css">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			background: #f9f9f9;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}

		/* Hero Section */
		.contact-hero {
			background: linear-gradient(135deg, #3e2723 0%, #5c4033 50%, #6d4c41 100%);
			padding: 80px 0;
			margin-top: 70px;
			color: white;
			text-align: center;
			position: relative;
			overflow: hidden;
		}

		.contact-hero::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,50 Q300,0 600,50 T1200,50 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.05)"></path></svg>');
			background-size: cover;
			opacity: 0.3;
		}

		.contact-hero-content {
			position: relative;
			z-index: 1;
			animation: fadeInDown 0.8s ease;
		}

		.contact-hero h1 {
			font-size: 56px;
			font-weight: 800;
			margin-bottom: 15px;
			text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
		}

		.contact-hero p {
			font-size: 20px;
			margin-bottom: 30px;
			opacity: 0.95;
		}

		.contact-hero-buttons {
			display: flex;
			gap: 15px;
			justify-content: center;
			flex-wrap: wrap;
		}

		.btn-hero {
			padding: 12px 30px;
			border-radius: 50px;
			font-weight: 600;
			transition: all 0.3s ease;
			text-decoration: none;
			border: none;
			cursor: pointer;
		}

		.btn-hero-primary {
			background-color: white;
			color: #3e2723;
		}

		.btn-hero-primary:hover {
			background-color: #ffd700;
			transform: translateY(-3px);
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
		}

		.btn-hero-secondary {
			background-color: transparent;
			color: white;
			border: 2px solid white;
		}

		.btn-hero-secondary:hover {
			background-color: rgba(255, 255, 255, 0.1);
			transform: translateY(-3px);
		}

		/* Main Container */
		.contact-main {
			padding: 60px 0;
			margin-bottom: 60px;
		}

		/* Contact Card */
		.contact-card {
			border-radius: 15px;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
			padding: 40px;
			background: white;
			transition: all 0.3s ease;
			border: none;
			height: 100%;
		}

		.contact-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
		}

		.contact-card h4 {
			color: #3e2723;
			font-weight: 700;
			font-size: 24px;
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.contact-card h4 i {
			font-size: 28px;
			background: linear-gradient(135deg, #3e2723, #d2691e);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}

		.contact-info-item {
			margin-bottom: 20px;
			padding-bottom: 20px;
			border-bottom: 1px solid #f0f0f0;
		}

		.contact-info-item:last-child {
			border-bottom: none;
			margin-bottom: 0;
			padding-bottom: 0;
		}

		.contact-info-item strong {
			color: #5c4033;
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 5px;
		}

		.contact-info-item strong i {
			color: #d2691e;
			font-size: 16px;
			width: 20px;
		}

		.contact-info-item a {
			color: #d2691e;
			text-decoration: none;
			transition: all 0.3s ease;
		}

		.contact-info-item a:hover {
			color: #5c4033;
			text-decoration: underline;
		}

		/* Hours Table */
		.hours-table {
			margin-top: 25px;
		}

		.hours-table table {
			margin-bottom: 0;
		}

		.hours-table th {
			background: linear-gradient(135deg, rgba(62, 39, 35, 0.1), rgba(210, 105, 30, 0.1));
			color: #5d3112;
			font-weight: 700;
			border: none;
			padding: 12px;
		}

		.hours-table td {
			padding: 12px;
			border-color: #f0f0f0;
			color: #666;
		}

		.hours-table tr:hover {
			background-color: rgba(62, 39, 35, 0.05);
		}

		/* Form Styling */
		.contact-form h4 {
			margin-bottom: 30px;
		}

		.form-group label {
			color: #5c4033;
			font-weight: 600;
			margin-bottom: 10px;
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
			border-color: #3e2723;
			background: white;
			box-shadow: 0 0 0 0.2rem rgba(62, 39, 35, 0.15);
		}

		.form-control::placeholder {
			color: #999;
		}

		.form-control-lg {
			border-radius: 8px;
		}

		/* Submit Button */
		.btn-submit {
			background: linear-gradient(135deg, #3e2723 0%, #5c4033 100%);
			border: none;
			color: white;
			padding: 14px 40px;
			border-radius: 8px;
			font-weight: 600;
			font-size: 16px;
			transition: all 0.3s ease;
			width: 100%;
			cursor: pointer;
		}

		.btn-submit:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 25px rgba(62, 39, 35, 0.3);
			background: linear-gradient(135deg, #5c4033 0%, #6d4c41 100%);
			color: white;
			text-decoration: none;
		}

		.btn-submit:active {
			transform: translateY(0);
		}

		/* Alert */
		.alert-success {
			background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
			border-left: 4px solid #28a745;
			border: none;
			color: #155724;
			border-radius: 8px;
			animation: slideInUp 0.5s ease;
		}

		.alert-success i {
			margin-right: 10px;
			color: #28a745;
		}

		.alert-danger {
			background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
			border-left: 4px solid #dc3545;
			border: none;
			color: #721c24;
			border-radius: 8px;
			animation: slideInUp 0.5s ease;
		}

		/* Side Links */
		.side-links {
			margin-top: 30px;
			padding-top: 30px;
			border-top: 1px solid #f0f0f0;
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
		}

		.side-links a {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			color: #d2691e;
			text-decoration: none;
			padding: 8px 15px;
			border-radius: 20px;
			background: rgba(62, 39, 35, 0.05);
			transition: all 0.3s ease;
			font-weight: 500;
			font-size: 14px;
		}

		.side-links a:hover {
			background: rgba(62, 39, 35, 0.15);
			color: #5c4033;
			text-decoration: none;
		}

		/* Animations */
		@keyframes fadeInDown {
			from {
				opacity: 0;
				transform: translateY(-30px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		@keyframes slideInUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		/* Responsive Design */
		@media (max-width: 768px) {
			.contact-hero h1 {
				font-size: 36px;
			}

			.contact-card {
				padding: 30px;
				margin-bottom: 20px;
			}

			.contact-hero-buttons {
				flex-direction: column;
			}

			.btn-hero {
				width: 100%;
			}
		}
	</style>
</head>
<body>
	<section class="contact-hero">
		<div class="container">
			<div class="contact-hero-content">
				<h1><i class="fas fa-envelope"></i> Get in Touch</h1>
				<p>Questions, catering, or feedback — we'd love to hear from you!</p>
				<div class="contact-hero-buttons">
					<a href="menu.php" class="btn-hero btn-hero-primary">
						<i class="fas fa-utensils"></i> View Menu
					</a>
					<a href="order.php" class="btn-hero btn-hero-secondary">
						<i class="fas fa-box"></i> My Orders
					</a>
				</div>
			</div>
		</div>
	</section>

	<div class="container contact-main">
		<div class="row">
			<!-- Contact Information -->
			<div class="col-lg-6 mb-4">
				<div class="contact-card">
					<h4><i class="fas fa-info-circle"></i> Contact Information</h4>
					<p style="color: #666; margin-bottom: 25px;">We're open daily — stop by or reach out using the details below.</p>
					
					<div class="contact-info-item">
						<strong><i class="fas fa-map-marker-alt"></i> Address</strong>
						<p style="margin: 0; color: #666;"><?php echo $cafe_address; ?></p>
					</div>

					<div class="contact-info-item">
						<strong><i class="fas fa-phone"></i> Phone</strong>
						<a href="tel:<?php echo str_replace([' ', '-', '(', ')'], '', $cafe_phone); ?>"><?php echo sanitize_input($cafe_phone); ?></a>
					</div>

					<div class="contact-info-item">
						<strong><i class="fas fa-envelope"></i> Email</strong>
						<a href="mailto:<?php echo sanitize_input($cafe_email); ?>"><?php echo sanitize_input($cafe_email); ?></a>
					</div>

					<div class="contact-info-item">
						<strong><i class="fas fa-globe"></i> Website</strong>
						<a href="<?php echo sanitize_input($cafe_website); ?>"><?php echo sanitize_input($cafe_website); ?></a>
					</div>

					<!-- Hours Table -->
					<div class="hours-table">
						<h5 style="color: #8b4513; font-weight: 700; margin-bottom: 15px;">
							<i class="fas fa-clock"></i> Opening Hours
						</h5>
						<table class="table table-sm">
							<thead>
								<tr>
									<th style="width: 50%;">Day</th>
									<th style="width: 50%;">Hours</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($hours_array as $hour): ?>
									<tr>
										<td><strong><?php echo sanitize_input($hour['day'] ?? 'N/A'); ?></strong></td>
										<td><?php echo sanitize_input($hour['hours'] ?? 'N/A'); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<!-- Side Links -->
					<div class="side-links">
						<a href="about_us.php">
							<i class="fas fa-info-circle"></i> About Us
						</a>
						<a href="review.php">
							<i class="fas fa-comments"></i> Give Feedback
						</a>
					</div>
				</div>
			</div>

			<!-- Contact Form -->
			<div class="col-lg-6">
				<div class="contact-card contact-form">
					<h4><i class="fas fa-paper-plane"></i> Send Us a Message</h4>
					
					<?php if ($message): ?>
						<div class="alert alert-<?php echo $alert_type; ?>" role="alert">
							<?php echo $message; ?>
						</div>
					<?php endif; ?>
					
					<form method="POST" action="contact.php" id="contactForm">
						<div class="form-group">
							<label for="name">
								<i class="fas fa-user"></i> Full Name *
							</label>
							<input id="name" type="text" name="name" class="form-control form-control-lg" placeholder="Enter your full name" required>
						</div>

						<div class="form-group">
							<label for="email">
								<i class="fas fa-envelope"></i> Email Address *
							</label>
							<input id="email" type="email" name="email" class="form-control form-control-lg" placeholder="your@email.com" required>
						</div>

						<div class="form-group">
							<label for="phone">
								<i class="fas fa-phone"></i> Phone (Optional)
							</label>
							<input id="phone" type="tel" name="phone" class="form-control form-control-lg" placeholder="Your phone number">
						</div>

						<div class="form-group">
							<label for="subject">
								<i class="fas fa-heading"></i> Subject *
							</label>
							<select id="subject" name="subject" class="form-control form-control-lg" required>
								<option value="">Select a subject...</option>
								<option value="General Inquiry">General Inquiry</option>
								<option value="Catering Request">Catering Request</option>
								<option value="Feedback">Feedback</option>
								<option value="Reservation">Reservation</option>
								<option value="Other">Other</option>
							</select>
						</div>

						<div class="form-group">
							<label for="message">
								<i class="fas fa-message"></i> Message *
							</label>
							<textarea id="message" name="message" rows="6" class="form-control form-control-lg" placeholder="Tell us more about your inquiry..." required></textarea>
							<small class="text-muted">Minimum 10 characters</small>
						</div>

						<button type="submit" name="submit_contact" class="btn-submit">
							<i class="fas fa-paper-plane"></i> Send Message
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

	<script>
		// Auto-hide alerts after 5 seconds
		document.addEventListener('DOMContentLoaded', function() {
			const alerts = document.querySelectorAll('.alert');
			alerts.forEach(alert => {
				setTimeout(() => {
					alert.style.display = 'none';
				}, 5000);
			});
		});
	</script>

<?php include 'footer.php'; ?>

</body>
</html>

