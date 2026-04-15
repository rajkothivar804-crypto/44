<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

include 'database/DB.php';

$user_id = intval($_SESSION['user_id']);
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id > 0) {
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

    // Add to wishlist
    $add_sql = "INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)
                ON DUPLICATE KEY UPDATE added_at = CURRENT_TIMESTAMP";
    if (mysqli_query($conn, $add_sql)) {
        $_SESSION['wishlist_message'] = 'Item added to wishlist!';
    } else {
        $_SESSION['wishlist_message'] = 'Item already in wishlist!';
    }
}

// Redirect to wishlist page
header('Location: wishlist.php');
exit();
?>
