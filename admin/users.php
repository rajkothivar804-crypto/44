<?php 
include 'header.php';
include '../database/DB.php';

$alert_message = '';
$alert_type = '';

// Handle User Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validation
    if (empty($fullname) || strlen($fullname) < 3) {
        $alert_message = "Full name must be at least 3 characters!";
        $alert_type = "danger";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = "Invalid email address!";
        $alert_type = "danger";
    } elseif (empty($phone) || !preg_match('/^[0-9]{10,}$/', $phone)) {
        $alert_message = "Phone must be at least 10 digits!";
        $alert_type = "danger";
    } elseif (empty($address)) {
        $alert_message = "Address is required!";
        $alert_type = "danger";
    } elseif (empty($password) || strlen($password) < 6) {
        $alert_message = "Password must be at least 6 characters!";
        $alert_type = "danger";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 's', $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        mysqli_stmt_close($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $alert_message = "Email already exists!";
            $alert_type = "danger";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_sql = "INSERT INTO users (fullname, email, phone, address, password) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'sssss', $fullname, $email, $phone, $address, $hashed_password);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $alert_message = "User added successfully!";
                $alert_type = "success";
                // Refresh users list
                $sql = "SELECT id, fullname, email, phone, address, created_at FROM users ORDER BY created_at DESC";
                $result = mysqli_query($conn, $sql);
                $users = array();
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $users[] = $row;
                    }
                }
            } else {
                $alert_message = "Error adding user: " . mysqli_error($conn);
                $alert_type = "danger";
            }
            mysqli_stmt_close($insert_stmt);
        }
    }
}

// Handle User Deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $alert_message = "User deleted successfully!";
        $alert_type = "success";
    } else {
        $alert_message = "Error deleting user!";
        $alert_type = "danger";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all users from database
$sql = "SELECT id, fullname, email, phone, address, created_at FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$users = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// Get first letter for avatar
function getInitial($name) {
    return strtoupper(substr($name, 0, 1));
}

// Get color for avatar
function getAvatarColor($index) {
    $colors = [
        'linear-gradient(135deg, #8b4513, #d2691e)',      // Brown
        'linear-gradient(135deg, #007bff, #0056b3)',      // Blue
        'linear-gradient(135deg, #28a745, #1e7e34)',      // Green
        'linear-gradient(135deg, #ffc107, #e0a800)',      // Yellow
        'linear-gradient(135deg, #dc3545, #c82333)',      // Red
        'linear-gradient(135deg, #6f42c1, #5a32a3)'       // Purple
    ];
    return $colors[$index % count($colors)];
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
            </style>

            <!-- Page Title -->
            <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="color: #5c4033; font-weight: 700; margin-bottom: 5px;">Users Management</h1>
                    <p style="color: #999;">Total Users: <strong><?php echo count($users); ?></strong></p>
                </div>
                <a href="#" onclick="$('#addUserModal').modal('show')" class="btn-admin btn-admin-primary">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 40px; height: 40px; background: <?php echo getAvatarColor($index); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px;">
                                                <?php echo getInitial($user['fullname']); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="viewUserDetails(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="?delete=1&id=<?php echo $user['id']; ?>" class="btn-admin btn-admin-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Are you sure?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    No users found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

<!-- User Details Modal -->
<div id="userDetailsModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 30px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 22px;">
                    <i class="fas fa-user-circle"></i> User Details
                </h5>
                <button type="button" class="close" style="color: white; opacity: 0.8;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 40px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Left Column -->
                    <div>
                        <div style="text-align: center; margin-bottom: 30px;">
                            <div id="modalUserAvatar" style="width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 40px; margin: 0 auto 15px;">
                            </div>
                            <h3 id="modalUserName" style="margin: 0; color: #5c4033; font-weight: 700;"></h3>
                            <p id="modalUserEmail" style="color: #999; margin-top: 5px;"></p>
                        </div>

                        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px;">
                            <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> Contact Information
                            </h5>
                            
                            <div style="margin-bottom: 15px;">
                                <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Email</strong>
                                <p id="detailEmail" style="margin: 5px 0; color: #333;"></p>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Phone</strong>
                                <p id="detailPhone" style="margin: 5px 0; color: #333;"></p>
                            </div>

                            <div>
                                <strong style="color: #5c4033; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Member Since</strong>
                                <p id="detailDate" style="margin: 5px 0; color: #333;"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </h5>
                        
                        <div style="background: linear-gradient(135deg, #f5f5dc20 0%, #fffacd40 100%); padding: 20px; border-radius: 10px; border-left: 5px solid #8b4513; margin-bottom: 30px;">
                            <p id="detailAddress" style="margin: 0; color: #333; line-height: 1.6;"></p>
                        </div>

                        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px;">
                            <h5 style="color: #5c4033; font-weight: 700; margin-bottom: 15px;">
                                <i class="fas fa-chart-bar"></i> Statistics
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 700; color: #8b4513;">0</div>
                                    <small style="color: #999;">Total Orders</small>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 700; color: #28a745;">Active</div>
                                    <small style="color: #999;">Account Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #eee; padding: 20px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #6c757d; border: none; padding: 8px 20px; border-radius: 5px; color: white; font-weight: 600;">Close</button>

            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 30px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 22px;">
                    <i class="fas fa-user-plus"></i> Add New User
                </h5>
                <button type="button" class="close" style="color: white; opacity: 0.8;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 40px;">
                <form method="POST" action="">
                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" name="fullname" class="form-control" placeholder="Enter full name" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="addFullnameError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="addEmailError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" name="phone" class="form-control" placeholder="Enter 10 digit phone number" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="addPhoneError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </label>
                        <textarea name="address" class="form-control" placeholder="Enter address" rows="3" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px; font-family: inherit;"></textarea>
                        <div class="error-message" id="addAddressError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password (min 6 characters)" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="addPasswordError"></div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" name="add_user" class="btn btn-success" style="background: linear-gradient(135deg, #28a745, #20c997); border: none; color: white; padding: 12px 30px; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer; text-transform: uppercase;">
                            <i class="fas fa-save"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Clear errors when modal is shown
    $('#addUserModal').on('show.bs.modal', function() {
        clearAddErrors();
    });
    
    // Real-time validation - clear errors when user starts typing
    $('#addUserModal input, #addUserModal textarea').on('input', function() {
        const fieldName = $(this).attr('name');
        const errorId = 'add' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Error';
        $('#' + errorId).hide();
        $(this).removeClass('is-invalid');
    });
    
    // Add User Form Validation
    $('#addUserModal form').on('submit', function(e) {
        clearAddErrors();
        let isValid = true;
        
        const fullname = $('input[name="fullname"]').val().trim();
        const email = $('input[name="email"]').val().trim();
        const phone = $('input[name="phone"]').val().trim();
        const address = $('textarea[name="address"]').val().trim();
        const password = $('input[name="password"]').val().trim();
        
        // Full name validation
        if (fullname === '') {
            showAddError('addFullnameError', 'Full name is required.');
            $('input[name="fullname"]').addClass('is-invalid');
            isValid = false;
        } else if (fullname.length < 3) {
            showAddError('addFullnameError', 'Full name must be at least 3 characters.');
            $('input[name="fullname"]').addClass('is-invalid');
            isValid = false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '') {
            showAddError('addEmailError', 'Email is required.');
            $('input[name="email"]').addClass('is-invalid');
            isValid = false;
        } else if (!emailRegex.test(email)) {
            showAddError('addEmailError', 'Please enter a valid email address.');
            $('input[name="email"]').addClass('is-invalid');
            isValid = false;
        }
        
        // Phone validation
        const phoneRegex = /^\d{10,}$/;
        if (phone === '') {
            showAddError('addPhoneError', 'Phone number is required.');
            $('input[name="phone"]').addClass('is-invalid');
            isValid = false;
        } else if (!phoneRegex.test(phone.replace(/\D/g, ''))) {
            showAddError('addPhoneError', 'Phone must be at least 10 digits.');
            $('input[name="phone"]').addClass('is-invalid');
            isValid = false;
        }
        
        // Address validation
        if (address === '') {
            showAddError('addAddressError', 'Address is required.');
            $('textarea[name="address"]').addClass('is-invalid');
            isValid = false;
        }
        
        // Password validation
        if (password === '') {
            showAddError('addPasswordError', 'Password is required.');
            $('input[name="password"]').addClass('is-invalid');
            isValid = false;
        } else if (password.length < 6) {
            showAddError('addPasswordError', 'Password must be at least 6 characters.');
            $('input[name="password"]').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    function showAddError(errorId, message) {
        $('#' + errorId).text(message).show();
    }
    
    function clearAddErrors() {
        $('.error-message[id^="add"]').hide();
        $('#addUserModal .form-control').removeClass('is-invalid');
    }
});
</script>

<script>
function viewUserDetails(user) {
    // Format the date
    const date = new Date(user.created_at);
    const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    
    // Get initial
    const initial = user.fullname.charAt(0).toUpperCase();
    
    // Avatar colors
    const colors = [
        'linear-gradient(135deg, #8b4513, #d2691e)',
        'linear-gradient(135deg, #007bff, #0056b3)',
        'linear-gradient(135deg, #28a745, #1e7e34)',
        'linear-gradient(135deg, #ffc107, #e0a800)',
        'linear-gradient(135deg, #dc3545, #c82333)',
        'linear-gradient(135deg, #6f42c1, #5a32a3)'
    ];
    
    const colorIndex = Object.values(user).join().charCodeAt(0) % colors.length;
    const avatarColor = colors[colorIndex];
    
    // Populate modal
    document.getElementById('modalUserAvatar').style.background = avatarColor;
    document.getElementById('modalUserAvatar').textContent = initial;
    document.getElementById('modalUserName').textContent = user.fullname;
    document.getElementById('modalUserEmail').textContent = user.email;
    document.getElementById('detailEmail').textContent = user.email;
    document.getElementById('detailPhone').textContent = user.phone;
    document.getElementById('detailDate').textContent = formattedDate;
    document.getElementById('detailAddress').textContent = user.address;
    
    // Show modal
    $('#userDetailsModal').modal('show');
}

// Auto-refresh page if user was added successfully
<?php if ($alert_type === 'success' && strpos($alert_message, 'added') !== false): ?>
    setTimeout(function() {
        location.reload();
    }, 1500);
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
