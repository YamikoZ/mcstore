-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mcstore
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target` varchar(255) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'NTHN2002','register',NULL,'New user registered','127.0.0.1','2026-03-25 11:29:51'),(2,'NTHN2002','logout',NULL,'User logged out','127.0.0.1','2026-03-25 11:55:51'),(3,'NTHN2002','login',NULL,'Login successful','127.0.0.1','2026-03-25 11:56:10'),(4,'NTHN2002','topup',NULL,'TrueWallet topup: 10 THB','127.0.0.1','2026-03-25 13:59:57'),(5,'NTHN2002','topup_request',NULL,'Promptpay request: 10 THB','127.0.0.1','2026-03-25 14:53:01'),(6,'NTHN2002','logout',NULL,'User logged out','127.0.0.1','2026-03-25 14:53:37'),(7,'NTHN2002','login',NULL,'Login successful','127.0.0.1','2026-03-25 14:53:43'),(8,'NTHN2002','admin_topup_approve',NULL,'Topup #4 amount=10.00 user=NTHN2002','127.0.0.1','2026-03-25 14:55:26'),(9,'NTHN2002','topup_request',NULL,'Promptpay request: 70 THB','127.0.0.1','2026-03-25 14:56:18'),(10,'NTHN2002','admin_topup_approve',NULL,'Topup #5 amount=70.00 user=NTHN2002','127.0.0.1','2026-03-25 14:56:40'),(11,'NTHN2002','topup_request',NULL,'Promptpay request: 70 THB (manual review)','127.0.0.1','2026-03-25 15:02:42'),(12,'NTHN2002','topup_request',NULL,'Promptpay request: 70 THB (manual review)','127.0.0.1','2026-03-25 15:02:52'),(13,'NTHN2002','admin_topup_reject',NULL,'Topup #7 amount=70.00 user=NTHN2002','127.0.0.1','2026-03-25 15:03:07'),(14,'NTHN2002','admin_topup_reject',NULL,'Topup #6 amount=70.00 user=NTHN2002','127.0.0.1','2026-03-25 15:03:10'),(15,'NTHN2002','topup_request',NULL,'Promptpay request: 70 THB (manual review)','127.0.0.1','2026-03-25 15:27:53'),(16,'NTHN2002','topup_request',NULL,'Promptpay request: 10 THB (manual review)','127.0.0.1','2026-03-25 15:42:43'),(17,'NTHN2002','topup',NULL,'Promptpay auto-topup: 10 THB ref:202603252104280361','127.0.0.1','2026-03-25 15:49:13'),(18,'NTHN2002','topup_request',NULL,'Promptpay request: 10 THB (manual review)','127.0.0.1','2026-03-25 15:49:27'),(19,'NTHN2002','topup',NULL,'Promptpay auto-topup: 70 THB ref:202603252003961006','127.0.0.1','2026-03-25 15:49:39'),(20,'NTHN2002','topup_request',NULL,'Promptpay request: 10 THB (manual review)','127.0.0.1','2026-03-25 15:54:38'),(21,'NTHN2002','topup_request',NULL,'Promptpay request: 10 THB (manual review)','127.0.0.1','2026-03-25 15:54:49'),(22,'NTHN2002','topup_request',NULL,'Promptpay request: 10 THB (manual review)','127.0.0.1','2026-03-25 15:54:58'),(23,'NTHN2002','topup',NULL,'Promptpay auto-topup: 10 THB ref:202603252104280361','127.0.0.1','2026-03-25 15:57:01'),(24,'NTHN2002','topup',NULL,'Promptpay auto-topup: 70 THB ref:202603252003961006','127.0.0.1','2026-03-25 15:57:20'),(25,'NTHN2002','login',NULL,'Login successful','127.0.0.1','2026-03-25 17:46:56'),(26,'NTHN2002','logout',NULL,'User logged out','127.0.0.1','2026-03-25 18:07:56'),(27,'NTHN2002','login',NULL,'Login successful','127.0.0.1','2026-03-25 18:08:04'),(28,'NTHN2002','admin_topup_reject',NULL,'Topup #3 amount=10.00 user=NTHN2002','127.0.0.1','2026-03-25 18:09:46'),(29,'NTHN2002','admin_topup_reject',NULL,'Topup #2 amount=10.00 user=NTHN2002','127.0.0.1','2026-03-25 18:09:48'),(30,'NTHN2002','admin_topup_reject',NULL,'Topup #1 amount=10.00 user=NTHN2002','127.0.0.1','2026-03-25 18:09:51'),(31,'NTHN2002','admin_gateway_update',NULL,'Updated gateway: truewallet','127.0.0.1','2026-03-25 18:13:35'),(32,'NTHN2002','login',NULL,'Login successful','127.0.0.1','2026-03-25 18:32:09'),(33,'NTHN2002','admin_settings_update',NULL,'Updated system settings','127.0.0.1','2026-03-26 09:30:10'),(34,'NTHN2002','admin_settings_update',NULL,'Updated system settings','127.0.0.1','2026-03-26 09:30:13'),(35,'NTHN2002','admin_settings_update',NULL,'Updated system settings','127.0.0.1','2026-03-26 09:30:35'),(36,'NTHN2002','admin_settings_update',NULL,'Updated 2 settings','127.0.0.1','2026-03-26 10:03:12'),(37,'NTHN2002','admin_settings_update',NULL,'Updated 6 settings','127.0.0.1','2026-03-26 10:03:34'),(38,'NTHN2002','admin_settings_update',NULL,'Updated 3 settings','127.0.0.1','2026-03-26 10:09:33'),(39,'NTHN2002','login',NULL,'Login successful','127.0.0.1','2026-03-26 16:00:37'),(40,'NTHN2002','admin_server_update',NULL,'Updated server: survival','127.0.0.1','2026-03-27 09:02:28'),(41,'NTHN2002','admin_product_update',NULL,'Updated product #6','127.0.0.1','2026-03-27 09:45:32'),(42,'NTHN2002','admin_settings_update',NULL,'Updated 5 settings','127.0.0.1','2026-03-27 10:00:49');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authme`
--

DROP TABLE IF EXISTS `authme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT 'your@email.com',
  `ip` varchar(45) DEFAULT NULL,
  `lastlogin` bigint(20) DEFAULT 0,
  `regip` varchar(45) DEFAULT NULL,
  `regdate` bigint(20) DEFAULT 0,
  `x` double DEFAULT 0,
  `y` double DEFAULT 0,
  `z` double DEFAULT 0,
  `world` varchar(255) DEFAULT 'world',
  `isLogged` smallint(6) DEFAULT 0,
  `hasSession` smallint(6) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authme`
--

LOCK TABLES `authme` WRITE;
/*!40000 ALTER TABLE `authme` DISABLE KEYS */;
INSERT INTO `authme` VALUES (1,'admin','Admin','$SHA$7a4c510e37e3e924$a32601dd24814ab6308ab1264e5309c06369c21a1d97e4db1f77750a27f1d594','admin@mcstore.com',NULL,0,NULL,1774434367,0,0,0,'world',0,0),(2,'','NTHN2002','$SHA$df9e351393ef43cc$f33f7f0b1597972866a7bc5d346cebeeed74a8e6f3c66380873f42f0c7de91ad','jjrocktv@gmail.com','127.0.0.1',1774438191000,NULL,1774438191000,0,0,0,'world',0,0);
/*!40000 ALTER TABLE `authme` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(500) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (1,'ยินดีต้อนรับสู่ MC Sakura Store!','https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200&h=400&fit=crop','/mcstore/shop',1,1,NULL,NULL),(2,'โปรโมชั่นเดือนนี้ ลด 30%!','https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=1200&h=400&fit=crop','/mcstore/shop',2,1,NULL,NULL),(3,'กาชาใหม่! ลุ้นดาบ Legendary','https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&h=400&fit=crop','/mcstore/gacha',3,1,NULL,NULL);
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT 'fa-box',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'survival','อาวุธ','fa-sword',1,1),(2,'survival','ชุดเกราะ','fa-shield',2,1),(3,'survival','เครื่องมือ','fa-pickaxe',3,1),(4,'survival','แรงค์/VIP','fa-crown',4,1),(5,'survival','อื่นๆ','fa-cube',5,1),(6,'creative','World Edit','fa-globe',1,1),(7,'creative','แรงค์/VIP','fa-crown',2,1),(8,'skyblock','ไอเทม','fa-gem',1,1),(9,'skyblock','แรงค์/VIP','fa-crown',2,1),(10,'prison','แรงค์/VIP','fa-crown',1,1),(11,'prison','เครื่องมือ','fa-hammer',2,1),(12,'prison','บูสเตอร์','fa-rocket',3,1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_logs`
--

DROP TABLE IF EXISTS `delivery_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `delivery_id` (`delivery_id`),
  CONSTRAINT `delivery_logs_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `delivery_queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_logs`
--

LOCK TABLES `delivery_logs` WRITE;
/*!40000 ALTER TABLE `delivery_logs` DISABLE KEYS */;
INSERT INTO `delivery_logs` VALUES (1,5,'failed','Exception: Unhandled exception executing \'give NTHN2002 stone_sword{Enchantments:[{id:sharpness,lvl:2}]} 1\' in org.bukkit.craftbukkit.command.VanillaCommandWrapper(give)','2026-03-27 08:57:02'),(2,8,'failed','Exception: Unhandled exception executing \'give NTHN2002 wooden_sword{Enchantments:[{id:sharpness,lvl:1}]} 1\' in org.bukkit.craftbukkit.command.VanillaCommandWrapper(give)','2026-03-27 08:57:02'),(3,6,'failed','Exception: Unhandled exception executing \'give NTHN2002 wooden_sword{Enchantments:[{id:sharpness,lvl:1}]} 1\' in org.bukkit.craftbukkit.command.VanillaCommandWrapper(give)','2026-03-27 08:57:02'),(4,7,'failed','Exception: Unhandled exception executing \'give NTHN2002 leather_helmet{Enchantments:[{id:protection,lvl:1}]} 1\' in org.bukkit.craftbukkit.command.VanillaCommandWrapper(give)','2026-03-27 08:57:02'),(5,9,'failed','Exception: Unhandled exception executing \'give NTHN2002 iron_sword{Enchantments:[{id:sharpness,lvl:3}]} 1\' in org.bukkit.craftbukkit.command.VanillaCommandWrapper(give)','2026-03-27 08:57:02'),(6,12,'delivered','OK','2026-03-27 09:23:55'),(7,14,'delivered','OK','2026-03-27 09:25:55'),(8,15,'delivered','OK','2026-03-27 09:34:25'),(9,18,'delivered','OK','2026-03-27 09:35:25'),(10,17,'delivered','OK','2026-03-27 09:35:25'),(11,16,'delivered','OK','2026-03-27 09:35:25'),(12,22,'delivered','OK','2026-03-27 09:37:25'),(13,20,'delivered','OK','2026-03-27 09:37:25'),(14,21,'delivered','OK','2026-03-27 09:37:25'),(15,19,'delivered','OK','2026-03-27 09:37:25'),(16,23,'delivered','OK','2026-03-27 09:37:25'),(17,27,'delivered','OK','2026-03-27 09:46:25'),(18,26,'delivered','OK','2026-03-27 09:46:25'),(19,25,'delivered','OK','2026-03-27 09:46:25'),(20,24,'delivered','OK','2026-03-27 09:46:25'),(21,28,'delivered','OK','2026-03-27 09:46:25'),(22,29,'delivered','OK','2026-03-27 10:12:56'),(23,32,'delivered','OK','2026-03-27 10:13:26'),(24,31,'delivered','OK','2026-03-27 10:13:26'),(25,30,'delivered','OK','2026-03-27 10:13:26'),(26,33,'delivered','OK','2026-03-27 10:15:24'),(27,35,'delivered','OK','2026-03-27 10:28:24'),(28,38,'delivered','OK','2026-03-27 10:28:24'),(29,36,'delivered','OK','2026-03-27 10:28:24'),(30,37,'delivered','OK','2026-03-27 10:28:24'),(31,34,'delivered','OK','2026-03-27 10:28:24'),(32,39,'delivered','OK','2026-03-27 10:28:54'),(33,42,'delivered','OK','2026-03-27 10:28:54'),(34,43,'delivered','OK','2026-03-27 10:28:54'),(35,41,'delivered','OK','2026-03-27 10:28:54'),(36,40,'delivered','OK','2026-03-27 10:28:54'),(37,47,'delivered','OK','2026-03-27 11:35:24'),(38,44,'delivered','OK','2026-03-27 11:35:24'),(39,46,'delivered','OK','2026-03-27 11:35:24'),(40,48,'delivered','OK','2026-03-27 11:35:24'),(41,45,'delivered','OK','2026-03-27 11:35:24');
/*!40000 ALTER TABLE `delivery_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_queue`
--

DROP TABLE IF EXISTS `delivery_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `server_id` varchar(50) NOT NULL,
  `player_name` varchar(255) NOT NULL,
  `command` varchar(500) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','delivered','failed','player_offline') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_server_status` (`server_id`,`status`),
  KEY `idx_username` (`username`),
  CONSTRAINT `delivery_queue_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_queue`
--

LOCK TABLES `delivery_queue` WRITE;
/*!40000 ALTER TABLE `delivery_queue` DISABLE KEYS */;
INSERT INTO `delivery_queue` VALUES (5,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 stone_sword{Enchantments:[{id:sharpness,lvl:2}]} 1','ดาบหิน Sharpness II','failed',0,10,'2026-03-25 18:34:40','2026-03-27 08:57:02'),(6,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 wooden_sword{Enchantments:[{id:sharpness,lvl:1}]} 1','ดาบไม้ Sharpness I','failed',0,10,'2026-03-25 18:34:42','2026-03-27 08:57:02'),(7,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 leather_helmet{Enchantments:[{id:protection,lvl:1}]} 1','หมวกหนัง Protection I','failed',0,10,'2026-03-26 09:00:17','2026-03-27 08:57:02'),(8,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 wooden_sword{Enchantments:[{id:sharpness,lvl:1}]} 1','ดาบไม้ Sharpness I','failed',0,10,'2026-03-26 09:07:03','2026-03-27 08:57:02'),(9,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 iron_sword{Enchantments:[{id:sharpness,lvl:3}]} 1','ดาบเหล็ก Sharpness III','failed',0,10,'2026-03-26 09:10:59','2026-03-27 08:57:02'),(12,13,'NTHN2002','survival','NTHN2002','give NTHN2002 trident[minecraft:enchantments={loyalty:3,channeling:1}] 1','ตรีศูล Loyalty III','delivered',0,10,'2026-03-27 09:23:50','2026-03-27 09:23:55'),(14,15,'NTHN2002','survival','NTHN2002','give NTHN2002 trident[minecraft:enchantments={loyalty:3,channeling:1}] 1','ตรีศูล Loyalty III','delivered',0,10,'2026-03-27 09:25:44','2026-03-27 09:25:55'),(15,16,'NTHN2002','survival','NTHN2002','give NTHN2002 trident[minecraft:enchantments={loyalty:3,channeling:1}] 1','ตรีศูล Loyalty III','delivered',0,10,'2026-03-27 09:34:21','2026-03-27 09:34:25'),(16,17,'NTHN2002','survival','NTHN2002','give NTHN2002 trident[minecraft:enchantments={loyalty:3,channeling:1}] 1','ตรีศูล Loyalty III','delivered',0,10,'2026-03-27 09:35:00','2026-03-27 09:35:25'),(17,17,'NTHN2002','survival','NTHN2002','give NTHN2002 trident[minecraft:enchantments={loyalty:3,channeling:1}] 1','ตรีศูล Loyalty III','delivered',0,10,'2026-03-27 09:35:00','2026-03-27 09:35:25'),(18,17,'NTHN2002','survival','NTHN2002','give NTHN2002 trident[minecraft:enchantments={loyalty:3,channeling:1}] 1','ตรีศูล Loyalty III','delivered',0,10,'2026-03-27 09:35:00','2026-03-27 09:35:25'),(19,18,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_helmet[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:37:20','2026-03-27 09:37:25'),(20,18,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_chestplate[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:37:20','2026-03-27 09:37:25'),(21,18,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_leggings[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:37:20','2026-03-27 09:37:25'),(22,18,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_boots[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:37:20','2026-03-27 09:37:25'),(23,18,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:37:20','2026-03-27 09:37:25'),(24,19,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_helmet[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:46:07','2026-03-27 09:46:25'),(25,19,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_chestplate[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:46:07','2026-03-27 09:46:25'),(26,19,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_leggings[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:46:07','2026-03-27 09:46:25'),(27,19,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_boots[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:46:07','2026-03-27 09:46:25'),(28,19,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 09:46:07','2026-03-27 09:46:25'),(29,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 totem_of_undying 1','Totem of Undying','delivered',0,10,'2026-03-27 10:12:45','2026-03-27 10:12:56'),(30,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 totem_of_undying 1','Totem of Undying','delivered',0,10,'2026-03-27 10:13:07','2026-03-27 10:13:26'),(31,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 golden_apple 8','Golden Apple x8','delivered',0,10,'2026-03-27 10:13:15','2026-03-27 10:13:26'),(32,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 totem_of_undying 1','Totem of Undying','delivered',0,10,'2026-03-27 10:13:24','2026-03-27 10:13:26'),(33,NULL,'NTHN2002','survival','NTHN2002','give NTHN2002 golden_apple 8','Golden Apple x8','delivered',0,10,'2026-03-27 10:15:13','2026-03-27 10:15:24'),(34,20,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_helmet[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:23','2026-03-27 10:28:24'),(35,20,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_chestplate[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:23','2026-03-27 10:28:24'),(36,20,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_leggings[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:23','2026-03-27 10:28:24'),(37,20,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_boots[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:23','2026-03-27 10:28:24'),(38,20,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:23','2026-03-27 10:28:24'),(39,21,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_helmet[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:50','2026-03-27 10:28:54'),(40,21,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_chestplate[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:50','2026-03-27 10:28:54'),(41,21,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_leggings[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:50','2026-03-27 10:28:54'),(42,21,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_boots[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:50','2026-03-27 10:28:54'),(43,21,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 10:28:50','2026-03-27 10:28:54'),(44,22,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_helmet[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 11:34:58','2026-03-27 11:35:24'),(45,22,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_chestplate[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 11:34:58','2026-03-27 11:35:24'),(46,22,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_leggings[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 11:34:58','2026-03-27 11:35:24'),(47,22,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_boots[minecraft:enchantments={protection:4}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 11:34:58','2026-03-27 11:35:24'),(48,22,'NTHN2002','survival','NTHN2002','give NTHN2002 netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1','ชุดเกราะเนเธอไรท์ Full Set','delivered',0,10,'2026-03-27 11:34:58','2026-03-27 11:35:24');
/*!40000 ALTER TABLE `delivery_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gacha_crates`
--

DROP TABLE IF EXISTS `gacha_crates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gacha_crates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `crate_type` varchar(50) DEFAULT 'normal',
  `is_active` tinyint(4) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  CONSTRAINT `gacha_crates_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gacha_crates`
--

LOCK TABLES `gacha_crates` WRITE;
/*!40000 ALTER TABLE `gacha_crates` DISABLE KEYS */;
INSERT INTO `gacha_crates` VALUES (1,'survival','กล่องอาวุธลึกลับ','ลุ้นอาวุธตั้งแต่ธรรมดาจนถึง Legendary!','https://mc-heads.net/item/chest',29.00,'normal',1,1,'2026-03-25 11:14:42'),(2,'survival','กล่องเกราะมหัศจรรย์','ลุ้นชุดเกราะคุณภาพสูง มีโอกาสได้ Netherite!','https://mc-heads.net/item/ender_chest',39.00,'normal',1,2,'2026-03-25 11:14:42'),(3,'survival','กล่อง Legendary','กล่องพิเศษ! โอกาสสูงที่จะได้ของหายาก','https://mc-heads.net/item/dragon_egg',99.00,'premium',1,3,'2026-03-25 11:14:42');
/*!40000 ALTER TABLE `gacha_crates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gacha_history`
--

DROP TABLE IF EXISTS `gacha_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gacha_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `crate_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `rarity` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `crate_id` (`crate_id`),
  KEY `reward_id` (`reward_id`),
  KEY `idx_username` (`username`),
  CONSTRAINT `gacha_history_ibfk_1` FOREIGN KEY (`crate_id`) REFERENCES `gacha_crates` (`id`),
  CONSTRAINT `gacha_history_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `gacha_rewards` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gacha_history`
--

LOCK TABLES `gacha_history` WRITE;
/*!40000 ALTER TABLE `gacha_history` DISABLE KEYS */;
INSERT INTO `gacha_history` VALUES (1,'PVPMaster',2,11,'legendary','2026-03-25 11:14:43'),(2,'BuilderGod',1,3,'rare','2026-03-25 09:14:43'),(3,'BuilderGod',2,7,'common','2026-03-25 07:14:43'),(4,'PVPMaster',3,13,'rare','2026-03-25 05:14:43'),(5,'RedstoneWiz',3,17,'mythic','2026-03-25 03:14:43'),(6,'RedstoneWiz',1,1,'common','2026-03-25 01:14:43'),(8,'Notch_TH',2,11,'legendary','2026-03-24 21:14:43'),(9,'RedstoneWiz',1,5,'legendary','2026-03-24 19:14:43'),(10,'RedstoneWiz',3,17,'mythic','2026-03-24 17:14:43'),(11,'BuilderGod',3,14,'rare','2026-03-24 15:14:43'),(12,'Notch_TH',1,3,'rare','2026-03-24 13:14:43'),(13,'CreeperSlayer',1,2,'common','2026-03-24 11:14:43'),(14,'RedstoneWiz',2,10,'mythic','2026-03-24 09:14:43'),(15,'BuilderGod',3,18,'legendary','2026-03-24 07:14:43'),(18,'NTHN2002',1,2,'common','2026-03-25 18:34:40'),(19,'NTHN2002',1,1,'common','2026-03-25 18:34:42'),(20,'NTHN2002',2,6,'common','2026-03-26 09:00:17'),(21,'NTHN2002',1,1,'common','2026-03-26 09:07:03'),(22,'NTHN2002',1,3,'rare','2026-03-26 09:10:59'),(25,'NTHN2002',3,15,'epic','2026-03-27 10:12:45'),(26,'NTHN2002',3,15,'epic','2026-03-27 10:13:07'),(27,'NTHN2002',3,13,'rare','2026-03-27 10:13:15'),(28,'NTHN2002',3,15,'epic','2026-03-27 10:13:24'),(29,'NTHN2002',3,13,'rare','2026-03-27 10:15:13');
/*!40000 ALTER TABLE `gacha_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gacha_rewards`
--

DROP TABLE IF EXISTS `gacha_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gacha_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crate_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `rarity` varchar(50) NOT NULL COMMENT 'common,rare,epic,mythic,legendary',
  `weight` int(11) NOT NULL COMMENT 'Ó©óÓ©┤Ó╣êÓ©çÓ©íÓ©▓Ó©ü Ó©óÓ©┤Ó╣êÓ©çÓ©öÓ©úÓ©¡Ó©øÓ©çÓ╣êÓ©▓Ó©ó',
  `command` varchar(500) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `crate_id` (`crate_id`),
  CONSTRAINT `gacha_rewards_ibfk_1` FOREIGN KEY (`crate_id`) REFERENCES `gacha_crates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gacha_rewards`
--

LOCK TABLES `gacha_rewards` WRITE;
/*!40000 ALTER TABLE `gacha_rewards` DISABLE KEYS */;
INSERT INTO `gacha_rewards` VALUES (1,1,'ดาบไม้ Sharpness I','https://mc-heads.net/item/wooden_sword','common',40,'give {player} wooden_sword[minecraft:enchantments={sharpness:1}] 1',1),(2,1,'ดาบหิน Sharpness II','https://mc-heads.net/item/stone_sword','common',30,'give {player} stone_sword[minecraft:enchantments={sharpness:2}] 1',1),(3,1,'ดาบเหล็ก Sharpness III','https://mc-heads.net/item/iron_sword','rare',20,'give {player} iron_sword[minecraft:enchantments={sharpness:3}] 1',1),(4,1,'ดาบเพชร Sharpness IV','https://mc-heads.net/item/diamond_sword','epic',8,'give {player} diamond_sword[minecraft:enchantments={sharpness:4}] 1',1),(5,1,'ดาบเนเธอไรท์ Sharpness V','https://mc-heads.net/item/netherite_sword','legendary',2,'give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1',1),(6,2,'หมวกหนัง Protection I','https://mc-heads.net/item/leather_helmet','common',35,'give {player} leather_helmet[minecraft:enchantments={protection:1}] 1',1),(7,2,'เกราะเหล็ก Protection II','https://mc-heads.net/item/iron_chestplate','common',25,'give {player} iron_chestplate[minecraft:enchantments={protection:2}] 1',1),(8,2,'เกราะเพชร Protection III','https://mc-heads.net/item/diamond_chestplate','rare',20,'give {player} diamond_chestplate[minecraft:enchantments={protection:3}] 1',1),(9,2,'เกราะเพชรครบเซ็ต P.IV','https://mc-heads.net/item/diamond_chestplate','epic',12,'give {player} diamond_helmet[minecraft:enchantments={protection:4}] 1',1),(10,2,'เกราะเนเธอไรท์ครบเซ็ต','https://mc-heads.net/item/netherite_chestplate','mythic',6,'give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1',1),(11,2,'Elytra ปีกบิน','https://mc-heads.net/item/elytra','legendary',2,'give {player} elytra 1',1),(12,3,'เพชร x32','https://mc-heads.net/item/diamond','common',25,'give {player} diamond 32',1),(13,3,'Golden Apple x8','https://mc-heads.net/item/golden_apple','rare',20,'give {player} golden_apple 8',1),(14,3,'Enchanted Golden Apple x2','https://mc-heads.net/item/enchanted_golden_apple','rare',15,'give {player} enchanted_golden_apple 2',1),(15,3,'Totem of Undying','https://mc-heads.net/item/totem_of_undying','epic',15,'give {player} totem_of_undying 1',1),(16,3,'ดาบเนเธอไรท์ GOD Sword','https://mc-heads.net/item/netherite_sword','mythic',10,'give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2,knockback:2,looting:3}] 1',1),(17,3,'Beacon','https://mc-heads.net/item/beacon','mythic',8,'give {player} beacon 1',1),(18,3,'Dragon Egg','https://mc-heads.net/item/dragon_egg','legendary',5,'give {player} dragon_egg 1',1),(19,3,'ELITE แรงค์ (30 วัน)','https://mc-heads.net/item/nether_star','legendary',2,'lp user {player} parent addtemp elite 30d',1),(20,4,'Spawner ไก่','https://mc-heads.net/item/spawner','common',35,'give {player} spawner[minecraft:block_entity_data={id:\"minecraft:mob_spawner\",SpawnData:{entity:{id:\"minecraft:chicken\"}}}] 1',1),(21,4,'Spawner หมู','https://mc-heads.net/item/spawner','common',25,'give {player} spawner[minecraft:block_entity_data={id:\"minecraft:mob_spawner\",SpawnData:{entity:{id:\"minecraft:pig\"}}}] 1',1),(22,4,'Spawner วัว','https://mc-heads.net/item/spawner','rare',18,'give {player} spawner[minecraft:block_entity_data={id:\"minecraft:mob_spawner\",SpawnData:{entity:{id:\"minecraft:cow\"}}}] 1',1),(23,4,'Spawner Enderman','https://mc-heads.net/item/spawner','epic',12,'give {player} spawner[minecraft:block_entity_data={id:\"minecraft:mob_spawner\",SpawnData:{entity:{id:\"minecraft:enderman\"}}}] 1',1),(24,4,'Spawner Iron Golem','https://mc-heads.net/item/spawner','mythic',7,'give {player} spawner[minecraft:block_entity_data={id:\"minecraft:mob_spawner\",SpawnData:{entity:{id:\"minecraft:iron_golem\"}}}] 1',1),(25,4,'Spawner Blaze','https://mc-heads.net/item/spawner','legendary',3,'give {player} spawner[minecraft:block_entity_data={id:\"minecraft:mob_spawner\",SpawnData:{entity:{id:\"minecraft:blaze\"}}}] 1',1),(26,5,'พิคแอ็กซ์เหล็ก Eff.III','https://mc-heads.net/item/iron_pickaxe','common',35,'give {player} iron_pickaxe[minecraft:enchantments={efficiency:3}] 1',1),(27,5,'พิคแอ็กซ์เพชร Eff.IV','https://mc-heads.net/item/diamond_pickaxe','rare',25,'give {player} diamond_pickaxe[minecraft:enchantments={efficiency:4}] 1',1),(28,5,'พิคแอ็กซ์เพชร Eff.V + Fortune III','https://mc-heads.net/item/diamond_pickaxe','epic',20,'give {player} diamond_pickaxe[minecraft:enchantments={efficiency:5,fortune:3}] 1',1),(29,5,'พิคแอ็กซ์เนเธอไรท์ Eff.V','https://mc-heads.net/item/netherite_pickaxe','mythic',12,'give {player} netherite_pickaxe[minecraft:enchantments={efficiency:5,fortune:3}] 1',1),(30,5,'พิคแอ็กซ์เนเธอไรท์ GOD Pick','https://mc-heads.net/item/netherite_pickaxe','legendary',3,'give {player} netherite_pickaxe[minecraft:enchantments={efficiency:10,fortune:5,unbreaking:10}] 1',1),(31,5,'XP Booster 3x (2 ชม.)','https://mc-heads.net/item/experience_bottle','epic',5,'booster give {player} xp 3 7200',1);
/*!40000 ALTER TABLE `gacha_rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'order,topup,delivery,redeem,system',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`username`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'SakuraAdmin','system','ยินดีต้อนรับ!','ยินดีต้อนรับสู่ MC Sakura Store ระบบพร้อมใช้งานแล้ว','/mcstore/',0,'2026-03-25 11:14:43'),(2,'Notch_TH','order','สั่งซื้อสำเร็จ','คำสั่งซื้อ #1 ถูกส่งแล้ว เข้าเกมเพื่อรับไอเทม','/mcstore/profile/deliveries',0,'2026-03-25 10:14:43'),(3,'DiamondKing','topup','เติมเงินสำเร็จ','เติมเงิน 500 บาท สำเร็จแล้ว','/mcstore/profile/wallet',0,'2026-03-25 08:14:43'),(4,'DiamondKing','delivery','ส่งไอเทมสำเร็จ','ดาบเพชร Sharpness V ถูกส่งให้คุณแล้ว','/mcstore/profile/deliveries',0,'2026-03-25 09:14:43'),(5,'SkyBlockPro','redeem','รีดีมสำเร็จ!','ใช้โค้ด WELCOME2024 รับ 50 บาท','/mcstore/redeem',0,'2026-03-25 06:14:43'),(6,'NTHN2002','success','เติมเงินสำเร็จ','เติมเงิน 10 บาท ผ่าน TrueWallet สำเร็จ','profile/wallet',1,'2026-03-25 13:59:57'),(7,'NTHN2002','เติมเงิน 10.00 ฿ สำเร็จแล้ว','topup','เติมเงินสำเร็จ',NULL,1,'2026-03-25 14:55:26'),(8,'NTHN2002','เติมเงิน 70.00 ฿ สำเร็จแล้ว','topup','เติมเงินสำเร็จ',NULL,1,'2026-03-25 14:56:40'),(9,'NTHN2002','รายการเติมเงิน 70.00 ฿ ถูกปฏิเสธ','topup','เติมเงินถูกปฏิเสธ',NULL,1,'2026-03-25 15:03:07'),(10,'NTHN2002','รายการเติมเงิน 70.00 ฿ ถูกปฏิเสธ','topup','เติมเงินถูกปฏิเสธ',NULL,1,'2026-03-25 15:03:10'),(11,'NTHN2002','success','เติมเงินสำเร็จ','เติมเงิน 10 บาท ผ่าน Promptpay สำเร็จ (อัตโนมัติ)','profile/wallet',1,'2026-03-25 15:49:13'),(12,'NTHN2002','success','เติมเงินสำเร็จ','เติมเงิน 70 บาท ผ่าน Promptpay สำเร็จ (อัตโนมัติ)','profile/wallet',1,'2026-03-25 15:49:39'),(13,'NTHN2002','success','เติมเงินสำเร็จ','เติมเงิน 10 บาท ผ่าน Promptpay สำเร็จ (อัตโนมัติ)','profile/wallet',1,'2026-03-25 15:57:01'),(14,'NTHN2002','success','เติมเงินสำเร็จ','เติมเงิน 70 บาท ผ่าน Promptpay สำเร็จ (อัตโนมัติ)','profile/wallet',1,'2026-03-25 15:57:20'),(15,'NTHN2002','รายการเติมเงิน 10.00 ฿ ถูกปฏิเสธ','topup','เติมเงินถูกปฏิเสธ',NULL,1,'2026-03-25 18:09:46'),(16,'NTHN2002','รายการเติมเงิน 10.00 ฿ ถูกปฏิเสธ','topup','เติมเงินถูกปฏิเสธ',NULL,1,'2026-03-25 18:09:48'),(17,'NTHN2002','รายการเติมเงิน 10.00 ฿ ถูกปฏิเสธ','topup','เติมเงินถูกปฏิเสธ',NULL,1,'2026-03-25 18:09:51'),(18,'NTHN2002','success','ซื้อสำเร็จ','ตรีศูล Loyalty III — 280.00 ฿','orders',1,'2026-03-27 09:23:50'),(19,'NTHN2002','success','ซื้อสำเร็จ','Spawner หมู — 200.00 ฿','orders',1,'2026-03-27 09:24:22'),(20,'NTHN2002','success','ซื้อสำเร็จ','ตรีศูล Loyalty III — 280.00 ฿','orders',1,'2026-03-27 09:25:44'),(21,'NTHN2002','success','ซื้อสำเร็จ','ตรีศูล Loyalty III x1 — 280.00 ฿','orders',1,'2026-03-27 09:34:21'),(22,'NTHN2002','success','ซื้อสำเร็จ','ตรีศูล Loyalty III x3 — 840.00 ฿','orders',1,'2026-03-27 09:35:00'),(23,'NTHN2002','success','ซื้อสำเร็จ','ชุดเกราะเนเธอไรท์ Full Set x1 — 800.00 ฿','orders',1,'2026-03-27 09:37:20'),(24,'NTHN2002','success','ซื้อสำเร็จ','ชุดเกราะเนเธอไรท์ Full Set x1 — 800.00 ฿','orders',1,'2026-03-27 09:46:07'),(25,'NTHN2002','success','ซื้อสำเร็จ','ชุดเกราะเนเธอไรท์ Full Set x1 — 800.00 ฿','orders',1,'2026-03-27 10:28:23'),(26,'NTHN2002','success','ซื้อสำเร็จ','ชุดเกราะเนเธอไรท์ Full Set x1 — 800.00 ฿','orders',1,'2026-03-27 10:28:50'),(27,'NTHN2002','success','ซื้อสำเร็จ','ชุดเกราะเนเธอไรท์ Full Set x1 — 800.00 ฿','orders',1,'2026-03-27 11:34:58');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `server_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `command` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (3,13,4,'survival','ตรีศูล Loyalty III',280.00,1,'give {player} trident[minecraft:enchantments={loyalty:3,channeling:1}] 1'),(5,15,4,'survival','ตรีศูล Loyalty III',280.00,1,'give {player} trident[minecraft:enchantments={loyalty:3,channeling:1}] 1'),(6,16,4,'survival','ตรีศูล Loyalty III',280.00,1,'give {player} trident[minecraft:enchantments={loyalty:3,channeling:1}] 1'),(7,17,4,'survival','ตรีศูล Loyalty III',280.00,3,'give {player} trident[minecraft:enchantments={loyalty:3,channeling:1}] 1'),(8,18,6,'survival','ชุดเกราะเนเธอไรท์ Full Set',800.00,1,'[\"give {player} netherite_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_boots[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1\"]'),(9,19,6,'survival','ชุดเกราะเนเธอไรท์ Full Set',800.00,1,'[\"give {player} netherite_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_boots[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1\"]'),(10,20,6,'survival','ชุดเกราะเนเธอไรท์ Full Set',800.00,1,'[\"give {player} netherite_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_boots[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1\"]'),(11,21,6,'survival','ชุดเกราะเนเธอไรท์ Full Set',800.00,1,'[\"give {player} netherite_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_boots[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1\"]'),(12,22,6,'survival','ชุดเกราะเนเธอไรท์ Full Set',800.00,1,'[\"give {player} netherite_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_boots[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1\"]');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','delivered','cancelled','refunded') DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'DiamondKing',1500.00,'delivered',NULL,'2026-03-23 11:14:43','2026-03-25 11:14:43'),(2,'DiamondKing',800.00,'delivered',NULL,'2026-03-20 11:14:43','2026-03-25 11:14:43'),(3,'SkyBlockPro',600.00,'delivered',NULL,'2026-03-24 11:14:43','2026-03-25 11:14:43'),(4,'Notch_TH',400.00,'delivered',NULL,'2026-03-22 11:14:43','2026-03-25 11:14:43'),(5,'PVPMaster',350.00,'delivered',NULL,'2026-03-21 11:14:43','2026-03-25 11:14:43'),(6,'CreeperSlayer',300.00,'delivered',NULL,'2026-03-18 11:14:43','2026-03-25 11:14:43'),(7,'BuilderGod',250.00,'delivered',NULL,'2026-03-19 11:14:43','2026-03-25 11:14:43'),(8,'RedstoneWiz',199.00,'delivered',NULL,'2026-03-17 11:14:43','2026-03-25 11:14:43'),(9,'DiamondKing',299.00,'delivered',NULL,'2026-03-25 11:14:43','2026-03-25 11:14:43'),(10,'SkyBlockPro',500.00,'delivered',NULL,'2026-03-22 11:14:43','2026-03-25 11:14:43'),(13,'NTHN2002',280.00,'',NULL,'2026-03-27 09:23:50','2026-03-27 09:23:50'),(14,'NTHN2002',200.00,'',NULL,'2026-03-27 09:24:22','2026-03-27 09:24:22'),(15,'NTHN2002',280.00,'',NULL,'2026-03-27 09:25:44','2026-03-27 09:25:44'),(16,'NTHN2002',280.00,'',NULL,'2026-03-27 09:34:21','2026-03-27 09:34:21'),(17,'NTHN2002',840.00,'',NULL,'2026-03-27 09:35:00','2026-03-27 09:35:00'),(18,'NTHN2002',800.00,'',NULL,'2026-03-27 09:37:20','2026-03-27 09:37:20'),(19,'NTHN2002',800.00,'',NULL,'2026-03-27 09:46:07','2026-03-27 09:46:07'),(20,'NTHN2002',800.00,'',NULL,'2026-03-27 10:28:23','2026-03-27 10:28:23'),(21,'NTHN2002',800.00,'',NULL,'2026-03-27 10:28:50','2026-03-27 10:28:50'),(22,'NTHN2002',800.00,'',NULL,'2026-03-27 11:34:58','2026-03-27 11:34:58');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (4,'rules','กฎเซิร์ฟเวอร์','<h3>กฎทั่วไป</h3>\r\n<ol>\r\n<li>ห้ามใช้โปรแกรมโกง (Hack/Cheat) ทุกชนิด</li>\r\n<li>ห้ามด่า พูดจาหยาบคาย ดูถูกผู้เล่นอื่น</li>\r\n<li>ห้ามสแปมข้อความ หรือโฆษณาเซิร์ฟอื่น</li>\r\n<li>ห้ามหลอกลวง/โกงผู้เล่นอื่น</li>\r\n<li>ห้ามใช้ Exploit/Bug เพื่อประโยชน์ส่วนตัว</li>\r\n<li>เคารพ Staff ทุกคน ปฏิบัติตามคำแนะนำ</li>\r\n<li>ห้ามขายของในเกมเป็นเงินจริง</li>\r\n<li>ห้ามแอบอ้างเป็น Staff หรือแอดมิน</li>\r\n<li>ห้ามใช้สกินหรือชื่อที่ไม่เหมาะสม</li>\r\n<li>การตัดสินใจของ Staff ถือเป็นที่สิ้นสุด</li>\r\n</ol>\r\n<h3>บทลงโทษ</h3>\r\n<ul>\r\n<li>ครั้งที่ 1: เตือน</li>\r\n<li>ครั้งที่ 2: แบนชั่วคราว 1 วัน</li>\r\n<li>ครั้งที่ 3: แบนชั่วคราว 7 วัน</li>\r\n<li>ครั้งที่ 4: แบนถาวร</li>\r\n</ul>',1),(5,'faq','คำถามที่พบบ่อย','<h3>Q: เติมเงินแล้วไม่ได้ของทำยังไง?</h3>\r\n<p>A: รอ 1-5 นาที หากยังไม่ได้รับของ ให้ติดต่อแอดมินผ่าน Discord พร้อมแนบสลิป</p>\r\n<h3>Q: ลืมรหัสผ่านทำยังไง?</h3>\r\n<p>A: ติดต่อแอดมินผ่าน Discord เพื่อรีเซ็ตรหัสผ่าน</p>\r\n<h3>Q: ซื้อของแล้วไม่ได้ไอเทมในเกม?</h3>\r\n<p>A: ตรวจสอบว่าคุณออนไลน์อยู่ในเซิร์ฟเวอร์ที่ถูกต้อง ระบบจะส่งให้อัตโนมัติเมื่อคุณออนไลน์</p>\r\n<h3>Q: สามารถขอคืนเงินได้ไหม?</h3>\r\n<p>A: สินค้าดิจิทัลไม่สามารถขอคืนเงินได้ กรุณาตรวจสอบก่อนซื้อ</p>\r\n<h3>Q: กาชาหมุนแล้วได้ของเมื่อไหร่?</h3>\r\n<p>A: ของจะถูกส่งอัตโนมัติทันทีเมื่อคุณออนไลน์ในเซิร์ฟเวอร์</p>',1),(6,'about','เกี่ยวกับเรา','<p>MC Sakura Store — ร้านค้าออนไลน์สำหรับเซิร์ฟเวอร์ Minecraft ของเรา</p>\r\n<p>เราเปิดให้บริการตั้งแต่ปี 2024 และมุ่งมั่นที่จะให้บริการที่ดีที่สุดแก่ผู้เล่นทุกคน</p>\r\n<h3>ทีมงาน</h3>\r\n<ul>\r\n<li><strong>Owner:</strong> SakuraAdmin</li>\r\n<li><strong>Developer:</strong> ทีมพัฒนา MCStore</li>\r\n<li><strong>Staff:</strong> ทีม Moderator ที่คอยดูแลเซิร์ฟเวอร์</li>\r\n</ul>\r\n<h3>ติดต่อเรา</h3>\r\n<p>Discord: discord.gg/mcsakura</p>',1);
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_gateways`
--

DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_gateways` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(500) DEFAULT NULL,
  `config_json` text DEFAULT NULL,
  `min_amount` decimal(10,2) DEFAULT 0.00,
  `max_amount` decimal(10,2) DEFAULT 99999.00,
  `fee_percent` decimal(5,2) DEFAULT 0.00,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_gateways`
--

LOCK TABLES `payment_gateways` WRITE;
/*!40000 ALTER TABLE `payment_gateways` DISABLE KEYS */;
INSERT INTO `payment_gateways` VALUES ('truewallet','TrueMoney Wallet','เติมเงินผ่าน TrueMoney Voucher','/assets/img/truewallet.png','{\"phone\":\"0638935401\"}',10.00,5000.00,1.00,1,1);
/*!40000 ALTER TABLE `payment_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `command` varchar(500) NOT NULL,
  `stock` int(11) DEFAULT -1 COMMENT '-1 = unlimited',
  `is_featured` tinyint(4) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `one_per_user` tinyint(4) DEFAULT 0 COMMENT '1 = ??????????? (?????/VIP)',
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`),
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'survival',1,'ดาบเพชร Sharpness V','ดาบเพชรสุดแกร่ง พร้อมเอนชานท์ Sharpness V','https://mc-heads.net/item/diamond_sword',150.00,200.00,'give {player} diamond_sword[minecraft:enchantments={sharpness:5}] 1',-1,1,1,1,'2026-03-25 11:14:42',0),(2,'survival',1,'ดาบเนเธอไรท์ Fire Aspect','ดาบเนเธอไรท์พร้อม Fire Aspect II','https://mc-heads.net/item/netherite_sword',350.00,NULL,'give {player} netherite_sword[minecraft:enchantments={fire_aspect:2,sharpness:5}] 1',-1,1,1,2,'2026-03-25 11:14:42',0),(3,'survival',1,'ธนู Power V','ธนูพลังสูงสุด Power V + Infinity','https://mc-heads.net/item/bow',120.00,150.00,'give {player} bow[minecraft:enchantments={power:5,infinity:1}] 1',-1,0,1,3,'2026-03-25 11:14:42',0),(4,'survival',1,'ตรีศูล Loyalty III','ตรีศูลเวทย์มนตร์ Loyalty III + Channeling','https://mc-heads.net/item/trident',280.00,NULL,'give {player} trident[minecraft:enchantments={loyalty:3,channeling:1}] 1',4,1,1,4,'2026-03-25 11:14:42',0),(5,'survival',2,'ชุดเกราะเพชร Protection IV','ชุดเกราะเพชรครบเซ็ต 4 ชิ้น Protection IV','https://mc-heads.net/item/diamond_chestplate',400.00,500.00,'[\"give {player} diamond_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} diamond_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} diamond_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} diamond_boots[minecraft:enchantments={protection:4}] 1\"]',-1,1,1,1,'2026-03-25 11:14:42',0),(6,'survival',2,'ชุดเกราะเนเธอไรท์ Full Set','ชุดเกราะเนเธอไรท์ครบเซ็ต สุดยอดความแข็งแกร่ง','https://mc-heads.net/item/netherite_chestplate',800.00,NULL,'[\"give {player} netherite_helmet[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_chestplate[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_leggings[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_boots[minecraft:enchantments={protection:4}] 1\",\"give {player} netherite_sword[minecraft:enchantments={sharpness:5,fire_aspect:2}] 1\"]',0,1,1,2,'2026-03-25 11:14:42',0),(7,'survival',2,'Elytra ปีกบิน','Elytra สำหรับบินได้ในเซิร์ฟเวอร์','https://mc-heads.net/item/elytra',600.00,750.00,'give {player} elytra 1',20,1,1,3,'2026-03-25 11:14:42',0),(8,'survival',3,'พิคแอ็กซ์เพชร Efficiency V','พิคแอ็กซ์เพชรขุดเร็วสุด Efficiency V + Fortune III','https://mc-heads.net/item/diamond_pickaxe',180.00,NULL,'give {player} diamond_pickaxe[minecraft:enchantments={efficiency:5,fortune:3}] 1',-1,0,1,1,'2026-03-25 11:14:42',0),(9,'survival',3,'ขวานเนเธอไรท์ Ultimate','ขวานเนเธอไรท์ Efficiency V + Sharpness V','https://mc-heads.net/item/netherite_axe',300.00,NULL,'give {player} netherite_axe[minecraft:enchantments={efficiency:5,sharpness:5}] 1',-1,0,1,2,'2026-03-25 11:14:42',0),(10,'survival',3,'เบ็ดตกปลา Luck of the Sea','เบ็ดตกปลา Luck III + Lure III','https://mc-heads.net/item/fishing_rod',80.00,100.00,'give {player} fishing_rod[minecraft:enchantments={luck_of_the_sea:3,lure:3}] 1',-1,0,1,3,'2026-03-25 11:14:42',0),(11,'survival',4,'VIP แรงค์','แรงค์ VIP — สิทธิพิเศษมากมาย /fly, /heal, สกินพิเศษ','https://mc-heads.net/item/gold_ingot',299.00,NULL,'lp user {player} parent set vip',-1,1,1,1,'2026-03-25 11:14:42',1),(12,'survival',4,'MVP แรงค์','แรงค์ MVP — ครบทุกสิทธิ์ VIP + /god, /speed, prefix สี','https://mc-heads.net/item/diamond',599.00,NULL,'lp user {player} parent set mvp',-1,1,1,2,'2026-03-25 11:14:42',1),(13,'survival',4,'ELITE แรงค์','แรงค์ ELITE — สุดยอดแรงค์ ทุกสิทธิ์ + /nick, /hat, /ptime','https://mc-heads.net/item/emerald',999.00,NULL,'lp user {player} parent set elite',-1,1,1,3,'2026-03-25 11:14:42',1),(14,'survival',5,'เพชร x64','64 เพชร สำหรับคราฟต์ของต่างๆ','https://mc-heads.net/item/diamond',50.00,NULL,'give {player} diamond 64',-1,0,1,1,'2026-03-25 11:14:42',0),(15,'survival',5,'Totem of Undying x1','โทเท็มแห่งชีวิต ป้องกันความตาย 1 ครั้ง','https://mc-heads.net/item/totem_of_undying',120.00,NULL,'give {player} totem_of_undying 1',50,0,1,2,'2026-03-25 11:14:42',0),(16,'survival',5,'Shulker Box (ว่าง)','กล่อง Shulker สำหรับเก็บของ','https://mc-heads.net/item/shulker_box',80.00,NULL,'give {player} shulker_box 1',-1,0,1,3,'2026-03-25 11:14:42',0),(17,'survival',5,'Golden Apple x16','แอปเปิ้ลทอง 16 ชิ้น ฟื้นฟูพลังชีวิต','https://mc-heads.net/item/golden_apple',60.00,80.00,'give {player} golden_apple 16',-1,0,1,4,'2026-03-25 11:14:42',0);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL COMMENT 'IP Ó©½Ó©úÓ©ÀÓ©¡ username',
  `action` varchar(100) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_identifier_action` (`identifier`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
INSERT INTO `rate_limits` VALUES (28,'127.0.0.1','buynow_2',1,'2026-03-27 11:34:58');
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redeem_codes`
--

DROP TABLE IF EXISTS `redeem_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redeem_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `reward_type` enum('balance','item','gacha') NOT NULL,
  `reward_value` text NOT NULL COMMENT 'Ó©êÓ©│Ó©ÖÓ©ºÓ©ÖÓ╣ÇÓ©çÓ©┤Ó©Ö Ó©½Ó©úÓ©ÀÓ©¡ JSON command',
  `server_id` varchar(50) DEFAULT NULL,
  `max_uses` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `per_user_limit` int(11) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redeem_codes`
--

LOCK TABLES `redeem_codes` WRITE;
/*!40000 ALTER TABLE `redeem_codes` DISABLE KEYS */;
INSERT INTO `redeem_codes` VALUES (1,'WELCOME2024','balance','50',NULL,100,0,1,'2027-12-31 16:59:59',1,'2026-03-25 11:14:42'),(2,'SAKURA100','balance','100',NULL,50,0,1,'2027-06-30 16:59:59',1,'2026-03-25 11:14:42'),(3,'FREEDIAMOND','item','give {player} diamond 16','survival',200,0,1,'2027-12-31 16:59:59',1,'2026-03-25 11:14:42'),(4,'VIPFREE','item','lp user {player} parent addtemp vip 7d','survival',20,0,1,'2027-03-31 16:59:59',1,'2026-03-25 11:14:43');
/*!40000 ALTER TABLE `redeem_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redeem_usage`
--

DROP TABLE IF EXISTS `redeem_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redeem_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_user` (`code_id`,`username`),
  CONSTRAINT `redeem_usage_ibfk_1` FOREIGN KEY (`code_id`) REFERENCES `redeem_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redeem_usage`
--

LOCK TABLES `redeem_usage` WRITE;
/*!40000 ALTER TABLE `redeem_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `redeem_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servers`
--

DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servers` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(500) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `port` int(11) DEFAULT 25565,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `last_poll` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servers`
--

LOCK TABLES `servers` WRITE;
/*!40000 ALTER TABLE `servers` DISABLE KEYS */;
INSERT INTO `servers` VALUES ('survival','Survival','เซิร์ฟเวอร์ Survival — เอาชีวิตรอดและสร้างฐาน','','play.mcsakura.com',25565,2,1,NULL);
/*!40000 ALTER TABLE `servers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('text','number','boolean','json','image','color') DEFAULT 'text',
  `category` varchar(50) DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('accent_color','#7dd3fc','color','theme','สีเน้น'),('auth_col_email','email','text','auth','คอลัมน์ email'),('auth_col_ip','ip','text','auth','คอลัมน์ IP'),('auth_col_password','password','text','auth','คอลัมน์ password'),('auth_col_regdate','regdate','text','auth','คอลัมน์วันสมัคร'),('auth_col_username','username','text','auth','คอลัมน์ username'),('auth_hash','SHA256','text','auth','Hash: SHA256/BCRYPT/ARGON2/PBKDF2'),('auth_mode','plugin','text','auth','โหมด: plugin/standalone'),('auth_table','authme','text','auth','ชื่อตาราง plugin'),('bg_color','#0a1628','color','theme','สีพื้นหลัง'),('bg_image','','image','theme','รูปพื้นหลัง'),('border_color','rgba(56,189,248,0.15)','text','theme','สีเส้นขอบ'),('border_radius','12','number','theme','ความโค้งขอบ (px)'),('card_bg','rgba(15,30,60,0.7)','text','theme','สีพื้น card'),('contact_enabled','1','boolean','features','เปิด/ปิดติดต่อ'),('currency_name','บาท','text','payment','ชื่อสกุลเงิน'),('currency_symbol','฿','text','payment','สัญลักษณ์เงิน'),('discord_invite','https://discord.gg/mcsakura','text','social','ลิงก์ Discord'),('discord_webhook','','text','notify','Discord Webhook URL'),('download_enabled','1','boolean','features','เปิด/ปิดหน้าดาวน์โหลด'),('font_family','\'Prompt\', \'Plus Jakarta Sans\', \'Noto Sans Thai\', sans-serif','text','theme','Font'),('gacha_enabled','1','boolean','features','เปิด/ปิดระบบกาชา'),('glass_blur','12','number','theme','ความเบลอ glass (px)'),('license_cache_expires','','text','general',NULL),('license_cache_reason','domain_mismatch','text','general',NULL),('license_cache_result','0','text','general',NULL),('license_cache_time','1774611380','text','general',NULL),('license_key','KEY-TEST-0001','text','general',NULL),('maintenance_allowed_ips','','text','system','IP ที่เข้าได้ตอนปิดปรับปรุง (คั่นด้วย ,)'),('maintenance_mode','0','boolean','system','โหมดปิดปรับปรุง'),('maintenance_msg','กำลังปรับปรุงระบบ กรุณารอสักครู่...','text','system','ข้อความปิดปรับปรุง'),('max_topup','10000','number','payment','เติมสูงสุด'),('min_topup','10','number','payment','เติมขั้นต่ำ'),('notify_contact_discord','1','boolean','notify','แจ้งเตือน Discord เมื่อมีข้อความติดต่อ'),('notify_order_discord','1','boolean','notify','แจ้งเตือน Discord เมื่อมีออเดอร์'),('notify_topup_discord','1','boolean','notify','แจ้งเตือน Discord เมื่อเติมเงิน'),('plugin_api_secret','ChangeThisSecretKey123!','text','plugin','HMAC Secret Key'),('plugin_poll_limit','10','number','plugin','จำนวน delivery ต่อ poll'),('plugin_timestamp_tolerance','60','number','plugin','Tolerance timestamp (วินาที)'),('primary_color','#38bdf8','color','theme','สีหลัก'),('redeem_enabled','1','boolean','features','เปิด/ปิดระบบ Redeem'),('register_enabled','1','boolean','features','เปิด/ปิดสมัครสมาชิก'),('secondary_color','#0ea5e9','color','theme','สีรอง'),('server_bedrock_port','19132','number','server','Bedrock Port'),('server_ip','play.mcsakura.com','text','server','IP เซิร์ฟเวอร์'),('server_max_players','100','number','server','จำนวนผู้เล่นสูงสุด'),('server_online_count','1','text','general',NULL),('server_port','25565','number','server','Port เซิร์ฟเวอร์'),('server_version','1.21.x','text','server','เวอร์ชันเซิร์ฟเวอร์'),('shop_enabled','1','boolean','features','เปิด/ปิดร้านค้า'),('site_description','ร้านค้าเซิร์ฟเวอร์ Minecraft','text','general','คำอธิบายเว็บ'),('site_favicon','/assets/img/favicon.ico','image','general','Favicon'),('site_logo','/assets/img/logo.png','image','general','โลโก้'),('site_name','MC Sakura Store','text','general','ชื่อเว็บ'),('site_url','','text','general',NULL),('social_facebook','','text','social','Facebook Page URL'),('social_line','','text','social','LINE Official ID'),('social_tiktok','','text','social','TikTok URL'),('social_twitter','','text','social','Twitter/X URL'),('social_youtube','','text','social','YouTube Channel URL'),('text_color','#e0f2fe','color','theme','สีตัวอักษร'),('text_muted','#94a3b8','color','theme','สีตัวอักษรจาง'),('topup_enabled','1','boolean','features','เปิด/ปิดเติมเงิน'),('topup_fee_enabled','1','boolean','payment','เปิด/ปิดค่าธรรมเนียม'),('topup_fee_max','100','number','payment','ค่าธรรมเนียมสูงสุด (บาท)'),('topup_fee_min','1','number','payment','ค่าธรรมเนียมขั้นต่ำ (บาท)'),('topup_fee_type','percent','text','payment','ประเภทค่าธรรมเนียม (percent/fixed/none)'),('topup_fee_value','5','number','payment','ค่าธรรมเนียมเติมเงิน (% หรือ บาท)'),('tw_proxy_key','McSakura2026TwApi!','text','payment','TrueWallet Proxy API Key'),('tw_proxy_url','http://82.26.104.146','text','payment','TrueWallet Proxy API URL'),('welcome_enabled','1','boolean','features','บังคับหน้า Welcome');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topup_transactions`
--

DROP TABLE IF EXISTS `topup_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topup_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `gateway_ref` varchar(255) DEFAULT NULL,
  `idempotency_key` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topup_transactions`
--

LOCK TABLES `topup_transactions` WRITE;
/*!40000 ALTER TABLE `topup_transactions` DISABLE KEYS */;
INSERT INTO `topup_transactions` VALUES (1,'NTHN2002',10.00,'promptpay','failed',NULL,NULL,'QR:สลิปธนาคาร | ref:202603252003961006=OK | QRไม่มีข้อมูลผู้รับ:รอแอดมิน','2026-03-25 15:54:38','2026-03-25 18:09:51'),(2,'NTHN2002',10.00,'promptpay','failed',NULL,NULL,'QR:สลิปธนาคาร | ref:202603252104280361=OK | QRไม่มีข้อมูลผู้รับ:รอแอดมิน','2026-03-25 15:54:49','2026-03-25 18:09:48'),(3,'NTHN2002',10.00,'promptpay','failed',NULL,NULL,'QR:สลิปธนาคาร | ref:202603252003961006=OK | QRไม่มีข้อมูลผู้รับ:รอแอดมิน','2026-03-25 15:54:58','2026-03-25 18:09:46'),(4,'NTHN2002',10.00,'promptpay','completed','202603252104280361',NULL,'QR:สลิปธนาคาร | ref:202603252104280361=OK | ยอด:10=OK','2026-03-25 15:57:01','2026-03-25 15:57:01'),(5,'NTHN2002',70.00,'promptpay','completed','202603252003961006',NULL,'QR:สลิปธนาคาร | ref:202603252003961006=OK | ยอด:70=OK','2026-03-25 15:57:20','2026-03-25 15:57:20'),(6,'NTHN2002',0.00,'truewallet','failed','019d254b1b147328729040bb8413424c9fC',NULL,'Proxy returned invalid JSON','2026-03-25 17:47:07',NULL);
/*!40000 ALTER TABLE `topup_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `username` varchar(255) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`username`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES ('NTHN2002','notifications_enabled','1');
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL COMMENT 'NULL Ó©ûÓ╣ëÓ©▓Ó╣âÓ©èÓ╣ë plugin mode',
  `authme_id` int(11) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `role` enum('user','vip','admin') DEFAULT 'user',
  `avatar_url` varchar(500) DEFAULT NULL,
  `is_banned` tinyint(4) DEFAULT 0,
  `ban_reason` varchar(500) DEFAULT NULL,
  `last_login_web` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@mcstore.com',NULL,NULL,0.00,'admin',NULL,0,NULL,NULL,'2026-03-25 10:26:07'),(2,'NTHN2002','jjrocktv@gmail.com',NULL,2,3551.00,'admin',NULL,0,NULL,'2026-03-26 16:00:37','2026-03-25 11:29:51');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_ledger`
--

DROP TABLE IF EXISTS `wallet_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference` varchar(255) DEFAULT NULL COMMENT 'Ó╣ÇÓ©èÓ╣êÓ©Ö topup#1, order#5, gacha#10',
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_ledger`
--

LOCK TABLES `wallet_ledger` WRITE;
/*!40000 ALTER TABLE `wallet_ledger` DISABLE KEYS */;
INSERT INTO `wallet_ledger` VALUES (10,'NTHN2002','debit',35.00,10214.00,'gacha#5','กาชา: กล่องพิคแอ็กซ์','2026-03-25 18:07:42'),(11,'NTHN2002','debit',35.00,10179.00,'gacha#5','กาชา: กล่องพิคแอ็กซ์','2026-03-25 18:07:44'),(12,'NTHN2002','debit',29.00,10150.00,'gacha#1','กาชา: กล่องอาวุธลึกลับ','2026-03-25 18:34:40'),(13,'NTHN2002','debit',29.00,10121.00,'gacha#1','กาชา: กล่องอาวุธลึกลับ','2026-03-25 18:34:42'),(14,'NTHN2002','debit',39.00,10082.00,'gacha#2','กาชา: กล่องเกราะมหัศจรรย์','2026-03-26 09:00:17'),(15,'NTHN2002','debit',29.00,10053.00,'gacha#1','กาชา: กล่องอาวุธลึกลับ','2026-03-26 09:07:03'),(16,'NTHN2002','debit',29.00,10024.00,'gacha#1','กาชา: กล่องอาวุธลึกลับ','2026-03-26 09:10:59'),(17,'NTHN2002','debit',49.00,9975.00,'gacha#4','กาชา: กล่อง Spawner สุ่ม','2026-03-26 09:11:10'),(18,'NTHN2002','debit',49.00,9926.00,'gacha#4','กาชา: กล่อง Spawner สุ่ม','2026-03-26 09:11:18'),(19,'NTHN2002','debit',280.00,9646.00,'order#13','ซื้อ ตรีศูล Loyalty III','2026-03-27 09:23:50'),(20,'NTHN2002','debit',200.00,9446.00,'order#14','ซื้อ Spawner หมู','2026-03-27 09:24:22'),(21,'NTHN2002','debit',280.00,9166.00,'order#15','ซื้อ ตรีศูล Loyalty III','2026-03-27 09:25:44'),(22,'NTHN2002','debit',280.00,8886.00,'order#16','ซื้อ ตรีศูล Loyalty III x1','2026-03-27 09:34:21'),(23,'NTHN2002','debit',840.00,8046.00,'order#17','ซื้อ ตรีศูล Loyalty III x3','2026-03-27 09:35:00'),(24,'NTHN2002','debit',800.00,7246.00,'order#18','ซื้อ ชุดเกราะเนเธอไรท์ Full Set x1','2026-03-27 09:37:20'),(25,'NTHN2002','debit',800.00,6446.00,'order#19','ซื้อ ชุดเกราะเนเธอไรท์ Full Set x1','2026-03-27 09:46:07'),(26,'NTHN2002','debit',99.00,6347.00,'gacha#3','กาชา: กล่อง Legendary','2026-03-27 10:12:45'),(27,'NTHN2002','debit',99.00,6248.00,'gacha#3','กาชา: กล่อง Legendary','2026-03-27 10:13:07'),(28,'NTHN2002','debit',99.00,6149.00,'gacha#3','กาชา: กล่อง Legendary','2026-03-27 10:13:15'),(29,'NTHN2002','debit',99.00,6050.00,'gacha#3','กาชา: กล่อง Legendary','2026-03-27 10:13:24'),(30,'NTHN2002','debit',99.00,5951.00,'gacha#3','กาชา: กล่อง Legendary','2026-03-27 10:15:13'),(31,'NTHN2002','debit',800.00,5151.00,'order#20','ซื้อ ชุดเกราะเนเธอไรท์ Full Set x1','2026-03-27 10:28:23'),(32,'NTHN2002','debit',800.00,4351.00,'order#21','ซื้อ ชุดเกราะเนเธอไรท์ Full Set x1','2026-03-27 10:28:50'),(33,'NTHN2002','debit',800.00,3551.00,'order#22','ซื้อ ชุดเกราะเนเธอไรท์ Full Set x1','2026-03-27 11:34:58');
/*!40000 ALTER TABLE `wallet_ledger` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-27 18:37:37
