<?php
$servername = "ip address"; // 你的MySQL IP
$username = "username";          // 你的MySQL帳號
$password = "password";          // 你的MySQL密碼
$dbname = "database";             // 你的資料庫名

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
} 
$conn->query("SET time_zone = '+08:00'");
// 連線成功就不做事
?>
