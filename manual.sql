-- in users table 
-- password = 8-D 
INSERT INTO users (username, password, role, first_name, last_name, contact_no, avatar)
VALUES ('mas-sir', '$2y$10$yg9tpPVPz3677NZmMtx0t..fHtlJIyG3YMsOCmyJK93qnNOjH4ivi', 'admin', 'Admin', 'User', '1234567890', NULL);


-- in users table 

ALTER TABLE users
ADD shift VARCHAR(20) DEFAULT NULL,
ADD is_drmc BOOLEAN DEFAULT FALSE,
ADD collage_id INT UNIQUE;
