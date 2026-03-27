# SillyWorld Store — Minecraft Webshop

ระบบร้านค้าออนไลน์สำหรับเซิร์ฟเวอร์ Minecraft **SillyWorld**
ผู้เล่นซื้อสินค้า/ยศผ่านเว็บ → ระบบส่งคำสั่งเข้าเซิร์ฟเวอร์อัตโนมัติ

**เว็บไซต์:** https://sillyworld.online
**Discord:** https://discord.gg/pbtnv9A8cH

---

## Features

- ร้านค้าสินค้า (อาวุธ, ชุดเกราะ, เครื่องมือ, ยศ)
- ระบบยศ 13 ระดับ (Rookie → God) ผ่าน LuckPerms
- ระบบ Gacha
- เติมเงิน / กระเป๋าเงิน
- รีดีมโค้ด (หมดอายุ, จำกัดครั้ง)
- ส่งของอัตโนมัติผ่าน Plugin API (HMAC-SHA256)
- แจ้งเตือน in-game เมื่อรับของสำเร็จ
- หน้าดาวน์โหลดเกม
- Admin Panel ครบ
- Auth เชื่อมกับ AuthMe (SHA256) — ใช้ username Minecraft เดียวกัน

---

## Tech Stack

| Component | รายละเอียด |
|---|---|
| Backend | PHP 8.0+ (no framework) |
| Database | MySQL / MariaDB 5.7+ |
| Web Server | Apache (XAMPP) |
| Frontend | Tailwind CSS, SweetAlert2, Font Awesome |
| Auth | AuthMe plugin (SHA256 hash) |
| Plugin | Java Paper/Spigot 1.13–1.21 |
| Java | 17+ |

---

## โครงสร้างโปรเจกต์

```
mcstore/
├── api/
│   ├── plugin/
│   │   ├── pending.php       # Plugin poll pending deliveries (HMAC auth)
│   │   └── confirm.php       # Plugin confirm delivery complete
│   └── shop/
│       └── buynow.php        # Purchase API (atomic stock/balance)
├── classes/
│   ├── Auth.php              # Login, Register, Session (plugin/standalone mode)
│   ├── Database.php          # PDO wrapper + rowCount()
│   └── Settings.php          # Key-value settings cache
├── config/
│   ├── auth.php              # Auth mode: plugin | standalone
│   └── database.php          # DB credentials
├── layout/
│   ├── header.php            # Nav, Flash messages
│   └── footer.php
├── pages/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── shop.php              # Product listing + pagination
│   ├── checkout.php          # Order flow
│   ├── redeem.php            # Redeem codes (atomic race-condition safe)
│   ├── topup.php
│   ├── orders.php
│   ├── gacha.php
│   ├── download.php
│   ├── contact.php
│   └── profile/
└── assets/
    ├── css/theme.php         # Dynamic CSS theme
    └── img/
```

---

## ติดตั้ง (Local / XAMPP)

```bash
# 1. Clone
git clone https://github.com/YamikoZ/mcstore.git
cd mcstore

# 2. สร้าง Database
mysql -u root -p < database/schema.sql

# 3. ตั้งค่า DB
cp config/database.example.php config/database.php
# แก้ host, database, username, password

# 4. ตั้งค่า Auth
# แก้ config/auth.php → mode: 'plugin' หรือ 'standalone'

# 5. เปิด XAMPP → Apache + MySQL → เข้า http://localhost/mcstore
```

---

## ติดตั้ง Plugin (Minecraft Server)

1. วาง `MCStore-Plugin-4.0.jar` ใน `plugins/`
2. ตั้งค่า `plugins/Mysqlcmd/config.yml`:

```yaml
api:
  url: "https://sillyworld.online"
  server_id: "survival"
  secret: "YOUR_SECRET_KEY"
settings:
  polling_interval_ticks: 600   # poll ทุก 30 วิ
  max_commands_per_cycle: 20
allowed_commands:
  - "give"
  - "lp"
  - "eco"
  - "say"
```

3. ตั้งค่า Admin Panel → Settings → Plugin API Secret ให้ตรงกัน
4. `/plugman reload Mysqlcmd` หรือ restart server

---

## Plugin API

### GET Pending Deliveries
```
GET /api/plugin/pending.php
Headers:
  X-Server-ID: survival
  X-Timestamp: 1711234567
  X-Signature: hmac_sha256(server_id:timestamp, secret)
```

### POST Confirm Delivery
```
POST /api/plugin/confirm.php
Headers: (เดียวกัน)
Body: { "delivery_id": 123, "status": "delivered" }
```

---

## Auth Modes

### Plugin Mode (AuthMe)
ใช้ตาราง `authme` ร่วมกับ AuthMe plugin
ผู้เล่น login เว็บด้วย username/password เดียวกับในเกม

```php
// config/auth.php
'mode' => 'plugin',
'plugin' => [
    'table'          => 'authme',
    'columns'        => ['username' => 'realname', ...],
    'hash_algorithm' => 'SHA256',
]
```

### Standalone Mode
ระบบ user แยกจาก Minecraft ใช้ BCRYPT hash

---

## Database Tables หลัก

| Table | ใช้ทำอะไร |
|---|---|
| `users` | Web user profiles + balance |
| `authme` | Minecraft auth (shared กับ plugin) |
| `products` | สินค้าในร้าน |
| `categories` | หมวดหมู่สินค้า |
| `servers` | Minecraft servers |
| `orders` | ประวัติออเดอร์ |
| `order_items` | รายการสินค้าในออเดอร์ |
| `delivery_queue` | คิวส่งของรอ plugin poll |
| `delivery_logs` | log การส่งของ |
| `redeem_codes` | โค้ดรีดีม |
| `banners` | แบนเนอร์หน้าแรก |
| `settings` | ตั้งค่าระบบทั้งหมด |
| `wallet_ledger` | ประวัติธุรกรรมเงิน |
| `notifications` | การแจ้งเตือน in-app |

---

## Security

- CSRF token ทุก form
- Rate limiting (login, buynow, redeem)
- Atomic balance deduction (`AND balance >= ?` + rowCount check)
- Atomic stock reduction (race-condition safe)
- Atomic redeem code usage (race-condition safe)
- HMAC-SHA256 สำหรับ Plugin API
- Timestamp validation ±5 นาที (ป้องกัน replay attack)
- Command whitelist สำหรับ delivery
- Parameterized queries ทุก query (no SQL injection)

---

## Environment

- **Server:** Windows Server 2022 + XAMPP
- **Domain:** sillyworld.online
- **Minecraft:** Paper 1.21.x
- **Ports:** Survival 25567, Login 25566, Velocity proxy

---

## License

Private — SillyWorld Server
ห้ามนำโค้ดไปใช้เชิงพาณิชย์โดยไม่ได้รับอนุญาต
