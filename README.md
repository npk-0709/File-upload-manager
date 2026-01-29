# Hệ Thống Quản Lý File

Dự án quản lý file đơn giản với PHP và MySQL.

## 📋 Tính năng

- 🔐 Đăng nhập bảo mật (không dùng SQL, kiểm tra chuỗi text)
- 📤 Upload nhiều file cùng lúc
- 🎯 Kéo thả file (drag & drop)
- 📊 Bảng quản lý file với đầy đủ thông tin (ID, Hash, Name, Size, Directory)
- ⬇️ Download file
- 🔗 Lấy link download public
- 🗑️ Xóa file
- 💾 Lưu trữ thông tin file trong MySQL database

## 🚀 Cài đặt

### 1. Yêu cầu hệ thống
- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Apache/Nginx web server

### 2. Cài đặt database

Import file `database.sql` vào MySQL:

```bash
mysql -u root -p < database.sql
```

Hoặc sử dụng phpMyAdmin để import file SQL.

### 3. Cấu hình

Mở file `config.php` và chỉnh sửa thông tin kết nối database:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'file_manager');
```

Thay đổi thông tin đăng nhập (nếu cần):

```php
define('LOGIN_USERNAME', 'admin');
define('LOGIN_PASSWORD', 'admin123');
```

### 4. Phân quyền thư mục

Đảm bảo thư mục `resources/` có quyền ghi:

```bash
chmod 755 resources/
```

### 5. Chạy ứng dụng

Mở trình duyệt và truy cập:
```
http://localhost/login.html
```

## 🔑 Thông tin đăng nhập mặc định

- **Username:** admin
- **Password:** admin123

## 📁 Cấu trúc dự án

```
├── config.php          # Cấu hình database và đăng nhập
├── database.sql        # Script tạo database
├── login.html          # Trang đăng nhập
├── login.php           # Xử lý đăng nhập (AJAX)
├── logout.php          # Đăng xuất
├── home.php            # Trang chính quản lý file
├── upload.php          # Xử lý upload file
├── download.php        # Xử lý download file
├── delete.php          # Xử lý xóa file
├── getlink.php         # Lấy link download
├── style.css           # CSS styling hiện đại
├── script.js           # JavaScript cho drag & drop, AJAX
└── resources/          # Thư mục lưu trữ file upload
```

## 🎨 Giao diện

- Thiết kế hiện đại, responsive
- Gradient background đẹp mắt
- Smooth animations
- Hỗ trợ mobile-friendly
- Dark/Light elements

## 🛠️ Công nghệ sử dụng

- **Backend:** PHP (mysqli)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **AJAX:** XMLHttpRequest & Fetch API

## 📝 Chức năng chi tiết

### Đăng nhập
- Form đăng nhập với AJAX
- Kiểm tra so sánh 2 chuỗi text (username & password)
- Không sử dụng SQL để xác thực
- Session management

### Upload File
- Hỗ trợ drag & drop
- Upload nhiều file cùng lúc
- Preview danh sách file trước khi upload
- Progress indicator
- Tự động tạo hash SHA-256 cho mỗi file
- Lưu thông tin vào database

### Quản lý File
- Hiển thị bảng với các thông tin:
  - STT (số thứ tự)
  - ID
  - Hash (SHA-256 - 12 ký tự đầu)
  - Tên file
  - Kích thước (tự động format KB/MB/GB)
  - Đường dẫn
  - Ngày upload
- Actions:
  - ⬇️ Download file
  - 🔗 Get link download (copy to clipboard)
  - 🗑️ Delete file

## 🔒 Bảo mật

- Session-based authentication
- File hash để tránh trùng lặp
- Prepared statements cho MySQL (chống SQL injection)
- HTML escaping (chống XSS)

## 📱 Responsive Design

Giao diện tự động điều chỉnh cho:
- Desktop
- Tablet
- Mobile

## ⚙️ Tùy chỉnh

### Thay đổi thư mục upload

Trong `config.php`:
```php
define('UPLOAD_DIR', __DIR__ . '/your-folder/');
```

### Giới hạn kích thước file

Chỉnh sửa trong `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
```

## 🐛 Xử lý lỗi

- Hiển thị thông báo lỗi rõ ràng
- Log errors
- Graceful error handling

## 📄 License

MIT License - Free to use

## 👨‍💻 Hỗ trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
1. Database đã được tạo chưa
2. Thông tin kết nối database trong config.php
3. Quyền ghi thư mục resources/
4. PHP extensions: mysqli, fileinfo

---

**Phát triển bởi:** File Management System
**Version:** 1.0.0
**Ngày:** 2026
