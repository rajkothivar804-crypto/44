<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'hader.php';
include 'database/DB.php';

$alert_message = '';
$alert_type = '';
$profile_data = array();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Fetch user profile data
$sql = "SELECT id, fullname, email, phone, address, profile_picture, created_at FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $profile_data = mysqli_fetch_assoc($result);
} else {
    $alert_message = "User profile not found!";
    $alert_type = "danger";
}
mysqli_stmt_close($stmt);

// Fetch contact messages for this user
$messages_sql = "SELECT id, name, email, subject, message, status, created_at, admin_reply, admin_reply_date FROM contact_messages WHERE user_id = ? ORDER BY created_at DESC";
$messages_stmt = mysqli_prepare($conn, $messages_sql);
mysqli_stmt_bind_param($messages_stmt, 'i', $user_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);
$contact_messages = [];

if ($messages_result && mysqli_num_rows($messages_result) > 0) {
    while ($row = mysqli_fetch_assoc($messages_result)) {
        $contact_messages[] = $row;
    }
}
mysqli_stmt_close($messages_stmt);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Handle profile picture upload
    $profile_picture_path = $profile_data['profile_picture']; // Keep existing if no new upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if (!empty($profile_data['profile_picture']) && file_exists($profile_data['profile_picture'])) {
                    unlink($profile_data['profile_picture']);
                }
                $profile_picture_path = $upload_path;
            } else {
                $alert_message = "Error uploading profile picture!";
                $alert_type = "danger";
            }
        } else {
            $alert_message = "Invalid file type! Only JPG, PNG, GIF allowed.";
            $alert_type = "danger";
        }
    }

    // Validation
    if (empty($fullname) || strlen($fullname) < 3) {
        $alert_message = "Full name must be at least 3 characters!";
        $alert_type = "danger";
    } elseif (empty($phone) || !preg_match('/^[0-9]{10,}$/', $phone)) {
        $alert_message = "Phone must be at least 10 digits!";
        $alert_type = "danger";
    } elseif (empty($address)) {
        $alert_message = "Address is required!";
        $alert_type = "danger";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $alert_message = "New passwords do not match!";
        $alert_type = "danger";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $alert_message = "New password must be at least 6 characters!";
        $alert_type = "danger";
    } else {
        // If password change is requested, verify current password
        if (!empty($new_password)) {
            $pass_sql = "SELECT password FROM users WHERE id = ?";
            $pass_stmt = mysqli_prepare($conn, $pass_sql);
            mysqli_stmt_bind_param($pass_stmt, 'i', $user_id);
            mysqli_stmt_execute($pass_stmt);
            $pass_result = mysqli_stmt_get_result($pass_stmt);
            $pass_data = mysqli_fetch_assoc($pass_result);
            mysqli_stmt_close($pass_stmt);

            if (!password_verify($current_password, $pass_data['password'])) {
                $alert_message = "Current password is incorrect!";
                $alert_type = "danger";
            } else {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET fullname = ?, phone = ?, address = ?, password = ?, profile_picture = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, 'sssssi', $fullname, $phone, $address, $hashed_password, $profile_picture_path, $user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    $alert_message = "Profile updated successfully (password changed)!";
                    $alert_type = "success";
                    $_SESSION['user_name'] = $fullname;
                    $profile_data['fullname'] = $fullname;
                    $profile_data['phone'] = $phone;
                    $profile_data['address'] = $address;
                    $profile_data['profile_picture'] = $profile_picture_path;
                } else {
                    $alert_message = "Error updating profile: " . mysqli_error($conn);
                    $alert_type = "danger";
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            // Update without password change
            $update_sql = "UPDATE users SET fullname = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'ssssi', $fullname, $phone, $address, $profile_picture_path, $user_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $alert_message = "Profile updated successfully!";
                $alert_type = "success";
                $_SESSION['user_name'] = $fullname;
                $profile_data['fullname'] = $fullname;
                $profile_data['phone'] = $phone;
                $profile_data['address'] = $address;
                $profile_data['profile_picture'] = $profile_picture_path;
            } else {
                $alert_message = "Error updating profile: " . mysqli_error($conn);
                $alert_type = "danger";
            }
            mysqli_stmt_close($update_stmt);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Cafe Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 50%, #f5f5dc 100%);
            padding-top: 100px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .profile-wrapper {
            padding: 40px 20px;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(67, 46, 26, 0.2);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-header {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 50%, #a0522d 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .profile-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            position: relative;
            z-index: 1;
        }
        
        .profile-header i {
            font-size: 45px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .profile-body {
            padding: 50px;
        }
        
        .profile-section {
            margin-bottom: 40px;
            animation: fadeIn 0.8s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .profile-section h3 {
            color: #5c4033;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, #8b4513, #d2691e) 1;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-section h3 i {
            color: #8b4513;
            font-size: 24px;
        }
        
        .info-display {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 0;
        }
        
        .info-card {
            padding: 20px;
            background: linear-gradient(135deg, #f5f5dc20 0%, #fffacd40 100%);
            border-radius: 12px;
            border-left: 5px solid #8b4513;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.2);
        }
        
        .info-card strong {
            color: #8b4513;
            display: block;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .info-card p {
            color: #333;
            margin: 0;
            font-size: 15px;
            word-break: break-word;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            color: #5c4033;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fafaf9;
        }
        
        .form-control:focus {
            border-color: #8b4513;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
            background: white;
        }
        
        .form-control:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            border: none;
            color: white;
            padding: 14px 35px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139, 69, 19, 0.3);
        }
        
        .btn-edit i {
            margin-right: 8px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            font-size: 16px;
            text-transform: uppercase;
            cursor: pointer;
        }
        
        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            color: white;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            font-size: 16px;
            text-transform: uppercase;
            cursor: pointer;
        }
        
        .btn-cancel:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.3);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .edit-hidden {
            display: none !important;
        }
        
        .password-note {
            font-size: 12px;
            color: #888;
            margin-top: 8px;
            display: block;
            font-style: italic;
        }
        
        .alert {
            border-radius: 12px;
            margin-bottom: 25px;
            border: none;
            padding: 18px 20px;
            font-weight: 500;
            animation: slideDown 0.4s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 35px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #8b451320 0%, #d2691e20 100%);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #8b451140;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(139, 69, 19, 0.05) 100%);
            pointer-events: none;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.15);
            border-color: #8b4513;
        }
        
        .stat-card strong {
            color: #5c4033;
            display: block;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        
        .stat-card p {
            color: #666;
            font-size: 13px;
            margin: 8px 0 0 0;
            position: relative;
            z-index: 1;
        }
        
        .badge {
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #8b4513 50%, transparent 100%);
            margin: 30px 0;
        }
        
        .text-muted {
            color: #888 !important;
            font-size: 13px;
        }
        
        @media (max-width: 600px) {
            .profile-body {
                padding: 30px;
            }
            
            .info-display {
                grid-template-columns: 1fr;
            }
            
            .profile-header h1 {
                font-size: 28px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn-save, .btn-cancel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="profile-wrapper">
        <div class="profile-container">
            <div class="profile-header">
                <h1>
                    <?php if (!empty($profile_data['profile_picture']) && file_exists($profile_data['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($profile_data['profile_picture']); ?>" 
                             alt="Profile Picture" 
                             style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid white; margin-right: 15px;">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="margin-right: 15px;"></i>
                    <?php endif; ?>
                    My Profile
                </h1>
            </div>
            
            <div class="profile-body">
                <?php if (!empty($alert_message)): ?>
                    <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                        <strong><?php echo ($alert_type === 'success') ? '✓ Success!' : '✕ Error!'; ?></strong>
                        <?php echo $alert_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($profile_data)): ?>
                    
                    <div class="profile-stats">
                        <div class="stat-card">
                            <strong><i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>Member Since</strong>
                            <p><?php echo date('M d, Y', strtotime($profile_data['created_at'])); ?></p>
                        </div>
                        <div class="stat-card">
                            <strong><i class="fas fa-check-circle" style="margin-right: 8px;"></i>Account Status</strong>
                            <p><span class="badge badge-success">Active</span></p>
                        </div>
                    </div>
                    
                    <form id="profileForm" method="post" action="" enctype="multipart/form-data">
                        <!-- View Mode -->
                        <div id="viewMode" class="profile-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                            
                            <div class="info-display">
                                <div class="info-card">
                                    <strong><i class="fas fa-user-tag"></i> Full Name</strong>
                                    <p><?php echo htmlspecialchars($profile_data['fullname']); ?></p>
                                </div>
                                <div class="info-card">
                                    <strong><i class="fas fa-envelope"></i> Email Address</strong>
                                    <p><?php echo htmlspecialchars($profile_data['email']); ?></p>
                                </div>
                                <div class="info-card">
                                    <strong><i class="fas fa-phone"></i> Phone Number</strong>
                                    <p><?php echo htmlspecialchars($profile_data['phone']); ?></p>
                                </div>
                                <div class="info-card">
                                    <strong><i class="fas fa-map-marker-alt"></i> Address</strong>
                                    <p><?php echo htmlspecialchars($profile_data['address']); ?></p>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-edit" onclick="toggleEditMode()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                        </div>
                        
                        <!-- Edit Mode -->
                        <div id="editMode" class="profile-section edit-hidden">
                            <h3><i class="fas fa-pencil-alt"></i> Edit Personal Information</h3>
                            
                            <div class="form-group">
                                <label for="profile_picture"><i class="fas fa-camera"></i> Profile Picture</label>
                                <input type="file" class="form-control" name="profile_picture" id="profile_picture" 
                                       accept="image/*">
                                <small class="text-muted">Upload a new profile picture (JPG, PNG, GIF). Leave empty to keep current picture.</small>
                                <?php if (!empty($profile_data['profile_picture']) && file_exists($profile_data['profile_picture'])): ?>
                                    <div style="margin-top: 10px;">
                                        <img src="<?php echo htmlspecialchars($profile_data['profile_picture']); ?>" 
                                             alt="Current Profile Picture" 
                                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #8b4513;">
                                        <small class="text-muted d-block">Current profile picture</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="fullname"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" class="form-control" name="fullname" id="fullname" 
                                       value="<?php echo htmlspecialchars($profile_data['fullname']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email Address (Cannot be changed)</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo htmlspecialchars($profile_data['email']); ?>" disabled>
                                <small class="text-muted">Email address cannot be changed for security reasons</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" class="form-control" name="phone" id="phone" 
                                       value="<?php echo htmlspecialchars($profile_data['phone']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                                <textarea class="form-control" name="address" id="address" required><?php echo htmlspecialchars($profile_data['address']); ?></textarea>
                            </div>
                            
                            <hr>
                            <h3 style="font-size: 18px; border: none; padding: 0;"><i class="fas fa-lock"></i> Change Password (Optional)</h3>
                            
                            <div class="form-group" style="margin-top: 20px;">
                                <label for="current_password"><i class="fas fa-key"></i> Current Password</label>
                                <input type="password" class="form-control" name="current_password" id="current_password" 
                                       placeholder="Enter your current password (only if changing password)">
                                <small class="password-note">Leave blank if you don't want to change password</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password"><i class="fas fa-lock-open"></i> New Password</label>
                                <input type="password" class="form-control" name="new_password" id="new_password" 
                                       placeholder="Enter new password (min 6 characters)">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" 
                                       placeholder="Confirm new password">
                            </div>
                            

                            <br>
                            <br>
                            <br>
                            <div class="button-group">
                                <button type="submit" name="update_profile" class="btn btn-save">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-cancel" onclick="toggleEditMode()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                    
                <?php endif; ?>

                <!-- Contact Messages Section -->
                <hr>
                <div class="profile-section">
                    <h3><i class="fas fa-envelope"></i> Contact Messages & Admin Replies</h3>
                    
                    <?php if (count($contact_messages) > 0): ?>
                        <div style="display: grid; gap: 20px;">
                            <?php foreach ($contact_messages as $msg): ?>
                                <div style="background: white; border: 2px solid #e0e0e0; border-radius: 12px; padding: 20px; transition: all 0.3s ease; hover {transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1);}">
                                    <!-- Message Header -->
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px;">
                                        <div>
                                            <h5 style="color: #8b4513; font-weight: 700; margin: 0; font-size: 18px;">
                                                <i class="fas fa-comment"></i> <?php echo htmlspecialchars($msg['subject']); ?>
                                            </h5>
                                            <small style="color: #999; display: block; margin-top: 5px;">
                                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y \a\t h:i A', strtotime($msg['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php 
                                                if ($msg['status'] === 'Replied') {
                                                    echo '<span style="background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">✓ Replied</span>';
                                                } elseif ($msg['status'] === 'Read') {
                                                    echo '<span style="background: #ffc107; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Viewed</span>';
                                                } else {
                                                    echo '<span style="background: #17a2b8; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Pending</span>';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Your Message -->
                                    <div style="margin-bottom: 15px; padding: 12px; background: #f9f9f9; border-left: 4px solid #8b4513; border-radius: 4px;">
                                        <strong style="color: #8b4513; display: block; margin-bottom: 8px;">📧 Your Message:</strong>
                                        <p style="margin: 0; color: #333; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Admin Reply (if exists) -->
                                    <?php if (!empty($msg['admin_reply'])): ?>
                                        <div style="padding: 12px; background: #f0f8f5; border-left: 4px solid #28a745; border-radius: 4px;">
                                            <strong style="color: #28a745; display: block; margin-bottom: 8px;">
                                                <i class="fas fa-reply"></i> Admin Reply:
                                            </strong>
                                            <p style="margin: 0; color: #333; line-height: 1.6;">
                                                <?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?>
                                            </p>
                                            <small style="color: #999; display: block; margin-top: 10px;">
                                                <i class="fas fa-check-circle"></i> Replied on: <?php echo date('M d, Y \a\t h:i A', strtotime($msg['admin_reply_date'])); ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div style="padding: 12px; background: #fff8f0; border-left: 4px solid #ffc107; border-radius: 4px; color: #666;">
                                            <i class="fas fa-clock"></i> Waiting for admin response...
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 12px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
                            <p style="color: #999; font-size: 16px;">No contact messages yet.</p>
                            <p style="color: #bbb; font-size: 14px;">
                                <a href="contact.php" style="color: #8b4513; text-decoration: none; font-weight: 600;">Send us a message</a> and we'll get back to you soon!
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleEditMode() {
            const viewMode = document.getElementById('viewMode');
            const editMode = document.getElementById('editMode');
            
            if (viewMode.classList.contains('edit-hidden')) {
                viewMode.classList.remove('edit-hidden');
                editMode.classList.add('edit-hidden');
            } else {
                viewMode.classList.add('edit-hidden');
                editMode.classList.remove('edit-hidden');
            }
        }


    </script>

</body>
</html>

<?php include 'footer.php'; ?>
