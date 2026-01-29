<?php
require_once 'config.php';

header('Content-Type: application/json');

// Lấy dữ liệu từ POST request
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Kiểm tra so sánh 2 chuỗi text (không dùng SQL)
if ($username === LOGIN_USERNAME && $password === LOGIN_PASSWORD) {
    // Đăng nhập thành công
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $username;
    
    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công!'
    ]);
} else {
    // Đăng nhập thất bại
    echo json_encode([
        'success' => false,
        'message' => 'Tên đăng nhập hoặc mật khẩu không đúng!'
    ]);
}
?>
