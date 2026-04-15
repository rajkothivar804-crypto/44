<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
include 'database/DB.php';

// Function to get cart header settings
function getCartHeaderSettings() {
    global $conn;
    $defaults = [
        'cart_header_title' => '🛒 Shopping Cart',
        'cart_header_subtitle' => 'Review and manage your items',
        'cart_header_image' => 's.jpg',
        'cart_header_title_color' => '#ffffff',
        'cart_header_subtitle_color' => '#ffffff',
        'cart_header_title_size' => '45px'
    ];
    
    // Ensure cafe_settings table exists
    $create_table = "CREATE TABLE IF NOT EXISTS cafe_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
    
    $sql = "SELECT setting_key, setting_value FROM cafe_settings 
            WHERE setting_key IN ('cart_header_title', 'cart_header_subtitle', 'cart_header_image', 
                                 'cart_header_title_color', 'cart_header_subtitle_color', 'cart_header_title_size')";
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
    if (!empty($settings['cart_header_title_size']) && strpos($settings['cart_header_title_size'], 'px') === false) {
        $settings['cart_header_title_size'] = preg_replace('/[^0-9]/', '', $settings['cart_header_title_size']) . 'px';
    }
    
    return $settings;
}

$cart_settings = getCartHeaderSettings();

// Detect AJAX requests
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Helper function to fetch product from database
function get_product_details($product_id) {
    global $conn;
    $sql = "SELECT id, product_name, price, image_url FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
    return null;
}

// Handle add to cart via GET or POST
if (isset($_GET['id']) || isset($_POST['id'])) {
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
    $product_name = isset($_GET['item']) ? trim($_GET['item']) : (isset($_POST['item']) ? trim($_POST['item']) : '');
    $product_price = isset($_GET['price']) ? floatval($_GET['price']) : (isset($_POST['price']) ? floatval($_POST['price']) : 0);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product already in cart
    $product_exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] === $product_id) {
            $item['quantity'] += $quantity;
            $product_exists = true;
            break;
        }
    }
    
    // Add product if not in cart
    if (!$product_exists) {
        $_SESSION['cart'][] = [
            'id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $quantity
        ];
    }
    
    // Check if AJAX request (AJAX requests typically have XMLHttpRequest header or specific parameter)
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        $subtotal = array_sum(array_map(function($item) { 
            return $item['price'] * $item['quantity']; 
        }, $_SESSION['cart']));
        echo json_encode([
            'success' => true,
            'message' => 'Item added to cart!',
            'cart_count' => count($_SESSION['cart']),
            'subtotal' => number_format($subtotal, 2)
        ]);
        exit;
    }
    
    // For regular form submissions, show success message and stay on page
    $_SESSION['cart_message'] = 'Item added to cart successfully!';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    if (isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
            return $item['id'] !== $product_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header('Location: add_to_cart.php');
    exit;
}

// Handle increase/decrease quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['increase']) || isset($_POST['decrease'])) {
        $product_id = isset($_POST['increase']) ? intval($_POST['increase']) : intval($_POST['decrease']);
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $product_id) {
                    if (isset($_POST['increase'])) {
                        $item['quantity']++;
                    } else {
                        if ($item['quantity'] > 1) {
                            $item['quantity']--;
                        } else {
                            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($i) use ($product_id) {
                                return $i['id'] !== $product_id;
                            });
                            $_SESSION['cart'] = array_values($_SESSION['cart']);
                        }
                    }
                    break;
                }
            }
            unset($item);
        }

        if ($is_ajax) {
            $subtotal = 0;
            foreach (isset($_SESSION['cart']) ? $_SESSION['cart'] : [] as $cart_item) {
                $subtotal += floatval($cart_item['price']) * intval($cart_item['quantity']);
            }
            $tax = $subtotal * 0.10;
            $delivery_charge = 2.00;
            $total = $subtotal + $tax + $delivery_charge;

            $updated_quantity = null;
            $updated_item_total = null;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $cart_item) {
                    if ($cart_item['id'] === $product_id) {
                        $updated_quantity = intval($cart_item['quantity']);
                        $updated_item_total = floatval($cart_item['price']) * intval($cart_item['quantity']);
                        break;
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cart_count' => count(isset($_SESSION['cart']) ? $_SESSION['cart'] : []),
                'subtotal' => number_format($subtotal, 2),
                'tax' => number_format($tax, 2),
                'total' => number_format($total, 2),
                'item_id' => $product_id,
                'item_quantity' => $updated_quantity,
                'item_total' => $updated_item_total !== null ? number_format($updated_item_total, 2) : null,
                'item_removed' => $updated_quantity === null
            ]);
            exit;
        }

        header('Location: add_to_cart.php');
        exit;
    }
}



// Handle clear cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header('Location: add_to_cart.php');
    exit;
}

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$tax = $subtotal * 0.10;
$delivery_charge = 2.00;
$total = $subtotal + $tax + $delivery_charge;

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

include 'hader.php';
?>

<style>
    /* ========== CART PAGE STYLING ========== */

    .cart-header {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                    url('<?php echo htmlspecialchars($cart_settings['cart_header_image']); ?>');
        background-size: cover;
        background-position: center;
        padding: 80px 0;
        text-align: center;
        color: white;
        margin-top: -75px;
        padding-top: 130px;
    }

    .cart-header h1 {
        font-size: <?php echo htmlspecialchars($cart_settings['cart_header_title_size']); ?>;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        color: <?php echo htmlspecialchars($cart_settings['cart_header_title_color']); ?>;
    }

    .cart-header p {
        font-size: 16px;
        color: <?php echo htmlspecialchars($cart_settings['cart_header_subtitle_color']); ?>;
        margin: 10px 0 0 0;
    }

    /* Main Cart Container */
    .cart-container {
        padding: 60px 0;
        background: #fff;
    }

    .cart-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }

    /* Cart Items */
    .cart-items-section {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .cart-items-title {
        font-size: 24px;
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 25px;
        border-bottom: 2px solid #d2691e;
        padding-bottom: 15px;
    }

    /* Cart Item */
    .cart-item {
        background: white;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 10px;
        border-left: 4px solid #d2691e;
        display: grid;
        grid-template-columns: 80px 1fr 100px 100px 50px;
        gap: 20px;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .cart-item:hover {
        box-shadow: 0 4px 12px rgba(139, 69, 19, 0.15);
    }

    .item-image {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #f5f5dc, #fffacd);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
    }

    .item-details h3 {
        color: #5c4033;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .item-details p {
        color: #666;
        font-size: 13px;
        margin: 0;
    }

    .item-price {
        color: #d2691e;
        font-weight: 700;
        font-size: 16px;
    }

    .item-quantity {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .qty-btn {
        background: #e8e4d8;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 700;
        color: #5c4033;
        transition: all 0.3s ease;
    }

    .qty-btn:hover {
        background: #d2691e;
        color: white;
    }

    .qty-input {
        width: 40px;
        text-align: center;
        border: 1px solid #d2691e;
        border-radius: 4px;
        padding: 5px;
        font-weight: 600;
    }

    .item-total {
        color: #8b4513;
        font-weight: 700;
        font-size: 16px;
    }

    .remove-btn {
        background: #ff6b6b;
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .remove-btn:hover {
        background: #ff4444;
        transform: scale(1.1);
    }

    /* Empty Cart */
    .empty-cart {
        text-align: center;
        padding: 50px 20px;
        color: #666;
    }

    .empty-cart-icon {
        font-size: 60px;
        margin-bottom: 20px;
    }

    /* Order Summary */
    .order-summary {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        height: fit-content;
        position: sticky;
        top: 100px;
    }

    .summary-title {
        font-size: 22px;
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 20px;
        border-bottom: 2px solid #d2691e;
        padding-bottom: 15px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        color: #666;
        font-size: 14px;
    }

    .summary-row.total {
        border-top: 2px solid #d2691e;
        padding-top: 15px;
        margin-top: 15px;
        color: #5c4033;
        font-weight: 700;
        font-size: 18px;
    }

    .summary-value {
        font-weight: 600;
        color: #d2691e;
    }

    .total-value {
        font-size: 24px;
        color: #8b4513;
    }

    .discount-badge {
        background: #000000;
        color: white;
        padding: 8px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-top: 10px;
    }

    /* Buttons */
    .cart-actions {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 25px;
    }

    .cart-actions a {
        padding: 14px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 15px;
        text-align: center;
        display: block;
        text-decoration: none !important;
    }

    .continue-shopping {
        background: #e8e4d8;
        color: #5c4033;
        border: 2px solid #d2691e;
    }

    .continue-shopping:hover {
        background: #d2691e;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(210, 105, 30, 0.3);
    }

    .checkout-btn {
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: none;
    }

    .checkout-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        box-shadow: 0 6px 16px rgba(139, 69, 19, 0.4);
        transform: translateY(-2px);
    }

    /* Promo Code */
    .promo-section {
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #d2691e;
    }

    .promo-label {
        font-size: 13px;
        color: #666;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .promo-input {
        width: 100%;
        padding: 10px;
        border: 2px solid #d2691e;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .promo-btn {
        width: 100%;
        padding: 10px;
        background: #8b4513;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .promo-btn:hover {
        background: #d2691e;
    }

    /* Recommended Items */
    .recommended-section {
        margin-top: 50px;
        padding: 40px;
        background: linear-gradient(135deg, #f5f5dc, #fffacd);
        border-radius: 12px;
    }

    .recommended-title {
        font-size: 24px;
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 25px;
    }

    .recommended-item {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .recommended-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(139, 69, 19, 0.15);
    }

    .rec-icon {
        font-size: 40px;
        margin-bottom: 10px;
    }

    .rec-name {
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .rec-price {
        color: #d2691e;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .rec-add-btn {
        background: #d2691e;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .rec-add-btn:hover {
        background: #8b4513;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .cart-section {
            grid-template-columns: 1fr;
        }

        .order-summary {
            position: static;
        }

        .cart-item {
            grid-template-columns: 1fr;
        }

        .item-image {
            display: none;
        }

        .recommended-section {
            padding: 20px;
        }
    }
</style>
<br>
<br>
<br>
<!-- Cart Header with Background Image Only -->
<div class="cart-header" style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo htmlspecialchars($cart_settings['cart_header_image']); ?>'); background-size: cover; background-position: center; padding: 80px 0; text-align: center; color: white; margin-top: -75px; padding-top: 130px;">
    <h1><?php echo htmlspecialchars($cart_settings['cart_header_title']); ?></h1>
    <p><?php echo htmlspecialchars($cart_settings['cart_header_subtitle']); ?></p>
</div>

<?php if (isset($_SESSION['cart_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 20px 0; max-width: 800px; margin-left: auto; margin-right: auto;">
    <strong>✓ Success!</strong> <?php echo htmlspecialchars($_SESSION['cart_message']); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['cart_message']); ?>
<?php endif; ?>

<!-- Cart Content -->
<section class="cart-container">
    <div class="container">

        <div class="cart-section">
            <!-- Cart Items -->
            <div class="cart-items-section">
                <h2 class="cart-items-title">Items in Cart (<?php echo count($cart_items); ?>)</h2>

                <?php if (empty($cart_items)): ?>
                    <!-- Empty Cart -->
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h3>Your cart is empty</h3>
                        <p>Add some delicious items to get started!</p>
                        <br>
                        <a href="menu.php" class="btn btn-warning" style="color: white;">Browse Menu</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                            $product = get_product_details($item['id']);
                            $item_total = $item['price'] * $item['quantity'];
                        ?>
                        <!-- Cart Item -->
                        <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                            <div class="item-image">
                                <?php if (!empty($product['image_url']) && file_exists(__DIR__ . '/' . $product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    ☕
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><?php echo isset($product['product_name']) ? htmlspecialchars($product['product_name']) : 'Premium Item'; ?></p>
                            </div>
                            <div class="item-price"><?php echo getCurrencySymbol(); ?><?php echo number_format($item['price'], 2); ?></div>
                            <div class="item-quantity">
                                <form method="POST" action="" class="quantity-form" data-id="<?php echo $item['id']; ?>" style="display: flex; gap: 8px; align-items: center;">
                                    <button type="submit" class="qty-btn" name="decrease" value="<?php echo $item['id']; ?>">-</button>
                                    <input type="text" class="qty-input" value="<?php echo $item['quantity']; ?>" readonly>
                                    <button type="submit" class="qty-btn" name="increase" value="<?php echo $item['id']; ?>">+</button>
                                </form>
                            </div>
                            <div class="item-total" data-item-total="<?php echo $item['id']; ?>"><?php echo getCurrencySymbol(); ?><?php echo number_format($item_total, 2); ?></div>
                            <a href="add_to_cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn" onclick="return confirm('Remove this item?');">✕</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 class="summary-title">Order Summary</h3>

                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span class="summary-value summary-subtotal"><?php echo getCurrencySymbol(); ?><?php echo number_format($subtotal, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Shipping:</span>
                    <span class="summary-value summary-shipping"><?php echo getCurrencySymbol(); ?><?php echo number_format($delivery_charge, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Tax (10%):</span>
                    <span class="summary-value summary-tax"><?php echo getCurrencySymbol(); ?><?php echo number_format($tax, 2); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total:</span>
                    <span class="total-value summary-total"><?php echo getCurrencySymbol(); ?><?php echo number_format($total, 2); ?></span>
                </div>

                <div class="cart-actions">
                    <a href="menu.php" class="continue-shopping" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Continue Shopping</a>
                    <?php if (!empty($cart_items)): ?>
                        <a href="payment.php" class="checkout-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Proceed to Checkout →</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recommended Items -->
        <div class="recommended-section">
            <h3 class="recommended-title">✨ You Might Also Like</h3>
            <div class="row">
                <?php 
                // Fetch recommended products from database
                $recommended_sql = "SELECT id, product_name, price, image_url FROM products ORDER BY id DESC LIMIT 4";
                $recommended_result = mysqli_query($conn, $recommended_sql);
                
                if ($recommended_result && mysqli_num_rows($recommended_result) > 0):
                    while ($rec_product = mysqli_fetch_assoc($recommended_result)):
                ?>
                    <div class="col-md-3">
                        <div class="recommended-item">
                            <div class="rec-icon">
                                <?php if (!empty($rec_product['image_url']) && file_exists(__DIR__ . '/' . $rec_product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($rec_product['image_url']); ?>" alt="<?php echo htmlspecialchars($rec_product['product_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    ☕
                                <?php endif; ?>
                            </div>
                            <div class="rec-name"><?php echo htmlspecialchars($rec_product['product_name']); ?></div>
                            <div class="rec-price"><?php echo getCurrencySymbol(); ?><?php echo number_format($rec_product['price'], 2); ?></div>
                            <button type="button" class="rec-add-btn" onclick="addToCart(<?php echo $rec_product['id']; ?>, '<?php echo addslashes($rec_product['product_name']); ?>', <?php echo $rec_product['price']; ?>)">+ Add</button>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="col-12">
                        <p style="text-align: center; color: #999;">No products available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<script>
function addToCart(productId, productName, productPrice) {
    // Create form data
    var formData = new FormData();
    formData.append('id', productId);
    formData.append('item', productName);
    formData.append('price', productPrice);
    formData.append('quantity', 1);

    // Send AJAX request
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Item added to cart successfully!', 'success');
            var cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(function(element) {
                element.textContent = data.cart_count;
            });
            setTimeout(function() {
                location.reload();
            }, 1500);
        } else {
            showMessage('Error adding item to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding item to cart', 'error');
    });
}

function updateQuantity(productId, direction) {
    var formData = new FormData();
    formData.append(direction, productId);

    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showMessage('Unable to update quantity', 'error');
            return;
        }

        var itemElement = document.querySelector('.cart-item[data-item-id="' + data.item_id + '"]');
        if (data.item_removed) {
            if (itemElement) {
                itemElement.remove();
            }
            if (data.cart_count === 0) {
                location.reload();
                return;
            }
        } else if (itemElement) {
            var quantityInput = itemElement.querySelector('.qty-input');
            var itemTotal = itemElement.querySelector('.item-total');
            if (quantityInput) {
                quantityInput.value = data.item_quantity;
            }
            if (itemTotal) {
                itemTotal.textContent = '<?php echo getCurrencySymbol(); ?>' + data.item_total;
            }
        }

        var cartTitle = document.querySelector('.cart-items-title');
        if (cartTitle) {
            cartTitle.textContent = 'Items in Cart (' + data.cart_count + ')';
        }

        var cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(function(element) {
            element.textContent = data.cart_count;
        });

        var subtotalElement = document.querySelector('.summary-subtotal');
        var taxElement = document.querySelector('.summary-tax');
        var totalElement = document.querySelector('.summary-total');
        if (subtotalElement) { subtotalElement.textContent = '<?php echo getCurrencySymbol(); ?>' + data.subtotal; }
        if (taxElement) { taxElement.textContent = '<?php echo getCurrencySymbol(); ?>' + data.tax; }
        if (totalElement) { totalElement.textContent = '<?php echo getCurrencySymbol(); ?>' + data.total; }

        showMessage('Quantity updated', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Unable to update quantity', 'error');
    });
}

function attachQuantityButtons() {
    document.querySelectorAll('.quantity-form .qty-btn').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            var direction = button.name;
            var form = button.closest('.quantity-form');
            if (!form) return;
            var productId = form.dataset.id;
            updateQuantity(productId, direction);
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    attachQuantityButtons();
});

function showMessage(message, type) {
    // Remove existing messages
    var existingMessages = document.querySelectorAll('.cart-message');
    existingMessages.forEach(function(msg) {
        msg.remove();
    });

    // Create new message
    var messageDiv = document.createElement('div');
    messageDiv.className = 'cart-message alert alert-' + (type === 'success' ? 'success' : 'danger');
    messageDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    messageDiv.innerHTML = '<strong>' + (type === 'success' ? '✓' : '✕') + '</strong> ' + message + 
                          '<button type="button" class="close" onclick="this.parentElement.remove()">' +
                          '<span>&times;</span></button>';
    
    document.body.appendChild(messageDiv);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        if (messageDiv.parentElement) {
            messageDiv.remove();
        }
    }, 3000);
}
</script>

<?php include 'footer.php'; ?>
