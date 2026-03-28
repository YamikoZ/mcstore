-- Migration: เพิ่มตาราง online_players
-- Plugin จะ INSERT/UPDATE ตารางนี้ทุกครั้งที่ผู้เล่น join/quit
-- buynow.php ใช้ตรวจว่าผู้รับออนไลน์อยู่ก่อนอนุญาตซื้อ

CREATE TABLE IF NOT EXISTS `online_players` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(36)      NOT NULL,
  `server_id`  VARCHAR(64)      NOT NULL,
  `updated_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_player_server` (`username`, `server_id`),
  KEY `idx_server` (`server_id`),
  KEY `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
