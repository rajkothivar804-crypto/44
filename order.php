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
include 'otp_mailer.php';

// Ensure the orders table accepts Delivered as a valid status
$status_column_result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
if ($status_column_result && mysqli_num_rows($status_column_result) > 0) {
    $status_column = mysqli_fetch_assoc($status_column_result);
    if (strpos($status_column['Type'], "'Delivered'") === false) {
        mysqli_query($conn, "ALTER TABLE orders MODIFY COLUMN status ENUM('Pending','Processing','Ready','Completed','Cancelled','Delivered') DEFAULT 'Pending'");
    }
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

// Get user ID from session
$user_id = intval($_SESSION['user_id']);

$user_email = '';
$user_name = '';
$user_contact_sql = "SELECT fullname, email FROM users WHERE id = ? LIMIT 1";
$user_contact_stmt = mysqli_prepare($conn, $user_contact_sql);
if ($user_contact_stmt) {
    mysqli_stmt_bind_param($user_contact_stmt, 'i', $user_id);
    mysqli_stmt_execute($user_contact_stmt);
    $user_contact_result = mysqli_stmt_get_result($user_contact_stmt);
    if ($user_contact_result && mysqli_num_rows($user_contact_result) > 0) {
        $user_contact = mysqli_fetch_assoc($user_contact_result);
        $user_name = $user_contact['fullname'] ?: 'Customer';
        $user_email = $user_contact['email'];
    }
    mysqli_stmt_close($user_contact_stmt);
}

$status_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'], $_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    if ($order_id > 0) {
        $check_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_status);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!empty($current_status) && $current_status !== 'Delivered' && $current_status !== 'Cancelled') {
            $update_sql = "UPDATE orders SET status = 'Delivered', updated_at = NOW() WHERE id = ? AND user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'ii', $order_id, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $status_message = 'Order #' . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ' has been marked as Delivered.';
                if (!empty($user_email)) {
                    $subject = 'Order Delivered: #' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
                    $bodyHtml = "<html><body>" .
                        "<h2>Your order has been delivered!</h2>" .
                        "<p>Hello " . htmlspecialchars($user_name) . ",</p>" .
                        "<p>Your order <strong>" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . "</strong> has been marked as <strong>Delivered</strong>. We hope you enjoy your order!</p>" .
                        "<p>If you have any questions, feel free to contact our support team.</p>" .
                        "<br><p>Best regards,<br>Cafe Menu Team</p>" .
                        "</body></html>";
                    $altBody = "Hello " . $user_name . ",\n\nYour order " . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " has been delivered.\n\nBest regards,\nCafe Menu Team";
                    $emailResult = sendOrderEmail($user_email, $user_name, $subject, $bodyHtml, $altBody);
                    if (!$emailResult['success']) {
                        error_log($emailResult['message']);
                    }
                }
            } else {
                $status_message = 'Unable to update order status. Please try again.';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $status_message = 'This order cannot be marked delivered.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'], $_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    if ($order_id > 0) {
        $check_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_status);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!empty($current_status) && $current_status !== 'Delivered' && $current_status !== 'Cancelled') {
            $update_sql = "UPDATE orders SET status = 'Cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'ii', $order_id, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $status_message = 'Order #' . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ' has been cancelled.';
                if (!empty($user_email)) {
                    $subject = 'Order Cancelled: #' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
                    $bodyHtml = "<html><body>" .
                        "<h2>Your order has been cancelled</h2>" .
                        "<p>Hello " . htmlspecialchars($user_name) . ",</p>" .
                        "<p>Your order <strong>" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . "</strong> has been <strong>Cancelled</strong>. If this was a mistake, please contact support.</p>" .
                        "<p>We are sorry for any inconvenience.</p>" .
                        "<br><p>Best regards,<br>Cafe Menu Team</p>" .
                        "</body></html>";
                    $altBody = "Hello " . $user_name . ",\n\nYour order " . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " has been cancelled.\n\nBest regards,\nCafe Menu Team";
                    $emailResult = sendOrderEmail($user_email, $user_name, $subject, $bodyHtml, $altBody);
                    if (!$emailResult['success']) {
                        error_log($emailResult['message']);
                    }
                }
            } else {
                $status_message = 'Unable to cancel order. Please try again.';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $status_message = 'This order cannot be cancelled.';
        }
    }
}

// Fetch all orders for the user
$orders_sql = "SELECT id, created_at, total_amount, status FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$orders_result = mysqli_query($conn, $orders_sql);
$user_orders = [];

if ($orders_result && mysqli_num_rows($orders_result) > 0) {
    while ($order = mysqli_fetch_assoc($orders_result)) {
        $user_orders[] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Cafe Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #fafafa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-title {
            color: #5c4033;
            font-weight: 700;
            margin-bottom: 30px;
            margin-top: 20px;
        }
        .order-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .order-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .order-id {
            font-weight: 600;
            color: #5c4033;
            font-size: 18px;
        }
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-delivered {
            background-color: #c3e6cb;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .order-items {
            margin: 15px 0;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-details {
            display: flex;
            flex-direction: column;
        }
        .item-name {
            font-weight: 500;
            color: #333;
        }
        .item-qty {
            font-size: 12px;
            color: #666;
        }
        .item-price {
            font-weight: 600;
            color: #8b4513;
        }
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #8b4513;
        }
        .order-total {
            font-size: 18px;
            font-weight: 700;
            color: #5c4033;
        }
        .btn-custom {
            background-color: #8b4513;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-custom:hover {
            background-color: #5c4033;
            color: white;
            text-decoration: none;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state-icon {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .empty-state-text {
            color: #999;
            font-size: 18px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body><br>
<br>
<br>
    <div class="container" style="margin-top: 30px; margin-bottom: 30px;">    <br>
<br>
<br>
<br>
        <?php if (!empty($status_message)): ?>
            <div class="alert alert-success" role="alert" style="margin-top: 20px;">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <h1 class="page-title"><i class="fas fa-receipt"></i> My Orders</h1>
        
        <?php if (empty($user_orders)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
            <div class="empty-state-text">You haven't placed any orders yet</div>
            <a href="menu.php" class="btn-custom">Browse Menu</a>
        </div>
        
        <?php else: ?>
        <!-- Orders List -->
        <?php foreach ($user_orders as $order): ?>
            <?php
            // Get order items for this order
            $order_id = intval($order['id']);
            $items_sql = "SELECT oi.*, p.product_name as item_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id";
            $items_result = mysqli_query($conn, $items_sql);
            $order_items = [];
            
            if ($items_result && mysqli_num_rows($items_result) > 0) {
                while ($item = mysqli_fetch_assoc($items_result)) {
                    $order_items[] = $item;
                }
            }
            
            // Determine status badge class
            $status_class = 'status-pending';
            $status_icon = 'fa-clock';
            if ($order['status'] == 'Processing') {
                $status_class = 'status-processing';
                $status_icon = 'fa-hourglass-half';
            } elseif ($order['status'] == 'Completed' || $order['status'] == 'Delivered') {
                $status_class = 'status-completed';
                $status_icon = 'fa-check-circle';
            } elseif ($order['status'] == 'Cancelled') {
                $status_class = 'status-cancelled';
                $status_icon = 'fa-times-circle';
            }
            ?>
        <div class="order-card">
            <div class="order-header">
                <div class="order-id">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                <span class="order-status <?php echo $status_class; ?>">
                    <i class="fas <?php echo $status_icon; ?>"></i> <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </div>
            
            <div class="order-items">
                <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <div class="item-details">
                        <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                        <span class="item-qty">Qty: <?php echo intval($item['quantity']); ?></span>
                    </div>
                    <span class="item-price"><?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($item['subtotal']), 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-footer">
                <div>
                    <div style="font-size: 12px; color: #999;">Ordered: <?php echo date('M d, Y @ h:i A', strtotime($order['created_at'])); ?></div>
                    <div class="order-total" style="margin-top: 8px;">Total: <?php echo getCurrencySymbol(); ?><?php echo number_format(floatval($order['total_amount']), 2); ?></div>
                </div>
                <div>
                    <button type="button" class="btn-custom track-btn" data-order-id="<?php echo $order['id']; ?>" data-order-status="<?php echo htmlspecialchars($order['status']); ?>" data-order-date="<?php echo htmlspecialchars(date('M d, Y @ h:i A', strtotime($order['created_at']))); ?>" data-order-total="<?php echo htmlspecialchars(getCurrencySymbol() . number_format(floatval($order['total_amount']), 2)); ?>" style="margin-right: 10px;">Track Order</button>
                    <a href="product_review.php" class="btn-custom" style="background-color: #d2691e; margin-right: 10px;">✍️ Write Review</a>
                    <a href="menu.php" class="btn-custom">Reorder</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
       
    </div>

    <div class="modal fade" id="trackModal" tabindex="-1" aria-labelledby="trackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #8b4513; color: #fff;">
                <h5 class="modal-title" id="trackModalLabel">Track Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Order ID:</strong> <span id="modalOrderId"></span></p>
                <p><strong>Order Date:</strong> <span id="modalOrderDate"></span></p>
                <p><strong>Total:</strong> <span id="modalOrderTotal"></span></p>
                <p><strong>Status:</strong> <span id="modalOrderStatus"></span></p>
                <div id="modalOrderMessage" style="margin-top: 12px;"></div>
                <div id="modalActions" style="margin-top: 15px;">
                    <form method="POST" id="deliverForm" style="display: none; margin-bottom: 10px;">
                        <input type="hidden" name="order_id" id="deliverOrderId" value="">
                        <button type="submit" name="mark_delivered" class="btn-custom" style="background-color: #28a745;">Mark Delivered</button>
                    </form>
                    <form method="POST" id="cancelForm" style="display: none;">
                        <input type="hidden" name="order_id" id="cancelOrderId" value="">
                        <button type="submit" name="cancel_order" class="btn-custom" style="background-color: #dc3545;" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var trackButtons = document.querySelectorAll('.track-btn');
        trackButtons.forEach(function(button) {
            button.addEventListener('click', function () {
                var orderId = button.getAttribute('data-order-id');
                var orderStatus = button.getAttribute('data-order-status');
                var orderDate = button.getAttribute('data-order-date');
                var orderTotal = button.getAttribute('data-order-total');

                document.getElementById('modalOrderId').textContent = '#' + orderId.padStart(5, '0');
                document.getElementById('modalOrderDate').textContent = orderDate;
                document.getElementById('modalOrderTotal').textContent = orderTotal;
                document.getElementById('modalOrderStatus').textContent = orderStatus;

                var deliverForm = document.getElementById('deliverForm');
                var deliverOrderId = document.getElementById('deliverOrderId');
                var cancelForm = document.getElementById('cancelForm');
                var cancelOrderId = document.getElementById('cancelOrderId');
                var modalMessage = document.getElementById('modalOrderMessage');

                deliverOrderId.value = orderId;
                cancelOrderId.value = orderId;
                if (orderStatus !== 'Delivered' && orderStatus !== 'Cancelled') {
                    deliverForm.style.display = 'block';
                    cancelForm.style.display = 'block';
                    modalMessage.innerHTML = '<div class="alert alert-info" role="alert">If you have received this order, click "Mark Delivered" to confirm. If you want to cancel, click "Cancel Order".</div>';
                } else {
                    deliverForm.style.display = 'none';
                    cancelForm.style.display = 'none';
                    modalMessage.innerHTML = '<div class="alert alert-secondary" role="alert">This order cannot be modified.</div>';
                }

                $('#trackModal').modal('show');
            });
        });
    });
</script>

<?php include 'footer.php'; ?>
