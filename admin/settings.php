<?php 
include 'header.php';
include '../database/DB.php';

$alert_message = '';
$alert_type = '';

// Create settings table if it doesn't exist
$create_settings_table = "CREATE TABLE IF NOT EXISTS cafe_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

mysqli_query($conn, $create_settings_table);

// Fetch current settings
$settings_sql = "SELECT setting_key, setting_value FROM cafe_settings";
$settings_result = mysqli_query($conn, $settings_sql);

$settings = [
    'cafe_name' => 'My Cafe',
    'email' => 'info@mycafe.com',
    'phone' => '+1 (555) 000-0000',
    'address' => '123 Cafe Street',
    'website' => 'INR (₹)',
    'monday_open' => '07:00',
    'monday_close' => '20:00',
    'saturday_open' => '08:00',
    'saturday_close' => '21:00',
    'sunday_open' => '09:00',
    'sunday_close' => '19:00',
    'email_notifications' => '1',
    'order_notifications' => '1',
    'stock_notifications' => '1',
    'menu_header_title' => '☕ Our Menu',
    'menu_header_subtitle' => 'Discover Our Delicious Selection of Beverages & Delights',
    'menu_header_image' => 'images/menu-header.jpg',
    'menu_header_title_color' => '#ffffff',
    'menu_header_title_size' => '50px',
    'menu_header_subtitle_color' => '#f5f5dc',
    'home_header_image' => 'home.jpeg',
    'home_hero_title_color' => '#ffffff',
    'home_hero_subtitle_color' => '#ffffff',
    'home_hero_title_size' => '60px',
    'home_hero_title' => 'Welcome to Cafe Delicious',
    'home_hero_subtitle' => 'Your Premium Coffee Experience Awaits',
    'wishlist_header_title' => '❤️ My Wishlist',
    'wishlist_header_subtitle' => 'Your favorite items saved for later',
    'wishlist_header_image' => 'https://images.unsplash.com/photo-1495474472645-4d71bcdd2016?w=1200&h=300&fit=crop',
    'wishlist_header_title_color' => '#ffffff',
    'wishlist_header_subtitle_color' => '#ffffff',
    'wishlist_header_title_size' => '45px',
    'cart_header_title' => '🛒 Shopping Cart',
    'cart_header_subtitle' => 'Review and manage your items',
    'cart_header_image' => 's.jpg',
    'cart_header_title_color' => '#ffffff',
    'cart_header_subtitle_color' => '#ffffff',
    'cart_header_title_size' => '45px',
    'review_header_title' => '⭐ Product Reviews',
    'review_header_subtitle' => 'Share your experience with our products',
    'review_header_image' => 'https://images.unsplash.com/photo-1495474472645-4d71bcdd2016?w=1200&h=300&fit=crop',
    'review_header_title_color' => '#ffffff',
    'review_header_subtitle_color' => '#ffffff',
    'review_header_title_size' => '45px',
    'hero_title' => 'Welcome to My Cafe',
    'hero_subtitle' => 'Your Premium Coffee Experience Awaits',
    'about_title' => 'Craft Coffee at Its Finest',
    'about_description' => 'Welcome to our cafe, where passion meets perfection. We\'ve been crafting exceptional coffee experiences for our beloved customers. Our expert baristas use only the finest, freshly roasted beans sourced from premium suppliers around the world. Every cup tells a story of dedication, quality, and love for the craft. Whether you\'re a coffee connoisseur or just looking for your daily pick-me-up, we have something special waiting for you.',
    'features_title' => 'Why Choose Us',
    'commitment_title' => 'Our Commitment',
    'cta_title' => 'Ready to Experience Excellence?',
    'cta_subtitle' => 'Visit us today or order online for delivery to your doorstep',
    'stat1_label' => 'Happy Customers',
    'stat1_value' => '100',
    'stat2_label' => 'Years Experience',
    'stat2_value' => '8+',
    'stat3_label' => 'Menu Items',
    'stat3_value' => '50',
    'stat4_label' => 'Orders Served',
    'stat4_value' => '1000+',
    'feature1_icon' => '☕',
    'feature1_title' => 'Premium Quality',
    'feature1_desc' => 'Handpicked, freshly roasted beans from the finest coffee regions across the globe.',
    'feature2_icon' => '👨‍💼',
    'feature2_title' => 'Expert Baristas',
    'feature2_desc' => 'Our skilled baristas craft each cup with precision and passion for the perfect taste.',
    'feature3_icon' => '🎨',
    'feature3_title' => 'Cozy Ambiance',
    'feature3_desc' => 'Enjoy your coffee in our warm, welcoming environment perfect for work or relaxation.',
    'feature4_icon' => '⚡',
    'feature4_title' => 'Fast Service',
    'feature4_desc' => 'Quick and efficient service without compromising on quality or taste.',
    'feature5_icon' => '💰',
    'feature5_title' => 'Affordable Prices',
    'feature5_desc' => 'Premium coffee experience at prices that won\'t break your budget.',
    'feature6_icon' => '🌍',
    'feature6_title' => 'Sustainable',
    'feature6_desc' => 'Eco-friendly practices ensuring a better future for the planet.',
    'commitment1_title' => '100% Fresh Ingredients',
    'commitment1_desc' => 'All our ingredients are sourced fresh daily. No shortcuts, no compromises.',
    'commitment2_title' => 'Customer Satisfaction',
    'commitment2_desc' => 'Your happiness is our priority. We guarantee satisfaction on every order.',
    'commitment3_title' => 'Hygiene & Safety',
    'commitment3_desc' => 'Strict hygiene standards maintained across all operations and facilities.',
    'commitment4_title' => 'Custom Orders',
    'commitment4_desc' => 'Create your perfect beverage with our customization options.',
    'commitment5_title' => 'Quick Delivery',
    'commitment5_desc' => 'Fast and reliable delivery service to get your coffee while it\'s hot.',
    'commitment6_title' => 'Expert Support',
    'commitment6_desc' => 'Our team is always ready to help with recommendations and special requests.'
];

if ($settings_result && mysqli_num_rows($settings_result) > 0) {
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $key = $row['setting_key'];
        if ($key === 'hours') {
            $decoded_hours = json_decode($row['setting_value'], true);
            if (is_array($decoded_hours) && !empty($decoded_hours)) {
                $settings['monday_open'] = $decoded_hours[0]['hours'] ? str_before($decoded_hours[0]['hours'], ' - ') : '07:00';
                $settings['monday_close'] = $decoded_hours[0]['hours'] ? str_after($decoded_hours[0]['hours'], ' - ') : '20:00';
                $settings['saturday_open'] = $decoded_hours[1]['hours'] ? str_before($decoded_hours[1]['hours'], ' - ') : '08:00';
                $settings['saturday_close'] = $decoded_hours[1]['hours'] ? str_after($decoded_hours[1]['hours'], ' - ') : '21:00';
                $settings['sunday_open'] = $decoded_hours[2]['hours'] ? str_before($decoded_hours[2]['hours'], ' - ') : '09:00';
                $settings['sunday_close'] = $decoded_hours[2]['hours'] ? str_after($decoded_hours[2]['hours'], ' - ') : '19:00';
            }
        } else {
            $settings[$key] = $row['setting_value'];
        }
    }
}

// Handle General Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
    $cafe_name = isset($_POST['cafe_name']) ? trim($_POST['cafe_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'INR (₹)';

    if (empty($cafe_name) || strlen($cafe_name) < 2) {
        $alert_message = "Cafe name must be at least 2 characters!";
        $alert_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = "Please enter a valid email!";
        $alert_type = "danger";
    } else {
        $settings = [
            'cafe_name' => $cafe_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'website' => $currency
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $alert_message = "General settings updated successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating settings!";
            $alert_type = "danger";
        }
    }
}

// Handle Business Hours Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hours'])) {
    $monday_open = isset($_POST['monday_open']) ? trim($_POST['monday_open']) : '';
    $monday_close = isset($_POST['monday_close']) ? trim($_POST['monday_close']) : '';
    $saturday_open = isset($_POST['saturday_open']) ? trim($_POST['saturday_open']) : '';
    $saturday_close = isset($_POST['saturday_close']) ? trim($_POST['saturday_close']) : '';
    $sunday_open = isset($_POST['sunday_open']) ? trim($_POST['sunday_open']) : '';
    $sunday_close = isset($_POST['sunday_close']) ? trim($_POST['sunday_close']) : '';

    if (empty($monday_open) || empty($monday_close)) {
        $alert_message = "Please fill in all business hours!";
        $alert_type = "danger";
    } else {
        $hours_array = [
            ['day' => 'Mon - Fri', 'hours' => $monday_open . ' - ' . $monday_close],
            ['day' => 'Saturday', 'hours' => $saturday_open . ' - ' . $saturday_close],
            ['day' => 'Sunday', 'hours' => $sunday_open . ' - ' . $sunday_close]
        ];
        
        $hours_json = json_encode($hours_array);
        
        $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $sql);
        $key = 'hours';
        mysqli_stmt_bind_param($stmt, 'sss', $key, $hours_json, $hours_json);
        
        if (mysqli_stmt_execute($stmt)) {
            $alert_message = "Business hours updated successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating business hours!";
            $alert_type = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $alert_message = "All password fields are required!";
        $alert_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $alert_message = "New password must be at least 6 characters!";
        $alert_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $alert_message = "Passwords do not match!";
        $alert_type = "danger";
    } else {
        // Verify current password
        $user_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
        $check_sql = "SELECT password FROM users WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, 'si', $hashed_password, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $alert_message = "Password changed successfully!";
                    $alert_type = "success";
                } else {
                    $alert_message = "Error changing password!";
                    $alert_type = "danger";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $alert_message = "Current password is incorrect!";
                $alert_type = "danger";
            }
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Handle Menu Header Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_menu_header'])) {
    $menu_header_title = isset($_POST['menu_header_title']) ? trim($_POST['menu_header_title']) : '';
    $menu_header_subtitle = isset($_POST['menu_header_subtitle']) ? trim($_POST['menu_header_subtitle']) : '';
    $menu_header_title_color = isset($_POST['menu_header_title_color']) ? trim($_POST['menu_header_title_color']) : '#ffffff';
    $menu_header_title_size = isset($_POST['menu_header_title_size']) ? trim($_POST['menu_header_title_size']) : '50px';
    $menu_header_subtitle_color = isset($_POST['menu_header_subtitle_color']) ? trim($_POST['menu_header_subtitle_color']) : '#f5f5dc';
    $menu_header_image = $settings['menu_header_image'];
    $menu_header_image_uploaded = false;
    
    // Ensure title size has 'px' suffix
    if (!empty($menu_header_title_size)) {
        $menu_header_title_size = preg_replace('/[^0-9]/', '', $menu_header_title_size) . 'px';
    }
    
    // Handle image upload
    if (isset($_FILES['menu_header_image_file']) && $_FILES['menu_header_image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = basename($_FILES['menu_header_image_file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create images directory if it doesn't exist
            $images_dir = '../images';
            if (!is_dir($images_dir)) {
                mkdir($images_dir, 0755, true);
            }
            
            $new_filename = 'menu-header-' . time() . '.' . $ext;
            $upload_path = $images_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['menu_header_image_file']['tmp_name'], $upload_path)) {
                // Delete old uploaded image if it's not the default
                if ($settings['menu_header_image'] !== 'images/menu-header.jpg' && file_exists('../' . $settings['menu_header_image'])) {
                    unlink('../' . $settings['menu_header_image']);
                }
                $menu_header_image = 'images/' . $new_filename;
                $menu_header_image_uploaded = true;
            } else {
                $alert_message = "Error uploading image! Check folder permissions.";
                $alert_type = "danger";
            }
        } else {
            $alert_message = "Invalid image format! Allowed: JPG, PNG, GIF, WebP";
            $alert_type = "danger";
        }
    }
    
    if (empty($menu_header_title)) {
        $alert_message = "Menu header title is required!";
        $alert_type = "danger";
    } else if (empty($alert_message)) {  // Only save if no error occurred
        $menu_settings = [
            'menu_header_title' => $menu_header_title,
            'menu_header_subtitle' => $menu_header_subtitle,
            'menu_header_title_color' => $menu_header_title_color,
            'menu_header_title_size' => $menu_header_title_size,
            'menu_header_subtitle_color' => $menu_header_subtitle_color
        ];
        if ($menu_header_image_uploaded) {
            $menu_settings['menu_header_image'] = $menu_header_image;
        }
        
        $success = true;
        foreach ($menu_settings as $key => $value) {
            $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $alert_message = "Menu header settings updated successfully!";
            $alert_type = "success";
            $scroll_to = 'menu-header-section';
        } else {
            $alert_message = "Error updating menu header settings!";
            $alert_type = "danger";
        }
    }
}

// Handle Home Header Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_home_header'])) {
    $home_hero_title = isset($_POST['home_hero_title']) ? trim($_POST['home_hero_title']) : '';
    $home_hero_subtitle = isset($_POST['home_hero_subtitle']) ? trim($_POST['home_hero_subtitle']) : '';
    $home_hero_title_color = isset($_POST['home_hero_title_color']) ? trim($_POST['home_hero_title_color']) : '#ffffff';
    $home_hero_subtitle_color = isset($_POST['home_hero_subtitle_color']) ? trim($_POST['home_hero_subtitle_color']) : '#ffffff';
    $home_hero_title_size = isset($_POST['home_hero_title_size']) ? trim($_POST['home_hero_title_size']) : '60px';
    $home_header_image = $settings['home_header_image'];
    $home_header_image_uploaded = false;
    
    // Ensure title size has 'px' suffix
    if (!empty($home_hero_title_size)) {
        $home_hero_title_size = preg_replace('/[^0-9]/', '', $home_hero_title_size) . 'px';
    }
    
    // Handle image upload
    if (isset($_FILES['home_header_image_file']) && $_FILES['home_header_image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = basename($_FILES['home_header_image_file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create images directory if it doesn't exist
            $images_dir = '../images';
            if (!is_dir($images_dir)) {
                mkdir($images_dir, 0755, true);
            }
            
            $new_filename = 'home-header-' . time() . '.' . $ext;
            $upload_path = $images_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['home_header_image_file']['tmp_name'], $upload_path)) {
                // Delete old uploaded image if it's not the default
                if ($settings['home_header_image'] !== 'home.jpeg' && file_exists('../' . $settings['home_header_image'])) {
                    unlink('../' . $settings['home_header_image']);
                }
                $home_header_image = 'images/' . $new_filename;
                $home_header_image_uploaded = true;
            } else {
                $alert_message = "Error uploading image! Check folder permissions.";
                $alert_type = "danger";
            }
        } else {
            $alert_message = "Invalid image format! Allowed: JPG, PNG, GIF, WebP";
            $alert_type = "danger";
        }
    }
    
    if (empty($home_hero_title)) {
        $alert_message = "Hero title is required!";
        $alert_type = "danger";
    } else if (empty($alert_message)) {  // Only save if no error occurred
        $home_settings = [
            'home_hero_title' => $home_hero_title,
            'home_hero_subtitle' => $home_hero_subtitle,
            'home_hero_title_color' => $home_hero_title_color,
            'home_hero_subtitle_color' => $home_hero_subtitle_color,
            'home_hero_title_size' => $home_hero_title_size
        ];
        if ($home_header_image_uploaded) {
            $home_settings['home_header_image'] = $home_header_image;
        }
        
        $success = true;
        foreach ($home_settings as $key => $value) {
            $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $alert_message = "Home header settings updated successfully!";
            $alert_type = "success";
            $scroll_to = 'home-header-section';
        } else {
            $alert_message = "Error updating home header settings!";
            $alert_type = "danger";
        }
    }
}

// Handle Wishlist Header Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wishlist_header'])) {
    $wishlist_header_title = isset($_POST['wishlist_header_title']) ? trim($_POST['wishlist_header_title']) : '';
    $wishlist_header_subtitle = isset($_POST['wishlist_header_subtitle']) ? trim($_POST['wishlist_header_subtitle']) : '';
    $wishlist_header_title_color = isset($_POST['wishlist_header_title_color']) ? trim($_POST['wishlist_header_title_color']) : '#ffffff';
    $wishlist_header_subtitle_color = isset($_POST['wishlist_header_subtitle_color']) ? trim($_POST['wishlist_header_subtitle_color']) : '#ffffff';
    $wishlist_header_title_size = isset($_POST['wishlist_header_title_size']) ? trim($_POST['wishlist_header_title_size']) : '45px';
    $wishlist_header_image = $settings['wishlist_header_image'];
    $wishlist_header_image_uploaded = false;
    
    // Ensure title size has 'px' suffix
    if (!empty($wishlist_header_title_size)) {
        $wishlist_header_title_size = preg_replace('/[^0-9]/', '', $wishlist_header_title_size) . 'px';
    }
    
    // Handle image upload
    if (isset($_FILES['wishlist_header_image_file']) && $_FILES['wishlist_header_image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = basename($_FILES['wishlist_header_image_file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create images directory if it doesn't exist
            $images_dir = '../images';
            if (!is_dir($images_dir)) {
                mkdir($images_dir, 0755, true);
            }
            
            $new_filename = 'wishlist-header-' . time() . '.' . $ext;
            $upload_path = $images_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['wishlist_header_image_file']['tmp_name'], $upload_path)) {
                // Delete old uploaded image if it's a local file
                if (strpos($settings['wishlist_header_image'], 'images/') === 0 && file_exists('../' . $settings['wishlist_header_image'])) {
                    unlink('../' . $settings['wishlist_header_image']);
                }
                $wishlist_header_image = 'images/' . $new_filename;
                $wishlist_header_image_uploaded = true;
            } else {
                $alert_message = "Error uploading image! Check folder permissions.";
                $alert_type = "danger";
            }
        } else {
            $alert_message = "Invalid image format! Allowed: JPG, PNG, GIF, WebP";
            $alert_type = "danger";
        }
    }
    
    if (empty($wishlist_header_title)) {
        $alert_message = "Wishlist header title is required!";
        $alert_type = "danger";
    } else if (empty($alert_message)) {  // Only save if no error occurred
        $wishlist_settings = [
            'wishlist_header_title' => $wishlist_header_title,
            'wishlist_header_subtitle' => $wishlist_header_subtitle,
            'wishlist_header_title_color' => $wishlist_header_title_color,
            'wishlist_header_subtitle_color' => $wishlist_header_subtitle_color,
            'wishlist_header_title_size' => $wishlist_header_title_size
        ];
        if ($wishlist_header_image_uploaded) {
            $wishlist_settings['wishlist_header_image'] = $wishlist_header_image;
        }
        
        $success = true;
        foreach ($wishlist_settings as $key => $value) {
            $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $alert_message = "Wishlist header settings updated successfully!";
            $alert_type = "success";
            $scroll_to = 'wishlist-header-section';
        } else {
            $alert_message = "Error updating wishlist header settings!";
            $alert_type = "danger";
        }
    }
}

// Handle Cart Header Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cart_header'])) {
    $cart_header_title = isset($_POST['cart_header_title']) ? trim($_POST['cart_header_title']) : '';
    $cart_header_subtitle = isset($_POST['cart_header_subtitle']) ? trim($_POST['cart_header_subtitle']) : '';
    $cart_header_title_color = isset($_POST['cart_header_title_color']) ? trim($_POST['cart_header_title_color']) : '#ffffff';
    $cart_header_subtitle_color = isset($_POST['cart_header_subtitle_color']) ? trim($_POST['cart_header_subtitle_color']) : '#ffffff';
    $cart_header_title_size = isset($_POST['cart_header_title_size']) ? trim($_POST['cart_header_title_size']) : '45px';
    $cart_header_image = $settings['cart_header_image'];
    $cart_header_image_uploaded = false;
    
    // Ensure title size has 'px' suffix
    if (!empty($cart_header_title_size)) {
        $cart_header_title_size = preg_replace('/[^0-9]/', '', $cart_header_title_size) . 'px';
    }
    
    // Handle image upload
    if (isset($_FILES['cart_header_image_file']) && $_FILES['cart_header_image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = basename($_FILES['cart_header_image_file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create images directory if it doesn't exist
            $images_dir = '../images';
            if (!is_dir($images_dir)) {
                mkdir($images_dir, 0755, true);
            }
            
            $new_filename = 'cart-header-' . time() . '.' . $ext;
            $upload_path = $images_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['cart_header_image_file']['tmp_name'], $upload_path)) {
                // Delete old uploaded image if it's not the default
                if ($settings['cart_header_image'] !== 's.jpg' && file_exists('../' . $settings['cart_header_image'])) {
                    unlink('../' . $settings['cart_header_image']);
                }
                $cart_header_image = 'images/' . $new_filename;
                $cart_header_image_uploaded = true;
            } else {
                $alert_message = "Error uploading image! Check folder permissions.";
                $alert_type = "danger";
            }
        } else {
            $alert_message = "Invalid image format! Allowed: JPG, PNG, GIF, WebP";
            $alert_type = "danger";
        }
    }
    
    if (empty($cart_header_title)) {
        $alert_message = "Cart header title is required!";
        $alert_type = "danger";
    } else if (empty($alert_message)) {  // Only save if no error occurred
        $cart_settings = [
            'cart_header_title' => $cart_header_title,
            'cart_header_subtitle' => $cart_header_subtitle,
            'cart_header_title_color' => $cart_header_title_color,
            'cart_header_subtitle_color' => $cart_header_subtitle_color,
            'cart_header_title_size' => $cart_header_title_size
        ];
        if ($cart_header_image_uploaded) {
            $cart_settings['cart_header_image'] = $cart_header_image;
        }
        
        $success = true;
        foreach ($cart_settings as $key => $value) {
            $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $alert_message = "Cart header settings updated successfully!";
            $alert_type = "success";
            $scroll_to = 'cart-header-section';
        } else {
            $alert_message = "Error updating cart header settings!";
            $alert_type = "danger";
        }
    }
}

// Handle Product Review Header Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review_header'])) {
    $review_header_title = isset($_POST['review_header_title']) ? trim($_POST['review_header_title']) : '';
    $review_header_subtitle = isset($_POST['review_header_subtitle']) ? trim($_POST['review_header_subtitle']) : '';
    $review_header_title_color = isset($_POST['review_header_title_color']) ? trim($_POST['review_header_title_color']) : '#ffffff';
    $review_header_subtitle_color = isset($_POST['review_header_subtitle_color']) ? trim($_POST['review_header_subtitle_color']) : '#ffffff';
    $review_header_title_size = isset($_POST['review_header_title_size']) ? trim($_POST['review_header_title_size']) : '45px';
    $review_header_image = $settings['review_header_image'];
    $review_header_image_uploaded = false;
    
    // Ensure title size has 'px' suffix
    if (!empty($review_header_title_size)) {
        $review_header_title_size = preg_replace('/[^0-9]/', '', $review_header_title_size) . 'px';
    }
    
    // Handle image upload
    if (isset($_FILES['review_header_image_file']) && $_FILES['review_header_image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = basename($_FILES['review_header_image_file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create images directory if it doesn't exist
            $images_dir = '../images';
            if (!is_dir($images_dir)) {
                mkdir($images_dir, 0755, true);
            }
            
            $new_filename = 'review-header-' . time() . '.' . $ext;
            $upload_path = $images_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['review_header_image_file']['tmp_name'], $upload_path)) {
                // Delete old uploaded image if it's a local file
                if (strpos($settings['review_header_image'], 'images/') === 0 && file_exists('../' . $settings['review_header_image'])) {
                    unlink('../' . $settings['review_header_image']);
                }
                $review_header_image = 'images/' . $new_filename;
                $review_header_image_uploaded = true;
            } else {
                $alert_message = "Error uploading image! Check folder permissions.";
                $alert_type = "danger";
            }
        } else {
            $alert_message = "Invalid image format! Allowed: JPG, PNG, GIF, WebP";
            $alert_type = "danger";
        }
    }
    
    if (empty($review_header_title)) {
        $alert_message = "Review header title is required!";
        $alert_type = "danger";
    } else if (empty($alert_message)) {  // Only save if no error occurred
        $review_settings = [
            'review_header_title' => $review_header_title,
            'review_header_subtitle' => $review_header_subtitle,
            'review_header_title_color' => $review_header_title_color,
            'review_header_subtitle_color' => $review_header_subtitle_color,
            'review_header_title_size' => $review_header_title_size
        ];
        if ($review_header_image_uploaded) {
            $review_settings['review_header_image'] = $review_header_image;
        }
        
        $success = true;
        foreach ($review_settings as $key => $value) {
            $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success) {
            $alert_message = "Product Review header settings updated successfully!";
            $alert_type = "success";
            $scroll_to = 'review-header-section';
        } else {
            $alert_message = "Error updating product review header settings!";
            $alert_type = "danger";
        }
    }
}

// Handle Homepage Settings Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_homepage'])) {
    $hero_title = isset($_POST['hero_title']) ? trim($_POST['hero_title']) : '';
    $hero_subtitle = isset($_POST['hero_subtitle']) ? trim($_POST['hero_subtitle']) : '';
    $about_title = isset($_POST['about_title']) ? trim($_POST['about_title']) : '';
    $about_description = isset($_POST['about_description']) ? trim($_POST['about_description']) : '';
    $features_title = isset($_POST['features_title']) ? trim($_POST['features_title']) : '';
    $commitment_title = isset($_POST['commitment_title']) ? trim($_POST['commitment_title']) : '';
    $cta_title = isset($_POST['cta_title']) ? trim($_POST['cta_title']) : '';
    $cta_subtitle = isset($_POST['cta_subtitle']) ? trim($_POST['cta_subtitle']) : '';
    $stat1_label = isset($_POST['stat1_label']) ? trim($_POST['stat1_label']) : '';
    $stat1_value = isset($_POST['stat1_value']) ? trim($_POST['stat1_value']) : '';
    $stat2_label = isset($_POST['stat2_label']) ? trim($_POST['stat2_label']) : '';
    $stat2_value = isset($_POST['stat2_value']) ? trim($_POST['stat2_value']) : '';
    $stat3_label = isset($_POST['stat3_label']) ? trim($_POST['stat3_label']) : '';
    $stat3_value = isset($_POST['stat3_value']) ? trim($_POST['stat3_value']) : '';
    $stat4_label = isset($_POST['stat4_label']) ? trim($_POST['stat4_label']) : '';
    $stat4_value = isset($_POST['stat4_value']) ? trim($_POST['stat4_value']) : '';
    
    // Features section
    $feature1_icon = isset($_POST['feature1_icon']) ? trim($_POST['feature1_icon']) : '';
    $feature1_title = isset($_POST['feature1_title']) ? trim($_POST['feature1_title']) : '';
    $feature1_desc = isset($_POST['feature1_desc']) ? trim($_POST['feature1_desc']) : '';
    $feature2_icon = isset($_POST['feature2_icon']) ? trim($_POST['feature2_icon']) : '';
    $feature2_title = isset($_POST['feature2_title']) ? trim($_POST['feature2_title']) : '';
    $feature2_desc = isset($_POST['feature2_desc']) ? trim($_POST['feature2_desc']) : '';
    $feature3_icon = isset($_POST['feature3_icon']) ? trim($_POST['feature3_icon']) : '';
    $feature3_title = isset($_POST['feature3_title']) ? trim($_POST['feature3_title']) : '';
    $feature3_desc = isset($_POST['feature3_desc']) ? trim($_POST['feature3_desc']) : '';
    $feature4_icon = isset($_POST['feature4_icon']) ? trim($_POST['feature4_icon']) : '';
    $feature4_title = isset($_POST['feature4_title']) ? trim($_POST['feature4_title']) : '';
    $feature4_desc = isset($_POST['feature4_desc']) ? trim($_POST['feature4_desc']) : '';
    $feature5_icon = isset($_POST['feature5_icon']) ? trim($_POST['feature5_icon']) : '';
    $feature5_title = isset($_POST['feature5_title']) ? trim($_POST['feature5_title']) : '';
    $feature5_desc = isset($_POST['feature5_desc']) ? trim($_POST['feature5_desc']) : '';
    $feature6_icon = isset($_POST['feature6_icon']) ? trim($_POST['feature6_icon']) : '';
    $feature6_title = isset($_POST['feature6_title']) ? trim($_POST['feature6_title']) : '';
    $feature6_desc = isset($_POST['feature6_desc']) ? trim($_POST['feature6_desc']) : '';
    
    // Commitment section
    $commitment1_title = isset($_POST['commitment1_title']) ? trim($_POST['commitment1_title']) : '';
    $commitment1_desc = isset($_POST['commitment1_desc']) ? trim($_POST['commitment1_desc']) : '';
    $commitment2_title = isset($_POST['commitment2_title']) ? trim($_POST['commitment2_title']) : '';
    $commitment2_desc = isset($_POST['commitment2_desc']) ? trim($_POST['commitment2_desc']) : '';
    $commitment3_title = isset($_POST['commitment3_title']) ? trim($_POST['commitment3_title']) : '';
    $commitment3_desc = isset($_POST['commitment3_desc']) ? trim($_POST['commitment3_desc']) : '';
    $commitment4_title = isset($_POST['commitment4_title']) ? trim($_POST['commitment4_title']) : '';
    $commitment4_desc = isset($_POST['commitment4_desc']) ? trim($_POST['commitment4_desc']) : '';
    $commitment5_title = isset($_POST['commitment5_title']) ? trim($_POST['commitment5_title']) : '';
    $commitment5_desc = isset($_POST['commitment5_desc']) ? trim($_POST['commitment5_desc']) : '';
    $commitment6_title = isset($_POST['commitment6_title']) ? trim($_POST['commitment6_title']) : '';
    $commitment6_desc = isset($_POST['commitment6_desc']) ? trim($_POST['commitment6_desc']) : '';

    $homepage_settings = [
        'hero_title' => $hero_title,
        'hero_subtitle' => $hero_subtitle,
        'about_title' => $about_title,
        'about_description' => $about_description,
        'features_title' => $features_title,
        'commitment_title' => $commitment_title,
        'cta_title' => $cta_title,
        'cta_subtitle' => $cta_subtitle,
        'stat1_label' => $stat1_label,
        'stat1_value' => $stat1_value,
        'stat2_label' => $stat2_label,
        'stat2_value' => $stat2_value,
        'stat3_label' => $stat3_label,
        'stat3_value' => $stat3_value,
        'stat4_label' => $stat4_label,
        'stat4_value' => $stat4_value,
        'feature1_icon' => $feature1_icon,
        'feature1_title' => $feature1_title,
        'feature1_desc' => $feature1_desc,
        'feature2_icon' => $feature2_icon,
        'feature2_title' => $feature2_title,
        'feature2_desc' => $feature2_desc,
        'feature3_icon' => $feature3_icon,
        'feature3_title' => $feature3_title,
        'feature3_desc' => $feature3_desc,
        'feature4_icon' => $feature4_icon,
        'feature4_title' => $feature4_title,
        'feature4_desc' => $feature4_desc,
        'feature5_icon' => $feature5_icon,
        'feature5_title' => $feature5_title,
        'feature5_desc' => $feature5_desc,
        'feature6_icon' => $feature6_icon,
        'feature6_title' => $feature6_title,
        'feature6_desc' => $feature6_desc,
        'commitment1_title' => $commitment1_title,
        'commitment1_desc' => $commitment1_desc,
        'commitment2_title' => $commitment2_title,
        'commitment2_desc' => $commitment2_desc,
        'commitment3_title' => $commitment3_title,
        'commitment3_desc' => $commitment3_desc,
        'commitment4_title' => $commitment4_title,
        'commitment4_desc' => $commitment4_desc,
        'commitment5_title' => $commitment5_title,
        'commitment5_desc' => $commitment5_desc,
        'commitment6_title' => $commitment6_title,
        'commitment6_desc' => $commitment6_desc
    ];
    
    $success = true;
    foreach ($homepage_settings as $key => $value) {
        $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
        
        if (!mysqli_stmt_execute($stmt)) {
            $success = false;
            break;
        }
        mysqli_stmt_close($stmt);
    }
    
    if ($success) {
        $alert_message = "Homepage settings updated successfully!";
        $alert_type = "success";
    } else {
        $alert_message = "Error updating homepage settings!";
        $alert_type = "danger";
    }
}

// Handle Notifications Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? '1' : '0';
    $order_notifications = isset($_POST['order_notifications']) ? '1' : '0';
    $stock_notifications = isset($_POST['stock_notifications']) ? '1' : '0';

    $notification_settings = [
        'email_notifications' => $email_notifications,
        'order_notifications' => $order_notifications,
        'stock_notifications' => $stock_notifications
    ];
    
    $success = true;
    foreach ($notification_settings as $key => $value) {
        $sql = "INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
        
        if (!mysqli_stmt_execute($stmt)) {
            $success = false;
            break;
        }
        mysqli_stmt_close($stmt);
    }
    
    if ($success) {
        $alert_message = "Notification preferences updated successfully!";
        $alert_type = "success";
    } else {
        $alert_message = "Error updating notification preferences!";
        $alert_type = "danger";
    }
}

// Helper functions for string operations
function str_before($subject, $search) {
    $pos = strpos($subject, $search);
    return $pos !== false ? trim(substr($subject, 0, $pos)) : $subject;
}

function str_after($subject, $search) {
    $pos = strpos($subject, $search);
    return $pos !== false ? trim(substr($subject, $pos + strlen($search))) : '';
}
?>

            <!-- Alert Messages -->
            <?php if (!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert" style="margin-bottom: 20px; border-radius: 10px; border-left: 5px solid;">
                    <strong><?php echo ($alert_type === 'success') ? '✓ Success!' : '✕ Error!'; ?></strong>
                    <?php echo $alert_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <style>
                .error-message {
                    color: #dc3545;
                    font-size: 13px;
                    margin-top: 5px;
                    display: none;
                }
                
                .form-control.is-invalid {
                    border-color: #dc3545 !important;
                    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
                }

                /* Homepage Settings Styling */
                .form-group {
                    margin-bottom: 18px;
                }

                .form-group label {
                    margin-bottom: 8px;
                    display: block;
                }

                .form-control {
                    border: 2px solid #e8e0d5;
                    border-radius: 6px;
                    padding: 10px 12px;
                    font-size: 14px;
                    transition: all 0.3s ease;
                }

                .form-control:focus {
                    border-color: #d2691e;
                    box-shadow: 0 0 0 0.2rem rgba(210, 105, 30, 0.15);
                    outline: none;
                }

                .form-control:hover {
                    border-color: #d2a679;
                }

                textarea.form-control {
                    resize: vertical;
                    font-family: inherit;
                    min-height: 70px;
                }

                /* Section Headers */
                h5 {
                    border-bottom: 3px solid #d2691e;
                    padding-bottom: 10px;
                    display: inline-block;
                }

                /* Features and Commitment Section Styling */
                .row .col-md-4,
                .row .col-md-6 {
                    padding: 15px;
                    background: #faf7f3;
                    border-radius: 8px;
                    margin-bottom: 15px;
                    border-left: 4px solid #d2691e;
                }

                .row .col-md-3 {
                    padding: 12px;
                    background: #f9f7f4;
                    border-radius: 6px;
                    margin-bottom: 12px;
                }

                /* Button Styling */
                .btn-admin {
                    padding: 12px 24px;
                    font-weight: 600;
                    border-radius: 6px;
                    border: none;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .btn-admin-primary {
                    background-color: #8b4513;
                    color: white;
                }

                .btn-admin-primary:hover {
                    background-color: #5c3111;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
                }

                /* Table Container */
                .table-container {
                    padding: 25px;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
                    border-top: 4px solid #d2691e;
                }

                .table-container h4 {
                    border-bottom: 2px solid #f0e6d2;
                    padding-bottom: 15px;
                }
            </style>

            <!-- Page Title -->
            <div style="margin-bottom: 30px;">
                <h1 style="color: #5c4033; font-weight: 700; margin-bottom: 5px;">Settings</h1>
                <p style="color: #999;">Manage cafe settings and preferences</p>
            </div>

            <!-- Settings Content -->
            <div class="row">
                <!-- General Settings -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-cog" style="color: #8b4513; margin-right: 10px;"></i>
                            General Settings
                        </h4>
                        <form method="POST">
                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Cafe Name</label>
                                <input type="text" name="cafe_name" class="form-control" value="<?php echo htmlspecialchars($settings['cafe_name']); ?>" placeholder="Enter cafe name">
                                <div class="error-message" id="cafeNameError"></div>
                            </div>

                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($settings['email']); ?>" placeholder="Enter email">
                                <div class="error-message" id="emailError"></div>
                            </div>

                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Phone</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($settings['phone']); ?>" placeholder="Enter phone">
                            </div>

                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Address</label>
                                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($settings['address']); ?>" placeholder="Enter address">
                            </div>

                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Currency</label>
                                <select name="currency" class="form-control">
                                    <option value="USD ($)" <?php echo $settings['currency'] === 'USD ($)' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR (€)" <?php echo $settings['currency'] === 'EUR (€)' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP (£)" <?php echo $settings['currency'] === 'GBP (£)' ? 'selected' : ''; ?>>GBP (£)</option>
                                    <option value="INR (₹)" <?php echo $settings['currency'] === 'INR (₹)' ? 'selected' : ''; ?>>INR (₹)</option>
                                </select>
                            </div>

                            <button type="submit" name="save_general" class="btn-admin btn-admin-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Business Hours -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-clock" style="color: #8b4513; margin-right: 10px;"></i>
                            Business Hours
                        </h4>
                        <form method="POST">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Monday</label>
                                        <input type="time" name="monday_open" class="form-control" value="<?php echo htmlspecialchars($settings['monday_open']); ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">to</label>
                                        <input type="time" name="monday_close" class="form-control" value="<?php echo htmlspecialchars($settings['monday_close']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Saturday</label>
                                        <input type="time" name="saturday_open" class="form-control" value="<?php echo htmlspecialchars($settings['saturday_open']); ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">to</label>
                                        <input type="time" name="saturday_close" class="form-control" value="<?php echo htmlspecialchars($settings['saturday_close']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Sunday</label>
                                        <input type="time" name="sunday_open" class="form-control" value="<?php echo htmlspecialchars($settings['sunday_open']); ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">to</label>
                                        <input type="time" name="sunday_close" class="form-control" value="<?php echo htmlspecialchars($settings['sunday_close']); ?>">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_hours" class="btn-admin btn-admin-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-shield-alt" style="color: #8b4513; margin-right: 10px;"></i>
                            Security Settings
                        </h4>
                        <form method="POST">
                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                                <div class="error-message" id="currentPasswordError"></div>
                            </div>

                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                                <div class="error-message" id="newPasswordError"></div>
                            </div>

                            <div class="form-group">
                                <label style="color: #5c4033; font-weight: 600;">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                                <div class="error-message" id="confirmPasswordError"></div>
                            </div>

                            <button type="submit" name="change_password" class="btn-admin btn-admin-primary" style="width: 100%;">
                                <i class="fas fa-unlock"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-bell" style="color: #8b4513; margin-right: 10px;"></i>
                            Notifications
                        </h4>
                        <form method="POST">
                            <div class="form-group">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <input type="checkbox" name="email_notifications" id="emailNotif" value="1" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <label for="emailNotif" style="margin: 0; color: #5c4033; font-weight: 500; cursor: pointer;">
                                        Email Notifications
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <input type="checkbox" name="order_notifications" id="orderNotif" value="1" <?php echo $settings['order_notifications'] ? 'checked' : ''; ?>>
                                    <label for="orderNotif" style="margin: 0; color: #5c4033; font-weight: 500; cursor: pointer;">
                                        Order Notifications
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <input type="checkbox" name="stock_notifications" id="stockNotif" value="1" <?php echo $settings['stock_notifications'] ? 'checked' : ''; ?>>
                                    <label for="stockNotif" style="margin: 0; color: #5c4033; font-weight: 500; cursor: pointer;">
                                        Low Stock Alerts
                                    </label>
                                </div>
                            </div>

                            <button type="submit" name="save_notifications" class="btn-admin btn-admin-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Menu Header Settings -->
            <div class="row" id="menu-header-section">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-image" style="color: #8b4513; margin-right: 10px;"></i>
                            Menu Header Settings
                        </h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Menu Header Title</label>
                                        <input type="text" name="menu_header_title" class="form-control" value="<?php echo htmlspecialchars($settings['menu_header_title']); ?>" placeholder="e.g., ☕ Our Menu">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Menu Header Subtitle</label>
                                        <input type="text" name="menu_header_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['menu_header_subtitle']); ?>" placeholder="e.g., Discover Our Delicious Selection...">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Color</label>
                                        <input type="color" name="menu_header_title_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['menu_header_title_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Subtitle Color</label>
                                        <input type="color" name="menu_header_subtitle_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['menu_header_subtitle_color']); ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Font Size</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="range" id="titleSizeRange" min="20" max="80" value="<?php $size_num = intval($settings['menu_header_title_size']); echo ($size_num > 0) ? $size_num : 50; ?>" class="form-control" style="flex: 1;" onchange="updateTitleSize(this.value)">
                                            <input type="text" id="titleSizeInput" name="menu_header_title_size" class="form-control" style="width: 80px; text-align: center;" value="<?php echo htmlspecialchars($settings['menu_header_title_size']); ?>" onchange="updateTitleSizeInput()">
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Current: <span id="titleSizeDisplay"><?php echo htmlspecialchars($settings['menu_header_title_size']); ?></span></small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Background Image</label>
                                        <input type="file" name="menu_header_image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small style="color: #999; margin-top: 5px; display: block;">Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Previous Image in Use</label>
                                        <div style="padding: 10px; background: #f9f7f4; border: 2px solid #d2691e; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($settings['menu_header_image'])):
                                                $menuHeaderImageUrl = $settings['menu_header_image'];
                                                if (strpos($menuHeaderImageUrl, 'http') !== 0) {
                                                    $menuHeaderImageUrl = (strpos($menuHeaderImageUrl, '/') === 0) ? $menuHeaderImageUrl : '../' . $menuHeaderImageUrl;
                                                }
                                            ?>
                                                <img src="<?php echo htmlspecialchars($menuHeaderImageUrl); ?>" alt="Menu Header" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 6px;" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <span style="color:#999;">No image set</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Upload a new file above to change</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_menu_header" class="btn-admin btn-admin-primary" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-save"></i> Save Menu Header Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Home Header Settings -->
            <div class="row" id="home-header-section">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-home" style="color: #8b4513; margin-right: 10px;"></i>
                            Home Page Header Settings
                        </h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Title</label>
                                        <input type="text" name="home_hero_title" class="form-control" value="<?php echo htmlspecialchars($settings['home_hero_title']); ?>" placeholder="e.g., Welcome to Cafe Delicious">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Subtitle</label>
                                        <input type="text" name="home_hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['home_hero_subtitle']); ?>" placeholder="e.g., Your Premium Coffee Experience Awaits">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Title Color</label>
                                        <input type="color" name="home_hero_title_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['home_hero_title_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Subtitle Color</label>
                                        <input type="color" name="home_hero_subtitle_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['home_hero_subtitle_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Title Font Size</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="range" id="heroTitleSizeRange" min="30" max="100" value="<?php $size_num = intval($settings['home_hero_title_size']); echo ($size_num > 0) ? $size_num : 60; ?>" class="form-control" style="flex: 1;" onchange="updateHeroTitleSize(this.value)">
                                            <input type="text" id="heroTitleSizeInput" name="home_hero_title_size" class="form-control" style="width: 80px; text-align: center;" value="<?php echo htmlspecialchars($settings['home_hero_title_size']); ?>" onchange="updateHeroTitleSizeInput()">
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Current: <span id="heroTitleSizeDisplay"><?php echo htmlspecialchars($settings['home_hero_title_size']); ?></span></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Background Image</label>
                                        <div style="margin-bottom: 10px;">
                                            <small style="color: #666; display: block; margin-bottom: 8px;">Current: <?php echo htmlspecialchars($settings['home_header_image']); ?></small>
                                            <?php if (!empty($settings['home_header_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($settings['home_header_image']); ?>" alt="Home Header" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; border: 2px solid #d2691e;" onerror="this.style.display='none';">
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="home_header_image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small style="color: #999; margin-top: 5px; display: block;">Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Previous Image in Use</label>
                                        <div style="padding: 10px; background: #f9f7f4; border: 2px solid #d2691e; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($settings['home_header_image'])):
                                                $homeHeaderImageUrl = $settings['home_header_image'];
                                                if (strpos($homeHeaderImageUrl, 'http') !== 0) {
                                                    if (strpos($homeHeaderImageUrl, '../') !== 0 && strpos($homeHeaderImageUrl, '/') !== 0) {
                                                        $homeHeaderImageUrl = '../' . $homeHeaderImageUrl;
                                                    }
                                                }
                                            ?>
                                                <img src="<?php echo htmlspecialchars($homeHeaderImageUrl); ?>" alt="Home Header" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 6px;" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <span style="color:#999;">No image set</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Upload a new file above to change</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_home_header" class="btn-admin btn-admin-primary" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-save"></i> Save Home Header Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Wishlist Header Settings -->
            <div class="row" id="wishlist-header-section">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-heart" style="color: #8b4513; margin-right: 10px;"></i>
                            Wishlist Header Settings
                        </h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Title</label>
                                        <input type="text" name="wishlist_header_title" class="form-control" value="<?php echo htmlspecialchars($settings['wishlist_header_title']); ?>" placeholder="e.g., ❤️ My Wishlist">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Subtitle</label>
                                        <input type="text" name="wishlist_header_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['wishlist_header_subtitle']); ?>" placeholder="e.g., Your favorite items saved for later">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Color</label>
                                        <input type="color" name="wishlist_header_title_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['wishlist_header_title_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Subtitle Color</label>
                                        <input type="color" name="wishlist_header_subtitle_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['wishlist_header_subtitle_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Font Size</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="range" id="wishlistTitleSizeRange" min="30" max="80" value="<?php $size_num = intval($settings['wishlist_header_title_size']); echo ($size_num > 0) ? $size_num : 45; ?>" class="form-control" style="flex: 1;" onchange="updateWishlistTitleSize(this.value)">
                                            <input type="text" id="wishlistTitleSizeInput" name="wishlist_header_title_size" class="form-control" style="width: 80px; text-align: center;" value="<?php echo htmlspecialchars($settings['wishlist_header_title_size']); ?>" onchange="updateWishlistTitleSizeInput()">
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Current: <span id="wishlistTitleSizeDisplay"><?php echo htmlspecialchars($settings['wishlist_header_title_size']); ?></span></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Background Image</label>
                                        <div style="margin-bottom: 10px;">
                                            <small style="color: #666; display: block; margin-bottom: 8px;">Current: <?php echo htmlspecialchars($settings['wishlist_header_image']); ?></small>
                                            <?php if (!empty($settings['wishlist_header_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($settings['wishlist_header_image']); ?>" alt="Wishlist Header" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; border: 2px solid #d2691e;" onerror="this.style.display='none';">
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="wishlist_header_image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small style="color: #999; margin-top: 5px; display: block;">Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Previous Image in Use</label>
                                        <div style="padding: 10px; background: #f9f7f4; border: 2px solid #d2691e; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($settings['wishlist_header_image'])):
                                                $wishlistHeaderImageUrl = $settings['wishlist_header_image'];
                                                if (strpos($wishlistHeaderImageUrl, 'http') !== 0) {
                                                    if (strpos($wishlistHeaderImageUrl, '../') !== 0 && strpos($wishlistHeaderImageUrl, '/') !== 0) {
                                                        $wishlistHeaderImageUrl = '../' . $wishlistHeaderImageUrl;
                                                    }
                                                }
                                            ?>
                                                <img src="<?php echo htmlspecialchars($wishlistHeaderImageUrl); ?>" alt="Wishlist Header" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 6px;" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <span style="color:#999;">No image set</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Upload a new file above to change</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_wishlist_header" class="btn-admin btn-admin-primary" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-save"></i> Save Wishlist Header Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Cart Header Settings -->
            <div class="row" id="cart-header-section">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-shopping-cart" style="color: #8b4513; margin-right: 10px;"></i>
                            Cart Header Settings
                        </h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Title</label>
                                        <input type="text" name="cart_header_title" class="form-control" value="<?php echo htmlspecialchars($settings['cart_header_title']); ?>" placeholder="e.g., 🛒 Shopping Cart">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Subtitle</label>
                                        <input type="text" name="cart_header_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['cart_header_subtitle']); ?>" placeholder="e.g., Review and manage your items">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Color</label>
                                        <input type="color" name="cart_header_title_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['cart_header_title_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Subtitle Color</label>
                                        <input type="color" name="cart_header_subtitle_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['cart_header_subtitle_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Font Size</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="range" id="cartTitleSizeRange" min="30" max="80" value="<?php $size_num = intval($settings['cart_header_title_size']); echo ($size_num > 0) ? $size_num : 45; ?>" class="form-control" style="flex: 1;" onchange="updateCartTitleSize(this.value)">
                                            <input type="text" id="cartTitleSizeInput" name="cart_header_title_size" class="form-control" style="width: 80px; text-align: center;" value="<?php echo htmlspecialchars($settings['cart_header_title_size']); ?>" onchange="updateCartTitleSizeInput()">
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Current: <span id="cartTitleSizeDisplay"><?php echo htmlspecialchars($settings['cart_header_title_size']); ?></span></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Background Image</label>
                                        <div style="margin-bottom: 10px;">
                                            <small style="color: #666; display: block; margin-bottom: 8px;">Current: <?php echo htmlspecialchars($settings['cart_header_image']); ?></small>
                                            <?php if (!empty($settings['cart_header_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($settings['cart_header_image']); ?>" alt="Cart Header" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; border: 2px solid #d2691e;" onerror="this.style.display='none';">
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="cart_header_image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small style="color: #999; margin-top: 5px; display: block;">Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Previous Image in Use</label>
                                        <div style="padding: 10px; background: #f9f7f4; border: 2px solid #d2691e; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($settings['cart_header_image'])):
                                                $cartHeaderImageUrl = $settings['cart_header_image'];
                                                if (strpos($cartHeaderImageUrl, 'http') !== 0) {
                                                    if (strpos($cartHeaderImageUrl, '../') !== 0 && strpos($cartHeaderImageUrl, '/') !== 0) {
                                                        $cartHeaderImageUrl = '../' . $cartHeaderImageUrl;
                                                    }
                                                }
                                            ?>
                                                <img src="<?php echo htmlspecialchars($cartHeaderImageUrl); ?>" alt="Cart Header" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 6px;" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <span style="color:#999;">No image set</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Upload a new file above to change</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_cart_header" class="btn-admin btn-admin-primary" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-save"></i> Save Cart Header Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Product Review Header Settings -->
            <div class="row" id="review-header-section">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-star" style="color: #8b4513; margin-right: 10px;"></i>
                            Product Review Header Settings
                        </h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Title</label>
                                        <input type="text" name="review_header_title" class="form-control" value="<?php echo htmlspecialchars($settings['review_header_title']); ?>" placeholder="e.g., ⭐ Product Reviews">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Subtitle</label>
                                        <input type="text" name="review_header_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['review_header_subtitle']); ?>" placeholder="e.g., Share your experience with our products">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Color</label>
                                        <input type="color" name="review_header_title_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['review_header_title_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Subtitle Color</label>
                                        <input type="color" name="review_header_subtitle_color" class="form-control" style="height: 50px; cursor: pointer;" value="<?php echo htmlspecialchars($settings['review_header_subtitle_color']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Title Font Size</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="range" id="reviewTitleSizeRange" min="30" max="80" value="<?php $size_num = intval($settings['review_header_title_size']); echo ($size_num > 0) ? $size_num : 45; ?>" class="form-control" style="flex: 1;" onchange="updateReviewTitleSize(this.value)">
                                            <input type="text" id="reviewTitleSizeInput" name="review_header_title_size" class="form-control" style="width: 80px; text-align: center;" value="<?php echo htmlspecialchars($settings['review_header_title_size']); ?>" onchange="updateReviewTitleSizeInput()">
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Current: <span id="reviewTitleSizeDisplay"><?php echo htmlspecialchars($settings['review_header_title_size']); ?></span></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Header Background Image</label>
                                        <div style="margin-bottom: 10px;">
                                            <small style="color: #666; display: block; margin-bottom: 8px;">Current: <?php echo htmlspecialchars($settings['review_header_image']); ?></small>
                                            <?php if (!empty($settings['review_header_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($settings['review_header_image']); ?>" alt="Review Header" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; border: 2px solid #d2691e;" onerror="this.style.display='none';">
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="review_header_image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small style="color: #999; margin-top: 5px; display: block;">Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Previous Image in Use</label>
                                        <div style="padding: 10px; background: #f9f7f4; border: 2px solid #d2691e; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($settings['review_header_image'])):
                                                $reviewHeaderImageUrl = $settings['review_header_image'];
                                                if (strpos($reviewHeaderImageUrl, 'http') !== 0) {
                                                    if (strpos($reviewHeaderImageUrl, '../') !== 0 && strpos($reviewHeaderImageUrl, '/') !== 0) {
                                                        $reviewHeaderImageUrl = '../' . $reviewHeaderImageUrl;
                                                    }
                                                }
                                            ?>
                                                <img src="<?php echo htmlspecialchars($reviewHeaderImageUrl); ?>" alt="Review Header" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 6px;" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <span style="color:#999;">No image set</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #999; margin-top: 5px; display: block;">Upload a new file above to change</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_review_header" class="btn-admin btn-admin-primary" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-save"></i> Save Product Review Header Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Homepage Settings -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-home" style="color: #8b4513; margin-right: 10px;"></i>
                            Homepage Settings
                        </h4>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Title</label>
                                        <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($settings['hero_title']); ?>" placeholder="Enter hero title">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Hero Subtitle</label>
                                        <input type="text" name="hero_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['hero_subtitle']); ?>" placeholder="Enter hero subtitle">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">About Section Title</label>
                                        <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars($settings['about_title']); ?>" placeholder="Enter about section title">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">About Description</label>
                                        <textarea name="about_description" class="form-control" rows="4" placeholder="Enter about section description"><?php echo htmlspecialchars($settings['about_description']); ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Features Section Title</label>
                                        <input type="text" name="features_title" class="form-control" value="<?php echo htmlspecialchars($settings['features_title']); ?>" placeholder="Enter features section title">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Commitment Section Title</label>
                                        <input type="text" name="commitment_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment_title']); ?>" placeholder="Enter commitment section title">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Call to Action Title</label>
                                        <input type="text" name="cta_title" class="form-control" value="<?php echo htmlspecialchars($settings['cta_title']); ?>" placeholder="Enter CTA title">
                                    </div>

                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Call to Action Subtitle</label>
                                        <input type="text" name="cta_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['cta_subtitle']); ?>" placeholder="Enter CTA subtitle">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 1 Label</label>
                                        <input type="text" name="stat1_label" class="form-control" value="<?php echo htmlspecialchars($settings['stat1_label']); ?>" placeholder="e.g., Happy Customers">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 1 Value</label>
                                        <input type="text" name="stat1_value" class="form-control" value="<?php echo htmlspecialchars($settings['stat1_value']); ?>" placeholder="e.g., 100">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 2 Label</label>
                                        <input type="text" name="stat2_label" class="form-control" value="<?php echo htmlspecialchars($settings['stat2_label']); ?>" placeholder="e.g., Years Experience">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 2 Value</label>
                                        <input type="text" name="stat2_value" class="form-control" value="<?php echo htmlspecialchars($settings['stat2_value']); ?>" placeholder="e.g., 8+">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 3 Label</label>
                                        <input type="text" name="stat3_label" class="form-control" value="<?php echo htmlspecialchars($settings['stat3_label']); ?>" placeholder="e.g., Menu Items">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 3 Value</label>
                                        <input type="text" name="stat3_value" class="form-control" value="<?php echo htmlspecialchars($settings['stat3_value']); ?>" placeholder="e.g., 50">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 4 Label</label>
                                        <input type="text" name="stat4_label" class="form-control" value="<?php echo htmlspecialchars($settings['stat4_label']); ?>" placeholder="e.g., Orders Served">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600;">Statistic 4 Value</label>
                                        <input type="text" name="stat4_value" class="form-control" value="<?php echo htmlspecialchars($settings['stat4_value']); ?>" placeholder="e.g., 1000+">
                                    </div>
                                </div>
                            </div>

                            <!-- Features Section -->
                            <h5 style="color: #5c4033; font-weight: 600; margin-top: 30px; margin-bottom: 15px;">Features Section</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 1 Icon</label>
                                        <input type="text" name="feature1_icon" class="form-control" value="<?php echo htmlspecialchars($settings['feature1_icon']); ?>" placeholder="e.g., ☕">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 1 Title</label>
                                        <input type="text" name="feature1_title" class="form-control" value="<?php echo htmlspecialchars($settings['feature1_title']); ?>" placeholder="Feature title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 1 Description</label>
                                        <textarea name="feature1_desc" class="form-control" rows="2" placeholder="Feature description"><?php echo htmlspecialchars($settings['feature1_desc']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 2 Icon</label>
                                        <input type="text" name="feature2_icon" class="form-control" value="<?php echo htmlspecialchars($settings['feature2_icon']); ?>" placeholder="e.g., 👨‍💼">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 2 Title</label>
                                        <input type="text" name="feature2_title" class="form-control" value="<?php echo htmlspecialchars($settings['feature2_title']); ?>" placeholder="Feature title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 2 Description</label>
                                        <textarea name="feature2_desc" class="form-control" rows="2" placeholder="Feature description"><?php echo htmlspecialchars($settings['feature2_desc']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 3 Icon</label>
                                        <input type="text" name="feature3_icon" class="form-control" value="<?php echo htmlspecialchars($settings['feature3_icon']); ?>" placeholder="e.g., 🎨">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 3 Title</label>
                                        <input type="text" name="feature3_title" class="form-control" value="<?php echo htmlspecialchars($settings['feature3_title']); ?>" placeholder="Feature title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 3 Description</label>
                                        <textarea name="feature3_desc" class="form-control" rows="2" placeholder="Feature description"><?php echo htmlspecialchars($settings['feature3_desc']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 4 Icon</label>
                                        <input type="text" name="feature4_icon" class="form-control" value="<?php echo htmlspecialchars($settings['feature4_icon']); ?>" placeholder="e.g., ⚡">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 4 Title</label>
                                        <input type="text" name="feature4_title" class="form-control" value="<?php echo htmlspecialchars($settings['feature4_title']); ?>" placeholder="Feature title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 4 Description</label>
                                        <textarea name="feature4_desc" class="form-control" rows="2" placeholder="Feature description"><?php echo htmlspecialchars($settings['feature4_desc']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 5 Icon</label>
                                        <input type="text" name="feature5_icon" class="form-control" value="<?php echo htmlspecialchars($settings['feature5_icon']); ?>" placeholder="e.g., 💰">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 5 Title</label>
                                        <input type="text" name="feature5_title" class="form-control" value="<?php echo htmlspecialchars($settings['feature5_title']); ?>" placeholder="Feature title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 5 Description</label>
                                        <textarea name="feature5_desc" class="form-control" rows="2" placeholder="Feature description"><?php echo htmlspecialchars($settings['feature5_desc']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 6 Icon</label>
                                        <input type="text" name="feature6_icon" class="form-control" value="<?php echo htmlspecialchars($settings['feature6_icon']); ?>" placeholder="e.g., 🌍">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 6 Title</label>
                                        <input type="text" name="feature6_title" class="form-control" value="<?php echo htmlspecialchars($settings['feature6_title']); ?>" placeholder="Feature title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Feature 6 Description</label>
                                        <textarea name="feature6_desc" class="form-control" rows="2" placeholder="Feature description"><?php echo htmlspecialchars($settings['feature6_desc']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Commitment Section -->
                            <h5 style="color: #5c4033; font-weight: 600; margin-top: 30px; margin-bottom: 15px;">Commitment Section</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 1 Title</label>
                                        <input type="text" name="commitment1_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment1_title']); ?>" placeholder="Commitment title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 1 Description</label>
                                        <textarea name="commitment1_desc" class="form-control" rows="2" placeholder="Commitment description"><?php echo htmlspecialchars($settings['commitment1_desc']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 2 Title</label>
                                        <input type="text" name="commitment2_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment2_title']); ?>" placeholder="Commitment title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 2 Description</label>
                                        <textarea name="commitment2_desc" class="form-control" rows="2" placeholder="Commitment description"><?php echo htmlspecialchars($settings['commitment2_desc']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 3 Title</label>
                                        <input type="text" name="commitment3_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment3_title']); ?>" placeholder="Commitment title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 3 Description</label>
                                        <textarea name="commitment3_desc" class="form-control" rows="2" placeholder="Commitment description"><?php echo htmlspecialchars($settings['commitment3_desc']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 4 Title</label>
                                        <input type="text" name="commitment4_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment4_title']); ?>" placeholder="Commitment title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 4 Description</label>
                                        <textarea name="commitment4_desc" class="form-control" rows="2" placeholder="Commitment description"><?php echo htmlspecialchars($settings['commitment4_desc']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 5 Title</label>
                                        <input type="text" name="commitment5_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment5_title']); ?>" placeholder="Commitment title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 5 Description</label>
                                        <textarea name="commitment5_desc" class="form-control" rows="2" placeholder="Commitment description"><?php echo htmlspecialchars($settings['commitment5_desc']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 6 Title</label>
                                        <input type="text" name="commitment6_title" class="form-control" value="<?php echo htmlspecialchars($settings['commitment6_title']); ?>" placeholder="Commitment title">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #5c4033; font-weight: 600; font-size: 12px;">Commitment 6 Description</label>
                                        <textarea name="commitment6_desc" class="form-control" rows="2" placeholder="Commitment description"><?php echo htmlspecialchars($settings['commitment6_desc']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_homepage" class="btn-admin btn-admin-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Save Homepage Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- jQuery and Bootstrap JS -->
            <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

            <script>
            $(document).ready(function() {
                // General Settings Form Validation
                $('form').first().on('submit', function(e) {
                    // Only validate the general settings form (first form on page)
                    if (!$(this).find('input[name="save_general"]').length) return;
                    
                    clearGeneralErrors();
                    let isValid = true;
                    
                    const cafeName = $('input[name="cafe_name"]').val().trim();
                    const email = $('input[name="email"]').val().trim();
                    
                    // Cafe name validation
                    if (cafeName === '') {
                        showGeneralError('cafeNameError', 'Cafe name is required.');
                        $('input[name="cafe_name"]').addClass('is-invalid');
                        isValid = false;
                    } else if (cafeName.length < 2) {
                        showGeneralError('cafeNameError', 'Cafe name must be at least 2 characters.');
                        $('input[name="cafe_name"]').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (email === '') {
                        showGeneralError('emailError', 'Email is required.');
                        $('input[name="email"]').addClass('is-invalid');
                        isValid = false;
                    } else if (!emailRegex.test(email)) {
                        showGeneralError('emailError', 'Please enter a valid email address.');
                        $('input[name="email"]').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
                
                // Password Change Form Validation
                $('form').eq(1).on('submit', function(e) {
                    // Only validate the password change form (second form)
                    if (!$(this).find('input[name="change_password"]').length) return;
                    
                    clearPasswordErrors();
                    let isValid = true;
                    
                    const currentPassword = $('input[name="current_password"]').val().trim();
                    const newPassword = $('input[name="new_password"]').val().trim();
                    const confirmPassword = $('input[name="confirm_password"]').val().trim();
                    
                    // Current password validation
                    if (currentPassword === '') {
                        showPasswordError('currentPasswordError', 'Current password is required.');
                        $('input[name="current_password"]').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // New password validation
                    if (newPassword === '') {
                        showPasswordError('newPasswordError', 'New password is required.');
                        $('input[name="new_password"]').addClass('is-invalid');
                        isValid = false;
                    } else if (newPassword.length < 6) {
                        showPasswordError('newPasswordError', 'New password must be at least 6 characters.');
                        $('input[name="new_password"]').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Confirm password validation
                    if (confirmPassword === '') {
                        showPasswordError('confirmPasswordError', 'Please confirm your new password.');
                        $('input[name="confirm_password"]').addClass('is-invalid');
                        isValid = false;
                    } else if (newPassword !== confirmPassword) {
                        showPasswordError('confirmPasswordError', 'Passwords do not match.');
                        $('input[name="confirm_password"]').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
                
                // Real-time validation - clear errors when user starts typing
                $('input[type="text"], input[type="email"], input[type="password"]').on('input', function() {
                    const fieldName = $(this).attr('name');
                    let errorId = '';
                    
                    if (fieldName === 'cafe_name') errorId = 'cafeNameError';
                    else if (fieldName === 'email') errorId = 'emailError';
                    else if (fieldName === 'current_password') errorId = 'currentPasswordError';
                    else if (fieldName === 'new_password') errorId = 'newPasswordError';
                    else if (fieldName === 'confirm_password') errorId = 'confirmPasswordError';
                    
                    if (errorId) {
                        $('#' + errorId).hide();
                        $(this).removeClass('is-invalid');
                    }
                });
                
                function showGeneralError(errorId, message) {
                    $('#' + errorId).text(message).show();
                }
                
                function showPasswordError(errorId, message) {
                    $('#' + errorId).text(message).show();
                }
                
                function clearGeneralErrors() {
                    $('.error-message[id*="Name"], .error-message[id*="email"]').hide();
                    $('input[name="cafe_name"], input[name="email"]').removeClass('is-invalid');
                }
                
                function clearPasswordErrors() {
                    $('.error-message[id*="Password"]').hide();
                    $('input[type="password"]').removeClass('is-invalid');
                }
                
                // Menu header font size slider functions
                window.updateTitleSize = function(value) {
                    const sizeValue = parseInt(value);
                    if (!isNaN(sizeValue)) {
                        document.getElementById('titleSizeInput').value = sizeValue + 'px';
                        document.getElementById('titleSizeDisplay').textContent = sizeValue + 'px';
                    }
                };
                
                window.updateTitleSizeInput = function() {
                    const input = document.getElementById('titleSizeInput').value;
                    const numValue = parseInt(input);
                    if (!isNaN(numValue) && numValue >= 20 && numValue <= 80) {
                        document.getElementById('titleSizeRange').value = numValue;
                        document.getElementById('titleSizeDisplay').textContent = numValue + 'px';
                    }
                };
                
                // Home hero title font size slider functions
                window.updateHeroTitleSize = function(value) {
                    const sizeValue = parseInt(value);
                    if (!isNaN(sizeValue)) {
                        document.getElementById('heroTitleSizeInput').value = sizeValue + 'px';
                        document.getElementById('heroTitleSizeDisplay').textContent = sizeValue + 'px';
                    }
                };
                
                window.updateHeroTitleSizeInput = function() {
                    const input = document.getElementById('heroTitleSizeInput').value;
                    const numValue = parseInt(input);
                    if (!isNaN(numValue) && numValue >= 30 && numValue <= 100) {
                        document.getElementById('heroTitleSizeRange').value = numValue;
                        document.getElementById('heroTitleSizeDisplay').textContent = numValue + 'px';
                    }
                };
                
                // Wishlist header title font size slider functions
                window.updateWishlistTitleSize = function(value) {
                    const sizeValue = parseInt(value);
                    if (!isNaN(sizeValue)) {
                        document.getElementById('wishlistTitleSizeInput').value = sizeValue + 'px';
                        document.getElementById('wishlistTitleSizeDisplay').textContent = sizeValue + 'px';
                    }
                };
                
                window.updateWishlistTitleSizeInput = function() {
                    const input = document.getElementById('wishlistTitleSizeInput').value;
                    const numValue = parseInt(input);
                    if (!isNaN(numValue) && numValue >= 30 && numValue <= 80) {
                        document.getElementById('wishlistTitleSizeRange').value = numValue;
                        document.getElementById('wishlistTitleSizeDisplay').textContent = numValue + 'px';
                    }
                };
                
                // Cart header title font size slider functions
                window.updateCartTitleSize = function(value) {
                    const sizeValue = parseInt(value);
                    if (!isNaN(sizeValue)) {
                        document.getElementById('cartTitleSizeInput').value = sizeValue + 'px';
                        document.getElementById('cartTitleSizeDisplay').textContent = sizeValue + 'px';
                    }
                };
                
                window.updateCartTitleSizeInput = function() {
                    const input = document.getElementById('cartTitleSizeInput').value;
                    const numValue = parseInt(input);
                    if (!isNaN(numValue) && numValue >= 30 && numValue <= 80) {
                        document.getElementById('cartTitleSizeRange').value = numValue;
                        document.getElementById('cartTitleSizeDisplay').textContent = numValue + 'px';
                    }
                };
                
                // Product review header title font size slider functions
                window.updateReviewTitleSize = function(value) {
                    const sizeValue = parseInt(value);
                    if (!isNaN(sizeValue)) {
                        document.getElementById('reviewTitleSizeInput').value = sizeValue + 'px';
                        document.getElementById('reviewTitleSizeDisplay').textContent = sizeValue + 'px';
                    }
                };
                
                window.updateReviewTitleSizeInput = function() {
                    const input = document.getElementById('reviewTitleSizeInput').value;
                    const numValue = parseInt(input);
                    if (!isNaN(numValue) && numValue >= 30 && numValue <= 80) {
                        document.getElementById('reviewTitleSizeRange').value = numValue;
                        document.getElementById('reviewTitleSizeDisplay').textContent = numValue + 'px';
                    }
                };
                
                // Initialize slider sync on page load
                document.addEventListener('DOMContentLoaded', function() {
                    const titleSizeInput = document.getElementById('titleSizeInput');
                    const titleSizeRange = document.getElementById('titleSizeRange');
                    if (titleSizeInput && titleSizeRange) {
                        const currentValue = parseInt(titleSizeInput.value);
                        if (!isNaN(currentValue)) {
                            titleSizeRange.value = currentValue;
                        }
                    }
                    
                    const heroTitleSizeInput = document.getElementById('heroTitleSizeInput');
                    const heroTitleSizeRange = document.getElementById('heroTitleSizeRange');
                    if (heroTitleSizeInput && heroTitleSizeRange) {
                        const currentValue = parseInt(heroTitleSizeInput.value);
                        if (!isNaN(currentValue)) {
                            heroTitleSizeRange.value = currentValue;
                        }
                    }
                    
                    const wishlistTitleSizeInput = document.getElementById('wishlistTitleSizeInput');
                    const wishlistTitleSizeRange = document.getElementById('wishlistTitleSizeRange');
                    if (wishlistTitleSizeInput && wishlistTitleSizeRange) {
                        const currentValue = parseInt(wishlistTitleSizeInput.value);
                        if (!isNaN(currentValue)) {
                            wishlistTitleSizeRange.value = currentValue;
                        }
                    }
                    
                    const cartTitleSizeInput = document.getElementById('cartTitleSizeInput');
                    const cartTitleSizeRange = document.getElementById('cartTitleSizeRange');
                    if (cartTitleSizeInput && cartTitleSizeRange) {
                        const currentValue = parseInt(cartTitleSizeInput.value);
                        if (!isNaN(currentValue)) {
                            cartTitleSizeRange.value = currentValue;
                        }
                    }
                    
                    const reviewTitleSizeInput = document.getElementById('reviewTitleSizeInput');
                    const reviewTitleSizeRange = document.getElementById('reviewTitleSizeRange');
                    if (reviewTitleSizeInput && reviewTitleSizeRange) {
                        const currentValue = parseInt(reviewTitleSizeInput.value);
                        if (!isNaN(currentValue)) {
                            reviewTitleSizeRange.value = currentValue;
                        }
                    }
                });
            });
            </script>
            <?php if (isset($scroll_to)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const element = document.getElementById('<?php echo $scroll_to; ?>');
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            </script>
            <?php endif; ?>

<?php include 'footer.php'; ?>
