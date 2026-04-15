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
include 'otp_mailer.php';

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

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
$alert_message = '';
$alert_type = '';

// Create discount codes table if it doesn't exist
$create_discounts_table = "CREATE TABLE IF NOT EXISTS discount_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('Percentage', 'Fixed') DEFAULT 'Percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    max_uses INT NULL,
    current_uses INT DEFAULT 0,
    min_order_amount DECIMAL(10, 2) DEFAULT 0,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_discounts_table);

// Get cart items from session
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (empty($cart_items)) {
    header('Location: menu.php');
    exit();
}

// Get user info using prepared statement to prevent SQL injection
$user_sql = "SELECT email FROM users WHERE id = ? LIMIT 1";
$user_stmt = mysqli_prepare($conn, $user_sql);
if (!$user_stmt) {
    die('Prepare failed: ' . mysqli_error($conn));
}
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_email = '';
if ($user_result && mysqli_num_rows($user_result) > 0) {
    $user = mysqli_fetch_assoc($user_result);
    $user_email = $user['email'];
}
mysqli_stmt_close($user_stmt);

// Handle discount code validation
$discount_code = '';
$discount_amount = 0;
$discount_message = '';
$discount_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_discount'])) {
    $discount_code = sanitize_input($_POST['discount_code'] ?? '');
    
    if (empty($discount_code)) {
        $discount_message = '❌ Please enter a discount code';
        $discount_type = 'danger';
    } else {
        // Calculate subtotal for minimum order check
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }
        
        // Make code case-insensitive by converting to uppercase
        $discount_code_upper = strtoupper($discount_code);
        
        // Check if discount code exists and is valid
        $discount_sql = "SELECT id, discount_type, discount_value, max_uses, current_uses, min_order_amount, end_date 
                        FROM discount_codes 
                        WHERE UPPER(code) = ? AND is_active = 1 AND (end_date IS NULL OR end_date > NOW())";
        $discount_stmt = mysqli_prepare($conn, $discount_sql);
        mysqli_stmt_bind_param($discount_stmt, 's', $discount_code_upper);
        mysqli_stmt_execute($discount_stmt);
        $discount_result = mysqli_stmt_get_result($discount_stmt);
        
        if (mysqli_num_rows($discount_result) > 0) {
            $discount_row = mysqli_fetch_assoc($discount_result);
            
            // Check if code has reached max uses
            if ($discount_row['max_uses'] !== null && $discount_row['current_uses'] >= $discount_row['max_uses']) {
                $discount_message = '❌ This discount code has reached its usage limit';
                $discount_type = 'danger';
                $discount_code = '';
            } 
            // Check minimum order amount
            elseif ($subtotal < floatval($discount_row['min_order_amount'])) {
                $discount_message = '❌ Minimum order amount of $' . number_format($discount_row['min_order_amount'], 2) . ' required';
                $discount_type = 'danger';
                $discount_code = '';
            }
            // Valid discount code
            else {
                if ($discount_row['discount_type'] === 'Percentage') {
                    $discount_amount = ($subtotal * floatval($discount_row['discount_value'])) / 100;
                } else {
                    $discount_amount = floatval($discount_row['discount_value']);
                }
                $discount_message = '✅ Discount code applied! You saved $' . number_format($discount_amount, 2);
                $discount_type = 'success';
                $_SESSION['discount_code'] = $discount_code_upper;
                $_SESSION['discount_amount'] = $discount_amount;
            }
        } else {
            $discount_message = '❌ Invalid or expired discount code';
            $discount_type = 'danger';
            $discount_code = '';
        }
        mysqli_stmt_close($discount_stmt);
    }
}

// Use stored discount if already applied
if (isset($_SESSION['discount_code']) && !isset($_POST['validate_discount'])) {
    $discount_code = $_SESSION['discount_code'];
    $discount_amount = $_SESSION['discount_amount'] ?? 0;
}

// Handle place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    $delivery_type = trim($_POST['delivery_type'] ?? 'Delivery');
    $payment_method = trim($_POST['payment_method'] ?? 'Card');
    $shipping_charge = $delivery_type === 'Delivery' ? 2.00 : 0.00;

    if (empty($delivery_address)) {
        $alert_message = 'Please enter delivery address!';
        $alert_type = 'danger';
    } else {
        // Calculate totals from cart
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }
        $tax = $subtotal * 0.10;
        
        // Get discount amount from session
        $discount_amount = isset($_SESSION['discount_amount']) ? floatval($_SESSION['discount_amount']) : 0;
        
        $total_amount = $subtotal + $tax + $shipping_charge - $discount_amount;
        if ($total_amount < 0) $total_amount = 0;

        // Create orders table if not exists
        $create_orders_table = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(100),
            customer_phone VARCHAR(20),
            subtotal DECIMAL(10, 2) DEFAULT 0,
            tax DECIMAL(10, 2) DEFAULT 0,
            shipping_charge DECIMAL(10, 2) DEFAULT 0,
            discount_code VARCHAR(50),
            discount_amount DECIMAL(10, 2) DEFAULT 0,
            total_amount DECIMAL(10, 2) NOT NULL,
            status ENUM('Pending', 'Processing', 'Ready', 'Completed', 'Cancelled', 'Delivered') DEFAULT 'Pending',
            payment_method VARCHAR(50),
            delivery_address TEXT,
            special_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        if (!mysqli_query($conn, $create_orders_table)) {
            $alert_message = 'Error creating orders table: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }

        $create_order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            subtotal DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";
        if (!mysqli_query($conn, $create_order_items_table)) {
            $alert_message = 'Error creating order items table: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }

        // Insert order
        $customer_name = sanitize_input(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Customer');
        $discount_code_used = isset($_SESSION['discount_code']) ? $_SESSION['discount_code'] : NULL;
        $insert_order_sql = "INSERT INTO orders (user_id, customer_name, customer_email, subtotal, tax, shipping_charge, discount_code, discount_amount, total_amount, payment_method, delivery_address, special_notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_order_sql);
        if (!$stmt) {
            $alert_message = 'Prepare failed: ' . mysqli_error($conn);
            $alert_type = 'danger';
        } else {
            mysqli_stmt_bind_param($stmt, 'issddddsdsss', $user_id, $customer_name, $user_email, $subtotal, $tax, $shipping_charge, $discount_code_used, $discount_amount, $total_amount, $payment_method, $delivery_address, $special_instructions);
            
            if (mysqli_stmt_execute($stmt)) {
            $order_id = mysqli_insert_id($conn);
            
            // Update coupon usage count
            if (!empty($discount_code_used)) {
                $update_coupon_sql = "UPDATE discount_codes SET current_uses = current_uses + 1 WHERE UPPER(code) = ?";
                $coupon_stmt = mysqli_prepare($conn, $update_coupon_sql);
                mysqli_stmt_bind_param($coupon_stmt, 's', $discount_code_used);
                mysqli_stmt_execute($coupon_stmt);
                mysqli_stmt_close($coupon_stmt);
            }
            
            // Insert order items
            $insert_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
            $order_items_html = '<ul style="padding-left: 18px; margin: 0;">';
            foreach ($cart_items as $item) {
                $stmt_item = mysqli_prepare($conn, $insert_item_sql);
                $item_subtotal = floatval($item['price']) * intval($item['quantity']);
                $item_price = floatval($item['price']);
                $item_qty = intval($item['quantity']);
                $product_id = intval($item['id']);
                
                mysqli_stmt_bind_param($stmt_item, 'iiidd', $order_id, $product_id, $item_qty, $item_price, $item_subtotal);
                mysqli_stmt_execute($stmt_item);
                mysqli_stmt_close($stmt_item);

                $order_items_html .= '<li>' . htmlspecialchars($item['name']) . ' × ' . $item_qty . ' @ ' . getCurrencySymbol() . number_format($item_price, 2) . ' = ' . getCurrencySymbol() . number_format($item_subtotal, 2) . '</li>';
            }
            $order_items_html .= '</ul>';

            // Send order confirmation email
            $customer_name = sanitize_input(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Customer');
            $order_date = date('M d, Y @ h:i A');
            $subject = 'Order Confirmed: #' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
            $bodyHtml = "<html><body>" .
                "<h2>Thank you for your order, " . htmlspecialchars($customer_name) . "!</h2>" .
                "<p>Your order <strong>" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . "</strong> has been placed successfully.</p>" .
                "<p><strong>Order Date:</strong> " . $order_date . "</p>" .
                "<p><strong>Order Items:</strong></p>" .
                $order_items_html .
                "<p><strong>Total Amount:</strong> " . getCurrencySymbol() . number_format($total_amount, 2) . "</p>" .
                "<p><strong>Delivery Address:</strong> " . nl2br(htmlspecialchars($delivery_address)) . "</p>" .
                "<p>We will notify you again when your order is delivered or if it is cancelled.</p>" .
                "<br><p>Best regards,<br>Cafe Menu Team</p>" .
                "</body></html>";
            $altBody = "Thank you for your order, " . $customer_name . "!\n" .
                "Order ID: " . str_pad($order_id, 5, '0', STR_PAD_LEFT) . "\n" .
                "Order Date: " . $order_date . "\n" .
                "Total Amount: " . getCurrencySymbol() . number_format($total_amount, 2) . "\n" .
                "Delivery Address: " . $delivery_address . "\n\n" .
                "We will notify you again when your order is delivered or cancelled.\n\n" .
                "Best regards,\nCafe Menu Team";
            if (!empty($user_email)) {
                $emailResult = sendOrderEmail($user_email, $customer_name, $subject, $bodyHtml, $altBody);
                if (!$emailResult['success']) {
                    error_log($emailResult['message']);
                }
            }

            // Clear cart and discount session and redirect to order confirmation
            unset($_SESSION['cart']);
            unset($_SESSION['discount_code']);
            unset($_SESSION['discount_amount']);
            header('Location: order_confirmation.php?order_id=' . $order_id);
            exit();
        } else {
                $alert_message = 'Error placing order: ' . mysqli_stmt_error($stmt);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$tax = $subtotal * 0.10;
$delivery_charge = 2.00;

// Get stored discount if exists
$discount_display = isset($_SESSION['discount_amount']) ? floatval($_SESSION['discount_amount']) : 0;

$total_amount = $subtotal + $tax + $delivery_charge - $discount_display;
if ($total_amount < 0) $total_amount = 0;

$upi_id = 'rajkothivar@oksbi';
$merchant_name = isset($cafe_name) && !empty($cafe_name) ? $cafe_name : 'Cafe Delicious';
$qr_payment_amount = number_format($total_amount, 2, '.', '');
$qr_payload = "upi://pay?pa={$upi_id}&pn={$merchant_name}&am={$qr_payment_amount}&cu=INR";
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_payload);
?>

<style>
    /* ========== PAYMENT PAGE STYLING ========== */

    .payment-header {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                    url('https://images.unsplash.com/photo-1495474472645-4d71bcdd2016?w=1200&h=300&fit=crop');
        background-size: cover;
        background-position: center;
        padding: 80px 0;
        text-align: center;
        color: white;
        margin-top: -75px;
        padding-top: 130px;
    }

    .payment-header h1 {
        font-size: 45px;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
    }

    /* Payment Container */
    .payment-container {
        padding: 60px 0;
        background: #fff;
    }

    .payment-wrapper {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    /* Progress Bar */
    .progress-section {
        margin-bottom: 40px;
    }

    .progress-steps {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 20px;
    }

    .progress-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #d2691e;
        z-index: -1;
    }

    .step {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        background: #f9f7f4;
        border: 3px solid #d2691e;
        border-radius: 50%;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #5c4033;
        transition: all 0.3s ease;
    }

    .step.active .step-circle {
        background: #d2691e;
        color: white;
    }

    .step-label {
        font-size: 13px;
        color: #666;
        font-weight: 600;
    }

    /* Payment Methods */
    .payment-methods-section {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .section-title {
        font-size: 20px;
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 20px;
        border-bottom: 2px solid #d2691e;
        padding-bottom: 10px;
    }

    .payment-methods {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .payment-option {
        background: white;
        padding: 20px;
        border: 2px solid #e8e4d8;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .payment-option:hover {
        border-color: #d2691e;
        box-shadow: 0 4px 12px rgba(139, 69, 19, 0.15);
    }

    .payment-option.selected {
        border-color: #d2691e;
        background: rgba(210, 105, 30, 0.05);
    }

    .payment-icon {
        font-size: 35px;
        margin-bottom: 10px;
    }

    .payment-label {
        color: #5c4033;
        font-weight: 600;
        font-size: 14px;
    }

    /* Card Form */
    .card-form {
        background: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: none;
    }

    .card-form.active {
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        color: #5c4033;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e8e4d8;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #d2691e;
        box-shadow: 0 0 0 3px rgba(210, 105, 30, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    /* Delivery Info */
    .delivery-section {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .delivery-options {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .delivery-option {
        flex: 1;
        padding: 15px;
        background: white;
        border: 2px solid #e8e4d8;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s ease;
    }

    .delivery-option:hover {
        border-color: #d2691e;
    }

    .delivery-option.selected {
        border-color: #d2691e;
        background: rgba(210, 105, 30, 0.05);
    }

    .delivery-title {
        color: #5c4033;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .delivery-time {
        color: #d2691e;
        font-size: 12px;
        font-weight: 600;
    }

    /* Order Summary Sidebar */
    .order-summary-sidebar {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        height: fit-content;
        position: sticky;
        top: 100px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e8e4d8;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .item-detail {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        color: #666;
    }

    .item-detail strong {
        color: #5c4033;
    }

    .summary-total {
        background: white;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
    }

    .total-label {
        color: #666;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .total-amount {
        color: #8b4513;
        font-size: 28px;
        font-weight: 700;
    }

    /* Place Order Button */
    .place-order-btn {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #8b4513, #d2691e);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
    }

    .place-order-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        box-shadow: 0 6px 16px rgba(139, 69, 19, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .payment-wrapper {
            grid-template-columns: 1fr;
        }

        .order-summary-sidebar {
            position: static;
        }

        .payment-methods {
            grid-template-columns: 1fr;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .delivery-options {
            flex-flow: wrap;
        }
    }
</style>
<br>
<br>
<br>
<!-- Payment Header -->
<div class="payment-header">    <br>
<br>
<br>
<br>
    <h1>💳 Checkout</h1>
    <p style="margin: 10px 0 0 0; font-size: 16px;">Complete your order securely</p>
</div>

<!-- Payment Content -->
<section class="payment-container">
    <div class="container">

        <div class="payment-wrapper">
            <!-- Payment Form -->
            <div class="payment-form-section">
                <!-- Progress -->
                <div class="progress-section">
                    <div class="progress-steps">
                        <div class="step active">
                            <div class="step-circle">1</div>
                            <div class="step-label">Delivery</div>
                        </div>
                        <div class="step active">
                            <div class="step-circle">2</div>
                            <div class="step-label">Payment</div>
                        </div>
                        <div class="step">
                            <div class="step-circle">3</div>
                            <div class="step-label">Confirm</div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div class="delivery-section">
                    <h3 class="section-title">📦 Delivery Option</h3>
                    <form id="paymentForm" method="POST">
                    <div class="delivery-options">
                        <div class="delivery-option selected" onclick="selectDelivery(this, 'Delivery')">
                            <input type="hidden" name="delivery_type" id="delivery_type" value="Delivery">
                            <div class="delivery-title">🚚 Delivery</div>
                            <div class="delivery-time">30-45 mins</div>
                        </div>
                        <div class="delivery-option" onclick="selectDelivery(this, 'Pickup')">
                            <div class="delivery-title">🏪 Pickup</div>
                            <div class="delivery-time">10-15 mins</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Delivery Address</label>
                        <textarea class="form-input" name="delivery_address" rows="3" placeholder="Enter your delivery address" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Special Instructions (Optional)</label>
                        <textarea class="form-input" name="special_instructions" rows="2" placeholder="E.g., Ring doorbell twice, etc."></textarea>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="payment-methods-section">
                    <h3 class="section-title">💳 Payment Method</h3>
                    <div class="payment-methods">
                        <div class="payment-option selected" onclick="selectPayment(this, 'card')">
                            <div class="payment-icon">💳</div>
                            <div class="payment-label">Credit Card</div>
                        </div>
                        <div class="payment-option" onclick="selectPayment(this, 'qr')">
                            <div class="payment-icon">📲</div>
                            <div class="payment-label">Scan QR</div>
                        </div>
                        <div class="payment-option" onclick="selectPayment(this, 'cod')">
                            <div class="payment-icon">💵</div>
                            <div class="payment-label">Cash on Delivery</div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                </div>

                <!-- Card Form -->
                <div class="card-form active">
                    <div class="form-group">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-input" placeholder="John Doe">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-input" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-input" placeholder="MM/YY">
                        </div>
                        <div class="form-group">
                            <label class="form-label">CVV</label>
                            <input type="text" class="form-input" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>

                <!-- QR Scan Form -->
                <div class="card-form">
                    <div class="form-group">
                        <label class="form-label">Scan QR Code</label>
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 14px;">
                            <img src="<?php echo htmlspecialchars($qr_image_url); ?>" alt="Scan QR code to pay" style="max-width: 260px; width: 100%; border: 2px solid #e8e4d8; border-radius: 14px; background: #fff;">
                            <p style="margin: 0; font-size: 14px; color: #333; text-align: center;">Scan this QR code with your UPI app and complete payment of <?php echo getCurrencySymbol(); ?><?php echo number_format($total_amount, 2); ?>.</p>
                            <p style="margin: 0; font-size: 14px; color: #5c4033; text-align: center;">UPI ID: <strong><?php echo htmlspecialchars($upi_id); ?></strong></p>
                        </div>
                    </div>
                </div>

                <!-- COD Form -->
                <div class="card-form">
                    <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; text-align: center;">
                        <p style="color: #2e7d32; font-weight: 600; margin: 0;">✓ Pay when your order arrives</p>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="delivery-section" style="margin-top: 30px;">
                    <h3 class="section-title">📍 Billing Address</h3>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" checked>
                            <span>Same as delivery address</span>
                        </label>
                    </div>
                </div>
                    </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary-sidebar">
                <h3 class="section-title" style="margin-bottom: 20px;">📋 Order Summary</h3>

                <?php foreach ($cart_items as $item): ?>
                <div class="summary-item">
                    <div>
                        <div class="item-detail"><strong><?php echo htmlspecialchars($item['name']); ?> (x<?php echo intval($item['quantity']); ?>)</strong></div>
                        <div class="item-detail"><?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($item['price']), 2); ?> × <?php echo intval($item['quantity']); ?></div>
                    </div>
                    <div><strong><?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($item['price']) * intval($item['quantity']), 2); ?></strong></div>
                </div>
                <?php endforeach; ?>

                <div class="summary-item">
                    <strong>Subtotal:</strong>
                    <strong><?php echo getCurrencySymbol(); ?><?php echo number_format($subtotal, 2); ?></strong>
                </div>

                <div class="summary-item">
                    <strong>Delivery Charge:</strong>
                    <strong id="delivery-charge"><?php echo getCurrencySymbol(); ?><?php echo number_format($delivery_charge, 2); ?></strong>
                </div>

                <div class="summary-item">
                    <strong>Tax (10%):</strong>
                    <strong><?php echo getCurrencySymbol(); ?><?php echo number_format($tax, 2); ?></strong>
                </div>

                <!-- Discount Code Section -->
                <div style="background: linear-gradient(135deg, #fff8f0, #fffacd); padding: 15px; border-radius: 10px; margin: 15px 0; border: 2px dashed #d2691e;">
                    <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                        <input 
                            type="text" 
                            id="discount_code_input" 
                            placeholder="Enter discount code" 
                            style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;"
                            value="<?php echo htmlspecialchars($discount_code); ?>"
                        >
                        <button 
                            type="button" 
                            class="apply-discount-btn"
                            onclick="applyDiscount()"
                            style="padding: 10px 20px; background: #8b4513; color: white; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                        >
                            Apply
                        </button>
                    </div>
                    <div id="discount-message">
                        <?php if (!empty($discount_message)): ?>
                            <div style="color: <?php echo ($discount_type === 'success') ? '#28a745' : '#d32f2f'; ?>; font-size: 13px; font-weight: 600;">
                                <?php echo $discount_message; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="summary-item" style="border-bottom: 2px solid #d2691e; padding-bottom: 15px;">
                    <strong>Discount:</strong>
                    <strong style="color: #28a745;" id="discount-amount">-<?php echo getCurrencySymbol(); ?><?php echo number_format($discount_display, 2); ?></strong>
                </div>

                <div class="summary-total">
                    <div class="total-label">Total Amount</div>
                    <div class="total-amount" id="total-amount"><?php echo getCurrencySymbol(); ?><?php echo number_format($total_amount, 2); ?></div>
                </div>

                <button class="place-order-btn" onclick="submitPaymentForm()">Place Order</button>

                <div style="text-align: center; margin-top: 15px; font-size: 12px; color: #999;">
                    🔒 Your payment is secure and encrypted
                </div>
            </div>
        </div>

    </div>
</section>


<?php include 'footer.php'; ?>
<?php ob_end_flush(); ?>

<script>
function selectDelivery(element, type) {
    // Remove selected class from all delivery options
    document.querySelectorAll('.delivery-options .delivery-option').forEach(el => {
        el.classList.remove('selected');
    });
    // Add selected class to clicked element
    element.classList.add('selected');
    // Update hidden input
    document.getElementById('delivery_type').value = type;
    
    // Update delivery charge based on type
    const chargeElement = document.getElementById('delivery-charge');
    const totalElement = document.getElementById('total-amount');
    const subtotal = <?php echo $subtotal; ?>;
    const tax = <?php echo $tax; ?>;
    
    let deliveryCharge = 0;
    if (type === 'Delivery') {
        deliveryCharge = 2.00;
    }
    
    const newTotal = subtotal + tax + deliveryCharge;
    chargeElement.textContent = '$' + deliveryCharge.toFixed(2);
    totalElement.textContent = '$' + newTotal.toFixed(2);
}

function selectPayment(element, method) {
    // Remove selected class from all payment options
    document.querySelectorAll('.payment-methods .payment-option').forEach(el => {
        el.classList.remove('selected');
    });
    // Add selected class to clicked element
    element.classList.add('selected');
    // Update hidden input
    document.getElementById('payment_method').value = method;
    
    // Show/hide form based on payment method
    document.querySelectorAll('.card-form').forEach(form => {
        form.classList.remove('active');
    });
    
    const formIndex = Array.from(document.querySelectorAll('.payment-option')).indexOf(element);
    document.querySelectorAll('.card-form')[formIndex].classList.add('active');
}

function submitPaymentForm() {
    const form = document.getElementById('paymentForm');
    const deliveryAddress = form.querySelector('[name="delivery_address"]').value.trim();
    
    if (!deliveryAddress) {
        alert('Please enter a delivery address!');
        return;
    }
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'place_order';
    input.value = '1';
    form.appendChild(input);
    
    form.submit();
}

function applyDiscount() {
    const discountCode = document.getElementById('discount_code_input').value.trim();
    const messageDiv = document.getElementById('discount-message');
    
    if (!discountCode) {
        messageDiv.innerHTML = '<div style="color: #d32f2f; font-size: 12px; font-weight: 600;">Please enter a discount code</div>';
        return;
    }
    
    // Create a form to submit discount code
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const codeInput = document.createElement('input');
    codeInput.type = 'hidden';
    codeInput.name = 'discount_code';
    codeInput.value = discountCode;
    
    const validateInput = document.createElement('input');
    validateInput.type = 'hidden';
    validateInput.name = 'validate_discount';
    validateInput.value = '1';
    
    form.appendChild(codeInput);
    form.appendChild(validateInput);
    document.body.appendChild(form);
    form.submit();
}

// Initialize delivery type
document.querySelectorAll('.delivery-options .delivery-option').forEach((el, index) => {
    if (index === 0) {
        el.onclick = () => selectDelivery(el, 'Delivery');
    } else {
        el.onclick = () => selectDelivery(el, 'Pickup');
    }
});
</script>


