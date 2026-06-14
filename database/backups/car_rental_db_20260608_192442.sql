-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: car_rental_db
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
-- Current Database: `car_rental_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `car_rental_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `car_rental_db`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'manager',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Suspended',
  `perm_users` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can manage customers/KYC',
  `avatar` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `perm_fleet` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can manage cars/categories',
  `perm_bookings` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can manage bookings/reports',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin_TCF','$2y$10$jJD7MppZS5F3IonxSu/NSume3WEi6I2XM82FVVxLv0PjixeioaGIW','super_admin',1,0,'assets/uploads/1770666188_WhatsApp Image 2026-02-10 at 03.38.53.jpeg','clement.lee.jun@student.mmu.edu.my','d99e312eeaedf643d82078ea795445f6998880f8db8eacdcf200223432920e4c','2026-06-06 18:36:48',0,0),(2,'admin_Menghui','$2y$10$uFKCJToss5T19ijaZTPZ0eRz3FM346gfldsNkC/a5pc/cA92sU80C','manager',1,1,'assets/uploads/1770667061_OIP (2).webp','hoo.meng.hui@student.mmu.edu.my','a46331cfd7eb0cee180c4600a042afd70cc02f80caa98efc7471c6b7c41be4de','2026-06-08 13:22:29',1,1);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(100) NOT NULL,
  `action_type` varchar(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE',
  `car_model` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_admin` (`admin_id`),
  CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,NULL,'System Admin','UPDATE','Toyota Toyota Vios 1.5 E','Updated vehicle specifications. Current stock: 5, Price/Day: RM120','2026-05-18 14:18:41'),(2,NULL,'System Admin','UPDATE','Toyota Toyota Vios 1.5 E','Updated vehicle specifications. Current stock: 5, Price/Day: RM120','2026-05-18 14:18:44'),(3,NULL,'Admin ID: 1','UPDATE','Toyota Toyota Vios 1.5 E','Updated record details without major spec changes.','2026-05-18 14:22:40'),(4,NULL,'Admin ID: 1','UPDATE','Toyota Toyota Vios 1.5 E','Updated record details without major spec changes.','2026-05-18 14:22:44'),(5,NULL,'Admin ID: 1','UPDATE','Toyota Toyota Corolla Cross Hybrid','Modified: Uploaded new images','2026-05-29 09:08:33');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_items`
--

DROP TABLE IF EXISTS `booking_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `car_name` varchar(100) NOT NULL,
  `rental_type` enum('hourly','daily') NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Hours or Days',
  `pickup_state` varchar(50) NOT NULL,
  `pickup_location` varchar(100) NOT NULL,
  `dropoff_state` varchar(50) NOT NULL,
  `dropoff_location` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `services_cost` decimal(10,2) DEFAULT 0.00,
  `age_surcharge` decimal(10,2) DEFAULT 0.00,
  `insurance_cost` decimal(10,2) DEFAULT 0.00,
  `fuel_policy` enum('same-to-same','pre-purchase') DEFAULT 'same-to-same',
  `fuel_cost` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `driver_age` varchar(20) DEFAULT NULL,
  `insurance_level` varchar(20) DEFAULT 'basic',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_car_id` (`car_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_items`
--

LOCK TABLES `booking_items` WRITE;
/*!40000 ALTER TABLE `booking_items` DISABLE KEYS */;
INSERT INTO `booking_items` VALUES (1,101,31,'Vios 1.5 G','daily','2026-03-22 23:48:28','2026-03-23 23:48:28',1,'Johor','Toyota Showroom','Johor','Toyota Showroom',95500.00,0.00,0.00,0.00,'same-to-same',0.00,500.00,NULL,'basic','2026-03-22 15:48:28'),(2,102,29,'Hilux 2.8 Rogue','daily','2026-03-22 23:48:28','2026-03-23 23:48:28',1,'Johor','Toyota Showroom','Johor','Toyota Showroom',158880.00,0.00,0.00,0.00,'same-to-same',0.00,1500.00,NULL,'basic','2026-03-22 15:48:28'),(3,103,20,'Corolla Cross Hybrid','daily','2026-03-22 23:48:28','2026-03-23 23:48:28',1,'Johor','Toyota Showroom','Johor','Toyota Showroom',142000.00,0.00,0.00,0.00,'same-to-same',0.00,1000.00,NULL,'basic','2026-03-22 15:48:28'),(4,104,25,'Alphard 2.4T Executive Lounge','daily','2026-03-22 23:48:28','2026-03-23 23:48:28',1,'Johor','Toyota Showroom','Johor','Toyota Showroom',538000.00,0.00,0.00,0.00,'same-to-same',0.00,5000.00,NULL,'basic','2026-03-22 15:48:28'),(5,110,31,'Vios 1.5 G','daily','2026-03-22 23:51:23','2026-03-23 23:51:23',1,'Johor','Toyota Showroom','Johor','Toyota Showroom',95500.00,0.00,0.00,0.00,'same-to-same',0.00,500.00,NULL,'basic','2026-03-22 15:51:23'),(6,105,31,'Vios 1.5 G','daily','2026-03-22 23:54:54','2026-03-23 23:54:54',1,'Johor','Toyota Showroom','Johor','Toyota Showroom',95500.00,0.00,0.00,0.00,'same-to-same',0.00,500.00,NULL,'basic','2026-03-22 15:54:54'),(7,111,1,'','hourly','2026-05-20 10:00:00','2026-05-23 10:00:00',0,'Kuala Lumpur','KLIA Terminal 1','','',0.00,0.00,0.00,0.00,'same-to-same',0.00,450.00,NULL,'basic','2026-05-18 14:14:38'),(8,112,1,'Toyota Vios 1.5 G','hourly','2026-05-25 09:00:00','2026-05-28 09:00:00',3,'Kuala Lumpur','KLCC Parking','Kuala Lumpur','KLCC Parking',150.00,0.00,0.00,0.00,'same-to-same',0.00,450.00,NULL,'basic','2026-05-18 14:17:00'),(9,114,19,'Toyota Vios 1.5 G','hourly','2026-05-25 09:00:00','2026-05-28 09:00:00',3,'Kuala Lumpur','KLCC Parking','Kuala Lumpur','KLCC Parking',150.00,0.00,0.00,0.00,'same-to-same',0.00,450.00,NULL,'basic','2026-05-18 14:17:46'),(10,115,19,'Toyota Vios 1.5 G','hourly','2026-05-25 09:00:00','2026-05-28 09:00:00',3,'Kuala Lumpur','KLCC Parking','Kuala Lumpur','KLCC Parking',150.00,0.00,0.00,0.00,'same-to-same',0.00,450.00,NULL,'basic','2026-05-18 14:52:50'),(11,116,19,'Toyota Vios 1.5 G','hourly','2026-05-25 09:00:00','2026-05-28 09:00:00',3,'Kuala Lumpur','KLCC Parking','Kuala Lumpur','KLCC Parking',150.00,0.00,0.00,0.00,'same-to-same',0.00,450.00,NULL,'basic','2026-05-18 14:53:42');
/*!40000 ALTER TABLE `booking_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_services`
--

DROP TABLE IF EXISTS `booking_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_item_id` int(11) NOT NULL,
  `service_name` varchar(50) NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking_item_id` (`booking_item_id`),
  CONSTRAINT `booking_services_ibfk_1` FOREIGN KEY (`booking_item_id`) REFERENCES `booking_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_services`
--

LOCK TABLES `booking_services` WRITE;
/*!40000 ALTER TABLE `booking_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `deposit_status` enum('Pending','Captured','Refunded') NOT NULL DEFAULT 'Pending',
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `promo_code` varchar(50) DEFAULT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `booking_status` enum('Pending','Confirmed','Active','Completed','Cancelled') DEFAULT 'Pending',
  `admin_notes` text DEFAULT NULL COMMENT '管理员审核备注',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ic_document_path` varchar(255) DEFAULT NULL,
  `license_document_path` varchar(255) DEFAULT NULL COMMENT '驾照文件路径',
  `security_deposit` decimal(10,2) DEFAULT 0.00 COMMENT '租车押金',
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (101,'ORD-2026-001',110,'MENGHUI','hoomenghui120@gmail.com','0127612283','Online Banking','Pending',500.00,0.00,0.00,0.00,0.00,NULL,500.00,'Completed',NULL,'pending','2026-03-22 15:48:28',NULL,NULL,NULL),(102,'ORD-2026-002',111,'KHPANG','KH22@gmail.com','012-9997878','Credit Card','Pending',1500.00,0.00,0.00,0.00,0.00,NULL,1500.00,'Completed',NULL,'pending','2026-03-22 15:48:28',NULL,NULL,NULL),(103,'ORD-2026-003',112,'SUHAIMI','suhaimi@gmail.com','01277789876','FPX Transfer','Pending',1000.00,0.00,0.00,0.00,0.00,NULL,1000.00,'Cancelled',NULL,'pending','2026-03-22 15:48:28',NULL,NULL,NULL),(104,'ORD-2026-004',113,'SUHAIMI1','suhaimi2@gmail.com','01276136042','Credit Card','Pending',3000.00,0.00,0.00,0.00,0.00,NULL,3000.00,'Completed',NULL,'pending','2026-03-22 15:48:28',NULL,NULL,NULL),(105,'ORD-2026-008',110,'MENGHUI','hoomenghui120@gmail.com','0127612283','Online Banking','Pending',500.00,0.00,0.00,0.00,0.00,NULL,500.00,'Cancelled',NULL,'pending','2026-03-22 15:54:54',NULL,NULL,NULL),(110,'ORD-2026-010',110,'MENGHUI','hoomenghui120@gmail.com','0127612283','Online Banking','Pending',500.00,0.00,0.00,0.00,0.00,NULL,500.00,'Completed',NULL,'pending','2026-03-22 15:51:23',NULL,NULL,NULL),(111,'ORD-2026-888',1,'ALEX WONG','alex.wong@example.com','012-3456789','Credit Card','Pending',450.00,0.00,27.00,15.00,0.00,NULL,492.00,'Completed',NULL,'pending','2026-05-18 14:14:37','assets/img/dummy_ic.jpg','assets/img/dummy_license.jpg',200.00),(112,'ORD-2026-999',1,'STEVE JOBS','steve@example.com','019-8765432','Online Banking','Pending',450.00,0.00,27.00,15.00,0.00,NULL,492.00,'Cancelled',NULL,'pending','2026-05-18 14:17:00','assets/img/dummy_ic.jpg','assets/img/dummy_license.jpg',200.00),(114,'ORD-2026-998',1,'STEVE JOBS','steve@example.com','019-8765432','Online Banking','Pending',450.00,0.00,27.00,15.00,0.00,NULL,492.00,'Completed',NULL,'pending','2026-05-18 14:17:46','assets/img/dummy_ic.jpg','assets/img/dummy_license.jpg',200.00),(115,'ORD-2026-997',1,'STEVE JOBS','steve@example.com','019-8765432','Online Banking','Pending',450.00,0.00,27.00,15.00,0.00,NULL,492.00,'Completed',NULL,'pending','2026-05-18 14:52:49','assets/img/dummy_ic.jpg','assets/img/dummy_license.jpg',200.00),(116,'ORD-2026-996',1,'STEVE JOBS','steve@example.com','019-8765432','Online Banking','Pending',450.00,0.00,27.00,15.00,0.00,NULL,492.00,'Completed',NULL,'pending','2026-05-18 14:53:42','assets/img/dummy_ic.jpg','assets/img/dummy_license.jpg',200.00);
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(50) NOT NULL,
  `brand_logo` varchar(255) DEFAULT NULL COMMENT '品牌Logo图片路径',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Toyota','assets/uploads/brands/1778868553_103.png','2026-05-15 17:57:31'),(2,'Honda',NULL,'2026-05-15 17:57:31'),(3,'BMW',NULL,'2026-05-15 17:57:31'),(4,'Mercedes-Benz',NULL,'2026-05-15 17:57:31'),(5,'Porsche',NULL,'2026-05-15 17:57:31');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `car_images`
--

DROP TABLE IF EXISTS `car_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `image_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_car_id` (`car_id`),
  CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `car_images`
--

LOCK TABLES `car_images` WRITE;
/*!40000 ALTER TABLE `car_images` DISABLE KEYS */;
INSERT INTO `car_images` VALUES (84,29,'../assets/uploads/1774172306_toyota-hilux-medium-angle-front-view-600202.webp',0,'2026-03-22 09:38:26'),(87,30,'../assets/uploads/1774173857_Vios-Baru-2023-Red.png',0,'2026-03-22 10:04:17'),(88,31,'../assets/uploads/1774173961_toyota-vios-2023-indonesia-10-1.jpg',0,'2026-03-22 10:06:01'),(89,27,'../assets/uploads/1774174194_2-ignition-red.png',0,'2026-03-22 10:09:54'),(90,26,'../assets/uploads/1774174501_222_Precious-Metal_404347.avif',0,'2026-03-22 10:15:01'),(91,25,'../assets/uploads/1774174564_image-ob579.webp',0,'2026-03-22 10:16:04'),(92,24,'../assets/uploads/1774174640_toyota-fortuner-color-515265.webp',0,'2026-03-22 10:17:20'),(93,23,'../assets/uploads/1774174689_harrier-black.png',0,'2026-03-22 10:18:09'),(94,22,'../assets/uploads/1774174737_Toyota-RAV4-Plug-in-GR-SPORT-ObsidianBlueBi-tone-1250x750.jpg',0,'2026-03-22 10:18:57'),(95,21,'../assets/uploads/1774174780_Silver-Metallic-camry.png',0,'2026-03-22 10:19:40'),(97,18,'../assets/uploads/1774174963_toyota-yaris-color-420249.webp',0,'2026-03-22 10:22:43'),(98,19,'../assets/uploads/1774175234_toyota-corolla-color-849862.webp',0,'2026-03-22 10:27:14'),(99,32,'../assets/uploads/1778533633_Screenshot 2026-05-08 182722.png',0,'2026-05-11 21:07:13'),(100,20,'../assets/uploads/car_6a195791c49665.04022601.jpeg',0,'2026-05-29 09:08:33');
/*!40000 ALTER TABLE `car_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_name` varchar(100) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `image_url` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `transmission` varchar(50) DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `availability` tinyint(1) DEFAULT 1,
  `stock_quantity` int(11) NOT NULL DEFAULT 1,
  `is_popular` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `horsepower` int(11) DEFAULT NULL,
  `acceleration` decimal(4,2) DEFAULT NULL COMMENT '0-100 km/h in seconds',
  `is_tss` tinyint(1) DEFAULT 0,
  `is_tnga` tinyint(1) DEFAULT 0,
  `is_hybrid` tinyint(1) DEFAULT 0,
  `colors` varchar(255) DEFAULT '',
  `model_3d_url` varchar(500) DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=正常, 1=已软删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (18,'Toyota Yaris 1.5G','Toyota','Yaris',140.00,15.00,'https://images.unsplash.com/photo-1629897048514-3dd7414272aa?q=80&w=1000&auto=format&fit=crop','Hatchback','Automatic',5,'Gasoline',1,5,'1','Toyota Vehicle','1.5L Engine, 7-speed CVT',107,11.00,1,0,0,'Red Mica Metallic, Platinum White Pearl','',0),(19,'Toyota Corolla 1.8G','Toyota','Corolla',250.00,25.00,'https://images.unsplash.com/photo-1623345805780-8f01f714e65f?q=80&w=1000&auto=format&fit=crop','Sedan','Automatic',5,'Gasoline',1,5,'1','Toyota Vehicle','1.8L Engine, TNGA Platform',139,10.20,1,1,0,'Celestite Gray Metallic, Attitude Black','',0),(20,'Toyota Corolla Cross Hybrid','Toyota','Corolla Cross',280.00,30.00,'https://images.unsplash.com/photo-1656468019748-0d3a51052df3?q=80&w=1000&auto=format&fit=crop','SUV','Auto',5,'Hybrid',1,4,'1','Toyota Vehicle','1.8L Hybrid Engine, TNGA',122,10.50,1,1,1,'Nebula Blue Metallic, Platinum White Pearl','',0),(21,'Toyota Camry 2.5V','Toyota','Camry',400.00,40.00,'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fd?q=80&w=1000&auto=format&fit=crop','Sedan','Automatic',5,'Gasoline',1,5,'1','Toyota Vehicle','2.5L Dynamic Force Engine, 8-speed AT',209,8.30,1,1,0,'Graphite Metallic, Silver Metallic','',0),(22,'Toyota RAV4 2.5','Toyota','RAV4',450.00,45.00,'https://images.unsplash.com/photo-1581022295087-35e59dce04a0?q=80&w=1000&auto=format&fit=crop','SUV','Automatic',5,'Gasoline',1,5,'0','Toyota Vehicle','2.5L Dynamic Force, TNGA-K',207,8.10,1,1,0,'Dark Blue Mica, Red Mica','',0),(23,'Toyota Harrier 2.0 Luxury','Toyota','Harrier',600.00,60.00,'https://images.unsplash.com/photo-1678120612440-422cb434c449?q=80&w=1000&auto=format&fit=crop','SUV','Automatic',5,'Gasoline',1,5,'1','Toyota Vehicle','2.0L Dynamic Force, Direct Shift CVT',173,9.70,1,1,0,'Precious Black, Steel Blonde Metallic','',0),(24,'Toyota Fortuner 2.8 VRZ','Toyota','Fortuner',450.00,45.00,'https://images.unsplash.com/photo-1616422285623-14ff01620e1c?q=80&w=1000&auto=format&fit=crop','SUV','Automatic',7,'Diesel',1,5,'1','Toyota Vehicle','2.8L Turbo Diesel, 4WD',204,10.00,1,0,0,'Bronze Mica Metallic, Super White II','',0),(25,'Toyota Alphard 2.4T Executive Lounge','Toyota','Alphard',1000.00,100.00,'https://images.unsplash.com/photo-1698295696611-30376cefe85f?q=80&w=1000&auto=format&fit=crop','MPV','Automatic',7,'Gasoline',1,5,'1','Toyota Vehicle','2.4L Turbo, 8-speed AT',278,8.50,1,1,0,'Precious Metal, Platinum White Pearl','',0),(26,'Toyota Vellfire 2.5','Toyota','Vellfire',850.00,85.00,'https://images.unsplash.com/photo-1688628468727-4148e640523f?q=80&w=1000&auto=format&fit=crop','MPV','Automatic',7,'Gasoline',1,5,'1','Toyota Vehicle','2.5L Dual VVT-i, Super CVT-i',182,9.80,1,1,0,'Black, Precious Metal','',0),(27,'Toyota GR86','Toyota','GR86',900.00,90.00,'https://images.unsplash.com/photo-1669023030485-573b6926359e?q=80&w=1000&auto=format&fit=crop','','Automatic',4,'Gasoline',1,5,'0','','',237,6.30,0,0,0,'Ignition Red, Crystal Black Silica','../assets/model/toyota_gr_supra/scene.gltf',0),(29,'Toyota Hilux 2.8 Rogue','Toyota','Hilux',350.00,35.00,'https://images.unsplash.com/photo-1550355291-bbee04a92027?q=80&w=1000&auto=format&fit=crop','','Automatic',5,'Diesel',1,5,'1','','',204,10.50,1,0,0,'Phantom Brown Metallic, Crimson Spark Red Metallic, Super White II','../assets/model/toyota_hilux/scene.gltf',0),(30,'Toyota Vios 1.5 E','Toyota','Vios',120.00,12.00,'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=1000&auto=format&fit=crop','Sedan','Auto',5,'Gasoline',1,5,'1','Toyota Vehicle','1.5L Dual VVT-i Engine, Standard Safety',106,11.50,0,0,0,'Silver Metallic, White','',0),(31,'Toyota Vios 1.5 G','Toyota','Vios',130.00,14.00,'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=1000&auto=format&fit=crop','Sedan','Automatic',5,'Gasoline',1,4,'1','Toyota Vehicle','1.5L Dual VVT-i, TSS, Leather Seats',106,11.20,1,0,0,'Nebula Blue, Spicy Scarlet','',0),(32,'Toyota Vios 1.5 GR-S','Toyota','Vios',150.00,16.00,'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=1000&auto=format&fit=crop','Sedan','Automatic',5,'Gasoline',1,5,'1','','',106,11.00,1,0,0,'Platinum White Pearl, Red Mica','../assets/model/toyota_gr_supra/scene.gltf',0);
/*!40000 ALTER TABLE `cars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_item_id` int(11) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking_item_id` (`booking_item_id`),
  CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`booking_item_id`) REFERENCES `booking_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_contacts`
--

LOCK TABLES `emergency_contacts` WRITE;
/*!40000 ALTER TABLE `emergency_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_code_usage`
--

DROP TABLE IF EXISTS `promo_code_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_code_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promo_code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_promo_code` (`promo_code_id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `promo_code_usage_ibfk_1` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promo_code_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_code_usage`
--

LOCK TABLES `promo_code_usage` WRITE;
/*!40000 ALTER TABLE `promo_code_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_code_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `usage_limit` int(11) DEFAULT 0,
  `usage_count` int(11) DEFAULT 0,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
INSERT INTO `promo_codes` VALUES (1,'SUHAIMINO1','percentage',10.00,1000,2,'2026-02-07 23:23:54','2030-12-31 00:00:00',1);
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `ic_front_image` varchar(255) DEFAULT NULL,
  `driving_license_image` varchar(255) DEFAULT NULL,
  `kyc_status` enum('Unverified','Pending','Verified','Rejected') NOT NULL DEFAULT 'Unverified',
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'MENGHUI1202','HMH@gmail.com','051202Hmh*','01111111234',NULL,NULL,'Unverified',NULL,NULL,NULL,NULL,'2026-02-07 15:23:53','2026-02-07 15:30:28'),(109,'MENGHUI2','hoomenghui2@gmail.com','$2y$10$rfSZXT41jMRxG/ayONdkheB4o.5G9Ny.BqfS98NbCCqb7tBUXlLJK','0127612283',NULL,NULL,'Unverified','33, Jalan Badik 30','2004-03-03','D1234561','uploads/profile_pictures/user_109_1770481996.jpg','2026-02-07 16:25:28','2026-02-07 16:33:16'),(110,'MENGHUI','hoomenghui120@gmail.com','$2y$10$Y246uInPx1LmSOK3tf/5OOx1fCj4I8e6C58wmlDttx/JiaMcDGmbm','0127612283',NULL,NULL,'Unverified','33, Jalan Badik 30','2003-02-26','D1234560',NULL,'2026-02-07 19:24:01','2026-02-07 19:24:01'),(111,'KHPANG','KH22@gmail.com','$2y$10$IzH1TXRAqaKioK9.L2anp.6GO.v5cSaHBvufO.8.FxdqMpZQO32LG','012-9997878',NULL,NULL,'Unverified','35,JALAN SYIOK','2005-05-05','D1234567','uploads/profile_pictures/user_111_1770496397.jpg','2026-02-07 20:31:24','2026-02-08 00:59:35'),(112,'SUHAIMI','suhaimi@gmail.com','$2y$10$NP3ZZGaNhAkBmreCNtHrS.8bX3dvFDqg.VjeRoN5ksFij1b.CK9I6','01277789876',NULL,NULL,'Unverified','Johor Bahru','2005-02-09','D1234563',NULL,'2026-02-09 15:51:16','2026-02-09 15:51:16'),(113,'SUHAIMI1','suhaimi2@gmail.com','$2y$10$45aowrJPc7qyNW9kmNKhs.jyTDoKIzwi3ywbDQuM6Eqw.Nce1CFmO','01276136042',NULL,NULL,'Unverified','Johor Bahru','2005-02-09','D1234564',NULL,'2026-02-09 19:23:25','2026-02-10 03:28:29');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_categories`
--

DROP TABLE IF EXISTS `vehicle_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicle_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_categories`
--

LOCK TABLES `vehicle_categories` WRITE;
/*!40000 ALTER TABLE `vehicle_categories` DISABLE KEYS */;
INSERT INTO `vehicle_categories` VALUES (1,'Sedan','2026-02-07 21:21:01'),(2,'SUV','2026-02-07 21:21:01'),(3,'MPV','2026-02-07 21:21:01'),(4,'Hatchback','2026-02-07 21:21:01');
/*!40000 ALTER TABLE `vehicle_categories` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-08 19:24:45
