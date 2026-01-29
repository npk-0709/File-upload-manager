<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

// Hiển thị toàn bộ thông tin để debug
echo json_encode([
    'FILES' => $_FILES,
    'POST' => $_POST,
    'ini' => [
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
    ]
], JSON_PRETTY_PRINT);
