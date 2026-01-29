<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();



// Lấy danh sách files từ database
$query = "SELECT * FROM files ORDER BY upload_date DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý File</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <header class="header">
            <h1>📁 Quản lý File System</h1>
            <div class="user-info">
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn btn-secondary">Đăng xuất</a>
            </div>
        </header>

        <div class="upload-section">
            <h2>📤 Upload Files</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📎</div>
                    <p class="upload-text">Kéo thả file vào đây hoặc click để chọn file</p>
                    <p class="upload-hint">Hỗ trợ upload nhiều file cùng lúc hoặc cả folder</p>
                    <input type="file" id="fileInput" name="files[]" multiple style="display: none;">
                    <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">📄 Chọn Files</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('folderInput').click()" style="margin-left: 10px;">📁 Chọn Folder</button>
                </div>

                <div id="fileList" class="file-list"></div>

                <button type="submit" id="uploadBtn" class="btn btn-success" style="display: none;">
                    <span id="uploadBtnText">Upload Files</span>
                    <span id="uploadProgress" style="display: none;"></span>
                </button>
            </form>
        </div>

        <div class="table-section">
            <h2>📋 Danh sách Files</h2>
            <div id="message" class="message"></div>

            <div class="table-responsive">
                <table class="file-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>ID</th>
                            <th>Hash</th>
                            <th>Tên File</th>
                            <th>Kích thước</th>
                            <th>Thư mục</th>
                            <th>Ngày upload</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="fileTableBody">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            $stt = 1;
                            while ($row = $result->fetch_assoc()) {
                                $fileSize = formatFileSize($row['size']);
                                $uploadDate = date('d/m/Y H:i', strtotime($row['upload_date']));
                                echo "<tr data-id='{$row['id']}'>
                                    <td>{$stt}</td>
                                    <td>{$row['id']}</td>
                                    <td><code class='hash'>" . substr($row['hash'], 0, 12) . "...</code></td>
                                    <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                    <td>{$fileSize}</td>
                                    <td><small>" . htmlspecialchars($row['dir']) . "</small></td>
                                    <td>{$uploadDate}</td>
                                    <td class='action-buttons'>
                                        <button onclick='downloadFile(\"{$row['hash']}\")' class='btn btn-sm btn-info' title='Download'>⬇️</button>
                                        <button onclick='getLink(\"{$row['hash']}\")' class='btn btn-sm btn-warning' title='Get Link'>🔗</button>
                                        <button onclick='getPath(\"{$row['hash']}\")' class='btn btn-sm btn-primary' title='Get Path'>🔗</button>
                                        <button onclick='deleteFile(\"{$row['hash']}\")' class='btn btn-sm btn-danger' title='Delete'>🗑️</button>
                                    </td>
                                </tr>";
                                $stt++;
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align: center; padding: 30px;'>Chưa có file nào được upload</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>

<?php
$conn->close();

// Hàm format kích thước file
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>