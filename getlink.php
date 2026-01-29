<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

// Lấy hash file
$hash = $_POST['hash'] ?? 0;

if (empty($hash)) {
    echo json_encode(['success' => false, 'message' => 'Hash file không hợp lệ!']);
    exit();
}

$conn = getDBConnection();

// Lấy thông tin file từ database
$stmt = $conn->prepare("SELECT * FROM files WHERE hash = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'File không tồn tại!']);
    $stmt->close();
    $conn->close();
    exit();
}

$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Tạo link download public
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$downloadLink = $protocol . '://' . $host . '/download.php?hash=' . $file['hash'];

echo json_encode([
    'success' => true,
    'link' => $downloadLink,
    'fileName' => $file['name']
]);
