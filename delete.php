<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

// Lấy ID file cần xóa
$hash = $_POST['hash'] ?? 0;

if (empty($hash)) {
    echo json_encode(['status' => false, 'message' => 'hash file không hợp lệ!']);
    exit();
}

$conn = getDBConnection();

// Lấy thông tin file từ database
$stmt = $conn->prepare("SELECT * FROM files WHERE hash = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => false, 'message' => 'File không tồn tại!']);
    $stmt->close();
    $conn->close();
    exit();
}

$file = $result->fetch_assoc();
$stmt->close();

// Xóa file vật lý
$filePath = __DIR__ . '/' . $file['dir'];
if (file_exists($filePath)) {
    unlink($filePath);
}

// Xóa record trong database
$stmt = $conn->prepare("DELETE FROM files WHERE hash = ?");
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo json_encode([
        'status' => true,
        'message' => 'File đã được xóa thành công!'
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Lỗi xóa file từ database!'
    ]);
}

$stmt->close();
$conn->close();
