<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Cafe4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../css/form-validation.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #8b4513;
            --secondary-color: #d2691e;
            --dark-color: #5c4033;
            --light-color: #f5f5dc;
            --gray-light: #f9f9f9;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-light);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 270px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--dark-color) 100%);
            padding: 20px 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Sidebar Brand */
        .sidebar-brand {
            padding: 20px 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .sidebar-brand i {
            font-size: 28px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 8px;
        }

        .sidebar-brand h3 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 1px;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
            padding-left: 24px;
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            min-height: 100vh;
            background-color: var(--gray-light);
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .top-bar-left h2 {
            color: var(--dark-color);
            font-weight: 700;
            margin: 0;
            font-size: 24px;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: var(--gray-light);
            border-radius: 25px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .admin-name {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .admin-name small {
            color: #999;
            font-size: 12px;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 20px;
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        /* Page Container */
        .page-container {
            padding: 30px;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-card.blue {
            border-top-color: #007bff;
        }

        .stat-card.green {
            border-top-color: #28a745;
        }

        .stat-card.orange {
            border-top-color: var(--secondary-color);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 15px;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #999;
            font-size: 14px;
            font-weight: 500;
        }

        /* Buttons */
        .btn-admin {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-admin-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-admin-primary:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }

        .btn-admin-secondary {
            background: var(--gray-light);
            color: var(--dark-color);
            border: 2px solid var(--primary-color);
        }

        .btn-admin-secondary:hover {
            background: var(--primary-color);
            color: white;
            text-decoration: none;
        }

        .btn-admin-danger {
            background: #dc3545;
            color: white;
        }

        .btn-admin-danger:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, rgba(139, 69, 19, 0.1), rgba(210, 105, 30, 0.1));
            color: var(--dark-color);
            font-weight: 700;
            border: none;
            padding: 15px;
        }

        .table tbody td {
            padding: 12px 15px;
            border-color: #f0f0f0;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: var(--gray-light);
        }

        /* Badge */
        .badge-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
                transition: all 0.3s ease;
            }

            .sidebar.active {
                width: 270px;
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .page-container {
                padding: 20px;
            }

            .top-bar {
                padding: 15px 20px;
            }

            .top-bar-right {
                gap: 10px;
            }

            .admin-profile {
                padding: 6px 10px;
            }

            .admin-name {
                display: none;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .stat-card {
            animation: slideIn 0.5s ease;
        }

        /* Notification Dropdown */
        .notification-dropdown {
            position: absolute;
            top: 70px;
            right: 30px;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            display: none;
            max-height: 500px;
            overflow-y: auto;
            animation: slideDown 0.3s ease;
        }

        .notification-dropdown.active {
            display: block;
        }

        .notification-header {
            padding: 15px 20px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h5 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 700;
        }

        .close-notif {
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            background: var(--gray-light);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .notification-message {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .notification-detail {
            font-size: 13px;
            color: #666;
        }

        .no-notifications {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .no-notifications i {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-mug-hot"></i>
            <h3>Cafe4</h3>
        </a>

        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" id="dashboardLink">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="products.php" id="productsLink">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="orders.php" id="ordersLink">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li>
                <a href="users.php" id="usersLink">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="manage_contacts.php" id="contactsLink">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                </a>
            </li>
            <li>
                <a href="manage_discounts.php" id="discountsLink">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Discount Codes</span>
                </a>
            </li>
            <li>
                <a href="manage_about_us.php" id="aboutLink">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </li>
            <li>
                <a href="manage_ratings.php" id="ratingsLink">
                    <i class="fas fa-star"></i>
                    <span>Ratings</span>
                </a>
            </li>
            <li>
                <a href="manage_reviews.php" id="reviewsLink">
                    <i class="fas fa-comments"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li>
                <a href="manage_product_reviews.php" id="productReviewsLink">
                    <i class="fas fa-star-half-alt"></i>
                    <span>Product Reviews</span>
                </a>
            </li>
            <li>
                <a href="settings.php" id="settingsLink">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li style="border-top: 1px solid rgba(255, 255, 255, 0.2); margin-top: 20px; padding-top: 15px;">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button class="btn btn-link" id="sidebarToggle" style="display: none; color: var(--primary-color);">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h2 id="pageTitle">Dashboard</h2>
            </div>

            <div class="top-bar-right">
                <div class="notification-bell" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notifBadge">3</span>
                </div>

                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin'], 0, 1)); ?>
                    </div>
                    <div class="admin-name">
                        <strong><?php echo htmlspecialchars($_SESSION['admin']); ?></strong>
                        <small>Administrator</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Dropdown -->
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
                <h5>Notifications</h5>
                <button class="close-notif" onclick="toggleNotifications()">&times;</button>
            </div>
            <div class="notification-list" id="notificationList">
                <div style="text-align: center; padding: 20px; color: #999;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>

        <script>
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('active');
            
            if (dropdown.classList.contains('active')) {
                loadNotifications();
            }
        }

        function loadNotifications() {
            const notificationList = document.getElementById('notificationList');
            
            // Fetch notifications from PHP
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.notifications && data.notifications.length > 0) {
                        notificationList.innerHTML = '';
                        data.notifications.forEach(notif => {
                            const notifItem = document.createElement('div');
                            notifItem.className = 'notification-item';
                            notifItem.innerHTML = `
                                <div class="notification-message"><i class="${notif.icon}"></i> ${notif.title}</div>
                                <div class="notification-detail">${notif.message}</div>
                                <div class="notification-time">${notif.time}</div>
                            `;
                            notificationList.appendChild(notifItem);
                        });
                        
                        // Update badge
                        document.getElementById('notifBadge').textContent = data.count;
                    } else {
                        notificationList.innerHTML = `
                            <div class="no-notifications">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    notificationList.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">Error loading notifications</div>';
                });
        }

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notificationDropdown');
            const bell = document.querySelector('.notification-bell');
            
            if (!dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
        </script>

        <!-- Page Content -->
        <div class="page-container">
