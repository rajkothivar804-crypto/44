<?php
session_start();
error_reporting(0);
// Check if user is logged in and is admin

include '../database/DB.php';
include 'header.php';

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$response = '';
$response_type = '';

// Create discount codes table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS discount_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('Percentage', 'Fixed') DEFAULT 'Percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    max_uses INT NULL,
    current_uses INT DEFAULT 0,
    min_order_amount DECIMAL(10, 2) DEFAULT 0,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

// Handle add discount code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_discount'])) {
    $code = sanitize_input($_POST['code'] ?? '');
    $discount_type = sanitize_input($_POST['discount_type'] ?? 'Percentage');
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : NULL;
    $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    
    if (empty($code)) {
        $response = '❌ Discount code cannot be empty';
        $response_type = 'danger';
    } elseif ($discount_value <= 0) {
        $response = '❌ Discount value must be greater than 0';
        $response_type = 'danger';
    } else {
        $insert_sql = "INSERT INTO discount_codes (code, discount_type, discount_value, max_uses, min_order_amount, end_date) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, 'ssdids', $code, $discount_type, $discount_value, $max_uses, $min_order_amount, $end_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $response = '✅ Discount code added successfully!';
            $response_type = 'success';
        } else {
            $response = '❌ Error adding discount: ' . mysqli_stmt_error($stmt);
            $response_type = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle delete discount code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_discount'])) {
    $discount_id = intval($_POST['discount_id']);
    
    $delete_sql = "DELETE FROM discount_codes WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, 'i', $discount_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $response = '✅ Discount code deleted successfully!';
        $response_type = 'success';
    } else {
        $response = '❌ Error deleting discount: ' . mysqli_stmt_error($delete_stmt);
        $response_type = 'danger';
    }
    mysqli_stmt_close($delete_stmt);
}

// Handle toggle discount status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $discount_id = intval($_POST['discount_id']);
    
    $toggle_sql = "UPDATE discount_codes SET is_active = !is_active WHERE id = ?";
    $toggle_stmt = mysqli_prepare($conn, $toggle_sql);
    mysqli_stmt_bind_param($toggle_stmt, 'i', $discount_id);
    
    if (mysqli_stmt_execute($toggle_stmt)) {
        $response = '✅ Discount status updated!';
        $response_type = 'success';
    } else {
        $response = '❌ Error updating discount: ' . mysqli_stmt_error($toggle_stmt);
        $response_type = 'danger';
    }
    mysqli_stmt_close($toggle_stmt);
}

// Fetch all discount codes
$discounts_sql = "SELECT * FROM discount_codes ORDER BY created_at DESC";
$discounts_result = mysqli_query($conn, $discounts_sql);
$discounts = [];

if ($discounts_result && mysqli_num_rows($discounts_result) > 0) {
    while ($row = mysqli_fetch_assoc($discounts_result)) {
        $discounts[] = $row;
    }
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_codes,
    SUM(current_uses) as total_uses,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_codes
FROM discount_codes";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Discount Codes - Admin</title>
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
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #8b4513;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card h4 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-card .count {
            font-size: 32px;
            font-weight: 800;
            color: #d2691e;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .form-section h5 {
            color: #8b4513;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

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

        .form-group label {
            font-weight: 600;
            color: #5c4033;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }

        .form-control:focus {
            border-color: #8b4513;
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.2);
        }

        .btn-submit {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
            color: white;
        }

        .discounts-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .table thead {
            background: linear-gradient(135deg, #8b4513, #d2691e);
            color: white;
        }

        .table th {
            padding: 15px;
            font-weight: 700;
            border: none;
            text-align: left;
        }

        .table tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
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
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-toggle {
            background-color: #17a2b8;
            color: white;
        }

        .btn-toggle:hover {
            background-color: #138496;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .no-discounts {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-discounts i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
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
            <h1><i class="fas fa-ticket-alt"></i> Discount Codes Management</h1>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($response)): ?>
            <div class="alert alert-<?php echo $response_type; ?>" role="alert">
                <?php echo $response; ?>
            </div>
            <script>
                setTimeout(function() {
                    document.querySelector('.alert').style.display = 'none';
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <h4><i class="fas fa-tag"></i> Total Codes</h4>
                <div class="count"><?php echo $stats['total_codes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-check-circle"></i> Active Codes</h4>
                <div class="count"><?php echo $stats['active_codes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-chart-bar"></i> Total Uses</h4>
                <div class="count"><?php echo $stats['total_uses'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Add New Discount Form -->
        <div class="form-section">
            <h5><i class="fas fa-plus-circle"></i> Create New Discount Code</h5>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Discount Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., SAVE10, WELCOME20">
                        <div class="error-message" id="addCodeError"></div>
                    </div>
                    <div class="form-group">
                        <label>Discount Type *</label>
                        <select name="discount_type" class="form-control">
                            <option value="Percentage">Percentage (%)</option>
                            <option value="Fixed">Fixed Amount (₹)</option>
                        </select>
                        <div class="error-message" id="addTypeError"></div>
                    </div>
                    <div class="form-group">
                        <label>Discount Value *</label>
                        <input type="number" name="discount_value" class="form-control" placeholder="10" step="0.01" min="0">
                        <div class="error-message" id="addValueError"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Min Order Amount (₹)</label>
                        <input type="number" name="min_order_amount" class="form-control" placeholder="0" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Maximum Uses</label>
                        <input type="number" name="max_uses" class="form-control" placeholder="Leave empty for unlimited" min="1">
                    </div>
                    <div class="form-group">
                        <label>Expiration Date</label>
                        <input type="datetime-local" name="end_date" class="form-control">
                    </div>
                </div>

                <button type="submit" name="add_discount" class="btn-submit">
                    <i class="fas fa-plus"></i> Create Discount Code
                </button>
            </form>
        </div>

        <!-- Discount Codes Table -->
        <div class="discounts-table">
            <?php if (count($discounts) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Code</th>
                                <th style="width: 12%;">Type</th>
                                <th style="width: 12%;">Value</th>
                                <th style="width: 10%;">Uses</th>
                                <th style="width: 12%;">Min Order</th>
                                <th style="width: 12%;">Expires</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 17%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discounts as $discount): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($discount['code']); ?></strong></td>
                                    <td><?php echo $discount['discount_type'] === 'Percentage' ? '%' : '₹'; ?></td>
                                    <td><?php echo number_format($discount['discount_value'], 2); ?></td>
                                    <td>
                                        <?php 
                                            if ($discount['max_uses'] !== null) {
                                                echo $discount['current_uses'] . '/' . $discount['max_uses'];
                                            } else {
                                                echo $discount['current_uses'] . '/∞';
                                            }
                                        ?>
                                    </td>
                                    <td>₹<?php echo number_format($discount['min_order_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                            if ($discount['end_date']) {
                                                echo date('M d, Y', strtotime($discount['end_date']));
                                            } else {
                                                echo '<span style="color: #999;">No limit</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($discount['is_active']) {
                                                echo '<span class="badge badge-success">Active</span>';
                                            } else {
                                                echo '<span class="badge badge-danger">Inactive</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <button type="submit" class="btn-action btn-toggle">
                                                    <i class="fas fa-<?php echo $discount['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this coupon?');">
                                                <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
                                                <input type="hidden" name="delete_discount" value="1">
                                                <button type="submit" class="btn-action btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-discounts">
                    <i class="fas fa-ticket-alt"></i>
                    <h5>No Discount Codes Yet</h5>
                    <p>Create your first discount code above to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Add Discount Form Validation
        $('form[method="POST"]').first().on('submit', function(e) {
            // Only validate the add discount form (first form on page)
            if (!$(this).find('input[name="add_discount"]').length) return;
            
            clearAddErrors();
            let isValid = true;
            
            const code = $('input[name="code"]').val().trim();
            const discountType = $('select[name="discount_type"]').val();
            const discountValue = parseFloat($('input[name="discount_value"]').val());
            
            // Code validation
            if (code === '') {
                showAddError('addCodeError', 'Discount code is required.');
                $('input[name="code"]').addClass('is-invalid');
                isValid = false;
            } else if (code.length < 3) {
                showAddError('addCodeError', 'Discount code must be at least 3 characters.');
                $('input[name="code"]').addClass('is-invalid');
                isValid = false;
            }
            
            // Discount type validation
            if (!discountType) {
                showAddError('addTypeError', 'Discount type is required.');
                $('select[name="discount_type"]').addClass('is-invalid');
                isValid = false;
            }
            
            // Discount value validation
            if (isNaN(discountValue) || discountValue <= 0) {
                showAddError('addValueError', 'Discount value must be greater than 0.');
                $('input[name="discount_value"]').addClass('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Real-time validation - clear errors when user starts typing
        $('.form-section input, .form-section select').on('input change', function() {
            const fieldName = $(this).attr('name');
            let errorId = '';
            
            if (fieldName === 'code') errorId = 'addCodeError';
            else if (fieldName === 'discount_type') errorId = 'addTypeError';
            else if (fieldName === 'discount_value') errorId = 'addValueError';
            
            if (errorId) {
                $('#' + errorId).hide();
                $(this).removeClass('is-invalid');
            }
        });
        
        function showAddError(errorId, message) {
            $('#' + errorId).text(message).show();
        }
        
        function clearAddErrors() {
            $('.error-message[id^="add"]').hide();
            $('.form-section .form-control').removeClass('is-invalid');
        }
    });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
