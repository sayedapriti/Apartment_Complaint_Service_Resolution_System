-- ============================================================
-- Apartment Complaint & Service Resolution System - Schema (3NF)
-- ============================================================
CREATE DATABASE IF NOT EXISTS apartment_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apartment_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS resolution_details;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS complaint_issues;
DROP TABLE IF EXISTS service_staff_profiles;
DROP TABLE IF EXISTS resident_profiles;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;


-- 1. Users Table (No redundant attributes, in 3NF)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','staff','resident') NOT NULL DEFAULT 'resident',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Resident Profiles Table (No redundant attributes, in 3NF)
CREATE TABLE resident_profiles (
    resident_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    apartment_number VARCHAR(20) NOT NULL,
    contact_address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Service Staff Profiles Table (Removed contact_number to eliminate redundancy with users.phone, in 3NF)
CREATE TABLE service_staff_profiles (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    available_status ENUM('available','busy','off_duty') DEFAULT 'available',
    staff_type VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Complaint Categories Table
CREATE TABLE complaint_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

-- Insert default categories
INSERT INTO complaint_categories (category_name) VALUES
('Plumbing'),
('Electrical'),
('HVAC'),
('Appliance'),
('Structural'),
('Pest Control'),
('Landscaping'),
('Security'),
('Cleaning'),
('Noise/Disturbance'),
('Parking'),
('Other');



-- 6. Complaints Table
CREATE TABLE complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    category_name VARCHAR(100) DEFAULT NULL,
    complaint_title VARCHAR(200) NOT NULL,
    complaint_description TEXT NOT NULL,
    complaint_status ENUM('pending','assigned','in_progress','resolved','closed') DEFAULT 'pending',
    assigned_staff_id INT DEFAULT NULL,
    assigned_by INT DEFAULT NULL,
    assigned_at TIMESTAMP NULL DEFAULT NULL,
    progress_note TEXT,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES resident_profiles(resident_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_staff_id) REFERENCES service_staff_profiles(staff_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- 7. Resolution Details Table
CREATE TABLE resolution_details (
    resolution_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    resolution_description TEXT NOT NULL,
    resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES service_staff_profiles(staff_id)
);

-- ============================================================
-- SEED DATA
-- Default Passwords are 'admin123', 'staff123', 'tenant123' (hashed using bcrypt)
-- Hashed password for all seeded users is: $2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG
-- ============================================================

-- Seed Users
INSERT INTO users (full_name, email, password, phone, role) VALUES
('System Administrator', 'admin@residehub.com', '$2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG', '+880-1700-000000', 'admin'),
('Rahim Uddin', 'rahim@staff.com', '$2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG', '+880-1711-111111', 'staff'),
('Karim Hossain', 'karim@staff.com', '$2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG', '+880-1722-222222', 'staff'),
('Fatima Begum', 'fatima@staff.com', '$2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG', '+880-1733-333333', 'staff'),
('Nadia Islam', 'nadia@resident.com', '$2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG', '+880-1744-444444', 'resident'),
('Ahmed Hasan', 'ahmed@resident.com', '$2y$10$TKh8H1.PfuB8GQWy7nKGMuYJf5/gAIm8h2VbNE/D3Y8W2Gl6QCWKG', '+880-1755-555555', 'resident');

-- Seed Profiles
INSERT INTO service_staff_profiles (user_id, available_status, staff_type) VALUES
(2, 'available', 'Plumber'),
(3, 'available', 'Electrician'),
(4, 'available', 'General Maintenance');

INSERT INTO resident_profiles (user_id, apartment_number, contact_address) VALUES
(5, 'A-301', 'Block A, Floor 3, Apt 301'),
(6, 'B-205', 'Block B, Floor 2, Apt 205');



-- Seed Complaints
INSERT INTO complaints (resident_id, category_name, complaint_title, complaint_description, complaint_status, assigned_staff_id, assigned_by, assigned_at, progress_note, updated_by, submitted_at) VALUES
-- Nadia Islam (resident_id: 1)
(1, 'Plumbing', 'Water leakage under kitchen sink', 'There is a steady water leakage from the pipe under the kitchen sink. It is flooding the cabinet.', 'pending', NULL, NULL, NULL, 'Complaint filed by tenant.', 5, NOW() - INTERVAL 5 DAY),
(1, 'Electrical', 'Frequent power outages in Block A', 'We are experiencing 3-4 power outages daily only in Block A. Other blocks seem fine.', 'assigned', 2, 1, NOW() - INTERVAL 4 DAY, 'Staff member assigned.', 1, NOW() - INTERVAL 4 DAY),
(1, 'Structural', 'Wall crack in guest bedroom', 'A hairline crack has developed on the north wall of the guest bedroom. It is expanding.', 'resolved', NULL, NULL, NULL, 'Crack sealed and repainted.', 3, NOW() - INTERVAL 3 DAY),
-- Ahmed Hasan (resident_id: 2)
(2, 'Plumbing', 'Blocked toilet drain', 'The main toilet drain is completely blocked and overflowing. Need urgent help.', 'in_progress', 1, 1, NOW() - INTERVAL 2 DAY, 'Currently clearing the drain block.', 2, NOW() - INTERVAL 2 DAY),
(2, 'Pest Control', 'Cockroach infestation in kitchen', 'Seeing lots of cockroaches in kitchen cabinets. Need pest control treatment.', 'closed', NULL, NULL, NULL, 'Tenant confirmed resolution.', 1, NOW() - INTERVAL 10 DAY);

-- Seed Resolution Details
INSERT INTO resolution_details (complaint_id, staff_id, resolution_description, resolved_at) VALUES
(3, 3, 'Sealed the wall crack with putty and painted over it.', NOW() - INTERVAL 2 DAY),
(5, 3, 'Applied gel bait and performed insecticide spray in kitchen.', NOW() - INTERVAL 9 DAY);
