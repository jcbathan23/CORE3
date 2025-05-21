-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 03:03 PM
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
-- Database: `core3`
--

-- --------------------------------------------------------

--
-- Table structure for table `beds`
--

CREATE TABLE `beds` (
  `bed_id` int(11) NOT NULL,
  `bed_number` varchar(10) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `bed_type` enum('Regular','Private','ICU','Emergency') NOT NULL DEFAULT 'Regular',
  `status` enum('Available','Occupied','Maintenance') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `beds`
--

INSERT INTO `beds` (`bed_id`, `bed_number`, `room_number`, `bed_type`, `status`, `created_at`, `updated_at`) VALUES
(2, '101B', '101', 'Regular', 'Occupied', '2025-05-16 15:37:27', '2025-05-20 12:05:44'),
(5, '201B', '201', 'ICU', 'Occupied', '2025-05-16 15:37:27', '2025-05-20 12:12:38'),
(6, '301A', '301', 'Emergency', 'Available', '2025-05-16 15:37:27', '2025-05-20 12:16:35'),
(7, '101-C', '206', 'Emergency', 'Available', '2025-05-16 16:02:53', '2025-05-20 12:07:24'),
(8, '101A', '202', 'Regular', 'Occupied', '2025-05-19 14:32:39', '2025-05-20 12:16:07'),
(9, '202B', '208', 'Regular', 'Available', '2025-05-20 02:45:23', '2025-05-20 12:20:07');

-- --------------------------------------------------------

--
-- Table structure for table `bed_allocations`
--

CREATE TABLE `bed_allocations` (
  `allocation_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `bed_number` varchar(10) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `expected_discharge` date NOT NULL,
  `status` enum('occupied','discharged','transferred') NOT NULL DEFAULT 'occupied',
  `treatment_plan` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bed_allocations`
--

INSERT INTO `bed_allocations` (`allocation_id`, `room_number`, `bed_number`, `patient_id`, `admission_date`, `expected_discharge`, `status`, `treatment_plan`, `created_by`, `created_at`) VALUES
(1, '101', '1', 1, '2024-03-01', '2024-03-10', 'occupied', '123', 0, '2025-05-20 12:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `bill_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `bill_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pending','Partial','Paid','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `item_id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `item_type` enum('Medicine','Service','Package','Room','Other') NOT NULL,
  `reference_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diet_management`
--

CREATE TABLE `diet_management` (
  `patient_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `treatment_plan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homis_bills`
--

CREATE TABLE `homis_bills` (
  `bill_id` int(11) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homis_bills`
--

INSERT INTO `homis_bills` (`bill_id`, `patient_name`, `service_type`, `amount`, `description`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(4, 'ad', 'consultation', 100.00, 'asd', '2025-05-23', 'Pending', '2025-05-21 13:01:26', '2025-05-21 13:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `homis_inventory`
--

CREATE TABLE `homis_inventory` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `supplier` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homis_inventory`
--

INSERT INTO `homis_inventory` (`item_id`, `item_name`, `category`, `quantity`, `unit`, `price`, `supplier`, `expiry_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Bed Sheets', 'Medicine', 3, 'Boxes', 1000.00, 'asdasd', '2025-05-17', 'In Stock', '2025-05-14 13:34:55', '2025-05-14 13:35:06');

-- --------------------------------------------------------

--
-- Table structure for table `homis_inventory_transactions`
--

CREATE TABLE `homis_inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('In','Out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homis_inventory_transactions`
--

INSERT INTO `homis_inventory_transactions` (`transaction_id`, `item_id`, `transaction_type`, `quantity`, `notes`, `transaction_date`) VALUES
(1, 1, 'In', 3, 'Initial stock', '2025-05-14 13:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `homis_payments`
--

CREATE TABLE `homis_payments` (
  `payment_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inpatient_outpatient`
--

CREATE TABLE `inpatient_outpatient` (
  `patientpk` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `treatment_plan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `linen_assignments`
--

CREATE TABLE `linen_assignments` (
  `assignment_id` int(11) NOT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `linen_type_id` int(11) DEFAULT NULL,
  `quantity_assigned` int(11) NOT NULL,
  `assignment_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('Assigned','Returned','Damaged') DEFAULT 'Assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `linen_inventory`
--

CREATE TABLE `linen_inventory` (
  `linen_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `condition` enum('New','Good','Fair','Poor') NOT NULL DEFAULT 'New',
  `status` enum('Available','In Use','In Laundry','Discarded') NOT NULL DEFAULT 'Available',
  `last_washed_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `linen_inventory`
--

INSERT INTO `linen_inventory` (`linen_id`, `item_name`, `quantity`, `condition`, `status`, `last_washed_date`, `created_at`, `updated_at`) VALUES
(1, 'Bed Sheet', 102, 'New', 'Discarded', NULL, '2025-05-14 12:58:25', '2025-05-16 16:03:20'),
(2, 'Pillow Case', 150, 'Good', 'Available', NULL, '2025-05-14 12:58:25', '2025-05-14 12:58:25'),
(3, 'Blanket', 75, 'Good', 'Available', NULL, '2025-05-14 12:58:25', '2025-05-14 12:58:25'),
(4, 'Towel', 200, 'New', 'Available', NULL, '2025-05-14 12:58:25', '2025-05-14 12:58:25');

-- --------------------------------------------------------

--
-- Table structure for table `linen_laundry`
--

CREATE TABLE `linen_laundry` (
  `laundry_id` int(11) NOT NULL,
  `linen_id` int(11) NOT NULL,
  `sent_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('Sent','Processing','Returned') NOT NULL DEFAULT 'Sent',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `linen_laundry_requests`
--

CREATE TABLE `linen_laundry_requests` (
  `request_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `priority` enum('normal','urgent','emergency') NOT NULL DEFAULT 'normal',
  `request_type` enum('laundry','replacement') NOT NULL DEFAULT 'laundry',
  `status` enum('Pending','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `linen_laundry_requests`
--

INSERT INTO `linen_laundry_requests` (`request_id`, `item_type`, `quantity`, `priority`, `request_type`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'bed_sheet', 123, 'urgent', 'replacement', 'Pending', 'asd', '2025-05-14 12:58:37', '2025-05-14 12:58:41'),
(2, 'pillow_case', 4, 'emergency', 'replacement', 'Completed', 'asdasd', '2025-05-14 13:26:11', '2025-05-14 13:27:26');

-- --------------------------------------------------------

--
-- Table structure for table `linen_types`
--

CREATE TABLE `linen_types` (
  `linen_type_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `standard_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` enum('admin','manager','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `email`, `password`, `account_type`, `created_at`, `updated_at`) VALUES
(1, 'jezi', '123', 'admin', '2025-05-14 12:57:44', '2025-05-14 12:57:44'),
(2, 'aghik', '123', 'admin', '2025-05-15 16:23:44', '2025-05-15 16:23:44'),
(3, 'mani', '123', 'manager', '2025-05-19 12:30:06', '2025-05-19 12:30:06'),
(4, 'ugh', '123', 'user', '2025-05-19 13:30:55', '2025-05-19 13:30:55');

-- --------------------------------------------------------

--
-- Table structure for table `medical_packages`
--

CREATE TABLE `medical_packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `services` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_packages`
--

INSERT INTO `medical_packages` (`package_id`, `package_name`, `description`, `price`, `duration`, `services`, `created_at`, `updated_at`) VALUES
(1, 'Basic Health Checkup', 'Complete physical examination with basic lab tests', 2500.00, 30, 'General physical examination\nBlood pressure check\nBasic blood work\nUrinalysis', '2025-05-14 14:16:05', '2025-05-14 14:16:05'),
(2, 'Comprehensive Wellness', 'Full body checkup with advanced diagnostics and consultations', 7500.00, 60, 'Complete physical examination\nComprehensive blood panel\nECG\nChest X-ray\nAbdominal ultrasound\nDietitian consultation', '2025-05-14 14:16:05', '2025-05-14 14:16:05'),
(3, 'Cardiac Care', 'Specialized cardiac evaluation and monitoring package', 5000.00, 45, 'Cardiac consultation\nECG\nEchocardiogram\nLipid profile\nStress test\nFollow-up visits', '2025-05-14 14:16:05', '2025-05-14 14:16:05'),
(4, 'AGHIK', 'asdadadaddadad', 999.00, 15, 'sadasdasd', '2025-05-14 14:20:54', '2025-05-14 14:20:54');

-- --------------------------------------------------------

--
-- Table structure for table `medical_record`
--

CREATE TABLE `medical_record` (
  `mrn` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `diagnosis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_categories`
--

CREATE TABLE `medicine_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

CREATE TABLE `nurses` (
  `nurse_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `designation` varchar(50) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`nurse_id`, `first_name`, `last_name`, `designation`, `status`, `created_at`) VALUES
(1, 'John', 'Smith', 'RN', 'active', '2025-05-14 13:08:14'),
(2, 'Jane', 'Doe', 'LPN', 'active', '2025-05-14 13:08:14'),
(3, 'Emily', 'Johnson', 'CNA', 'active', '2025-05-14 13:08:14');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_assignments`
--

CREATE TABLE `nurse_assignments` (
  `assignment_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `shift` enum('morning','afternoon','night') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurse_assignments`
--

INSERT INTO `nurse_assignments` (`assignment_id`, `nurse_id`, `ward_id`, `shift`, `start_date`, `end_date`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 2, 1, 'afternoon', '2025-05-14', '2025-05-22', 'active', '123', 1, '2025-05-14 13:13:09');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_shifts`
--

CREATE TABLE `nurse_shifts` (
  `shift_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `shift_start` datetime NOT NULL,
  `shift_end` datetime NOT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_payments`
--

CREATE TABLE `package_payments` (
  `payment_id` int(11) NOT NULL,
  `patient_package_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','credit_card','debit_card','bank_transfer') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_payments`
--

INSERT INTO `package_payments` (`payment_id`, `patient_package_id`, `amount`, `payment_date`, `payment_method`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 999.00, '2025-05-14', 'credit_card', 'asdasd', 1, '2025-05-14 14:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `package_services`
--

CREATE TABLE `package_services` (
  `service_id` int(11) NOT NULL,
  `patient_package_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_date` date NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_services`
--

INSERT INTO `package_services` (`service_id`, `patient_package_id`, `service_name`, `service_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'sadasdasd', '2025-05-14', 'pending', NULL, 1, '2025-05-14 14:25:43', '2025-05-14 14:25:43'),
(2, 1, 'NANI', '2025-05-14', 'completed', 'asdadd', 1, '2025-05-14 14:26:06', '2025-05-14 14:26:22');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('M','F','Other') NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `address`, `contact_number`, `emergency_contact`, `emergency_contact_number`, `blood_type`, `created_at`) VALUES
(1, 'jc', 'bathan', '0000-00-00', 'M', NULL, NULL, NULL, NULL, NULL, '2025-05-14 13:16:06'),
(11, 'mark', 'asd', '0000-00-00', 'M', NULL, NULL, NULL, NULL, NULL, '2025-05-20 12:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `patient_assignments`
--

CREATE TABLE `patient_assignments` (
  `assignment_id` int(11) NOT NULL,
  `bed_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `patient_type` enum('Inpatient','Outpatient') NOT NULL,
  `assignment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Discharged') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_assignments`
--

INSERT INTO `patient_assignments` (`assignment_id`, `bed_id`, `first_name`, `middle_initial`, `last_name`, `diagnosis`, `patient_type`, `assignment_date`, `status`) VALUES
(2, 2, 'john', 'C', 'bathan', 'Cancer', 'Inpatient', '2025-05-20 05:52:02', 'Discharged'),
(3, 2, 'john', 'C', 'bathan', 'Cancer', 'Inpatient', '2025-05-20 06:02:03', 'Active'),
(4, 5, 'asdad', 'a', 'asdasd', 'asdad', 'Inpatient', '2025-05-20 12:01:15', 'Discharged'),
(5, 5, 'mark', 'a', 'asd', 'Lbm', 'Outpatient', '2025-05-20 12:12:38', 'Active'),
(6, 8, 'asd', 'a', 'asdasd', 'Lbm', 'Outpatient', '2025-05-20 12:16:07', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `patient_bed_assignments`
--

CREATE TABLE `patient_bed_assignments` (
  `assignment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `bed_id` int(11) NOT NULL,
  `admission_date` datetime NOT NULL,
  `discharge_date` datetime DEFAULT NULL,
  `status` enum('Active','Discharged','Transferred') NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_bed_assignments`
--

INSERT INTO `patient_bed_assignments` (`assignment_id`, `patient_id`, `bed_id`, `admission_date`, `discharge_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 6, 6, '2025-05-25 20:16:00', NULL, 'Active', NULL, '2025-05-20 12:16:20', '2025-05-20 12:16:20'),
(3, 11, 9, '2025-05-21 20:19:00', NULL, 'Active', NULL, '2025-05-20 12:19:30', '2025-05-20 12:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `patient_monitoring`
--

CREATE TABLE `patient_monitoring` (
  `monitoring_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `pulse_rate` int(11) DEFAULT NULL,
  `condition` enum('stable','critical','improving','deteriorating') NOT NULL DEFAULT 'stable',
  `monitoring_time` datetime NOT NULL,
  `treatment_plan` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_monitoring`
--

INSERT INTO `patient_monitoring` (`monitoring_id`, `patient_id`, `temperature`, `blood_pressure`, `pulse_rate`, `condition`, `monitoring_time`, `treatment_plan`, `created_by`, `created_at`) VALUES
(3, 1, 35.1, '120/80', 43, 'stable', '2025-05-19 17:40:00', '2123', 1, '2025-05-19 15:41:55'),
(5, 11, 35.8, '130/70', 45, 'improving', '2025-05-25 14:49:00', 'as', 1, '2025-05-21 12:49:58');

-- --------------------------------------------------------

--
-- Table structure for table `patient_packages`
--

CREATE TABLE `patient_packages` (
  `patient_package_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_packages`
--

INSERT INTO `patient_packages` (`patient_package_id`, `patient_id`, `package_id`, `start_date`, `end_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 4, '2025-05-14', '2025-05-21', 'active', 'asdadd', '2025-05-14 14:25:43', '2025-05-14 14:25:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `patient_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `treatment_plan` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `bill_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` enum('Cash','Credit Card','Insurance','Other') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `patient_id`, `first_name`, `middle_initial`, `last_name`, `diagnosis`, `treatment_plan`, `payment_status`, `bill_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `received_by`, `created_at`) VALUES
(3, '1', 'john', 'm', 'cena', 'Cancer', 'asd', 'paid', NULL, 0.00, '0000-00-00 00:00:00', 'Cash', NULL, NULL, '2025-05-19 14:55:19');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_inventory`
--

CREATE TABLE `pharmacy_inventory` (
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `category` enum('tablet','capsule','syrup','injection','cream') NOT NULL DEFAULT 'tablet',
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `manufacturer` varchar(255) NOT NULL,
  `expiry_date` date NOT NULL,
  `storage_location` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'In Stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_inventory`
--

INSERT INTO `pharmacy_inventory` (`medicine_id`, `medicine_name`, `category`, `quantity`, `unit`, `price`, `manufacturer`, `expiry_date`, `storage_location`, `status`) VALUES
(2, 'paracetamol', 'tablet', 5, 'boxes', 99.00, 'aghik', '2025-05-31', 'Shelf B2', 'Low Stock');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_orders`
--

CREATE TABLE `pharmacy_orders` (
  `order_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_orders`
--

INSERT INTO `pharmacy_orders` (`order_id`, `supplier_id`, `order_date`, `expected_delivery_date`, `status`, `priority`, `notes`, `created_at`) VALUES
(1, 1, '2025-05-14', '2025-05-21', 'completed', 'medium', 'Sample order for testing', '2025-05-14 13:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_order_items`
--

CREATE TABLE `pharmacy_order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_prescriptions`
--

CREATE TABLE `pharmacy_prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `doctor` varchar(255) NOT NULL,
  `prescription_date` date NOT NULL,
  `status` enum('pending','filled','rejected') DEFAULT 'pending',
  `dispensed_by` varchar(255) DEFAULT NULL,
  `dispensing_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_prescriptions`
--

INSERT INTO `pharmacy_prescriptions` (`prescription_id`, `patient_name`, `doctor`, `prescription_date`, `status`, `dispensed_by`, `dispensing_date`, `notes`, `created_at`) VALUES
(1, 'John Doe', 'Dr. Smith', '2025-05-19', 'pending', NULL, NULL, NULL, '2025-05-19 14:47:56'),
(2, 'Jane Smith', 'Dr. Johnson', '2025-05-19', 'pending', NULL, NULL, NULL, '2025-05-19 14:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_prescription_items`
--

CREATE TABLE `pharmacy_prescription_items` (
  `item_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_sales`
--

CREATE TABLE `pharmacy_sales` (
  `sale_id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) NOT NULL,
  `sale_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_sales`
--

INSERT INTO `pharmacy_sales` (`sale_id`, `prescription_id`, `patient_name`, `sale_date`, `total_amount`, `payment_method`, `created_by`, `created_at`) VALUES
(1, NULL, 'John Doe', '2025-05-21', 20.00, 'Cash', 'Admin', '2025-05-21 12:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_sale_items`
--

CREATE TABLE `pharmacy_sale_items` (
  `item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_suppliers`
--

CREATE TABLE `pharmacy_suppliers` (
  `supplier_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_suppliers`
--

INSERT INTO `pharmacy_suppliers` (`supplier_id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `status`, `created_at`) VALUES
(1, 'PharmaCorp Inc.', 'Will Smith', 'will@pharmacorp.com', '+1234567890', '123 Pharma St.', 'active', '2025-05-14 13:52:02'),
(2, 'MediSupply Co.', 'Jane', 'jane@medisupply.com', '+0987654321', '456 Medi Ave.', 'inactive', '2025-05-14 13:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `prescribed_by` int(11) DEFAULT NULL,
  `prescription_date` datetime NOT NULL,
  `status` enum('Pending','Filled','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `item_id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `dosage` varchar(50) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_utilization`
--

CREATE TABLE `service_utilization` (
  `utilization_id` int(11) NOT NULL,
  `patient_package_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `usage_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `login_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `hire_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `company_name`, `contact_person`, `contact_number`, `email`, `address`, `status`, `created_at`) VALUES
(1, 'ABC Pharmaceuticals', 'John Doe', '09123456789', 'john@abcpharma.com', '123 Pharma St, Manila', 'active', '2025-05-21 12:46:38'),
(2, 'XYZ Medical Supplies', 'Jane Smith', '09234567890', 'jane@xyzmed.com', '456 Medical Ave, Quezon City', 'active', '2025-05-21 12:46:38'),
(3, 'MediCorp', 'Mike Johnson', '09345678901', 'mike@medicorp.com', '789 Health Blvd, Makati', 'active', '2025-05-21 12:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `ward`
--

CREATE TABLE `ward` (
  `ward_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `ward_id` int(11) NOT NULL,
  `ward_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `floor` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`ward_id`, `ward_name`, `capacity`, `floor`, `created_at`) VALUES
(1, 'General Ward', 30, '1st Floor', '2025-05-14 13:08:14'),
(2, 'ICU', 10, '2nd Floor', '2025-05-14 13:08:14'),
(3, 'Emergency', 15, '1st Floor', '2025-05-14 13:08:14'),
(4, 'Pediatric', 20, '3rd Floor', '2025-05-14 13:08:14');

-- --------------------------------------------------------

--
-- Table structure for table `ward_beds`
--

CREATE TABLE `ward_beds` (
  `bed_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `bed_number` varchar(10) NOT NULL,
  `status` enum('available','occupied','maintenance') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ward_beds`
--

INSERT INTO `ward_beds` (`bed_id`, `ward_id`, `room_number`, `bed_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '101', 'A1', 'available', '2025-05-14 12:58:16', '2025-05-14 12:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `ward_supplies`
--

CREATE TABLE `ward_supplies` (
  `supply_id` int(11) NOT NULL,
  `item_type` enum('linen','medical','equipment') NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` enum('pieces','boxes','sets') NOT NULL,
  `status` enum('available','low','out') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ward_supplies`
--

INSERT INTO `ward_supplies` (`supply_id`, `item_type`, `item_name`, `quantity`, `unit`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'medical', 'Bed Sheets', 3, 'boxes', 'available', 'asdasd', 1, '2025-05-14 13:24:56', '2025-05-14 13:25:03');

-- --------------------------------------------------------

--
-- Table structure for table `ward_types`
--

CREATE TABLE `ward_types` (
  `ward_type_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `daily_rate` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `beds`
--
ALTER TABLE `beds`
  ADD PRIMARY KEY (`bed_id`),
  ADD UNIQUE KEY `room_bed` (`room_number`,`bed_number`);

--
-- Indexes for table `bed_allocations`
--
ALTER TABLE `bed_allocations`
  ADD PRIMARY KEY (`allocation_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_bill_status` (`status`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `diet_management`
--
ALTER TABLE `diet_management`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `homis_bills`
--
ALTER TABLE `homis_bills`
  ADD PRIMARY KEY (`bill_id`);

--
-- Indexes for table `homis_inventory`
--
ALTER TABLE `homis_inventory`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `homis_inventory_transactions`
--
ALTER TABLE `homis_inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `homis_payments`
--
ALTER TABLE `homis_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `inpatient_outpatient`
--
ALTER TABLE `inpatient_outpatient`
  ADD PRIMARY KEY (`patientpk`);

--
-- Indexes for table `linen_assignments`
--
ALTER TABLE `linen_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `linen_type_id` (`linen_type_id`);

--
-- Indexes for table `linen_inventory`
--
ALTER TABLE `linen_inventory`
  ADD PRIMARY KEY (`linen_id`);

--
-- Indexes for table `linen_laundry`
--
ALTER TABLE `linen_laundry`
  ADD PRIMARY KEY (`laundry_id`),
  ADD KEY `linen_id` (`linen_id`);

--
-- Indexes for table `linen_laundry_requests`
--
ALTER TABLE `linen_laundry_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `linen_types`
--
ALTER TABLE `linen_types`
  ADD PRIMARY KEY (`linen_type_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `medical_packages`
--
ALTER TABLE `medical_packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD PRIMARY KEY (`mrn`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_medicine_name` (`name`);

--
-- Indexes for table `medicine_categories`
--
ALTER TABLE `medicine_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `nurses`
--
ALTER TABLE `nurses`
  ADD PRIMARY KEY (`nurse_id`);

--
-- Indexes for table `nurse_assignments`
--
ALTER TABLE `nurse_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `ward_id` (`ward_id`);

--
-- Indexes for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  ADD PRIMARY KEY (`shift_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `ward_id` (`ward_id`);

--
-- Indexes for table `package_payments`
--
ALTER TABLE `package_payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `package_services`
--
ALTER TABLE `package_services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `patient_package_id` (`patient_package_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `idx_patient_name` (`last_name`,`first_name`);

--
-- Indexes for table `patient_assignments`
--
ALTER TABLE `patient_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `bed_id` (`bed_id`);

--
-- Indexes for table `patient_bed_assignments`
--
ALTER TABLE `patient_bed_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `bed_id` (`bed_id`);

--
-- Indexes for table `patient_monitoring`
--
ALTER TABLE `patient_monitoring`
  ADD PRIMARY KEY (`monitoring_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_packages`
--
ALTER TABLE `patient_packages`
  ADD PRIMARY KEY (`patient_package_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  ADD PRIMARY KEY (`medicine_id`);

--
-- Indexes for table `pharmacy_orders`
--
ALTER TABLE `pharmacy_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `pharmacy_order_items`
--
ALTER TABLE `pharmacy_order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `pharmacy_prescriptions`
--
ALTER TABLE `pharmacy_prescriptions`
  ADD PRIMARY KEY (`prescription_id`);

--
-- Indexes for table `pharmacy_prescription_items`
--
ALTER TABLE `pharmacy_prescription_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `pharmacy_sales`
--
ALTER TABLE `pharmacy_sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `pharmacy_sale_items`
--
ALTER TABLE `pharmacy_sale_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `pharmacy_suppliers`
--
ALTER TABLE `pharmacy_suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `prescribed_by` (`prescribed_by`),
  ADD KEY `idx_prescription_status` (`status`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `service_utilization`
--
ALTER TABLE `service_utilization`
  ADD PRIMARY KEY (`utilization_id`),
  ADD KEY `patient_package_id` (`patient_package_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `login_id` (`login_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `ward`
--
ALTER TABLE `ward`
  ADD PRIMARY KEY (`ward_id`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`ward_id`);

--
-- Indexes for table `ward_beds`
--
ALTER TABLE `ward_beds`
  ADD PRIMARY KEY (`bed_id`),
  ADD UNIQUE KEY `room_bed` (`room_number`,`bed_number`);

--
-- Indexes for table `ward_supplies`
--
ALTER TABLE `ward_supplies`
  ADD PRIMARY KEY (`supply_id`);

--
-- Indexes for table `ward_types`
--
ALTER TABLE `ward_types`
  ADD PRIMARY KEY (`ward_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `beds`
--
ALTER TABLE `beds`
  MODIFY `bed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bed_allocations`
--
ALTER TABLE `bed_allocations`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diet_management`
--
ALTER TABLE `diet_management`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `homis_bills`
--
ALTER TABLE `homis_bills`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `homis_inventory`
--
ALTER TABLE `homis_inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `homis_inventory_transactions`
--
ALTER TABLE `homis_inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `homis_payments`
--
ALTER TABLE `homis_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inpatient_outpatient`
--
ALTER TABLE `inpatient_outpatient`
  MODIFY `patientpk` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `linen_assignments`
--
ALTER TABLE `linen_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `linen_inventory`
--
ALTER TABLE `linen_inventory`
  MODIFY `linen_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `linen_laundry`
--
ALTER TABLE `linen_laundry`
  MODIFY `laundry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `linen_laundry_requests`
--
ALTER TABLE `linen_laundry_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `linen_types`
--
ALTER TABLE `linen_types`
  MODIFY `linen_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_packages`
--
ALTER TABLE `medical_packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `mrn` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_categories`
--
ALTER TABLE `medicine_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `nurse_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nurse_assignments`
--
ALTER TABLE `nurse_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_payments`
--
ALTER TABLE `package_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `package_services`
--
ALTER TABLE `package_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `patient_assignments`
--
ALTER TABLE `patient_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `patient_bed_assignments`
--
ALTER TABLE `patient_bed_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient_monitoring`
--
ALTER TABLE `patient_monitoring`
  MODIFY `monitoring_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `patient_packages`
--
ALTER TABLE `patient_packages`
  MODIFY `patient_package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pharmacy_orders`
--
ALTER TABLE `pharmacy_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pharmacy_order_items`
--
ALTER TABLE `pharmacy_order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_prescriptions`
--
ALTER TABLE `pharmacy_prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pharmacy_prescription_items`
--
ALTER TABLE `pharmacy_prescription_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_sales`
--
ALTER TABLE `pharmacy_sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pharmacy_sale_items`
--
ALTER TABLE `pharmacy_sale_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pharmacy_suppliers`
--
ALTER TABLE `pharmacy_suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_utilization`
--
ALTER TABLE `service_utilization`
  MODIFY `utilization_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ward`
--
ALTER TABLE `ward`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ward_beds`
--
ALTER TABLE `ward_beds`
  MODIFY `bed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ward_supplies`
--
ALTER TABLE `ward_supplies`
  MODIFY `supply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ward_types`
--
ALTER TABLE `ward_types`
  MODIFY `ward_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`);

--
-- Constraints for table `homis_inventory_transactions`
--
ALTER TABLE `homis_inventory_transactions`
  ADD CONSTRAINT `homis_inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `homis_inventory` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `homis_payments`
--
ALTER TABLE `homis_payments`
  ADD CONSTRAINT `homis_payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `homis_bills` (`bill_id`) ON DELETE CASCADE;

--
-- Constraints for table `linen_assignments`
--
ALTER TABLE `linen_assignments`
  ADD CONSTRAINT `linen_assignments_ibfk_1` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`ward_id`),
  ADD CONSTRAINT `linen_assignments_ibfk_2` FOREIGN KEY (`linen_type_id`) REFERENCES `linen_types` (`linen_type_id`);

--
-- Constraints for table `linen_laundry`
--
ALTER TABLE `linen_laundry`
  ADD CONSTRAINT `linen_laundry_ibfk_1` FOREIGN KEY (`linen_id`) REFERENCES `linen_inventory` (`linen_id`) ON UPDATE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `medicine_categories` (`category_id`);

--
-- Constraints for table `nurse_assignments`
--
ALTER TABLE `nurse_assignments`
  ADD CONSTRAINT `nurse_assignments_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`nurse_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `nurse_assignments_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`ward_id`) ON UPDATE CASCADE;

--
-- Constraints for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  ADD CONSTRAINT `nurse_shifts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `nurse_shifts_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`ward_id`);

--
-- Constraints for table `package_services`
--
ALTER TABLE `package_services`
  ADD CONSTRAINT `package_services_ibfk_1` FOREIGN KEY (`patient_package_id`) REFERENCES `patient_packages` (`patient_package_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_assignments`
--
ALTER TABLE `patient_assignments`
  ADD CONSTRAINT `patient_assignments_ibfk_1` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`bed_id`);

--
-- Constraints for table `patient_bed_assignments`
--
ALTER TABLE `patient_bed_assignments`
  ADD CONSTRAINT `patient_bed_assignments_ibfk_1` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`bed_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_monitoring`
--
ALTER TABLE `patient_monitoring`
  ADD CONSTRAINT `patient_monitoring_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_packages`
--
ALTER TABLE `patient_packages`
  ADD CONSTRAINT `patient_packages_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `medical_packages` (`package_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `billing` (`bill_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `pharmacy_orders`
--
ALTER TABLE `pharmacy_orders`
  ADD CONSTRAINT `pharmacy_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `pharmacy_suppliers` (`supplier_id`);

--
-- Constraints for table `pharmacy_order_items`
--
ALTER TABLE `pharmacy_order_items`
  ADD CONSTRAINT `pharmacy_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `pharmacy_orders` (`order_id`),
  ADD CONSTRAINT `pharmacy_order_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `pharmacy_inventory` (`medicine_id`);

--
-- Constraints for table `pharmacy_prescription_items`
--
ALTER TABLE `pharmacy_prescription_items`
  ADD CONSTRAINT `pharmacy_prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `pharmacy_prescriptions` (`prescription_id`),
  ADD CONSTRAINT `pharmacy_prescription_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `pharmacy_inventory` (`medicine_id`);

--
-- Constraints for table `pharmacy_sales`
--
ALTER TABLE `pharmacy_sales`
  ADD CONSTRAINT `pharmacy_sales_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `pharmacy_prescriptions` (`prescription_id`);

--
-- Constraints for table `pharmacy_sale_items`
--
ALTER TABLE `pharmacy_sale_items`
  ADD CONSTRAINT `pharmacy_sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `pharmacy_sales` (`sale_id`),
  ADD CONSTRAINT `pharmacy_sale_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `pharmacy_inventory` (`medicine_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`prescribed_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`),
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`);

--
-- Constraints for table `service_utilization`
--
ALTER TABLE `service_utilization`
  ADD CONSTRAINT `service_utilization_ibfk_1` FOREIGN KEY (`patient_package_id`) REFERENCES `patient_packages` (`patient_package_id`),
  ADD CONSTRAINT `service_utilization_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `package_services` (`service_id`),
  ADD CONSTRAINT `service_utilization_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`login_id`) REFERENCES `login` (`id`),
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
