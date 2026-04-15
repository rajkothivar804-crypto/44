<?php 
include 'header.php';
include '../database/DB.php';

// Function to get currency symbol
function getCurrencySymbol() {
    global $conn;
    $sql = "SELECT setting_value FROM cafe_settings WHERE setting_key = 'website'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $currency = $row['setting_value'];
        if (strpos($currency, 'INR') !== false) {
            return '₹';
        } elseif (strpos($currency, 'EUR') !== false) {
            return '€';
        } elseif (strpos($currency, 'GBP') !== false) {
            return '£';
        } else {
            return '₹';
        }
    }
    return '₹'; // Default to INR
}

$alert_message = '';
$alert_type = '';

// Create orders tables if they don't exist
$create_orders_table = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Processing', 'Ready', 'Completed', 'Cancelled', 'Delivered') DEFAULT 'Pending',
    payment_method VARCHAR(50),
    delivery_address TEXT,
    special_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$create_order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

mysqli_query($conn, $create_orders_table);
mysqli_query($conn, $create_order_items_table);

// Handle Order Status Update (both GET and POST)
if ((isset($_GET['update_status']) && isset($_GET['order_id'])) || (isset($_POST['update_status']) && isset($_POST['order_id']))) {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : intval($_GET['order_id']);
    $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : (isset($_GET['new_status']) ? $_GET['new_status'] : '');
    
    $valid_statuses = ['Pending', 'Processing', 'Ready', 'Completed', 'Cancelled', 'Delivered'];
    
    if (in_array($new_status, $valid_statuses)) {
        $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, 'si', $new_status, $order_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $alert_message = "Order status updated to " . $new_status . "!";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating order status!";
            $alert_type = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Order Deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $delete_sql = "DELETE FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, 'i', $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $alert_message = "Order deleted successfully!";
        $alert_type = "success";
    } else {
        $alert_message = "Error deleting order!";
        $alert_type = "danger";
    }
    mysqli_stmt_close($stmt);
}

// Get order statistics
$stats_sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$stats_result = mysqli_query($conn, $stats_sql);
$order_stats = [];
while ($row = mysqli_fetch_assoc($stats_result)) {
    $order_stats[$row['status']] = $row['count'];
}
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT id, user_id, customer_name, customer_email, customer_phone, total_amount, status, created_at FROM orders";
$conditions = array();

if (!empty($filter_status)) {
    $conditions[] = "status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

if (!empty($search_query)) {
    $conditions[] = "(customer_name LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%' OR customer_email LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%' OR id LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%')";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);
$orders = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}

// Get order items
function getOrderItems($order_id, $conn) {
    $sql = "SELECT oi.id, p.product_name as item_name, oi.quantity, oi.price, oi.subtotal FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $items = array();
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
    return $items;
}

// Get badge color for status
function getStatusBadge($status) {
    $colors = [
        'Pending' => 'badge-warning',
        'Processing' => 'badge-info',
        'Ready' => 'badge-primary',
        'Completed' => 'badge-success',
        'Cancelled' => 'badge-danger'
    ];
    return isset($colors[$status]) ? $colors[$status] : 'badge-secondary';
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

            <!-- Page Title -->
            <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="color: #5c4033; font-weight: 700; margin-bottom: 5px;">Orders Management</h1>
                    <p style="color: #999;">Total Orders: <strong><?php echo count($orders); ?></strong></p>
                </div>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 8px; min-width: 80px;">
                        <div style="font-size: 18px; font-weight: 700; color: #856404;">⏳</div>
                        <div style="font-size: 12px; color: #856404;">Pending</div>
                        <div style="font-size: 16px; font-weight: 700; color: #856404;"><?php echo $order_stats['Pending'] ?? 0; ?></div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #d1ecf1; border-radius: 8px; min-width: 80px;">
                        <div style="font-size: 18px; font-weight: 700; color: #0c5460;">🔄</div>
                        <div style="font-size: 12px; color: #0c5460;">Processing</div>
                        <div style="font-size: 16px; font-weight: 700; color: #0c5460;"><?php echo $order_stats['Processing'] ?? 0; ?></div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #d4edda; border-radius: 8px; min-width: 80px;">
                        <div style="font-size: 18px; font-weight: 700; color: #155724;">✅</div>
                        <div style="font-size: 12px; color: #155724;">Ready</div>
                        <div style="font-size: 16px; font-weight: 700; color: #155724;"><?php echo $order_stats['Ready'] ?? 0; ?></div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #d4edda; border-radius: 8px; min-width: 80px;">
                        <div style="font-size: 18px; font-weight: 700; color: #155724;">🎉</div>
                        <div style="font-size: 12px; color: #155724;">Completed</div>
                        <div style="font-size: 16px; font-weight: 700; color: #155724;"><?php echo $order_stats['Completed'] ?? 0; ?></div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #f8d7da; border-radius: 8px; min-width: 80px;">
                        <div style="font-size: 18px; font-weight: 700; color: #721c24;">❌</div>
                        <div style="font-size: 12px; color: #721c24;">Cancelled</div>
                        <div style="font-size: 16px; font-weight: 700; color: #721c24;"><?php echo $order_stats['Cancelled'] ?? 0; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; color: #5c4033; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-search"></i> Search Order
                        </label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by customer name, email, or order ID..." style="width: 100%; border: 2px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="display: block; color: #5c4033; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-filter"></i> Filter by Status
                        </label>
                        <select name="status" style="border: 2px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $filter_status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Ready" <?php echo $filter_status === 'Ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="Completed" <?php echo $filter_status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $filter_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" style="background: linear-gradient(135deg, #8b4513, #d2691e); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="orders.php" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Total Amount</th>
                            <th>Status <small style="color: #999; font-weight: normal;">(Click to edit)</small></th>
                            <th>Order Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#ORD-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><strong><?php echo getCurrencySymbol(); ?><?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <form method="POST" style="display: inline; margin: 0;">
                                            <select name="new_status" onchange="if(confirm('Update order status to ' + this.value + '?')) this.form.submit()" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; font-size: 12px; background: white; min-width: 100px;">
                                                <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                                <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>🔄 Processing</option>
                                                <option value="Ready" <?php echo $order['status'] === 'Ready' ? 'selected' : ''; ?>>✅ Ready</option>
                                                <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>🎉 Completed</option>
                                                <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>🚚 Delivered</option>
                                                <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                            </select>
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="viewOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>, <?php echo json_encode(getOrderItems($order['id'], $conn)); ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="?delete=1&id=<?php echo $order['id']; ?>" class="btn-admin btn-admin-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Are you sure?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    No orders found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 30px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 22px;">
                    <i class="fas fa-receipt"></i> Order Details - <span id="modalOrderID"></span>
                </h5>
                <button type="button" class="close" style="color: white; opacity: 0.8;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 40px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                    <!-- Left Column -->
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 10px;">
                        <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                            <i class="fas fa-user"></i> Customer Information
                        </h5>
                        
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase;">Name</strong>
                            <p id="modalCustomerName" style="margin: 5px 0; color: #333;"></p>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase;">Email</strong>
                            <p id="modalCustomerEmail" style="margin: 5px 0; color: #333;"></p>
                        </div>

                        <div>
                            <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase;">Phone</strong>
                            <p id="modalCustomerPhone" style="margin: 5px 0; color: #333;"></p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 10px;">
                        <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Order Information
                        </h5>
                        
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase;">Order Date</strong>
                            <p id="modalOrderDate" style="margin: 5px 0; color: #333;"></p>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase;">Status</strong>
                            <p id="modalOrderStatus" style="margin: 5px 0; color: #333;"></p>
                        </div>

                        <div>
                            <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase;">Total Amount</strong>
                            <p id="modalOrderAmount" style="margin: 5px 0; color: #333; font-size: 18px; font-weight: 700; color: #8b4513;"></p>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div style="background: linear-gradient(135deg, #f5f5dc20 0%, #fffacd40 100%); padding: 20px; border-radius: 10px; border: 2px solid #8b451140; margin-bottom: 20px;">
                    <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                        <i class="fas fa-list"></i> Order Items
                    </h5>
                    <div id="modalOrderItems" style="max-height: 300px; overflow-y: auto;">
                        <!-- Items will be populated here -->
                    </div>
                </div>

                <!-- Status Update -->
                <div style="background: #f9f9f9; padding: 20px; border-radius: 10px;">
                    <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                        <i class="fas fa-sync-alt"></i> Update Order Status
                    </h5>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;" id="statusButtons">
                        <!-- Status buttons will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(order, items) {
    // Format the date
    const date = new Date(order.created_at);
    const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    
    // Populate modal
    document.getElementById('modalOrderID').textContent = '#ORD-' + String(order.id).padStart(3, '0');
    document.getElementById('modalCustomerName').textContent = order.customer_name;
    document.getElementById('modalCustomerEmail').textContent = order.customer_email || 'N/A';
    document.getElementById('modalCustomerPhone').textContent = order.customer_phone || 'N/A';
    document.getElementById('modalOrderDate').textContent = formattedDate;
    document.getElementById('modalOrderStatus').innerHTML = '<span class="badge-' + getStatusColor(order.status) + '">' + order.status + '</span>';
    document.getElementById('modalOrderAmount').textContent = '<?php echo getCurrencySymbol(); ?>' + parseFloat(order.total_amount).toFixed(2);
    
    // Populate items
    let itemsHTML = '';
    if (items.length > 0) {
        items.forEach(item => {
            itemsHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #ddd;">
                    <div>
                        <strong>${item.item_name}</strong>
                        <p style="margin: 5px 0; color: #999; font-size: 12px;">Qty: ${item.quantity}</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="margin: 0; color: #333; font-weight: 600;">$${parseFloat(item.subtotal).toFixed(2)}</p>
                        <small style="color: #999;">@$${parseFloat(item.price).toFixed(2)}</small>
                    </div>
                </div>
            `;
        });
    } else {
        itemsHTML = '<p style="color: #999; text-align: center; padding: 20px;">No items in this order</p>';
    }
    document.getElementById('modalOrderItems').innerHTML = itemsHTML;
    
    // Populate status buttons
    const statuses = [
        {value: 'Pending', label: ' Pending'},
        {value: 'Processing', label: ' Processing'},
        {value: 'Ready', label: ' Ready'},
        {value: 'Completed', label: ' Completed'},
        {value: 'Cancelled', label: ' Cancelled'}
    ];
    let buttonsHTML = '';
    statuses.forEach(status => {
        const isActive = status.value === order.status;
        buttonsHTML += `
            <a href="?update_status=1&order_id=${order.id}&new_status=${status.value}" 
               style="background: ${isActive ? '#8b4513' : '#ddd'}; color: ${isActive ? 'white' : '#333'}; 
               padding: 8px 16px; border-radius: 5px; text-decoration: none; font-weight: 600; font-size: 12px; margin: 2px;"
               onclick="return confirm('Update status to ${status.label}?');">
                ${status.label}
            </a>
        `;
    });
    document.getElementById('statusButtons').innerHTML = buttonsHTML;
    
    // Show modal
    $('#orderDetailsModal').modal('show');
}

function getStatusColor(status) {
    const colors = {
        'Pending': 'warning',
        'Processing': 'info',
        'Ready': 'primary',
        'Completed': 'success',
        'Cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}
</script>

<?php include 'footer.php'; ?>
