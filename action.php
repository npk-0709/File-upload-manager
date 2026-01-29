<?php
require_once 'config.php';

// Lấy action từ request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'download':
        handleDownload();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'getlink':
        handleGetLink();
        break;
    case 'getpath':
        handleGetPath();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ!']);
        exit();
}

// ===================== UPLOAD =====================
function handleUpload()
{
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

    // Lấy paths nếu có (upload folder)
    $paths = isset($_POST['paths']) ? $_POST['paths'] : [];

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

        // Lấy relative path nếu có (từ folder upload)
        $relativePath = isset($paths[$i]) ? $paths[$i] : $fileName;

        // Tạo cấu trúc thư mục dựa trên relative path
        $pathInfo = pathinfo($relativePath);
        $fileExtension = $pathInfo['extension'] ?? '';
        $baseFileName = $pathInfo['filename'];
        $subDir = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '';

        // Tạo tên file unique
        $newFileName = $baseFileName . '_' . substr($fileHash, 0, 8) . ($fileExtension ? '.' . $fileExtension : '');

        // Tạo đường dẫn đầy đủ với cấu trúc thư mục
        $uploadDir = rtrim(UPLOAD_DIR, '/') . '/' . $subDir;
        $fullFilePath = $uploadDir . $newFileName;

        // Tạo thư mục con nếu chưa tồn tại
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $errors[] = "Không thể tạo thư mục: $uploadDir";
                continue;
            }
        }

        if (!is_writable($uploadDir)) {
            $errors[] = "Không có quyền ghi vào: $uploadDir";
            continue;
        }

        // Di chuyển file vào thư mục resources
        if (move_uploaded_file($fileTmpName, $fullFilePath)) {
            // Lưu thông tin vào database
            $stmt = $conn->prepare("INSERT INTO files (hash, name, size, dir) VALUES (?, ?, ?, ?)");
            $relativeDir = 'resources/' . $subDir . $newFileName;
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
}

// ===================== DOWNLOAD =====================
function handleDownload()
{
    // Lấy hash file cần download
    $hash = $_GET['hash'] ?? 0;

    if (empty($hash)) {
        die('Hash file không hợp lệ!');
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
}

// ===================== DELETE =====================
function handleDelete()
{
    requireLogin();

    header('Content-Type: application/json');

    // Lấy hash file cần xóa
    $hash = $_POST['hash'] ?? 0;

    if (empty($hash)) {
        echo json_encode(['status' => false, 'message' => 'Hash file không hợp lệ!']);
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
}

// ===================== GET LINK =====================
function handleGetLink()
{
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
    $downloadLink = $protocol . '://' . $host . '/action.php?action=download&hash=' . $file['hash'];

    echo json_encode([
        'success' => true,
        'link' => $downloadLink,
        'fileName' => $file['name']
    ]);
}

// ===================== GET PATH =====================
function handleGetPath()
{
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
    $downloadLink = $protocol . '://' . $host . '/' . $file['dir'];

    echo json_encode([
        'success' => true,
        'path' => $downloadLink,
        'fileName' => $file['name']
    ]);
}
