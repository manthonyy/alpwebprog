-- ============================================================
-- SuRide - Full Database Setup
-- Run this in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS `suride_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `suride_db`;

-- ============================================================
-- USERS TABLE (for login/register)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`    INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `phone`      VARCHAR(20),
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','customer') DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`category_name`) VALUES
('Sedan'), ('SUV'), ('MPV'), ('Sport')
ON DUPLICATE KEY UPDATE `category_name` = VALUES(`category_name`);

-- ============================================================
-- CARS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `cars` (
  `car_id`        INT AUTO_INCREMENT PRIMARY KEY,
  `brand`         VARCHAR(100) NOT NULL,
  `model`         VARCHAR(100) NOT NULL,
  `year`          YEAR NOT NULL,
  `license_plate` VARCHAR(20) NOT NULL UNIQUE,
  `category_id`   INT NOT NULL,
  `status`        ENUM('available','rented','maintenance') DEFAULT 'available',
  `price_per_day` DECIMAL(12,2) NOT NULL,
  `image_url`     TEXT,
  `description`   TEXT,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cars` (`brand`, `model`, `year`, `license_plate`, `category_id`, `status`, `price_per_day`, `image_url`, `description`) VALUES
('Toyota',       'Camry',         2024, 'L 1234 AB', 1, 'available',   650000,  'https://images.unsplash.com/photo-1571987502951-3db0ab1cdcd8?w=600&q=80', 'Executive sedan with premium interior'),
('BMW',          'X5',            2023, 'L 5678 CD', 2, 'available',   1500000, 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80', 'Luxury SUV perfect for family and business'),
('Toyota',       'Alphard',       2024, 'L 9012 EF', 3, 'rented',      1800000, 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=600&q=80', 'Premium MPV with ultra-spacious cabin'),
('Porsche',      'Cayenne',       2023, 'L 3456 GH', 4, 'available',   3500000, 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600&q=80', 'High-performance luxury sport SUV'),
('Mercedes-Benz','E-Class',       2024, 'L 7890 IJ', 1, 'maintenance', 1200000, 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80', 'Refined German engineering for executives'),
('Range Rover',  'Sport',         2023, 'L 2468 KL', 2, 'available',   2800000, 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=600&q=80', 'Iconic British luxury off-roader')
ON DUPLICATE KEY UPDATE `model` = VALUES(`model`);

-- ============================================================
-- DRIVERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `drivers` (
  `driver_id`     INT AUTO_INCREMENT PRIMARY KEY,
  `driver_name`   VARCHAR(100) NOT NULL,
  `phone_number`  VARCHAR(20)  NOT NULL,
  `driver_status` ENUM('available','assigned','off') DEFAULT 'available',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `drivers` (`driver_name`, `phone_number`, `driver_status`) VALUES
('Budi Santoso',  '+62 812-3456-7890', 'available'),
('Agus Wijaya',   '+62 813-9876-5432', 'assigned'),
('Hendra Kusuma', '+62 857-1234-5678', 'available'),
('Rizky Pratama', '+62 878-5555-1234', 'off'),
('Doni Setiawan', '+62 821-6789-0123', 'available')
ON DUPLICATE KEY UPDATE `driver_name` = VALUES(`driver_name`);
