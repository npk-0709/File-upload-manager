<?php
require_once 'config.php';
requireLogin();

// Lấy ID file cần download
$hash = $_GET['hash'] ?? 0;

if (empty($hash)) {
    die('ID file không hợp lệ!');
}

$conn = getDBConnection();

// Lấy thông tin file từ database
$stmt = $conn->prepare("SELECT * FROM files WHERE hash = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('File không tồn tại!');
}

$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Đường dẫn file
$filePath = __DIR__ . '/' . $file['dir'];

if (!file_exists($filePath)) {
    die('File không tồn tại trên hệ thống!');
}

// Set headers để download file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Xuất file
readfile($filePath);
exit();
?>
