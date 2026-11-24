-- =====================================================================
-- Migration: Add Walk-in Transaction Management
-- Database: barangay_reservation
-- =====================================================================

CREATE TABLE IF NOT EXISTS walkin_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(30) NOT NULL UNIQUE,
    resident_id INT DEFAULT NULL,
    resident_name VARCHAR(120) NOT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    certificate_type VARCHAR(100) NOT NULL DEFAULT 'Barangay Certificate',
    purpose VARCHAR(150) NOT NULL,
    request_date DATE NOT NULL,
    assigned_staff_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('Free', 'Paid', 'Unpaid', 'Waived') NOT NULL DEFAULT 'Free',
    receipt_number VARCHAR(50) DEFAULT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Released', 'Cancelled') NOT NULL DEFAULT 'Pending',
    remarks TEXT DEFAULT NULL,
    released_at DATETIME DEFAULT NULL,
    released_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_staff_id) REFERENCES staff(id) ON DELETE RESTRICT,
    FOREIGN KEY (released_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_walkin_ref (reference_number),
    INDEX idx_walkin_resident (resident_id),
    INDEX idx_walkin_date (request_date),
    INDEX idx_walkin_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
