<?php
    session_start();
    include('./db_connect.php');

    function handle_file($file) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpg','image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
    
            // check file type
            if (!in_array($fileType, $allowedTypes)) {
                echo "<script>alert('Unsupported file type! Please upload a JPG, JPEG, PNG, or GIF file.'); </script>";
                return '1';
            }

            // upload file
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time().'-'.basename($file['name']);
            $uploadFilePath = $uploadDir . $fileName;
            move_uploaded_file($file['tmp_name'], $uploadFilePath);

            return $uploadFilePath;

        } elseif (isset($file) && $file['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        } else {
            $errorMessage = file_upload_error_message($file['error']);
            echo "<script>alert('File upload error! " . $errorMessage . "'); </script>";
            return '1';
        }
    }
    function file_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_OK:
                return 'There is no error, the file uploaded successfully.';
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded files exceeds the post_max_size directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Get user information
    $user_sql = "SELECT * FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        // User not found (should not happen, but just in case)
        session_destroy();
        echo("<script>alert('User not found. Please log in again.'); </script>");
        header("Location: login.html");
        exit;
    }
    
    $user = $user_result->fetch_assoc();
    
    // section預設是account
    // 但如果有傳參數section進來，就用傳進來的值
    $section = isset($_GET['section']) ? $_GET['section'] : 'account';
    $message = ''; 
    
    
    // Handle form submissions based on section
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($section) {
            case 'account':
                // Handle basic information update
                if (isset($_POST['update_info'])) {                         // after clicking update information button
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $fullname = trim($_POST['fullname']);
                    $address = trim($_POST['address']);
                    $phone = trim($_POST['phone']);
                    
                    // Check if username is already taken by someone else
                    $check_sql_username = "SELECT user_id FROM users WHERE user_name = ? AND user_id != ?";
                    $check_stmt = $conn->prepare($check_sql_username);
                    $check_stmt->bind_param("si", $username,  $user_id);
                    $check_stmt->execute();
                    $check_result_username = $check_stmt->get_result();
                    
                    $check_sql_email = "SELECT user_id FROM users WHERE account = ? AND user_id != ?";
                    $check_stmt = $conn->prepare($check_sql_email);
                    $check_stmt->bind_param("si", $email,  $user_id);
                    $check_stmt->execute();
                    $check_result_email = $check_stmt->get_result();

                    if ($check_result_username->num_rows > 0) {
                        $message = "Username already exists. Please choose another one. ";
                    } elseif ($check_result_email->num_rows > 0) {
                        $message = "Email already exists. Please choose another one. ";
                    } elseif ($check_result_username->num_rows >0 && $check_result_email->num_rows > 0) {
                        $message = "Username and email already exist. Please choose another ones. ";
                    } else {
                        // Update user information
                        $update_sql = "UPDATE users SET user_name = ?, account = ?, fullname = ?, address = ?, phone = ? WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("sssssi", $username, $email, $fullname, $address, $phone, $user_id);
                        
                        if ($update_stmt->execute()) {
                            $message = "Profile updated successfully! ";
                            
                            // Update user variable with new information
                            // for my own use not for database
                            $user['user_name'] = $username;
                            $user['account'] = $email;
                            $user['fullname'] = $fullname;
                            $user['address'] = $address;
                            $user['phone'] = $phone;
                        } else {
                            $message = "Error updating profile: " . $conn->error;
                        }
                    }

                    // if user has uploaded a new picture successfully, then update the database
                    $user_picture = handle_file($_FILES['picture']);
                    if ($user_picture === '1') {
                        $message = "Error uploading file. Please try again.";
                    } elseif ($user_picture !== '') {
                        $update_sql = "UPDATE users SET user_picture = ? WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $user_picture, $user_id);
                        if ($update_stmt->execute()) {
                            $message .= "Profile picture updated successfully! ";
                            // Update user variable with new picture
                            $user['user_picture'] = $user_picture;
                        } else {
                            $message = "Error updating profile picture: " . $conn->error;
                        }
                    }
                }
                break;
                
            case 'password':
                // Handle password change
                if (isset($_POST['change_password'])) {
                    $current_password = $_POST['current_password'];
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    // Verify current password
                    if (!password_verify($current_password, $user['hash_password'])) {
                        $message = "Current password is incorrect.";
                    } elseif ($new_password !== $confirm_password) {
                        $message = "New password and confirmation do not match.";
                    } else {
                        // Hash the new password
                        $hash_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update password in database
                        $update_sql = "UPDATE users SET hash_password = ? WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $hash_password, $user_id);
                        
                        if ($update_stmt->execute()) {
                            $message = "Password changed successfully!";
                            // Update user variable
                            $user['hash_password'] = $hash_password;
                        } else {
                            $message = "Error changing password: " . $conn->error;
                        }
                    }
                }
                break;

            case 'products':
                // Handle product operations (add, edit, delete)
                if (isset($_POST['add_product'])) {
                    // Process new product
                    $product_name = trim($_POST['product_name']);
                    $category = $_POST['category'];
                    $price = (int)$_POST['price'];
                    $stock = (int)$_POST['stock'];
                    $description = trim($_POST['description']);
                    
                    // Validate input
                    $errors = [];
                    if (empty($product_name)) $errors[] = "Product name is required.";
                    if ($price <= 0) $errors[] = "Price must be greater than 0.";
                    if ($stock <= 0) $errors[] = "Stock must be greater than 0.";
                    
                    // Process images
                    $images = [];
                    if (!empty($_FILES['product_images']['name'][0])) {
                        $uploadDir = 'uploads_products/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        
                        foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                            $file_name = $_FILES['product_images']['name'][$key];
                            $file_size = $_FILES['product_images']['size'][$key];
                            $file_tmp = $_FILES['product_images']['tmp_name'][$key];
                            $file_type = $_FILES['product_images']['type'][$key];
                            $file_error = $_FILES['product_images']['error'][$key];

                            if($file_error !== UPLOAD_ERR_OK) {
                                $errorMessage = file_upload_error_message($file_error);
                                $errors[] = "Error uploading file: $file_name. $errorMessage";
                                continue;
                            }
                            
                            // Check file type
                            if (!in_array($file_type, $allowedTypes)) {
                                $errors[] = "File type not allowed: $file_name. Only JPG, JPEG, PNG, and GIF are supported.";
                                continue;
                            }
                            
                            // Generate unique filename
                            $new_file_name = time() . '_' . rand(1000, 9999) . '_' . $file_name;
                            $file_path = $uploadDir . $new_file_name;
                            
                            if (move_uploaded_file($file_tmp, $file_path)) {
                                $images[] = $file_path;
                            } else {
                                $errors[] = "Failed to upload file: $file_name";
                            }
                        }
                    } else {
                        $errors[] = "At least one product image is required.";
                    }
                    
                    if (empty($errors)) {
                        // Save product to database
                        // $product_id = uniqid('prod_');
                        $images_str = implode(',', $images);
                        // $created_at = date('Y-m-d H:i:s');
                        
                        $sql = "INSERT INTO products (product_name, category, seller_id, description, product_image, in_stock, price)
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssissii",  $product_name, $category, $user_id, $description, $images_str, $stock, $price);
                        
                        if ($stmt->execute()) {
                            $message = "Product added successfully!";
                        } else {
                            $message = "Error adding product: " . $conn->error;
                        }
                    } else {
                        $message = implode('<br>', $errors);
                    }
                }
                elseif (isset($_POST['edit_product'])) {
                    // Process edit product
                    $product_id = $_POST['product_id'];
                    $product_name = trim($_POST['product_name']);
                    $category = $_POST['category'];
                    $price = (int)$_POST['price'];
                    $stock = (int)$_POST['stock'];
                    $description = trim($_POST['description']);
                    
                    // Validate input
                    $errors = [];
                    if (empty($product_name)) $errors[] = "Product name is required.";
                    if ($price <= 0) $errors[] = "Price must be greater than 0.";
                    if ($stock <= 0) $errors[] = "Stock must be greater than 0.";
                    
                    // Get current product information
                    $get_product_sql = "SELECT * FROM products WHERE product_id = ? AND seller_id = ?";
                    $get_product_stmt = $conn->prepare($get_product_sql);
                    $get_product_stmt->bind_param("ii", $product_id, $user_id);
                    $get_product_stmt->execute();
                    $get_product_result = $get_product_stmt->get_result();
                    
                    if ($get_product_result->num_rows === 0) {
                        $errors[] = "Product not found or you don't have permission to edit it.";
                    } else {
                        $product = $get_product_result->fetch_assoc();
                        $images = explode(',', $product['product_image']);
                        
                        // Process new images if provided
                        if (!empty($_FILES['product_images']['name'][0])) {
                            $uploadDir = 'uploads_products/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                            $new_images = [];
                            
                            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                                $file_name = $_FILES['product_images']['name'][$key];
                                $file_size = $_FILES['product_images']['size'][$key];
                                $file_tmp = $_FILES['product_images']['tmp_name'][$key];
                                $file_type = $_FILES['product_images']['type'][$key];
                                
                                // Check file type
                                if (!in_array($file_type, $allowedTypes)) {
                                    $errors[] = "File type not allowed: $file_name. Only JPG, JPEG, PNG, and GIF are supported.";
                                    continue;
                                }
                                
                                // Generate unique filename
                                $new_file_name = time() . '_' . rand(1000, 9999) . '_' . $file_name;
                                $file_path = $uploadDir . $new_file_name;
                                
                                if (move_uploaded_file($file_tmp, $file_path)) {
                                    $new_images[] = $file_path;
                                } else {
                                    $errors[] = "Failed to upload file: $file_name";
                                }
                            }
                            
                            if (!empty($new_images)) {
                                $images = $new_images;
                            }
                        }
                    }
                    
                    if (empty($errors)) {
                        // Update product in database
                        $images_str = implode(',', $images);
                        // $updated_at = date('Y-m-d H:i:s');
                        
                        $conn->query("SET time_zone = '+08:00'");
                        $sql = "UPDATE products SET product_name = ?, category = ?, description = ?, product_image = ?, in_stock = ?, price = ?  
                                WHERE product_id = ? AND seller_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssiiii", $product_name, $category, $description, $images_str, $stock, $price, $product_id, $user_id);
                        

                        if ($stmt->execute()) {
                            $message = "Product updated successfully!";
                        } else {
                            $message = "Error updating product: " . $conn->error;
                        }
                    } else {
                        $message = implode('<br>', $errors);
                    }
                }
                elseif (isset($_POST['delete_product'])) {
                    // Process delete product
                    $product_id = $_POST['product_id'];
                    
                    // Check if product exists and belongs to the user
                    $check_sql = "SELECT product_id FROM products WHERE product_id = ? AND seller_id = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("ii", $product_id, $user_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows === 0) {
                        $message = "Product not found or you don't have permission to delete it.";
                    } else {
                        // Delete product
                        $delete_sql = "DELETE FROM products WHERE product_id = ? AND seller_id = ?";
                        $delete_stmt = $conn->prepare($delete_sql);
                        $delete_stmt->bind_param("ii", $product_id, $user_id);
                        
                        if ($delete_stmt->execute()) {
                            $message = "Product deleted successfully!";
                        } else {
                            $message = "Error deleting product: " . $conn->error;
                        }
                    }
                }
                break;
                
            case 'delete':
                // Handle account deletion
                if (isset($_POST['confirmation'])) {
                    $confirmation = $_POST['confirmation'];
                    $required_confirmation = $user['user_name'] . '@delete';
                    // echo "<script>alert('Confirmation text: $confirmation'); </script>";
                    if ($confirmation !== $required_confirmation) {
                        // echo "<script>alert('Incorrect confirmation text. Account not deleted.'); </script>";
                        $message = "Incorrect format. Please try again.";
                    } else {
                        // Check for incomplete orders
                        $order_sql = "SELECT order_id FROM orders 
                                      WHERE (seller_id = ? OR buyer_id = ?) 
                                      AND status != 'completed'";
                        $order_stmt = $conn->prepare($order_sql);
                        $order_stmt->bind_param("ii", $user_id, $user_id);
                        $order_stmt->execute();
                        $order_result = $order_stmt->get_result();
                        
                        if ($order_result->num_rows > 0) {
                            $message = "You have incomplete orders. Please complete them before deleting your account.";
                        } else {

                            $conn->begin_transaction();

                            try {
                                // Delete account and products
                                $delete_product_sql = "DELETE FROM products WHERE seller_id = ?";
                                $delete_product_stmt = $conn->prepare($delete_product_sql);
                                $delete_product_stmt->bind_param("i", $user_id);                            
                                $delete_product_stmt->execute();

                                $delete_sql = "DELETE FROM users WHERE user_id = ?";
                                $delete_stmt = $conn->prepare($delete_sql);
                                $delete_stmt->bind_param("i", $user_id);
                                
                                if ($delete_stmt->execute()) {
                                    // Log out the user
                                    session_destroy();
                                    header("Location: main.php");
                                    exit;
                                } else {
                                    $message = "Error deleting account: " . $conn->error;
                                }

                                // Commit transaction
                                $conn->commit();
                            } catch (Exception $e) {
                                // Rollback transaction in case of error
                                $conn->rollback();
                                echo "Error deleting account: " . $e->getMessage();
                            }
                                
                        }
                    }
                }
                break;
                
            default:
                // Invalid section
                break;
        }
    }
    
    // Get user products for product section
    $products = [];
    if ($section === 'products') {
        $products_sql = "SELECT * FROM products WHERE seller_id = ? ORDER BY create_at DESC";
        $products_stmt = $conn->prepare($products_sql);
        $products_stmt->bind_param("s", $user_id);
        $products_stmt->execute();
        $products_result = $products_stmt->get_result();
        
        while ($row = $products_result->fetch_assoc()) {
            $row['images'] = explode(',', $row['product_image']);
            $products[] = $row;
        }
    }
    
    // Get orders for orders section
    $purchased_orders = [];
    $sold_items = [];
    $history_orders = [];
    
    if ($section === 'orders') {
        // Purchased orders (incomplete)
        $purchased_sql = "SELECT orders.*, users.user_name as seller_name FROM orders 
                         JOIN users ON orders.seller_id = users.user_id 
                         WHERE orders.buyer_id = ? AND orders.status != 'completed' 
                         ORDER BY orders.checkout_at DESC";
        $purchased_stmt = $conn->prepare($purchased_sql);
        $purchased_stmt->bind_param("i", $user_id);
        $purchased_stmt->execute();
        $purchased_result = $purchased_stmt->get_result();
        
        while ($row = $purchased_result->fetch_assoc()) {
            // Parse order_products (assumed to be JSON string)
            $row['products'] = json_decode($row['order_products'], true);

            // Example of order_products JSON structure:
            // [{"id":"1","name":"iPhone","price":"999","quantity":"2","image":"iphone.jpg"},
            // {"id":"2","name":"Galaxy S","price":"899","quantity":"1","image":"galaxy.jpg"}]

            $purchased_orders[] = $row;
        }
        
        // Sold items (incomplete)
        $sold_sql = "SELECT orders.*, users.user_name as buyer_name FROM orders
                    JOIN users ON orders.buyer_id = users.user_id 
                    WHERE orders.seller_id = ? AND orders.status != 'completed' 
                    ORDER BY orders.checkout_at DESC";
        $sold_stmt = $conn->prepare($sold_sql);
        $sold_stmt->bind_param("i", $user_id);
        $sold_stmt->execute();
        $sold_result = $sold_stmt->get_result();
        
        while ($row = $sold_result->fetch_assoc()) {
            // Parse order_products (assumed to be JSON string)
            $row['products'] = json_decode($row['order_products'], true);
            $sold_items[] = $row;
        }
        
        // History orders (completed)
        $history_sql = "SELECT orders.*, seller.user_name as seller_name, buyer.user_name as buyer_name FROM orders 
                       JOIN users seller ON orders.seller_id = seller.user_id 
                       JOIN users buyer ON orders.buyer_id = buyer.user_id 
                       WHERE (orders.seller_id = ? OR orders.buyer_id = ?) 
                       AND orders.status = 'completed' 
                       ORDER BY orders.completed_at DESC";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("ii", $user_id, $user_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        
        while ($row = $history_result->fetch_assoc()) {
            // Parse order_products (assumed to be JSON string)
            $row['products'] = json_decode($row['order_products'], true);
            $history_orders[] = $row;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="user.css">
</head>
<body>
    <div class="user-container">
        <!-- Main Banner (similar to main.php) -->
        <div class="main-banner">
            <div class="main-page-link">
                <a href="main.php">E-Shop System</a>
            </div>
            
            <div class="user-function-area">
                <a href="cart.php" class="shopping-cart-btn">
                    <i class="fa-solid fa-cart-shopping"></i>
                </a>
                <div class="user-profile-pic">
                    <img src="<?php echo htmlspecialchars($user['user_picture']); ?>" alt="Profile Picture">
                </div>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </div>
        
        <!-- User Content Area -->
        <div class="user-content">
            <!-- Side Menu -->
            <div class="side-menu">
                <a href="user.php?section=account" class="menu-item <?php echo $section === 'account' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user"></i> My Account
                </a>
                <a href="user.php?section=password" class="menu-item <?php echo $section === 'password' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-lock"></i> Change Password
                </a>
                <a href="user.php?section=products" class="menu-item <?php echo $section === 'products' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-box"></i> My Products
                </a>
                <a href="user.php?section=orders" class="menu-item <?php echo $section === 'orders' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-receipt"></i> My Purchase
                </a>
                <a href="user.php?section=delete" class="menu-item <?php echo $section === 'delete' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-trash"></i> Delete Account
                </a>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($section === 'account'): ?>
                    <!-- My Account Section -->
                    <div class="section-header">
                        <h2>My Account</h2>
                    </div>
                    
                    <div class="basic-info">
                        <div class="info-display">
                            <div class="user-pic">
                                <img src="<?php echo htmlspecialchars($user['user_picture']); ?>" alt="User Picture">
                            </div>
                            <div class="user-details">
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['user_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['account']); ?></p>
                                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                            </div>
                        </div>
                        <button id="edit-info-btn" class="edit-btn">Edit Information</button>
                    </div>
                    
                    <div id="edit-info-form" class="edit-form" style="display: none;">
                        <h3>Edit Basic Information</h3>
                        <form method="POST" action="user.php?section=account" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="username">Update Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['user_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Update Email (Account)</label>
                                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($user['account']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="picture">Update user photo (Within 2 MB)</label>
                                <p style="color: red; margin-bottom: 5px;"> Hint: If you don't want to update the photo. Don't select any image.</p>
                                <div id="preview"></div>
                                <input type="file" id="picture" name="picture" accept="image/jpeg,image/jpg,image/png,image/gif" value="">
                            </div>
                            <div class="form-group">
                                <label for="fullname">Update Full Name</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Update Address</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Update Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            <div class="form-actions">
                                <button type="button" id="cancel-edit-btn" class="cancel-btn">Cancel</button>
                                <button type="submit" name="update_info" class="submit-btn">Update Information</button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($section === 'password'): ?>
                    <!-- Change Password Section -->
                    <div class="section-header">
                        <h2>Change Password</h2>
                    </div>
                    
                    <div class="password-form">
                        <form method="POST" action="user.php?section=password">
                            <div class="form-group password-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                                <span class="toggle-password" onclick="togglePassword('current_password')">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </span>
                            </div>
                            <div class="form-group password-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <span class="toggle-password" onclick="togglePassword('new_password')">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </span>
                            </div>
                            <div class="form-group password-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <span class="toggle-password" onclick="togglePassword('confirm_password')">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </span>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($section === 'products'): ?>
                    <!-- My Products Section -->
                    <div class="section-header">
                        <h2>My Products</h2>
                        <button id="add-product-btn" class="add-btn">Add Product</button>
                    </div>
                    
                    <!-- Add Product Form -->
                    <div id="add-product-form" class="product-form" style="display: none;">
                        <h3>Add New Product</h3> <br>
                        <form method="POST" action="user.php?section=products" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="product_name">Product Name</label>
                                <input type="text" id="product_name" name="product_name" placeholder="Enter Product Name" required>
                            </div>
                            <div class="form-group">
                                <label for="category">Select Category</label>
                                <select id="category" name="category" required>
                                    <option value="">Select a Category</option>
                                    <option value="Electronics & Accessories">Electronics & Accessories</option>
                                    <option value="Home Appliances & Living Essentials">Home Appliances & Living Essentials</option>
                                    <option value="Clothing & Accessories">Clothing & Accessories</option>
                                    <option value="Beauty & Personal Care">Beauty & Personal Care</option>
                                    <option value="Food & Beverages">Food & Beverages</option>
                                    <option value="Home & Furniture">Home & Furniture</option>
                                    <option value="Sports & Outdoor Equipment">Sports & Outdoor Equipment</option>
                                    <option value="Automotive & Motorcycle Accessories">Automotive & Motorcycle Accessories</option>
                                    <option value="Baby & Maternity Products">Baby & Maternity Products</option>
                                    <option value="Books & Office Supplies">Books & Office Supplies</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price">Set Price for the Product</label>
                                <input type="number" id="price" name="price" min="1" placeholder="Enter Price" required>
                            </div>
                            <div class="form-group">
                                <label for="stock">Product Inventory</label>
                                <input type="number" id="stock" name="stock" min="1" placeholder="Enter Product Quantity" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Product Description</label>
                                <textarea id="description" name="description" rows="10" placeholder="Enter Product Description" style="font-family: sans-serif;"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="product_images">Upload Product Images</label>
                                <p style="color: red; margin-bottom: 5px;"> Hint: Each image has to be under 2MB. Please don't upload more than 4 images.</p>
                                <input type="file" id="product_images" name="product_images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif" required>
                                <div id="image-preview" class="image-preview"></div>
                            </div>
                            <div class="form-actions">
                                <button type="button" id="cancel-add-product" class="cancel-btn">Cancel</button>
                                <button type="submit" name="add_product" class="submit-btn">Add Product</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Product List -->
                    <div class="product-list">
                        <?php if (empty($products)): ?>
                            <p class="no-items">You haven't added any products yet.</p>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <?php if (!empty($product['images'][0])): ?>
                                            <img src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                            
                                            <!-- more than one image -->
                                            <?php if (count($product['images']) > 1): ?>
                                                <div class="image-toggle">
                                                    <button class="prev-img" data-product-id="<?php echo $product['product_id']; ?>">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    <button class="next-img" data-product-id="<?php echo $product['product_id']; ?>">
                                                        <i class="fa-solid fa-chevron-right"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="no-image">No Image</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                        <p class="category"><?php echo htmlspecialchars($product['category']); ?></p>
                                        <p class="price">$<?php echo htmlspecialchars($product['price']); ?></p>
                                        <div class="stock-sold">
                                            <p>In Stock: <?php echo htmlspecialchars($product['in_stock']); ?></p>
                                            <p>Sold: <?php echo htmlspecialchars($product['sold']); ?></p>
                                        </div>
                                        <div class="product-actions">
                                            <button class="edit-product-btn" data-product-id="<?php echo $product['product_id']; ?>">Edit</button>
                                            <button class="delete-product-btn" data-product-id="<?php echo $product['product_id']; ?>" data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>">Delete</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Product Form (hidden by default) -->
                                    <div id="edit-form-<?php echo $product['product_id']; ?>" class="edit-product-form" style="display: none;">
                                        <h3>Edit Product</h3>
                                        <form method="POST" action="user.php?section=products" enctype="multipart/form-data">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <div class="form-group">
                                                <label for="edit_name_<?php echo $product['product_id']; ?>">Product Name</label>
                                                <input type="text" id="edit_name_<?php echo $product['product_id']; ?>" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_category_<?php echo $product['product_id']; ?>">Category</label>
                                                <select id="edit_category_<?php echo $product['product_id']; ?>" name="category" required>
                                                    <option value="">Select Category</option>
                                                    <option value="Electronics & Accessories" <?php echo $product['category'] === 'Electronics & Accessories' ? 'selected' : ''; ?>>Electronics & Accessories</option>
                                                    <option value="Home Appliances & Living Essentials" <?php echo $product['category'] === 'Home Appliances & Living Essentials' ? 'selected' : ''; ?>>Home Appliances & Living Essentials</option>
                                                    <option value="Clothing & Accessories" <?php echo $product['category'] === 'Clothing & Accessories' ? 'selected' : ''; ?>>Clothing & Accessories</option>
                                                    <option value="Beauty & Personal Care" <?php echo $product['category'] === 'Beauty & Personal Care' ? 'selected' : ''; ?>>Beauty & Personal Care</option>
                                                    <option value="Food & Beverages" <?php echo $product['category'] === 'Food & Beverages' ? 'selected' : ''; ?>>Food & Beverages</option>
                                                    <option value="Home & Furniture" <?php echo $product['category'] === 'Home & Furniture' ? 'selected' : ''; ?>>Home & Furniture</option>
                                                    <option value="Sports & Outdoor Equipment" <?php echo $product['category'] === 'Sports & Outdoor Equipment' ? 'selected' : ''; ?>>Sports & Outdoor Equipment</option>
                                                    <option value="Automotive & Motorcycle Accessories" <?php echo $product['category'] === 'Automotive & Motorcycle Accessories' ? 'selected' : ''; ?>>Automotive & Motorcycle Accessories</option>
                                                    <option value="Baby & Maternity Products" <?php echo $product['category'] === 'Baby & Maternity Products' ? 'selected' : ''; ?>>Baby & Maternity Products</option>
                                                    <option value="Books & Office Supplies" <?php echo $product['category'] === 'Books & Office Supplies' ? 'selected' : ''; ?>>Books & Office Supplies</option>
                                                    <option value="Other" <?php echo $product['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_price_<?php echo $product['product_id']; ?>">Price</label>
                                                <input type="number" id="edit_price_<?php echo $product['product_id']; ?>" name="price" min="1" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_stock_<?php echo $product['product_id']; ?>">In Stock</label>
                                                <input type="number" id="edit_stock_<?php echo $product['product_id']; ?>" name="stock" min="1" value="<?php echo htmlspecialchars($product['in_stock']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_description_<?php echo $product['product_id']; ?>">Description</label>
                                                <textarea id="edit_description_<?php echo $product['product_id']; ?>" name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Current Images</label>
                                                <div class="current-images">
                                                    <?php foreach ($product['images'] as $image): ?>
                                                        <div class="thumbnail">
                                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Product Image">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_images_<?php echo $product['product_id']; ?>">Upload New Images</label>
                                                <p style="color: red; margin-bottom: 5px;"> Hint: If you don't want to update the photo. Don't select any image.</p>
                                                <input type="file" id="edit_images_<?php echo $product['product_id']; ?>" name="product_images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif">
                                                <div class="image-preview"></div>
                                            </div>
                                            <div class="form-actions">
                                                <button type="button" class="cancel-edit-btn" data-product-id="<?php echo $product['product_id']; ?>">Cancel</button>
                                                <button type="submit" name="edit_product" class="submit-btn">Update Product</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Delete Product Confirmation Modal -->
                    <div id="delete-modal" class="modal">
                        <div class="modal-content">
                            <h3>Delete Product</h3>
                            <p>Are you sure you want to delete "<span id="delete-product-name"></span>"?</p>
                            <p>This action cannot be undone.</p>
                            <form method="POST" action="user.php?section=products">
                                <input type="hidden" id="delete-product-id" name="product_id">
                                <div class="form-actions">
                                    <button type="button" id="cancel-delete" class="cancel-btn">Cancel</button>
                                    <button type="submit" name="delete_product" class="delete-btn">Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'orders'): ?>
                    <!-- My Purchase Section -->
                    <div class="section-header">
                        <h2>My Purchase</h2>
                    </div>
                    
                    <div class="order-tabs">
                        <button class="tab-btn active" data-tab="purchased">My Purchased Orders</button>
                        <button class="tab-btn" data-tab="sold">My Sold Items</button>
                        <button class="tab-btn" data-tab="history">History Order</button>
                    </div>
                    
                    <!-- My Purchased Orders Tab -->
                    <div id="purchased-tab" class="order-tab active">
                        <?php if (empty($purchased_orders)): ?>
                            <p class="no-items">You don't have any ongoing purchases.</p>
                        <?php else: ?>
                            <?php foreach ($purchased_orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <p><strong>Seller:</strong> <?php echo htmlspecialchars($order['seller_name']); ?></p>
                                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['pay_method']); ?></p>
                                            <p><strong>Checkout Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['checkout_at'])); ?></p>
                                        </div>
                                        <div class="order-total">
                                            <p><strong>Total:</strong> $<?php echo htmlspecialchars($order['order_price']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="order-items">
                                        <?php foreach ($order['products'] as $item): ?>
                                            <div class="order-item">
                                                <div class="item-image">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="no-image">No Image</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-info">
                                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                                    <p>Price: $<?php echo htmlspecialchars($item['price']); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <?php if ($order['status'] === 'shipped'): ?>
                                            <form method="POST" action="complete_order.php">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" name="complete_order" class="action-btn">Complete Order</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn disabled">Order hasn't been shipped yet</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- My Sold Items Tab -->
                    <div id="sold-tab" class="order-tab">
                        <?php if (empty($sold_items)): ?>
                            <p class="no-items">You don't have any ongoing sales.</p>
                        <?php else: ?>
                            <?php foreach ($sold_items as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            
                                            <p><strong>Buyer:</strong> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['pay_method']); ?></p>
                                            <p><strong>Checkout Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['checkout_at'])); ?></p>
                                        </div>
                                        <div class="order-total">
                                            <p><strong>Total:</strong> $<?php echo htmlspecialchars($order['order_price']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="order-items">
                                        <?php foreach ($order['products'] as $item): ?>
                                            <div class="order-item">
                                                <div class="item-image">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="no-image">No Image</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-info">
                                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                                    <p>Price: $<?php echo htmlspecialchars($item['price']); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="POST" action="ship_order.php">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" name="ship_order" class="action-btn">Ship Order</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn disabled">Order Shipped</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- History Order Tab -->
                    <div id="history-tab" class="order-tab">
                        <?php if (empty($history_orders)): ?>
                            <p class="no-items">You don't have any completed orders.</p>
                        <?php else: ?>
                            <?php foreach ($history_orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
    
                                            <p><strong>Seller:</strong> <?php echo htmlspecialchars($order['seller_name']); ?></p>
                                            <p><strong>Buyer:</strong> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['pay_method']); ?></p>
                                            <p><strong>Checkout Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['checkout_at'])); ?></p>
                                            <p><strong>Completed Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['completed_at'])); ?></p>
                                        </div>
                                        <div class="order-total">
                                            <p><strong>Total:</strong> $<?php echo htmlspecialchars($order['order_price']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="order-items">
                                        <?php foreach ($order['products'] as $item): ?>
                                            <div class="order-item">
                                                <div class="item-image">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="no-image">No Image</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-info">
                                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                                    <p>Price: $<?php echo htmlspecialchars($item['price']); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($section === 'delete'): ?>
                    <!-- Delete Account Section -->
                    <div class="section-header">
                        <h2>Delete Account</h2>
                    </div>
                    
                    <div class="delete-account">
                        <div class="warning-box">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <p>Warning: This action cannot be undone. All your account data, including purchase history and product listings, will be permanently deleted.</p>
                        </div>
                        
                        <form method="POST" action="user.php?section=delete" id="delete-account-form">
                            <div class="form-group">
                                <label for="confirmation">If you want to delete your accout, please follow the following format: <br> Username@delete</label>
                                <input type="text" id="confirmation" name="confirmation" placeholder="<?php echo htmlspecialchars($user['user_name']); ?>@delete" required>
                            </div>
                            <div class="form-actions">
                                <button type="button" id="delete-account-btn" class="delete-btn">Delete My Account</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Delete Account Confirmation Modal -->
                    <div id="delete-account-modal" class="modal">
                        <div class="modal-content">
                            <h3>Delete Account</h3>
                            <p>Are you absolutely sure you want to delete your account?</p>
                            <p>This will permanently delete all your data and cannot be undone.</p>
                            <div class="form-actions">
                                <button type="button" id="cancel-delete-account" class="cancel-btn">Cancel</button>
                                <button type="button" id="confirm-delete-account" class="delete-btn">Yes</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 產品圖片資料，用於JavaScript使用 -->
    <script>
        // 初始化產品圖片資料，提供給外部JavaScript使用
        var productImagesData = {};
        
        <?php if ($section === 'products' && !empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <?php if (!empty($product['images'])): ?>
                    productImagesData['<?php echo $product['product_id']; ?>'] = {
                        images: <?php echo json_encode($product['images']); ?>,
                        currentIndex: 0
                    };
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </script>
    
    <!-- 引入外部 JavaScript 文件 -->
    <script src="user.js"></script>
</body>
</html>