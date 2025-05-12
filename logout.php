<?php
session_start();

// 先清除session變數，再銷毀session
session_unset();    // 清除所有session變數
session_destroy();  // 銷毀session

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header("Location: main.php");
exit();
?>