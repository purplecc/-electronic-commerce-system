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
        header("Location: user.php?section=orders");
        exit;
    }
    
    $order_id = $_POST['order_id'];
    
    // Verify that the logged-in user is the seller of this order
    $verify_sql = "SELECT * FROM orders WHERE order_id = ? AND seller_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        // Order not found or user is not the seller
        echo "<script>alert('Order not found.');</script>";
        header("Location: user.php?section=orders");
        exit;
    }
    
    $order = $verify_result->fetch_assoc();
    
    // Make sure the order is in 'pending' status
    if ($order['status'] !== 'pending') {
        // Order is already shipped or completed
        echo '<script>alert("Order is already shipped or completed.");</script>';
        header("Location: user.php?section=orders");
        exit;
    }
    
    // Update order status to 'shipped'
    $update_sql = "UPDATE orders SET status = 'shipped' WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $order_id);
    
    if ($update_stmt->execute()) {
        // Success
        header("Location: user.php?section=orders&shipped=1");
    } else {
        // Error
        header("Location: user.php?section=orders&error=1");
    }
    
    $conn->close();
?>