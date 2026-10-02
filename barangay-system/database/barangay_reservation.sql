-- =====================================================================
-- Barangay Certificate Online Reservation and Tracking System
-- Database: barangay_reservation
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < barangay_reservation.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS barangay_reservation
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE barangay_reservation;

-- ---------------------------------------------------------------------
-- Table: users
-- Holds login credentials for every account (resident, staff, admin)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,     -- always store password_hash() output
    role ENUM('resident','staff','admin') NOT NULL DEFAULT 'resident',
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: residents
-- Extra profile information for accounts with role = 'resident'
-- ---------------------------------------------------------------------
CREATE TABLE residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    address VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_resident_name (last_name, first_name),
    INDEX idx_contact (contact_number)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: staff
-- Extra profile information for accounts with role = 'staff' or 'admin'
-- ---------------------------------------------------------------------
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_staff_name (last_name, first_name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: appointment_slots
-- Defines bookable time slots for a given date
-- ---------------------------------------------------------------------
CREATE TABLE appointment_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_reservations INT NOT NULL DEFAULT 5,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appt_date (appointment_date),
    UNIQUE KEY uniq_slot (appointment_date, start_time, end_time)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: reservations
-- ---------------------------------------------------------------------
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    resident_id INT NOT NULL,
    certificate_type VARCHAR(100) NOT NULL DEFAULT 'Barangay Certificate',
    purpose VARCHAR(150) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Pending','Confirmed','Completed','Cancelled','Rejected','No-show')
        NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    INDEX idx_ref_number (reference_number),
    INDEX idx_appt_date_res (appointment_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: certificates
-- A row is only created here once staff actually issues a certificate
-- ---------------------------------------------------------------------
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    reservation_id INT DEFAULT NULL,
    certificate_type VARCHAR(100) NOT NULL DEFAULT 'Barangay Certificate',
    purpose VARCHAR(150) NOT NULL,
    issued_by INT NOT NULL,           -- staff.id
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES staff(id) ON DELETE RESTRICT,
    INDEX idx_cert_ref (reference_number),
    INDEX idx_cert_resident (resident_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: audit_logs
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SAMPLE DATA
-- All sample passwords are: Password123!
-- The hash below was generated with PHP password_hash('Password123!', PASSWORD_DEFAULT)
-- =====================================================================

-- 1 Administrator
INSERT INTO users (username, email, password, role, status) VALUES
('admin', 'admin@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
INSERT INTO staff (user_id, first_name, last_name, position) VALUES
(1, 'Ana', 'Villareal', 'Barangay Administrator');

-- 2 Barangay Staff accounts
INSERT INTO users (username, email, password, role, status) VALUES
('staff1', 'staff1@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active'),
('staff2', 'staff2@barangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active');
INSERT INTO staff (user_id, first_name, last_name, position) VALUES
(2, 'Mark', 'Torres', 'Barangay Clerk'),
(3, 'Liza', 'Fernandez', 'Barangay Secretary');

-- 5 Resident accounts
INSERT INTO users (username, email, password, role, status) VALUES
('jdelacruz', 'juan.delacruz@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'active'),
('msantos', 'maria.santos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'active'),
('preyes', 'pedro.reyes@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'active'),
('agarcia', 'ana.garcia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'active'),
('rlim', 'rico.lim@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'active');

INSERT INTO residents (user_id, first_name, middle_name, last_name, date_of_birth, address, contact_number) VALUES
(4, 'Juan', 'Reyes', 'Dela Cruz', '2000-01-01', '123 Mabini St, Barangay Example', '09171234567'),
(5, 'Maria', 'Lopez', 'Santos', '1995-05-12', '45 Rizal Ave, Barangay Example', '09182345678'),
(6, 'Pedro', 'Cruz', 'Reyes', '1988-11-23', '78 Bonifacio St, Barangay Example', '09193456789'),
(7, 'Ana', 'Diaz', 'Garcia', '1999-03-30', '12 Luna St, Barangay Example', '09204567890'),
(8, 'Rico', 'Tan', 'Lim', '1992-07-19', '56 Aguinaldo St, Barangay Example', '09215678901');

-- Appointment slots (a few upcoming dates)
INSERT INTO appointment_slots (appointment_date, start_time, end_time, max_reservations, status) VALUES
('2026-08-31', '09:00:00', '09:30:00', 5, 'open'),
('2026-08-31', '09:30:00', '10:00:00', 5, 'open'),
('2026-08-31', '10:00:00', '10:30:00', 5, 'open'),
('2026-09-01', '09:00:00', '09:30:00', 5, 'open'),
('2026-09-01', '09:30:00', '10:00:00', 5, 'open'),
('2026-09-02', '09:00:00', '09:30:00', 3, 'open');

-- Sample reservations
INSERT INTO reservations (reference_number, resident_id, certificate_type, purpose, appointment_date, appointment_time, status) VALUES
('BRG-2026-00001', 1, 'Barangay Certificate', 'Employment', '2026-08-31', '09:00:00', 'Confirmed'),
('BRG-2026-00002', 2, 'Barangay Certificate', 'School', '2026-08-31', '09:30:00', 'Pending'),
('BRG-2026-00003', 3, 'Barangay Certificate', 'Business', '2026-09-01', '09:00:00', 'Completed'),
('BRG-2026-00004', 1, 'Barangay Certificate', 'Financial Assistance', '2026-08-01', '10:00:00', 'Cancelled');

-- Sample certificate records (issued only for the Completed reservation)
INSERT INTO certificates (resident_id, reservation_id, certificate_type, purpose, issued_by, issued_at, reference_number) VALUES
(3, 3, 'Barangay Certificate', 'Business', 2, '2026-09-01 09:20:00', 'CERT-2026-00001'),
(1, NULL, 'Barangay Certificate', 'Employment', 2, '2026-01-15 10:00:00', 'CERT-2026-00000');

-- Sample audit log entries
INSERT INTO audit_logs (user_id, action, description) VALUES
(2, 'CONFIRM_RESERVATION', 'Confirmed reservation BRG-2026-00001'),
(2, 'ISSUE_CERTIFICATE', 'Issued certificate CERT-2026-00001 to resident Pedro Reyes');
