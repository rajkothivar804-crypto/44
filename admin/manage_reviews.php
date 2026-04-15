<?php
ob_start();
error_reporting(0);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin


include '../database/DB.php';
include 'header.php';

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

$response = '';
$response_type = '';

// Handle review deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    
    $delete_sql = "DELETE FROM reviews WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, 'i', $review_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $response = '✅ Review deleted successfully!';
        $response_type = 'success';
    } else {
        $response = '❌ Error deleting review: ' . mysqli_stmt_error($delete_stmt);
        $response_type = 'danger';
    }
    mysqli_stmt_close($delete_stmt);
}

// Fetch all reviews with search filter
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$reviews_sql = "SELECT id, customer_name, rating, review_text, created_at FROM reviews WHERE 1=1";

if (!empty($search_query)) {
    $reviews_sql .= " AND (customer_name LIKE ? OR review_text LIKE ?)";
}

$reviews_sql .= " ORDER BY created_at DESC";

$reviews_stmt = mysqli_prepare($conn, $reviews_sql);

if (!empty($search_query)) {
    $search_term = "%{$search_query}%";
    mysqli_stmt_bind_param($reviews_stmt, 'ss', $search_term, $search_term);
}

mysqli_stmt_execute($reviews_stmt);
$reviews_result = mysqli_stmt_get_result($reviews_stmt);
$reviews = [];

if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
    while ($row = mysqli_fetch_assoc($reviews_result)) {
        $reviews[] = $row;
    }
}
mysqli_stmt_close($reviews_stmt);

// Get statistics
$total_sql = "SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews";
$total_result = mysqli_query($conn, $total_sql);
$stats = mysqli_fetch_assoc($total_result);
$total_reviews = $stats['total'] ?? 0;
$avg_rating = round($stats['avg_rating'] ?? 0, 1);

// Get rating distribution
$distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$dist_sql = "SELECT rating, COUNT(*) as count FROM reviews GROUP BY rating";
$dist_result = mysqli_query($conn, $dist_sql);

if ($dist_result && mysqli_num_rows($dist_result) > 0) {
    while ($row = mysqli_fetch_assoc($dist_result)) {
        $distribution[$row['rating']] = $row['count'];
    }
}

function get_star_display($score) {
    $stars = '';
    for ($i = 0; $i < $score; $i++) {
        $stars .= '⭐';
    }
    return $stars;
}

function get_rating_color($rating) {
    if ($rating >= 4) return '#28a745';
    if ($rating >= 3) return '#ffc107';
    return '#dc3545';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f9f9f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-container {
            margin-top: 30px;
            margin-bottom: 40px;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #8b4513;
        }

        .page-header h1 {
            color: #8b4513;
            font-weight: 800;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            font-size: 36px;
            background: linear-gradient(135deg, #8b4513, #d2691e);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-left: 4px solid #8b4513;
        }

        .stat-card h4 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .stat-card .count {
            font-size: 28px;
            font-weight: 800;
            color: #d2691e;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .filter-section h5 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .search-input input {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            flex: 1;
            min-width: 200px;
        }

        .search-input input:focus {
            border-color: #8b4513;
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.2);
        }

        .reviews-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .review-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .review-item:hover {
            background-color: #f9f9f9;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .review-name {
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }

        .review-date {
            font-size: 12px;
            color: #999;
        }

        .review-rating {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .review-text {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f9f9f9;
            border-left: 3px solid #d2691e;
            border-radius: 3px;
        }

        .review-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-delete {
            padding: 6px 12px;
            font-size: 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #999;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .no-reviews i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .distribution-chart {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .distribution-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .stars-label {
            min-width: 80px;
            font-weight: 600;
            color: #8b4513;
        }

        .distribution-bar {
            flex: 1;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .distribution-fill {
            height: 100%;
            background: linear-gradient(135deg, #8b4513, #d2691e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            font-weight: 600;
        }

        .count-label {
            min-width: 40px;
            text-align: right;
            color: #666;
        }
    </style>
</head>
<body>

<div class="container-fluid admin-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-comments"></i>
            Manage Reviews
        </h1>
    </div>

    <!-- Alerts -->
    <?php if ($response): ?>
        <div class="alert alert-<?php echo $response_type; ?>">
            <?php echo $response; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-card">
            <h4>TOTAL REVIEWS</h4>
            <div class="count"><?php echo $total_reviews; ?></div>
        </div>
        <div class="stat-card">
            <h4>AVERAGE RATING</h4>
            <div class="count"><?php echo $avg_rating; ?>/5</div>
        </div>
        <div class="stat-card">
            <h4>5-STAR REVIEWS</h4>
            <div class="count"><?php echo $distribution[5]; ?></div>
        </div>
        <div class="stat-card">
            <h4>4-STAR REVIEWS</h4>
            <div class="count"><?php echo $distribution[4]; ?></div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <?php if ($total_reviews > 0): ?>
        <div class="distribution-chart">
            <h5 style="color: #8b4513; font-weight: 700; margin-bottom: 20px;">Rating Distribution</h5>
            <?php for ($i = 5; $i >= 1; $i--): 
                $count = $distribution[$i];
                $percentage = ($count / $total_reviews) * 100;
            ?>
                <div class="distribution-row">
                    <div class="stars-label">
                        <?php echo str_repeat('⭐', $i); ?>
                    </div>
                    <div class="distribution-bar">
                        <div class="distribution-fill" style="width: <?php echo $percentage; ?>%;">
                            <?php if ($percentage > 5) echo round($percentage, 1) . '%'; ?>
                        </div>
                    </div>
                    <div class="count-label"><?php echo $count; ?></div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Search Filter -->
    <?php if ($total_reviews > 0): ?>
        <div class="filter-section">
            <h5><i class="fas fa-search"></i> Search Reviews</h5>
            <form method="GET" class="filter-group">
                <div class="search-input" style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" placeholder="Search by customer name or review text..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn btn-warning" style="font-weight: 600;">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="manage_reviews.php" class="btn btn-secondary" style="font-weight: 600;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <!-- Reviews Display -->
    <?php if (count($reviews) > 0): ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div>
                            <div class="review-name"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                            <div class="review-date">
                                <i class="far fa-calendar"></i> <?php echo date('M d, Y @ H:i', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php echo get_star_display($review['rating']); ?>
                        </div>
                    </div>
                    <div class="review-text">
                        <?php echo htmlspecialchars($review['review_text']); ?>
                    </div>
                    <div class="review-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" name="delete_review" class="btn-delete" onclick="return confirm('Delete this review?');">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-reviews">
            <i class="far fa-comments"></i>
            <p><?php echo !empty($search_query) ? 'No reviews found matching your search.' : 'No reviews yet. Customers haven\'t submitted any reviews.'; ?></p>
        </div>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
