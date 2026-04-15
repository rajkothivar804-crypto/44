<?php
ob_start();
error_reporting(0);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}



include '../database/DB.php';
include 'header.php';

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$response = '';
$response_type = '';

// Handle rating deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rating'])) {
    $rating_line = intval($_POST['rating_index']);
    
    // Read all ratings
    $ratings = [];
    if (file_exists('../ratings.txt')) {
        $lines = file('../ratings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $index => $line) {
            if ($index !== $rating_line) {
                $ratings[] = $line;
            }
        }
        // Rewrite file without deleted rating
        file_put_contents('../ratings.txt', implode("\n", $ratings) . (count($ratings) > 0 ? "\n" : ''));
        $response = '✅ Rating deleted successfully!';
        $response_type = 'success';
    }
}

// Parse ratings from file
$all_ratings = [];
if (file_exists('../ratings.txt')) {
    $lines = array_filter(file('../ratings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    foreach ($lines as $index => $line) {
        $parts = array_pad(explode('|', $line, 3), 3, '');
        $all_ratings[] = [
            'index' => $index,
            'date' => $parts[0],
            'score' => intval($parts[1]),
            'comment' => $parts[2]
        ];
    }
}

// Reverse to show newest first
$all_ratings = array_reverse($all_ratings, true);

// Calculate statistics
$total_ratings = count($all_ratings);
$average_rating = 0;
$rating_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

foreach ($all_ratings as $rating) {
    $score = $rating['score'];
    if (isset($rating_distribution[$score])) {
        $rating_distribution[$score]++;
    }
    $average_rating += $score;
}

if ($total_ratings > 0) {
    $average_rating = round($average_rating / $total_ratings, 1);
}

function get_star_display($score) {
    $stars = '';
    for ($i = 0; $i < $score; $i++) {
        $stars .= '⭐';
    }
    return $stars;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ratings - Admin</title>
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

        .ratings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .rating-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-top: 3px solid #d2691e;
            position: relative;
        }

        .rating-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .rating-stars {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .rating-date {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }

        .rating-comment {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            min-height: 40px;
        }

        .rating-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-delete-rating {
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

        .btn-delete-rating:hover {
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

        .no-ratings {
            text-align: center;
            padding: 40px;
            color: #999;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .no-ratings i {
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
            <i class="fas fa-star"></i>
            Manage Ratings
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
            <h4>TOTAL RATINGS</h4>
            <div class="count"><?php echo $total_ratings; ?></div>
        </div>
        <div class="stat-card">
            <h4>AVERAGE RATING</h4>
            <div class="count"><?php echo $average_rating; ?>/5</div>
        </div>
        <div class="stat-card">
            <h4>5-STAR RATINGS</h4>
            <div class="count"><?php echo $rating_distribution[5]; ?></div>
        </div>
        <div class="stat-card">
            <h4>4-STAR RATINGS</h4>
            <div class="count"><?php echo $rating_distribution[4]; ?></div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <?php if ($total_ratings > 0): ?>
        <div class="distribution-chart">
            <h5 style="color: #8b4513; font-weight: 700; margin-bottom: 20px;">Rating Distribution</h5>
            <?php for ($i = 5; $i >= 1; $i--): 
                $count = $rating_distribution[$i];
                $percentage = ($count / $total_ratings) * 100;
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

    <!-- Ratings Display -->
    <?php if (count($all_ratings) > 0): ?>
        <div class="ratings-grid">
            <?php foreach ($all_ratings as $rating): ?>
                <div class="rating-card">
                    <div class="rating-card-header">
                        <div class="rating-stars"><?php echo get_star_display($rating['score']); ?></div>
                    </div>
                    <div class="rating-date">
                        <i class="far fa-calendar"></i> <?php echo htmlspecialchars($rating['date']); ?>
                    </div>
                    <div class="rating-comment">
                        <?php echo htmlspecialchars($rating['comment']); ?>
                    </div>
                    <div class="rating-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="rating_index" value="<?php echo $rating['index']; ?>">
                            <button type="submit" name="delete_rating" class="btn-delete-rating" onclick="return confirm('Delete this rating?');">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-ratings">
            <i class="far fa-star"></i>
            <p>No ratings yet. Customers haven't submitted any ratings.</p>
        </div>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
