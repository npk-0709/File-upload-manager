<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Kiểm tra xem có vượt quá post_max_size không
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $postMaxSize = ini_get('post_max_size');
    $uploadMaxSize = ini_get('upload_max_filesize');
    echo json_encode([
        'success' => false, 
        'message' => 'File quá lớn! Vượt quá giới hạn ' . $postMaxSize . '. Vui lòng liên hệ admin để tăng giới hạn hoặc upload file nhỏ hơn.',
        'size_sent' => $_SERVER['CONTENT_LENGTH'],
        'post_max_size' => $postMaxSize,
        'upload_max_filesize' => $uploadMaxSize
    ]);
    exit();
}

// Kiểm tra có file được upload không
if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'Không có file nào được chọn!']);
    exit();
}

// Tạo thư mục resources nếu chưa tồn tại
if (!file_exists(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo thư mục upload! Path: ' . UPLOAD_DIR]);
        exit();
    }
}

// Kiểm tra quyền ghi
if (!is_writable(UPLOAD_DIR)) {
    echo json_encode(['success' => false, 'message' => 'Thư mục upload không có quyền ghi! Path: ' . UPLOAD_DIR]);
    exit();
}

$conn = getDBConnection();
$uploadedFiles = [];
$errors = [];

// Xử lý từng file
$fileCount = count($_FILES['files']['name']);

for ($i = 0; $i < $fileCount; $i++) {
    $fileName = $_FILES['files']['name'][$i];
    $fileTmpName = $_FILES['files']['tmp_name'][$i];
    $fileSize = $_FILES['files']['size'][$i];
    $fileError = $_FILES['files']['error'][$i];

    // Kiểm tra lỗi upload
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "Lỗi upload file: $fileName";
        continue;
    }

    // Tạo hash cho file
    $fileHash = hash_file('sha256', $fileTmpName);

    // Tạo tên file unique để tránh trùng
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
    $newFileName = $baseFileName . '_' . substr($fileHash, 0, 8) . '.' . $fileExtension;
    $filePath = UPLOAD_DIR . $newFileName;

    // Kiểm tra và tạo đường dẫn trước
    $uploadDir = rtrim(UPLOAD_DIR, '/') . '/';
    $fullFilePath = $uploadDir . $newFileName;

    // Debug info
    if (!is_dir($uploadDir)) {
        $errors[] = "Thư mục không tồn tại: $uploadDir";
        continue;
    }

    if (!is_writable($uploadDir)) {
        $errors[] = "Không có quyền ghi vào: $uploadDir";
        continue;
    }

    // Di chuyển file vào thư mục resources
    if (move_uploaded_file($fileTmpName, $fullFilePath)) {
        // Lưu thông tin vào database
        $stmt = $conn->prepare("INSERT INTO files (hash, name, size, dir) VALUES (?, ?, ?, ?)");
        $relativeDir = 'resources/' . $newFileName;
        $stmt->bind_param("ssis", $fileHash, $fileName, $fileSize, $relativeDir);

        if ($stmt->execute()) {
            $uploadedFiles[] = [
                'id' => $stmt->insert_id,
                'name' => $fileName,
                'hash' => $fileHash,
                'size' => $fileSize,
                'dir' => $relativeDir
            ];
        } else {
            $errors[] = "Lỗi lưu database cho file: $fileName - " . $stmt->error;
            if (file_exists($fullFilePath)) {
                unlink($fullFilePath); // Xóa file đã upload nếu lưu DB thất bại
            }
        }

        $stmt->close();
    } else {
        $uploadError = error_get_last();
        $errors[] = "Lỗi di chuyển file: $fileName - " . ($uploadError ? $uploadError['message'] : 'Unknown error');
    }
}

$conn->close();

// Trả về kết quả
if (count($uploadedFiles) > 0) {
    $message = count($uploadedFiles) . ' file đã được upload thành công!';
    if (count($errors) > 0) {
        $message .= ' (' . count($errors) . ' file lỗi)';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'files' => $uploadedFiles,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể upload file!',
        'errors' => $errors
    ]);
}
