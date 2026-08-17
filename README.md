# TaskWork

Ứng dụng quản lý project và công việc cho team IT, xây dựng trên Laravel 13.

## Chạy local

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
composer run dev
```

Tài khoản mẫu sau khi seed: `admin@taskwork.local` / `ChangeMe123!`.

Document root khi triển khai trên aaPanel phải trỏ đến thư mục `public`.
