-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: systems
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
-- SET statements removed for shared-host compatibility

--
-- Table structure for table `asset_categories`
--

DROP TABLE IF EXISTS `asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_categories`
--

LOCK TABLES `asset_categories` WRITE;
/*!40000 ALTER TABLE `asset_categories` DISABLE KEYS */;
INSERT INTO `asset_categories` VALUES (1,'Computer Equipment','Desktop computers, laptops, monitors, keyboards, etc.','2025-09-25 05:13:15'),(2,'Office Furniture','Desks, chairs, cabinets, tables','2025-09-25 05:13:15'),(3,'Laboratory Equipment','Scientific instruments, microscopes, measuring tools','2025-09-25 05:13:15'),(4,'Audio Visual Equipment','Projectors, speakers, cameras, recording devices','2025-09-25 05:13:15'),(5,'Medical Supply','Medical-grade equipment and consumables for health services','2025-09-25 05:13:15'),(6,'Sports Equipment','Balls, nets, gymnasium equipment','2025-09-25 05:13:15'),(7,'Books and References','Textbooks, reference materials, library books','2025-09-25 05:13:15'),(8,'Computer Equipment','Desktop computers, laptops, monitors, keyboards, etc.','2025-09-27 05:28:21'),(9,'Office Furniture','Tables, chairs, cabinets, shelving units','2025-09-27 05:28:21'),(10,'Laboratory Equipment','Scientific instruments, microscopes, measuring tools','2025-09-27 05:28:21'),(11,'Audio Visual Equipment','Projectors, speakers, cameras, recording devices','2025-09-27 05:28:21'),(12,'Medical Supply','Medical-grade equipment and consumables for health services','2025-09-27 05:28:21'),(13,'Sports Equipment','Balls, nets, gymnasium equipment','2025-09-27 05:28:21'),(14,'Books and References','Textbooks, reference materials, library books','2025-09-27 05:28:21');
/*!40000 ALTER TABLE `asset_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_tag_relationships`
--

DROP TABLE IF EXISTS `asset_tag_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_tag_relationships` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `tag_id` int NOT NULL,
  `assigned_by` int DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asset_tag` (`asset_id`,`tag_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_asset_tag_relationships_asset` (`asset_id`),
  KEY `idx_asset_tag_relationships_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_tag_relationships`
--

LOCK TABLES `asset_tag_relationships` WRITE;
/*!40000 ALTER TABLE `asset_tag_relationships` DISABLE KEYS */;
INSERT INTO `asset_tag_relationships` VALUES (2,3,3,NULL,'2025-09-25 07:56:19'),(3,4,3,NULL,'2025-09-25 07:56:19'),(4,4,4,NULL,'2025-09-25 07:56:19'),(5,9,2,NULL,'2025-09-25 07:56:19'),(6,6,5,NULL,'2025-09-25 07:56:19'),(8,18,8,1,'2025-09-25 09:50:24'),(9,17,6,1,'2025-09-25 09:50:34'),(10,2,4,1,'2025-09-25 09:50:47'),(11,19,6,1,'2025-09-25 10:02:17');
/*!40000 ALTER TABLE `asset_tag_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_tags`
--

DROP TABLE IF EXISTS `asset_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_tags` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `color` varchar(7) COLLATE utf8mb4_general_ci DEFAULT '#3B82F6',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by` (`created_by`),
  KEY `idx_asset_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_tags`
--

LOCK TABLES `asset_tags` WRITE;
/*!40000 ALTER TABLE `asset_tags` DISABLE KEYS */;
INSERT INTO `asset_tags` VALUES (1,'High Priority','Critical assets requiring special attention','#DC2626',1,'2025-09-25 03:20:09'),(2,'Fragile','Assets that require careful handling','#F59E0B',1,'2025-09-25 03:20:09'),(3,'Portable','Easily movable assets','#10B981',1,'2025-09-25 03:20:09'),(4,'Expensive','High-value assets requiring extra security','#7C3AED',1,'2025-09-25 03:20:09'),(5,'Outdated','Assets scheduled for replacement','#6B7280',1,'2025-09-25 03:20:09'),(6,'New','Recently acquired assets','#06B6D4',1,'2025-09-25 03:20:09'),(7,'Warranty','Assets currently under warranty','#059669',1,'2025-09-25 03:20:09'),(8,'Shared','Assets used by multiple departments','#0EA5E9',1,'2025-09-25 03:20:09'),(9,'Personal','Assets assigned to specific individuals','#8B5CF6',1,'2025-09-25 03:20:09'),(10,'Backup','Redundant or backup equipment','#84CC16',1,'2025-09-25 03:20:09'),(22,'try','','#1c020f',1,'2025-09-25 10:00:50');
/*!40000 ALTER TABLE `asset_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` int NOT NULL,
  `asset_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor','damaged') COLLATE utf8mb4_general_ci DEFAULT 'good',
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `status` enum('available','assigned','maintenance','disposed') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `qr_code` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_generated` tinyint(1) DEFAULT '0',
  `current_value` decimal(15,2) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int DEFAULT NULL,
  `archive_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `archive_notes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `assigned_to` (`assigned_to`),
  KEY `idx_assets_qr_code` (`qr_code`),
  KEY `idx_assets_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (2,'PC-2024-002','Desktop Computer - Lab 2','Dell OptiPlex 3080 for Computer Lab','1','damaged','Computer Lab 1',NULL,'0000-00-00',0.00,'maintenance','2025-09-25 03:39:37','2025-09-29 04:59:37',NULL,0,35000.00,NULL,NULL,NULL,NULL),(3,'LAP-2024-001','Laptop - Teacher','HP EliteBook 840 G8 for faculty use','1','good','Faculty Office 205',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 07:56:19','QR_LAP-2024-001_1758771577',1,45000.00,NULL,NULL,NULL,NULL),(4,'LAP-2024-002','Laptop - Administration','Lenovo ThinkPad X1 Carbon for admin staff','1','good','Admin Office',NULL,NULL,NULL,'available','2025-09-25 03:39:37','2025-09-27 06:10:28',NULL,0,50000.00,NULL,NULL,NULL,NULL),(5,'MON-2024-001','Monitor - 24 inch','Dell UltraSharp U2419H','1','good','Computer Lab 1',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 07:56:19',NULL,0,15000.00,NULL,NULL,NULL,NULL),(6,'OF-2024-001','Executive Office Chair','Ergonomic chair for principal office','2','good','Principal Office',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 07:56:19',NULL,0,12000.00,NULL,NULL,NULL,NULL),(7,'OF-2024-002','Student Desk','Standard classroom desk','2','good','Classroom 101',NULL,NULL,NULL,'available','2025-09-25 03:39:37','2026-02-03 06:56:42',NULL,0,8000.00,NULL,NULL,NULL,NULL),(8,'OF-2024-003','Teacher Desk','Large wooden desk for faculty','2','good','Classroom 205',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 07:56:19','QR_OF-2024-003_1758778547',1,15000.00,NULL,NULL,NULL,NULL),(9,'LAB-2024-001','Microscope - Compound','Binocular compound microscope','3','good','Science Lab A',NULL,NULL,NULL,'available','2025-09-25 03:39:37','2025-09-25 07:56:19','QR_LAB-2024-001_1758771577',1,25000.00,NULL,NULL,NULL,NULL),(11,'AV-2024-001','Projector - Conference Room','LED Projector 4K for presentations',NULL,'good','Conference Room A',NULL,NULL,NULL,'available','2025-09-25 03:39:37','2025-09-25 03:39:37','QR_AV-2024-001_1758771577',1,NULL,NULL,NULL,NULL,NULL),(12,'AV-2024-002','Smart TV - 55 inch','Interactive smart TV for classroom',NULL,'good','Classroom 301',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 03:39:37',NULL,0,NULL,NULL,NULL,NULL,NULL),(13,'SP-2024-001','Basketball Hoop - Outdoor','Adjustable basketball hoop system',NULL,'good','Basketball Court',NULL,NULL,NULL,'available','2025-09-25 03:39:37','2025-09-25 03:39:37',NULL,0,NULL,NULL,NULL,NULL,NULL),(14,'SP-2024-002','Volleyball Net System','Professional volleyball net with poles',NULL,'good','Gymnasium',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 03:39:37',NULL,0,NULL,NULL,NULL,NULL,NULL),(15,'MU-2024-001','Digital Piano','Weighted key digital piano',NULL,'good','Music Room',NULL,NULL,NULL,'assigned','2025-09-25 03:39:37','2025-09-25 03:39:37','QR_MU-2024-001_1758771577',1,NULL,NULL,NULL,NULL,NULL),(17,'TEST-1758793457689','Test Asset via API','Testing asset creation',NULL,'good','Test Location',NULL,'0000-00-00',200.00,'available','2025-09-25 09:44:17','2025-09-25 09:50:34',NULL,0,180.00,NULL,NULL,NULL,NULL),(18,'TEST-1758793459704','Test Asset via API','Testing asset creation',NULL,'good','Test Location',NULL,'0000-00-00',200.00,'available','2025-09-25 09:44:19','2025-09-25 09:50:24',NULL,0,180.00,NULL,NULL,NULL,NULL),(19,'TEST001','Air Jordan 1 Retro','Jordan da Goat','7','excellent','Malacanyang',1,'2025-09-25',12300.00,'assigned','2025-09-25 10:02:17','2025-09-27 04:51:45',NULL,0,15000.00,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_history`
--

DROP TABLE IF EXISTS `assignment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_history` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL,
  `asset_id` int NOT NULL,
  `event_type` enum('request_submitted','request_reviewed','assignment_created','asset_issued','transfer_initiated','transfer_completed','returned','maintenance_linked','maintenance_completed','status_updated') COLLATE utf8mb4_general_ci NOT NULL,
  `actor_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_history`
--

LOCK TABLES `assignment_history` WRITE;
/*!40000 ALTER TABLE `assignment_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_maintenance_links`
--

DROP TABLE IF EXISTS `assignment_maintenance_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_maintenance_links` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL,
  `maintenance_id` int NOT NULL,
  `link_type` enum('preventive','corrective','inspection') COLLATE utf8mb4_general_ci DEFAULT 'preventive',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_maintenance_links`
--

LOCK TABLES `assignment_maintenance_links` WRITE;
/*!40000 ALTER TABLE `assignment_maintenance_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_maintenance_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_requests`
--

DROP TABLE IF EXISTS `assignment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_requests` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `asset_id` int NOT NULL,
  `purpose` text COLLATE utf8mb4_general_ci,
  `justification` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `approver_signature` longtext COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_requests`
--

LOCK TABLES `assignment_requests` WRITE;
/*!40000 ALTER TABLE `assignment_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_findings`
--

DROP TABLE IF EXISTS `audit_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_findings` (
  `id` int NOT NULL,
  `audit_id` int NOT NULL,
  `asset_id` int DEFAULT NULL,
  `finding_type` enum('missing','damaged','location_mismatch','data_error','unauthorized_use') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `corrective_action` text COLLATE utf8mb4_general_ci,
  `responsible_person` int DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_general_ci DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  KEY `asset_id` (`asset_id`),
  KEY `responsible_person` (`responsible_person`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_findings`
--

LOCK TABLES `audit_findings` WRITE;
/*!40000 ALTER TABLE `audit_findings` DISABLE KEYS */;
INSERT INTO `audit_findings` VALUES (1,1,NULL,'missing','Computer monitor not found in expected location','medium','Search other locations and update asset location',NULL,'2024-02-01','open','2025-09-29 05:21:39'),(2,3,NULL,'','Asset PC-2024-002 found in Computer Lab 1. Condition: good. Notes: Asset verified during audit','low','Asset verified and location updated',NULL,NULL,'open','2025-09-29 05:30:38'),(3,3,NULL,'missing','Laptop LAP-2024-001 is missing from Faculty Office 205. Last seen 2 weeks ago.','high','Search other locations and contact last known user',NULL,'2024-02-15','open','2025-09-29 05:30:48');
/*!40000 ALTER TABLE `audit_findings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custodian_transfers`
--

DROP TABLE IF EXISTS `custodian_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custodian_transfers` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL,
  `asset_id` int NOT NULL,
  `from_custodian_id` int NOT NULL,
  `to_custodian_id` int NOT NULL,
  `initiated_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `transfer_reason` text COLLATE utf8mb4_general_ci,
  `transfer_notes` text COLLATE utf8mb4_general_ci,
  `from_signature` longtext COLLATE utf8mb4_general_ci,
  `to_signature` longtext COLLATE utf8mb4_general_ci,
  `approver_signature` longtext COLLATE utf8mb4_general_ci,
  `status` enum('pending','approved','declined','completed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `transfer_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custodian_transfers`
--

LOCK TABLES `custodian_transfers` WRITE;
/*!40000 ALTER TABLE `custodian_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `custodian_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custodians`
--

DROP TABLE IF EXISTS `custodians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custodians` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `employee_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `office_location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  `status` enum('active','inactive','transferred') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  KEY `supervisor_id` (`supervisor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custodians`
--

LOCK TABLES `custodians` WRITE;
/*!40000 ALTER TABLE `custodians` DISABLE KEYS */;
INSERT INTO `custodians` VALUES (1,NULL,'EMP001','IT Department','Systems Administrator',NULL,NULL,NULL,'active','2025-09-27 05:28:28'),(2,102,'TEST001','Test Dept','Test Position',NULL,NULL,NULL,'active','2025-09-27 06:02:43');
/*!40000 ALTER TABLE `custodians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `damaged_items`
--

DROP TABLE IF EXISTS `damaged_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `damaged_items` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `asset_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `damage_type` enum('physical','electrical','software','wear','accident','vandalism','other') COLLATE utf8mb4_general_ci NOT NULL,
  `severity_level` enum('minor','moderate','major','total') COLLATE utf8mb4_general_ci NOT NULL,
  `damage_date` date NOT NULL,
  `reported_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `current_location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estimated_repair_cost` decimal(12,2) DEFAULT NULL,
  `damage_description` text COLLATE utf8mb4_general_ci,
  `damage_photos` text COLLATE utf8mb4_general_ci,
  `status` enum('reported','under_repair','repaired','write_off') COLLATE utf8mb4_general_ci DEFAULT 'reported',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `idx_asset_code` (`asset_code`),
  KEY `idx_status` (`status`),
  KEY `idx_damage_date` (`damage_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `damaged_items`
--

LOCK TABLES `damaged_items` WRITE;
/*!40000 ALTER TABLE `damaged_items` DISABLE KEYS */;
INSERT INTO `damaged_items` VALUES (1,2,'PC-2024-002','physical','moderate','2024-01-15','John Doe','Computer Lab 1',150.00,'Screen cracked on the left corner',NULL,'reported','2025-09-29 04:59:37','2025-09-29 04:59:37'),(2,2,'PC-2024-002','physical','moderate','2024-01-15','John Doe','Computer Lab 1',150.00,'Screen cracked on the left corner',NULL,'repaired','2025-09-29 05:10:38','2025-09-29 05:13:38');
/*!40000 ALTER TABLE `damaged_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_schedules`
--

DROP TABLE IF EXISTS `maintenance_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_schedules` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `maintenance_type` enum('preventive','corrective','emergency') COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `estimated_duration` decimal(4,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedules`
--

LOCK TABLES `maintenance_schedules` WRITE;
/*!40000 ALTER TABLE `maintenance_schedules` DISABLE KEYS */;
INSERT INTO `maintenance_schedules` VALUES (1,2,'preventive','2025-01-15','2025-09-29',6,'Updated routine maintenance check',NULL,3.50,1000.00,'completed','medium','Added some notes during editing','2025-09-29 04:40:39'),(2,13,'preventive','2025-09-29',NULL,11,'yes',1000.00,NULL,NULL,'cancelled','medium',NULL,'2025-09-29 04:42:25'),(3,13,'preventive','2025-09-29',NULL,NULL,'yes',20000.00,NULL,NULL,'scheduled','high',NULL,'2025-09-29 04:43:21'),(4,13,'corrective','2025-01-20',NULL,6,'Fix laptop keyboard issue',200.00,NULL,NULL,'scheduled','medium','Customer reported urgent issue with multiple keys not working','2025-09-29 04:47:52');
/*!40000 ALTER TABLE `maintenance_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procurement_request_items`
--

DROP TABLE IF EXISTS `procurement_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `procurement_request_items` (
  `id` int NOT NULL,
  `request_id` int NOT NULL,
  `item_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `quantity` int NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estimated_unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `specifications` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procurement_request_items`
--

LOCK TABLES `procurement_request_items` WRITE;
/*!40000 ALTER TABLE `procurement_request_items` DISABLE KEYS */;
INSERT INTO `procurement_request_items` VALUES (1,1,'Desktop Computer','Dell OptiPlex or equivalent',5,'piece',35000.00,175000.00,'Intel i5, 8GB RAM, 256GB SSD','2025-09-29 05:37:55'),(2,1,'Monitor','24-inch LED Monitor',5,'piece',12000.00,60000.00,'Full HD, IPS panel','2025-09-29 05:37:55'),(3,2,'A4 Paper','White bond paper',100,'ream',250.00,25000.00,NULL,'2025-09-29 05:38:05'),(4,2,'Ballpen','Blue ink ballpen',50,'piece',15.00,750.00,NULL,'2025-09-29 05:38:05'),(5,3,'car','',1,'piece',10000000.00,10000000.00,'','2025-09-29 05:44:05');
/*!40000 ALTER TABLE `procurement_request_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procurement_requests`
--

DROP TABLE IF EXISTS `procurement_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `procurement_requests` (
  `id` int NOT NULL,
  `request_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `request_type` enum('asset','supply','service') COLLATE utf8mb4_general_ci NOT NULL,
  `requestor_id` int NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `request_date` date NOT NULL,
  `required_date` date DEFAULT NULL,
  `justification` text COLLATE utf8mb4_general_ci,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `approved_cost` decimal(15,2) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `status` enum('draft','submitted','approved','rejected','ordered','received','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `approved_by` int DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_code` (`request_code`),
  KEY `requestor_id` (`requestor_id`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procurement_requests`
--

LOCK TABLES `procurement_requests` WRITE;
/*!40000 ALTER TABLE `procurement_requests` DISABLE KEYS */;
INSERT INTO `procurement_requests` VALUES (1,'AS-202509-001','asset',1,'IT Department','2024-01-15','2024-02-15','Need new computers for the computer lab upgrade',235000.00,230000.00,'high','approved',1,'2025-09-29','Approved with slight cost reduction','2025-09-29 05:37:55'),(2,'SU-202509-001','supply',2,'Office Management','2024-01-20',NULL,'Monthly office supplies replenishment',25750.00,NULL,'medium','draft',NULL,NULL,NULL,'2025-09-29 05:38:05'),(3,'AS-202509-002','asset',1,'Marcos Admin','2025-09-29','2025-10-02','Need to resign',10000000.00,NULL,'urgent','draft',NULL,NULL,'bad','2025-09-29 05:44:05');
/*!40000 ALTER TABLE `procurement_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_assignments`
--

DROP TABLE IF EXISTS `property_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_assignments` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `custodian_id` int NOT NULL,
  `assigned_by` int DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `assignment_purpose` text COLLATE utf8mb4_general_ci,
  `conditions` text COLLATE utf8mb4_general_ci,
  `acknowledgment_signed` tinyint(1) DEFAULT '0',
  `acknowledgment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','returned','transferred','lost') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `approved_by` int DEFAULT NULL,
  `approved_signature` longtext COLLATE utf8mb4_general_ci,
  `approved_at` datetime DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `issuer_signature` longtext COLLATE utf8mb4_general_ci,
  `issued_at` datetime DEFAULT NULL,
  `current_custodian_id` int DEFAULT NULL,
  `maintenance_status` enum('on_schedule','requires_attention','overdue','completed') COLLATE utf8mb4_general_ci DEFAULT 'on_schedule',
  `transfer_status` enum('none','transfer_pending','transfer_completed') COLLATE utf8mb4_general_ci DEFAULT 'none',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_assignments`
--

LOCK TABLES `property_assignments` WRITE;
/*!40000 ALTER TABLE `property_assignments` DISABLE KEYS */;
INSERT INTO `property_assignments` VALUES (1,4,1,1,'2025-09-27',NULL,'2025-09-27','Testing assignment',NULL,0,'2025-09-27 06:10:28','returned',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'on_schedule','none',NULL,'2025-09-27 06:09:10');
/*!40000 ALTER TABLE `property_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_audits`
--

DROP TABLE IF EXISTS `property_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_audits` (
  `id` int NOT NULL,
  `audit_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `audit_type` enum('physical_inventory','financial_audit','compliance_check') COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `auditor_id` int DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'planned',
  `total_assets_audited` int DEFAULT '0',
  `discrepancies_found` int DEFAULT '0',
  `summary` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_audits`
--

LOCK TABLES `property_audits` WRITE;
/*!40000 ALTER TABLE `property_audits` DISABLE KEYS */;
INSERT INTO `property_audits` VALUES (1,'AUD-2025-1757','physical_inventory','2024-01-15','2024-01-30',1,'IT Department - Updated','completed',25,2,'Quarterly physical inventory audit for IT equipment - COMPLETED','2025-09-29 05:20:33'),(3,'AUD-2025-1393','financial_audit','2025-09-29',NULL,1,'Administration','in_progress',0,0,'Updated summary for administration audit','2025-09-29 05:23:50');
/*!40000 ALTER TABLE `property_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_issuances`
--

DROP TABLE IF EXISTS `property_issuances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_issuances` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `employee_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_general_ci,
  `remarks` text COLLATE utf8mb4_general_ci,
  `status` enum('issued','returned','overdue','damaged') COLLATE utf8mb4_general_ci DEFAULT 'issued',
  `issued_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_issuances`
--

LOCK TABLES `property_issuances` WRITE;
/*!40000 ALTER TABLE `property_issuances` DISABLE KEYS */;
INSERT INTO `property_issuances` VALUES (5,4,'EMP001','Maria Santos','administration','2024-01-15','2024-12-31',NULL,'Daily office work and management tasks',NULL,'issued',1,'2025-09-27 04:13:24','2025-09-27 04:13:24'),(6,7,'EMP002','Juan dela Cruz','it','2024-02-01','2024-06-30',NULL,'Software development and system maintenance',NULL,'returned',1,'2025-09-27 04:13:24','2025-09-27 04:13:24'),(7,19,'1','admin','administration','2025-09-27','2025-09-28',NULL,NULL,NULL,'issued',1,'2025-09-27 04:51:45','2025-09-27 04:51:45');
/*!40000 ALTER TABLE `property_issuances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplies`
--

DROP TABLE IF EXISTS `supplies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplies` (
  `id` int NOT NULL,
  `item_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `current_stock` int DEFAULT '0',
  `minimum_stock` int DEFAULT '0',
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','discontinued') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int DEFAULT NULL,
  `archive_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `archive_notes` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplies`
--

LOCK TABLES `supplies` WRITE;
/*!40000 ALTER TABLE `supplies` DISABLE KEYS */;
INSERT INTO `supplies` VALUES (1,'SUP001','A4 Bond Paper','White bond paper for printing and copying','office','ream',51,20,250.00,12750.00,'Storage Room A','active','2025-09-27 05:11:25','2026-02-03 09:03:58',NULL,NULL,NULL,NULL),(2,'SUP002','Blue Ballpen','Blue ink ballpoint pen for writing','office','box',15,10,120.00,1800.00,'Storage Room A','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(3,'SUP003','Liquid Disinfectant','Alcohol-based disinfectant for cleaning','cleaning','bottle',25,15,45.00,1125.00,'Janitor Closet','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(4,'SUP004','Face Masks','Disposable surgical face masks','medical','box',8,20,180.00,1440.00,'Clinic Storage','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(5,'SUP005','Whiteboard Markers','Assorted color whiteboard markers','educational','set',12,8,85.00,1020.00,'Storage Room B','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(6,'SUP006','Toilet Paper','Soft toilet tissue paper','cleaning','pack',30,25,35.00,1050.00,'Janitor Closet','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(7,'SUP007','Hand Sanitizer','Alcohol-based hand sanitizer gel','medical','bottle',5,15,65.00,325.00,'Clinic Storage','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(8,'SUP008','Manila Envelopes','Brown manila envelopes various sizes','office','pack',20,10,95.00,1900.00,'Storage Room A','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(9,'SUP009','Floor Cleaner','Multi-purpose floor cleaning solution','cleaning','bottle',18,12,55.00,990.00,'Janitor Closet','active','2025-09-27 05:11:25','2025-09-27 05:11:25',NULL,NULL,NULL,NULL),(10,'SUP010','Copy Paper','Legal size copy paper','office','ream',4,15,280.00,1120.00,'Storage Room A','active','2025-09-27 05:11:25','2026-02-03 08:55:28',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `supplies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_transactions`
--

DROP TABLE IF EXISTS `supply_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supply_transactions` (
  `id` int NOT NULL,
  `supply_id` int NOT NULL,
  `transaction_type` enum('in','out','adjustment') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_transactions`
--

LOCK TABLES `supply_transactions` WRITE;
/*!40000 ALTER TABLE `supply_transactions` DISABLE KEYS */;
INSERT INTO `supply_transactions` VALUES (1,1,'in',50,250.00,12500.00,'PO-2024-001','Initial stock for A4 bond paper - Received from supplier',1,'2025-09-27 05:11:25'),(2,2,'in',25,120.00,3000.00,'PO-2024-002','Initial stock for blue ballpens - Received from supplier',1,'2025-09-27 05:11:25'),(3,3,'in',30,45.00,1350.00,'PO-2024-003','Initial stock for disinfectant - Received from supplier',1,'2025-09-27 05:11:25'),(4,4,'in',20,180.00,3600.00,'PO-2024-004','Initial stock for face masks - Received from supplier',1,'2025-09-27 05:11:25'),(5,2,'out',10,120.00,1200.00,'REQ-001','Distributed to Grade 1 teachers - Monthly distribution',1,'2025-09-27 05:11:25'),(6,3,'out',5,45.00,225.00,'REQ-002','Cleaning supplies for classrooms - Weekly cleaning',1,'2025-09-27 05:11:25'),(7,4,'out',12,180.00,2160.00,'REQ-003','Distributed to clinic - Medical supplies replenishment',1,'2025-09-27 05:11:25'),(8,5,'in',12,85.00,1020.00,'PO-2024-005','Whiteboard markers for teachers - New purchase',1,'2025-09-27 05:11:25'),(9,6,'in',30,35.00,1050.00,'PO-2024-006','Toilet paper stock - Monthly supply',1,'2025-09-27 05:11:25'),(10,7,'in',20,65.00,1300.00,'PO-2024-007','Hand sanitizer for clinic - COVID supplies',1,'2025-09-27 05:11:25'),(11,7,'out',15,65.00,975.00,'REQ-004','Distributed to classrooms - Safety protocol',1,'2025-09-27 05:11:25'),(12,8,'in',20,95.00,1900.00,'PO-2024-008','Manila envelopes for admin - Office supplies',1,'2025-09-27 05:11:25'),(13,9,'in',18,55.00,990.00,'PO-2024-009','Floor cleaner for maintenance - Cleaning supplies',1,'2025-09-27 05:11:25');
/*!40000 ALTER TABLE `supply_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-25 01:05:19'),(2,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-25 01:37:34'),(3,1,'generate_qr','assets',10,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-25 05:35:43'),(4,1,'generate_qr','assets',8,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-25 05:35:47'),(5,93,'register','users',93,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 01:37:51'),(6,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 01:37:58'),(7,93,'login','users',93,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 01:38:10'),(8,93,'login','users',93,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 02:12:08'),(9,93,'login','users',93,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 02:18:50'),(10,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 03:44:02'),(11,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 04:06:30'),(12,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 05:35:29'),(13,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 05:50:16'),(14,1,'login','users',1,'::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2025-09-27 05:52:54'),(15,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 06:07:25'),(16,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 04:23:26'),(17,1,'CREATE','maintenance_schedules',1,'::1',NULL,'2025-09-29 04:40:39'),(18,1,'CREATE','maintenance_schedules',2,'::1',NULL,'2025-09-29 04:42:25'),(19,1,'UPDATE','maintenance_schedules',1,'::1',NULL,'2025-09-29 04:42:34'),(20,1,'UPDATE','maintenance_schedules',1,'::1',NULL,'2025-09-29 04:42:46'),(21,1,'UPDATE','maintenance_schedules',2,'::1',NULL,'2025-09-29 04:42:54'),(22,1,'CREATE','maintenance_schedules',3,'::1',NULL,'2025-09-29 04:43:21'),(23,1,'UPDATE','maintenance_schedules',1,'::1',NULL,'2025-09-29 04:47:14'),(24,1,'CREATE','maintenance_schedules',4,'::1',NULL,'2025-09-29 04:47:52'),(25,1,'UPDATE','maintenance_schedules',4,'::1',NULL,'2025-09-29 04:48:47'),(26,1,'UPDATE','maintenance_schedules',4,'::1',NULL,'2025-09-29 04:50:18'),(27,1,'UPDATE','maintenance_schedules',3,'::1',NULL,'2025-09-29 04:50:30'),(28,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 10:16:38'),(29,1,'login','users',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-02 04:00:40');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','custodian','staff','maintenance') COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin123','System Administrator','admin@school.edu','admin','IT Department','active','2025-09-25 01:04:56','2025-09-25 01:04:56'),(2,'custodian','custodian123','Property Custodian','custodian@school.edu','custodian','Property Management','active','2025-09-25 01:04:56','2025-09-25 01:04:56'),(3,'staff','staff123','Staff Member','staff@school.edu','staff','General','active','2025-09-25 01:04:56','2025-09-25 01:04:56'),(4,'jsmith','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','John Smith','john.smith@school.edu','staff','IT Department','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(5,'mgarcia','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Maria Garcia','maria.garcia@school.edu','custodian','Property Office','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(6,'rjohnson','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Robert Johnson','robert.johnson@school.edu','maintenance','Facilities','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(7,'lwilson','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Linda Wilson','linda.wilson@school.edu','staff','Academic Office','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(8,'dbrown','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','David Brown','david.brown@school.edu','staff','Library','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(9,'sjones','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Sarah Jones','sarah.jones@school.edu','staff','Science Department','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(10,'mdavis','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Michael Davis','michael.davis@school.edu','staff','Physical Education','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(11,'alee','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Anna Lee','anna.lee@school.edu','admin','Administration','active','2025-09-25 03:28:47','2025-09-25 03:28:47'),(93,'admin123','$2y$10$mZsg7EHQiQ/oiFAt7z6zmemrlVqPZ/tCNK6uf/OpYSXV.cARzfQUC','admin123','admin123@gmail.com','admin','General','active','2025-09-27 01:37:51','2025-09-27 01:37:51'),(102,'TEST001','$2y$10$iMWu0eRusSeKGSAkFoTA4OPOgb0D8Mi7XWwWED2COFf8rLCzAYuY2','Test Custodian','test@test.com','custodian','Test Dept','active','2025-09-27 06:02:43','2025-09-27 06:02:43'),(103,'test_user','$2y$10$gdYvX80Ej4/G/CPLLzq2zuN/mvnnSX8n9Oz0tLlC4wF9CtikyqBj6','Test User','test@example.com','staff','Test Department','active','2025-09-29 09:35:35','2025-09-29 09:35:35');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `waste_management_records`
--

DROP TABLE IF EXISTS `waste_management_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `waste_management_records` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `identifier` varchar(120) DEFAULT NULL,
  `status` enum('archived','restored','disposed') NOT NULL DEFAULT 'archived',
  `archived_at` datetime NOT NULL,
  `archived_by` int unsigned DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  `archive_notes` text,
  `metadata` longtext,
  `disposed_at` datetime DEFAULT NULL,
  `disposed_by` int unsigned DEFAULT NULL,
  `disposal_method` varchar(120) DEFAULT NULL,
  `disposal_notes` text,
  `restored_at` datetime DEFAULT NULL,
  `restored_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waste_management_records`
--

LOCK TABLES `waste_management_records` WRITE;
/*!40000 ALTER TABLE `waste_management_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `waste_management_records` ENABLE KEYS */;
UNLOCK TABLES;
-- Removed SQL_LOG_BIN reset for shared-host compatibility
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-03 17:28:30
