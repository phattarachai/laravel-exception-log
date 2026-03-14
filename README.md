# Laravel Exception Log

บันทึก Exception ลงฐานข้อมูล พร้อมระบบแจ้งเตือนผ่านอีเมล

## ความสามารถ

- **บันทึก Exception** — ดักจับทุก Exception ที่เกิดขึ้นในแอป Laravel แล้วบันทึกลง database โดยไม่ต้องแก้โค้ดใดๆ
- **ระบบ Fingerprint Exception** — Exception เดียวกัน (class + file + line เดียวกัน) จะไม่สร้าง record ซ้ำ แต่จะนับจำนวน Exception ที่เกิดขึ้น
- **แจ้งเตือนผ่านอีเมล** — ส่งอีเมลแจ้งเตือนเฉพาะครั้งแรกที่เกิด exception ใหม่ และเมื่อจำนวนครั้งถึงหลัก 10 (ครั้งที่ 10, 100, 1000, ...) ไม่ส่งทุกครั้งจนอีเมลล้น
- **Mute ได้** — ปิดการแจ้งเตือน exception ที่ไม่ต้องการติดตามได้
- **หน้า Admin UI** — ดูรายการ exception ทั้งหมด, รายละเอียด stack trace, toggle mute, ลบ record ได้ผ่านเว็บ
- **Pruning** — ลบ exception เก่าอัตโนมัติตามจำนวนวันที่กำหนด (default 90 วัน)

## Requirements

- PHP 8.2+
- Laravel 11 หรือ 12

## Installation

```bash
composer require phattarachai/laravel-exception-log
```

Publish และ run migration:

```bash
php artisan vendor:publish --provider="Phattarachai\ExceptionLog\ExceptionLogServiceProvider" --tag="exception-log-migrations"
php artisan migrate
```

Publish config (optional):

```bash
php artisan vendor:publish --provider="Phattarachai\ExceptionLog\ExceptionLogServiceProvider" --tag="exception-log-config"
```

## Configuration

เพิ่มใน `.env`:

```env
EXCEPTION_LOG_ENABLED=true
EXCEPTION_LOG_NOTIFY_EMAIL=your@email.com
EXCEPTION_LOG_RETENTION_DAYS=90
```

## การใช้งาน

Package จะดักจับ exception ทั้งหมดให้อัตโนมัติ ไม่ต้องแก้โค้ดใดๆ เพียงติดตั้งและ migrate เท่านั้น

### Email Notifications

ส่งอีเมลแจ้งเตือนเมื่อ:
- เกิด exception **ใหม่ครั้งแรก**
- จำนวนครั้งถึง **หลักสิบ** (10, 100, 1000, ...)
- เฉพาะเมื่อตั้งค่า `notify_email` ไว้ และ exception ไม่ได้ถูก mute

### Admin UI

เข้าดูได้ที่ `/exception-logs` (ต้อง login ก่อน)

กำหนดสิทธิ์ผ่าน Gate:

```php
Gate::define('viewExceptionLogs', fn ($user) => $user->isAdmin());
```

### Pruning

```bash
php artisan exception-log:prune
```

หรือใช้ model pruning ของ Laravel:

```bash
php artisan model:prune --model="Phattarachai\ExceptionLog\Models\ExceptionLog"
```

### Publish Views

```bash
php artisan vendor:publish --provider="Phattarachai\ExceptionLog\ExceptionLogServiceProvider" --tag="exception-log-views"
```

## License

MIT
