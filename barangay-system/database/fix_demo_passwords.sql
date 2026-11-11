USE barangay_reservation;

CREATE TABLE IF NOT EXISTS login_attempts (
	id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_login_attempts_ip_time (ip_hash, attempted_at),
	INDEX idx_login_attempts_time (attempted_at)
) ENGINE=InnoDB;

UPDATE users
SET password = CASE username
	WHEN 'admin' THEN '$2y$10$OladPIw55hoGrv1E.wVYOO5sqfNDUyFJaCpHpw5RhUBq2FtIPcdgu'
	WHEN 'staff1' THEN '$2y$10$RwSn4hiBowQUe//d.S2lSexldMnBUbn2Dy8g6HJUimv5xh58ewnA2'
	WHEN 'staff2' THEN '$2y$10$sdmgagxeR5ReGjlPUzmxT.ygLa3H9vKu0kQQfYkmMVudcBAGDb6b.'
	WHEN 'jdelacruz' THEN '$2y$10$FP/aHhNd.rFgQomXZnto1OKk.rc6A2YM2N4oxFcnSZ3cFvw4M6f6a'
	WHEN 'msantos' THEN '$2y$10$qv84jlKF3PHWXfg4zEJBFutmdOLovWJD82N6SXskMFFmo9AQi/HMu'
	WHEN 'preyes' THEN '$2y$10$IUubcRqNTXPQ6umm147Tcug302Zdl5sqTIpEHOvjCs9ZvBGq4Xhdi'
	WHEN 'agarcia' THEN '$2y$10$hgldXXCWR8mM6qZXWN904e7QgOLIa4hgYvhVxz4r82IyKeEjpBWCy'
	WHEN 'rlim' THEN '$2y$10$O7RnF9/gqF1YGG9mVsZ99.QT7ldqt4wNC7ErH3KyPk0QQB4S7RzI6'
END
WHERE status = 'active'
	AND (
			(username = 'admin' AND role = 'admin')
			OR (username IN ('staff1', 'staff2') AND role = 'staff')
			OR (username IN ('jdelacruz', 'msantos', 'preyes', 'agarcia', 'rlim') AND role = 'resident')
	);