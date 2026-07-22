# PHẦN MỀM QUẢN LÝ CHỈ TIÊU KPI

## Công nghệ
- Laravel 12, PHP 8.2 trở lên
- MySQL 8 trở lên
- Blade + Bootstrap 5, không cần Node.js hoặc npm
- PhpSpreadsheet để nhập và xuất Excel

## Chức năng chính
- Đăng nhập, đăng xuất, ghi nhớ đăng nhập.
- Tài khoản Admin, Lãnh đạo, Giáo viên, Nhân viên.
- Khóa/mở, xóa mềm, khôi phục và đặt lại mật khẩu tài khoản.
- Quyền Xem, Thêm, Sửa, Xóa, Xuất file theo từng module.
- Admin cấp quyền riêng cho từng tài khoản; URL không có quyền trả lỗi 403.
- Quản lý nhân sự và cộng tác viên.
- Kế hoạch KPI theo Năm → Quý → Tháng.
- Giao chỉ tiêu bắt buộc hoặc không bắt buộc theo từng khóa học.
- Nhập chỉ tiêu bằng Excel theo tháng hoặc quý.
- Nhập kết quả bằng Excel theo tháng hoặc quý.
- Tính cộng dồn theo thành viên, khóa học, tháng, quý và năm.
- Khóa học B1 mặc định: đủ 2 lượt mới tính 1 KPI.
- Phần vượt chỉ được tính sau khi trừ chỉ tiêu bắt buộc.
- Tính thanh toán vượt KPI và thanh toán cộng tác viên.
- Báo cáo và xuất Excel theo tháng, quý, năm đang chọn.
- Nhật ký đăng nhập và thao tác quản trị.

## Cài đặt trên Windows/XAMPP

### Bước 1: Cài thư viện Laravel
Chạy:

```bat
CAI_DAT.bat
```

Hoặc chạy thủ công:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

### Bước 2: Import database riêng
Database KHÔNG chạy bằng migration. Import trực tiếp file:

```text
DATABASE_KPI_MANAGER.sql
```

Cách 1: Mở phpMyAdmin → Import → chọn file SQL.

Cách 2: Chạy:

```bat
IMPORT_DATABASE.bat
```

Database mặc định:

```text
Tên database: kpi_laravel
Tài khoản MySQL: root
Mật khẩu: để trống
```

Nếu MySQL có mật khẩu, sửa `DB_PASSWORD` trong `.env` và biến `MYSQL_PASSWORD` trong `IMPORT_DATABASE.bat`.

### Bước 3: Chạy phần mềm

```bat
CHAY_PHAN_MEM.bat
```

Mở trình duyệt:

```text
http://127.0.0.1:8000
```

## Tài khoản mặc định

```text
Email: admin@kpi.local
Mật khẩu: 12345678
```

Nên đổi mật khẩu ngay sau khi đăng nhập.

## Quy tắc B1
Khóa học `Chứng nhận B1` được cấu hình:

```text
conversion_quantity = 2
conversion_kpi = 1
conversion_mode = full_group
```

Kết quả:
- 1 lượt B1 = 0 KPI
- 2 lượt B1 = 1 KPI
- 3 lượt B1 = 1 KPI
- 4 lượt B1 = 2 KPI

Việc quy đổi được cộng dồn theo kỳ báo cáo được chọn.

## Quy tắc ưu tiên chỉ tiêu
- Báo cáo tháng: dùng chỉ tiêu tháng.
- Báo cáo quý: nếu có chỉ tiêu tháng trong quý thì cộng 3 tháng; nếu không có thì dùng chỉ tiêu quý.
- Báo cáo năm: cộng chỉ tiêu tháng hoặc quý; nếu không có thì dùng chỉ tiêu năm.
