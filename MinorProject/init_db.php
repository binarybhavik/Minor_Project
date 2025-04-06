<?php
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/dao/TimetableDAO.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Get database instance
    $db = Database::getInstance();
    if ($db->hasError()) {
        throw new Exception("Database connection error: " . $db->getError());
    }
    $conn = $db->getConnection();
    
    // Get DAO instance
    $dao = TimetableDAO::getInstance();
    
    // Insert sample data
    
    // 1. Insert faculty
    $conn->query("INSERT IGNORE INTO faculty (faculty_name) VALUES 
        ('Dr. John Smith'),
        ('Prof. Sarah Johnson'),
        ('Dr. Michael Brown')");
    
    // 2. Insert semesters
    $conn->query("INSERT IGNORE INTO semesters (semester_no) VALUES 
        ('1'),
        ('2'),
        ('3'),
        ('4')");
    
    // 3. Insert courses
    $conn->query("INSERT IGNORE INTO courses (course_name) VALUES 
        ('BCA'),
        ('MCA'),
        ('MBA')");
    
    // 4. Insert rooms
    $conn->query("INSERT IGNORE INTO rooms (room_number) VALUES 
        ('101'),
        ('102'),
        ('103')");
    
    // 5. Insert sessions
    $conn->query("INSERT IGNORE INTO sessions (session_year) VALUES 
        ('2024-2025'),
        ('2023-2024')");
    
    // Get IDs for relationships
    $faculty = $conn->query("SELECT faculty_id, faculty_name FROM faculty")->fetch_all(MYSQLI_ASSOC);
    $semesters = $conn->query("SELECT semester_id, semester_no FROM semesters")->fetch_all(MYSQLI_ASSOC);
    $courses = $conn->query("SELECT course_id, course_name FROM courses")->fetch_all(MYSQLI_ASSOC);
    $rooms = $conn->query("SELECT room_id, room_number FROM rooms")->fetch_all(MYSQLI_ASSOC);
    $sessions = $conn->query("SELECT session_id, session_year FROM sessions")->fetch_all(MYSQLI_ASSOC);
    
    // 6. Insert subjects
    foreach ($faculty as $f) {
        $conn->query("INSERT IGNORE INTO subjects (subject_name, subject_code, faculty_id) VALUES 
            ('Database Management Systems', 'DBMS-101', {$f['faculty_id']}),
            ('Web Development', 'WEB-201', {$f['faculty_id']}),
            ('Data Structures', 'DS-301', {$f['faculty_id']})");
    }
    
    $subjects = $conn->query("SELECT subject_id, subject_name FROM subjects")->fetch_all(MYSQLI_ASSOC);
    
    // 7. Insert timetable entries
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $times = [
        ['09:00:00', '10:00:00'],
        ['10:00:00', '11:00:00'],
        ['11:00:00', '12:00:00']
    ];
    
    foreach ($sessions as $session) {
        foreach ($courses as $course) {
            foreach ($semesters as $semester) {
                foreach ($days as $dayIndex => $day) {
                    foreach ($times as $timeIndex => $time) {
                        $subject = $subjects[array_rand($subjects)];
                        $room = $rooms[array_rand($rooms)];
                        
                        $sql = "INSERT IGNORE INTO timetable 
                            (day_of_week, start_time, end_time, room_id, subject_id, semester_id, course_id, session_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('sssiiiis', 
                            $day, 
                            $time[0], 
                            $time[1], 
                            $room['room_id'], 
                            $subject['subject_id'], 
                            $semester['semester_id'], 
                            $course['course_id'],
                            $session['session_id']
                        );
                        $stmt->execute();
                    }
                }
            }
        }
    }
    
    echo "Sample data has been successfully inserted into the database.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 