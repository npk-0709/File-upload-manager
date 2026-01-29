// Drag and drop functionality
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const folderInput = document.getElementById('folderInput');
const fileList = document.getElementById('fileList');
const uploadBtn = document.getElementById('uploadBtn');
const uploadForm = document.getElementById('uploadForm');
let selectedFiles = [];
let filesWithPaths = [];

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight drop area when dragging over
['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.classList.remove('dragover');
    }, false);
});

// Handle dropped files
uploadArea.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}, false);

// Handle file input change
fileInput.addEventListener('change', (e) => {
    handleFiles(e.target.files);
});

// Handle folder input change
folderInput.addEventListener('change', (e) => {
    handleFiles(e.target.files, true);
});

// Handle files
function handleFiles(files, isFolder = false) {
    selectedFiles = Array.from(files);
    filesWithPaths = [];

    // Lưu thông tin file kèm path nếu upload folder
    selectedFiles.forEach(file => {
        filesWithPaths.push({
            file: file,
            relativePath: file.webkitRelativePath || file.name
        });
    });

    displayFileList(isFolder);
    uploadBtn.style.display = selectedFiles.length > 0 ? 'block' : 'none';
}

// Display selected files
function displayFileList(isFolder = false) {
    fileList.innerHTML = '';

    if (isFolder && filesWithPaths.length > 0) {
        // Hiển thị tổng quan khi upload folder
        const folderName = filesWithPaths[0].relativePath.split('/')[0];
        const summary = document.createElement('div');
        summary.className = 'file-item';
        summary.style.backgroundColor = '#e8f5e9';
        summary.innerHTML = `
            <div class="file-item-info">
                <span class="file-item-name">📁 <strong>${folderName}</strong> (${selectedFiles.length} files)</span>
                <span class="file-item-size">${formatFileSize(selectedFiles.reduce((acc, f) => acc + f.size, 0))}</span>
            </div>
            <button type="button" class="file-item-remove" onclick="clearAllFiles()">Xóa tất cả</button>
        `;
        fileList.appendChild(summary);
    }

    filesWithPaths.forEach((item, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        const displayName = item.relativePath || item.file.name;
        fileItem.innerHTML = `
            <div class="file-item-info">
                <span class="file-item-name">📄 ${displayName}</span>
                <span class="file-item-size">${formatFileSize(item.file.size)}</span>
            </div>
            <button type="button" class="file-item-remove" onclick="removeFile(${index})">Xóa</button>
        `;
        fileList.appendChild(fileItem);
    });
}

// Remove file from list
function removeFile(index) {
    selectedFiles.splice(index, 1);
    filesWithPaths.splice(index, 1);
    displayFileList();
    uploadBtn.style.display = selectedFiles.length > 0 ? 'block' : 'none';
}

// Clear all files
function clearAllFiles() {
    selectedFiles = [];
    filesWithPaths = [];
    fileList.innerHTML = '';
    uploadBtn.style.display = 'none';
    fileInput.value = '';
    folderInput.value = '';
}

// Format file size
function formatFileSize(bytes) {
    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    } else {
        return bytes + ' B';
    }
}

// Upload form submission
uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (selectedFiles.length === 0) {
        showMessage('Vui lòng chọn file để upload!', 'error');
        return;
    }

    const formData = new FormData();

    // Append files theo đúng format PHP mong đợi, kèm relative path
    filesWithPaths.forEach((item, index) => {
        formData.append('files[]', item.file, item.file.name);
        formData.append('paths[]', item.relativePath);
    });

    // Disable button and show loading
    const uploadBtnText = document.getElementById('uploadBtnText');
    const uploadProgress = document.getElementById('uploadProgress');
    uploadBtn.disabled = true;
    uploadBtnText.style.display = 'none';
    uploadProgress.style.display = 'inline-block';
    uploadProgress.innerHTML = '<span class="loading"></span> Đang upload...';

    // Thêm action vào formData
    formData.append('action', 'upload');

    try {
        const response = await fetch('action.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            selectedFiles = [];
            fileList.innerHTML = '';
            uploadBtn.style.display = 'none';
            fileInput.value = '';

            // Reload page sau 1.5s
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Lỗi kết nối server!', 'error');
    } finally {
        uploadBtn.disabled = false;
        uploadBtnText.style.display = 'inline';
        uploadProgress.style.display = 'none';
    }
});

// Show message
function showMessage(text, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = text;
    messageDiv.className = 'message ' + type;
    messageDiv.style.display = 'block';

    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// Download file
function downloadFile(hash) {
    window.location.href = 'action.php?action=download&hash=' + hash;
}

// Get download link
async function getLink(hash) {
    try {
        const formData = new FormData();
        formData.append('action', 'getlink');
        formData.append('hash', hash);

        const response = await fetch('action.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Copy link to clipboard
            navigator.clipboard.writeText(result.link).then(() => {
                showMessage('Link đã được copy: ' + result.link, 'success');
            }).catch(() => {
                // Fallback: show link in alert
                prompt('Link download:', result.link);
            });
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Lỗi kết nối server!', 'error');
    }
}

// Get Path
async function getPath(hash) {
    try {
        const formData = new FormData();
        formData.append('action', 'getpath');
        formData.append('hash', hash);

        const response = await fetch('action.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Copy link to clipboard
            navigator.clipboard.writeText(result.path).then(() => {
                showMessage('Path đã được copy: ' + result.path, 'success');
            }).catch(() => {
                // Fallback: show link in alert
                prompt('Path download:', result.path);
            });
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Lỗi kết nối server!', 'error');
    }
}

// Delete file
async function deleteFile(hash) {
    if (!confirm('Bạn có chắc muốn xóa file này?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('hash', hash);

        const response = await fetch('action.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status) {
            showMessage(result.message, 'success');

            // Remove row from table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.remove();
            }

            // Update STT
            updateSTT();
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Lỗi kết nối server!', 'error');
    }
}

// Update STT after delete
function updateSTT() {
    const rows = document.querySelectorAll('#fileTableBody tr');
    rows.forEach((row, index) => {
        const sttCell = row.querySelector('td:first-child');
        if (sttCell) {
            sttCell.textContent = index + 1;
        }
    });
}
