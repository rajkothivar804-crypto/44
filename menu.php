<?php 
include 'hader.php';
include 'database/DB.php';

// Create product_reviews table if it doesn't exist
$create_reviews_table = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    customer_name VARCHAR(100),
    rating INT CHECK(rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_reviews_table);

// Function to get menu header settings
function getMenuHeaderSettings() {
    global $conn;
    $defaults = [
        'menu_header_title' => '☕ Our Menu',
        'menu_header_subtitle' => 'Discover Our Delicious Selection of Beverages & Delights',
        'menu_header_image' => 'images/menu-header.jpg',
        'menu_header_title_color' => '#ffffff',
        'menu_header_title_size' => '50px',
        'menu_header_subtitle_color' => '#f5f5dc'
    ];
    
    // Ensure cafe_settings table exists
    $create_table = "CREATE TABLE IF NOT EXISTS cafe_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
    
    $sql = "SELECT setting_key, setting_value FROM cafe_settings 
            WHERE setting_key IN ('menu_header_title', 'menu_header_subtitle', 'menu_header_image', 
                                 'menu_header_title_color', 'menu_header_title_size', 'menu_header_subtitle_color')";
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
    if (!empty($settings['menu_header_title_size']) && strpos($settings['menu_header_title_size'], 'px') === false) {
        $settings['menu_header_title_size'] = preg_replace('/[^0-9]/', '', $settings['menu_header_title_size']) . 'px';
    }
    
    return $settings;
}

$menu_settings = getMenuHeaderSettings();

// Function to get currency symbol
function getCurrencySymbol() {
    global $conn;
    $sql = "SELECT setting_value FROM cafe_settings WHERE setting_key = 'website'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $currency = $row['setting_value'];
        if (strpos($currency, 'INR') !== false) {
            return '₹';
        } elseif (strpos($currency, 'EUR') !== false) {
            return '€';
        } elseif (strpos($currency, 'GBP') !== false) {
            return '£';
        } else {
            return '₹';
        }
    }
    return '₹'; // Default to INR
}

// Function to get star display
function get_star_display($score) {
    $stars = '';
    for ($i = 0; $i < $score; $i++) {
        $stars .= '⭐';
    }
    return $stars;
}

// Function to get product reviews
function get_product_reviews($product_id, $limit = 3) {
    global $conn;
    $sql = "SELECT pr.rating, pr.review_text, pr.customer_name, pr.created_at 
            FROM product_reviews pr 
            WHERE pr.product_id = ? 
            ORDER BY pr.created_at DESC 
            LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $product_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $reviews = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $reviews[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $reviews;
}

// Function to get average rating for a product
function get_product_rating($product_id) {
    global $conn;
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
            FROM product_reviews 
            WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return [
            'avg_rating' => round($row['avg_rating'] ?? 0, 1),
            'total_reviews' => $row['total_reviews']
        ];
    }
    mysqli_stmt_close($stmt);
    return ['avg_rating' => 0, 'total_reviews' => 0];
}

// Get all products
$all_products_sql = "SELECT id, product_name, category, description, price, stock, image_url FROM products WHERE status = 'Available' AND stock > 0 ORDER BY category ASC, product_name ASC";
$all_products_result = mysqli_query($conn, $all_products_sql);

$all_products = array();
$categories = array();

if ($all_products_result && mysqli_num_rows($all_products_result) > 0) {
    while ($row = mysqli_fetch_assoc($all_products_result)) {
        $all_products[] = $row;
        if (!in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
    }
}

// Handle search filter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

$filtered_products = $all_products;

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        return stripos($product['product_name'], $search_query) !== false || 
               stripos($product['description'], $search_query) !== false;
    });
}

if (!empty($selected_category)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($selected_category) {
        return $product['category'] === $selected_category;
    });
}

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

// Group filtered products by category
$grouped_products = array();
foreach ($filtered_products as $product) {
    if (!isset($grouped_products[$product['category']])) {
        $grouped_products[$product['category']] = array();
    }
    $grouped_products[$product['category']][] = $product;
}

// Get the first category for scroll navigation when searching
$first_category_id = '';
if (!empty($search_query) && count($grouped_products) > 0) {
    $first_category = array_key_first($grouped_products);
    $first_category_id = 'category-' . strtolower(str_replace(' ', '-', $first_category));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Cafe Management</title>

    <style>
    html {
        scroll-behavior: smooth;
    }
    .menu-header {
        background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo htmlspecialchars($menu_settings['menu_header_image']); ?>');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: scroll;
        padding: 100px 0;
        text-align: center;
        color: white;
        margin-top: -75px;
        padding-top: 150px;
        min-height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .menu-header h1 {
        font-size: <?php echo htmlspecialchars($menu_settings['menu_header_title_size']); ?>;
        font-weight: 800;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        color: <?php echo htmlspecialchars($menu_settings['menu_header_title_color']); ?>;
    }

    .menu-header p {
        font-size: 18px;
        color: <?php echo htmlspecialchars($menu_settings['menu_header_subtitle_color']); ?>;
    }

    /* Menu Navigation */
    .menu-nav {
        padding: 40px 0;
        background: white;
        text-align: center;
        border-bottom: 2px solid #f0f0f0;
        overflow-x: auto;
    }

    .menu-nav .btn {
        background: #f9f7f4;
        color: #5c4033;
        border: 2px solid #d2691e;
        padding: 10px 25px;
        margin: 5px;
        border-radius: 25px;
        transition: all 0.3s ease;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .menu-nav .btn:hover,
    .menu-nav .btn.active {
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border-color: #8b4513;
    }

    /* Menu Content */
    .menu-content {
        padding: 60px 0;
        background: #fff;
    }

    .menu-category {
        margin-bottom: 60px;
        scroll-margin-top: 100px;
    }

    .menu-category-title {
        font-size: 35px;
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 40px;
        position: relative;
        padding-bottom: 15px;
    }

    .menu-category-title::after {
        content: '';
        position: absolute;
        width: 60px;
        height: 4px;
        background: linear-gradient(135deg, #8b4513, #d2691e);
        bottom: 0;
        left: 0;
        border-radius: 2px;
    }

    /* Menu Item Card */
    .menu-item-card {
        background: #f9f7f4;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #d2691e;
    }

    .menu-item-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .menu-item-image:hover {
        border-color: #d2691e;
        transform: scale(1.02);
    }

    .menu-item-card:hover {
        transform: translateX(10px);
        box-shadow: 0 8px 20px rgba(139, 69, 19, 0.2);
        background: linear-gradient(135deg, #f5f5dc, #fffacd);
    }

    .menu-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .menu-item-name {
        font-size: 20px;
        color: #5c4033;
        font-weight: 700;
    }

    .menu-item-price {
        font-size: 20px;
        color: #d2691e;
        font-weight: 700;
    }

    .menu-item-description {
        color: #666;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 12px;
    }

    .menu-item-meta {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #999;
        margin-bottom: 10px;
    }

    .menu-item-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .add-to-cart-btn {
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 10px;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .add-to-cart-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        color: white;
    }

    .add-to-cart-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    /* Search Section */
    .search-menu-section {
        background: linear-gradient(135deg, #f5f5dc, #fffacd);
        padding: 30px 0;
    }

    .search-box {
        position: relative;
        margin-bottom: 0;
        display: flex;
        gap: 10px;
    }

    .search-box input {
        flex: 1;
        padding: 15px 20px;
        border: 2px solid #d2691e;
        border-radius: 25px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .search-box input::placeholder {
        color: #aaa;
    }

    .search-box input:focus {
        outline: none;
        border-color: #8b4513;
        box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
    }

    .search-btn {
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .search-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        color: white;
    }

    .search-box input:focus {
        outline: none;
        border-color: #8b4513;
        box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
    }

    /* Menu Stats */
    .menu-stats {
        display: flex;
        justify-content: center;
        align-items: stretch;
        flex-wrap: wrap;
        gap: 14px;
        background: transparent;
        color: white;
        padding: 0;
        text-align: center;
        margin-top: 20px;
    }

    .menu-stats-item {
        flex: 1 1 180px;
        min-width: 140px;
        max-width: 220px;
        background: #804922;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 14px;
        padding: 18px 14px;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.14);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .stats-icon {
        display: none;
    }

    .stats-number {
        font-size: 24px;
        color: #ffd700;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .stats-label {
        font-size: 14px;
        color: #f5f5dc;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .no-items {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    .no-items i {
        font-size: 48px;
        display: block;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .special-badge {
        background: #d2691e;
        color: white;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
        margin-bottom: 10px;
    }

    .menu-item-rating {
        margin: 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .menu-item-reviews {
        margin: 10px 0;
        padding: 10px;
        background: rgba(245, 245, 220, 0.5);
        border-radius: 8px;
        border-left: 3px solid #d2691e;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .menu-header h1 {
            font-size: 35px;
        }

        .menu-category-title {
            font-size: 25px;
        }

        .menu-item-header {
            flex-direction: column;
        }

        .menu-item-price {
            margin-top: 10px;
        }
        
        .menu-item-reviews {
            font-size: 11px;
        }

        .search-box {
            flex-direction: column;
        }

        .search-btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>

</head>
<body>
    <br>

<!-- Menu Header -->
<div class="menu-header">
    <h1><?php echo htmlspecialchars($menu_settings['menu_header_title']); ?></h1>
    <p><?php echo htmlspecialchars($menu_settings['menu_header_subtitle']); ?></p>
</div>

<!-- Search Section -->
<section class="search-menu-section">
    <div class="container">
        <form method="GET" action="">
            <div class="search-box">
                <input type="text" name="search" placeholder="🔍 Search menu items..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">
                    🔎 Search
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Menu Navigation (Category Filter) -->
<div class="menu-nav">
    <div class="container">
        <a href="menu.php" class="btn <?php echo empty($selected_category) ? 'active' : ''; ?>">All Items</a>
        <?php foreach ($categories as $cat): ?>
            <a href="menu.php?category=<?php echo urlencode($cat); ?>#category-<?php echo strtolower(str_replace(' ', '-', $cat)); ?>" class="btn <?php echo $selected_category === $cat ? 'active' : ''; ?>">
                <?php echo getCategoryIcon($cat); ?> <?php echo htmlspecialchars($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Menu Content -->
<section class="menu-content">
    <div class="container">
        <?php if (count($grouped_products) > 0): ?>
            <?php foreach ($grouped_products as $category => $products): ?>
                <div class="menu-category" id="category-<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                    <h2 class="menu-category-title"><?php echo getCategoryIcon($category); ?> <?php echo htmlspecialchars($category); ?></h2>
                    
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="menu-item-card">
                                    <?php if ($product['stock'] < 5): ?>
                                        <span class="special-badge">⚠️ Limited Stock</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($product['image_url']) && file_exists(__DIR__ . '/' . $product['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="menu-item-image">
                                    <?php else: ?>
                                        <div class="menu-item-image" style="background: linear-gradient(135deg, #f5f5dc, #fffacd); display: flex; align-items: center; justify-content: center; color: #8b4513; font-size: 48px; border: 2px dashed #d2691e;">
                                            <?php echo getCategoryIcon($product['category']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="menu-item-header">
                                        <div class="menu-item-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                        <div class="menu-item-price"><?php echo getCurrencySymbol(); ?><?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                    
                                    <div class="menu-item-description">
                                        <?php echo htmlspecialchars($product['description'] ?? 'Delicious cafe item'); ?>
                                    </div>
                                    
                                    <div class="menu-item-meta">
                                        <span>📦 <?php echo intval($product['stock']); ?> available</span>
                                    </div>
                                    
                                    <?php 
                                    $rating_data = get_product_rating($product['id']);
                                    if ($rating_data['total_reviews'] > 0): 
                                    ?>
                                    <div class="menu-item-rating" style="margin: 10px 0; display: flex; align-items: center; gap: 8px;">
                                        <div style="display: flex; align-items: center; gap: 2px;">
                                            <?php echo get_star_display(round($rating_data['avg_rating'])); ?>
                                            <span style="color: #5c4033; font-weight: 600; font-size: 14px;">
                                                <?php echo number_format($rating_data['avg_rating'], 1); ?>/5
                                            </span>
                                        </div>
                                        <span style="color: #666; font-size: 12px;">
                                            (<?php echo $rating_data['total_reviews']; ?> review<?php echo $rating_data['total_reviews'] > 1 ? 's' : ''; ?>)
                                        </span>
                                    </div>
                                    
                                    <?php 
                                    $reviews = get_product_reviews($product['id'], 2);
                                    if (!empty($reviews)): 
                                    ?>
                                    <div style="margin: 10px 0;">
                                        <button onclick="toggleReviews(<?php echo $product['id']; ?>)" class="show-reviews-btn" style="background: none; border: 1px solid #d2691e; color: #d2691e; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer;">Show Reviews</button>
                                    </div>
                                    <div id="reviews-<?php echo $product['id']; ?>" class="menu-item-reviews" style="margin: 10px 0; padding: 12px; background: rgba(245, 245, 220, 0.5); border-radius: 8px; border-left: 3px solid #d2691e; display: none;">
                                        <div style="font-size: 14px; color: #5c4033; font-weight: 600; margin-bottom: 10px;">Recent Reviews:</div>
                                        <?php foreach ($reviews as $review): ?>
                                        <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(139, 69, 19, 0.1);">
                                            <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 4px;">
                                                <span style="font-size: 13px; color: #8b4513; font-weight: 600;"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                                                <span style="font-size: 12px;"><?php echo get_star_display($review['rating']); ?></span>
                                            </div>
                                            <div style="font-size: 13px; color: #666; line-height: 1.4;">
                                                "<?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?><?php echo strlen($review['review_text']) > 100 ? '...' : ''; ?>"
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if ($rating_data['total_reviews'] > 2): ?>
                                        <div style="text-align: center; margin-top: 8px;">
                                            <a href="product_review.php" style="font-size: 11px; color: #d2691e; text-decoration: none; font-weight: 600;">View all <?php echo $rating_data['total_reviews']; ?> reviews →</a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div style="text-align: center; margin: 10px 0;">
                                        <span style="font-size: 11px; color: #666;">No reviews yet</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; gap: 8px; margin-top: 15px;">
                                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                                            <a href="add_to_cart.php?item=<?php echo urlencode($product['product_name']); ?>&price=<?php echo $product['price']; ?>&id=<?php echo $product['id']; ?>" class="add-to-cart-btn" style="flex: 1;">
                                                🛒 Add to Cart
                                            </a>
                                            <a href="add_to_wishlist.php?product_id=<?php echo intval($product['id']); ?>" class="add-to-cart-btn" style="flex: 0.3; padding: 0; display: flex; align-items: center; justify-content: center;" title="Add to Wishlist">
                                                ❤️
                                            </a>
                                        <?php else: ?>
                                            <a href="login.php" class="add-to-cart-btn" style="flex: 1;">
                                                🔐 Login to Order
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-items">
                <i class="fas fa-search"></i>
                <h4>No items found</h4>
                <p><?php echo !empty($search_query) ? 'Try a different search term' : 'No products available'; ?></p>
                <a href="menu.php" class="btn btn-primary" style="margin-top: 20px;">View All Items</a>
            </div>
        <?php endif; ?>

        <!-- Menu Stats -->
        <div class="menu-stats">
            <div class="menu-stats-item">
                <div class="stats-number"><?php echo count($all_products); ?></div>
                <div class="stats-label">Total Items</div>
            </div>
            <div class="menu-stats-item">
                <div class="stats-number"><?php echo count($categories); ?></div>
                <div class="stats-label">Categories</div>
            </div>
            <div class="menu-stats-item">
                <div class="stats-number">
                    <?php echo getCurrencySymbol(); ?><?php 
                    $total_revenue = 0;
                    foreach ($all_products as $p) {
                        $total_revenue += $p['price'];
                    }
                    echo number_format((count($all_products) > 0) ? ($total_revenue / count($all_products)) : 0, 2);
                    ?>
                </div>
                <div class="stats-label">Avg Price</div>
            </div>
        </div>
    </div>
</section>

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
function toggleReviews(productId) {
    const reviewsDiv = document.getElementById('reviews-' + productId);
    const button = reviewsDiv.previousElementSibling.querySelector('.show-reviews-btn');
    
    if (reviewsDiv.style.display === 'none') {
        reviewsDiv.style.display = 'block';
        button.textContent = 'Hide Reviews';
    } else {
        reviewsDiv.style.display = 'none';
        button.textContent = 'Show Reviews';
    }
}

// Auto-scroll to first search result
document.addEventListener('DOMContentLoaded', function() {
    const firstCategoryId = '<?php echo $first_category_id; ?>';
    if (firstCategoryId) {
        setTimeout(function() {
            const element = document.getElementById(firstCategoryId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Highlight the first category title
                const categoryTitle = element.querySelector('.menu-category-title');
                if (categoryTitle) {
                    categoryTitle.style.backgroundColor = '#fff8f0';
                    categoryTitle.style.padding = '10px';
                    categoryTitle.style.borderRadius = '8px';
                    setTimeout(() => {
                        categoryTitle.style.backgroundColor = 'transparent';
                    }, 2000);
                }
            }
        }, 300);
    }
});
</script>

</body>
</html>
