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

// Create about_us table if it doesn't exist
$create_about_table = "CREATE TABLE IF NOT EXISTS about_us_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    section_type ENUM('mission', 'vision', 'history', 'values') DEFAULT 'mission',
    display_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_about_table);

// Create team members table if it doesn't exist
$create_team_table = "CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    bio TEXT,
    image_path VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_team_table);

// Create careers table if it doesn't exist
$create_careers_table = "CREATE TABLE IF NOT EXISTS careers_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    email VARCHAR(100) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_careers_table);

// Fetch about us content
$mission_sql = "SELECT title, content FROM about_us_content WHERE section_type = 'mission' LIMIT 1";
$mission_result = mysqli_query($conn, $mission_sql);
$mission_title = 'Our Mission';
$mission_content = 'We aim to deliver the finest coffee experience — ethically sourced beans, freshly roasted, and brewed with love.';

if ($mission_result && mysqli_num_rows($mission_result) > 0) {
    $mission_row = mysqli_fetch_assoc($mission_result);
    $mission_title = $mission_row['title'];
    $mission_content = $mission_row['content'];
}

// Fetch page header content
$header_sql = "SELECT title, content FROM about_us_content WHERE section_type = 'history' LIMIT 1";
$header_result = mysqli_query($conn, $header_sql);
$page_title = 'Our Story';
$page_subtitle = 'From a small roastery to your favorite neighborhood cafe — crafted with care since 2015.';

if ($header_result && mysqli_num_rows($header_result) > 0) {
    $header_row = mysqli_fetch_assoc($header_result);
    $page_title = $header_row['title'];
    $page_subtitle = $header_row['content'];
}

// Fetch team members
$team_sql = "SELECT id, name, position, bio, image_path FROM team_members WHERE is_active = 1 ORDER BY display_order ASC";
$team_result = mysqli_query($conn, $team_sql);
$team_members = [];

if ($team_result && mysqli_num_rows($team_result) > 0) {
    while ($row = mysqli_fetch_assoc($team_result)) {
        $team_members[] = $row;
    }
}

// Fetch careers info
$careers_sql = "SELECT title, description, email FROM careers_info LIMIT 1";
$careers_result = mysqli_query($conn, $careers_sql);
$careers_title = 'Want to work with us?';
$careers_desc = 'We\'re always looking for friendly baristas and pastry lovers. Send your CV via email.';
$careers_email = 'jobs@cafedelicious.com';

if ($careers_result && mysqli_num_rows($careers_result) > 0) {
    $careers_row = mysqli_fetch_assoc($careers_result);
    $careers_title = $careers_row['title'];
    $careers_desc = $careers_row['description'];
    $careers_email = $careers_row['email'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f9f7f4 0%, #faf8f5 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ===== HERO SECTION ===== */
        .hero {
            background: linear-gradient(135deg, #714840 0%, #5c4033 50%, #6d4c41 100%);
            color: white;
            padding: 120px 20px;
            margin-top: 70px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(62, 39, 35, 0.3);
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.8s ease-out;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .hero .lead {
            font-size: 22px;
            color: #ffd700;
            font-weight: 500;
            animation: fadeIn 1s ease-out 0.3s both;
            position: relative;
            z-index: 1;
        }

        .hero .btn {
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== SECTION CARDS ===== */
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 30px;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            border-left: 5px solid #d2691e;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(210, 105, 30, 0.03) 0%, transparent 100%);
            pointer-events: none;
        }

        .section-card:hover {
            box-shadow: 0 12px 30px rgba(248, 242, 242, 0.15);
            transform: translateY(-8px);
            border-left-color: #ffd700;
        }

        .section-card h3 {
            color: #3e2723;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0e6d2;
            position: relative;
            z-index: 1;
        }

        .section-card h3::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 28px;
            background: linear-gradient(180deg, #d2691e 0%, #ffd700 100%);
            margin-right: 12px;
            border-radius: 3px;
        }

        .section-card p {
            color: #444;
            line-height: 1.85;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        /* ===== TEAM SECTION ===== */
        .team-container {
            margin-top: 50px;
            margin-bottom: 50px;
        }

        .team-title {
            text-align: center;
            color: #3e2723;
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 50px;
            letter-spacing: 1px;
        }

        .team-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #d2691e 0%, #ffd700 100%);
            margin: 15px auto 0;
            border-radius: 2px;
        }

        /* ===== TEAM MEMBER CARD ===== */
        .team-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .team-card:hover {
            box-shadow: 0 15px 40px rgba(62, 39, 35, 0.2);
            transform: translateY(-12px);
        }

        .team-card-image {
            position: relative;
            overflow: hidden;
            height: 300px;
            background: linear-gradient(135deg, #f0e6d2 0%, #ede0c8 100%);
        }

        .team-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .team-card:hover .team-card-image img {
            transform: scale(1.1);
        }

        .team-card-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.2) 100%);
        }

        .team-card-content {
            padding: 25px;
            position: relative;
            z-index: 1;
        }

        .team-card-name {
            font-size: 22px;
            color: #3e2723;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .team-card-position {
            font-size: 14px;
            color: #d2691e;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .team-card-bio {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        /* ===== CAREERS SECTION ===== */
        .careers-card {
            background: linear-gradient(135deg, #3e2723 0%, #5c4033 100%);
            color: white;
            border-radius: 15px;
            padding: 50px 40px;
            text-align: center;
            margin: 50px 0;
            box-shadow: 0 8px 25px rgba(62, 39, 35, 0.2);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .careers-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .careers-card h4 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 20px;
            margin-top: 0;
            position: relative;
            z-index: 1;
        }

        .careers-card p {
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 25px;
            color: #f5f5dc;
            position: relative;
            z-index: 1;
        }

        /* ===== BUTTONS ===== */
        .btn-cta {
            background: #d2691e;
            color: white;
            border: none;
            padding: 12px 35px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-cta:hover {
            background: #ffd700;
            color: #3e2723;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.5);
        }

        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            padding: 10px 33px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-outline-custom:hover {
            background: white;
            color: #3e2723;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.5);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .hero {
                padding: 80px 20px;
                margin-top: 60px;
            }

            .hero h1 {
                font-size: 40px;
                letter-spacing: 1px;
            }

            .hero .lead {
                font-size: 18px;
            }

            .section-card,
            .team-card-content {
                padding: 25px;
            }

            .section-card h3,
            .team-card-name {
                font-size: 22px;
            }

            .team-title {
                font-size: 32px;
            }

            .careers-card {
                padding: 35px 25px;
            }

            .careers-card h4 {
                font-size: 24px;
            }

            .careers-card p {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 60px 15px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero .lead {
                font-size: 16px;
            }

            .section-card {
                padding: 20px;
            }

            .team-title {
                font-size: 28px;
            }

            .btn-cta,
            .btn-outline-custom {
                width: 100%;
                margin: 8px 0;
            }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="container text-center">
            <h1><?php echo sanitize_input($page_title); ?></h1>
            <p class="lead"><?php echo sanitize_input($page_subtitle); ?></p>
            <div class="mt-4">
                <a href="menu.php" class="btn btn-cta mr-2">
                    <i class="fas fa-book"></i> Browse Menu
                </a>
                <a href="contact.php" class="btn btn-outline-custom">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </section>

    <div class="container team-container">
        <!-- Mission Section -->
        <div class="row mb-5">
            <div class="col-md-8 mx-auto">
                <div class="section-card">
                    <h3><i class="fas fa-bullseye"></i> <?php echo sanitize_input($mission_title); ?></h3>
                    <p><?php echo nl2br(sanitize_input($mission_content)); ?></p>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <h2 class="team-title">Meet Our Team</h2>
        <div class="row">
            <?php if (!empty($team_members)): ?>
                <?php foreach ($team_members as $member): ?>
                    <div class="col-md-4 mb-4">
                        <div class="team-card">
                            <div class="team-card-image">
                                <?php 
                                    $image_src = 'https://via.placeholder.com/400x300?text=' . urlencode($member['name']);
                                    if (!empty($member['image_path'])) {
                                        if (file_exists($member['image_path'])) {
                                            $image_src = $member['image_path'];
                                        } elseif (file_exists('../' . $member['image_path'])) {
                                            $image_src = '../' . $member['image_path'];
                                        }
                                    }
                                ?>
                                <img src="<?php echo $image_src; ?>" alt="<?php echo sanitize_input($member['name']); ?>">
                            </div>
                            <div class="team-card-content">
                                <div class="team-card-name"><?php echo sanitize_input($member['name']); ?></div>
                                <div class="team-card-position"><?php echo sanitize_input($member['position']); ?></div>
                                <?php if ($member['bio']): ?>
                                    <div class="team-card-bio"><?php echo sanitize_input($member['bio']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> Team members coming soon...
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'footer.php'; ?>

</body>
</html>
