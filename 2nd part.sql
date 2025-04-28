-- in users table 

INSERT INTO users (username, password, role, first_name, last_name, contact_no, avatar)
VALUES ('mas-sir', '$2y$10$yg9tpPVPz3677NZmMtx0t..fHtlJIyG3YMsOCmyJK93qnNOjH4ivi', 'admin', 'Admin', NULL, '01720212008', NULL);


-- in users table 

ALTER TABLE users
ADD shift VARCHAR(20) DEFAULT NULL,
ADD is_drmc BOOLEAN DEFAULT FALSE,
ADD collage_id INT UNIQUE;

-- Modify results table to add new fields
ALTER TABLE results
ADD COLUMN description TEXT DEFAULT NULL,
ADD COLUMN type ENUM('result', 'sheet') NOT NULL DEFAULT 'result',
ADD COLUMN published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE read_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (message_id) REFERENCES messages(id)
);



