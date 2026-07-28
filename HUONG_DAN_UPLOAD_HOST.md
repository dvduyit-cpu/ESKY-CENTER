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

## Zalo

Điền `ZALO_APP_ID`, `ZALO_APP_SECRET` và đăng ký callback sau trong Zalo Developers:

```text
https://ten-mien-cua-ban/auth/zalo/callback
```

Người dùng cần đăng nhập bằng mật khẩu một lần để liên kết Zalo trong Cài đặt cá nhân.

## Reverb

Mẫu production mặc định dùng `BROADCAST_CONNECTION=null`, phù hợp shared hosting. Nếu dùng VPS và muốn thông báo tức thời, cấu hình Reverb theo `deploy/REVERB_UBUNTU.md` rồi đổi sang `BROADCAST_CONNECTION=reverb`.

## Sau khi chạy

- Đổi ngay mật khẩu admin mặc định.
- Xác nhận `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- Không để `DATABASE_KPI_MANAGER.sql`, `.env`, `vendor` hoặc source Laravel truy cập trực tiếp từ web.

## Tự động deploy từ GitHub

Workflow `.github/workflows/deploy.yml` chạy test trước, sau đó kết nối SSH đến host,
pull nhánh `master`, backup MySQL, chạy migration và tối ưu Laravel.

Trong GitHub, mở `Settings → Secrets and variables → Actions`, tạo các repository secrets:

- `SSH_HOST`: tên miền hoặc IP SSH của host.
- `SSH_PORT`: cổng SSH; thường là `22`.
- `SSH_USER`: tài khoản SSH.
- `SSH_PRIVATE_KEY`: private key dùng riêng cho GitHub Actions.
- `DEPLOY_PATH`: đường dẫn tuyệt đối tới dự án trên host, ví dụ `/home/account/esky`.

Trên host, clone repository một lần vào `DEPLOY_PATH`, tạo `.env`, cấu hình database
và bảo đảm lệnh `php`, `composer`, `mysqldump` có trong `PATH`. Sau đó mỗi lần push
lên nhánh `master`, GitHub sẽ tự chạy test và deploy. Nếu test, backup hoặc migration
lỗi, workflow dừng và không báo deploy thành công.
