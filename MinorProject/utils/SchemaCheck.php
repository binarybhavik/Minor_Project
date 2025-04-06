<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class SchemaCheck {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger('SchemaCheck');
    }
    
    /**
     * Check if all required tables exist and create them if missing
     */
    public function checkAndCreateTables() {
        $this->logger->info('Checking database schema...');
        
        try {
            // Check and create tables in the correct order
            $this->checkAndCreateSessionsTable();
            $this->checkAndCreateFacultyTable();
            $this->checkAndCreateCoursesTable();
            $this->checkAndCreateSemestersTable();
            $this->checkAndCreateRoomsTable();
            $this->checkAndCreateSubjectsTable();
            $this->checkAndCreateTimetableTable();
            
            $this->logger->info('Schema check complete.');
            return true;
        } catch (Exception $e) {
            $this->logger->error('Schema check failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if a table exists
     */
    private function tableExists($tableName) {
        $result = $this->db->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->num_rows > 0;
    }
    
    /**
     * Check and create sessions table
     */
    private function checkAndCreateSessionsTable() {
        if (!$this->tableExists('sessions')) {
            $this->logger->info('Creating sessions table...');
            
            $sql = "CREATE TABLE sessions (
                session_id INT AUTO_INCREMENT PRIMARY KEY,
                session_year VARCHAR(10) NOT NULL,
                batch_id INT,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create sessions table: " . $this->db->error);
            }
            
            // Insert default data
            $defaultSessions = [
                ['2023-2024', 1, 'Academic Year 2023-2024'],
                ['2024-2025', 2, 'Academic Year 2024-2025'],
                ['2025-2026', 3, 'Academic Year 2025-2026']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO sessions (session_year, batch_id, description) VALUES (?, ?, ?)");
            
            foreach ($defaultSessions as $session) {
                $stmt->bind_param('sis', $session[0], $session[1], $session[2]);
                $stmt->execute();
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Check and create faculty table
     */
    private function checkAndCreateFacultyTable() {
        if (!$this->tableExists('faculty')) {
            $this->logger->info('Creating faculty table...');
            
            $sql = "CREATE TABLE faculty (
                faculty_id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_name VARCHAR(100) NOT NULL,
                faculty_code VARCHAR(20),
                email VARCHAR(100),
                phone VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create faculty table: " . $this->db->error);
            }
            
            // Insert default faculty
            $defaultFaculty = [
                ['Dr. Sharma', 'SHARMA', 'sharma@example.com', '1234567890'],
                ['Prof. Singh', 'SINGH', 'singh@example.com', '9876543210']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO faculty (faculty_name, faculty_code, email, phone) VALUES (?, ?, ?, ?)");
            
            foreach ($defaultFaculty as $faculty) {
                $stmt->bind_param('ssss', $faculty[0], $faculty[1], $faculty[2], $faculty[3]);
                $stmt->execute();
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Check and create courses table
     */
    private function checkAndCreateCoursesTable() {
        if (!$this->tableExists('courses')) {
            $this->logger->info('Creating courses table...');
            
            $sql = "CREATE TABLE courses (
                course_id INT AUTO_INCREMENT PRIMARY KEY,
                course_name VARCHAR(100) NOT NULL,
                course_code VARCHAR(20),
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create courses table: " . $this->db->error);
            }
            
            // Insert default courses
            $defaultCourses = [
                ['B.Tech (IT)', 'BTECH-IT', 'Bachelor of Technology in Information Technology'],
                ['B.Tech (CS)', 'BTECH-CS', 'Bachelor of Technology in Computer Science'],
                ['MCA', 'MCA', 'Master of Computer Applications']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO courses (course_name, course_code, description) VALUES (?, ?, ?)");
            
            foreach ($defaultCourses as $course) {
                $stmt->bind_param('sss', $course[0], $course[1], $course[2]);
                $stmt->execute();
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Check and create semesters table
     */
    private function checkAndCreateSemestersTable() {
        if (!$this->tableExists('semesters')) {
            $this->logger->info('Creating semesters table...');
            
            $sql = "CREATE TABLE semesters (
                semester_id INT AUTO_INCREMENT PRIMARY KEY,
                semester_no VARCHAR(10) NOT NULL,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create semesters table: " . $this->db->error);
            }
            
            // Insert default semesters
            $defaultSemesters = [
                ['1', 'First Semester'],
                ['2', 'Second Semester'],
                ['3', 'Third Semester'],
                ['4', 'Fourth Semester'],
                ['5', 'Fifth Semester'],
                ['6', 'Sixth Semester'],
                ['7', 'Seventh Semester'],
                ['8', 'Eighth Semester']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO semesters (semester_no, description) VALUES (?, ?)");
            
            foreach ($defaultSemesters as $semester) {
                $stmt->bind_param('ss', $semester[0], $semester[1]);
                $stmt->execute();
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Check and create rooms table
     */
    private function checkAndCreateRoomsTable() {
        if (!$this->tableExists('rooms')) {
            $this->logger->info('Creating rooms table...');
            
            $sql = "CREATE TABLE rooms (
                room_id INT AUTO_INCREMENT PRIMARY KEY,
                room_number VARCHAR(20) NOT NULL,
                room_type VARCHAR(50),
                capacity INT,
                building VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create rooms table: " . $this->db->error);
            }
            
            // Insert default rooms
            $defaultRooms = [
                ['201', 'Classroom', 60, 'IT Building'],
                ['202', 'Classroom', 60, 'IT Building'],
                ['203', 'Classroom', 60, 'IT Building'],
                ['204', 'Classroom', 60, 'IT Building'],
                ['205', 'Classroom', 60, 'IT Building'],
                ['301', 'Computer Lab', 40, 'IT Building'],
                ['302', 'Computer Lab', 40, 'IT Building']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO rooms (room_number, room_type, capacity, building) VALUES (?, ?, ?, ?)");
            
            foreach ($defaultRooms as $room) {
                $stmt->bind_param('ssis', $room[0], $room[1], $room[2], $room[3]);
                $stmt->execute();
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Check and create subjects table
     */
    private function checkAndCreateSubjectsTable() {
        if (!$this->tableExists('subjects')) {
            $this->logger->info('Creating subjects table...');
            
            $sql = "CREATE TABLE subjects (
                subject_id INT AUTO_INCREMENT PRIMARY KEY,
                subject_name VARCHAR(100) NOT NULL,
                subject_code VARCHAR(20) NOT NULL,
                faculty_id INT,
                credit_hours INT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create subjects table: " . $this->db->error);
            }
            
            // Get faculty IDs for default subjects
            $result = $this->db->query("SELECT faculty_id, faculty_name FROM faculty LIMIT 2");
            $facultyIds = [];
            while ($row = $result->fetch_assoc()) {
                $facultyIds[$row['faculty_name']] = $row['faculty_id'];
            }
            
            if (empty($facultyIds)) {
                return; // No faculty, skip default subjects
            }
            
            // Insert default subjects
            $defaultSubjects = [
                ['Data Communication', 'IT-201', $facultyIds['Dr. Sharma'], 4, 'Principles of Data Communication'],
                ['Computer Networks', 'IT-202', $facultyIds['Dr. Sharma'], 4, 'Fundamentals of Computer Networks'],
                ['Database Systems', 'IT-203', $facultyIds['Prof. Singh'], 4, 'Database Management Systems'],
                ['Operating Systems', 'IT-204', $facultyIds['Prof. Singh'], 4, 'Principles of Operating Systems']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO subjects (subject_name, subject_code, faculty_id, credit_hours, description) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($defaultSubjects as $subject) {
                $stmt->bind_param('ssiis', $subject[0], $subject[1], $subject[2], $subject[3], $subject[4]);
                $stmt->execute();
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Check and create timetable table
     */
    private function checkAndCreateTimetableTable() {
        if (!$this->tableExists('timetable')) {
            $this->logger->info('Creating timetable table...');
            
            $sql = "CREATE TABLE timetable (
                timetable_id INT AUTO_INCREMENT PRIMARY KEY,
                day_of_week VARCHAR(20) NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                semester_id INT NOT NULL,
                course_id INT NOT NULL,
                room_id INT NOT NULL,
                subject_id INT NOT NULL,
                session_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
                FOREIGN KEY (course_id) REFERENCES courses(course_id),
                FOREIGN KEY (room_id) REFERENCES rooms(room_id),
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
                FOREIGN KEY (session_id) REFERENCES sessions(session_id)
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create timetable table: " . $this->db->error);
            }
        }
    }
}
?> 