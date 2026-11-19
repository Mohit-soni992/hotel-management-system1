-- StayEase Mini - Hostel / PG Management (import in phpMyAdmin)
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS stayease_mini CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stayease_mini;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS complaints, payments, tenants, rooms, admins;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. admins : who can log in
CREATE TABLE admins (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  name     VARCHAR(100) NOT NULL,
  email    VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL            -- stored with password_hash()
) ENGINE=InnoDB;

-- 2. rooms
CREATE TABLE rooms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(20) NOT NULL UNIQUE,
  room_type   ENUM('Single','Double','Triple','Dormitory') NOT NULL,
  capacity    TINYINT NOT NULL,               -- number of beds
  rent        DECIMAL(10,2) NOT NULL          -- monthly rent per bed
) ENGINE=InnoDB;

-- 3. tenants (room_id = the room they live in)
CREATE TABLE tenants (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  phone         VARCHAR(10)  NOT NULL,
  guardian_name VARCHAR(100) NOT NULL,
  address       VARCHAR(255) NOT NULL,
  joining_date  DATE NOT NULL,
  room_id       INT NOT NULL,
  status        ENUM('Active','Left') NOT NULL DEFAULT 'Active',
  leaving_date  DATE NULL,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. payments (one row per payment; a month can be paid in parts)
CREATE TABLE payments (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id    INT NOT NULL,
  month        CHAR(7) NOT NULL,              -- like 2026-10
  amount       DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  method       ENUM('Cash','UPI') NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. complaints
CREATE TABLE complaints (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id   INT NOT NULL,
  title       VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  status      ENUM('Pending','Resolved') NOT NULL DEFAULT 'Pending',
  created_at  DATE NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------- demo data ----------
-- Demo login: admin@stayease.com / admin123  (change it for real use)
INSERT INTO admins (name, email, password) VALUES ('Admin', 'admin@stayease.com', '$2y$10$5UpZjPsDZj4IfY.XWrDNUOKOBWZfyq6L5Q1S5BrRFZ6mL3UDYn7qe');

INSERT INTO rooms (room_number, room_type, capacity, rent) VALUES
('101','Single',1,6000), ('102','Double',2,4500), ('103','Triple',3,3500), ('201','Dormitory',6,2500);

INSERT INTO tenants (name, phone, guardian_name, address, joining_date, room_id, status, leaving_date) VALUES
('Rahul Sharma',  '9876543210','Mahesh Sharma','Jodhpur, Rajasthan', DATE_SUB(CURDATE(), INTERVAL 90 DAY), 1,'Active',NULL),
('Amit Verma',    '9876501234','Rakesh Verma', 'Ajmer, Rajasthan',   DATE_SUB(CURDATE(), INTERVAL 80 DAY), 2,'Active',NULL),
('Karan Singh',   '9812345670','Bhanwar Singh','Osian, Rajasthan',   DATE_SUB(CURDATE(), INTERVAL 70 DAY), 2,'Active',NULL),
('Vikram Joshi',  '9823456781','Dinesh Joshi', 'Pali, Rajasthan',    DATE_SUB(CURDATE(), INTERVAL 60 DAY), 3,'Active',NULL),
('Mohan Lal',     '9867890125','Gopal Lal',    'Barmer, Rajasthan',  DATE_SUB(CURDATE(), INTERVAL 100 DAY),4,'Left', DATE_SUB(CURDATE(), INTERVAL 20 DAY));

INSERT INTO payments (tenant_id, month, amount, payment_date, method) VALUES
(1, DATE_FORMAT(CURDATE(),'%Y-%m'), 6000, CURDATE(), 'UPI'),
(2, DATE_FORMAT(CURDATE(),'%Y-%m'), 2000, CURDATE(), 'Cash'),
(1, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m'), 6000, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 'UPI');

INSERT INTO complaints (tenant_id, title, description, status, created_at) VALUES
(2, 'Tap leaking', 'Bathroom tap is leaking for two days.', 'Pending', CURDATE()),
(1, 'Wi-Fi slow', 'Internet is very slow at night.', 'Resolved', DATE_SUB(CURDATE(), INTERVAL 5 DAY));
