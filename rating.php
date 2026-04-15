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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Us - Cafe Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .rating-hero { background: linear-gradient(135deg, #fffacd, #f5f5dc); padding: 90px 0 60px; margin-top: 70px; text-align: center; }
        .rating-card { border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.12); }
        .star-rating { direction: rtl; display: inline-flex; }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 32px;
            color: #d3d3d3;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #d2691e;
        }
        .rating-note { font-size: 14px; color: #5c4033; }
    </style>
</head>
<body>

<?php
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $comment = str_replace(["\r", "\n", "|"], ['','',' '], $comment);

    $entry = sprintf("%s|%d|%s\n", date('Y-m-d H:i:s'), $rating, $comment);
    file_put_contents('ratings.txt', $entry, FILE_APPEND | LOCK_EX);

    $message = 'Thanks! Your rating has been recorded.';
}

$recentRatings = [];
if (file_exists('ratings.txt')) {
    $lines = array_filter(file('ratings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    $lines = array_slice($lines, -5); // show last 5 ratings
    foreach ($lines as $line) {
        [$date, $score, $text] = array_pad(explode('|', $line, 3), 3, '');
        $recentRatings[] = ['date' => $date, 'score' => $score, 'comment' => $text];
    }
}
?>

<section class="rating-hero">
    <div class="container">
        <h1 class="display-4" style="color:#5c4033; font-weight:700;">Rate Your Cafe Experience</h1>
        <p class="lead" style="color:#6b4b3a;">Your feedback helps us brew better moments. Pick your stars and leave a short note.</p>
    </div>
</section>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="p-4 bg-white rating-card">
                <?php if ($message): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" action="rating.php">
                    <div class="form-group">
                        <label class="font-weight-bold" for="rating">Your Rating</label>
                        <div class="star-rating" aria-label="Star rating">
                            <input type="radio" name="rating" id="star5" value="5"><label for="star5" title="5 stars">★</label>
                            <input type="radio" name="rating" id="star4" value="4"><label for="star4" title="4 stars">★</label>
                            <input type="radio" name="rating" id="star3" value="3"><label for="star3" title="3 stars">★</label>
                            <input type="radio" name="rating" id="star2" value="2"><label for="star2" title="2 stars">★</label>
                            <input type="radio" name="rating" id="star1" value="1"><label for="star1" title="1 star">★</label>
                        </div>
                        <p class="rating-note mt-2">Click a star to rate. 1 = needs improvement, 5 = outstanding.</p>
                    </div>

                    <div class="form-group">
                        <label for="comment" class="font-weight-bold">Your Feedback</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Share what you liked or how we can improve."></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block">Submit Rating</button>
                </form>

                <?php if (!empty($recentRatings)): ?>
                    <hr>
                    <h5 class="mt-4" style="color:#5c4033;">Recent Ratings</h5>
                    <ul class="list-group">
                        <?php foreach ($recentRatings as $row): ?>
                            <li class="list-group-item">
                                <strong><?php echo htmlspecialchars($row['score']); ?>/5</strong>
                                <span class="text-muted">&middot; <?php echo htmlspecialchars($row['date']); ?></span>
                                <?php if ($row['comment']): ?>
                                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
