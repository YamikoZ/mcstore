# MCStore

ระบบเว็บไซต์ร้านค้า Minecraft สำหรับรับชำระเงิน ออเดอร์สินค้า และจัดการผู้เล่น
พัฒนาด้วย PHP + MySQL รองรับทั้ง shared hosting และ VPS

---

## ✨ ฟีเจอร์หลัก

### 🛒 Shop — ร้านค้าไอเทม
- ขายสินค้า/ไอเทมในเกม แยกตามเซิร์ฟเวอร์และหมวดหมู่
- ระบบตะกร้าสินค้า เพิ่ม/ลบ/เช็คสต็อก
- Checkout จ่ายด้วยเงินในระบบ (Wallet)
- ส่งคำสั่งไปยัง Minecraft Server อัตโนมัติผ่าน Plugin

### 🎰 Gacha — กล่องสุ่มรางวัล
- สุ่มรางวัลแบบ weighted probability
- กำหนดรางวัลและเรต drop ได้เอง
- แสดงประวัติการสุ่มย้อนหลัง

### 💰 Topup — เติมเงินเข้าระบบ
- รองรับ **ซองอั่งเปา TrueWallet** (อัตโนมัติ)
- เครดิตเข้า Wallet ทันทีหลังเติม
- ประวัติการเติมเงินครบถ้วน

### 🎫 Redeem Code — แลกโค้ด
- สร้างโค้ดแจกลูกค้า กำหนดมูลค่าและจำนวนครั้งใช้ได้
- แลกโค้ดรับเงินเข้า Wallet หรือสินค้าโดยตรง

### 👤 ระบบสมาชิก
- รองรับ **AuthMe / nLogin** (ใช้ตาราง plugin โดยตรง)
- รองรับ **Standalone** (ระบบ login ของ MCStore เอง)
- โปรไฟล์ผู้ใช้: แก้ไขข้อมูล, เปลี่ยนรหัสผ่าน, ประวัติออเดอร์
- ระบบ Wallet (ยอดเงินในระบบ)
- ระบบแจ้งเตือน (Notifications)

### 🔧 Admin Panel — จัดการทุกอย่างในที่เดียว
- **Dashboard** — สถิติยอดขาย, ออเดอร์, ผู้ใช้ วันนี้/รวม
- **สินค้า** — เพิ่ม/แก้ไข/ลบสินค้า, จัดการสต็อก, หมวดหมู่
- **เซิร์ฟเวอร์** — จัดการ Minecraft Server หลายตัวพร้อมกัน
- **กาชา** — ออกแบบกล่องสุ่ม กำหนดรางวัลและเรต
- **รีดีมโค้ด** — สร้างและจัดการโค้ดแลก
- **ออเดอร์** — ดูและจัดการออเดอร์ทั้งหมด, ส่งสินค้าซ้ำ
- **เติมเงิน** — ตรวจสอบการเติมเงิน, อนุมัติ manual
- **ผู้ใช้** — จัดการสมาชิก, แบน/ปลดแบน, เติม/ตัดเงิน
- **แบนเนอร์** — จัดการ slideshow หน้าแรก
- **หน้าเพจ** — สร้างหน้า static (เกี่ยวกับเรา, FAQ ฯลฯ)
- **ช่องทางจ่าย** — ตั้งค่า payment gateway
- **ข้อความ** — รับ/ตอบข้อความติดต่อจากลูกค้า
- **ตั้งค่า** — ปรับทุกอย่างผ่าน UI ไม่ต้องแก้โค้ด
- **License** — จัดการ License Key

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

### ราคา

| แพ็กเกจ | ราคา | หมายเหตุ |
|---------|------|---------|
| License Key (ถาวร) | **350 บาท** | 1 key ต่อ 1 domain |
| ย้าย Domain | **150 บาท**/ครั้ง | รีเซ็ต key ไปผูก domain ใหม่ |

- Key จะถูกผูกกับ domain ของคุณโดยอัตโนมัติเมื่อใช้ครั้งแรก
- ย้าย domain ต้องแจ้งผู้พัฒนาเพื่อรีเซ็ต (150 บาท/ครั้ง)

### ขอรับ License

เปิด Issue ได้ที่ → **[GitHub Issues](https://github.com/YamikoZ/mcstore/issues/new?title=ขอ%20License%20Key&body=**Domain:**%20%60yourdomain.com%60%0A%0A**ชื่อเว็บ%20/%20ร้านค้า:**%0A%0A**ช่องทางติดต่อ:**&labels=license)**

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

## 🔄 อัปเดต

เมื่อมีการอัปเดตใหม่ใน GitHub ให้รันคำสั่งนี้ในโฟลเดอร์ที่ติดตั้ง MCStore:

```bash
git pull
```

### สิ่งที่อัปเดตโดยอัตโนมัติ
- ไฟล์ PHP ทั้งหมด (หน้าเว็บ, คลาส, API, Admin)
- ไฟล์ CSS / JS / Assets
- Bug fix และ Security patch

### สิ่งที่ **ไม่** ถูกแตะต้อง (ปลอดภัย)
- `config/database.php` — การตั้งค่าฐานข้อมูล
- `config/license.php` — License config
- `config/auth.php` — Auth mode
- `uploads/` — รูปภาพที่อัปโหลด

### หากมีการอัปเดต Database Schema

ผู้พัฒนาจะแจ้งใน release notes หาก version ใหม่มีการเปลี่ยน schema ให้รัน SQL เพิ่มเติมที่แนบมาด้วย

### ติดตามการอัปเดต

- Watch repo นี้บน GitHub (⭐ Star → Watch → All Activity)
- หรือเช็ค [Releases](https://github.com/YamikoZ/mcstore/releases) เป็นระยะ

---

## 📝 หมายเหตุ

- `config/database.php` และ `config/license.php` ไม่รวมใน repo ต้องสร้างเองจาก `.example.php`
- หลัง `git pull` ไม่ต้องทำอะไรเพิ่ม config ไฟล์จะยังอยู่ครบ
- เปลี่ยนชื่อโฟลเดอร์ได้ตามต้องการ ไม่มีอะไรผูกกับชื่อ `mcstore`
