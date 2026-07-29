# Khắc phục 403 trên shared hosting

## Cấu hình khuyến nghị

Document root của domain phải trỏ trực tiếp tới thư mục `public`.

Nếu hosting bắt buộc domain trỏ vào thư mục gốc dự án, giữ nguyên cả hai file:

```text
.htaccess
public/.htaccess
```

File `.htaccess` ở thư mục gốc chuyển request vào `public` và chặn truy cập trực
tiếp tới `.env`, file SQL, source Laravel, `storage` và `vendor`.

## Sau mỗi lần cập nhật

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Thiết lập quyền nếu host dùng Linux:

```bash
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
```

Kiểm tra lần lượt:

```text
https://ten-mien/up
https://ten-mien/login
```

Nếu `/up` cũng trả 403 và không hiển thị giao diện lỗi Laravel, 403 đến từ
Apache/Nginx hoặc quyền file. Nếu chỉ một chức năng sau đăng nhập trả giao diện
403 của phần mềm, cần cấp lại quyền module cho vai trò hoặc tài khoản.
