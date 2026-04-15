<?php
session_start();

error_reporting(0);

include '../database/DB.php';
include 'header.php';

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$response = '';
$response_type = '';

// Handle mission/vision update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_about'])) {
    $title = sanitize_input($_POST['title'] ?? '');
    $content = sanitize_input($_POST['content'] ?? '');
    $section_type = sanitize_input($_POST['section_type'] ?? 'mission');

    if (empty($title) || empty($content)) {
        $response = '❌ Title and content cannot be empty';
        $response_type = 'danger';
    } else {
        // Check if section exists
        $check_sql = "SELECT id FROM about_us_content WHERE section_type = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 's', $section_type);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing
            $update_sql = "UPDATE about_us_content SET title = ?, content = ? WHERE section_type = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'sss', $title, $content, $section_type);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $response = '✅ Content updated successfully!';
                $response_type = 'success';
            } else {
                $response = '❌ Error updating content: ' . mysqli_stmt_error($update_stmt);
                $response_type = 'danger';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            // Insert new
            $insert_sql = "INSERT INTO about_us_content (title, content, section_type) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'sss', $title, $content, $section_type);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $response = '✅ Content added successfully!';
                $response_type = 'success';
            } else {
                $response = '❌ Error adding content: ' . mysqli_stmt_error($insert_stmt);
                $response_type = 'danger';
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Handle team member operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team_member'])) {
    $name = sanitize_input($_POST['name'] ?? '');
    $position = sanitize_input($_POST['position'] ?? '');
    $bio = sanitize_input($_POST['bio'] ?? '');
    $image_path = '';

    if (empty($name) || empty($position)) {
        $response = '❌ Name and position are required';
        $response_type = 'danger';
    } else {
        // Handle file upload
        if (isset($_FILES['team_image']) && $_FILES['team_image']['error'] === UPLOAD_ERR_OK) {
            // Create images directory if it doesn't exist
            $upload_dir = '../images/team_members/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Get file information
            $file_name = $_FILES['team_image']['name'];
            $file_tmp = $_FILES['team_image']['tmp_name'];
            $file_size = $_FILES['team_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file_ext, $allowed_ext)) {
                $response = '❌ Invalid file type. Only JPG, PNG, GIF, and WebP are allowed';
                $response_type = 'danger';
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                $response = '❌ File size exceeds 5MB limit';
                $response_type = 'danger';
            } else {
                // Generate unique filename
                $unique_name = 'team_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $file_ext;
                $upload_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image_path = 'images/team_members/' . $unique_name;
                } else {
                    $response = '❌ Failed to upload image';
                    $response_type = 'danger';
                }
            }
        }

        if (empty($response)) {
            $insert_sql = "INSERT INTO team_members (name, position, bio, image_path, display_order) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            $order = 0;
            mysqli_stmt_bind_param($insert_stmt, 'ssssi', $name, $position, $bio, $image_path, $order);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $response = '✅ Team member added successfully!';
                $response_type = 'success';
            } else {
                $response = '❌ Error adding team member: ' . mysqli_stmt_error($insert_stmt);
                $response_type = 'danger';
            }
            mysqli_stmt_close($insert_stmt);
        }
    }
}

// Handle team member deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_team_member'])) {
    $member_id = intval($_POST['member_id']);
    $delete_sql = "DELETE FROM team_members WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, 'i', $member_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $response = '✅ Team member removed!';
        $response_type = 'success';
    } else {
        $response = '❌ Error removing team member';
        $response_type = 'danger';
    }
    mysqli_stmt_close($delete_stmt);
}

// Fetch about content
$sections = ['mission' => 'Mission', 'vision' => 'Vision', 'history' => 'History', 'values' => 'Values'];
$about_content = [];

foreach ($sections as $key => $label) {
    $sql = "SELECT title, content FROM about_us_content WHERE section_type = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $about_content[$key] = mysqli_fetch_assoc($result);
    } else {
        $about_content[$key] = ['title' => $label, 'content' => ''];
    }
    mysqli_stmt_close($stmt);
}

// Fetch team members
$team_sql = "SELECT id, name, position, bio, image_path FROM team_members ORDER BY display_order ASC";
$team_result = mysqli_query($conn, $team_sql);
$team_members = [];

if ($team_result && mysqli_num_rows($team_result) > 0) {
    while ($row = mysqli_fetch_assoc($team_result)) {
        $team_members[] = $row;
    }
}

// Fetch careers info
$careers_sql = "SELECT title, description, email FROM careers_info LIMIT 1";
$careers_result = mysqli_query($conn, $careers_sql);
$careers = ['title' => 'Want to work with us?', 'description' => '', 'email' => ''];

if ($careers_result && mysqli_num_rows($careers_result) > 0) {
    $careers = mysqli_fetch_assoc($careers_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
            margin-top: 80px;
        }

        .admin-container {
            max-width: 1000px;
            margin: 40px auto;
        }

        .admin-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .admin-card h3 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-control, .form-control-lg {
            border: 2px solid #e8e4d8;
            border-radius: 8px;
        }

        .form-control:focus, .form-control-lg:focus {
            border-color: #8b4513;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 69, 19, 0.3);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .team-item {
            background: #f9f7f4;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert {
            border-radius: 8px;
            animation: slideInUp 0.5s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
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

        @media (max-width: 768px) {
            .admin-card {
                padding: 15px;
            }

            .team-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .custom-file-input::file-selector-button {
            background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .custom-file-label {
            color: #999;
        }

        .custom-file-input:focus ~ .custom-file-label {
            border-color: #8b4513;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#content">Content</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#team">Team Members</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#careers">Careers</a></li>
    </ul>

    <div class="tab-content">
        <!-- Content Tab -->
        <div id="content" class="tab-pane fade show active">
            <?php if ($response): ?>
                <div class="alert alert-<?php echo $response_type; ?>" role="alert">
                    <?php echo $response; ?>
                </div>
                <?php $response = ''; ?>
            <?php endif; ?>

            <div class="admin-card">
                <h3><i class="fas fa-file-alt"></i> Manage Content</h3>

                <?php foreach ($about_content as $type => $data): ?>
                    <form method="POST" action="manage_about_us.php" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #e8e4d8;">
                        <h5 style="color: #5c4033; margin-bottom: 15px;">
                            <i class="fas fa-edit"></i> <?php echo ucfirst($type); ?>
                        </h5>

                        <div class="form-group">
                            <label for="title_<?php echo $type; ?>">Title</label>
                            <input type="text" class="form-control" id="title_<?php echo $type; ?>" name="title" value="<?php echo sanitize_input($data['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="content_<?php echo $type; ?>">Content</label>
                            <textarea class="form-control" id="content_<?php echo $type; ?>" name="content" rows="5" required><?php echo sanitize_input($data['content']); ?></textarea>
                        </div>

                        <input type="hidden" name="section_type" value="<?php echo $type; ?>">
                        <button type="submit" name="update_about" class="btn-submit">
                            <i class="fas fa-save"></i> Save <?php echo ucfirst($type); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Team Tab -->
        <div id="team" class="tab-pane fade">
            <div class="admin-card">
                <h3><i class="fas fa-users"></i> Team Members</h3>

                <form method="POST" action="manage_about_us.php" enctype="multipart/form-data" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #e8e4d8;">
                    <h5 style="color: #5c4033; margin-bottom: 15px;">Add New Team Member</h5>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Full name" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="position">Position</label>
                            <input type="text" class="form-control" id="position" name="position" placeholder="Job position" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio (Optional)</label>
                        <textarea class="form-control" id="bio" name="bio" rows="2" placeholder="Short bio"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="team_image">Team Member Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="team_image" name="team_image" accept="image/*" required>
                            <label class="custom-file-label" for="team_image">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Max file size: 5MB. Allowed formats: JPG, PNG, GIF, WebP</small>
                    </div>

                    <button type="submit" name="add_team_member" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Team Member
                    </button>
                </form>

                <h5 style="color: #5c4033; margin-bottom: 15px;">Current Team Members</h5>
                <?php if (!empty($team_members)): ?>
                    <?php foreach ($team_members as $member): ?>
                        <div class="team-item" style="display: flex; gap: 15px; align-items: center;">
                            <div>
                                <?php if (!empty($member['image_path']) && file_exists('../' . $member['image_path'])): ?>
                                    <img src="../<?php echo $member['image_path']; ?>" alt="<?php echo sanitize_input($member['name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #e8e4d8; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                                        <i class="fas fa-user" style="font-size: 24px;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <strong><?php echo sanitize_input($member['name']); ?></strong>
                                <p style="margin: 5px 0; color: #666;"><?php echo sanitize_input($member['position']); ?></p>
                                <?php if ($member['bio']): ?>
                                    <small style="color: #999;"><?php echo sanitize_input($member['bio']); ?></small>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="manage_about_us.php" style="display: inline;">
                                <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                <button type="submit" name="delete_team_member" class="btn-delete" onclick="return confirm('Delete this member?')">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">No team members yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Careers Tab -->
        <div id="careers" class="tab-pane fade">
            <div class="admin-card">
                <h3><i class="fas fa-briefcase"></i> Careers Section</h3>

                <form method="POST" action="manage_about_us.php">
                    <div class="form-group">
                        <label for="careers_title">Title</label>
                        <input type="text" class="form-control" id="careers_title" name="title" value="<?php echo sanitize_input($careers['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="careers_desc">Description</label>
                        <textarea class="form-control" id="careers_desc" name="description" rows="5" required><?php echo sanitize_input($careers['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="careers_email">Contact Email</label>
                        <input type="email" class="form-control" id="careers_email" name="email" value="<?php echo sanitize_input($careers['email']); ?>" required>
                    </div>

                    <button type="submit" name="update_careers" class="btn-submit">
                        <i class="fas fa-save"></i> Save Careers Info
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Auto-hide alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });

        // File input label update
        const fileInput = document.getElementById('team_image');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'Choose file...';
                const label = this.nextElementSibling;
                if (label) {
                    label.textContent = fileName;
                }
            });
        }
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>
