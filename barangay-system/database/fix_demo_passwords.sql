USE barangay_reservation;

CREATE TABLE IF NOT EXISTS login_attempts (
	id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_login_attempts_ip_time (ip_hash, attempted_at),
	INDEX idx_login_attempts_time (attempted_at)
) ENGINE=InnoDB;

UPDATE users
SET password = '$2y$10$WC84TIErR4UnwMv2CVS4ceUz8OTpa4WVLtNLJulCbofbkAyReLpzO'
WHERE username IN ('admin', 'staff1', 'staff2', 'jdelacruz', 'msantos', 'preyes', 'agarcia', 'rlim');