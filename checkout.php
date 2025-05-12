<?php
    session_start();
    include('./db_connect.php');

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Check if seller ID is provided
    if (!isset($_GET['seller'])) {
        echo "<script>alert('Seller ID not provided. Redirecting to cart.');</script>";
        header("Location: cart.php");
        exit;
    }
    
    $seller_id = $_GET['seller'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        echo "<script>alert('cart not exisited');</script>";
        header("Location: cart.php");
        exit;
        // $_SESSION['cart'] = [];
    }
    
    // Get user information for pre-filling the form
    $user_sql = "SELECT * FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    // Get seller information
    $seller_sql = "SELECT user_name FROM users WHERE user_id = ?";
    $seller_stmt = $conn->prepare($seller_sql);
    $seller_stmt->bind_param("i", $seller_id);
    $seller_stmt->execute();
    $seller_result = $seller_stmt->get_result();
    
    if ($seller_result->num_rows === 0) {
        // Seller not found
        echo "<script>alert('Seller not found. Redirecting to cart.');</script>";
        header("Location: cart.php");
        exit;
    }
    
    $seller = $seller_result->fetch_assoc();
    
    // Get items from the cart for this seller
    $cart_items = [];
    $order_total = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        // echo "<script>console.log('item: " . json_encode($item) . "');</script>";
        if ($item['seller_id'] == $seller_id) {
            // echo "<script>console.log('enter');</script>";
            $cart_items[] = $item;
            $order_total += $item['price'] * $item['quantity'];
        }
    }
    
    if (empty($cart_items)) {
        // No items found for this seller
        echo "<script>alert('No item. Redirecting to cart.');</script>";
        header("Location: cart.php");
        exit;
    }
    
    // Handle checkout form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payment_method = $_POST['payment_method'];
        $fullname = trim($_POST['fullname']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        
        // Validate input
        $errors = [];
        if (empty($payment_method)) $errors[] = "Please select a payment method.";
        if (empty($fullname)) $errors[] = "Please enter your full name.";
        if (empty($address)) $errors[] = "Please enter your delivery address.";
        if (empty($phone)) $errors[] = "Please enter your phone number.";
        
        if (empty($errors)) {
            // Create order
            // $order_id = uniqid('order_');
            $order_price = $order_total;
            $checkout_at = date('Y-m-d H:i:s');
            
            // Prepare order_products as JSON
            $order_products = [];
            foreach ($cart_items as $item) {
                $order_products[] = [
                    'id' => $item['product_id'],
                    'name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'image' => $item['image']
                ];
                // $order_products[] = $item['product_id'];
            }
            // Example JSON format:
            // [{"id":"1","name":"iPhone","price":"999","quantity":"2","image":"iphone.jpg"},
            // {"id":"2","name":"Galaxy S","price":"899","quantity":"1","image":"galaxy.jpg"}]
            $order_products_json = json_encode($order_products);
            // $order_products_str = implode(',', $order_products);
            
            // Insert order into database
            $order_sql = "INSERT INTO orders (seller_id, buyer_id, order_price, pay_method, 
                        buyer_fullname, buyer_phone, buyer_address, order_products, status, checkout_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("iiissssss",  $seller_id, $user_id, $order_price, 
                                $payment_method, $fullname, $phone, $address, $order_products_json, $checkout_at);
            
            if ($order_stmt->execute()) {
                // Remove items from cart
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['seller_id'] == $seller_id) {
                        unset($_SESSION['cart'][$key]);
                    }
                }
                
                // Re-index the array
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                
                // Redirect to success page
                // header("Location: order_success.php?order_id=" . $order_id);
                echo "<script>
                    alert('Order placed successfully! Redirecting to cart.');
                    window.location.href = 'cart.php'; </script>";
                exit;
            } else {
                $errors[] = "Error placing order: " . $conn->error;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="checkout.css">
</head>
<body>
    <div class="checkout-container">
        <!-- Main Banner (similar to main.php) -->
        <div class="main-banner">
            <div class="main-page-link">
                <a href="main.php">E-Shop System</a>
            </div>
            
            <div class="user-function-area">
                <a href="cart.php" class="shopping-cart-btn">
                    <i class="fa-solid fa-cart-shopping"></i>
                </a>
                <a href="user.php" class="user-page-link">
                    <?php echo htmlspecialchars($user['user_name']);?>
                </a>
                <div class="user-profile-pic">
                    <img src="<?php echo htmlspecialchars($user['user_picture']); ?>" alt="Profile Picture">
                </div>
                
            </div>
        </div>
        
        <div class="checkout-content">
            <div class="section-header">
                <h2>Checkout</h2>
                <p>Seller: <?php echo htmlspecialchars($seller['user_name']); ?></p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="checkout-layout">
                <!-- Order Details -->
                <div class="order-details">
                    <h3>Order Products</h3>
                    <div class="order-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="checkout-item">
                                <div class="item-image">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p class="item-price">$<?php echo htmlspecialchars($item['price']); ?></p>
                                    <p class="item-quantity">Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                    <p class="item-subtotal">Subtotal: $<?php echo htmlspecialchars($item['price'] * $item['quantity']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-total">
                        <h3>Order Total</h3>
                        <p class="total-amount">$<?php echo htmlspecialchars($order_total); ?></p>
                    </div>
                </div>
                
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <h3>Order Information</h3>
                    <form method="POST" action="">
                        <div class="form-section">
                            <h4>Payment Method</h4>
                            <div class="payment-options">
                                <div class="payment-option">
                                    <input type="radio" id="credit-card" name="payment_method" value="credit card" required>
                                    <label for="credit-card">Credit Card</label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" id="cash" name="payment_method" value="cash on delivery" required>
                                    <label for="cash">Cash on Delivery</label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" id="e-wallet" name="payment_method" value="e-wallet" required>
                                    <label for="e-wallet">E-Wallet</label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" id="bank-transfer" name="payment_method" value="bank transfer" required>
                                    <label for="bank-transfer">Bank Transfer</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Shipping Information</h4>
                            <div class="form-group">
                                <label for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Delivery Address</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="place-order-btn">Place Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>