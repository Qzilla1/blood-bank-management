-- Blood Bank Management System Database Schema
-- Created on: 22nd May 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create Database if not exists (User can run this in phpMyAdmin)
-- CREATE DATABASE IF NOT EXISTS `blood_bank`;
-- USE `blood_bank`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Seeding data for table `admins`
--
-- Password is 'admin123' (hashed using BCrypt)
--
INSERT INTO `admins` (`id`, `username`, `password`, `fullname`) VALUES
(1, 'admin', '$2y$10$wL4P9qW.3aXskh/V/XW.c.f6R6N/15vR/8sS322c3q251d5s2f3f', 'Super Administrator')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE IF NOT EXISTS `donors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `age` INT(3) NOT NULL,
  `gender` VARCHAR(10) NOT NULL,
  `blood_group` VARCHAR(5) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `medical_history` TEXT DEFAULT NULL,
  `last_donation_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Seeding data for table `donors`
--

INSERT INTO `donors` (`id`, `name`, `age`, `gender`, `blood_group`, `email`, `phone`, `address`, `medical_history`, `last_donation_date`) VALUES
(1, 'Ahmad Ali', 28, 'Male', 'O+', 'ahmad.ali@email.com', '+92 300 1234567', 'House 42, Sector F-8, Islamabad', 'No major illness. Regularly donates every 4 months.', '2026-02-15'),
(2, 'Sara Khan', 24, 'Female', 'A-', 'sara.k@email.com', '+92 312 9876543', 'Apartment B-12, Gulshan-e-Iqbal, Karachi', 'Slightly low hemoglobin in 2024, now recovered. Blood pressure stable.', '2026-03-10'),
(3, 'Zainab Fatima', 31, 'Female', 'AB+', 'zainab.f@email.com', '+92 333 4567890', '45-C, DHA Phase 5, Lahore', 'Perfect health. No chronic conditions.', NULL),
(4, 'Bilal Ahmed', 35, 'Male', 'B+', 'bilal.a@email.com', '+92 345 5556677', 'Model Town, Block D, Lahore', 'Mild allergy to dust. Otherwise healthy.', '2026-01-20'),
(5, 'Usman Tariq', 29, 'Male', 'O-', 'usman.t@email.com', '+92 321 8889900', 'Street 3, G-9, Islamabad', 'Universal donor. No health warnings.', '2026-04-05');

-- --------------------------------------------------------

--
-- Table structure for table `blood_stock`
--

CREATE TABLE IF NOT EXISTS `blood_stock` (
  `blood_group` VARCHAR(5) NOT NULL,
  `units` INT(11) DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`blood_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Seeding data for table `blood_stock`
--

INSERT INTO `blood_stock` (`blood_group`, `units`) VALUES
('A+', 15),
('A-', 6),
('B+', 20),
('B-', 4),
('AB+', 8),
('AB-', 3),
('O+', 25),
('O-', 12)
ON DUPLICATE KEY UPDATE `units`=VALUES(`units`);

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE IF NOT EXISTS `blood_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_name` VARCHAR(100) NOT NULL,
  `blood_group` VARCHAR(5) NOT NULL,
  `units_requested` INT(11) NOT NULL,
  `hospital_name` VARCHAR(150) NOT NULL,
  `required_date` DATE NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Fulfilled', 'Cancelled') DEFAULT 'Pending',
  `contact_number` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Seeding data for table `blood_requests`
--

INSERT INTO `blood_requests` (`id`, `patient_name`, `blood_group`, `units_requested`, `hospital_name`, `required_date`, `status`, `contact_number`) VALUES
(1, 'Muhammad Rizwan', 'O+', 3, 'Shifa International Hospital, Islamabad', '2026-05-25', 'Pending', '+92 300 7654321'),
(2, 'Ayesha Bibi', 'B+', 2, 'Mayo Hospital, Lahore', '2026-05-20', 'Fulfilled', '+92 315 1112223'),
(3, 'Hamza Malik', 'A-', 4, 'Jinnah Post Graduate Medical Center, Karachi', '2026-05-28', 'Approved', '+92 334 9998887'),
(4, 'Fatima Shah', 'AB-', 1, 'PIMS Hospital, Islamabad', '2026-05-18', 'Cancelled', '+92 322 3334445');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
