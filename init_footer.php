<?php
include 'database/DB.php';
$defaults = [
    'about_text' => 'Premium coffee experience since 2015. We serve freshly roasted beans and create memories one cup at a time.',
    'social_facebook' => '#',
    'social_instagram' => '#',
    'social_twitter' => '#',
    'social_linkedin' => '#',
    'newsletter_text' => 'Get updates on new menu items and special offers!',
    'footer_bottom_text' => '&copy; 2025 Cafe Delicious. All Rights Reserved. | <a href="#" style="color: #ffd700; text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: #ffd700; text-decoration: none;">Terms & Conditions</a>',
    'payment_methods' => '💳,₹,💰'
];
foreach ($defaults as $key => $value) {
    $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
echo 'Footer settings initialized.';
?>