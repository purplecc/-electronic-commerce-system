<?php
    session_start();
    include('./db_connect.php');

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Handle remove product from cart
    if (isset($_GET['remove']) && isset($_GET['seller'])) {
        $remove_product_id = $_GET['remove'];
        $seller_id = $_GET['seller'];
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['product_id'] == $remove_product_id && $item['seller_id'] == $seller_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        
        // Re-index the array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
    }
    
    // Handle remove entire order (all products from a seller)
    if (isset($_GET['remove_order'])) {
        $seller_id = $_GET['remove_order'];
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['seller_id'] == $seller_id) {
                unset($_SESSION['cart'][$key]);
            }
        }
        
        // Re-index the array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
    }
    
    // Group cart items by seller
    $cart_by_seller = [];
    
    foreach ($_SESSION['cart'] as $item) {
        $seller_id = $item['seller_id'];
        
        if (!isset($cart_by_seller[$seller_id])) {
            // Get seller name
            $seller_sql = "SELECT user_name FROM users WHERE user_id = ?";
            $seller_stmt = $conn->prepare($seller_sql);
            $seller_stmt->bind_param("i", $seller_id);
            $seller_stmt->execute();
            $seller_result = $seller_stmt->get_result();
            $seller = $seller_result->fetch_assoc();
            
            $cart_by_seller[$seller_id] = [
                'seller_name' => $seller['user_name'],
                'items' => [],
                'total' => 0
            ];
        }
        
        $cart_by_seller[$seller_id]['items'][] = $item;
        $cart_by_seller[$seller_id]['total'] += $item['price'] * $item['quantity'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - E-Commerce</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="cart.css">
</head>
<body>
    <div class="cart-container">
        <!-- Main Banner (similar to main.php) -->
        <div class="main-banner">
            <div class="main-page-link">
                <a href="main.php">E-Shop System</a>
            </div>
            
            <div class="user-function-area">
                <?php
                    // Get user info
                    $user_sql = "SELECT user_picture, user_name FROM users WHERE user_id = ?";
                    $user_stmt = $conn->prepare($user_sql);
                    $user_stmt->bind_param("i", $user_id);
                    $user_stmt->execute();
                    $user_result = $user_stmt->get_result();
                    $user = $user_result->fetch_assoc();
                ?>
                <a href="user.php" class="user-page-link">
                    <?php echo htmlspecialchars($user['user_name']);?>
                </a>
                <div class="user-profile-pic">
                    <a href="user.php">
                        <img src="<?php echo htmlspecialchars($user['user_picture']); ?>" alt="Profile Picture">
                    </a>
                </div>
                
            </div>
        </div>
        
        <!-- Cart Content -->
        <div class="cart-content">
            <div class="section-header">
                <h2>Shopping Cart</h2>
                <a href="main.php" class="continue-shopping">Continue Shopping</a>
            </div>
            
            <?php if (empty($cart_by_seller)): ?>
                <div class="empty-cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <p>Your cart is empty.</p>
                    <a href="main.php" class="shop-now-btn">Shop Now</a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_by_seller as $seller_id => $seller_cart): ?>
                    <div class="cart-order">
                        <div class="order-header">
                            <h3>Seller: <?php echo htmlspecialchars($seller_cart['seller_name']); ?></h3>
                            <a href="cart.php?remove_order=<?php echo $seller_id; ?>" class="remove-order-btn">
                                <i class="fa-solid fa-trash"></i> Remove Order
                            </a>
                        </div>
                        
                        <div class="order-items">
                            <?php foreach ($seller_cart['items'] as $item): ?>
                                <div class="cart-item">
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
                                    
                                    <div class="item-actions">
                                        <a href="cart.php?remove=<?php echo $item['product_id']; ?>&seller=<?php echo $seller_id; ?>" class="remove-item-btn">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                <p>Order Total: <span>$<?php echo htmlspecialchars($seller_cart['total']); ?></span></p>
                            </div>
                            <a href="checkout.php?seller=<?php echo $seller_id; ?>" class="checkout-btn">
                                Checkout
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>