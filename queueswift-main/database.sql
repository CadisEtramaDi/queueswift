-- QueueSwift Database Setup
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS queueswift CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE queueswift;

CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category ENUM('office','salon','pet_grooming','clinic') NOT NULL,
    description TEXT,
    address VARCHAR(255),
    phone VARCHAR(30),
    open_time TIME DEFAULT '08:00:00',
    close_time TIME DEFAULT '17:00:00',
    avg_service_minutes INT DEFAULT 15,
    max_queue INT DEFAULT 50,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(30),
    customer_email VARCHAR(100),
    service_type VARCHAR(100),
    queue_number INT NOT NULL,
    token VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('waiting','serving','done','cancelled','no_show') DEFAULT 'waiting',
    notes TEXT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    served_at TIMESTAMP NULL,
    done_at TIMESTAMP NULL,
    estimated_wait INT DEFAULT 0,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS business_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    duration_minutes INT DEFAULT 15,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Sample Data
INSERT INTO businesses (name, category, description, address, phone, avg_service_minutes, max_queue) VALUES
('Reyes Law Office', 'office', 'Full-service legal consultations and documentation', '12 Ayala Ave, Makati City', '+63 912 345 6789', 30, 20),
('Glow & Flow Salon', 'salon', 'Premium hair, nail, and beauty services', '88 Quezon Blvd, Quezon City', '+63 923 456 7890', 45, 30),
('PawPerfect Grooming', 'pet_grooming', 'Professional grooming for dogs and cats', '5 Marikina St, Pasig City', '+63 934 567 8901', 60, 15),
('MedCare Family Clinic', 'clinic', 'General practice and specialist consultations', '200 Taft Ave, Manila', '+63 945 678 9012', 20, 40);

INSERT INTO business_services (business_id, service_name, duration_minutes) VALUES
(1, 'Legal Consultation', 30), (1, 'Document Notarization', 15), (1, 'Contract Review', 45),
(2, 'Haircut & Style', 45), (2, 'Hair Color', 90), (2, 'Manicure & Pedicure', 60), (2, 'Facial', 60),
(3, 'Bath & Trim (Small Dog)', 60), (3, 'Full Groom (Large Dog)', 90), (3, 'Cat Grooming', 60),
(4, 'General Check-up', 20), (4, 'Specialist Consultation', 30), (4, 'Vaccination', 15), (4, 'Laboratory', 45);
