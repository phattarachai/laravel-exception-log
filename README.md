# Laravel Exception Log

บันทึก Exception ลงฐานข้อมูล พร้อมระบบแจ้งเตือนผ่านอีเมล

## ความสามารถ

- **บันทึก Exception** — ดักจับทุก Exception ที่เกิดขึ้นในแอป Laravel แล้วบันทึกลง database โดยไม่ต้องแก้โค้ดใดๆ
- **ระบบ Fingerprint Exception** — Exception เดียวกัน (class + file + line เดียวกัน) จะไม่สร้าง record ซ้ำ แต่จะนับจำนวน
  Exception ที่เกิดขึ้น
- **Request Context** — บันทึกบริบทการเกิด exception อัตโนมัติ (HTTP: URL, method, IP, user / Console: command / Queue:
  job command) พร้อม sanitize ข้อมูลสำคัญ (password, token, secret, api_key, credit_card, cvv)
- **แจ้งเตือนผ่านอีเมล** — ส่งอีเมลแจ้งเตือนเมื่อเกิด exception ใหม่, เมื่อจำนวนถึง milestone (10, 100, 200, 300, ...,
  1000, 2000, ...), เมื่อ exception ที่ resolved กลับมาเกิดใหม่ (REOPENED), หรือเมื่อเงียบไปนานแล้วกลับมาเกิดอีก (
  re-alert)
- **Resolved/Unresolved** — ทำเครื่องหมาย exception ว่าแก้ไขแล้ว (resolved) ได้ ถ้าเกิดซ้ำจะ reopen
  อัตโนมัติพร้อมแจ้งเตือน
- **Ignore List** — กำหนด exception class ที่ไม่ต้องการบันทึกได้ (เช่น ValidationException, NotFoundHttpException)
  รองรับ subclass ด้วย
- **Mute ได้** — ปิดการแจ้งเตือน exception ที่ไม่ต้องการติดตามได้
- **หน้า Admin UI** — ดูรายการ exception ทั้งหมด, filter ตาม status/class/message/วันที่, ดูรายละเอียด stack trace และ
  context, resolve/reopen, toggle mute, ลบ record ได้
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
EXCEPTION_LOG_RE_ALERT_AFTER_HOURS=24
```

### Config Options

| Key                    | Default                      | Description                               |
|------------------------|------------------------------|-------------------------------------------|
| `enabled`              | `true`                       | เปิด/ปิดการบันทึก exception               |
| `notify_email`         | `null`                       | อีเมลที่จะรับการแจ้งเตือน (null = ไม่ส่ง) |
| `retention_days`       | `90`                         | จำนวนวันที่เก็บ exception ก่อน prune      |
| `route_prefix`         | `exception-logs`             | URL prefix ของหน้า admin                  |
| `route_middleware`     | `['web', 'auth']`            | Middleware สำหรับหน้า admin               |
| `ignore`               | `[ValidationException, ...]` | Exception classes ที่ไม่ต้องการบันทึก     |
| `re_alert_after_hours` | `24`                         | ชั่วโมงที่เงียบไปก่อนจะแจ้งเตือนอีกครั้ง  |

### Ignore List

กำหนด exception ที่ไม่ต้องการบันทึกใน config:

```php
'ignore' => [
    \Illuminate\Validation\ValidationException::class,
    \Illuminate\Auth\AuthenticationException::class,
    \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    \Illuminate\Database\Eloquent\ModelNotFoundException::class,
],
```

ใช้ `instanceof` ในการเช็ค ดังนั้น subclass จะถูก ignore ด้วย

## การใช้งาน

Package จะดักจับ exception ทั้งหมดให้อัตโนมัติ ไม่ต้องแก้โค้ดใดๆ เพียงติดตั้งและ migrate เท่านั้น

### Email Notifications

ส่งอีเมลแจ้งเตือนเมื่อ:

- เกิด exception **ใหม่ครั้งแรก**
- จำนวนครั้งถึง **milestone** (10, 100, 200, 300, ..., 1000, 2000, ...)
- exception ที่ **resolved แล้วกลับมาเกิดอีก** (subject ขึ้นต้นด้วย "REOPENED:")
- exception ที่ **เงียบไปนาน** เกินค่า `re_alert_after_hours` แล้วกลับมาเกิดอีก

### Request Context

ทุก exception จะบันทึกบริบทการเกิดอัตโนมัติ:

- **HTTP** — URL, method, route name, IP, user agent, user ID, sanitized input
- **Console** — artisan command ที่รัน
- **Queue** — queue worker command

ข้อมูลสำคัญ (password, token, secret, api_key, credit_card, cvv) จะถูก sanitize เป็น `[REDACTED]` อัตโนมัติ

### Resolved/Unresolved

- กด **Resolve** เพื่อทำเครื่องหมายว่าแก้ไขแล้ว
- ถ้า exception เดิมเกิดขึ้นอีก จะ **reopen อัตโนมัติ** พร้อมส่งอีเมลแจ้งเตือน
- Filter ดู exception ตาม status ได้ (Unresolved, Resolved, Muted)

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
