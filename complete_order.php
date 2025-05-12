<?php
    session_start();
    include('./db_connect.php');

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Check if order_id is provided
    if (!isset($_POST['order_id'])) {
        echo "<script>alert('Order ID not provided.');</script>";
        header("Location: user.php?section=orders");
        exit;
    }
    
    $order_id = $_POST['order_id'];
    
    // Verify that the logged-in user is the buyer of this order
    $verify_sql = "SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        // Order not found or user is not the buyer
        echo "<script>alert('Order not found.');</script>";
        header("Location: user.php?section=orders");
        exit;
    }
    
    $order = $verify_result->fetch_assoc();
    
    // Make sure the order is in 'shipped' status
    if ($order['status'] !== 'shipped') {
        // Order is not shipped yet or already completed
        echo '<script>alert("Order is not shipped yet or already completed.");</script>';
        header("Location: user.php?section=orders");
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update order status to 'completed' and set completed_at timestamp
        $complete_time = date('Y-m-d H:i:s');
        $update_sql = "UPDATE orders SET status = 'completed', completed_at = ? WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $complete_time, $order_id);
        $update_stmt->execute();
        
        // Get order products
        $products = json_decode($order['order_products'], true);
        
        // Update product stock and sold counts
        foreach ($products as $product) {
            $product_id = $product['id'];
            $quantity = $product['quantity'];
            
            $update_product_sql = "UPDATE products SET in_stock = in_stock - ?, sold = sold + ? 
                                   WHERE product_id = ?";
            $update_product_stmt = $conn->prepare($update_product_sql);
            $update_product_stmt->bind_param("iii", $quantity, $quantity, $product_id);
            $update_product_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Success
        header("Location: user.php?section=orders&completed=1");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Error
        header("Location: user.php?section=orders&error=1");
    }
    
    $conn->close();
?>