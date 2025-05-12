<?php
    session_start();
    include('./db_connect.php');

    // Check if product ID is provided
    if (!isset($_GET['id'])) {
        header("Location: main.php");
        exit;
    }

    $product_id = $_GET['id'];

    // Get product details
    $sql = "SELECT products.*, users.user_name AS seller_name 
            FROM products 
            JOIN users ON products.seller_id = users.user_id 
            WHERE products.product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Product not found
        header("Location: main.php");
        exit;
    }

    $product = $result->fetch_assoc();
    
    // Extract product images "/images/1.jpg", "images/2.jpg"
    $product['images'] = explode(',', $product['product_image']);

    // Handle Add to Cart functionality
    $message = '';
    
    // after clicking add to cart button
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        if (!isset($_SESSION['user_id'])) {
            // Redirect to login if not logged in
            header("Location: login.html");
            exit;
        }
        
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        // Validate quantity
        if ($quantity <= 0) {
            $quantity = 1;
        } elseif ($quantity > $product['in_stock']) {
            $quantity = $product['in_stock'];
            $message = "Maximum purchase quantity is {$product['in_stock']}!";
        }
        
        // Initialize cart in session if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if product already in cart
        $product_in_cart = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                // Update quantity
                $item['quantity'] += $quantity;
                $message = "Product added to cart!";
                // Check if quantity exceeds stock
                if ($item['quantity'] > $product['in_stock']) {
                    $item['quantity'] = $product['in_stock'];
                    $message = "Maximum purchase quantity is {$product['in_stock']}!";
                }
                $product_in_cart = true;
                break;
            }
        }
        
        // Add product to cart if not already in cart
        if (!$product_in_cart) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'seller_id' => $product['seller_id'],
                'product_name' => $product['product_name'],
                'price' => $product['price'],
                'image' => $product['images'][0] ?? '',
                'quantity' => $quantity
            ];
            $message = "Product added to cart!";
        }
        
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - E-Commerce</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="product.css">
</head>
<body>
    <div class="product-container">
        <!-- Main Banner (same as in main.php) -->
        <div class="main-banner">
            <div class="main-page-link">
                <a href="main.php">E-Shop System</a>
            </div>
            
            <div class="user-function-area">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <?php
                        // Get user info
                        $user_id = $_SESSION['user_id'];
                        $user_sql = "SELECT user_picture, user_name FROM users WHERE user_id = ?";
                        $user_stmt = $conn->prepare($user_sql);
                        $user_stmt->bind_param("s", $user_id);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        $user = $user_result->fetch_assoc();
                    ?>
                    <a href="cart.php" class="shopping-cart-btn">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </a>
                    <a href="user.php" class="user-page-link">
                        <?php echo htmlspecialchars($user['user_name']);?>
                    </a>
                    <div class="user-profile-pic">
                        <a href="user.php">
                            <img src="<?php echo htmlspecialchars($user['user_picture']); ?>" alt="Profile Picture">
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- User is not logged in -->
                    <a href="login.html" class="login-link">Login</a>
                    <a href="register.php" class="register-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="product-details">
            <!-- Display success/error message if any -->
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'Maximum') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="product-gallery">
                <?php if (!empty($product['images'])): ?>
                    <div class="main-image">
                        <img id="current-image" src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    </div>
                    
                    <?php if (count($product['images']) > 1): ?>
                        <!-- tbu -->
                        <div class="image-toggle">
                            <button class="prev-img" data-product-id="<?php echo $product['product_id']; ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button class="next-img" data-product-id="<?php echo $product['product_id']; ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                        <!-- tbu -->
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-image">No Image Available</div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
                <p class="seller">Seller: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                <p class="price">$<?php echo htmlspecialchars($product['price']); ?></p>
                <div class="stock-info">
                    <p>In Stock: <?php echo htmlspecialchars($product['in_stock']); ?></p>
                    
                </div>
                
                <div class="description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <!-- Add to Cart Form -->
                <form method="POST" class="add-to-cart-form">
                    <div class="quantity-input">
                        <label for="quantity">Quantity:</label>
                        <div class="quantity-controls">
                            <button type="button" id="quantity-decrease" class="quantity-btn">-</button>
                            <!-- TBU -->
                            <input type="text" id="quantity" name="quantity" value="1" >
                            <!-- TBU -->
                            <button type="button" id="quantity-increase" class="quantity-btn">+</button>
                        </div>
                        <div id="quantity-warning"></div>
                    </div>
                    
                    <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                        <i class="fa-solid fa-cart-plus"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const maxStock = <?php echo (int)$product['in_stock']; ?>;
        var productImagesData = {};
    
        <?php if (!empty($product['images'])): ?>
            productImagesData['<?php echo $product['product_id']; ?>'] = {
                images: <?php echo json_encode($product['images']); ?>,
                currentIndex: 0
            };
        <?php endif; ?>

    </script>
    <script src="product.js"></script>
</body>
</html>