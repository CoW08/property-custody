-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2025 at 06:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `property_custodian_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor','damaged') DEFAULT 'good',
  `location` varchar(200) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `status` enum('available','assigned','maintenance','disposed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `qr_code` varchar(255) DEFAULT NULL,
  `qr_generated` tinyint(1) DEFAULT 0,
  `current_value` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_code`, `name`, `description`, `category`, `condition_status`, `location`, `assigned_to`, `purchase_date`, `purchase_cost`, `status`, `created_at`, `updated_at`, `qr_code`, `qr_generated`, `current_value`) VALUES
(2, 'PC-2024-002', 'Desktop Computer - Lab 2', 'Dell OptiPlex 3080 for Computer Lab', '1', 'damaged', 'Computer Lab 1', NULL, '0000-00-00', 0.00, 'maintenance', '2025-09-25 03:39:37', '2025-09-29 04:59:37', NULL, 0, 35000.00),
(3, 'LAP-2024-001', 'Laptop - Teacher', 'HP EliteBook 840 G8 for faculty use', '1', 'good', 'Faculty Office 205', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 07:56:19', 'QR_LAP-2024-001_1758771577', 1, 45000.00),
(4, 'LAP-2024-002', 'Laptop - Administration', 'Lenovo ThinkPad X1 Carbon for admin staff', '1', 'good', 'Admin Office', NULL, NULL, NULL, 'available', '2025-09-25 03:39:37', '2025-09-27 06:10:28', NULL, 0, 50000.00),
(5, 'MON-2024-001', 'Monitor - 24 inch', 'Dell UltraSharp U2419H', '1', 'good', 'Computer Lab 1', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 07:56:19', NULL, 0, 15000.00),
(6, 'OF-2024-001', 'Executive Office Chair', 'Ergonomic chair for principal office', '2', 'good', 'Principal Office', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 07:56:19', NULL, 0, 12000.00),
(7, 'OF-2024-002', 'Student Desk', 'Standard classroom desk', '2', 'good', 'Classroom 101', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-27 06:10:16', NULL, 0, 8000.00),
(8, 'OF-2024-003', 'Teacher Desk', 'Large wooden desk for faculty', '2', 'good', 'Classroom 205', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 07:56:19', 'QR_OF-2024-003_1758778547', 1, 15000.00),
(9, 'LAB-2024-001', 'Microscope - Compound', 'Binocular compound microscope', '3', 'good', 'Science Lab A', NULL, NULL, NULL, 'available', '2025-09-25 03:39:37', '2025-09-25 07:56:19', 'QR_LAB-2024-001_1758771577', 1, 25000.00),
(11, 'AV-2024-001', 'Projector - Conference Room', 'LED Projector 4K for presentations', NULL, 'good', 'Conference Room A', NULL, NULL, NULL, 'available', '2025-09-25 03:39:37', '2025-09-25 03:39:37', 'QR_AV-2024-001_1758771577', 1, NULL),
(12, 'AV-2024-002', 'Smart TV - 55 inch', 'Interactive smart TV for classroom', NULL, 'good', 'Classroom 301', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 03:39:37', NULL, 0, NULL),
(13, 'SP-2024-001', 'Basketball Hoop - Outdoor', 'Adjustable basketball hoop system', NULL, 'good', 'Basketball Court', NULL, NULL, NULL, 'available', '2025-09-25 03:39:37', '2025-09-25 03:39:37', NULL, 0, NULL),
(14, 'SP-2024-002', 'Volleyball Net System', 'Professional volleyball net with poles', NULL, 'good', 'Gymnasium', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 03:39:37', NULL, 0, NULL),
(15, 'MU-2024-001', 'Digital Piano', 'Weighted key digital piano', NULL, 'good', 'Music Room', NULL, NULL, NULL, 'assigned', '2025-09-25 03:39:37', '2025-09-25 03:39:37', 'QR_MU-2024-001_1758771577', 1, NULL),
(17, 'TEST-1758793457689', 'Test Asset via API', 'Testing asset creation', NULL, 'good', 'Test Location', NULL, '0000-00-00', 200.00, 'available', '2025-09-25 09:44:17', '2025-09-25 09:50:34', NULL, 0, 180.00),
(18, 'TEST-1758793459704', 'Test Asset via API', 'Testing asset creation', NULL, 'good', 'Test Location', NULL, '0000-00-00', 200.00, 'available', '2025-09-25 09:44:19', '2025-09-25 09:50:24', NULL, 0, 180.00),
(19, 'TEST001', 'Air Jordan 1 Retro', 'Jordan da Goat', '7', 'excellent', 'Malacanyang', 1, '2025-09-25', 12300.00, 'assigned', '2025-09-25 10:02:17', '2025-09-27 04:51:45', NULL, 0, 15000.00);

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Computer Equipment', 'Desktop computers, laptops, monitors, keyboards, etc.', '2025-09-25 05:13:15'),
(2, 'Office Furniture', 'Desks, chairs, cabinets, tables', '2025-09-25 05:13:15'),
(3, 'Laboratory Equipment', 'Scientific instruments, microscopes, measuring tools', '2025-09-25 05:13:15'),
(4, 'Audio Visual Equipment', 'Projectors, speakers, cameras, recording devices', '2025-09-25 05:13:15'),
(5, 'Vehicles', 'School buses, service vehicles, motorcycles', '2025-09-25 05:13:15'),
(6, 'Kitchen Equipment', 'Stoves, refrigerators, cooking utensils', '2025-09-25 05:13:15'),
(7, 'Sports Equipment', 'Balls, nets, gymnasium equipment', '2025-09-25 05:13:15'),
(8, 'Books and References', 'Textbooks, reference materials, library books', '2025-09-25 05:13:15'),
(9, 'Computer Equipment', 'Desktop computers, laptops, monitors, keyboards, etc.', '2025-09-27 05:28:21'),
(10, 'Office Furniture', 'Desks, chairs, cabinets, tables', '2025-09-27 05:28:21'),
(11, 'Laboratory Equipment', 'Scientific instruments, microscopes, measuring tools', '2025-09-27 05:28:21'),
(12, 'Audio Visual Equipment', 'Projectors, speakers, cameras, recording devices', '2025-09-27 05:28:21'),
(13, 'Vehicles', 'School buses, service vehicles, motorcycles', '2025-09-27 05:28:21'),
(14, 'Kitchen Equipment', 'Stoves, refrigerators, cooking utensils', '2025-09-27 05:28:21'),
(15, 'Sports Equipment', 'Balls, nets, gymnasium equipment', '2025-09-27 05:28:21'),
(16, 'Books and References', 'Textbooks, reference materials, library books', '2025-09-27 05:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `asset_tags`
--

CREATE TABLE `asset_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3B82F6',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_tags`
--

INSERT INTO `asset_tags` (`id`, `name`, `description`, `color`, `created_by`, `created_at`) VALUES
(1, 'High Priority', 'Critical assets requiring special attention', '#DC2626', 1, '2025-09-25 03:20:09'),
(2, 'Fragile', 'Assets that require careful handling', '#F59E0B', 1, '2025-09-25 03:20:09'),
(3, 'Portable', 'Easily movable assets', '#10B981', 1, '2025-09-25 03:20:09'),
(4, 'Expensive', 'High-value assets requiring extra security', '#7C3AED', 1, '2025-09-25 03:20:09'),
(5, 'Outdated', 'Assets scheduled for replacement', '#6B7280', 1, '2025-09-25 03:20:09'),
(6, 'New', 'Recently acquired assets', '#06B6D4', 1, '2025-09-25 03:20:09'),
(7, 'Warranty', 'Assets currently under warranty', '#059669', 1, '2025-09-25 03:20:09'),
(8, 'Shared', 'Assets used by multiple departments', '#0EA5E9', 1, '2025-09-25 03:20:09'),
(9, 'Personal', 'Assets assigned to specific individuals', '#8B5CF6', 1, '2025-09-25 03:20:09'),
(10, 'Backup', 'Redundant or backup equipment', '#84CC16', 1, '2025-09-25 03:20:09'),
(22, 'try', '', '#1c020f', 1, '2025-09-25 10:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `asset_tag_relationships`
--

CREATE TABLE `asset_tag_relationships` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_tag_relationships`
--

INSERT INTO `asset_tag_relationships` (`id`, `asset_id`, `tag_id`, `assigned_by`, `assigned_at`) VALUES
(2, 3, 3, NULL, '2025-09-25 07:56:19'),
(3, 4, 3, NULL, '2025-09-25 07:56:19'),
(4, 4, 4, NULL, '2025-09-25 07:56:19'),
(5, 9, 2, NULL, '2025-09-25 07:56:19'),
(6, 6, 5, NULL, '2025-09-25 07:56:19'),
(8, 18, 8, 1, '2025-09-25 09:50:24'),
(9, 17, 6, 1, '2025-09-25 09:50:34'),
(10, 2, 4, 1, '2025-09-25 09:50:47'),
(11, 19, 6, 1, '2025-09-25 10:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `audit_findings`
--

CREATE TABLE `audit_findings` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `finding_type` enum('missing','damaged','location_mismatch','data_error','unauthorized_use') NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `corrective_action` text DEFAULT NULL,
  `responsible_person` int(11) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_findings`
--

INSERT INTO `audit_findings` (`id`, `audit_id`, `asset_id`, `finding_type`, `description`, `severity`, `corrective_action`, `responsible_person`, `target_date`, `status`, `created_at`) VALUES
(1, 1, NULL, 'missing', 'Computer monitor not found in expected location', 'medium', 'Search other locations and update asset location', NULL, '2024-02-01', 'open', '2025-09-29 05:21:39'),
(2, 3, NULL, '', 'Asset PC-2024-002 found in Computer Lab 1. Condition: good. Notes: Asset verified during audit', 'low', 'Asset verified and location updated', NULL, NULL, 'open', '2025-09-29 05:30:38'),
(3, 3, NULL, 'missing', 'Laptop LAP-2024-001 is missing from Faculty Office 205. Last seen 2 weeks ago.', 'high', 'Search other locations and contact last known user', NULL, '2024-02-15', 'open', '2025-09-29 05:30:48');

-- --------------------------------------------------------

--
-- Table structure for table `custodians`
--

CREATE TABLE `custodians` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `office_location` varchar(200) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','transferred') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custodians`
--

INSERT INTO `custodians` (`id`, `user_id`, `employee_id`, `department`, `position`, `contact_number`, `office_location`, `supervisor_id`, `status`, `created_at`) VALUES
(1, NULL, 'EMP001', 'IT Department', 'Systems Administrator', NULL, NULL, NULL, 'active', '2025-09-27 05:28:28'),
(2, 102, 'TEST001', 'Test Dept', 'Test Position', NULL, NULL, NULL, 'active', '2025-09-27 06:02:43');

-- --------------------------------------------------------

--
-- Table structure for table `damaged_items`
--

CREATE TABLE `damaged_items` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `damage_type` enum('physical','electrical','software','wear','accident','vandalism','other') NOT NULL,
  `severity_level` enum('minor','moderate','major','total') NOT NULL,
  `damage_date` date NOT NULL,
  `reported_by` varchar(100) NOT NULL,
  `current_location` varchar(200) DEFAULT NULL,
  `estimated_repair_cost` decimal(12,2) DEFAULT NULL,
  `damage_description` text DEFAULT NULL,
  `damage_photos` text DEFAULT NULL,
  `status` enum('reported','under_repair','repaired','write_off') DEFAULT 'reported',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `damaged_items`
--

INSERT INTO `damaged_items` (`id`, `asset_id`, `asset_code`, `damage_type`, `severity_level`, `damage_date`, `reported_by`, `current_location`, `estimated_repair_cost`, `damage_description`, `damage_photos`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'PC-2024-002', 'physical', 'moderate', '2024-01-15', 'John Doe', 'Computer Lab 1', 150.00, 'Screen cracked on the left corner', NULL, 'reported', '2025-09-29 04:59:37', '2025-09-29 04:59:37'),
(2, 2, 'PC-2024-002', 'physical', 'moderate', '2024-01-15', 'John Doe', 'Computer Lab 1', 150.00, 'Screen cracked on the left corner', NULL, 'repaired', '2025-09-29 05:10:38', '2025-09-29 05:13:38');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedules`
--

CREATE TABLE `maintenance_schedules` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` enum('preventive','corrective','emergency') NOT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `estimated_duration` decimal(4,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_schedules`
--

INSERT INTO `maintenance_schedules` (`id`, `asset_id`, `maintenance_type`, `scheduled_date`, `completed_date`, `assigned_to`, `description`, `estimated_cost`, `estimated_duration`, `actual_cost`, `status`, `priority`, `notes`, `created_at`) VALUES
(1, 2, 'preventive', '2025-01-15', '2025-09-29', 6, 'Updated routine maintenance check', NULL, 3.50, 1000.00, 'completed', 'medium', 'Added some notes during editing', '2025-09-29 04:40:39'),
(2, 13, 'preventive', '2025-09-29', NULL, 11, 'yes', 1000.00, NULL, NULL, 'cancelled', 'medium', NULL, '2025-09-29 04:42:25'),
(3, 13, 'preventive', '2025-09-29', NULL, NULL, 'yes', 20000.00, NULL, NULL, 'scheduled', 'high', NULL, '2025-09-29 04:43:21'),
(4, 13, 'corrective', '2025-01-20', NULL, 6, 'Fix laptop keyboard issue', 200.00, NULL, NULL, 'scheduled', 'medium', 'Customer reported urgent issue with multiple keys not working', '2025-09-29 04:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_requests`
--

CREATE TABLE `procurement_requests` (
  `id` int(11) NOT NULL,
  `request_code` varchar(50) NOT NULL,
  `request_type` enum('asset','supply','service') NOT NULL,
  `requestor_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `request_date` date NOT NULL,
  `required_date` date DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `approved_cost` decimal(15,2) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('draft','submitted','approved','rejected','ordered','received','cancelled') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procurement_requests`
--

INSERT INTO `procurement_requests` (`id`, `request_code`, `request_type`, `requestor_id`, `department`, `request_date`, `required_date`, `justification`, `estimated_cost`, `approved_cost`, `priority`, `status`, `approved_by`, `approval_date`, `notes`, `created_at`) VALUES
(1, 'AS-202509-001', 'asset', 1, 'IT Department', '2024-01-15', '2024-02-15', 'Need new computers for the computer lab upgrade', 235000.00, 230000.00, 'high', 'approved', 1, '2025-09-29', 'Approved with slight cost reduction', '2025-09-29 05:37:55'),
(2, 'SU-202509-001', 'supply', 2, 'Office Management', '2024-01-20', NULL, 'Monthly office supplies replenishment', 25750.00, NULL, 'medium', 'draft', NULL, NULL, NULL, '2025-09-29 05:38:05'),
(3, 'AS-202509-002', 'asset', 1, 'Marcos Admin', '2025-09-29', '2025-10-02', 'Need to resign', 10000000.00, NULL, 'urgent', 'draft', NULL, NULL, 'bad', '2025-09-29 05:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_request_items`
--

CREATE TABLE `procurement_request_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `estimated_unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procurement_request_items`
--

INSERT INTO `procurement_request_items` (`id`, `request_id`, `item_name`, `description`, `quantity`, `unit`, `estimated_unit_cost`, `total_cost`, `specifications`, `created_at`) VALUES
(1, 1, 'Desktop Computer', 'Dell OptiPlex or equivalent', 5, 'piece', 35000.00, 175000.00, 'Intel i5, 8GB RAM, 256GB SSD', '2025-09-29 05:37:55'),
(2, 1, 'Monitor', '24-inch LED Monitor', 5, 'piece', 12000.00, 60000.00, 'Full HD, IPS panel', '2025-09-29 05:37:55'),
(3, 2, 'A4 Paper', 'White bond paper', 100, 'ream', 250.00, 25000.00, NULL, '2025-09-29 05:38:05'),
(4, 2, 'Ballpen', 'Blue ink ballpen', 50, 'piece', 15.00, 750.00, NULL, '2025-09-29 05:38:05'),
(5, 3, 'car', '', 1, 'piece', 10000000.00, 10000000.00, '', '2025-09-29 05:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `property_assignments`
--

CREATE TABLE `property_assignments` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `custodian_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `assignment_purpose` text DEFAULT NULL,
  `conditions` text DEFAULT NULL,
  `acknowledgment_signed` tinyint(1) DEFAULT 0,
  `acknowledgment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','returned','transferred','lost') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_assignments`
--

INSERT INTO `property_assignments` (`id`, `asset_id`, `custodian_id`, `assigned_by`, `assignment_date`, `expected_return_date`, `actual_return_date`, `assignment_purpose`, `conditions`, `acknowledgment_signed`, `acknowledgment_date`, `status`, `notes`, `created_at`) VALUES
(1, 4, 1, 1, '2025-09-27', NULL, '2025-09-27', 'Testing assignment', NULL, 0, '2025-09-27 06:10:28', 'returned', NULL, '2025-09-27 06:09:10'),
(2, 7, 1, 1, '2025-09-27', '2025-10-02', NULL, '', NULL, 0, '2025-09-27 06:10:16', 'active', '', '2025-09-27 06:10:16');

-- --------------------------------------------------------

--
-- Table structure for table `property_audits`
--

CREATE TABLE `property_audits` (
  `id` int(11) NOT NULL,
  `audit_code` varchar(50) NOT NULL,
  `audit_type` enum('physical_inventory','financial_audit','compliance_check') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `auditor_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `total_assets_audited` int(11) DEFAULT 0,
  `discrepancies_found` int(11) DEFAULT 0,
  `summary` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_audits`
--

INSERT INTO `property_audits` (`id`, `audit_code`, `audit_type`, `start_date`, `end_date`, `auditor_id`, `department`, `status`, `total_assets_audited`, `discrepancies_found`, `summary`, `created_at`) VALUES
(1, 'AUD-2025-1757', 'physical_inventory', '2024-01-15', '2024-01-30', 1, 'IT Department - Updated', 'completed', 25, 2, 'Quarterly physical inventory audit for IT equipment - COMPLETED', '2025-09-29 05:20:33'),
(3, 'AUD-2025-1393', 'financial_audit', '2025-09-29', NULL, 1, 'Administration', 'in_progress', 0, 0, 'Updated summary for administration audit', '2025-09-29 05:23:50');

-- --------------------------------------------------------

--
-- Table structure for table `property_issuances`
--

CREATE TABLE `property_issuances` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('issued','returned','overdue','damaged') DEFAULT 'issued',
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_issuances`
--

INSERT INTO `property_issuances` (`id`, `asset_id`, `employee_id`, `recipient_name`, `department`, `issue_date`, `expected_return_date`, `actual_return_date`, `purpose`, `remarks`, `status`, `issued_by`, `created_at`, `updated_at`) VALUES
(5, 4, 'EMP001', 'Maria Santos', 'administration', '2024-01-15', '2024-12-31', NULL, 'Daily office work and management tasks', NULL, 'issued', 1, '2025-09-27 04:13:24', '2025-09-27 04:13:24'),
(6, 7, 'EMP002', 'Juan dela Cruz', 'it', '2024-02-01', '2024-06-30', NULL, 'Software development and system maintenance', NULL, 'returned', 1, '2025-09-27 04:13:24', '2025-09-27 04:13:24'),
(7, 19, '1', 'admin', 'administration', '2025-09-27', '2025-09-28', NULL, NULL, NULL, 'issued', 1, '2025-09-27 04:51:45', '2025-09-27 04:51:45');

-- --------------------------------------------------------

--
-- Table structure for table `supplies`
--

CREATE TABLE `supplies` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('active','discontinued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplies`
--

INSERT INTO `supplies` (`id`, `item_code`, `name`, `description`, `category`, `unit`, `current_stock`, `minimum_stock`, `unit_cost`, `total_value`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SUP001', 'A4 Bond Paper', 'White bond paper for printing and copying', 'office', 'ream', 50, 20, 250.00, 12500.00, 'Storage Room A', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(2, 'SUP002', 'Blue Ballpen', 'Blue ink ballpoint pen for writing', 'office', 'box', 15, 10, 120.00, 1800.00, 'Storage Room A', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(3, 'SUP003', 'Liquid Disinfectant', 'Alcohol-based disinfectant for cleaning', 'cleaning', 'bottle', 25, 15, 45.00, 1125.00, 'Janitor Closet', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(4, 'SUP004', 'Face Masks', 'Disposable surgical face masks', 'medical', 'box', 8, 20, 180.00, 1440.00, 'Clinic Storage', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(5, 'SUP005', 'Whiteboard Markers', 'Assorted color whiteboard markers', 'educational', 'set', 12, 8, 85.00, 1020.00, 'Storage Room B', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(6, 'SUP006', 'Toilet Paper', 'Soft toilet tissue paper', 'cleaning', 'pack', 30, 25, 35.00, 1050.00, 'Janitor Closet', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(7, 'SUP007', 'Hand Sanitizer', 'Alcohol-based hand sanitizer gel', 'medical', 'bottle', 5, 15, 65.00, 325.00, 'Clinic Storage', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(8, 'SUP008', 'Manila Envelopes', 'Brown manila envelopes various sizes', 'office', 'pack', 20, 10, 95.00, 1900.00, 'Storage Room A', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(9, 'SUP009', 'Floor Cleaner', 'Multi-purpose floor cleaning solution', 'cleaning', 'bottle', 18, 12, 55.00, 990.00, 'Janitor Closet', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25'),
(10, 'SUP010', 'Copy Paper', 'Legal size copy paper', 'office', 'ream', 0, 15, 280.00, 0.00, 'Storage Room A', 'active', '2025-09-27 05:11:25', '2025-09-27 05:11:25');

-- --------------------------------------------------------

--
-- Table structure for table `supply_transactions`
--

CREATE TABLE `supply_transactions` (
  `id` int(11) NOT NULL,
  `supply_id` int(11) NOT NULL,
  `transaction_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply_transactions`
--

INSERT INTO `supply_transactions` (`id`, `supply_id`, `transaction_type`, `quantity`, `unit_cost`, `total_cost`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'in', 50, 250.00, 12500.00, 'PO-2024-001', 'Initial stock for A4 bond paper - Received from supplier', 1, '2025-09-27 05:11:25'),
(2, 2, 'in', 25, 120.00, 3000.00, 'PO-2024-002', 'Initial stock for blue ballpens - Received from supplier', 1, '2025-09-27 05:11:25'),
(3, 3, 'in', 30, 45.00, 1350.00, 'PO-2024-003', 'Initial stock for disinfectant - Received from supplier', 1, '2025-09-27 05:11:25'),
(4, 4, 'in', 20, 180.00, 3600.00, 'PO-2024-004', 'Initial stock for face masks - Received from supplier', 1, '2025-09-27 05:11:25'),
(5, 2, 'out', 10, 120.00, 1200.00, 'REQ-001', 'Distributed to Grade 1 teachers - Monthly distribution', 1, '2025-09-27 05:11:25'),
(6, 3, 'out', 5, 45.00, 225.00, 'REQ-002', 'Cleaning supplies for classrooms - Weekly cleaning', 1, '2025-09-27 05:11:25'),
(7, 4, 'out', 12, 180.00, 2160.00, 'REQ-003', 'Distributed to clinic - Medical supplies replenishment', 1, '2025-09-27 05:11:25'),
(8, 5, 'in', 12, 85.00, 1020.00, 'PO-2024-005', 'Whiteboard markers for teachers - New purchase', 1, '2025-09-27 05:11:25'),
(9, 6, 'in', 30, 35.00, 1050.00, 'PO-2024-006', 'Toilet paper stock - Monthly supply', 1, '2025-09-27 05:11:25'),
(10, 7, 'in', 20, 65.00, 1300.00, 'PO-2024-007', 'Hand sanitizer for clinic - COVID supplies', 1, '2025-09-27 05:11:25'),
(11, 7, 'out', 15, 65.00, 975.00, 'REQ-004', 'Distributed to classrooms - Safety protocol', 1, '2025-09-27 05:11:25'),
(12, 8, 'in', 20, 95.00, 1900.00, 'PO-2024-008', 'Manila envelopes for admin - Office supplies', 1, '2025-09-27 05:11:25'),
(13, 9, 'in', 18, 55.00, 990.00, 'PO-2024-009', 'Floor cleaner for maintenance - Cleaning supplies', 1, '2025-09-27 05:11:25');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 01:05:19'),
(2, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 01:37:34'),
(3, 1, 'generate_qr', 'assets', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 05:35:43'),
(4, 1, 'generate_qr', 'assets', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-25 05:35:47'),
(5, 93, 'register', 'users', 93, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 01:37:51'),
(6, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 01:37:58'),
(7, 93, 'login', 'users', 93, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 01:38:10'),
(8, 93, 'login', 'users', 93, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:12:08'),
(9, 93, 'login', 'users', 93, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:18:50'),
(10, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:44:02'),
(11, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:06:30'),
(12, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:35:29'),
(13, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:50:16'),
(14, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-09-27 05:52:54'),
(15, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:07:25'),
(16, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 04:23:26'),
(17, 1, 'CREATE', 'maintenance_schedules', 1, '::1', NULL, '2025-09-29 04:40:39'),
(18, 1, 'CREATE', 'maintenance_schedules', 2, '::1', NULL, '2025-09-29 04:42:25'),
(19, 1, 'UPDATE', 'maintenance_schedules', 1, '::1', NULL, '2025-09-29 04:42:34'),
(20, 1, 'UPDATE', 'maintenance_schedules', 1, '::1', NULL, '2025-09-29 04:42:46'),
(21, 1, 'UPDATE', 'maintenance_schedules', 2, '::1', NULL, '2025-09-29 04:42:54'),
(22, 1, 'CREATE', 'maintenance_schedules', 3, '::1', NULL, '2025-09-29 04:43:21'),
(23, 1, 'UPDATE', 'maintenance_schedules', 1, '::1', NULL, '2025-09-29 04:47:14'),
(24, 1, 'CREATE', 'maintenance_schedules', 4, '::1', NULL, '2025-09-29 04:47:52'),
(25, 1, 'UPDATE', 'maintenance_schedules', 4, '::1', NULL, '2025-09-29 04:48:47'),
(26, 1, 'UPDATE', 'maintenance_schedules', 4, '::1', NULL, '2025-09-29 04:50:18'),
(27, 1, 'UPDATE', 'maintenance_schedules', 3, '::1', NULL, '2025-09-29 04:50:30'),
(28, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 10:16:38'),
(29, 1, 'login', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-02 04:00:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','custodian','staff','maintenance') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `department`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin123', 'System Administrator', 'admin@school.edu', 'admin', 'IT Department', 'active', '2025-09-25 01:04:56', '2025-09-25 01:04:56'),
(2, 'custodian', 'custodian123', 'Property Custodian', 'custodian@school.edu', 'custodian', 'Property Management', 'active', '2025-09-25 01:04:56', '2025-09-25 01:04:56'),
(3, 'staff', 'staff123', 'Staff Member', 'staff@school.edu', 'staff', 'General', 'active', '2025-09-25 01:04:56', '2025-09-25 01:04:56'),
(4, 'jsmith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'john.smith@school.edu', 'staff', 'IT Department', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(5, 'mgarcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Garcia', 'maria.garcia@school.edu', 'custodian', 'Property Office', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(6, 'rjohnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert Johnson', 'robert.johnson@school.edu', 'maintenance', 'Facilities', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(7, 'lwilson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Linda Wilson', 'linda.wilson@school.edu', 'staff', 'Academic Office', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(8, 'dbrown', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Brown', 'david.brown@school.edu', 'staff', 'Library', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(9, 'sjones', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Jones', 'sarah.jones@school.edu', 'staff', 'Science Department', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(10, 'mdavis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Davis', 'michael.davis@school.edu', 'staff', 'Physical Education', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(11, 'alee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Lee', 'anna.lee@school.edu', 'admin', 'Administration', 'active', '2025-09-25 03:28:47', '2025-09-25 03:28:47'),
(93, 'admin123', '$2y$10$mZsg7EHQiQ/oiFAt7z6zmemrlVqPZ/tCNK6uf/OpYSXV.cARzfQUC', 'admin123', 'admin123@gmail.com', 'admin', 'General', 'active', '2025-09-27 01:37:51', '2025-09-27 01:37:51'),
(102, 'TEST001', '$2y$10$iMWu0eRusSeKGSAkFoTA4OPOgb0D8Mi7XWwWED2COFf8rLCzAYuY2', 'Test Custodian', 'test@test.com', 'custodian', 'Test Dept', 'active', '2025-09-27 06:02:43', '2025-09-27 06:02:43'),
(103, 'test_user', '$2y$10$gdYvX80Ej4/G/CPLLzq2zuN/mvnnSX8n9Oz0tLlC4wF9CtikyqBj6', 'Test User', 'test@example.com', 'staff', 'Test Department', 'active', '2025-09-29 09:35:35', '2025-09-29 09:35:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_assets_qr_code` (`qr_code`),
  ADD KEY `idx_assets_status` (`status`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `asset_tags`
--
ALTER TABLE `asset_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_asset_tags_name` (`name`);

--
-- Indexes for table `asset_tag_relationships`
--
ALTER TABLE `asset_tag_relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asset_tag` (`asset_id`,`tag_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_asset_tag_relationships_asset` (`asset_id`),
  ADD KEY `idx_asset_tag_relationships_tag` (`tag_id`);

--
-- Indexes for table `audit_findings`
--
ALTER TABLE `audit_findings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_id` (`audit_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `responsible_person` (`responsible_person`);

--
-- Indexes for table `custodians`
--
ALTER TABLE `custodians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `damaged_items`
--
ALTER TABLE `damaged_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `idx_asset_code` (`asset_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_damage_date` (`damage_date`);

--
-- Indexes for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_code` (`request_code`),
  ADD KEY `requestor_id` (`requestor_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `procurement_request_items`
--
ALTER TABLE `procurement_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `property_assignments`
--
ALTER TABLE `property_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `custodian_id` (`custodian_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `property_audits`
--
ALTER TABLE `property_audits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `audit_code` (`audit_code`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `property_issuances`
--
ALTER TABLE `property_issuances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `supplies`
--
ALTER TABLE `supplies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `supply_transactions`
--
ALTER TABLE `supply_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supply_id` (`supply_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `asset_tags`
--
ALTER TABLE `asset_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `asset_tag_relationships`
--
ALTER TABLE `asset_tag_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `audit_findings`
--
ALTER TABLE `audit_findings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `custodians`
--
ALTER TABLE `custodians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `damaged_items`
--
ALTER TABLE `damaged_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `procurement_request_items`
--
ALTER TABLE `procurement_request_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `property_assignments`
--
ALTER TABLE `property_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `property_audits`
--
ALTER TABLE `property_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `property_issuances`
--
ALTER TABLE `property_issuances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `supply_transactions`
--
ALTER TABLE `supply_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_tags`
--
ALTER TABLE `asset_tags`
  ADD CONSTRAINT `asset_tags_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `asset_tag_relationships`
--
ALTER TABLE `asset_tag_relationships`
  ADD CONSTRAINT `asset_tag_relationships_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_tag_relationships_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `asset_tags` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_tag_relationships_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `audit_findings`
--
ALTER TABLE `audit_findings`
  ADD CONSTRAINT `audit_findings_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `property_audits` (`id`),
  ADD CONSTRAINT `audit_findings_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  ADD CONSTRAINT `audit_findings_ibfk_3` FOREIGN KEY (`responsible_person`) REFERENCES `users` (`id`);

--
-- Constraints for table `custodians`
--
ALTER TABLE `custodians`
  ADD CONSTRAINT `custodians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `custodians_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `custodians` (`id`);

--
-- Constraints for table `damaged_items`
--
ALTER TABLE `damaged_items`
  ADD CONSTRAINT `damaged_items_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  ADD CONSTRAINT `maintenance_schedules_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD CONSTRAINT `procurement_requests_ibfk_1` FOREIGN KEY (`requestor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `procurement_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `procurement_request_items`
--
ALTER TABLE `procurement_request_items`
  ADD CONSTRAINT `procurement_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`);

--
-- Constraints for table `property_assignments`
--
ALTER TABLE `property_assignments`
  ADD CONSTRAINT `property_assignments_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  ADD CONSTRAINT `property_assignments_ibfk_2` FOREIGN KEY (`custodian_id`) REFERENCES `custodians` (`id`),
  ADD CONSTRAINT `property_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `property_audits`
--
ALTER TABLE `property_audits`
  ADD CONSTRAINT `property_audits_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `property_issuances`
--
ALTER TABLE `property_issuances`
  ADD CONSTRAINT `property_issuances_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_transactions`
--
ALTER TABLE `supply_transactions`
  ADD CONSTRAINT `supply_transactions_ibfk_1` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
