<?php
// Start output buffering to allow header() calls after includes
ob_start();

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

// Function to get wishlist header settings
function getWishlistHeaderSettings() {
    global $conn;
    $defaults = [
        'wishlist_header_title' => '❤️ My Wishlist',
        'wishlist_header_subtitle' => 'Your favorite items saved for later',
        'wishlist_header_image' => 'https://images.unsplash.com/photo-1495474472645-4d71bcdd2016?w=1200&h=300&fit=crop',
        'wishlist_header_title_color' => '#ffffff',
        'wishlist_header_subtitle_color' => '#ffffff',
        'wishlist_header_title_size' => '45px'
    ];
    
    // Ensure cafe_settings table exists
    $create_table = "CREATE TABLE IF NOT EXISTS cafe_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
    
    $sql = "SELECT setting_key, setting_value FROM cafe_settings 
            WHERE setting_key IN ('wishlist_header_title', 'wishlist_header_subtitle', 'wishlist_header_image', 
                                 'wishlist_header_title_color', 'wishlist_header_subtitle_color', 'wishlist_header_title_size')";
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
    if (!empty($settings['wishlist_header_title_size']) && strpos($settings['wishlist_header_title_size'], 'px') === false) {
        $settings['wishlist_header_title_size'] = preg_replace('/[^0-9]/', '', $settings['wishlist_header_title_size']) . 'px';
    }
    
    return $settings;
}

$wishlist_settings = getWishlistHeaderSettings();

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

$user_id = intval($_SESSION['user_id']);

// Create wishlist table if not exists
$create_wishlist_table = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
)";
mysqli_query($conn, $create_wishlist_table);

// Handle remove from wishlist
if (isset($_GET['remove']) && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $remove_sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $remove_stmt = mysqli_prepare($conn, $remove_sql);
    mysqli_stmt_bind_param($remove_stmt, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($remove_stmt);
    mysqli_stmt_close($remove_stmt);
    header('Location: wishlist.php');
    exit();
}

// Handle clear all wishlist
if (isset($_GET['clear_all'])) {
    $clear_sql = "DELETE FROM wishlist WHERE user_id = ?";
    $clear_stmt = mysqli_prepare($conn, $clear_sql);
    mysqli_stmt_bind_param($clear_stmt, 'i', $user_id);
    mysqli_stmt_execute($clear_stmt);
    mysqli_stmt_close($clear_stmt);
    header('Location: wishlist.php');
    exit();
}

// Fetch wishlist items
$wishlist_sql = "SELECT p.id, p.product_name, p.description, p.price, p.stock, p.category, p.image_url 
                 FROM wishlist w 
                 JOIN products p ON w.product_id = p.id 
                 WHERE w.user_id = ?
                 ORDER BY w.added_at DESC";$wishlist_stmt = mysqli_prepare($conn, $wishlist_sql);
mysqli_stmt_bind_param($wishlist_stmt, 'i', $user_id);
mysqli_stmt_execute($wishlist_stmt);
$wishlist_result = mysqli_stmt_get_result($wishlist_stmt);
$wishlist_items = [];
if ($wishlist_result && mysqli_num_rows($wishlist_result) > 0) {
    while($item = mysqli_fetch_assoc($wishlist_result)) {
        $wishlist_items[] = $item;
    }
}
mysqli_stmt_close($wishlist_stmt);

// Handle add all wishlist items to cart
if (isset($_POST['add_all'])) {
    if (!empty($wishlist_items)) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        foreach ($wishlist_items as $item) {
            if ($item['stock'] <= 0) {
                continue;
            }

            $product_id = intval($item['id']);
            $product_name = $item['product_name'];
            $product_price = floatval($item['price']);

            $found = false;
            foreach ($_SESSION['cart'] as &$cart_item) {
                if ($cart_item['id'] === $product_id) {
                    $cart_item['quantity'] += 1;
                    $found = true;
                    break;
                }
            }
            unset($cart_item);

            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product_id,
                    'name' => $product_name,
                    'price' => $product_price,
                    'quantity' => 1
                ];
            }
        }

        $_SESSION['cart_message'] = 'Wishlist items added to cart successfully!';
    }
    header('Location: add_to_cart.php');
    exit();
}

// Fetch wishlist count
$count_sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";$wishlist_stmt = mysqli_prepare($conn, $wishlist_sql);
mysqli_stmt_bind_param($wishlist_stmt, 'i', $user_id);
mysqli_stmt_execute($wishlist_stmt);
$wishlist_result = mysqli_stmt_get_result($wishlist_stmt);
$wishlist_items = [];
if ($wishlist_result && mysqli_num_rows($wishlist_result) > 0) {
    while($item = mysqli_fetch_assoc($wishlist_result)) {
        $wishlist_items[] = $item;
    }
}
mysqli_stmt_close($wishlist_stmt);

// Fetch wishlist count
$count_sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, 'i', $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$wishlist_count = 0;
if ($count_result && mysqli_num_rows($count_result) > 0) {
    $row = mysqli_fetch_assoc($count_result);
    $wishlist_count = $row['count'];
}
mysqli_stmt_close($count_stmt);

// Helper function to get category emoji
function getCategoryEmoji($category) {
    $emojis = [
        'Coffee' => '☕',
        'Tea' => '🍵',
        'Pastry' => '🥐',
        'Dessert' => '🍰',
        'Breakfast' => '🥞',
        'Sandwich' => '🥪',
        'Beverage' => '🥤',
        'Cake' => '🎂',
        'Cookie' => '🍪'
    ];
    return isset($emojis[$category]) ? $emojis[$category] : '☕';
}
?>

<style>
    /* ========== WISHLIST PAGE STYLING ========== */

    .wishlist-header {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                    url('<?php echo htmlspecialchars($wishlist_settings['wishlist_header_image']); ?>');
        background-size: cover;
        background-position: center;
        padding: 80px 0;
        text-align: center;
        color: white;
        margin-top: -75px;
        padding-top: 130px;
    }

    .wishlist-header h1 {
        font-size: <?php echo htmlspecialchars($wishlist_settings['wishlist_header_title_size']); ?>;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        color: <?php echo htmlspecialchars($wishlist_settings['wishlist_header_title_color']); ?>;
    }

    .wishlist-header p {
        font-size: 16px;
        color: <?php echo htmlspecialchars($wishlist_settings['wishlist_header_subtitle_color']); ?>;
        margin: 10px 0 0 0;
    }

    /* Wishlist Container */
    .wishlist-container {
        padding: 60px 0;
        background: #fff;
    }

    .wishlist-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: #f9f7f4;
        border-radius: 10px;
    }

    .wishlist-count {
        color: #5c4033;
        font-size: 18px;
        font-weight: 700;
    }

    .control-buttons {
        display: flex;
        gap: 10px;
    }

    .control-btn {
        padding: 10px 20px;
        border: 2px solid #d2691e;
        background: transparent;
        color: #5c4033;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .control-btn:hover {
        background: #d2691e;
        color: white;
    }

    /* Wishlist Grid */
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .wishlist-item {
        background: #f9f7f4;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .wishlist-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 20px rgba(139, 69, 19, 0.2);
    }

    .item-image-container {
        background: linear-gradient(135deg, #f5f5dc, #fffacd);
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 60px;
        position: relative;
    }

    .wishlist-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ff6b6b;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .wishlist-badge:hover {
        background: #ff4444;
        transform: scale(1.1);
    }

    .item-content {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .wish-item-name {
        color: #5c4033;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .wish-item-desc {
        color: #666;
        font-size: 13px;
        margin-bottom: 12px;
    }

    .wish-item-price {
        color: #d2691e;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .wishlist-actions {
        display: flex;
        gap: 10px;
        margin-top: auto;
    }

    .wish-add-btn {
        flex: 1;
        padding: 10px;
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .wish-add-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
    }

    .wish-add-btn:disabled {
        background: linear-gradient(135deg, #ccc, #999);
        cursor: not-allowed;
        opacity: 0.6;
    }

    .wish-remove-btn {
        padding: 10px 15px;
        background: #e8e4d8;
        border: none;
        color: #5c4033;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .wish-remove-btn:hover {
        background: #d2691e;
        color: white;
    }

    /* Empty Wishlist */
    .empty-wishlist {
        text-align: center;
        padding: 80px 20px;
        background: #f9f7f4;
        border-radius: 12px;
    }

    .empty-icon {
        font-size: 80px;
        margin-bottom: 20px;
    }

    .empty-title {
        color: #5c4033;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .empty-desc {
        color: #666;
        margin-bottom: 25px;
    }

    .empty-btn {
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .empty-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wishlist-controls {
            flex-direction: column;
            gap: 15px;
        }

        .control-buttons {
            width: 100%;
        }

        .control-btn {
            flex: 1;
        }

        .wishlist-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
    }
</style>
<br>
<br>
<br>

<!-- Wishlist Header -->
<div class="wishlist-header">    <br>
<br>
<br>
<br>
    <h1><?php echo htmlspecialchars($wishlist_settings['wishlist_header_title']); ?></h1>
    <p><?php echo htmlspecialchars($wishlist_settings['wishlist_header_subtitle']); ?></p>
</div>

<!-- Wishlist Content -->
<section class="wishlist-container">
    <div class="container">

        <?php if (empty($wishlist_items)): ?>
        <!-- Empty Wishlist -->
        <div class="empty-wishlist">
            <div class="empty-icon">💭</div>
            <div class="empty-title">Your Wishlist is Empty</div>
            <div class="empty-desc">Start adding your favorite items to your wishlist!</div>
            <a href="menu.php" style="text-decoration: none;">
                <button class="empty-btn">Browse Menu</button>
            </a>
        </div>

        <?php else: ?>
        
        <div class="wishlist-controls">
            <div class="wishlist-count">❤️ <?php echo count($wishlist_items); ?> Item<?php echo count($wishlist_items) != 1 ? 's' : ''; ?> in Wishlist</div>
            <div class="control-buttons">
                <a href="menu.php" style="text-decoration: none; flex: 1;">
                    <button class="control-btn" style="width: 100%;">View All Products</button>
                </a>                <form method="POST" style="flex: 1;">
                    <button class="control-btn" type="submit" name="add_all" style="width: 100%;" <?php echo empty($wishlist_items) ? 'disabled' : ''; ?>>Add All to Cart</button>
                </form>                <a href="wishlist.php?clear_all=1" style="text-decoration: none; flex: 1;" onclick="return confirm('Are you sure you want to clear your entire wishlist?');">
                    <button class="control-btn" style="width: 100%;">Clear All</button>
                </a>
            </div>
        </div>

        <!-- Wishlist Items Grid -->
        <div class="wishlist-grid">
            <?php foreach ($wishlist_items as $item): ?>
            <div class="wishlist-item">
                <div class="item-image-container">
                    <?php if (!empty($item['image_url']) && file_exists(__DIR__ . '/' . $item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                        <?php echo getCategoryEmoji($item['category']); ?>
                    <?php endif; ?>
                    <div class="wishlist-badge">❤️</div>
                </div>
                <div class="item-content">
                    <div class="wish-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="wish-item-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 40) . '...'); ?></div>
                    <div class="wish-item-price"><?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($item['price']), 2); ?></div>
                    <div style="font-size: 12px; color: #999; margin-bottom: 10px;">
                        <?php 
                        if ($item['stock'] > 0) {
                            echo '📦 ' . intval($item['stock']) . ' available';
                        } else {
                            echo '❌ Out of Stock';
                        }
                        ?>
                    </div>
                    <div class="wishlist-actions">
                        <a href="add_to_cart.php?id=<?php echo intval($item['id']); ?>&item=<?php echo urlencode($item['product_name']); ?>&price=<?php echo floatval($item['price']); ?>" style="text-decoration: none; flex: 1;">
                            <button class="wish-add-btn" <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>Add to Cart</button>
                        </a>
                        <a href="wishlist.php?remove=1&product_id=<?php echo intval($item['id']); ?>" style="text-decoration: none;">
                            <button class="wish-remove-btn">✕</button>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

    </div>
</section>



<?php ob_end_flush(); ?>
<?php include 'footer.php'; ?>

