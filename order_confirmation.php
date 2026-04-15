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

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = intval($_SESSION['user_id']);

if ($order_id <= 0) {
    header('Location: menu.php');
    exit();
}

// Fetch order details
$order_sql = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id LIMIT 1";
$order_result = mysqli_query($conn, $order_sql);

if (!$order_result || mysqli_num_rows($order_result) === 0) {
    header('Location: menu.php');
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Fetch order items
$items_sql = "SELECT oi.*, p.product_name as item_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_sql);
$order_items = [];

if ($items_result && mysqli_num_rows($items_result) > 0) {
    while ($item = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $item;
    }
}

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += floatval($item['subtotal']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Cafe</title>
    <style>
        .confirmation-container {
            padding: 60px 0;
            min-height: calc(100vh - 200px);
        }

        .confirmation-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 0.6s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .confirmation-title {
            color: #5c4033;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .confirmation-subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .order-details {
            background: #f9f7f4;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e8e4d8;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 600;
        }

        .detail-value {
            color: #5c4033;
            font-weight: 700;
        }

        .order-items-section {
            text-align: left;
            padding: 20px 0;
        }

        .order-items-section h3 {
            color: #5c4033;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e8e4d8;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: #5c4033;
        }

        .item-details {
            font-size: 13px;
            color: #999;
        }

        .item-price {
            font-weight: 700;
            color: #d2691e;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-action {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #d2691e, #8b4513);
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }

        .btn-secondary {
            background: #e8e4d8;
            color: #5c4033;
        }

        .btn-secondary:hover {
            background: #d2691e;
            color: white;
        }

        .order-status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #d2691e;
            margin-top: 15px;
            padding-top: 15px;
        }

        .total-label {
            font-size: 18px;
            font-weight: 700;
            color: #5c4033;
        }

        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #d2691e;
        }
    </style>
</head>
<body>
    <br><br><br>
    <section class="confirmation-container">
        <div class="container">
            <div class="confirmation-card">
                <div class="success-icon">✅</div>
                <div class="order-status status-pending">Order Received</div>
                
                <h1 class="confirmation-title">Order Placed Successfully!</h1>
                <p class="confirmation-subtitle">Thank you for your order. Your food will be ready soon!</p>

                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date('M d, Y @ h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['status']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Delivery Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                    </div>
                </div>

                <div class="order-items-section">
                    <h3>📦 Order Items</h3>
                    <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div>
                            <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <div class="item-details">Qty: <?php echo intval($item['quantity']); ?> × <?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($item['price']), 2); ?></div>
                        </div>
                        <div class="item-price"><?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($item['subtotal']), 2); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="total-row">
                        <span class="total-label">Total Amount:</span>
                        <span class="total-amount"><?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($order['total_amount']), 2); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="order.php" class="btn-action btn-primary">View My Orders</a>
                    <a href="menu.php" class="btn-action btn-secondary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
