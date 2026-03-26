# MCStore

ระบบเว็บไซต์ร้านค้า Minecraft สำหรับรับชำระเงิน ออเดอร์สินค้า และจัดการผู้เล่น

---

## ✨ ฟีเจอร์หลัก

- 🛒 **Shop** — ขายสินค้า/ไอเทมในเกม
- 🎰 **Gacha** — กล่องสุ่มรางวัล
- 💰 **Topup** — เติมเงินผ่านซองอั่งเปา TrueWallet
- 🎫 **Redeem Code** — แลกโค้ดรับสินค้า
- 👤 **ระบบสมาชิก** — รองรับ AuthMe / nLogin และ Standalone
- 🔧 **Admin Panel** — จัดการสินค้า ออเดอร์ ผู้ใช้ และตั้งค่าทั้งหมด

---

## 📋 ความต้องการ

| รายการ | เวอร์ชัน |
|--------|---------|
| PHP | 8.0+ |
| MySQL | 5.7+ |
| Apache | 2.4+ (mod_rewrite) |
| XAMPP / Laragon | หรือ web server อื่นๆ |

---

## ⚡ ติดตั้ง

### 1. โหลดไฟล์

```bash
git clone https://github.com/YamikoZ/mcstore.git
cd mcstore
```

### 2. สร้างไฟล์ Config

```bash
# Database
copy config\database.example.php config\database.php

# License
copy config\license.example.php config\license.php
```

แก้ไข `config/database.php` ให้ตรงกับ database ของคุณ:

```php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'mcstore',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];
```

### 3. Import Database

นำเข้าไฟล์ `mcstore.sql` ผ่าน phpMyAdmin หรือ:

```bash
mysql -u root -p mcstore < mcstore.sql
```

### 4. ใส่ License Key

เปิดเว็บ → จะเห็นหน้า **ใส่ License Key** → กรอก key ที่ได้รับแล้วกด ยืนยัน

---

## 🔑 License

ระบบนี้ต้องมี **License Key** เพื่อใช้งาน

ติดต่อขอ License ได้ที่ผู้พัฒนา key จะถูกผูกกับ domain ของคุณโดยอัตโนมัติเมื่อใช้ครั้งแรก

---

## 🔗 เชื่อมกับ TrueWallet (ระบบอั่งเปา)

ต้องติดตั้ง **[truewallet-api](https://github.com/YamikoZ/truewallet-api)** บน Linux server แยกต่างหาก แล้วตั้งค่าใน Admin → Settings:

| Key | ค่า |
|-----|-----|
| `tw_proxy_url` | URL ของ truewallet-api เช่น `http://103.x.x.x` |
| `tw_proxy_key` | API Key ที่ตั้งตอนติดตั้ง |

---

## 🔌 เชื่อมกับ Minecraft Plugin

ตั้งค่าใน Admin → Settings → Plugin:

| Key | คำอธิบาย |
|-----|---------|
| `plugin_api_secret` | Secret key สำหรับ plugin ยืนยันตัวตน |

Plugin endpoint:
```
GET  /api/plugin/pending   — ดึงรายการคำสั่งที่รอส่ง
POST /api/plugin/callback  — แจ้งผลการส่งคำสั่ง
```

---

## 🔐 Auth Mode

รองรับ 2 โหมดใน `config/auth.php`:

| โหมด | คำอธิบาย |
|------|---------|
| `plugin` | ใช้ตาราง AuthMe / nLogin จาก MySQL |
| `standalone` | ระบบสมาชิกของ MCStore เอง |

---

## 📁 โครงสร้างไฟล์

```
mcstore/
├── index.php              ← Front controller / Router
├── mcstore.sql            ← Database schema
├── config/
│   ├── database.php       ← DB config (ไม่ขึ้น git)
│   ├── license.php        ← License config (ไม่ขึ้น git)
│   └── auth.php           ← Auth mode config
├── classes/               ← Core classes (Database, Auth, Settings...)
├── admin/                 ← Admin panel pages
├── api/                   ← JSON API endpoints
├── pages/                 ← Frontend pages
├── layout/                ← Header / Footer templates
└── assets/                ← CSS, JS
```

---

## 📝 หมายเหตุ

- `config/database.php` และ `config/license.php` ไม่รวมใน repo ต้องสร้างเองจาก `.example.php`
- หลัง `git pull` ไม่ต้องทำอะไรเพิ่ม config ไฟล์จะยังอยู่ครบ
