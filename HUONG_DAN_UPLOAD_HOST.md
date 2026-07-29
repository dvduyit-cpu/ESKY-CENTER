# Hướng dẫn upload lên host

## Yêu cầu

- PHP 8.2 trở lên.
- Extension: curl, dom, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, simplexml, xml, xmlreader, xmlwriter và zip.
- Document root của tên miền phải trỏ tới thư mục `public`.
- `storage` và `bootstrap/cache` phải có quyền ghi cho PHP.

## Triển khai

1. Upload mã nguồn. Không đặt `.env` hoặc file SQL bên trong thư mục public.
2. Chạy `composer install --no-dev --optimize-autoloader` trên host. Nếu không có SSH, upload cả thư mục `vendor` đã cài bằng đúng phiên bản PHP tương thích.
3. Tạo database rỗng, chọn database đó trong phpMyAdmin rồi import `database/host.sql`. File này không khóa cứng tên database nên phù hợp với shared hosting.
4. Sao chép `.env.production.example` thành `.env`, sau đó điền domain và thông tin database thật.
5. Nếu tạo `.env` mới và `APP_KEY` đang trống, chạy `php artisan key:generate`. Không đổi APP_KEY sau khi hệ thống đã có dữ liệu/session đang dùng.
6. Chạy:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

7. Kiểm tra `https://ten-mien-cua-ban/up`, sau đó mở trang đăng nhập.

## Reverb

Mẫu production mặc định dùng `BROADCAST_CONNECTION=null`, phù hợp shared hosting. Nếu dùng VPS và muốn thông báo tức thời, cấu hình Reverb theo `deploy/REVERB_UBUNTU.md` rồi đổi sang `BROADCAST_CONNECTION=reverb`.

## Sau khi chạy

- Đổi ngay mật khẩu admin mặc định.
- Xác nhận `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- Không để `DATABASE_KPI_MANAGER.sql`, `.env`, `vendor` hoặc source Laravel truy cập trực tiếp từ web.
