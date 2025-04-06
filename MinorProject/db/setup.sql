-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS FTT_iips;
USE FTT_iips;

-- Create timetable table
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester VARCHAR(10) NOT NULL,
    course VARCHAR(50) NOT NULL,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    faculty_name VARCHAR(100) NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_search (semester, course, day),
    INDEX idx_faculty (faculty_name),
    INDEX idx_subject (subject_code, subject_name),
    INDEX idx_room (room_number),
    INDEX idx_time (time_start, time_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO timetable (semester, course, day, time_start, time_end, subject_code, subject_name, faculty_name, room_number) VALUES
('II', 'MCA Sec-A', 'Monday', '09:00:00', '10:00:00', 'CS101', 'Computer Science', 'Dr. John Doe', 'Room 101'),
('II', 'MCA Sec-A', 'Monday', '10:00:00', '11:00:00', 'CS102', 'Programming', 'Dr. Jane Smith', 'Room 102'),
('IV', 'MCA Sec-B', 'Tuesday', '09:00:00', '10:00:00', 'CS201', 'Data Structures', 'Dr. Mike Johnson', 'Room 201'),
('IV', 'MCA Sec-B', 'Tuesday', '10:00:00', '11:00:00', 'CS202', 'Algorithms', 'Dr. Sarah Wilson', 'Room 202'); 