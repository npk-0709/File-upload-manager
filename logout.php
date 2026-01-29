<?php
require_once 'config.php';

// Xóa session và đăng xuất
session_destroy();

// Chuyển về trang login
header('Location: login.html');
exit();
?>
