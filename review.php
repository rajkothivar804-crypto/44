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

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Create reviews table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at DESC)
)";
mysqli_query($conn, $create_table);

$message = '';
$alert_type = '';
$user_id = intval($_SESSION['user_id']);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $name = sanitize_input($_POST['name'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitize_input($_POST['comment'] ?? '');

    // Validation
    if (empty($name)) {
        $message = 'Please enter your name.';
        $alert_type = 'danger';
    } elseif ($rating < 1 || $rating > 5) {
        $message = 'Please select a rating between 1 and 5 stars.';
        $alert_type = 'danger';
    } elseif (empty($comment)) {
        $message = 'Please enter your review.';
        $alert_type = 'danger';
    } else {
        // Insert review into database
        $insert_sql = "INSERT INTO reviews (user_id, customer_name, rating, review_text) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        if (!$stmt) {
            $message = 'Error: ' . mysqli_error($conn);
            $alert_type = 'danger';
        } else {
            mysqli_stmt_bind_param($stmt, 'isss', $user_id, $name, $rating, $comment);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = '⭐ Thank you for your review! We appreciate your feedback.';
                $alert_type = 'success';
            } else {
                $message = 'Error submitting review: ' . mysqli_stmt_error($stmt);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch recent reviews from database
$reviews_sql = "SELECT customer_name, rating, review_text, created_at FROM reviews ORDER BY created_at DESC LIMIT 10";
$reviews_result = mysqli_query($conn, $reviews_sql);
$recentReviews = [];

if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
    while ($row = mysqli_fetch_assoc($reviews_result)) {
        $recentReviews[] = $row;
    }
}

// Get average rating
$avg_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews";
$avg_result = mysqli_query($conn, $avg_sql);
$avg_rating = 0;
$total_reviews = 0;

if ($avg_result && mysqli_num_rows($avg_result) > 0) {
    $avg_data = mysqli_fetch_assoc($avg_result);
    $avg_rating = round($avg_data['avg_rating'] ?? 0, 1);
    $total_reviews = $avg_data['total_reviews'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - Cafe Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .review-hero { background: linear-gradient(135deg, #fffacd, #f5f5dc); padding: 90px 0 60px; margin-top: 70px; text-align: center; }
        .review-card { border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.12); }
        .review-card h3 { color: #5c4033; }
        .review-card textarea { resize: vertical; }
        .star-rating { font-size: 24px; letter-spacing: 10px; }
        .star { cursor: pointer; color: #ddd; transition: color 0.2s; }
        .star.active { color: #ffc107; }
        .star:hover, .star.hover { color: #ffc107; }
        .rating-display { color: #ffc107; font-size: 20px; }
        .stats-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .review-item { padding: 15px; border-left: 4px solid #d2691e; margin-bottom: 15px; background: #f9f7f4; border-radius: 4px; }
        .review-rating { color: #ffc107; margin: 5px 0; }
        .review-date { font-size: 12px; color: #999; }
    </style>
</head>
<body>

<section class="review-hero">
    <div class="container">
        <h1 class="display-4" style="color:#5c4033; font-weight:700;">Share Your Experience</h1>
        <p class="lead" style="color:#6b4b3a;">Your review helps us improve and guides our customers.</p>
    </div>
</section>

<!-- Statistics Section -->
<div class="container" style="margin-top: 40px;">
    <div class="row">
        <div class="col-md-4">
            <div class="stats-card">
                <h2 style="color: #d2691e;">⭐ <?php echo $avg_rating; ?></h2>
                <p class="mb-0" style="color: #666;">Average Rating</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h2 style="color: #d2691e;"><?php echo $total_reviews; ?></h2>
                <p class="mb-0" style="color: #666;">Total Reviews</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h2 style="color: #d2691e;">100%</h2>
                <p class="mb-0" style="color: #666;">Verified Reviews</p>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="p-4 bg-white review-card">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $alert_type; ?>" role="alert"><?php echo $message; ?></div>
                <?php endif; ?>

                <h3 class="mb-4">Write Your Review</h3>
                <form method="POST" action="review.php">
                    <div class="form-group">
                        <label for="name" class="font-weight-bold">Your Name *</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Jane Doe" required>
                    </div>

                    <div class="form-group">
                        <label for="rating" class="font-weight-bold">Rating *</label>
                        <div class="star-rating">
                            <span class="star" onclick="setRating(1)" onmouseover="hoverRating(1)" onmouseout="resetRating()">★</span>
                            <span class="star" onclick="setRating(2)" onmouseover="hoverRating(2)" onmouseout="resetRating()">★</span>
                            <span class="star" onclick="setRating(3)" onmouseover="hoverRating(3)" onmouseout="resetRating()">★</span>
                            <span class="star" onclick="setRating(4)" onmouseover="hoverRating(4)" onmouseout="resetRating()">★</span>
                            <span class="star" onclick="setRating(5)" onmouseover="hoverRating(5)" onmouseout="resetRating()">★</span>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="comment" class="font-weight-bold">Your Review *</label>
                        <textarea class="form-control" id="comment" name="comment" rows="5" placeholder="Tell us what you enjoyed or what we can improve." required></textarea>
                        <small class="text-muted">Minimum 10 characters</small>
                    </div>

                    <button type="submit" name="submit_review" class="btn btn-warning btn-block btn-lg font-weight-bold">Submit Review</button>
                </form>

                <?php if (!empty($recentReviews)): ?>
                    <hr class="my-5">
                    <h4 style="color:#5c4033; margin-bottom: 20px;">✨ Recent Reviews (<?php echo count($recentReviews); ?>)</h4>
                    
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="review-item">
                            <strong style="color: #5c4033;"><?php echo sanitize_input($review['customer_name']); ?></strong>
                            <div class="review-rating">
                                <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                <span style="color: #666; font-size: 14px;"><?php echo $review['rating']; ?>/5</span>
                            </div>
                            <p class="mb-2" style="color: #333;"><?php echo nl2br(sanitize_input($review['review_text'])); ?></p>
                            <div class="review-date">
                                📅 <?php echo date('M d, Y h:i A', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <hr class="my-5">
                    <p style="text-align: center; color: #999;">No reviews yet. Be the first to share your experience!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
let currentRating = 0;

function setRating(value) {
    currentRating = value;
    document.getElementById('rating').value = value;
    updateStars();
}

function hoverRating(value) {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < value) {
            star.classList.add('hover');
        } else {
            star.classList.remove('hover');
        }
    });
}

function resetRating() {
    updateStars();
}

function updateStars() {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < currentRating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
        star.classList.remove('hover');
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const rating = document.getElementById('rating').value;
    const comment = document.getElementById('comment').value.trim();

    if (!name || rating == 0 || comment.length < 10) {
        e.preventDefault();
        if (!name) alert('Please enter your name');
        else if (rating == 0) alert('Please select a rating');
        else alert('Review must be at least 10 characters');
    }
});
</script>