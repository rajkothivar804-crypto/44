<?php
include '../database/DB.php';

// Get recent pending/processing orders
$orders_sql = "SELECT id, customer_name, status, created_at FROM orders WHERE status IN ('Pending', 'Processing') ORDER BY created_at DESC LIMIT 5";
$orders_result = mysqli_query($conn, $orders_sql);

$notifications = array();

if ($orders_result && mysqli_num_rows($orders_result) > 0) {
    while ($row = mysqli_fetch_assoc($orders_result)) {
        $time_diff = time() - strtotime($row['created_at']);
        
        if ($time_diff < 60) {
            $time_display = "Just now";
        } elseif ($time_diff < 3600) {
            $mins = floor($time_diff / 60);
            $time_display = $mins . " min ago";
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            $time_display = $hours . " hours ago";
        } else {
            $time_display = date('M d, Y', strtotime($row['created_at']));
        }
        
        $icon = ($row['status'] === 'Pending') ? 'fas fa-hourglass-start' : 'fas fa-spinner fa-spin';
        
        $notifications[] = array(
            'title' => $row['status'] . ' Order #' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            'message' => 'From ' . htmlspecialchars($row['customer_name']),
            'time' => $time_display,
            'icon' => $icon
        );
    }
}

// Get low stock alerts
$low_stock_sql = "SELECT product_name, stock FROM products WHERE stock < 10 AND stock > 0 ORDER BY stock ASC LIMIT 3";
$low_stock_result = mysqli_query($conn, $low_stock_sql);

if ($low_stock_result && mysqli_num_rows($low_stock_result) > 0) {
    while ($row = mysqli_fetch_assoc($low_stock_result)) {
        $notifications[] = array(
            'title' => 'Low Stock Alert',
            'message' => htmlspecialchars($row['product_name']) . ' has only ' . intval($row['stock']) . ' units left',
            'time' => 'Recent',
            'icon' => 'fas fa-exclamation-triangle'
        );
    }
}

// Check for out of stock products
$out_of_stock_sql = "SELECT COUNT(*) as count FROM products WHERE stock = 0";
$out_of_stock_result = mysqli_query($conn, $out_of_stock_sql);

if ($out_of_stock_result && mysqli_num_rows($out_of_stock_result) > 0) {
    $row = mysqli_fetch_assoc($out_of_stock_result);
    if ($row['count'] > 0) {
        $notifications[] = array(
            'title' => 'Out of Stock Products',
            'message' => intval($row['count']) . ' product(s) need to be restocked',
            'time' => 'Recent',
            'icon' => 'fas fa-box-open'
        );
    }
}

// If no notifications, add a welcome message
if (count($notifications) === 0) {
    $notifications[] = array(
        'title' => 'All Good!',
        'message' => 'No pending orders or stock alerts',
        'time' => 'Now',
        'icon' => 'fas fa-check-circle'
    );
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(array(
    'notifications' => $notifications,
    'count' => count($notifications)
));
?>
