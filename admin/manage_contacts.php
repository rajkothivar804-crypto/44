<?php
// Check if user is logged in and is admin
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit();
// }

include '../database/DB.php';
include 'header.php';

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Ensure admin_reply columns exist in contact_messages table
$check_reply_col = "SHOW COLUMNS FROM contact_messages LIKE 'admin_reply'";
$check_result = mysqli_query($conn, $check_reply_col);

if (!$check_result || mysqli_num_rows($check_result) == 0) {
    $add_col = "ALTER TABLE contact_messages ADD COLUMN admin_reply TEXT NULL, ADD COLUMN admin_reply_date TIMESTAMP NULL";
    mysqli_query($conn, $add_col);
}

$response = '';
$response_type = '';

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $message_id = intval($_POST['message_id']);
    
    $delete_sql = "DELETE FROM contact_messages WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, 'i', $message_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $response = '✅ Message deleted successfully!';
        $response_type = 'success';
    } else {
        $response = '❌ Error deleting message: ' . mysqli_stmt_error($delete_stmt);
        $response_type = 'danger';
    }
    mysqli_stmt_close($delete_stmt);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $message_id = intval($_POST['message_id']);
    $status = sanitize_input($_POST['status']);
    
    $allowed_statuses = ['New', 'Read', 'Replied'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'New';
    }
    
    $update_sql = "UPDATE contact_messages SET status = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, 'si', $status, $message_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $response = '✅ Status updated successfully!';
        $response_type = 'success';
    } else {
        $response = '❌ Error updating status: ' . mysqli_stmt_error($update_stmt);
        $response_type = 'danger';
    }
    mysqli_stmt_close($update_stmt);
}

// Handle message reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $message_id = intval($_POST['message_id']);
    $reply_text = sanitize_input($_POST['reply_text']);
    
    if (empty($reply_text)) {
        $response = '❌ Reply cannot be empty';
        $response_type = 'danger';
    } else {
        // Get the original message
        $get_msg_sql = "SELECT email FROM contact_messages WHERE id = ?";
        $get_msg_stmt = mysqli_prepare($conn, $get_msg_sql);
        mysqli_stmt_bind_param($get_msg_stmt, 'i', $message_id);
        mysqli_stmt_execute($get_msg_stmt);
        $msg_result = mysqli_stmt_get_result($get_msg_stmt);
        
        if ($msg_data = mysqli_fetch_assoc($msg_result)) {
            // Update with reply
            $update_reply_sql = "UPDATE contact_messages SET admin_reply = ?, admin_reply_date = NOW(), status = 'Replied' WHERE id = ?";
            $reply_stmt = mysqli_prepare($conn, $update_reply_sql);
            mysqli_stmt_bind_param($reply_stmt, 'si', $reply_text, $message_id);
            
            if (mysqli_stmt_execute($reply_stmt)) {
                $response = '✅ Reply sent successfully!';
                $response_type = 'success';
            } else {
                $response = '❌ Error sending reply: ' . mysqli_stmt_error($reply_stmt);
                $response_type = 'danger';
            }
            mysqli_stmt_close($reply_stmt);
        }
        mysqli_stmt_close($get_msg_stmt);
    }
}

// Fetch all contact messages with optional filtering
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$messages_sql = "SELECT id, name, email, phone, subject, message, status, created_at, admin_reply, admin_reply_date FROM contact_messages WHERE 1=1";

if (!empty($filter_status)) {
    $messages_sql .= " AND status = ?";
}
if (!empty($search_query)) {
    $messages_sql .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ?)";
}

$messages_sql .= " ORDER BY created_at DESC";

$messages_stmt = mysqli_prepare($conn, $messages_sql);

if (!empty($filter_status) && !empty($search_query)) {
    $search_term = "%{$search_query}%";
    mysqli_stmt_bind_param($messages_stmt, 'ssss', $filter_status, $search_term, $search_term, $search_term);
} elseif (!empty($filter_status)) {
    mysqli_stmt_bind_param($messages_stmt, 's', $filter_status);
} elseif (!empty($search_query)) {
    $search_term = "%{$search_query}%";
    mysqli_stmt_bind_param($messages_stmt, 'sss', $search_term, $search_term, $search_term);
}

mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);
$messages = [];

if ($messages_result && mysqli_num_rows($messages_result) > 0) {
    while ($row = mysqli_fetch_assoc($messages_result)) {
        $messages[] = $row;
    }
}
mysqli_stmt_close($messages_stmt);

// Get message counts by status
$status_counts = [];
$status_sql = "SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);

if ($status_result && mysqli_num_rows($status_result) > 0) {
    while ($row = mysqli_fetch_assoc($status_result)) {
        $status_counts[$row['status']] = $row['count'];
    }
}

// Get total count
$total_sql = "SELECT COUNT(*) as total FROM contact_messages";
$total_result = mysqli_query($conn, $total_sql);
$total_count = ($total_result && $row = mysqli_fetch_assoc($total_result)) ? $row['total'] : 0;

function get_status_badge($status) {
    $colors = [
        'New' => '#dc3545',
        'Read' => '#ffc107',
        'Replied' => '#28a745'
    ];
    $color = $colors[$status] ?? '#6c757d';
    return "<span style='background-color: {$color}; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px;'>$status</span>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contact Messages - Admin</title>
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
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid #8b4513;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card h4 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 5px;
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

        .search-input {
            flex: 1;
            min-width: 200px;
        }

        .search-input input {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
        }

        .search-input input:focus {
            border-color: #8b4513;
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.2);
        }

        .status-links {
            display: flex;
            gap: 10px;
        }

        .status-link {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .status-link:hover {
            background-color: #e8e8e8;
        }

        .status-link.active {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
            border-color: #8b4513;
        }

        .messages-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-responsive {
            border-radius: 10px;
        }

        .messages-table table {
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .messages-table thead {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
        }

        .messages-table th {
            padding: 15px;
            font-weight: 700;
            border: none;
            text-align: left;
        }

        .messages-table tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .messages-table tbody tr:hover {
            background-color: #f9f9f9;
            box-shadow: inset 0 0 10px rgba(139, 69, 19, 0.05);
        }

        .messages-table td {
            padding: 15px;
            vertical-align: middle;
        }

        .message-preview {
            font-size: 13px;
            color: #666;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background-color: #138496;
        }

        .btn-reply {
            background-color: #28a745;
            color: white;
        }

        .btn-reply:hover {
            background-color: #218838;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .modal-header {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
            border: none;
        }

        .modal-header .close {
            color: white;
            opacity: 0.7;
        }

        .modal-body {
            padding: 25px;
        }

        .message-detail {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #8b4513;
        }

        .message-detail h6 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .message-detail p {
            margin-bottom: 0;
            color: #333;
        }

        .reply-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .reply-form h6 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #8b4513;
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }

        .btn-send {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
            color: white;
        }

        .btn-send:focus {
            color: white;
        }

        .no-messages {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-messages i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .filter-group {
                flex-direction: column;
            }

            .search-input {
                min-width: 100%;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid admin-container">
        <div class="page-header">
            <h1><i class="fas fa-envelope"></i> Contact Messages Management</h1>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($response)): ?>
            <div class="alert alert-<?php echo $response_type; ?>" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $response; ?>
            </div>
            <script>
                setTimeout(function() {
                    document.querySelector('.alert').style.display = 'none';
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stat-card" onclick="location.href='manage_contacts.php'">
                <h4>Total Messages</h4>
                <div class="count"><?php echo $total_count; ?></div>
            </div>
            <div class="stat-card" onclick="location.href='manage_contacts.php?status=New'">
                <h4>New</h4>
                <div class="count"><?php echo $status_counts['New'] ?? 0; ?></div>
            </div>
            <div class="stat-card" onclick="location.href='manage_contacts.php?status=Read'">
                <h4>Read</h4>
                <div class="count"><?php echo $status_counts['Read'] ?? 0; ?></div>
            </div>
            <div class="stat-card" onclick="location.href='manage_contacts.php?status=Replied'">
                <h4>Replied</h4>
                <div class="count"><?php echo $status_counts['Replied'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5>Filter & Search</h5>
            <div class="filter-group">
                <div class="status-links">
                    <a href="manage_contacts.php" class="status-link <?php if (empty($filter_status)) echo 'active'; ?>">All</a>
                    <a href="manage_contacts.php?status=New" class="status-link <?php if ($filter_status === 'New') echo 'active'; ?>">New</a>
                    <a href="manage_contacts.php?status=Read" class="status-link <?php if ($filter_status === 'Read') echo 'active'; ?>">Read</a>
                    <a href="manage_contacts.php?status=Replied" class="status-link <?php if ($filter_status === 'Replied') echo 'active'; ?>">Replied</a>
                </div>
                <form method="GET" class="search-input">
                    <?php if (!empty($filter_status)): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <?php endif; ?>
                    <input type="search" name="search" placeholder="Search by name, email, or subject..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-sm" style="background: linear-gradient(135deg, #8b4513, #d2691e); color: white; border: none; padding: 10px 15px; border-radius: 5px; font-weight: 600;">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="messages-table">
            <?php if (count($messages) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 15%;">From</th>
                                <th style="width: 20%;">Email</th>
                                <th style="width: 20%;">Subject</th>
                                <th style="width: 25%;">Message</th>
                                <th style="width: 8%;">Status</th>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td>#<?php echo $msg['id']; ?></td>
                                    <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                    <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                    <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                    <td><span class="message-preview"><?php echo htmlspecialchars($msg['message']); ?></span></td>
                                    <td><?php echo get_status_badge($msg['status']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" data-toggle="modal" data-target="#viewMessageModal<?php echo $msg['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn-action btn-reply" data-toggle="modal" data-target="#replyMessageModal<?php echo $msg['id']; ?>">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                <input type="hidden" name="delete_message" value="1">
                                                <button type="submit" class="btn-action btn-delete">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- View Message Modal -->
                                <div class="modal fade" id="viewMessageModal<?php echo $msg['id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Message Details</h5>
                                                <button type="button" class="close" data-dismiss="modal" style="color: white;">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="message-detail">
                                                    <h6><i class="fas fa-user"></i> From</h6>
                                                    <p><?php echo htmlspecialchars($msg['name']); ?></p>
                                                </div>
                                                <div class="message-detail">
                                                    <h6><i class="fas fa-envelope"></i> Email</h6>
                                                    <p><?php echo htmlspecialchars($msg['email']); ?></p>
                                                </div>
                                                <div class="message-detail">
                                                    <h6><i class="fas fa-phone"></i> Phone</h6>
                                                    <p><?php echo htmlspecialchars($msg['phone'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="message-detail">
                                                    <h6><i class="fas fa-heading"></i> Subject</h6>
                                                    <p><?php echo htmlspecialchars($msg['subject']); ?></p>
                                                </div>
                                                <div class="message-detail">
                                                    <h6><i class="fas fa-comment"></i> Message</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                </div>

                                                <?php if (!empty($msg['admin_reply'])): ?>
                                                    <div class="message-detail" style="border-left-color: #28a745; background-color: #f0f8f5;">
                                                        <h6 style="color: #28a745;"><i class="fas fa-reply"></i> Admin Reply</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?></p>
                                                        <small style="color: #999;">Replied on: <?php echo date('M d, Y \a\t H:i', strtotime($msg['admin_reply_date'])); ?></small>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="message-detail">
                                                    <h6><i class="fas fa-tasks"></i> Current Status</h6>
                                                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                        <select name="status" class="form-control" style="flex: 1;">
                                                            <option value="New" <?php if ($msg['status'] === 'New') echo 'selected'; ?>>New</option>
                                                            <option value="Read" <?php if ($msg['status'] === 'Read') echo 'selected'; ?>>Read</option>
                                                            <option value="Replied" <?php if ($msg['status'] === 'Replied') echo 'selected'; ?>>Replied</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                        <button type="submit" class="btn btn-send" style="flex: 0.3;">Update</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reply Message Modal -->
                                <div class="modal fade" id="replyMessageModal<?php echo $msg['id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reply to Message</h5>
                                                <button type="button" class="close" data-dismiss="modal" style="color: white;">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="message-detail">
                                                    <h6>Customer Message</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                </div>
                                                <form method="POST" class="reply-form">
                                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                    <h6>Your Reply (to <?php echo htmlspecialchars($msg['email']); ?>)</h6>
                                                    <div class="form-group">
                                                        <textarea name="reply_text" class="form-control" rows="6" placeholder="Type your reply here..." required></textarea>
                                                    </div>
                                                    <button type="submit" name="send_reply" class="btn btn-send">
                                                        <i class="fas fa-paper-plane"></i> Send Reply
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-messages">
                    <i class="fas fa-inbox"></i>
                    <h5>No Messages Found</h5>
                    <p><?php echo !empty($search_query) ? 'Try adjusting your search criteria.' : 'No contact messages yet.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
