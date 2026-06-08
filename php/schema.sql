-- ============================================================
--  SuRide — MySQL Database Schema
--  Run this once in your MySQL client or phpMyAdmin:
--    mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS suride_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE suride_db;

-- ──────────────────────────────────────────────────────────────
--  USERS TABLE
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  email         VARCHAR(255) NOT NULL UNIQUE,
  phone         VARCHAR(20)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME              ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────
--  CARS (FLEET) TABLE
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cars (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand         VARCHAR(80)  NOT NULL,
  model         VARCHAR(120) NOT NULL,
  year          YEAR         NOT NULL,
  category      ENUM('MPV','SUV','Sedan','Luxury','Sport') NOT NULL,
  transmission  ENUM('Automatic','Manual')                 NOT NULL DEFAULT 'Automatic',
  seats         TINYINT UNSIGNED NOT NULL DEFAULT 5,
  status        ENUM('available','rented','maintenance')   NOT NULL DEFAULT 'available',
  price_per_day INT UNSIGNED NOT NULL COMMENT 'In IDR (Rupiah)',
  image_url     VARCHAR(500) DEFAULT NULL,
  description   TEXT         DEFAULT NULL,
  engine        VARCHAR(120) DEFAULT NULL,
  fuel          VARCHAR(60)  DEFAULT NULL,
  colour        VARCHAR(60)  DEFAULT NULL,
  drive         VARCHAR(20)  DEFAULT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME              ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_status   (status),
  INDEX idx_price    (price_per_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────
--  SEED: Admin account
--  Password: Admin@SuRide1  (change immediately in production!)
--  Hash generated with: password_hash('Admin@SuRide1', PASSWORD_BCRYPT, ['cost'=>12])
-- ──────────────────────────────────────────────────────────────
INSERT IGNORE INTO users (first_name, last_name, email, phone, password_hash, role)
VALUES (
  'Admin', 'SuRide',
  'admin@suride.id',
  '081234567890',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Admin@SuRide1
  'admin'
);

-- ──────────────────────────────────────────────────────────────
--  SEED: Demo customer
--  Password: Customer1!
-- ──────────────────────────────────────────────────────────────
INSERT IGNORE INTO users (first_name, last_name, email, phone, password_hash, role)
VALUES (
  'Budi', 'Santoso',
  'budi@example.com',
  '082345678901',
  '$2y$12$eImiTXuWVxfM37uY4JANjQ==HASHED_PLACEHOLDER',  -- replace with real hash
  'customer'
);

-- ──────────────────────────────────────────────────────────────
--  SEED: Fleet data (mirrors the JS FLEET_DATA array)
-- ──────────────────────────────────────────────────────────────
INSERT INTO cars
  (brand, model, year, category, transmission, seats, status, price_per_day, image_url, description, engine, fuel, colour, drive)
VALUES
  ('Toyota','Alphard 3.5 Executive',2024,'MPV','Automatic',7,'available',1800000,
   'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&q=80',
   'The pinnacle of MPV luxury. Exceptional comfort with captain seats, premium acoustics, and a commanding presence on Surabaya\'s roads.',
   '3.5L V6 280hp','Petrol','Pearl White','2WD'),

  ('BMW','X5 xDrive40i',2023,'SUV','Automatic',5,'available',2500000,
   'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80',
   'German engineering at its finest. Athletic dynamism with premium cabin refinement — engineered for executives who refuse to compromise.',
   '3.0L Turbo 340hp','Petrol','Mineral White','AWD'),

  ('Mercedes-Benz','E-Class 300',2024,'Sedan','Automatic',5,'available',2200000,
   'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80',
   'Timeless sophistication. The E-Class 300 is the definitive business sedan.',
   '2.0L Turbo 255hp','Petrol','Obsidian Black','RWD'),

  ('Range Rover','Sport HSE',2023,'Luxury','Automatic',5,'rented',3500000,
   'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=800&q=80',
   'Where luxury meets capability. The Range Rover Sport HSE commands attention in boardrooms and boulevards alike.',
   '3.0L Supercharged 354hp','Petrol','Santorini Black','AWD'),

  ('Honda','CR-V Turbo Prestige',2024,'SUV','Automatic',5,'available',900000,
   'https://images.unsplash.com/photo-1606016159991-dfe4f2746ad5?w=800&q=80',
   'Smart, spacious, and supremely comfortable. Advanced safety features included.',
   '1.5L Turbo 190hp','Petrol','Lunar Silver','AWD'),

  ('Toyota','Camry V 2.5 Hybrid',2023,'Sedan','Automatic',5,'available',1100000,
   'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80',
   'Refined, efficient, and effortlessly stylish. Pairs a silky V6 feel with superior fuel economy.',
   '2.5L Hybrid 215hp','Hybrid','Midnight Black','FWD'),

  ('Lexus','ES 300h',2024,'Luxury','Automatic',5,'available',2800000,
   'https://images.unsplash.com/photo-1563720223185-11003d516935?w=800&q=80',
   'Japanese luxury perfected. Mark Levinson audio, heated/cooled seats, and supernatural silence.',
   '2.5L Hybrid 215hp','Hybrid','Caviar','FWD'),

  ('Toyota','Fortuner GR Sport',2024,'SUV','Automatic',7,'available',1200000,
   'https://images.unsplash.com/photo-1598697551562-1d6de7d618c4?w=800&q=80',
   'The GR Sport edition turns an already capable SUV into a head-turning statement.',
   '2.8L Diesel 204hp','Diesel','Attitude Black','4WD'),

  ('Nissan','Serena Highway Star',2024,'MPV','Automatic',8,'available',850000,
   'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?w=800&q=80',
   'The family favourite elevated. ProPilot assist, premium entertainment, 8-seat panoramic interior.',
   '2.0L Hybrid 145hp','Hybrid','Brilliant Silver','FWD'),

  ('Audi','A6 40 TFSI',2023,'Luxury','Automatic',5,'maintenance',2600000,
   'https://images.unsplash.com/photo-1603386329225-868f9b1ee6c9?w=800&q=80',
   'Progressive Teutonic luxury. Virtual Cockpit, Bang & Olufsen audio, bespoke cabin in motion.',
   '2.0L Turbo 245hp','Petrol','Mythos Black','FWD'),

  ('Porsche','Cayenne S',2023,'Sport','Automatic',5,'available',5500000,
   'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=800&q=80',
   'The sports car of SUVs. Twin-turbocharged V6 thunder with Alcantara interior.',
   '2.9L Twin-Turbo V6 440hp','Petrol','Night Blue','AWD'),

  ('Mitsubishi','Outlander PHEV',2023,'SUV','Automatic',7,'available',1050000,
   'https://images.unsplash.com/photo-1514316454349-750a7fd3da3a?w=800&q=80',
   'Pioneering plug-in hybrid SUV technology. Drive pure electric through the city.',
   '2.4L PHEV 302hp','Plug-in Hybrid','White Diamond','AWD');
