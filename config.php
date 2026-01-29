<?php
// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

// Cấu hình đăng nhập (không dùng SQL)
define('LOGIN_USERNAME', 'khuongsosad');
define('LOGIN_PASSWORD', 'khuongsosad');


// Thư mục lưu trữ files
define('UPLOAD_DIR', __DIR__ . '/resources/');

// cấu hình php 


// Kết nối database
function getDBConnection()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Lỗi kết nối database: ' . $conn->connect_error]));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// Kiểm tra đăng nhập
session_start();

function isLoggedIn()
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.html');
        exit();
    }
}

