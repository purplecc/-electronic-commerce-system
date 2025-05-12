<?php
$servername = "140.123.102.94"; // 你的MySQL IP
$username = "410410023";          // 你的MySQL帳號
$password = "410410023";          // 你的MySQL密碼
$dbname = "410410023";             // 你的資料庫名

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
} 
$conn->query("SET time_zone = '+08:00'");
// 連線成功就不做事
?>
