<?php 
include 'header.php';
include '../database/DB.php';

// Get Total Orders Count
$total_orders_sql = "SELECT COUNT(*) as count FROM orders";
$total_orders_result = mysqli_query($conn, $total_orders_sql);
$total_orders = 0;
if ($total_orders_result && mysqli_num_rows($total_orders_result) > 0) {
    $row = mysqli_fetch_assoc($total_orders_result);
    $total_orders = intval($row['count']);
}

// Get Total Revenue
$total_revenue_sql = "SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'Cancelled'";
$total_revenue_result = mysqli_query($conn, $total_revenue_sql);
$total_revenue = 0;
if ($total_revenue_result && mysqli_num_rows($total_revenue_result) > 0) {
    $row = mysqli_fetch_assoc($total_revenue_result);
    $total_revenue = floatval($row['revenue']) ?? 0;
}

// Get Total Customers/Users
$total_customers_sql = "SELECT COUNT(*) as count FROM users";
$total_customers_result = mysqli_query($conn, $total_customers_sql);
$total_customers = 0;
if ($total_customers_result && mysqli_num_rows($total_customers_result) > 0) {
    $row = mysqli_fetch_assoc($total_customers_result);
    $total_customers = intval($row['count']);
}

// Get Total Products
$total_products_sql = "SELECT COUNT(*) as count FROM products";
$total_products_result = mysqli_query($conn, $total_products_sql);
$total_products = 0;
if ($total_products_result && mysqli_num_rows($total_products_result) > 0) {
    $row = mysqli_fetch_assoc($total_products_result);
    $total_products = intval($row['count']);
}

// Get Recent Orders
$recent_orders_sql = "SELECT id, customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_sql);
$recent_orders = array();
if ($recent_orders_result && mysqli_num_rows($recent_orders_result) > 0) {
    while ($row = mysqli_fetch_assoc($recent_orders_result)) {
        $recent_orders[] = $row;
    }
}

// Get Top Products (most ordered)
$top_products_sql = "SELECT p.product_name, COUNT(oi.id) as sold_count 
                     FROM products p 
                     LEFT JOIN order_items oi ON p.id = oi.product_id 
                     GROUP BY p.id 
                     ORDER BY sold_count DESC LIMIT 4";
$top_products_result = mysqli_query($conn, $top_products_sql);
$top_products = array();
if ($top_products_result && mysqli_num_rows($top_products_result) > 0) {
    while ($row = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = $row;
    }
}

// If no top products from orders, show products by stock
if (count($top_products) == 0) {
    $top_products_sql = "SELECT product_name, stock as sold_count FROM products ORDER BY stock DESC LIMIT 4";
    $top_products_result = mysqli_query($conn, $top_products_sql);
    if ($top_products_result && mysqli_num_rows($top_products_result) > 0) {
        while ($row = mysqli_fetch_assoc($top_products_result)) {
            $top_products[] = $row;
        }
    }
}

// Get badge color based on status
function getStatusBadgeColor($status) {
    switch($status) {
        case 'Completed':
            return 'badge-success';
        case 'Processing':
        case 'Ready':
            return 'badge-warning';
        case 'Pending':
            return 'badge-info';
        case 'Cancelled':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Calculate max sold for bar width
$max_sold = 1;
if (count($top_products) > 0) {
    $max_sold = max(array_column($top_products, 'sold_count'));
}
?>


            <!-- Page Title -->
            <div style="margin-bottom: 30px;">
                <h1 style="color: #5c4033; font-weight: 700; margin-bottom: 10px;">Dashboard</h1>
                <p style="color: #999;">Welcome back! Here's what's happening with your cafe today.</p>
            </div>

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="stat-card blue">
                        <div class="stat-icon blue">
                            <i class="fas fa-indian-rupee-sign"></i>
                        </div>
                        <div class="stat-number">₹<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="stat-card green">
                        <div class="stat-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_customers); ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_products); ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-chart-bar" style="color: #8b4513; margin-right: 10px;"></i>
                            Recent Orders
                        </h4>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_orders) > 0): ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong>#ORD-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><span class="<?php echo getStatusBadgeColor($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #999; padding: 20px;">No orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-fire" style="color: #8b4513; margin-right: 10px;"></i>
                            Top Products
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php if (count($top_products) > 0): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <div style="padding: 15px; background: var(--gray-light); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <strong style="color: #5c4033;"><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                            <span style="color: #8b4513; font-weight: 600;"><?php echo intval($product['sold_count']); ?> sold</span>
                                        </div>
                                        <div style="width: 100%; height: 6px; background: #ddd; border-radius: 3px; overflow: hidden;">
                                            <div style="width: <?php echo ($max_sold > 0) ? (intval($product['sold_count']) / $max_sold * 100) : 0; ?>%; height: 100%; background: linear-gradient(90deg, #8b4513, #d2691e);"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 15px; background: var(--gray-light); border-radius: 8px; text-align: center; color: #999;">
                                    No products available
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="table-container">
                        <h4 style="color: #5c4033; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-bolt" style="color: #8b4513; margin-right: 10px;"></i>
                            Quick Actions
                        </h4>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <a href="products.php" class="btn-admin btn-admin-primary">
                                <i class="fas fa-plus"></i> Add New Product
                            </a>
                            <a href="orders.php" class="btn-admin btn-admin-primary">
                                <i class="fas fa-eye"></i> View All Orders
                            </a>
                            <a href="users.php" class="btn-admin btn-admin-primary">
                                <i class="fas fa-user-plus"></i> Manage Users
                            </a>
                            <a href="settings.php" class="btn-admin btn-admin-secondary">
                                <i class="fas fa-wrench"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>

<?php include 'footer.php'; ?>
