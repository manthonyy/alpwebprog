-- ============================================
-- SuRide - Drivers Table
-- ============================================

CREATE TABLE IF NOT EXISTS `drivers` (
  `driver_id`     INT AUTO_INCREMENT PRIMARY KEY,
  `driver_name`   VARCHAR(100) NOT NULL,
  `phone_number`  VARCHAR(20)  NOT NULL,
  `driver_status` ENUM('available','assigned','off') DEFAULT 'available',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Sample Data
-- ============================================

INSERT INTO `drivers` (`driver_name`, `phone_number`, `driver_status`) VALUES
('Budi Santoso',   '+62 812-3456-7890', 'available'),
('Agus Wijaya',    '+62 813-9876-5432', 'assigned'),
('Hendra Kusuma',  '+62 857-1234-5678', 'available'),
('Rizky Pratama',  '+62 878-5555-1234', 'off'),
('Doni Setiawan',  '+62 821-6789-0123', 'available');
