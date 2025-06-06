-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2025 at 08:17 PM
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
-- Database: `hospital_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ambulances`
--

CREATE TABLE `ambulances` (
  `id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `driver_phone` varchar(20) NOT NULL,
  `status` enum('available','busy','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambulances`
--

INSERT INTO `ambulances` (`id`, `plate_number`, `driver_name`, `driver_phone`, `status`, `created_at`, `updated_at`) VALUES
(1, '2357', 'محمد على ', '01123445841', 'available', '2025-04-07 19:31:33', '2025-04-07 19:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_requests`
--

CREATE TABLE `ambulance_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_location` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `emergency_level` enum('low','medium','high') NOT NULL,
  `patient_condition` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','completed') DEFAULT 'pending',
  `assigned_ambulance_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambulance_requests`
--

INSERT INTO `ambulance_requests` (`id`, `patient_id`, `request_date`, `pickup_location`, `destination`, `emergency_level`, `patient_condition`, `additional_notes`, `status`, `assigned_ambulance_id`, `created_at`, `updated_at`) VALUES
(2, 3, '2025-04-07 19:44:11', 'بني زيد الاكراد', 'بني زيدالاكراد', 'high', 'متعب للغايه ', 'لايوجد', 'pending', 1, '2025-04-07 19:44:11', '2025-04-07 19:44:47'),
(3, 3, '2025-04-28 21:16:25', 'بني زيد الاكراد', 'بني زيدالاكراد', 'high', 'متعب جدا', 'المريض يحتاج رعايه خاصه ', 'pending', NULL, '2025-04-28 21:16:25', '2025-04-28 21:16:25'),
(4, 3, '2025-04-28 21:17:06', 'بني زيد الاكراد', 'بني زيدالاكراد', 'high', 'متعب جدا', 'المريض يحتاج رعايه خاصه ', 'pending', NULL, '2025-04-28 21:17:06', '2025-04-28 21:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `video_call_id` int(11) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `status`, `created_at`, `updated_at`, `video_call_id`, `payment_status`) VALUES
(1, 1, 1, '2025-04-04', '10:00:00', NULL, 'confirmed', '2025-04-04 10:57:42', '2025-04-04 18:00:03', NULL, 'pending'),
(4, 3, 1, '2025-04-05', '17:00:00', NULL, 'pending', '2025-04-04 13:18:16', '2025-04-07 13:38:28', NULL, 'pending'),
(5, 3, 1, '2025-04-04', '17:00:00', NULL, 'cancelled', '2025-04-04 13:25:47', '2025-04-07 04:24:44', NULL, 'pending'),
(6, 3, 1, '2025-04-23', '10:30:00', 'الم ', 'cancelled', '2025-04-07 04:56:36', '2025-04-07 05:30:36', NULL, 'pending'),
(7, 3, 2, '2025-04-11', '11:30:00', 'الام في المفاصل\r\n', 'cancelled', '2025-04-07 05:02:38', '2025-04-07 05:30:43', 2, 'pending'),
(14, 3, 1, '2025-04-19', '09:30:00', NULL, 'cancelled', '2025-04-07 05:18:29', '2025-04-07 05:30:30', NULL, 'pending'),
(15, 3, 1, '2025-04-18', '09:30:00', NULL, 'cancelled', '2025-04-07 05:20:20', '2025-04-07 05:30:41', NULL, 'pending'),
(16, 3, 1, '2025-04-09', '10:04:00', NULL, 'cancelled', '2025-04-07 06:02:09', '2025-04-07 06:19:26', NULL, 'pending'),
(17, 3, 1, '2025-04-25', '08:09:00', NULL, 'cancelled', '2025-04-07 06:06:38', '2025-04-07 06:19:21', NULL, 'pending'),
(18, 3, 1, '2025-04-08', '08:13:00', 'الام في المفاصل', 'confirmed', '2025-04-07 06:10:33', '2025-04-07 10:03:41', NULL, 'paid'),
(19, 3, 1, '2025-04-14', '00:23:00', 'الام في المفاصل\r\n', 'cancelled', '2025-04-07 06:19:56', '2025-04-07 13:38:37', NULL, 'paid'),
(20, 3, 1, '2025-04-07', '08:23:00', '', 'confirmed', '2025-04-07 06:22:00', '2025-04-07 06:22:47', NULL, 'paid'),
(21, 3, 6, '2025-04-07', '11:57:00', 'اللام في المفاصل', 'confirmed', '2025-04-07 09:50:24', '2025-04-07 09:52:02', NULL, 'paid'),
(22, 3, 24, '2025-04-21', '23:57:00', 'الام في البطن والرقبه\r\n', 'confirmed', '2025-04-20 15:51:40', '2025-04-20 15:56:12', NULL, 'paid'),
(23, 3, 24, '2025-04-30', '23:00:00', '', 'pending', '2025-04-27 20:56:46', '2025-04-27 20:56:46', NULL, 'pending'),
(24, 3, 24, '2025-04-28', '10:00:00', NULL, 'pending', '2025-04-27 21:38:16', '2025-04-27 21:38:16', NULL, 'pending'),
(25, 3, 6, '2025-04-29', '06:43:00', '', 'pending', '2025-04-28 22:40:02', '2025-04-28 22:40:02', NULL, 'pending'),
(26, 3, 6, '2025-05-14', '08:47:00', 'الم مزمن ', 'confirmed', '2025-05-13 00:42:48', '2025-06-03 17:31:54', NULL, 'paid'),
(27, 3, 1, '2025-05-14', '06:45:00', 'الم في البطن ', 'confirmed', '2025-05-13 00:44:08', '2025-05-14 17:27:05', NULL, 'paid'),
(28, 3, 24, '2025-05-14', '06:55:00', 'عمل عمليه جراحيه \r\n', 'pending', '2025-05-13 00:53:34', '2025-05-13 00:53:34', NULL, 'pending'),
(40, 3, 24, '2025-05-20', '22:16:00', 'عمليات ', 'cancelled', '2025-05-14 17:14:29', '2025-05-14 17:19:42', NULL, 'pending'),
(41, 3, 24, '2025-05-16', '12:21:00', '', 'pending', '2025-05-14 17:17:58', '2025-05-14 17:17:58', NULL, 'pending'),
(42, 3, 6, '2025-06-05', '23:23:00', '', 'confirmed', '2025-05-14 17:20:15', '2025-05-14 19:54:16', NULL, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`) VALUES
(1, 'Cardiology', 'Heart and cardiovascular system specialists'),
(2, 'Neurology', 'Brain and nervous system specialists'),
(3, 'Pediatrics', 'Child healthcare specialists'),
(4, 'Orthopedics', 'Bone and joint specialists'),
(6, 'قسم الجراحه ', 'جراحه الفكين'),
(7, 'قسم النسا والولاده ', 'نسا و ولاده\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `dispensed_medicines`
--

CREATE TABLE `dispensed_medicines` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `pharmacist_id` int(11) NOT NULL,
  `dispense_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('complete','partial') DEFAULT 'complete',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispensed_medicines`
--

INSERT INTO `dispensed_medicines` (`id`, `prescription_id`, `pharmacist_id`, `dispense_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 10, 3, '2025-05-14 23:44:42', 'complete', 'تم صرف الأدوية بواسطة الصيدلي', '2025-05-14 23:44:42', '2025-05-14 23:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `specialization` varchar(100) NOT NULL,
  `qualification` varchar(100) NOT NULL,
  `experience` int(11) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `payment_card` varchar(19) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default-profile.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `status`, `specialization`, `qualification`, `experience`, `consultation_fee`, `payment_card`, `department_id`, `profile_picture`) VALUES
(1, 2, 'John', 'Smith', 'john.smith@hospital.com', '+1234567890', 'active', 'Cardiology', 'MD in Cardiology', 10, 150.00, NULL, 1, 'default-profile.png'),
(2, 3, 'Sarah', 'Jones', 'sarah.jones@hospital.com', '+1234567890', 'active', 'Pediatrics', 'MD in Pediatrics', 8, 120.00, NULL, 1, 'default-profile.png'),
(6, 14, 'OSAMA', 'MOHAMED', 'osama@hospital.com', '01120786462', 'active', 'نسا و ولاده', 'كليه الطب جامعه الازهر', 30, 150.00, NULL, 7, '6825bbfb9b81e.jpg'),
(24, 53, 'سامح ', 'حسين', NULL, NULL, 'active', 'جراحه', 'كليه الطب جامعه الازهر', 30, 1500.00, NULL, 6, 'default-profile.png'),
(25, 68, 'omar', 'elseemy', NULL, NULL, 'active', 'جراحه', 'كليه صيدله جامعه القاهره', 50, 1500.00, '123456789123456', 6, 'default-profile.png'),
(26, 69, 'ahmed', 'gamal', NULL, NULL, 'active', 'النسا', 'كليه الطب جامعه الازهر', 15, 150.00, '1234567891234567', 2, 'default-profile.png');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_holidays`
--

CREATE TABLE `doctor_holidays` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedule`
--

CREATE TABLE `doctor_schedule` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('sunday','monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_working` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedule`
--

INSERT INTO `doctor_schedule` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `is_working`, `created_at`, `updated_at`) VALUES
(1, 6, 'saturday', '09:00:00', '17:00:00', 1, '2025-04-07 14:55:56', '2025-04-07 14:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_vacations`
--

CREATE TABLE `doctor_vacations` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_vacations`
--

INSERT INTO `doctor_vacations` (`id`, `doctor_id`, `start_date`, `end_date`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, '2025-10-23', '2025-10-24', 'i have appointment', 'approved', '2025-04-07 15:07:55', '2025-04-07 15:15:05'),
(2, 6, '2025-04-22', '2025-04-23', 'i have appointment', 'rejected', '2025-04-21 19:00:59', '2025-04-21 19:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `medical_attachments`
--

CREATE TABLE `medical_attachments` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `visit_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `patient_id`, `doctor_id`, `diagnosis`, `treatment`, `prescription`, `notes`, `visit_date`, `created_at`, `updated_at`) VALUES
(2, 3, 6, 'اعاتنا', NULL, 'ىةوىتناتن', 'نتاناتن', '0000-00-00', '2025-04-07 11:41:57', '2025-04-07 11:41:57'),
(3, 3, 6, 'يتنمي', NULL, 'يخبنمكي', 'يمبتنتي\\', '0000-00-00', '2025-04-07 11:42:28', '2025-04-07 11:42:28'),
(4, 3, 6, 'قشره في الشعر ', NULL, 'زيت اركان ', 'لا يوجد ملاجظات طبيه ', '0000-00-00', '2025-04-07 12:04:44', '2025-04-07 12:04:44'),
(5, 3, 6, 'كرونا ', NULL, 'انتي بيوتك ', 'لا يوجد', '0000-00-00', '2025-04-07 14:02:38', '2025-04-07 14:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `medical_reports`
--

CREATE TABLE `medical_reports` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_reports`
--

INSERT INTO `medical_reports` (`id`, `patient_id`, `doctor_id`, `appointment_id`, `diagnosis`, `prescription`, `report_file`, `created_at`) VALUES
(1, 1, 1, 1, 'Regular checkup - Normal', 'Continue regular exercise and healthy diet', NULL, '2025-04-04 10:57:42'),
(2, 1, 1, 1, 'Regular checkup - Normal', 'Continue regular exercise and healthy diet', NULL, '2025-04-07 10:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `type` enum('حبوب','شراب','حقن','مرهم','قطرة','أخرى') NOT NULL,
  `dosage_form` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 10,
  `expiry_date` date NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `alternatives` text DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `generic_name`, `category`, `type`, `dosage_form`, `manufacturer`, `quantity_in_stock`, `min_stock_level`, `expiry_date`, `purchase_price`, `selling_price`, `description`, `side_effects`, `alternatives`, `requires_prescription`, `created_at`, `updated_at`, `price`) VALUES
(1, 'باراسيتامول', 'Paracetamol', 'مسكنات', 'حبوب', 'أقراص', 'شركة الدواء المصرية', 100, 20, '2026-04-30', 8.50, 10.50, 'مسكن للألم وخافض للحرارة', 'نادرة عند الاستخدام بالجرعات الموصى بها', 'أدفيل، بروفين', 0, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(2, 'أموكسيسيلين', 'Amoxicillin', 'مضادات حيوية', 'حبوب', 'أقراص', 'شركة الدواء العربية', 80, 15, '2025-10-15', 20.00, 25.75, 'مضاد حيوي واسع المجال', 'طفح جلدي، إسهال، غثيان', 'أوجمنتين، سيفالكسين', 1, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(3, 'سالبوتامول', 'Salbutamol', 'أدوية الجهاز التنفسي', 'شراب', 'أقراص', 'شركة الدواء العالمية', 49, 10, '2025-08-20', 30.00, 35.00, 'موسع للشعب الهوائية', 'تسارع ضربات القلب، رعشة', 'فينتولين، بروفينتيل', 1, '2025-04-30 07:00:00', '2025-05-14 23:44:42', 0.00),
(4, 'ديكلوفيناك', 'Diclofenac', 'مضادات التهاب', 'مرهم', 'أقراص', 'شركة الدواء الوطنية', 60, 12, '2025-05-10', 12.00, 15.25, 'مضاد للالتهاب', 'تهيج جلدي، حرقان', 'فولتارين، كيتوبروفين', 0, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(6, 'باراسيتامول', 'Paracetamol', 'مسكنات', 'حبوب', 'أقراص', 'شركة الدواء المصرية', 100, 20, '2026-04-30', 8.50, 10.50, 'مسكن للألم وخافض للحرارة', 'نادرة عند الاستخدام بالجرعات الموصى بها', 'أدفيل، بروفين', 0, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(7, 'أموكسيسيلين', 'Amoxicillin', 'مضادات حيوية', 'حبوب', 'أقراص', 'شركة الدواء العربية', 80, 15, '2025-10-15', 20.00, 25.75, 'مضاد حيوي واسع المجال', 'طفح جلدي، إسهال، غثيان', 'أوجمنتين، سيفالكسين', 1, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(8, 'سالبوتامول', 'Salbutamol', 'أدوية الجهاز التنفسي', 'شراب', 'أقراص', 'شركة الدواء العالمية', 50, 10, '2025-08-20', 30.00, 35.00, 'موسع للشعب الهوائية', 'تسارع ضربات القلب، رعشة', 'فينتولين، بروفينتيل', 1, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(9, 'ديكلوفيناك', 'Diclofenac', 'مضادات التهاب', 'مرهم', 'أقراص', 'شركة الدواء الوطنية', 60, 12, '2025-05-10', 12.00, 15.25, 'مضاد للالتهاب', 'تهيج جلدي، حرقان', 'فولتارين، كيتوبروفين', 0, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00),
(10, 'أتورفاستاتين', 'Atorvastatin', 'خافضات الكوليسترول', 'حبوب', 'أقراص', 'شركة الدواء الدولية', 70, 15, '2026-02-15', 45.00, 55.00, 'خافض للكوليسترول', 'ألم عضلي، صداع', 'روزوفاستاتين، سيمفاستاتين', 1, '2025-04-30 07:00:00', '2025-05-14 22:58:08', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `medicine_categories`
--

CREATE TABLE `medicine_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_categories`
--

INSERT INTO `medicine_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'مسكنات', 'أدوية لتخفيف الألم', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(2, 'مضادات حيوية', 'أدوية لعلاج العدوى البكتيرية', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(3, 'أدوية الجهاز التنفسي', 'أدوية لعلاج أمراض الجهاز التنفسي', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(4, 'مضادات التهاب', 'أدوية لتخفيف الالتهابات', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(5, 'خافضات الكوليسترول', 'أدوية لخفض مستوى الكوليسترول في الدم', '2025-04-30 07:00:00', '2025-04-30 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

CREATE TABLE `nurses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `assigned_doctor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`id`, `user_id`, `first_name`, `last_name`, `department`, `assigned_doctor_id`) VALUES
(11, 60, 'كارم', 'محمد', 'قسم النسا والولاده ', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nurse_shifts`
--

CREATE TABLE `nurse_shifts` (
  `id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('morning','afternoon','night') NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','O+','O-','AB+','AB-') DEFAULT 'A+',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `created_at`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`, `address`, `emergency_contact`, `emergency_phone`, `blood_type`, `updated_at`) VALUES
(1, 6, '2025-04-07 03:39:58', 'John', 'Doe', '1990-05-15', 'male', '1234567890', '', '', '', 'A+', '2025-04-04 13:52:55'),
(3, 12, '2025-04-07 03:39:58', 'ahmed ', 'hamdy', '2025-04-18', 'male', '01120786462', 'asyut elgomhorya\r\nelma3dy', 'محمود على ', '01033157604', 'A+', '2025-04-07 02:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacists`
--

CREATE TABLE `pharmacists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacists`
--

INSERT INTO `pharmacists` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `status`, `qualification`, `experience`, `created_at`, `updated_at`) VALUES
(1, 61, 'أحمد', 'محمد', 'ahmed.mohamed@hospital.com', '01234567890', 'active', 'بكالوريوس صيدلة', 5, '2025-04-30 04:00:00', '2025-04-30 04:00:00'),
(3, 65, 'نادر ', 'هاني ', '', NULL, 'active', 'كليه صيدله جامعه القاهره', NULL, '2025-05-14 19:51:28', '2025-05-14 19:51:28');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_inventory`
--

CREATE TABLE `pharmacy_inventory` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `status` enum('active','expired','low_stock') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_inventory`
--

INSERT INTO `pharmacy_inventory` (`id`, `medicine_id`, `batch_number`, `quantity`, `expiry_date`, `purchase_date`, `purchase_price`, `supplier`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'PARA-2025-001', 100, '2026-04-30', '2025-04-01', 8.50, 'شركة توزيع الأدوية المصرية', 'active', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(2, 2, 'AMOX-2025-001', 80, '2025-10-15', '2025-03-15', 20.00, 'شركة توزيع الأدوية العربية', 'active', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(3, 3, 'SALB-2025-001', 50, '2025-08-20', '2025-02-20', 30.00, 'شركة توزيع الأدوية العالمية', 'active', '2025-04-30 07:00:00', '2025-04-30 07:00:00'),
(4, 4, 'DICL-2025-001', 60, '2025-05-10', '2025-01-10', 12.00, 'شركة توزيع الأدوية الوطنية', 'active', '2025-04-30 07:00:00', '2025-04-30 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `medical_record_id` int(11) DEFAULT NULL,
  `prescription_date` date NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','dispensed','partially_dispensed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `patient_id`, `doctor_id`, `medical_record_id`, `prescription_date`, `diagnosis`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(8, 3, 6, NULL, '2025-05-14', 'اكزيما ', 'الأدوية المصروفة:\r\n- باراسيتامول (الكمية: 1)\r\n  الجرعة: قرص واحد, التكرار: 3 مرات يوميا , المدة: 5 ايام , تعليمات: حسب الحاجة', 'dispensed', '2025-05-14 20:14:46', '2025-05-14 20:16:23'),
(9, 3, 6, NULL, '2025-05-15', 'كدمه ', 'الأدوية المصروفة:\r\n- أموكسيسيلين (الكمية: 1)\r\n  الجرعة: قرص واحد, التكرار: 3 مرات يوميا , المدة: 5 ايام , تعليمات: قبل الطعام', 'dispensed', '2025-05-14 22:31:35', '2025-05-14 23:30:10'),
(10, 3, 6, NULL, '2025-05-15', 'اكزيما', '\n\nالأدوية المصروفة:\n- سالبوتامول (الكمية: 1)\n  الجرعة: قرص واحد, التكرار: 3 مرات يوميا , المدة: 5 ايام , تعليمات: مع الطعام\n', 'dispensed', '2025-05-14 23:39:32', '2025-05-14 23:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `dispensed` tinyint(1) DEFAULT 0,
  `dispensed_quantity` int(11) DEFAULT 0,
  `dispensed_date` timestamp NULL DEFAULT NULL,
  `dispensed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_medicines`
--

CREATE TABLE `prescription_medicines` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `dosage_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_medicines`
--

INSERT INTO `prescription_medicines` (`id`, `prescription_id`, `medicine_id`, `quantity`, `dosage_instructions`, `created_at`, `updated_at`) VALUES
(1, 10, 3, 1, 'الجرعة:  التكرار:  المدة:  تعليمات: مع الطعام', '2025-05-14 23:39:32', '2025-05-14 23:39:32');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `hospital_name` varchar(100) NOT NULL,
  `hospital_address` text NOT NULL,
  `hospital_phone` varchar(20) NOT NULL,
  `hospital_email` varchar(100) NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL,
  `appointment_duration` int(11) NOT NULL,
  `working_hours_start` time NOT NULL,
  `working_hours_end` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `hospital_name`, `hospital_address`, `hospital_phone`, `hospital_email`, `consultation_fee`, `appointment_duration`, `working_hours_start`, `working_hours_end`, `created_at`, `updated_at`) VALUES
(1, 'Birmistan Future Hospital', '123 Hospital Street', '1234567890', 'info@hospital.com', 100.00, 30, '09:00:00', '17:00:00', '2025-04-04 13:04:14', '2025-04-04 13:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `test_reports`
--

CREATE TABLE `test_reports` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `test_date` date NOT NULL,
  `result` text DEFAULT NULL,
  `reference_range` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('normal','abnormal','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','doctor','nurse','patient','pharmacist') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `updated_at`, `phone`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hospital.com', 'admin', '2025-04-04 10:57:41', '2025-04-04 10:57:41', NULL),
(2, 'dr.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr.smith@hospital.com', 'doctor', '2025-04-04 10:57:41', '2025-04-04 10:57:41', NULL),
(3, 'dr.jones', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr.jones@hospital.com', 'doctor', '2025-04-04 10:57:41', '2025-04-04 10:57:41', NULL),
(6, 'patient.doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient.doe@email.com', 'patient', '2025-04-04 10:57:41', '2025-04-04 10:57:41', NULL),
(7, 'patient.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient.smith@email.com', 'patient', '2025-04-04 10:57:41', '2025-04-04 10:57:41', NULL),
(12, 'ahmedhamdy', '$2y$10$0sGAdd/QEUP7UDERWQQTnePXYUMHSPzaiCtJy3IoWiAk81R1xyz7S', 'ahmedhamdy23@gmail.com', 'patient', '2025-04-04 13:14:49', '2025-04-07 02:29:29', NULL),
(13, 'ahmedhamdy23@gmail.com', '$2y$10$3C0hWFe0r8/CEERk8c/bsuzx48RF6huG7K9pdsNX/Ty/DGTBKMiki', 'ayman@gmail.com', 'doctor', '2025-04-07 04:25:59', '2025-04-07 04:25:59', NULL),
(14, 'OSAMA', '$2y$10$NDzvX1gdcSSCfl1Xx9OCx.As/.cyjbbPIBMQC35cmRQF4/yXXOM6q', 'OSAMA@HOSPITAL.COM', 'doctor', '2025-04-07 04:45:38', '2025-04-07 04:45:38', NULL),
(15, 'ahmedmohamed', '$2y$10$Wy0o/tuA1Q.s/kaMkluJdu7ItOf31S9o/NUuaYpQjgT7sbW13bbhS', 'ahmedmohamed@gmail.com', 'patient', '2025-04-08 05:14:58', '2025-04-08 05:14:58', NULL),
(20, 'mohamed.ragab', '$2y$10$pVjq8kZbyTtNgUKcBWXJxeUKFZi1ZJLneLynQ/uH1mvQQ.LhKTOJa', 'mohamedragab@gmail.com', 'patient', '2025-04-19 19:23:30', '2025-04-19 19:23:30', NULL),
(22, 'yasser.elseemy', '$2y$10$UwkMqzXhd3PngvjE23Fg2eMKrGk9ECD0TZ2.vSssb56v44OixBdMK', 'yasser@gmail.com', 'patient', '2025-04-19 19:52:34', '2025-04-19 19:52:34', NULL),
(23, '', '$2y$10$6F9gU3TSQTmuFI/HgJIg7uVTVquDgDR7ltDQsP08PPthEmgOXlAYq', 'hossam@gmial.com', 'doctor', '2025-04-19 20:04:45', '2025-04-19 20:04:45', NULL),
(26, 'AHMEDRAMZY', '$2y$10$spETUo/nU9jqcchwyIHxv.XievgL3av.MGp/YrSiVivrnDaC5MOhS', 'ahmedramzy@gmail.com', 'doctor', '2025-04-19 20:12:26', '2025-04-19 20:14:44', NULL),
(28, 'yousef.ossama', '$2y$10$DWzuK34F5zg5rixGKyC5QO3IYfQhVlW8iLjmCaO7IRQex/PpgqeXW', 'you@gmail.com', 'doctor', '2025-04-19 20:31:05', '2025-04-19 20:31:05', NULL),
(29, 'محمود..غانم', '$2y$10$9Fks2NzgYKKBkrfA3PsMReqq0Krs63pM4cJuXxmIfLCoigwKMTeHi', 'ghanem@gmail.com', 'doctor', '2025-04-19 20:36:59', '2025-04-19 20:36:59', NULL),
(31, 'ahmed.geemy', '$2y$10$s7BU6QPQHpzMcLluSh0GxOnRRnhXl6.71VEkaTUchcriJ9mOSgDPa', 'geemy@gmail.com', 'doctor', '2025-04-19 20:46:40', '2025-04-19 20:46:40', NULL),
(32, 'ahmed hussien', '$2y$10$hfo4TZ/UJfM/CEEprq2hM.f6jt9Tb6ZYV.os/puiWC0Ebl6gQ0ZQS', 'ahmedhussien@gmail.com', 'doctor', '2025-04-19 20:58:11', '2025-04-19 20:58:11', NULL),
(43, 'kareem ', '$2y$10$w39lC9f5UIKJ9.daUOh6veQfTOqpUnnDM0.VIdpxF4.S.IF1X1kDa', 'kareem@gmail.com', 'doctor', '2025-04-19 22:17:03', '2025-04-19 22:17:03', NULL),
(53, 'سامح..حسين', '$2y$10$pdp4XidwGpBe.Nb5FiZbZOjo4Eay3LQCvZSdJNatw0UNkm8.1SU1y', 'sameh@gmail.com', 'doctor', '2025-04-20 15:49:33', '2025-04-20 15:49:33', NULL),
(60, 'karm', '$2y$10$uSSPjBukb1rjaFNuqG3B4.qNvOzstKMlyyXY2exNMzt94jO6M2moe', 'karm@gmail.com', 'nurse', '2025-04-25 21:26:22', '2025-04-25 21:26:22', NULL),
(61, 'ahmed.pharmacist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ahmed.mohamed@hospital.com', 'pharmacist', '2025-04-30 04:00:00', '2025-04-30 04:00:00', NULL),
(63, 'yasser omar', '$2y$10$E9pcOcwgM1PQghC0FU1Dr.qhAUxhPLGjiHPVJxN.9OlO6xDFkzFzK', 'yassero@gmail.com', 'patient', '2025-05-13 00:56:21', '2025-05-13 00:56:21', NULL),
(64, 'NASR', '$2y$10$U3w7ri90UMQdUHvqsI1f2eqGq1FO56ccnDNPeJBga8hNLPanhuRFO', 'NASR@GMAIL.COM', 'patient', '2025-05-14 15:16:14', '2025-05-14 15:16:14', NULL),
(65, 'nader ', '$2y$10$iWrt2TSVv1INRMbb2fKBIetasRc1NpLu8/I3C8V9BzweDJejh1c1G', 'naderhany@gmail.com', 'pharmacist', '2025-05-14 19:51:28', '2025-05-14 19:51:28', NULL),
(68, 'omar.elseemy', '$2y$10$NLaNc1imy4zfSi8xJE.dtugKZWZCrq4o82yVGnylq9Hf6GiyYouGy', 'omarsheemy89@gmail.com', 'doctor', '2025-05-15 10:31:06', '2025-05-15 10:31:06', NULL),
(69, 'ahmed.gamal', '$2y$10$qZjDkIbpXtbs7N6aQgRi6..gRM4ieSCbqCf5qbM86yhcnioSGZMeC', 'ahmedgmal@gmail.com', 'doctor', '2025-06-03 17:26:22', '2025-06-03 17:26:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `video_calls`
--

CREATE TABLE `video_calls` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `room_id` varchar(100) NOT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `meeting_password` varchar(50) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recording_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `video_calls`
--

INSERT INTO `video_calls` (`id`, `appointment_id`, `room_id`, `meeting_link`, `meeting_password`, `status`, `start_time`, `end_time`, `duration`, `notes`, `recording_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'ROOM-001', 'https://meet.example.com/room-001', 'pass123', 'scheduled', NULL, NULL, NULL, NULL, NULL, '2025-04-04 10:57:42', '2025-04-04 10:57:42'),
(2, 7, 'VC-67f35c6e24be1', NULL, 'SZ6KOV', 'scheduled', NULL, NULL, NULL, NULL, NULL, '2025-04-07 05:02:38', '2025-04-07 05:02:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ambulances`
--
ALTER TABLE `ambulances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`);

--
-- Indexes for table `ambulance_requests`
--
ALTER TABLE `ambulance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `assigned_ambulance_id` (`assigned_ambulance_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `video_call_id` (`video_call_id`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_appointment_status` (`status`),
  ADD KEY `idx_patient_doctor` (`patient_id`,`doctor_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dispensed_medicines`
--
ALTER TABLE `dispensed_medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `pharmacist_id` (`pharmacist_id`),
  ADD KEY `idx_dispense_date` (`dispense_date`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `doctor_holidays`
--
ALTER TABLE `doctor_holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_vacations`
--
ALTER TABLE `doctor_vacations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `medical_attachments`
--
ALTER TABLE `medical_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medical_record_id` (`medical_record_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medical_record_id` (`medical_record_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medicine_name` (`name`),
  ADD KEY `idx_medicine_category` (`category`),
  ADD KEY `idx_medicine_expiry` (`expiry_date`);

--
-- Indexes for table `medicine_categories`
--
ALTER TABLE `medicine_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `nurses`
--
ALTER TABLE `nurses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_doctor_id` (`assigned_doctor_id`);

--
-- Indexes for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nurse_shift` (`nurse_id`,`shift_date`,`shift_type`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `pharmacists`
--
ALTER TABLE `pharmacists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `idx_inventory_status` (`status`),
  ADD KEY `idx_inventory_expiry` (`expiry_date`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `medical_record_id` (`medical_record_id`),
  ADD KEY `idx_prescription_date` (`prescription_date`),
  ADD KEY `idx_prescription_status` (`status`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `dispensed_by` (`dispensed_by`);

--
-- Indexes for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `test_reports`
--
ALTER TABLE `test_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medical_record_id` (`medical_record_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `video_calls`
--
ALTER TABLE `video_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ambulances`
--
ALTER TABLE `ambulances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ambulance_requests`
--
ALTER TABLE `ambulance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `dispensed_medicines`
--
ALTER TABLE `dispensed_medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `doctor_holidays`
--
ALTER TABLE `doctor_holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_vacations`
--
ALTER TABLE `doctor_vacations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medical_attachments`
--
ALTER TABLE `medical_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `medical_reports`
--
ALTER TABLE `medical_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `medicine_categories`
--
ALTER TABLE `medicine_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pharmacists`
--
ALTER TABLE `pharmacists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `test_reports`
--
ALTER TABLE `test_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `video_calls`
--
ALTER TABLE `video_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ambulance_requests`
--
ALTER TABLE `ambulance_requests`
  ADD CONSTRAINT `ambulance_requests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ambulance_requests_ibfk_2` FOREIGN KEY (`assigned_ambulance_id`) REFERENCES `ambulances` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`video_call_id`) REFERENCES `video_calls` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`video_call_id`) REFERENCES `video_calls` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dispensed_medicines`
--
ALTER TABLE `dispensed_medicines`
  ADD CONSTRAINT `dispensed_medicines_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dispensed_medicines_ibfk_2` FOREIGN KEY (`pharmacist_id`) REFERENCES `pharmacists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `doctors_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `doctor_holidays`
--
ALTER TABLE `doctor_holidays`
  ADD CONSTRAINT `doctor_holidays_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD CONSTRAINT `doctor_schedule_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_vacations`
--
ALTER TABLE `doctor_vacations`
  ADD CONSTRAINT `doctor_vacations_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `medical_attachments`
--
ALTER TABLE `medical_attachments`
  ADD CONSTRAINT `medical_attachments_ibfk_1` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD CONSTRAINT `medical_reports_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `medical_reports_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `medical_reports_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nurses`
--
ALTER TABLE `nurses`
  ADD CONSTRAINT `nurses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `nurses_ibfk_2` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `nurse_shifts`
--
ALTER TABLE `nurse_shifts`
  ADD CONSTRAINT `nurse_shifts_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurse_shifts_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pharmacists`
--
ALTER TABLE `pharmacists`
  ADD CONSTRAINT `pharmacists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  ADD CONSTRAINT `pharmacy_inventory_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_items_ibfk_3` FOREIGN KEY (`dispensed_by`) REFERENCES `pharmacists` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `test_reports`
--
ALTER TABLE `test_reports`
  ADD CONSTRAINT `test_reports_ibfk_1` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_calls`
--
ALTER TABLE `video_calls`
  ADD CONSTRAINT `video_calls_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
