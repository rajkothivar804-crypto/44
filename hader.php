<?php 
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'database/DB.php';

// Check if user is logged in (matches login.php session variables)
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$isGuest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;
$userName = $isLoggedIn ? $_SESSION['user_name'] : ($isGuest ? 'Guest' : '');
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : '';
$userId = $isLoggedIn ? $_SESSION['user_id'] : '';

// Fetch profile picture if user is logged in
$profilePicture = '';
if ($isLoggedIn && $userId) {
    $sql = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 1) {
        $userData = mysqli_fetch_assoc($result);
        $profilePicture = $userData['profile_picture'];
    }
    mysqli_stmt_close($stmt);
}
?>

<?php if (!headers_sent()): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Management</title>
     <link rel="icon" type="image/x-icon" href="logo.ico"> 
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Form Validation CSS -->
    <link rel="stylesheet" href="css/form-validation.css">
    <style>
        /* Cafe Management Navbar Styling */


        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        /* Main Navbar Styling */
        .navbar {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 50%, #f5f5dc 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 15px 0;
           
            position: fixed !important;
            top: 0;
            width: 100%;
            z-index: 1000;
            left: 0;
            right: 0;
            /* margin: 0; */
        }

        /* Navbar Brand */
        .navbar-brand {
            font-size: 28px !important;
            font-weight: 700 !important;
            color: #5c4033 !important;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-right: 30px;
            position: relative;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand img {
            height: 45px;
            width: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }

        .navbar-brand::before {
            content: '';
            display: none;
        }

        .navbar-brand:hover {
            color: #8b4513 !important;
            text-shadow: 0 0 10px rgba(139, 69, 19, 0.3);
            transform: scale(1.05);
        }

        /* Navigation Links */
        .navbar-nav .nav-link {
            color: #5c4033 !important;
            margin: 0 5px;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            padding: 8px 15px !important;
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 0;
            left: 50%;
            background: linear-gradient(90deg, #8b4513, #d2691e);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover {
            color: #8b4513 !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link:hover::after {
            width: 80%;
            box-shadow: 0 2px 8px rgba(139, 69, 19, 0.4);
        }

        .navbar-nav .nav-link.active {
            color: #8b4513 !important;
            background: rgba(139, 69, 19, 0.1);
            border-radius: 5px;
        }

        /* Login/Registration Button Styling */
        .nav-link.auth-btn {
            background-color: #ffffff !important;
            border: 2px solid #000000 !important;
            color: #000000 !important;
            border-radius: 25px !important;
            padding: 8px 20px !important;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .nav-link.auth-btn:hover {
            background-color: #000000 !important;
            border-color: #ffffff !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .nav-link.auth-btn::after {
            display: none;
        }

        .navbar-nav .nav-link.active::after {
            width: 80%;
        }

        /* Mobile Toggle Button */
        .navbar-toggler {
            border: none;
            padding: 5px 10px;
            background: rgba(139, 69, 19, 0.1) !important;
            border-radius: 5px;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(139, 69, 19, 0.25);
        }

        .navbar-toggler-icon {
            background-color: #8b4513;
        }

        /* Right-aligned items */
        .navbar-nav.ml-auto {
            gap: 5px;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .navbar-brand {
                font-size: 24px !important;
                margin-right: 0;
            }

            .navbar-nav .nav-link {
                padding: 10px 0 !important;
                margin: 10px 0;
            }

            .navbar-nav .nav-link::after {
                display: none;
            }

            .navbar-collapse {
                background: rgba(245, 245, 220, 0.95);
                padding: 20px;
                border-radius: 10px;
                margin-top: 10px;
            }
        }

        /* Animation */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar {
            animation: fadeInDown 0.5s ease;
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
            border: 2px solid #8b4513;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .dropdown-menu .dropdown-item {
            color: #5c4033;
            transition: all 0.3s ease;
            padding: 10px 20px;
        }

        .dropdown-menu .dropdown-item i {
            margin-right: 10px;
            width: 18px;
            text-align: center;
            color: #8b4513;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: rgba(139, 69, 19, 0.1);
            color: #8b4513;
            border-radius: 5px;
        }

        .dropdown-menu .dropdown-item.text-danger {
            color: #dc3545 !important;
        }

        .dropdown-menu .dropdown-item.text-danger i {
            color: #dc3545;
        }

        .dropdown-menu .dropdown-item.text-danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: #c82333 !important;
        }

        .dropdown-menu .dropdown-divider {
            border-color: #8b4513;
        }

        .dropdown-toggle::after {
            border-color: #8b4513;
        }

        .navbar-nav .nav-link i {
            margin-right: 5px;
        }

        /* Profile Dropdown Styling */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-toggle {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-dropdown-toggle:hover {
            background: rgba(139, 69, 19, 0.1);
            transform: scale(1.1);
        }

        .profile-picture-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #8b4513;
            transition: all 0.3s ease;
        }

        .profile-picture-icon:hover {
            border-color: #d2691e;
            box-shadow: 0 0 10px rgba(139, 69, 19, 0.3);
        }

        .profile-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
            border: 2px solid #8b4513;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            min-width: 200px;
            z-index: 1000;
            animation: fadeInDown 0.3s ease;
        }

        .profile-dropdown-menu.show {
            display: block;
        }

        .profile-dropdown-menu .dropdown-item {
            color: #5c4033;
            transition: all 0.3s ease;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(139, 69, 19, 0.1);
        }

        .profile-dropdown-menu .dropdown-item:last-child {
            border-bottom: none;
        }

        .profile-dropdown-menu .dropdown-item i {
            margin-right: 10px;
            width: 18px;
            text-align: center;
            color: #8b4513;
        }

        .profile-dropdown-menu .dropdown-item:hover {
            background-color: rgba(139, 69, 19, 0.1);
            color: #8b4513;
            border-radius: 5px;
        }

        .profile-dropdown-menu .dropdown-item.text-danger {
            color: #dc3545 !important;
        }

        .profile-dropdown-menu .dropdown-item.text-danger i {
            color: #dc3545;
        }

        .profile-dropdown-menu .dropdown-item.text-danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: #c82333 !important;
        }

        .profile-user-info {
            padding: 15px 20px;
            border-bottom: 2px solid #8b4513;
            background: rgba(139, 69, 19, 0.05);
            border-radius: 8px 8px 0 0;
        }

        .profile-user-info .user-name {
            font-weight: 600;
            color: #5c4033;
            margin-bottom: 2px;
        }

        .profile-user-info .user-email {
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <a class="navbar-brand" href="admin/dashboard.php">
    <img src="logo.jpg" alt="Cafe Logo">
    Cafe Management
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ml-auto">
      <?php if ($isLoggedIn || $isGuest): ?>
          <li class="nav-item active">
        <a class="nav-link" href="home.php">Home</a>
      </li>
        <li class="nav-item">
          <a class="nav-link" href="menu.php">Menu</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="order.php">Orders</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="wishlist.php">Wishlist</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="payment.php">Payment</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="product_review.php">Reviews</a>
        </li>
       
        <!-- Profile Dropdown -->
        <li class="nav-item profile-dropdown">
          <button class="profile-dropdown-toggle" onclick="toggleProfileDropdown()">
            <?php if (!empty($profilePicture) && file_exists($profilePicture)): ?>
              <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile" class="profile-picture-icon">
            <?php else: ?>
              <i class="fas fa-user-circle profile-picture-icon" style="font-size: 40px; color: #8b4513;"></i>
            <?php endif; ?>
          </button>
          <div class="profile-dropdown-menu" id="profileDropdown">
            <div class="profile-user-info">
              <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
              <div class="user-email"><?php echo htmlspecialchars($userEmail); ?></div>
            </div>
            <a class="dropdown-item" href="profile.php">
              <i class="fas fa-user"></i> My Profile
            </a>
            <a class="dropdown-item text-danger" href="logout.php">
              <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          </div>
        </li>
   
      <?php else: ?>
        <li class="nav-item">
          <a class="nav-link" href="home.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="menu.php">Menu</a>
        </li>
        <!-- <li class="nav-item">
          <a class="nav-link" href="contact.php">Contact</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="about_us.php">About Us</a>
        </linav-item">
          <a class="nav-link" href="logout.php">Logout</a>
        </li> -->
    
        <li class="nav-item">
          <a class="nav-link auth-btn" href="login.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link auth-btn" href="regisration.php">Registration</a>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<!-- Optional JavaScript -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

// Close profile dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const toggle = event.target.closest('.profile-dropdown-toggle');
    
    if (!toggle && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
</script>

</body>
</html>
<?php endif; ?>
