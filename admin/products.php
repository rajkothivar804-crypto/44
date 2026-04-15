<?php 
include 'header.php';
include '../database/DB.php';

$alert_message = '';
$alert_type = '';

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

// Create products table if it doesn't exist
$create_products_table = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    status ENUM('Available', 'Out of Stock', 'Discontinued') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

mysqli_query($conn, $create_products_table);

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    
    $image_url = '';
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/products/';
        $file_name = basename($_FILES['product_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            // Use original filename but ensure uniqueness
            $base_name = pathinfo($file_name, PATHINFO_FILENAME);
            $new_file_name = $base_name . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // If file exists, add timestamp to make it unique
            $counter = 1;
            while (file_exists($upload_path)) {
                $new_file_name = $base_name . '_' . $counter . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;
                $counter++;
            }
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = 'images/products/' . $new_file_name;
            }
        }
    }
    
    // Validation
    if (empty($product_name) || strlen($product_name) < 2) {
        $alert_message = "Product name must be at least 2 characters!";
        $alert_type = "danger";
    } elseif (empty($category)) {
        $alert_message = "Category is required!";
        $alert_type = "danger";
    } elseif ($price <= 0) {
        $alert_message = "Price must be greater than 0!";
        $alert_type = "danger";
    } elseif ($stock < 0) {
        $alert_message = "Stock cannot be negative!";
        $alert_type = "danger";
    } else {
        // Determine status
        $status = $stock > 0 ? 'Available' : 'Out of Stock';
        
        $insert_sql = "INSERT INTO products (product_name, category, description, price, stock, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, 'sssdiss', $product_name, $category, $description, $price, $stock, $image_url, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $alert_message = "Product added successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "Error adding product!";
            $alert_type = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = intval($_POST['product_id']);
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    
    $image_url = isset($_POST['current_image']) ? $_POST['current_image'] : '';
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/products/';
        $file_name = basename($_FILES['product_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            // Generate unique filename
            $new_file_name = uniqid('product_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = 'images/products/' . $new_file_name;
                
                // Delete old image if it exists
                if (!empty($_POST['current_image']) && file_exists('../' . $_POST['current_image'])) {
                    unlink('../' . $_POST['current_image']);
                }
            }
        }
    }
    
    // Validation
    if (empty($product_name) || strlen($product_name) < 2) {
        $alert_message = "Product name must be at least 2 characters!";
        $alert_type = "danger";
    } elseif (empty($category)) {
        $alert_message = "Category is required!";
        $alert_type = "danger";
    } elseif ($price <= 0) {
        $alert_message = "Price must be greater than 0!";
        $alert_type = "danger";
    } elseif ($stock < 0) {
        $alert_message = "Stock cannot be negative!";
        $alert_type = "danger";
    } else {
        // Determine status
        $status = $stock > 0 ? 'Available' : 'Out of Stock';
        
        $update_sql = "UPDATE products SET product_name = ?, category = ?, description = ?, price = ?, stock = ?, image_url = ?, status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, 'sssdissi', $product_name, $category, $description, $price, $stock, $image_url, $status, $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $alert_message = "Product updated successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating product!";
            $alert_type = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Product
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $alert_message = "Product deleted successfully!";
        $alert_type = "success";
    } else {
        $alert_message = "Error deleting product!";
        $alert_type = "danger";
    }
    mysqli_stmt_close($stmt);
}

// Fetch all products
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT id, product_name, category, description, price, stock, image_url, status, created_at FROM products";
$conditions = array();

if (!empty($search_query)) {
    $conditions[] = "(product_name LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%' OR category LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%')";
}

if (!empty($filter_category)) {
    $conditions[] = "category = '" . mysqli_real_escape_string($conn, $filter_category) . "'";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);
$products = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Get all categories for dropdown
$category_sql = "SELECT DISTINCT category FROM products ORDER BY category ASC";
$category_result = mysqli_query($conn, $category_sql);
$categories = array();

if ($category_result && mysqli_num_rows($category_result) > 0) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row['category'];
    }
}

// Get badge color based on status
function getStatusBadge($status, $stock) {
    if ($stock <= 0) {
        return 'badge-danger';
    } elseif ($stock < 20) {
        return 'badge-warning';
    } else {
        return 'badge-success';
    }
}

// Get icon for category
function getCategoryIcon($category) {
    $icons = [
        'Beverages' => 'fas fa-mug-hot',
        'Pastries' => 'fas fa-cookie',
        'Snacks' => 'fas fa-apple-alt',
        'Desserts' => 'fas fa-ice-cream',
        'Sandwiches' => 'fas fa-breadslice'
    ];
    return isset($icons[$category]) ? $icons[$category] : 'fas fa-cube';
}

// Get color for category
function getCategoryColor($index) {
    $colors = [
        'linear-gradient(135deg, #ffc107, #e0a800)',
        'linear-gradient(135deg, #ff9800, #f57c00)',
        'linear-gradient(135deg, #f4a261, #e76f51)',
        'linear-gradient(135deg, #a8dadc, #457b9d)',
        'linear-gradient(135deg, #8b4513, #d2691e)'
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
                    <h1 style="color: #5c4033; font-weight: 700; margin-bottom: 5px;">Products Management</h1>
                    <p style="color: #999;">Total Products: <strong><?php echo count($products); ?></strong></p>
                </div>
                <a href="#" onclick="$('#addProductModal').modal('show')" class="btn-admin btn-admin-primary">
                    <i class="fas fa-plus-circle"></i> Add New Product
                </a>
            </div>

            <!-- Search and Filter Section -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; color: #5c4033; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-search"></i> Search Product
                        </label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by product name or category..." style="width: 100%; border: 2px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="display: block; color: #5c4033; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-filter"></i> Filter by Category
                        </label>
                        <select name="category" style="border: 2px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" style="background: linear-gradient(135deg, #8b4513, #d2691e); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="products.php" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Products Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $index => $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #f8f9fa; border: 2px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                                                <i class="fas fa-image" style="font-size: 24px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-primary" style="background: rgba(139, 69, 19, 0.1); color: #8b4513; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($product['category']); ?></span>
                                    </td>
                                    <td><strong><?php echo getCurrencySymbol(); ?><?php echo number_format($product['price'], 2); ?></strong></td>
                                    <td>
                                        <span style="background: <?php echo $product['stock'] > 20 ? '#d4edda' : ($product['stock'] > 0 ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $product['stock'] > 20 ? '#155724' : ($product['stock'] > 0 ? '#856404' : '#721c24'); ?>; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                            <?php echo intval($product['stock']); ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?php echo getStatusBadge($product['status'], $product['stock']); ?>">
                                            <?php echo $product['stock'] > 20 ? 'Available' : ($product['stock'] > 0 ? 'Low Stock' : 'Out of Stock'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?delete=1&id=<?php echo $product['id']; ?>" class="btn-admin btn-admin-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Are you sure?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    No products found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 30px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 22px;">
                    <i class="fas fa-plus-circle"></i> Add New Product
                </h5>
                <button type="button" class="close" style="color: white; opacity: 0.8;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 40px;">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-box"></i> Product Name
                        </label>
                        <input type="text" name="product_name" class="form-control" placeholder="Enter product name" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="addProductNameError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-th"></i> Category
                        </label>
                        <input type="text" name="category" class="form-control" placeholder="e.g., Beverages, Pastries" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="addCategoryError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-align-left"></i> Description
                        </label>
                        <textarea name="description" class="form-control" placeholder="Product description" rows="3" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px; font-family: inherit;"></textarea>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-image"></i> Product Image
                        </label>
                        <input type="file" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.gif" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <small style="color: #666; font-size: 12px;">Accepted formats: JPG, PNG, GIF. Max size: 5MB</small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                                <i class="fas fa-rupee-sign"></i> Price (<?php echo getCurrencySymbol(); ?>)
                            </label>
                            <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                            <div class="error-message" id="addPriceError"></div>
                        </div>

                        <div class="form-group">
                            <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                                <i class="fas fa-cube"></i> Stock
                            </label>
                            <input type="number" name="stock" class="form-control" placeholder="0" min="0" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                            <div class="error-message" id="addStockError"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0; margin-top: 20px;">
                        <button type="submit" name="add_product" class="btn btn-success" style="background: linear-gradient(135deg, #28a745, #20c997); border: none; color: white; padding: 12px 30px; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer; text-transform: uppercase;">
                            <i class="fas fa-save"></i> Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border: none; border-radius: 15px 15px 0 0; padding: 30px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 22px;">
                    <i class="fas fa-edit"></i> Edit Product
                </h5>
                <button type="button" class="close" style="color: white; opacity: 0.8;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 40px;">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="editProductId">
                    <input type="hidden" name="current_image" id="editCurrentImage">
                    
                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-box"></i> Product Name
                        </label>
                        <input type="text" name="product_name" id="editProductName" class="form-control" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="editProductNameError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-th"></i> Category
                        </label>
                        <input type="text" name="category" id="editCategory" class="form-control" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <div class="error-message" id="editCategoryError"></div>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-align-left"></i> Description
                        </label>
                        <textarea name="description" id="editDescription" class="form-control" rows="3" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px; font-family: inherit;"></textarea>
                    </div>

                    <div class="form-group">
                        <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-image"></i> Product Image
                        </label>
                        <input type="file" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.gif" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                        <small style="color: #666; font-size: 12px;">Accepted formats: JPG, PNG, GIF. Max size: 5MB. Leave empty to keep current image.</small>
                        <div id="currentImagePreview" style="margin-top: 10px;"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                                <i class="fas fa-rupee-sign"></i> Price (<?php echo getCurrencySymbol(); ?>)
                            </label>
                            <input type="number" name="price" id="editPrice" class="form-control" step="0.01" min="0" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                            <div class="error-message" id="editPriceError"></div>
                        </div>

                        <div class="form-group">
                            <label style="color: #5c4033; font-weight: 600; margin-bottom: 10px;">
                                <i class="fas fa-cube"></i> Stock
                            </label>
                            <input type="number" name="stock" id="editStock" class="form-control" min="0" style="border: 2px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size: 14px;">
                            <div class="error-message" id="editStockError"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0; margin-top: 20px;">
                        <button type="submit" name="edit_product" class="btn btn-primary" style="background: linear-gradient(135deg, #007bff, #0056b3); border: none; color: white; padding: 12px 30px; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer; text-transform: uppercase;">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('editProductId').value = product.id;
    document.getElementById('editProductName').value = product.product_name;
    document.getElementById('editCategory').value = product.category;
    document.getElementById('editDescription').value = product.description || '';
    document.getElementById('editPrice').value = product.price;
    document.getElementById('editStock').value = product.stock;
    document.getElementById('editCurrentImage').value = product.image_url || '';
    
    // Show current image preview if exists
    const imagePreview = document.getElementById('currentImagePreview');
    if (product.image_url) {
        imagePreview.innerHTML = '<img src="../' + product.image_url + '" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 2px solid #ddd;" alt="Current product image"><br><small style="color: #666;">Current image - upload new image to replace</small>';
    } else {
        imagePreview.innerHTML = '<small style="color: #999;">No image uploaded yet</small>';
    }
    
    $('#editProductModal').modal('show');
}
</script>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Clear errors when modal is shown
    $('#addProductModal').on('show.bs.modal', function() {
        clearAddErrors();
    });
    
    $('#editProductModal').on('show.bs.modal', function() {
        clearEditErrors();
    });
    
    // Real-time validation - clear errors when user starts typing
    $('#addProductModal input').on('input', function() {
        const fieldName = $(this).attr('name');
        const errorId = 'add' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Error';
        $('#' + errorId).hide();
        $(this).removeClass('is-invalid');
    });
    
    $('#editProductModal input').on('input', function() {
        const fieldId = $(this).attr('id');
        const errorId = fieldId + 'Error';
        $('#' + errorId).hide();
        $(this).removeClass('is-invalid');
    });
    
    // Add Product Form Validation
    $('#addProductModal form').on('submit', function(e) {
        clearAddErrors();
        let isValid = true;
        
        const productName = $('input[name="product_name"]').val().trim();
        const category = $('input[name="category"]').val().trim();
        const price = parseFloat($('input[name="price"]').val());
        const stock = parseInt($('input[name="stock"]').val());
        
        if (productName === '') {
            showAddError('addProductNameError', 'Product name is required.');
            $('input[name="product_name"]').addClass('is-invalid');
            isValid = false;
        } else if (productName.length < 2) {
            showAddError('addProductNameError', 'Product name must be at least 2 characters.');
            $('input[name="product_name"]').addClass('is-invalid');
            isValid = false;
        }
        
        if (category === '') {
            showAddError('addCategoryError', 'Category is required.');
            $('input[name="category"]').addClass('is-invalid');
            isValid = false;
        }
        
        if (isNaN(price) || price <= 0) {
            showAddError('addPriceError', 'Price must be greater than 0.');
            $('input[name="price"]').addClass('is-invalid');
            isValid = false;
        }
        
        if (isNaN(stock) || stock < 0) {
            showAddError('addStockError', 'Stock cannot be negative.');
            $('input[name="stock"]').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Edit Product Form Validation
    $('#editProductModal form').on('submit', function(e) {
        clearEditErrors();
        let isValid = true;
        
        const productName = $('#editProductName').val().trim();
        const category = $('#editCategory').val().trim();
        const price = parseFloat($('#editPrice').val());
        const stock = parseInt($('#editStock').val());
        
        if (productName === '') {
            showEditError('editProductNameError', 'Product name is required.');
            $('#editProductName').addClass('is-invalid');
            isValid = false;
        } else if (productName.length < 2) {
            showEditError('editProductNameError', 'Product name must be at least 2 characters.');
            $('#editProductName').addClass('is-invalid');
            isValid = false;
        }
        
        if (category === '') {
            showEditError('editCategoryError', 'Category is required.');
            $('#editCategory').addClass('is-invalid');
            isValid = false;
        }
        
        if (isNaN(price) || price <= 0) {
            showEditError('editPriceError', 'Price must be greater than 0.');
            $('#editPrice').addClass('is-invalid');
            isValid = false;
        }
        
        if (isNaN(stock) || stock < 0) {
            showEditError('editStockError', 'Stock cannot be negative.');
            $('#editStock').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    function showAddError(errorId, message) {
        $('#' + errorId).text(message).show();
    }
    
    function showEditError(errorId, message) {
        $('#' + errorId).text(message).show();
    }
    
    function clearAddErrors() {
        $('.error-message[id^="add"]').hide();
        $('#addProductModal .form-control').removeClass('is-invalid');
    }
    
    function clearEditErrors() {
        $('.error-message[id^="edit"]').hide();
        $('#editProductModal .form-control').removeClass('is-invalid');
    }
});
</script>

<?php include 'footer.php'; ?>
