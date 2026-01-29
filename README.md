# 📁 File Management System

Hệ thống quản lý và lưu trữ file đơn giản với PHP + MySQL.

---

## 📋 Tính năng

- 🔐 Đăng nhập bảo mật (xác thực bằng chuỗi text, không dùng SQL)
- 📤 Upload nhiều file hoặc cả folder cùng lúc
- 🎯 Kéo thả file (Drag & Drop)
- 📊 Bảng quản lý file (ID, Hash, Name, Size, Directory, Date)
- ⬇️ Download file
- 🔗 Lấy link download public
- 📂 Lấy đường dẫn trực tiếp (path)
- 🗑️ Xóa file
- 💾 Lưu trữ metadata trong MySQL

---

## 🚀 Cài đặt

### 1. Yêu cầu hệ thống

| Yêu cầu | Phiên bản |
|---------|-----------|
| PHP | 7.4+ |
| MySQL | 5.7+ |
| Web Server | Apache / Nginx |

### 2. Tạo Database

Import file `database.sql` vào MySQL:

```bash
mysql -u root -p < database.sql
```

Hoặc dùng phpMyAdmin để import.

### 3. Cấu hình Database

Mở file `config.php` và chỉnh sửa:

```php
// Thông tin database
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');

// Thông tin đăng nhập admin
define('LOGIN_USERNAME', 'admin');
define('LOGIN_PASSWORD', 'admin123');

// Thư mục lưu file upload
define('UPLOAD_DIR', __DIR__ . '/resources/');
```

### 4. Phân quyền thư mục

**Linux/macOS:**
```bash
chmod -R 755 resources/
chown -R www-data:www-data resources/
```

**Windows:** Đảm bảo IIS_IUSRS hoặc user chạy PHP có quyền ghi vào thư mục `resources/`.

### 5. Truy cập

```
http://your-domain.com/login.html
```

---

## ⚠️ Cấu hình Upload Size (Quan trọng!)

Mặc định PHP giới hạn kích thước upload khá nhỏ. Để upload file lớn, bạn cần chỉnh sửa các giá trị sau:

### Các tham số cần chỉnh

| Tham số | Mô tả | Gợi ý |
|---------|-------|-------|
| `upload_max_filesize` | Kích thước tối đa 1 file | `500M` hoặc `1G` |
| `post_max_size` | Kích thước tối đa POST request | Nên lớn hơn `upload_max_filesize` |
| `max_execution_time` | Thời gian chạy tối đa (giây) | `300` hoặc `600` |
| `max_input_time` | Thời gian nhận input tối đa | `300` hoặc `600` |
| `memory_limit` | Bộ nhớ tối đa cho PHP | `256M` hoặc `512M` |

### Cách 1: Chỉnh trong php.ini (Khuyến nghị)

Tìm file `php.ini`:
```bash
# Linux
php --ini

# Hoặc tạo file phpinfo
<?php phpinfo(); ?>
```

Chỉnh sửa:
```ini
upload_max_filesize = 500M
post_max_size = 512M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

**Restart web server sau khi chỉnh:**
```bash
# Apache
sudo systemctl restart apache2

# Nginx + PHP-FPM
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

### Cách 2: Chỉnh trong .htaccess (Apache)

Tạo hoặc chỉnh file `.htaccess` trong thư mục gốc:

```apache
php_value upload_max_filesize 500M
php_value post_max_size 512M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
```

> ⚠️ **Lưu ý:** Cách này chỉ hoạt động nếu server cho phép override (AllowOverride All)

### Cách 3: Chỉnh trong file PHP

Thêm vào đầu file `action.php` hoặc `config.php`:

```php
ini_set('upload_max_filesize', '500M');
ini_set('post_max_size', '512M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');
```

> ⚠️ **Lưu ý:** `upload_max_filesize` và `post_max_size` thường không thể thay đổi bằng `ini_set()` vì lý do bảo mật. Nên dùng Cách 1 hoặc Cách 2.

### Cách 4: Nginx + PHP-FPM

Chỉnh file config Nginx:
```nginx
server {
    client_max_body_size 500M;
    
    location ~ \.php$ {
        fastcgi_read_timeout 300;
    }
}
```

Chỉnh file PHP-FPM pool (thường ở `/etc/php/8.x/fpm/pool.d/www.conf`):
```ini
php_admin_value[upload_max_filesize] = 500M
php_admin_value[post_max_size] = 512M
```

---

## 📁 Cấu trúc dự án

```
├── config.php          # Cấu hình database & đăng nhập
├── database.sql        # Script tạo database
├── action.php          # API xử lý tất cả actions (upload, download, delete, getlink, getpath)
├── login.html          # Trang đăng nhập
├── login.php           # Xử lý đăng nhập (AJAX)
├── logout.php          # Đăng xuất
├── home.php            # Trang chính quản lý file
├── index.php           # Redirect đến login/home
├── style.css           # CSS styling
├── script.js           # JavaScript (drag & drop, AJAX)
└── resources/          # Thư mục lưu trữ file upload
    └── files/          # Thư mục con chứa files
```

---

## 🔌 API Endpoints

Tất cả actions được xử lý qua file `action.php` với param `action`:

| Action | Method | Params | Mô tả |
|--------|--------|--------|-------|
| `upload` | POST | `files[]`, `paths[]` | Upload files |
| `download` | GET | `hash` | Download file |
| `delete` | POST | `hash` | Xóa file |
| `getlink` | POST | `hash` | Lấy link download |
| `getpath` | POST | `hash` | Lấy đường dẫn trực tiếp |

### Ví dụ sử dụng

**Upload file:**
```javascript
const formData = new FormData();
formData.append('action', 'upload');
formData.append('files[]', file);
formData.append('paths[]', file.name);

fetch('action.php', { method: 'POST', body: formData });
```

**Download file:**
```
GET action.php?action=download&hash=abc123...
```

**Xóa file:**
```javascript
const formData = new FormData();
formData.append('action', 'delete');
formData.append('hash', 'abc123...');

fetch('action.php', { method: 'POST', body: formData });
```

---

## 🔒 Bảo mật

- ✅ Session-based authentication
- ✅ SHA-256 file hash
- ✅ Prepared statements (chống SQL Injection)
- ✅ HTML escaping (chống XSS)
- ✅ Kiểm tra quyền truy cập trước mỗi action

---

## 🐛 Xử lý lỗi thường gặp

### 1. Không upload được file lớn
- Kiểm tra `post_max_size` và `upload_max_filesize` trong php.ini
- Xem phần **Cấu hình Upload Size** ở trên

### 2. Lỗi "Permission denied"
- Kiểm tra quyền ghi thư mục `resources/`
- Chạy: `chmod -R 755 resources/`

### 3. Lỗi kết nối database
- Kiểm tra thông tin trong `config.php`
- Đảm bảo MySQL đang chạy
- Kiểm tra database đã được tạo

### 4. Upload timeout
- Tăng `max_execution_time` và `max_input_time`

---

## 📱 Responsive

Giao diện tự động điều chỉnh cho:
- 🖥️ Desktop (> 1024px)
- 📱 Tablet (768px - 1024px)
- 📱 Mobile (< 768px)

---

## 📄 License

MIT License - Miễn phí sử dụng

---

**Version:** 2.0.0  
**Cập nhật:** Tháng 1/2026
