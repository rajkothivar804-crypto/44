<?php 
include 'hader.php';
include 'database/DB.php';

// Function to get home header settings
function getHomeHeaderSettings() {
    global $conn;
    $defaults = [
        'home_hero_title' => 'Welcome to Cafe Delicious',
        'home_hero_subtitle' => 'Your Premium Coffee Experience Awaits',
        'home_header_image' => 'home.jpeg',
        'home_hero_title_color' => '#ffffff',
        'home_hero_subtitle_color' => '#ffffff',
        'home_hero_title_size' => '60px'
    ];
    
    // Ensure cafe_settings table exists
    $create_table = "CREATE TABLE IF NOT EXISTS cafe_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
    
    $sql = "SELECT setting_key, setting_value FROM cafe_settings 
            WHERE setting_key IN ('home_hero_title', 'home_hero_subtitle', 'home_header_image', 'home_hero_title_color', 'home_hero_subtitle_color', 'home_hero_title_size')";
    $result = mysqli_query($conn, $sql);
    
    $settings = $defaults;
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['setting_value'])) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    
    // Ensure title_size has px suffix
    if (!empty($settings['home_hero_title_size']) && strpos($settings['home_hero_title_size'], 'px') === false) {
        $settings['home_hero_title_size'] = preg_replace('/[^0-9]/', '', $settings['home_hero_title_size']) . 'px';
    }
    
    return $settings;
}

$home_settings = getHomeHeaderSettings();

// Get cafe settings
$cafe_name = "Cafe Delicious";
$cafe_email = "info@cafe.com";
$cafe_phone = "+1 (555) 123-4567";
$hero_title = $home_settings['home_hero_title'];
$hero_subtitle = $home_settings['home_hero_subtitle'];
$about_title = "Craft Coffee at Its Finest";
$about_description = "Welcome to Cafe Delicious, where passion meets perfection. We've been crafting exceptional coffee experiences for our beloved customers. Our expert baristas use only the finest, freshly roasted beans sourced from premium suppliers around the world. Every cup tells a story of dedication, quality, and love for the craft. Whether you're a coffee connoisseur or just looking for your daily pick-me-up, we have something special waiting for you.";
$features_title = "Why Choose Us";
$commitment_title = "Our Commitment";
$cta_title = "Ready to Experience Excellence?";
$cta_subtitle = "Visit us today or order online for delivery to your doorstep";
$stat1_label = "Happy Customers";
$stat1_value = "100";
$stat2_label = "Years Experience";
$stat2_value = "8+";
$stat3_label = "Menu Items";
$stat3_value = "50";
$stat4_label = "Orders Served";
$stat4_value = "1000+";
$feature1_icon = "☕";
$feature1_title = "Premium Quality";
$feature1_desc = "Handpicked, freshly roasted beans from the finest coffee regions across the globe.";
$feature2_icon = "👨‍💼";
$feature2_title = "Expert Baristas";
$feature2_desc = "Our skilled baristas craft each cup with precision and passion for the perfect taste.";
$feature3_icon = "🎨";
$feature3_title = "Cozy Ambiance";
$feature3_desc = "Enjoy your coffee in our warm, welcoming environment perfect for work or relaxation.";
$feature4_icon = "⚡";
$feature4_title = "Fast Service";
$feature4_desc = "Quick and efficient service without compromising on quality or taste.";
$feature5_icon = "💰";
$feature5_title = "Affordable Prices";
$feature5_desc = "Premium coffee experience at prices that won't break your budget.";
$feature6_icon = "🌍";
$feature6_title = "Sustainable";
$feature6_desc = "Eco-friendly practices ensuring a better future for the planet.";
$commitment1_title = "100% Fresh Ingredients";
$commitment1_desc = "All our ingredients are sourced fresh daily. No shortcuts, no compromises.";
$commitment2_title = "Customer Satisfaction";
$commitment2_desc = "Your happiness is our priority. We guarantee satisfaction on every order.";
$commitment3_title = "Hygiene & Safety";
$commitment3_desc = "Strict hygiene standards maintained across all operations and facilities.";
$commitment4_title = "Custom Orders";
$commitment4_desc = "Create your perfect beverage with our customization options.";
$commitment5_title = "Quick Delivery";
$commitment5_desc = "Fast and reliable delivery service to get your coffee while it's hot.";
$commitment6_title = "Expert Support";
$commitment6_desc = "Our team is always ready to help with recommendations and special requests.";

// Get category icons
function getCategoryIcon($category) {
    $icons = [
        'Beverages' => '☕',
        'Coffee' => '☕',
        'Tea' => '🍵',
        'Pastries' => '🥐',
        'Desserts' => '🍰',
        'Snacks' => '🍪',
        'Sandwiches' => '🥪'
    ];
    return isset($icons[$category]) ? $icons[$category] : '✨';
}

// Check if cafe_settings table exists
$check_table = "SHOW TABLES LIKE 'cafe_settings'";
$table_result = mysqli_query($conn, $check_table);

if ($table_result && mysqli_num_rows($table_result) > 0) 
    $settings_sql = "SELECT setting_key, setting_value FROM cafe_settings";
    $settings_result = mysqli_query($conn, $settings_sql);
    
    if ($settings_result && mysqli_num_rows($settings_result) > 0) {
        while ($row = mysqli_fetch_assoc($settings_result)) {
            if ($row['setting_key'] === 'cafe_name') {
                $cafe_name = $row['setting_value'];
            } elseif ($row['setting_key'] === 'email') {
                $cafe_email = $row['setting_value'];
            } elseif ($row['setting_key'] === 'phone') {
                $cafe_phone = $row['setting_value'];
            } elseif ($row['setting_key'] === 'hero_title') {
                $hero_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'hero_subtitle') {
                $hero_subtitle = $row['setting_value'];
            } elseif ($row['setting_key'] === 'about_title') {
                $about_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'about_description') {
                $about_description = $row['setting_value'];
            } elseif ($row['setting_key'] === 'features_title') {
                $features_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment_title') {
                $commitment_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'cta_title') {
                $cta_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'cta_subtitle') {
                $cta_subtitle = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat1_label') {
                $stat1_label = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat1_value') {
                $stat1_value = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat2_label') {
                $stat2_label = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat2_value') {
                $stat2_value = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat3_label') {
                $stat3_label = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat3_value') {
                $stat3_value = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat4_label') {
                $stat4_label = $row['setting_value'];
            } elseif ($row['setting_key'] === 'stat4_value') {
                $stat4_value = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature1_icon') {
                $feature1_icon = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature1_title') {
                $feature1_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature1_desc') {
                $feature1_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature2_icon') {
                $feature2_icon = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature2_title') {
                $feature2_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature2_desc') {
                $feature2_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature3_icon') {
                $feature3_icon = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature3_title') {
                $feature3_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature3_desc') {
                $feature3_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature4_icon') {
                $feature4_icon = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature4_title') {
                $feature4_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature4_desc') {
                $feature4_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature5_icon') {
                $feature5_icon = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature5_title') {
                $feature5_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature5_desc') {
                $feature5_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature6_icon') {
                $feature6_icon = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature6_title') {
                $feature6_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'feature6_desc') {
                $feature6_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment1_title') {
                $commitment1_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment1_desc') {
                $commitment1_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment2_title') {
                $commitment2_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment2_desc') {
                $commitment2_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment3_title') {
                $commitment3_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment3_desc') {
                $commitment3_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment4_title') {
                $commitment4_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment4_desc') {
                $commitment4_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment5_title') {
                $commitment5_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment5_desc') {
                $commitment5_desc = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment6_title') {
                $commitment6_title = $row['setting_value'];
            } elseif ($row['setting_key'] === 'commitment6_desc') {
                $commitment6_desc = $row['setting_value'];
        }
    }
}

// Get total customers (count of distinct users who logged in)
$customers_sql = "SELECT COUNT(DISTINCT id) as count FROM users";
$customers_result = mysqli_query($conn, $customers_sql);
$total_customers = 0;
if ($customers_result && mysqli_num_rows($customers_result) > 0) {
    $row = mysqli_fetch_assoc($customers_result);
    $total_customers = intval($row['count']);
}

// Get total products
$products_sql = "SELECT COUNT(*) as count FROM products";
$products_result = mysqli_query($conn, $products_sql);
$total_products = 0;
if ($products_result && mysqli_num_rows($products_result) > 0) {
    $row = mysqli_fetch_assoc($products_result);
    $total_products = intval($row['count']);
}

// Get total orders
$orders_sql = "SELECT COUNT(*) as count FROM orders";
$orders_result = mysqli_query($conn, $orders_sql);
$total_orders = 0;
if ($orders_result && mysqli_num_rows($orders_result) > 0) {
    $row = mysqli_fetch_assoc($orders_result);
    $total_orders = intval($row['count']);
}

// Get top rated products (from reviews if available)
$top_products_sql = "SELECT p.id, p.product_name, p.category, p.description, p.price, p.stock, p.image_url, 
                           AVG(COALESCE(pr.rating, 4.5)) as avg_rating, COUNT(pr.id) as review_count
                    FROM products p 
                    LEFT JOIN product_reviews pr ON p.id = pr.product_id 
                    WHERE p.status = 'Available' AND p.stock > 0 
                    GROUP BY p.id 
                    ORDER BY avg_rating DESC, review_count DESC 
                    LIMIT 6";
$top_products_result = mysqli_query($conn, $top_products_sql);
$top_products = [];
if ($top_products_result && mysqli_num_rows($top_products_result) > 0) {
    while ($row = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cafe_name); ?> - Premium Cafe Experience</title>
    <link rel="icon" type="image/x-icon" href="logo.ico"> 
    <style>
    /* ========== HOME PAGE STYLING ========== */
    
    /* Hero Section */
    .hero-section {
        background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('<?php echo htmlspecialchars($home_settings['home_header_image']); ?>');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-repeat: no-repeat;
        height: 600px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        margin-top: -75px;
        padding-top: 75px;
        overflow: hidden;
    }

    .hero-content {
        animation: slideInDown 0.8s ease;
        z-index: 2;
    }

    .hero-content h1 {
        font-size: <?php echo htmlspecialchars($home_settings['home_hero_title_size']); ?>;
        font-weight: 800;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        letter-spacing: 2px;
        color: <?php echo htmlspecialchars($home_settings['home_hero_title_color']); ?>;
    }

    .hero-content p {
        font-size: 24px;
        margin-bottom: 30px;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
        font-weight: 300;
        color: <?php echo htmlspecialchars($home_settings['home_hero_subtitle_color']); ?>;
    }

    .hero-btn {
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: 2px solid white;
        padding: 15px 40px;
        font-size: 18px;
        font-weight: 600;
        border-radius: 50px;
        transition: all 0.3s ease;
        margin: 10px;
        display: inline-block;
        text-decoration: none;
    }

    .hero-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        color: white;
    }

    /* About Section */
    .about-section {
        padding: 80px 0;
        background: #f9f7f4;
    }

    .about-section h2 {
        font-size: 45px;
        color: #5c4033;
        margin-bottom: 40px;
        font-weight: 700;
        text-align: center;
        position: relative;
        padding-bottom: 20px;
    }

    .about-section h2::after {
        content: '';
        position: absolute;
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #8b4513, #d2691e);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
    }

    .about-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: center;
    }

    .about-text h3 {
        color: #8b4513;
        font-size: 28px;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .about-text p {
        color: #666;
        font-size: 16px;
        line-height: 1.8;
        margin-bottom: 15px;
    }

    .about-image {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .about-image img {
        width: 100%;
        height: auto;
        transition: transform 0.5s ease;
    }

    .about-image:hover img {
        transform: scale(1.05);
    }

    /* Features Section */
    .features-section {
        padding: 80px 0;
        background: white;
    }

    .features-section h2 {
        font-size: 45px;
        color: #5c4033;
        margin-bottom: 50px;
        font-weight: 700;
        text-align: center;
        position: relative;
        padding-bottom: 20px;
    }

    .features-section h2::after {
        content: '';
        position: absolute;
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #8b4513, #d2691e);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
    }

    .feature-card {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(139, 69, 19, 0.2);
        background: linear-gradient(135deg, #f5f5dc, #fffacd);
    }

    .feature-icon {
        font-size: 50px;
        margin-bottom: 20px;
    }

    .feature-card h3 {
        color: #8b4513;
        font-size: 22px;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .feature-card p {
        color: #666;
        font-size: 15px;
        line-height: 1.6;
    }

    /* Why Choose Us */
    .why-us-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
    }

    .why-us-section h2 {
        font-size: 45px;
        color: #5c4033;
        margin-bottom: 50px;
        font-weight: 700;
        text-align: center;
        position: relative;
        padding-bottom: 20px;
    }

    .why-us-section h2::after {
        content: '';
        position: absolute;
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #8b4513, #d2691e);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
    }

    .why-us-item {
        margin-bottom: 30px;
        padding-left: 50px;
        position: relative;
    }

    .why-us-item::before {
        content: '✓';
        position: absolute;
        left: 0;
        font-size: 28px;
        color: #8b4513;
        font-weight: bold;
    }

    .why-us-item h4 {
        color: #8b4513;
        font-size: 18px;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .why-us-item p {
        color: #666;
        font-size: 15px;
        line-height: 1.6;
    }

    /* Statistics Section */
    .stats-section {
        padding: 60px 0;
        background: #5c4033;
        color: white;
        text-align: center;
    }

    .stat-item {
        padding: 30px;
    }

    .stat-number {
        font-size: 45px;
        font-weight: 700;
        color: #ffd700;
        margin-bottom: 10px;
    }

    .stat-label {
        font-size: 18px;
        color: #f5f5dc;
    }

    /* Call to Action Section */
    .cta-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #3e2723 0%, #5c4033 100%);
        color: white;
        text-align: center;
    }

    .cta-section h2 {
        font-size: 45px;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .cta-section p {
        font-size: 18px;
        margin-bottom: 30px;
        color: #f5f5dc;
    }

    .cta-btn {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #5c4033;
        border: none;
        padding: 15px 45px;
        font-size: 18px;
        font-weight: 700;
        border-radius: 50px;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-block;
        text-decoration: none;
    }

    .cta-btn:hover {
        background: linear-gradient(135deg, #ffed4e, #ffd700);
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(255, 215, 0, 0.4);
        color: #5c4033;
    }

    /* Animations */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 40px;
        }

        .hero-content p {
            font-size: 18px;
        }

        .about-content {
            grid-template-columns: 1fr;
        }

        .feature-card {
            margin-bottom: 20px;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 35px;
        }
    }

    /* Product Cards */
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(139, 69, 19, 0.2);
    }

    .product-card a:hover {
        text-decoration: none;
    }
</style>

</head>
<body>
    <br>
<br>
<br>
<!-- Hero Section -->
<div class="hero-section">
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($hero_title); ?></h1>
        <p><?php echo htmlspecialchars($hero_subtitle); ?></p>
        <a href="menu.php" class="hero-btn">Explore Menu</a>
        <a href="<?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'order.php' : 'login.php'; ?>" class="hero-btn">Order Now</a>
    </div>
</div>

<!-- About Section -->
<section class="about-section">
    <div class="container">
        <h2>About <?php echo htmlspecialchars($cafe_name); ?></h2>
        <div class="about-content">
            <div class="about-text">
                <h3><?php echo htmlspecialchars($about_title); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($about_description)); ?></p>
                <p><strong>📧 Email:</strong> <?php echo htmlspecialchars($cafe_email); ?></p>
                <p><strong>📱 Phone:</strong> <?php echo htmlspecialchars($cafe_phone); ?></p>
                <a href="menu.php" class="hero-btn" style="margin-top: 20px;">View Our Menu</a>
            </div>
            <div class="about-image">
                <img src="H.jpg" alt="<?php echo htmlspecialchars($cafe_name); ?>">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2><?php echo htmlspecialchars($features_title); ?></h2>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><?php echo htmlspecialchars($feature1_icon); ?></div>
                    <h3><?php echo htmlspecialchars($feature1_title); ?></h3>
                    <p><?php echo htmlspecialchars($feature1_desc); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><?php echo htmlspecialchars($feature2_icon); ?></div>
                    <h3><?php echo htmlspecialchars($feature2_title); ?></h3>
                    <p><?php echo htmlspecialchars($feature2_desc); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><?php echo htmlspecialchars($feature3_icon); ?></div>
                    <h3><?php echo htmlspecialchars($feature3_title); ?></h3>
                    <p><?php echo htmlspecialchars($feature3_desc); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><?php echo htmlspecialchars($feature4_icon); ?></div>
                    <h3><?php echo htmlspecialchars($feature4_title); ?></h3>
                    <p><?php echo htmlspecialchars($feature4_desc); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><?php echo htmlspecialchars($feature5_icon); ?></div>
                    <h3><?php echo htmlspecialchars($feature5_title); ?></h3>
                    <p><?php echo htmlspecialchars($feature5_desc); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><?php echo htmlspecialchars($feature6_icon); ?></div>
                    <h3><?php echo htmlspecialchars($feature6_title); ?></h3>
                    <p><?php echo htmlspecialchars($feature6_desc); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="products-section" style="padding: 80px 0; background: #f9f7f4;">
    <div class="container">
        <h2 style="font-size: 45px; color: #5c4033; margin-bottom: 50px; font-weight: 700; text-align: center; position: relative; padding-bottom: 20px;">
            Our Popular Items
            <span style="content: ''; position: absolute; width: 80px; height: 4px; background: linear-gradient(135deg, #8b4513, #d2691e); bottom: 0; left: 50%; transform: translateX(-50%); border-radius: 2px;"></span>
        </h2>
        
        <?php if (!empty($top_products)): ?>
        <div class="row">
            <?php foreach ($top_products as $product): ?>
            <div class="col-md-4 col-sm-6" style="margin-bottom: 30px;">
                <div class="product-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; height: 100%;">
                    <div style="position: relative; height: 200px; background: linear-gradient(135deg, #f5f5dc, #fffacd); display: flex; align-items: center; justify-content: center;">
                        <?php if (!empty($product['image_url']) && file_exists(__DIR__ . '/' . $product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 50px;"><?php echo getCategoryIcon($product['category']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <a href="add_to_wishlist.php?product_id=<?php echo intval($product['id']); ?>" 
                           style="position: absolute; top: 10px; right: 10px; background: rgba(255, 255, 255, 0.9); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" 
                           title="Add to Wishlist">
                            ❤️
                        </a>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 20px;">
                        <h4 style="color: #5c4033; font-size: 18px; font-weight: 700; margin-bottom: 8px;"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px; line-height: 1.4;"><?php echo htmlspecialchars(substr($product['description'], 0, 60) . '...'); ?></p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #d2691e; font-size: 20px; font-weight: 700;">₹<?php echo number_format(floatval($product['price']), 2); ?></span>
                            <div style="display: flex; gap: 5px;">
                                <?php 
                                $rating = round(floatval($product['avg_rating']), 1);
                                for ($i = 1; $i <= 5; $i++): 
                                    if ($i <= $rating) {
                                        echo '<span style="color: #ffd700;">⭐</span>';
                                    } else {
                                        echo '<span style="color: #ddd;">☆</span>';
                                    }
                                endfor;
                                ?>
                                <span style="color: #666; font-size: 12px; margin-left: 5px;">(<?php echo intval($product['review_count']); ?>)</span>
                            </div>
                        </div>
                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <a href="add_to_cart.php?id=<?php echo intval($product['id']); ?>&item=<?php echo urlencode($product['product_name']); ?>&price=<?php echo floatval($product['price']); ?>" 
                               style="flex: 1; background: linear-gradient(135deg, #8b4513, #d2691e); color: white; text-decoration: none; padding: 10px; border-radius: 6px; text-align: center; font-weight: 600; transition: all 0.3s ease;">
                                🛒 Add to Cart
                            </a>
                            <a href="menu.php" 
                               style="background: #f0f0f0; color: #5c4033; text-decoration: none; padding: 10px 15px; border-radius: 6px; text-align: center; font-weight: 600; transition: all 0.3s ease;">
                                View Menu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="menu.php" style="background: linear-gradient(135deg, #8b4513, #d2691e); color: white; text-decoration: none; padding: 15px 30px; border-radius: 50px; font-weight: 600; transition: all 0.3s ease; display: inline-block;">
                View Full Menu
            </a>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 64px; margin-bottom: 20px;">☕</div>
            <h3 style="color: #5c4033; margin-bottom: 15px;">Our Menu is Coming Soon!</h3>
            <p style="color: #666; margin-bottom: 25px;">We're preparing some amazing coffee and treats for you.</p>
            <a href="menu.php" style="background: linear-gradient(135deg, #8b4513, #d2691e); color: white; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: 600;">
                Check Our Menu
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us Detailed -->
<section class="why-us-section">
    <div class="container">
        <h2><?php echo htmlspecialchars($commitment_title); ?></h2>
        <div class="row">
            <div class="col-md-6">
                <div class="why-us-item">
                    <h4><?php echo htmlspecialchars($commitment1_title); ?></h4>
                    <p><?php echo htmlspecialchars($commitment1_desc); ?></p>
                </div>
                <div class="why-us-item">
                    <h4><?php echo htmlspecialchars($commitment2_title); ?></h4>
                    <p><?php echo htmlspecialchars($commitment2_desc); ?></p>
                </div>
                <div class="why-us-item">
                    <h4><?php echo htmlspecialchars($commitment3_title); ?></h4>
                    <p><?php echo htmlspecialchars($commitment3_desc); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="why-us-item">
                    <h4><?php echo htmlspecialchars($commitment4_title); ?></h4>
                    <p><?php echo htmlspecialchars($commitment4_desc); ?></p>
                </div>
                <div class="why-us-item">
                    <h4><?php echo htmlspecialchars($commitment5_title); ?></h4>
                    <p><?php echo htmlspecialchars($commitment5_desc); ?></p>
                </div>
                <div class="why-us-item">
                    <h4><?php echo htmlspecialchars($commitment6_title); ?></h4>
                    <p><?php echo htmlspecialchars($commitment6_desc); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($stat1_value); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($stat1_label); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($stat2_value); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($stat2_label); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($stat3_value); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($stat3_label); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($stat4_value); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($stat4_label); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section">
    <div class="container">
        <h2><?php echo htmlspecialchars($cta_title); ?></h2>
        <p><?php echo htmlspecialchars($cta_subtitle); ?></p>
        <a href="<?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'order.php' : 'login.php'; ?>" class="cta-btn">Order Coffee Now</a>
    </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>
