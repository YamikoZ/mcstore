-- =====================================================
-- MCStore Database Schema
-- Minecraft Server Webshop
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `mcstore` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mcstore`;

-- =====================================================
-- AUTH: authme (plugin สร้าง / เว็บเขียนด้วย)
-- =====================================================
CREATE TABLE `authme` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) NOT NULL,
    `realname`   VARCHAR(255) DEFAULT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) DEFAULT 'your@email.com',
    `ip`         VARCHAR(45) DEFAULT NULL,
    `lastlogin`  BIGINT DEFAULT 0,
    `regip`      VARCHAR(45) DEFAULT NULL,
    `regdate`    BIGINT DEFAULT 0,
    `x`          DOUBLE DEFAULT 0,
    `y`          DOUBLE DEFAULT 0,
    `z`          DOUBLE DEFAULT 0,
    `world`      VARCHAR(255) DEFAULT 'world',
    `isLogged`   SMALLINT DEFAULT 0,
    `hasSession` SMALLINT DEFAULT 0,
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USERS: ข้อมูลเว็บ (balance, role, avatar ฯลฯ)
-- =====================================================
CREATE TABLE `users` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `username`       VARCHAR(255) NOT NULL,
    `email`          VARCHAR(255) DEFAULT NULL,
    `password`       VARCHAR(255) DEFAULT NULL COMMENT 'NULL ถ้าใช้ plugin mode',
    `authme_id`      INT DEFAULT NULL,
    `balance`        DECIMAL(10,2) DEFAULT 0.00,
    `role`           ENUM('user','vip','admin') DEFAULT 'user',
    `avatar_url`     VARCHAR(500) DEFAULT NULL,
    `is_banned`      TINYINT DEFAULT 0,
    `ban_reason`     VARCHAR(500) DEFAULT NULL,
    `last_login_web` TIMESTAMP NULL DEFAULT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SERVERS
-- =====================================================
CREATE TABLE `servers` (
    `id`            VARCHAR(50) NOT NULL PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `description`   TEXT DEFAULT NULL,
    `icon`          VARCHAR(500) DEFAULT NULL,
    `ip`            VARCHAR(255) DEFAULT NULL,
    `port`          INT DEFAULT 25565,
    `display_order` INT DEFAULT 0,
    `is_active`     TINYINT DEFAULT 1,
    `last_poll`     TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `servers` (`id`, `name`, `description`, `ip`, `display_order`, `is_active`) VALUES
('lobby',     'Lobby',     'เซิร์ฟเวอร์ Lobby — จุดรวมพลก่อนเข้าเกม',     'play.mcsakura.com', 1, 1),
('survival',  'Survival',  'เซิร์ฟเวอร์ Survival — เอาชีวิตรอดและสร้างฐาน',  'play.mcsakura.com', 2, 1),
('creative',  'Creative',  'เซิร์ฟเวอร์ Creative — สร้างสรรค์ได้อิสระ',      'play.mcsakura.com', 3, 1),
('skyblock',  'SkyBlock',  'เซิร์ฟเวอร์ SkyBlock — ผจญภัยบนเกาะลอยฟ้า',   'play.mcsakura.com', 4, 1),
('prison',    'Prison',    'เซิร์ฟเวอร์ Prison — ขุดแร่ไต่แรงค์',            'play.mcsakura.com', 5, 1);

-- =====================================================
-- CATEGORIES
-- =====================================================
CREATE TABLE `categories` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `server_id`     VARCHAR(50) DEFAULT NULL,
    `name`          VARCHAR(100) NOT NULL,
    `icon`          VARCHAR(100) DEFAULT 'fa-box',
    `display_order` INT DEFAULT 0,
    `is_active`     TINYINT DEFAULT 1,
    FOREIGN KEY (`server_id`) REFERENCES `servers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`server_id`, `name`, `icon`, `display_order`) VALUES
('survival', 'อาวุธ',       'fa-sword',    1),
('survival', 'ชุดเกราะ',    'fa-shield',   2),
('survival', 'เครื่องมือ',  'fa-pickaxe',  3),
('survival', 'แรงค์/VIP',   'fa-crown',    4),
('survival', 'อื่นๆ',      'fa-cube',     5),
('creative', 'World Edit',  'fa-globe',    1),
('creative', 'แรงค์/VIP',   'fa-crown',    2),
('skyblock', 'ไอเทม',      'fa-gem',      1),
('skyblock', 'แรงค์/VIP',   'fa-crown',    2),
('prison',   'แรงค์/VIP',   'fa-crown',    1),
('prison',   'เครื่องมือ',  'fa-hammer',   2),
('prison',   'บูสเตอร์',   'fa-rocket',   3);

-- =====================================================
-- PRODUCTS
-- =====================================================
CREATE TABLE `products` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `server_id`      VARCHAR(50) NOT NULL,
    `category_id`    INT DEFAULT NULL,
    `name`           VARCHAR(255) NOT NULL,
    `description`    TEXT DEFAULT NULL,
    `image`          VARCHAR(500) DEFAULT NULL,
    `price`          DECIMAL(10,2) NOT NULL,
    `original_price` DECIMAL(10,2) DEFAULT NULL,
    `command`        VARCHAR(500) NOT NULL,
    `stock`          INT DEFAULT -1 COMMENT '-1 = unlimited',
    `is_featured`    TINYINT DEFAULT 0,
    `is_active`      TINYINT DEFAULT 1,
    `display_order`  INT DEFAULT 0,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`server_id`) REFERENCES `servers`(`id`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- GACHA CRATES
-- =====================================================
CREATE TABLE `gacha_crates` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `server_id`     VARCHAR(50) NOT NULL,
    `name`          VARCHAR(255) NOT NULL,
    `description`   TEXT DEFAULT NULL,
    `image`         VARCHAR(500) DEFAULT NULL,
    `price`         DECIMAL(10,2) NOT NULL,
    `crate_type`    VARCHAR(50) DEFAULT 'normal',
    `is_active`     TINYINT DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`server_id`) REFERENCES `servers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- GACHA REWARDS
-- =====================================================
CREATE TABLE `gacha_rewards` (
    `id`        INT AUTO_INCREMENT PRIMARY KEY,
    `crate_id`  INT NOT NULL,
    `name`      VARCHAR(255) NOT NULL,
    `image`     VARCHAR(500) DEFAULT NULL,
    `rarity`    VARCHAR(50) NOT NULL COMMENT 'common,rare,epic,mythic,legendary',
    `weight`    INT NOT NULL COMMENT 'ยิ่งมาก ยิ่งดรอปง่าย',
    `command`   VARCHAR(500) NOT NULL,
    `is_active` TINYINT DEFAULT 1,
    FOREIGN KEY (`crate_id`) REFERENCES `gacha_crates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- GACHA HISTORY
-- =====================================================
CREATE TABLE `gacha_history` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) NOT NULL,
    `crate_id`   INT NOT NULL,
    `reward_id`  INT NOT NULL,
    `rarity`     VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`crate_id`) REFERENCES `gacha_crates`(`id`),
    FOREIGN KEY (`reward_id`) REFERENCES `gacha_rewards`(`id`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDERS
-- =====================================================
CREATE TABLE `orders` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) NOT NULL,
    `total`      DECIMAL(10,2) NOT NULL,
    `status`     ENUM('pending','paid','delivered','cancelled','refunded') DEFAULT 'pending',
    `note`       TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDER ITEMS
-- =====================================================
CREATE TABLE `order_items` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`   INT NOT NULL,
    `product_id` INT DEFAULT NULL,
    `server_id`  VARCHAR(50) NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `price`      DECIMAL(10,2) NOT NULL,
    `quantity`    INT DEFAULT 1,
    `command`    VARCHAR(500) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TOPUP TRANSACTIONS
-- =====================================================
CREATE TABLE `topup_transactions` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `username`        VARCHAR(255) NOT NULL,
    `amount`          DECIMAL(10,2) NOT NULL,
    `gateway`         VARCHAR(50) NOT NULL,
    `status`          ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
    `gateway_ref`     VARCHAR(255) DEFAULT NULL,
    `idempotency_key` VARCHAR(100) DEFAULT NULL,
    `note`            TEXT DEFAULT NULL,
    `slip_image`      VARCHAR(255) DEFAULT NULL COMMENT 'ไฟล์สลิปโอนเงิน',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at`    TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_idempotency` (`idempotency_key`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- WALLET LEDGER (สมุดบัญชี)
-- =====================================================
CREATE TABLE `wallet_ledger` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) NOT NULL,
    `type`       ENUM('credit','debit') NOT NULL,
    `amount`     DECIMAL(10,2) NOT NULL,
    `balance_after` DECIMAL(10,2) NOT NULL,
    `reference`  VARCHAR(255) DEFAULT NULL COMMENT 'เช่น topup#1, order#5, gacha#10',
    `note`       VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DELIVERY QUEUE
-- =====================================================
CREATE TABLE `delivery_queue` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`     INT DEFAULT NULL,
    `username`     VARCHAR(255) NOT NULL,
    `server_id`    VARCHAR(50) NOT NULL,
    `player_name`  VARCHAR(255) NOT NULL,
    `command`      VARCHAR(500) NOT NULL,
    `item_name`    VARCHAR(255) DEFAULT NULL,
    `status`       ENUM('pending','processing','delivered','failed','player_offline') DEFAULT 'pending',
    `attempts`     INT DEFAULT 0,
    `max_attempts` INT DEFAULT 10,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `delivered_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`server_id`) REFERENCES `servers`(`id`),
    INDEX `idx_server_status` (`server_id`, `status`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DELIVERY LOGS
-- =====================================================
CREATE TABLE `delivery_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `delivery_id` INT NOT NULL,
    `status`      VARCHAR(50) NOT NULL,
    `response`    TEXT DEFAULT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`delivery_id`) REFERENCES `delivery_queue`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REDEEM CODES
-- =====================================================
CREATE TABLE `redeem_codes` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `code`           VARCHAR(50) NOT NULL,
    `reward_type`    ENUM('balance','item','gacha') NOT NULL,
    `reward_value`   TEXT NOT NULL COMMENT 'จำนวนเงิน หรือ JSON command',
    `server_id`      VARCHAR(50) DEFAULT NULL,
    `max_uses`       INT DEFAULT 1,
    `used_count`     INT DEFAULT 0,
    `per_user_limit` INT DEFAULT 1,
    `expires_at`     TIMESTAMP NULL DEFAULT NULL,
    `is_active`      TINYINT DEFAULT 1,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REDEEM USAGE
-- =====================================================
CREATE TABLE `redeem_usage` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `code_id`    INT NOT NULL,
    `username`   VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`code_id`) REFERENCES `redeem_codes`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_code_user` (`code_id`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAYMENT GATEWAYS
-- =====================================================
CREATE TABLE `payment_gateways` (
    `id`            VARCHAR(50) NOT NULL PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `description`   TEXT DEFAULT NULL,
    `icon`          VARCHAR(500) DEFAULT NULL,
    `config_json`   TEXT DEFAULT NULL,
    `min_amount`    DECIMAL(10,2) DEFAULT 0,
    `max_amount`    DECIMAL(10,2) DEFAULT 99999,
    `fee_percent`   DECIMAL(5,2) DEFAULT 0,
    `display_order` INT DEFAULT 0,
    `is_active`     TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_gateways` (`id`, `name`, `description`, `icon`, `config_json`, `min_amount`, `max_amount`, `display_order`, `is_active`) VALUES
('truewallet', 'TrueMoney Wallet', 'เติมเงินผ่าน TrueMoney Voucher',  '/assets/img/truewallet.png', '{"phone":"0812345678"}', 10, 5000, 1, 1),
('promptpay',  'PromptPay QR',     'โอนผ่าน QR Code PromptPay',      '/assets/img/promptpay.png',  '{"promptpay_id":"0812345678"}', 1, 10000, 2, 1),
('bank',       'โอนธนาคาร',       'โอนเงินเข้าบัญชีธนาคาร',         '/assets/img/bank.png',       '{"bank_name":"กสิกร","account_no":"xxx-x-xxxxx-x","account_name":"ชื่อ นามสกุล"}', 1, 50000, 3, 0);

-- =====================================================
-- NOTIFICATIONS
-- =====================================================
CREATE TABLE `notifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) NOT NULL,
    `type`       VARCHAR(50) NOT NULL COMMENT 'order,topup,delivery,redeem,system',
    `title`      VARCHAR(255) NOT NULL,
    `message`    TEXT DEFAULT NULL,
    `link`       VARCHAR(500) DEFAULT NULL,
    `is_read`    TINYINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_read` (`username`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONTACT MESSAGES
-- =====================================================
CREATE TABLE `contact_messages` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) DEFAULT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `subject`    VARCHAR(255) NOT NULL,
    `message`    TEXT NOT NULL,
    `status`     ENUM('unread','read','replied') DEFAULT 'unread',
    `admin_reply` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BANNERS
-- =====================================================
CREATE TABLE `banners` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `title`         VARCHAR(255) DEFAULT NULL,
    `image`         VARCHAR(500) NOT NULL,
    `link`          VARCHAR(500) DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active`     TINYINT DEFAULT 1,
    `starts_at`     TIMESTAMP NULL DEFAULT NULL,
    `ends_at`       TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAGES (static content)
-- =====================================================
CREATE TABLE `pages` (
    `id`        INT AUTO_INCREMENT PRIMARY KEY,
    `slug`      VARCHAR(100) NOT NULL,
    `title`     VARCHAR(255) NOT NULL,
    `content`   TEXT DEFAULT NULL,
    `is_active` TINYINT DEFAULT 1,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pages` (`slug`, `title`, `content`) VALUES
('rules', 'กฎเซิร์ฟเวอร์', '<h3>กฎทั่วไป</h3><ol><li>ห้ามใช้โปรแกรมโกง (Hack/Cheat) ทุกชนิด</li><li>ห้ามด่า พูดจาหยาบคาย ดูถูกผู้เล่นอื่น</li><li>ห้ามสแปมข้อความ หรือโฆษณาเซิร์ฟอื่น</li><li>ห้ามหลอกลวง/โกงผู้เล่นอื่น</li><li>ห้ามใช้ Exploit/Bug เพื่อประโยชน์ส่วนตัว</li><li>เคารพ Staff ทุกคน ปฏิบัติตามคำแนะนำ</li><li>ห้ามขายของในเกมเป็นเงินจริง</li><li>ห้ามแอบอ้างเป็น Staff หรือแอดมิน</li><li>ห้ามใช้สกินหรือชื่อที่ไม่เหมาะสม</li><li>การตัดสินใจของ Staff ถือเป็นที่สิ้นสุด</li></ol><h3>บทลงโทษ</h3><ul><li>ครั้งที่ 1: เตือน</li><li>ครั้งที่ 2: แบนชั่วคราว 1 วัน</li><li>ครั้งที่ 3: แบนชั่วคราว 7 วัน</li><li>ครั้งที่ 4: แบนถาวร</li></ul>'),
('faq', 'คำถามที่พบบ่อย', '<h3>Q: เติมเงินแล้วไม่ได้ของทำยังไง?</h3><p>A: รอ 1-5 นาที หากยังไม่ได้รับของ ให้ติดต่อแอดมินผ่าน Discord พร้อมแนบสลิป</p><h3>Q: ลืมรหัสผ่านทำยังไง?</h3><p>A: ติดต่อแอดมินผ่าน Discord เพื่อรีเซ็ตรหัสผ่าน</p><h3>Q: ซื้อของแล้วไม่ได้ไอเทมในเกม?</h3><p>A: ตรวจสอบว่าคุณออนไลน์อยู่ในเซิร์ฟเวอร์ที่ถูกต้อง ระบบจะส่งให้อัตโนมัติเมื่อคุณออนไลน์</p><h3>Q: สามารถขอคืนเงินได้ไหม?</h3><p>A: สินค้าดิจิทัลไม่สามารถขอคืนเงินได้ กรุณาตรวจสอบก่อนซื้อ</p><h3>Q: กาชาหมุนแล้วได้ของเมื่อไหร่?</h3><p>A: ของจะถูกส่งอัตโนมัติทันทีเมื่อคุณออนไลน์ในเซิร์ฟเวอร์</p>'),
('about', 'เกี่ยวกับเรา', '<p>MC Sakura Store — ร้านค้าออนไลน์สำหรับเซิร์ฟเวอร์ Minecraft ของเรา</p><p>เราเปิดให้บริการตั้งแต่ปี 2024 และมุ่งมั่นที่จะให้บริการที่ดีที่สุดแก่ผู้เล่นทุกคน</p>');

-- =====================================================
-- SETTINGS
-- =====================================================
CREATE TABLE `settings` (
    `setting_key`   VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `setting_type`  ENUM('text','number','boolean','json','image','color') DEFAULT 'text',
    `category`      VARCHAR(50) DEFAULT 'general',
    `description`   VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
-- General
('site_name',        'MC Sakura Store',          'text',    'general',  'ชื่อเว็บ'),
('site_logo',        '/assets/img/logo.png',     'image',   'general',  'โลโก้'),
('site_favicon',     '/assets/img/favicon.ico',  'image',   'general',  'Favicon'),
('site_description', 'ร้านค้าเซิร์ฟเวอร์ Minecraft', 'text', 'general', 'คำอธิบายเว็บ'),
-- Theme
('primary_color',    '#8b5cf6',    'color',   'theme',    'สีหลัก'),
('secondary_color',  '#06b6d4',    'color',   'theme',    'สีรอง'),
('accent_color',     '#f59e0b',    'color',   'theme',    'สีเน้น'),
('bg_color',         '#0f0f23',    'color',   'theme',    'สีพื้นหลัง'),
('card_bg',          'rgba(255,255,255,0.05)', 'text', 'theme', 'สีพื้น card'),
('text_color',       '#e2e8f0',    'color',   'theme',    'สีตัวอักษร'),
('text_muted',       '#94a3b8',    'color',   'theme',    'สีตัวอักษรจาง'),
('border_color',     'rgba(255,255,255,0.1)', 'text', 'theme', 'สีเส้นขอบ'),
('border_radius',    '16',         'number',  'theme',    'ความโค้งขอบ (px)'),
('glass_blur',       '20',         'number',  'theme',    'ความเบลอ glass (px)'),
('font_family',      "'Noto Sans Thai', 'Inter', sans-serif", 'text', 'theme', 'Font'),
('bg_image',         '',           'image',   'theme',    'รูปพื้นหลัง'),
-- Payment
('currency_symbol',  '฿',          'text',    'payment',  'สัญลักษณ์เงิน'),
('currency_name',    'บาท',        'text',    'payment',  'ชื่อสกุลเงิน'),
('min_topup',        '10',         'number',  'payment',  'เติมขั้นต่ำ'),
('max_topup',        '10000',      'number',  'payment',  'เติมสูงสุด'),
-- Features
('gacha_enabled',    '1',          'boolean', 'features', 'เปิด/ปิดระบบกาชา'),
('redeem_enabled',   '1',          'boolean', 'features', 'เปิด/ปิดระบบ Redeem'),
('register_enabled', '1',          'boolean', 'features', 'เปิด/ปิดสมัครสมาชิก'),
('topup_enabled',    '1',          'boolean', 'features', 'เปิด/ปิดเติมเงิน'),
('contact_enabled',  '1',          'boolean', 'features', 'เปิด/ปิดติดต่อ'),
('welcome_enabled',  '1',          'boolean', 'features', 'บังคับหน้า Welcome'),
-- Server
('server_ip',        'play.mcsakura.com', 'text', 'server', 'IP เซิร์ฟเวอร์'),
('server_port',      '25565',      'number',  'server',   'Port เซิร์ฟเวอร์'),
-- Social
('discord_invite',   'https://discord.gg/example', 'text', 'social', 'ลิงก์ Discord'),
('discord_webhook',  '',           'text',    'notify',   'Discord Webhook URL'),
-- System
('maintenance_mode', '0',          'boolean', 'system',   'โหมดปิดปรับปรุง'),
('maintenance_msg',  'กำลังปรับปรุงระบบ กรุณารอสักครู่...', 'text', 'system', 'ข้อความปิดปรับปรุง'),
-- Auth
('auth_mode',        'plugin',     'text',    'auth',     'โหมด: plugin/standalone'),
('auth_table',       'authme',     'text',    'auth',     'ชื่อตาราง plugin'),
('auth_col_username','username',   'text',    'auth',     'คอลัมน์ username'),
('auth_col_password','password',   'text',    'auth',     'คอลัมน์ password'),
('auth_col_email',   'email',      'text',    'auth',     'คอลัมน์ email'),
('auth_col_ip',      'ip',         'text',    'auth',     'คอลัมน์ IP'),
('auth_col_regdate', 'regdate',    'text',    'auth',     'คอลัมน์วันสมัคร'),
('auth_hash',        'SHA256',     'text',    'auth',     'Hash: SHA256/BCRYPT/ARGON2/PBKDF2'),
-- Plugin API
('plugin_api_secret','ChangeThisSecretKey123!', 'text', 'plugin', 'HMAC Secret Key'),
('plugin_poll_limit','10',         'number',  'plugin',   'จำนวน delivery ต่อ poll'),
('plugin_timestamp_tolerance','60','number',  'plugin',   'Tolerance timestamp (วินาที)'),
-- TrueWallet Proxy
('tw_proxy_url',     '',           'text',    'payment',  'TrueWallet Proxy API URL (เว้นว่าง = ไม่ใช้)'),
('tw_proxy_key',     '',           'text',    'payment',  'TrueWallet Proxy API Key');

-- =====================================================
-- USER SETTINGS
-- =====================================================
CREATE TABLE `user_settings` (
    `username`      VARCHAR(255) NOT NULL,
    `setting_key`   VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    PRIMARY KEY (`username`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOG
-- =====================================================
CREATE TABLE `audit_log` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(255) DEFAULT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `target`     VARCHAR(255) DEFAULT NULL,
    `detail`     TEXT DEFAULT NULL,
    `ip`         VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RATE LIMIT
-- =====================================================
CREATE TABLE `rate_limits` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL COMMENT 'IP หรือ username',
    `action`     VARCHAR(100) NOT NULL,
    `attempts`   INT DEFAULT 1,
    `window_start` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_identifier_action` (`identifier`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT ADMIN ACCOUNT
-- password: admin123 (SHA256 AuthMe format)
-- =====================================================
INSERT INTO `authme` (`username`, `realname`, `password`, `email`, `regdate`) VALUES
('admin', 'Admin', '$SHA$7a4c510e37e3e924$a32601dd24814ab6308ab1264e5309c06369c21a1d97e4db1f77750a27f1d594', 'admin@mcstore.com', UNIX_TIMESTAMP());

INSERT INTO `users` (`username`, `email`, `role`) VALUES
('admin', 'admin@mcstore.com', 'admin');

-- =====================================================
-- SAMPLE BANNERS
-- =====================================================
INSERT INTO `banners` (`title`, `image`, `link`, `display_order`, `is_active`) VALUES
('ยินดีต้อนรับสู่ MC Sakura Store!', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200&h=400&fit=crop', '/mcstore/shop', 1, 1),
('โปรโมชั่นเดือนนี้ ลด 30%!',        'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=1200&h=400&fit=crop', '/mcstore/shop', 2, 1),
('กาชาใหม่! ลุ้นดาบ Legendary',      'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&h=400&fit=crop', '/mcstore/gacha', 3, 1);

-- =====================================================
-- SAMPLE PRODUCTS
-- =====================================================
INSERT INTO `products` (`server_id`, `category_id`, `name`, `description`, `image`, `price`, `original_price`, `command`, `stock`, `is_featured`, `display_order`) VALUES
-- Survival อาวุธ
('survival', 1, 'ดาบเพชร Sharpness V',      'ดาบเพชรสุดแกร่ง พร้อมเอนชานท์ Sharpness V',                    'https://mc-heads.net/item/diamond_sword',      150, 200,  'give {player} diamond_sword{Enchantments:[{id:sharpness,lvl:5}]} 1', -1, 1, 1),
('survival', 1, 'ดาบเนเธอไรท์ Fire Aspect', 'ดาบเนเธอไรท์พร้อม Fire Aspect II',                             'https://mc-heads.net/item/netherite_sword',     350, NULL, 'give {player} netherite_sword{Enchantments:[{id:fire_aspect,lvl:2},{id:sharpness,lvl:5}]} 1', -1, 1, 2),
('survival', 1, 'ธนู Power V',               'ธนูพลังสูงสุด Power V + Infinity',                               'https://mc-heads.net/item/bow',                 120, 150,  'give {player} bow{Enchantments:[{id:power,lvl:5},{id:infinity,lvl:1}]} 1', -1, 0, 3),
('survival', 1, 'ตรีศูล Loyalty III',        'ตรีศูลเวทย์มนตร์ Loyalty III + Channeling',                     'https://mc-heads.net/item/trident',             280, NULL, 'give {player} trident{Enchantments:[{id:loyalty,lvl:3},{id:channeling,lvl:1}]} 1', 10, 1, 4),
-- Survival ชุดเกราะ
('survival', 2, 'ชุดเกราะเพชร Protection IV',  'ชุดเกราะเพชรครบเซ็ต 4 ชิ้น Protection IV',                      'https://mc-heads.net/item/diamond_chestplate',  400, 500,  'give {player} diamond_helmet{Enchantments:[{id:protection,lvl:4}]} 1', -1, 1, 1),
('survival', 2, 'ชุดเกราะเนเธอไรท์ Full Set', 'ชุดเกราะเนเธอไรท์ครบเซ็ต สุดยอดความแข็งแกร่ง',                  'https://mc-heads.net/item/netherite_chestplate', 800, NULL, 'give {player} netherite_chestplate{Enchantments:[{id:protection,lvl:4}]} 1', 5, 1, 2),
('survival', 2, 'Elytra ปีกบิน',               'Elytra สำหรับบินได้ในเซิร์ฟเวอร์',                               'https://mc-heads.net/item/elytra',              600, 750,  'give {player} elytra 1', 20, 1, 3),
-- Survival เครื่องมือ
('survival', 3, 'พิคแอ็กซ์เพชร Efficiency V', 'พิคแอ็กซ์เพชรขุดเร็วสุด Eff V + Fortune III',                    'https://mc-heads.net/item/diamond_pickaxe',     180, NULL, 'give {player} diamond_pickaxe{Enchantments:[{id:efficiency,lvl:5},{id:fortune,lvl:3}]} 1', -1, 0, 1),
('survival', 3, 'ขวานเนเธอไรท์ Ultimate',    'ขวานเนเธอไรท์ Efficiency V + Sharpness V',                       'https://mc-heads.net/item/netherite_axe',       300, NULL, 'give {player} netherite_axe{Enchantments:[{id:efficiency,lvl:5},{id:sharpness,lvl:5}]} 1', -1, 0, 2),
-- Survival แรงค์
('survival', 4, 'VIP แรงค์',  'แรงค์ VIP — สิทธิพิเศษ /fly, /heal, สกินพิเศษ',                  'https://mc-heads.net/item/gold_ingot',  299, NULL, 'lp user {player} parent set vip', -1, 1, 1),
('survival', 4, 'MVP แรงค์',  'แรงค์ MVP — ครบทุกสิทธิ์ VIP + /god, /speed, prefix สี',          'https://mc-heads.net/item/diamond',     599, NULL, 'lp user {player} parent set mvp', -1, 1, 2),
('survival', 4, 'ELITE แรงค์','แรงค์ ELITE — สุดยอดแรงค์ ทุกสิทธิ์ + /nick, /hat',               'https://mc-heads.net/item/emerald',     999, NULL, 'lp user {player} parent set elite', -1, 1, 3),
-- Survival อื่นๆ
('survival', 5, 'เพชร x64',           '64 เพชร สำหรับคราฟต์ของต่างๆ',                                 'https://mc-heads.net/item/diamond',           50,  NULL, 'give {player} diamond 64', -1, 0, 1),
('survival', 5, 'Totem of Undying x1','โทเท็มแห่งชีวิต ป้องกันความตาย 1 ครั้ง',                        'https://mc-heads.net/item/totem_of_undying',  120, NULL, 'give {player} totem_of_undying 1', 50, 0, 2),
('survival', 5, 'Golden Apple x16',   'แอปเปิ้ลทอง 16 ชิ้น ฟื้นฟูพลังชีวิต',                          'https://mc-heads.net/item/golden_apple',      60,  80,   'give {player} golden_apple 16', -1, 0, 3),
-- SkyBlock
('skyblock', 8, 'Spawner หมู',         'วาง Mob Spawner หมูในเกาะของคุณ',                              'https://mc-heads.net/item/spawner', 200, NULL, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:pig}}} 1', 30, 1, 1),
('skyblock', 8, 'Spawner Iron Golem', 'วาง Mob Spawner Iron Golem ฟาร์มเหล็ก',                       'https://mc-heads.net/item/spawner', 500, 650,  'give {player} spawner{BlockEntityTag:{SpawnData:{id:iron_golem}}} 1', 10, 1, 2),
('skyblock', 9, 'SkyBlock VIP',       'แรงค์ VIP ใน SkyBlock — /fly, /is biome',                       'https://mc-heads.net/item/gold_block', 250, NULL, 'lp user {player} parent set sb-vip', -1, 1, 1),
-- Prison
('prison',  10, 'Prison VIP',        'แรงค์ VIP ใน Prison — ขุดเร็วขึ้น 2x, /sell all',               'https://mc-heads.net/item/gold_ingot',      199, NULL, 'lp user {player} parent set pr-vip', -1, 1, 1),
('prison',  11, 'พิคแอ็กซ์ Tier 5', 'พิคแอ็กซ์ขั้นสูง Efficiency X + Fortune V',                     'https://mc-heads.net/item/diamond_pickaxe', 350, 450,  'give {player} diamond_pickaxe{Enchantments:[{id:efficiency,lvl:10},{id:fortune,lvl:5}]} 1', -1, 1, 1),
('prison',  12, 'XP Booster 2x',    'เพิ่ม XP ขุด 2 เท่า นาน 1 ชั่วโมง',                             'https://mc-heads.net/item/experience_bottle', 50, NULL, 'booster give {player} xp 2 3600', -1, 0, 1);

-- =====================================================
-- SAMPLE GACHA CRATES
-- =====================================================
INSERT INTO `gacha_crates` (`server_id`, `name`, `description`, `image`, `price`, `crate_type`, `display_order`) VALUES
('survival', 'กล่องอาวุธลึกลับ',   'ลุ้นอาวุธตั้งแต่ธรรมดาจนถึง Legendary!',              'https://mc-heads.net/item/chest',           29, 'normal',  1),
('survival', 'กล่องเกราะมหัศจรรย์', 'ลุ้นชุดเกราะคุณภาพสูง มีโอกาสได้ Netherite!',        'https://mc-heads.net/item/ender_chest',     39, 'normal',  2),
('survival', 'กล่อง Legendary',     'กล่องพิเศษ! โอกาสสูงที่จะได้ของหายาก',               'https://mc-heads.net/item/dragon_egg',      99, 'premium', 3),
('skyblock', 'กล่อง Spawner สุ่ม',  'ลุ้น Mob Spawner ได้ตั้งแต่หมูจนถึง Blaze!',         'https://mc-heads.net/item/spawner',         49, 'normal',  1),
('prison',   'กล่องพิคแอ็กซ์',     'ลุ้นพิคแอ็กซ์ตั้งแต่เหล็กจนถึง Netherite Enchanted!','https://mc-heads.net/item/diamond_pickaxe', 35, 'normal',  1);

-- =====================================================
-- SAMPLE GACHA REWARDS (reference crate IDs: 1-5)
-- =====================================================
-- กล่องอาวุธลึกลับ (crate 1)
INSERT INTO `gacha_rewards` (`crate_id`, `name`, `image`, `rarity`, `weight`, `command`) VALUES
(1, 'ดาบไม้ Sharpness I',         'https://mc-heads.net/item/wooden_sword',    'common',    40, 'give {player} wooden_sword{Enchantments:[{id:sharpness,lvl:1}]} 1'),
(1, 'ดาบหิน Sharpness II',        'https://mc-heads.net/item/stone_sword',     'common',    30, 'give {player} stone_sword{Enchantments:[{id:sharpness,lvl:2}]} 1'),
(1, 'ดาบเหล็ก Sharpness III',     'https://mc-heads.net/item/iron_sword',      'rare',      20, 'give {player} iron_sword{Enchantments:[{id:sharpness,lvl:3}]} 1'),
(1, 'ดาบเพชร Sharpness IV',       'https://mc-heads.net/item/diamond_sword',   'epic',       8, 'give {player} diamond_sword{Enchantments:[{id:sharpness,lvl:4}]} 1'),
(1, 'ดาบเนเธอไรท์ Sharpness V',  'https://mc-heads.net/item/netherite_sword', 'legendary',  2, 'give {player} netherite_sword{Enchantments:[{id:sharpness,lvl:5},{id:fire_aspect,lvl:2}]} 1');

-- กล่องเกราะมหัศจรรย์ (crate 2)
INSERT INTO `gacha_rewards` (`crate_id`, `name`, `image`, `rarity`, `weight`, `command`) VALUES
(2, 'หมวกหนัง Protection I',       'https://mc-heads.net/item/leather_helmet',      'common',    35, 'give {player} leather_helmet{Enchantments:[{id:protection,lvl:1}]} 1'),
(2, 'เกราะเหล็ก Protection II',    'https://mc-heads.net/item/iron_chestplate',     'common',    25, 'give {player} iron_chestplate{Enchantments:[{id:protection,lvl:2}]} 1'),
(2, 'เกราะเพชร Protection III',    'https://mc-heads.net/item/diamond_chestplate',  'rare',      20, 'give {player} diamond_chestplate{Enchantments:[{id:protection,lvl:3}]} 1'),
(2, 'เกราะเพชรครบเซ็ต P.IV',      'https://mc-heads.net/item/diamond_chestplate',  'epic',      12, 'give {player} diamond_helmet{Enchantments:[{id:protection,lvl:4}]} 1'),
(2, 'เกราะเนเธอไรท์ครบเซ็ต',      'https://mc-heads.net/item/netherite_chestplate', 'mythic',    6, 'give {player} netherite_chestplate{Enchantments:[{id:protection,lvl:4}]} 1'),
(2, 'Elytra ปีกบิน',               'https://mc-heads.net/item/elytra',               'legendary', 2, 'give {player} elytra 1');

-- กล่อง Legendary (crate 3)
INSERT INTO `gacha_rewards` (`crate_id`, `name`, `image`, `rarity`, `weight`, `command`) VALUES
(3, 'เพชร x32',                    'https://mc-heads.net/item/diamond',                  'common',    25, 'give {player} diamond 32'),
(3, 'Golden Apple x8',             'https://mc-heads.net/item/golden_apple',              'rare',      20, 'give {player} golden_apple 8'),
(3, 'Enchanted Golden Apple x2',   'https://mc-heads.net/item/enchanted_golden_apple',    'rare',      15, 'give {player} enchanted_golden_apple 2'),
(3, 'Totem of Undying',            'https://mc-heads.net/item/totem_of_undying',           'epic',      15, 'give {player} totem_of_undying 1'),
(3, 'ดาบเนเธอไรท์ GOD Sword',    'https://mc-heads.net/item/netherite_sword',            'mythic',    10, 'give {player} netherite_sword{Enchantments:[{id:sharpness,lvl:5},{id:fire_aspect,lvl:2},{id:knockback,lvl:2},{id:looting,lvl:3}]} 1'),
(3, 'Beacon',                       'https://mc-heads.net/item/beacon',                    'mythic',     8, 'give {player} beacon 1'),
(3, 'Dragon Egg',                   'https://mc-heads.net/item/dragon_egg',                'legendary',  5, 'give {player} dragon_egg 1'),
(3, 'ELITE แรงค์ (30 วัน)',        'https://mc-heads.net/item/nether_star',               'legendary',  2, 'lp user {player} parent addtemp elite 30d');

-- กล่อง Spawner สุ่ม (crate 4)
INSERT INTO `gacha_rewards` (`crate_id`, `name`, `image`, `rarity`, `weight`, `command`) VALUES
(4, 'Spawner ไก่',      'https://mc-heads.net/item/spawner', 'common',    35, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:chicken}}} 1'),
(4, 'Spawner หมู',      'https://mc-heads.net/item/spawner', 'common',    25, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:pig}}} 1'),
(4, 'Spawner วัว',      'https://mc-heads.net/item/spawner', 'rare',      18, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:cow}}} 1'),
(4, 'Spawner Enderman', 'https://mc-heads.net/item/spawner', 'epic',      12, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:enderman}}} 1'),
(4, 'Spawner Iron Golem','https://mc-heads.net/item/spawner','mythic',     7, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:iron_golem}}} 1'),
(4, 'Spawner Blaze',    'https://mc-heads.net/item/spawner', 'legendary',  3, 'give {player} spawner{BlockEntityTag:{SpawnData:{id:blaze}}} 1');

-- กล่องพิคแอ็กซ์ (crate 5)
INSERT INTO `gacha_rewards` (`crate_id`, `name`, `image`, `rarity`, `weight`, `command`) VALUES
(5, 'พิคแอ็กซ์เหล็ก Eff.III',          'https://mc-heads.net/item/iron_pickaxe',      'common',    35, 'give {player} iron_pickaxe{Enchantments:[{id:efficiency,lvl:3}]} 1'),
(5, 'พิคแอ็กซ์เพชร Eff.IV',            'https://mc-heads.net/item/diamond_pickaxe',   'rare',      25, 'give {player} diamond_pickaxe{Enchantments:[{id:efficiency,lvl:4}]} 1'),
(5, 'พิคแอ็กซ์เพชร Eff.V + Fortune III','https://mc-heads.net/item/diamond_pickaxe',  'epic',      20, 'give {player} diamond_pickaxe{Enchantments:[{id:efficiency,lvl:5},{id:fortune,lvl:3}]} 1'),
(5, 'พิคแอ็กซ์เนเธอไรท์ Eff.V',        'https://mc-heads.net/item/netherite_pickaxe', 'mythic',    12, 'give {player} netherite_pickaxe{Enchantments:[{id:efficiency,lvl:5},{id:fortune,lvl:3}]} 1'),
(5, 'พิคแอ็กซ์เนเธอไรท์ GOD Pick',     'https://mc-heads.net/item/netherite_pickaxe', 'legendary',  3, 'give {player} netherite_pickaxe{Enchantments:[{id:efficiency,lvl:10},{id:fortune,lvl:5},{id:unbreaking,lvl:10}]} 1'),
(5, 'XP Booster 3x (2 ชม.)',            'https://mc-heads.net/item/experience_bottle',  'epic',       5, 'booster give {player} xp 3 7200');

-- =====================================================
-- SAMPLE REDEEM CODES
-- =====================================================
INSERT INTO `redeem_codes` (`code`, `reward_type`, `reward_value`, `server_id`, `max_uses`, `per_user_limit`, `expires_at`) VALUES
('WELCOME2024', 'balance', '50',  NULL,       100, 1, '2027-12-31 23:59:59'),
('SAKURA100',   'balance', '100', NULL,        50, 1, '2027-06-30 23:59:59'),
('FREEDIAMOND', 'item',    'give {player} diamond 16', 'survival', 200, 1, '2027-12-31 23:59:59'),
('VIPFREE',     'item',    'lp user {player} parent addtemp vip 7d', 'survival', 20, 1, '2027-03-31 23:59:59');

COMMIT;
