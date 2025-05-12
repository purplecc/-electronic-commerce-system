<?php
    session_start();

    include('./db_connect.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $account = $_POST['account'];
        $password = $_POST['password'];
    

        // 1. 先寫SQL語句，有問號
        $sql = "SELECT * FROM users WHERE account = ?";

        // 2. 用 prepare 預備這個SQL
        $stmt = $conn->prepare($sql);

        // 3. 把真正的 account 值綁定進去
        $stmt->bind_param("s", $account); // "s" 是string，account是變數

        // 4. 執行
        $stmt->execute();

        // 5. 拿結果
        $result = $stmt->get_result();

        // 有找到這個帳號
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // 核對密碼
            if (password_verify($password, $row['hash_password'])) {
                // 密碼正確，登入成功
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['account'] = $row['account'];


                $stmt->close();
                $conn->close();
                
                header("Location: main.php");
                exit();
            } else {
                // 密碼錯誤
                echo "<script>alert('Incorrect password!'); window.history.back();</script>";
            } 
        } else {
            // 沒有找到這個帳號
            echo "<script>alert('This account has not been registered yet.'); window.history.back();</script>";
        }

        $stmt->close();
        $conn->close();
    } 

?>