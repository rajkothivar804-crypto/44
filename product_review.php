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

$user_id = intval($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Customer';

// Fetch review header settings from database
$review_settings = [
    'title' => '⭐ Product Reviews',
    'subtitle' => 'Share your experience with our products',
    'image' => 'https://images.unsplash.com/photo-1495474472645-4d71bcdd2016?w=1200&h=300&fit=crop',
    'title_color' => '#ffffff',
    'subtitle_color' => '#ffffff',
    'title_size' => '45px'
];

$review_settings_sql = "SELECT setting_key, setting_value FROM cafe_settings WHERE setting_key IN ('review_header_title', 'review_header_subtitle', 'review_header_image', 'review_header_title_color', 'review_header_subtitle_color', 'review_header_title_size')";
$review_settings_result = mysqli_query($conn, $review_settings_sql);
if ($review_settings_result && mysqli_num_rows($review_settings_result) > 0) {
    while ($row = mysqli_fetch_assoc($review_settings_result)) {
        $key = str_replace('review_header_', '', $row['setting_key']);
        $review_settings[$key] = $row['setting_value'];
    }
}

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Create reviews table if not exists
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

$alert_message = '';
$alert_type = '';

// Handle submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $review_text = sanitize_input($_POST['review_text']);

    if ($rating < 1 || $rating > 5) {
        $alert_message = '❌ Please select a rating between 1-5 stars';
        $alert_type = 'danger';
    } elseif (strlen($review_text) < 10) {
        $alert_message = '❌ Review must be at least 10 characters long';
        $alert_type = 'danger';
    } else {
        // Check if product exists and user can review it
        $check_product_sql = "SELECT p.id FROM products p 
                             JOIN order_items oi ON p.id = oi.product_id 
                             JOIN orders o ON oi.order_id = o.id 
                             WHERE p.id = ? AND o.user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_product_sql);
        mysqli_stmt_bind_param($check_stmt, 'ii', $product_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $product_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($product_result) > 0) {
            // Insert review
            $insert_sql = "INSERT INTO product_reviews (product_id, user_id, customer_name, rating, review_text) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'iisis', $product_id, $user_id, $user_name, $rating, $review_text);

            if (mysqli_stmt_execute($insert_stmt)) {
                $alert_message = '✅ Thank you! Your review has been posted successfully';
                $alert_type = 'success';
                $_POST = []; // Clear form
            } else {
                $alert_message = '❌ Error posting review: ' . mysqli_stmt_error($insert_stmt);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($insert_stmt);
        } else {
            $alert_message = '❌ Product not found';
            $alert_type = 'danger';
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Fetch all products for selection
$products_sql = "SELECT id, product_name FROM products ORDER BY product_name ASC";
$products_result = mysqli_query($conn, $products_sql);
$products = [];
if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
}

// Fetch products user can review (from any orders)
$eligible_products_sql = "SELECT DISTINCT p.id, p.product_name 
                         FROM products p 
                         JOIN order_items oi ON p.id = oi.product_id 
                         JOIN orders o ON oi.order_id = o.id 
                         WHERE o.user_id = ? 
                         ORDER BY p.product_name ASC";
$eligible_stmt = mysqli_prepare($conn, $eligible_products_sql);
mysqli_stmt_bind_param($eligible_stmt, 'i', $user_id);
mysqli_stmt_execute($eligible_stmt);
$eligible_products_result = mysqli_stmt_get_result($eligible_stmt);
$eligible_products = [];
if ($eligible_products_result && mysqli_num_rows($eligible_products_result) > 0) {
    while ($product = mysqli_fetch_assoc($eligible_products_result)) {
        $eligible_products[] = $product;
    }
}
mysqli_stmt_close($eligible_stmt);

// Fetch user reviews
$user_reviews_sql = "SELECT pr.id, pr.rating, pr.review_text, pr.created_at, p.product_name 
                     FROM product_reviews pr 
                     JOIN products p ON pr.product_id = p.id 
                     WHERE pr.user_id = ? 
                     ORDER BY pr.created_at DESC";
$user_reviews_stmt = mysqli_prepare($conn, $user_reviews_sql);
mysqli_stmt_bind_param($user_reviews_stmt, 'i', $user_id);
mysqli_stmt_execute($user_reviews_stmt);
$user_reviews_result = mysqli_stmt_get_result($user_reviews_stmt);
$user_reviews = [];
if ($user_reviews_result && mysqli_num_rows($user_reviews_result) > 0) {
    while ($review = mysqli_fetch_assoc($user_reviews_result)) {
        $user_reviews[] = $review;
    }
}
mysqli_stmt_close($user_reviews_stmt);

// Get average ratings for products
$avg_ratings_sql = "SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count 
                    FROM product_reviews 
                    GROUP BY product_id";
$avg_ratings_result = mysqli_query($conn, $avg_ratings_sql);
$avg_ratings = [];
if ($avg_ratings_result && mysqli_num_rows($avg_ratings_result) > 0) {
    while ($row = mysqli_fetch_assoc($avg_ratings_result)) {
        $avg_ratings[$row['product_id']] = $row;
    }
}

function generate_stars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '★' : '☆';
    }
    return $stars;
}
?>

<style>
    .review-header {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                    url('<?php echo htmlspecialchars($review_settings['image']); ?>');
        background-size: cover;
        background-position: center;
        padding: 80px 0;
        text-align: center;
        color: white;
        margin-top: -75px;
        padding-top: 130px;
    }

    .review-header h1 {
        font-size: <?php echo htmlspecialchars($review_settings['title_size']); ?>;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        color: <?php echo htmlspecialchars($review_settings['title_color']); ?>;
    }

    .review-header p {
        color: <?php echo htmlspecialchars($review_settings['subtitle_color']); ?>;
    }

    .review-container {
        padding: 60px 0;
        background: #fff;
    }

    .review-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 50px;
    }

    .review-form-box {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .form-section-title {
        color: #5c4033;
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 20px;
        border-bottom: 2px solid #d2691e;
        padding-bottom: 10px;
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

    .form-control {
        width: 100%;
        padding: 12px;
        border: 2px solid #e8e4d8;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #d2691e;
        box-shadow: 0 0 0 3px rgba(210, 105, 30, 0.1);
    }

    .star-rating {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }

    .star-btn {
        font-size: 40px;
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #ddd;
    }

    .star-btn:hover,
    .star-btn.active {
        color: #ffc107;
        transform: scale(1.2);
    }

    .submit-btn {
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
        margin-top: 15px;
    }

    .submit-btn:hover {
        background: linear-gradient(135deg, #d2691e, #8b4513);
        box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
    }

    .my-reviews {
        background: #f9f7f4;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .review-item {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
        border-left: 4px solid #d2691e;
    }

    .review-header-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .review-product-name {
        color: #5c4033;
        font-weight: 700;
        font-size: 16px;
    }

    .review-rating {
        color: #ffc107;
        font-size: 18px;
        font-weight: 700;
    }

    .review-date {
        color: #999;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .review-text {
        color: #666;
        line-height: 1.6;
    }

    .product-stats {
        padding: 15px;
        background: white;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .product-stat-name {
        font-weight: 600;
        color: #5c4033;
        margin-bottom: 5px;
    }

    .product-stat-rating {
        color: #ffc107;
        font-size: 16px;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        animation: slideInUp 0.5s ease;
    }

    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        border-left: 4px solid #28a745;
        color: #155724;
    }

    .alert-danger {
        background: rgba(220, 53, 69, 0.1);
        border-left: 4px solid #dc3545;
        color: #721c24;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .empty-message {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    @media (max-width: 768px) {
        .review-section {
            grid-template-columns: 1fr;
        }
    }
</style>

<br><br><br>

<!-- Review Header -->
<div class="review-header">
    <br><br><br><br>
    <h1><?php echo htmlspecialchars($review_settings['title']); ?></h1>
    <p style="margin: 10px 0 0 0; font-size: 16px;"><?php echo htmlspecialchars($review_settings['subtitle']); ?></p>
</div>

<!-- Review Content -->
<section class="review-container">
    <div class="container">
        <!-- Alert Messages -->
        <?php if (!empty($alert_message)): ?>
            <div class="alert alert-<?php echo $alert_type; ?>">
                <?php echo $alert_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($eligible_products)): ?>
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle"></i> Review Policy</h4>
                <p>You can write reviews for products you've ordered from our menu.</p>
                <p><strong>How to write a review:</strong></p>
                <ol>
                    <li>Place an order from our menu</li>
                    <li>Return to this page to share your experience</li>
                    <li>Select the product you ordered and write your review</li>
                </ol>
                <p><a href="menu.php" class="btn btn-primary btn-sm">Browse Menu & Order</a></p>
            </div>
        <?php endif; ?>

        <div class="review-section">
            <!-- Review Form -->
            <div class="review-form-box">
                <h3 class="form-section-title">✍️ Write a Review</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Select Product *</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">-- Choose a product --</option>
                            <?php foreach ($eligible_products as $product): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                    <?php if (isset($avg_ratings[$product['id']])): ?>
                                        (<?php echo generate_stars(round($avg_ratings[$product['id']]['avg_rating'])); ?> <?php echo number_format($avg_ratings[$product['id']]['avg_rating'], 1); ?>/5)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($eligible_products)): ?>
                            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 10px; margin-top: 10px;">
                                <strong style="color: #856404;">No products available for review</strong><br>
                                <small style="color: #856404;">Place an order first to be able to write reviews.</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Your Rating *</label>
                        <div class="star-rating" id="starRating">
                            <button type="button" class="star-btn" data-rating="1" title="1 Star">★</button>
                            <button type="button" class="star-btn" data-rating="2" title="2 Stars">★</button>
                            <button type="button" class="star-btn" data-rating="3" title="3 Stars">★</button>
                            <button type="button" class="star-btn" data-rating="4" title="4 Stars">★</button>
                            <button type="button" class="star-btn" data-rating="5" title="5 Stars">★</button>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Your Review *</label>
                        <textarea name="review_text" class="form-control" rows="5" placeholder="Share your experience (minimum 10 characters)" required></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="submit-btn">Submit Review</button>
                </form>
            </div>

            <!-- My Reviews -->
            <div class="my-reviews">
                <h3 class="form-section-title">📝 Your Reviews</h3>
                <?php if (empty($user_reviews)): ?>
                    <div class="empty-message">
                        <p>You haven't written any reviews yet.</p>
                        <p style="font-size: 12px;">Reviews can be written for any products you've ordered.</p>
                        <p style="font-size: 12px;"><a href="menu.php">Browse our menu</a> to place an order and share your experience.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($user_reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header-info">
                                <span class="review-product-name"><?php echo htmlspecialchars($review['product_name']); ?></span>
                                <span class="review-rating"><?php echo generate_stars($review['rating']); ?> <?php echo $review['rating']; ?>/5</span>
                            </div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                            <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Ratings Overview -->
        <div style="margin-top: 50px;">
            <h3 class="form-section-title">📊 Product Ratings Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <?php foreach ($products as $product): ?>
                    <div class="product-stats">
                        <div class="product-stat-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                        <?php if (isset($avg_ratings[$product['id']])): ?>
                            <div class="product-stat-rating">
                                <?php echo generate_stars(round($avg_ratings[$product['id']]['avg_rating'])); ?>
                                <span style="color: #5c4033; font-size: 14px; margin-left: 5px;">
                                    <?php echo number_format($avg_ratings[$product['id']]['avg_rating'], 1); ?>/5
                                    (<?php echo $avg_ratings[$product['id']]['review_count']; ?> reviews)
                                </span>
                            </div>
                        <?php else: ?>
                            <div style="color: #999; font-size: 12px;">No reviews yet</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php ob_end_flush(); ?>
<?php include 'footer.php'; ?>

<script>
    // Star rating interaction
    const starButtons = document.querySelectorAll('#starRating .star-btn');
    const ratingInput = document.getElementById('ratingInput');

    starButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const rating = button.getAttribute('data-rating');
            ratingInput.value = rating;

            // Update visual state
            starButtons.forEach(btn => {
                const btnRating = btn.getAttribute('data-rating');
                if (btnRating <= rating) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        });
    });

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });
    });
</script>
