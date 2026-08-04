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

## Cập nhật host đã có dữ liệu

Phần này dùng cho website đang vận hành. Việc cập nhật mã nguồn phải giữ nguyên database, cấu hình và các tệp người dùng đã tải lên.

1. Sao lưu database và thư mục `public/uploads` trước khi cập nhật.
2. Chỉ upload/đồng bộ mã nguồn mới; **không ghi đè tệp `.env` trên host**.
3. **Không import lại** `database/host.sql`, `database/demo.sql` hoặc các tệp SQL sao lưu. Những tệp này chỉ dùng khi cài mới hoặc khôi phục có chủ đích.
4. Giữ nguyên thư mục `public/uploads/branding` để logo tùy chỉnh không bị mất khi thay mã nguồn.
5. Chạy các lệnh cập nhật an toàn:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

`php artisan migrate --force` chỉ chạy các migration chưa có. Không dùng `migrate:fresh`, `migrate:refresh`, `db:wipe` hoặc chạy seeder trên host đang có dữ liệu.

## Reverb

Mẫu production mặc định dùng `BROADCAST_CONNECTION=null`, phù hợp shared hosting. Nếu dùng VPS và muốn thông báo tức thời, cấu hình Reverb theo `deploy/REVERB_UBUNTU.md` rồi đổi sang `BROADCAST_CONNECTION=reverb`.

## Sau khi chạy

- Đổi ngay mật khẩu admin mặc định.
- Xác nhận `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- Không để `DATABASE_KPI_MANAGER.sql`, `.env`, `vendor` hoặc source Laravel truy cập trực tiếp từ web.
