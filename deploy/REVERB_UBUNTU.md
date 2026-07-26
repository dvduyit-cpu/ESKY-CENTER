# Triển khai thông báo WebSocket trên Ubuntu

## 1. Biến môi trường

Thay tên miền và tạo ba chuỗi bí mật riêng:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=esky-production
REVERB_APP_KEY=mot-khoa-ngau-nhien
REVERB_APP_SECRET=mot-bi-mat-dai-ngau-nhien
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_PUBLIC_HOST=esky.example.com
REVERB_PUBLIC_PORT=443
REVERB_PUBLIC_SCHEME=https
REVERB_ALLOWED_ORIGINS=https://esky.example.com
```

Laravel gửi sự kiện tới Reverb nội bộ; trình duyệt kết nối qua Nginx và SSL. Không dùng các giá trị local trên production.

## 2. Cài ứng dụng

```bash
cd /var/www/esky
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

PHP trên máy chủ cần các extension hiện tại của dự án, bao gồm zip.

## 3. Nginx

Chép nội dung deploy/nginx/reverb-location.conf.example vào khối HTTPS server của website:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 4. Supervisor

```bash
sudo apt install supervisor
sudo cp deploy/supervisor/esky-reverb.conf.example /etc/supervisor/conf.d/esky-reverb.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start esky-reverb
sudo supervisorctl status esky-reverb
```

Điều chỉnh đường dẫn ứng dụng, đường dẫn PHP và user nếu máy chủ sử dụng cấu trúc khác.

## 5. Kiểm tra

```bash
sudo supervisorctl status esky-reverb
ss -lntp | grep 8080
tail -f storage/logs/reverb.log
```

Mở hai tài khoản trên hai trình duyệt, giao một công việc từ tài khoản thứ nhất và xác nhận chuông tài khoản thứ hai cập nhật ngay. Nếu Reverb dừng, thao tác vẫn được lưu; Laravel ghi cảnh báo và chuông đồng bộ lại khi kết nối phục hồi hoặc người dùng quay lại tab.
