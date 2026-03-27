# MCStore — Minecraft Webshop

ระบบร้านค้าออนไลน์สำหรับ Minecraft Server
รองรับ **ซื้อสินค้า → ส่งคำสั่งเข้า Server อัตโนมัติ** ผ่าน HTTP API

---

## สิ่งที่ต้องมี

| Component | รายละเอียด |
|-----------|-----------|
| PHP | 8.0+ พร้อม `mysqli`, `json`, `mbstring` |
| MySQL / MariaDB | 5.7+ |
| Web Server | Apache (XAMPP) หรือ Nginx |
| Minecraft Server | Spigot / Paper 1.13–1.21 |
| Java | 17+ (สำหรับ build plugin เอง) |

---

## ติดตั้ง Web

```bash
# 1. วางโฟลเดอร์ใน htdocs (หรือ web root)
# 2. Import ฐานข้อมูล
mysql -u root -p mcstore < mcstore.sql

# 3. คัดลอก config ตัวอย่าง
cp config/database.example.php config/database.php

# 4. แก้ไข config/database.php ให้ตรงกับ server
```

```php
return [
    'host'     => '127.0.0.1',
    'database' => 'mcstore',
    'username' => 'root',
    'password' => 'your_password',
];
```

```bash
# 5. เข้า Admin แรก → สร้าง account แล้วเปลี่ยน role เป็น admin ใน DB
UPDATE users SET role = 'admin' WHERE username = 'yourname';
```

---

## ติดตั้ง Plugin

### ดาวน์โหลด
ดาวน์โหลดไฟล์ **[MCStore-Plugin-4.0.jar](releases/MCStore-Plugin-4.0.jar)** จากโฟลเดอร์ `releases/`

### วางไฟล์
```
server/
└── plugins/
    └── MCStore-Plugin-4.0.jar   ← วางไว้ที่นี่
```

### ตั้งค่า `plugins/Mysqlcmd/config.yml`
```yaml
api:
  url: "https://yoursite.com"      # URL เว็บ MCStore (ไม่ต้องใส่ / ท้าย)
  server_id: "survival"            # ต้องตรงกับ servers.id ใน Database
  secret: "YOUR_PLUGIN_API_SECRET" # ตั้งค่าใน Admin → Settings → Plugin

settings:
  polling_interval_ticks: 600      # 600 ticks = 30 วินาที
  max_commands_per_cycle: 20

allowed_commands:
  - "give"
  - "lp"
  - "eco"
  - "rank"
  - "booster"
  - "crate"
  # เพิ่มคำสั่งที่อนุญาตได้ที่นี่
```

> **Plugin API Secret** — ตั้งค่าที่ `Admin → Settings → Plugin API Secret`
> Plugin จะ poll API ทุก 30 วินาที และส่งคำสั่งเข้า server อัตโนมัติ

### คำสั่ง In-Game
| คำสั่ง | คำอธิบาย |
|--------|---------|
| `/mysqlcmd reload` | โหลด config ใหม่โดยไม่ต้อง restart server |
| `/mysqlcmd status` | แสดงสถานะ API, สถิติการส่งของ |
| `/mysqlcmd poll` | สั่ง poll ทันทีโดยไม่ต้องรอ 30 วินาที |

**Permission:** `mysqlcmd.admin` (default: op)

---

## ฟีเจอร์หลัก

### ร้านค้า
- ซื้อสินค้าแบบ **Buy Now** (1 คลิก) พร้อมระบุจำนวน
- รองรับ **Set Items** — ชุดเกราะ/ของหลายชิ้นในคลิกเดียว (JSON array commands)
- ป้องกันซื้อซ้ำสำหรับสินค้าประเภทยศ/VIP (`one_per_user`)
- จัดการ stock (unlimited = -1, มี stock = ตัวเลข)
- Pagination ‹ 1 2 3 › พร้อม preserve filter

### ระบบ Gacha
- Weighted Random พร้อม 5 ระดับความหายาก: Common / Uncommon / Rare / Epic / Legendary
- ประวัติการหมุนพร้อม rarity glow border
- Pagination

### การชำระเงิน
- ระบบ Wallet (เติมเงิน → ใช้ซื้อสินค้า)
- รองรับสลิปโอนเงิน (อัปโหลดรูป + ยืนยันโดย Admin)

### Plugin ↔ Web (HTTP API)
- Plugin **ไม่ต้องเชื่อมต่อ DB โดยตรง** — ปลอดภัยกว่า
- HMAC-SHA256 Authentication ทุก request
- Console banner + สถิติ ✔/✘ per delivery
- Callback รายงานผลส่ง command กลับเว็บ

### Profile
- ภาพรวมสถิติ + Minecraft body skin
- ประวัติออเดอร์, กระเป๋าเงิน, กาชา, การส่งของ
- แจ้งเตือน, แก้ไขโปรไฟล์, เปลี่ยนรหัสผ่านพร้อม strength bar

### Admin Panel
- จัดการสินค้า, หมวดหมู่, เซิร์ฟเวอร์
- Command Builder (เพิ่มทีละบรรทัด / Set Items)
- ระบบ Redeem Code
- Delivery Queue Monitor
- Pagination ทุกหน้า

---

## โครงสร้างโปรเจกต์

```
mcstore/
├── api/
│   ├── plugin/          # Plugin polling & callback endpoints
│   └── shop/            # Buy Now API
├── admin/               # Admin panel pages
├── assets/
│   ├── css/theme.php    # Dynamic CSS themes
│   └── js/app.js        # Frontend JS (buyNow, SweetAlert2)
├── classes/             # Core classes (Auth, Database, Helpers...)
├── config/              # DB + License config (gitignored)
├── layout/              # Header/Footer templates
├── pages/               # Frontend pages
│   └── profile/         # Profile sub-pages
├── releases/
│   └── MCStore-Plugin-4.0.jar   ← Plugin พร้อมใช้งาน
├── mysqlcmd/            # Plugin source code (Maven / Java 17)
│   ├── src/
│   └── pom.xml
└── mcstore.sql          # Database schema + initial data
```

---

## Build Plugin จาก Source

```bash
cd mysqlcmd
set JAVA_HOME=C:/Program Files/Java/jdk-21
mvn clean package -q
# Output: target/MCStore-Plugin-4.0.jar
```

---

## Security

- CSRF Token ทุก form และ AJAX request (`X-CSRF-TOKEN` header)
- HMAC-SHA256 signature ระหว่าง Plugin ↔ API (ป้องกัน replay attack)
- Command Whitelist — plugin รันเฉพาะคำสั่งที่ระบุใน `allowed_commands`
- `execute` / `run` ถูกบล็อกอัตโนมัติเสมอ
- Rate limiting บน API endpoints
- Prepared statements ทุก query (ป้องกัน SQL Injection)
- `config/database.php` อยู่ใน `.gitignore`

---

## อัปเดตโปรเจกต์

```bash
git pull origin main
```

> ไฟล์ที่ไม่ถูกแตะต้อง: `config/database.php`, `config/license.php`, `uploads/`

---

## Changelog

### v4.0 (ล่าสุด)
- เปลี่ยน Plugin จาก Direct MySQL → HTTP API (ปลอดภัยกว่า)
- เพิ่ม HMAC-SHA256 Authentication
- ระบบ Buy Now แทน Cart
- รองรับ Set Items (JSON array commands)
- ป้องกันซื้อซ้ำ (one_per_user)
- รองรับ Minecraft 1.21 item component format
- Pagination ทุกหน้า (frontend + admin)
- Profile redesign — sidebar + hero card + rarity glow
- เหลือเซิร์ฟเวอร์ Survival เดียว

---

**Developer:** NTHNCH | Private — ห้ามนำไปขายต่อโดยไม่ได้รับอนุญาต
