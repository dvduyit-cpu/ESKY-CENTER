# Quy trình database local và host

## Nguyên tắc

- Database trên host là nguồn dữ liệu thật duy nhất.
- Không import toàn bộ database local để ghi đè database trên host.
- Mọi thay đổi cấu trúc bảng phải được tạo bằng migration mới.
- Luôn backup database host trước khi cập nhật code hoặc chạy migration.
- Bản sao dữ liệu thật tải về local phải được bảo quản, không đưa vào Git và không chia sẻ công khai.

## Đưa hệ thống mới lên host

1. Tạo database mới trên host.
2. Import database nền chỉ gồm cấu trúc và dữ liệu hệ thống cần thiết.
3. Kiểm tra chỉ còn tài khoản Admin, vai trò, module, quyền và cấu hình mặc định.
4. Cập nhật `.env` của host.
5. Chạy:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Không dùng database local đã có dữ liệu thử nghiệm làm database production.

## Cập nhật phần mềm

1. Backup database host bằng cPanel/phpMyAdmin hoặc `mysqldump`.
2. Tải bản backup về nơi an toàn.
3. Upload source code mới lên host.
4. Chạy:

```bash
php artisan migrate --force
php artisan optimize:clear
```

5. Kiểm tra đăng nhập, dashboard và các chức năng vừa sửa.

Không export database local rồi import ngược lên host sau mỗi lần sửa code.

## Lấy dữ liệu host về local để kiểm thử

1. Export database host thành file `.sql`.
2. Đóng phần mềm local.
3. Kéo file `.sql` thả vào `PHUC_HOI_DATABASE.bat`.
4. Nhập chính xác `PHUC HOI` khi được hỏi.
5. Script tự backup database local hiện tại trước khi restore.

Không chạy `PHUC_HOI_DATABASE.bat` trên host.

## Backup database local

Chạy:

```bat
BACKUP_DATABASE.bat
```

File được lưu tại:

```text
storage/backups/kpi_laravel_yyyyMMdd_HHmmss.sql
```

Mỗi lần backup tạo một file mới, không ghi đè bản cũ.

## Backup database host

### cPanel/phpMyAdmin

Chọn database, sau đó chọn `Export` → `Quick` → `SQL`.

### SSH

```bash
mysqldump -u DB_USER -p --single-transaction DB_NAME > backup_$(date +%Y%m%d_%H%M%S).sql
```

Không đặt file backup trong thư mục `public` hoặc vị trí có thể tải trực tiếp từ website.
