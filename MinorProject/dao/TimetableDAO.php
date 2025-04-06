<?php
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../models/TimetableEntry.php';
require_once __DIR__ . '/../utils/Logger.php';

class TimetableDAO {
    private $db;
    private $logger;
    private static $instance = null;

    private function __construct() {
        try {
            $database = Database::getInstance();
            
            // Check if there's a database error before continuing
            if ($database->hasError()) {
                $this->logger = new Logger('TimetableDAO');
                $this->logger->error("Database error: " . $database->getError());
                $this->db = null;
                return;
            }
            
            $this->db = $database->getConnection();
            $this->logger = new Logger('TimetableDAO');
            
            // Create tables if they don't exist
            $this->setupTables();
        } catch (Exception $e) {
            $this->logger = new Logger('TimetableDAO');
            $this->logger->error("Failed to initialize TimetableDAO: " . $e->getMessage());
            $this->db = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set up all required tables
     */
    private function setupTables() {
        if ($this->db === null) {
            return;
        }
        
        try {
            $this->createFacultyTableIfNotExists();
            $this->createSemestersTableIfNotExists();
            $this->createSubjectsTableIfNotExists();
            $this->createRoomsTableIfNotExists();
            $this->createSessionsTableIfNotExists();
            $this->createCoursesTableIfNotExists();
            $this->createTimetableTableIfNotExists();
        } catch (Exception $e) {
            $this->logger->error("Error setting up tables: " . $e->getMessage());
        }
    }

    /**
     * Get timetable entries based on filter parameters using prepared statements
     */
    public function getTimetableEntries($filters = []) {
        try {
            $conditions = [];
            $params = [];
            $types = '';

            // Log the filters being used
            error_log("TimetableDAO::getTimetableEntries - Filters: " . print_r($filters, true));

            $sql = "SELECT 
                t.day_of_week, 
                TIME(t.start_time) AS start_time, 
                TIME(t.end_time) AS end_time,
                r.room_number, 
                s.subject_name, 
                f.faculty_name, 
                sem.semester_no, 
                c.course_name,
                batch.BatchYear as session_year
            FROM 
                timetable t
            JOIN 
                rooms r ON t.room_id = r.room_id
            JOIN 
                subjects s ON t.subject_id = s.subject_id
            JOIN 
                faculty f ON s.faculty_id = f.faculty_id
            JOIN 
                semesters sem ON t.semester_id = sem.semester_id
            JOIN 
                courses c ON t.course_id = c.course_id
            LEFT JOIN 
                Batch_Year batch ON t.Batch_ID = batch.Batch_ID
            WHERE 1=1";

            // Add filters
            if (!empty($filters['semester'])) {
                $conditions[] = "sem.semester_no = ?";
                $params[] = $filters['semester'];
                $types .= 's';
            }
            
            if (!empty($filters['course'])) {
                $conditions[] = "c.course_name = ?";
                $params[] = $filters['course'];
                $types .= 's';
            }
            
            if (!empty($filters['day'])) {
                $conditions[] = "t.day_of_week = ?";
                $params[] = $filters['day'];
                $types .= 's';
            }
            
            if (!empty($filters['faculty'])) {
                $conditions[] = "f.faculty_name LIKE ?";
                $params[] = "%{$filters['faculty']}%";
                $types .= 's';
            }
            
            if (!empty($filters['subject'])) {
                $conditions[] = "s.subject_name LIKE ?";
                $params[] = "%{$filters['subject']}%";
                $types .= 's';
            }
            
            if (!empty($filters['room'])) {
                $conditions[] = "r.room_number = ?";
                $params[] = $filters['room'];
                $types .= 's';
            }

            if (!empty($filters['time'])) {
                $conditions[] = "TIME(?) BETWEEN t.start_time AND t.end_time";
                $params[] = $filters['time'];
                $types .= 's';
            }
            
            // Add session filter
            if (!empty($filters['session'])) {
                $conditions[] = "batch.BatchYear = ?";
                $params[] = $filters['session'];
                $types .= 's';
            }

            // Add conditions to SQL
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }

            // Add order by
            $sql .= " ORDER BY t.day_of_week, t.start_time, t.end_time";

            // Log the final SQL query and parameters
            error_log("TimetableDAO::getTimetableEntries - SQL: " . $sql);
            error_log("TimetableDAO::getTimetableEntries - Params: " . print_r($params, true));

            // Prepare and execute
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->db->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $entries = [];

            while ($row = $result->fetch_assoc()) {
                $entries[] = [
                    'day' => $row['day_of_week'],
                    'timeStart' => $row['start_time'],
                    'timeEnd' => $row['end_time'],
                    'roomNumber' => $row['room_number'],
                    'subjectName' => $row['subject_name'],
                    'facultyName' => $row['faculty_name'],
                    'semester' => $row['semester_no'],
                    'course' => $row['course_name'],
                    'session' => $row['session_year'] ?? 'N/A'
                ];
            }

            // Log the number of entries found
            error_log("TimetableDAO::getTimetableEntries - Found " . count($entries) . " entries");

            $stmt->close();
            return $entries;

        } catch (Exception $e) {
            $this->logger->error("Error retrieving timetable entries: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get distinct values for dropdowns
     */
    public function getDistinctValues($table, $column) {
        try {
            $validTables = [
                'semesters' => 'semester_no',
                'courses' => 'course_name',
                'faculty' => 'faculty_name',
                'rooms' => 'room_number',
                'sessions' => 'session_year',
                'Batch_Year' => 'BatchYear'
            ];

            // Special handling for the 'sessions' alias
            if ($table === 'sessions') {
                $table = 'Batch_Year';
                $column = 'BatchYear';
            }

            // Fallback if table doesn't exist
            if (!isset($validTables[$table])) {
                return []; // Return empty array instead of throwing exception
            }

            $columnName = $validTables[$table];
            
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE '$table'");
            if ($tableExists->num_rows == 0) {
                error_log("Table $table does not exist");
                return []; // Return empty array if table doesn't exist
            }
            
            $sql = "SELECT DISTINCT $columnName FROM $table ORDER BY $columnName";
            $result = $this->db->query($sql);

            if (!$result) {
                error_log("Failed to fetch distinct values: " . $this->db->error);
                return []; // Return empty array on error
            }

            $values = [];
            while ($row = $result->fetch_assoc()) {
                $values[] = $row[$columnName];
            }

            return $values;

        } catch (Exception $e) {
            error_log("Error in getDistinctValues: " . $e->getMessage());
            return []; // Return empty array on exception
        }
    }
    
    /**
     * Get batch years for dropdowns
     */
    public function getSessions() {
        try {
            // Check if Batch_Year table exists
            $tableExists = $this->db->query("SHOW TABLES LIKE 'Batch_Year'");
            if ($tableExists->num_rows == 0) {
                $this->createSessionsTableIfNotExists();
            }
            
            $sql = "SELECT Batch_ID, BatchYear FROM Batch_Year ORDER BY BatchYear DESC";
            $result = $this->db->query($sql);

            if (!$result) {
                $this->logger->error("Failed to fetch batch years: " . $this->db->error);
                return [];
            }

            $sessions = [];
            while ($row = $result->fetch_assoc()) {
                $sessions[] = $row;
            }

            return $sessions;

        } catch (Exception $e) {
            $this->logger->error("Error in getSessions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create Batch_Year table if it doesn't exist
     */
    public function createSessionsTableIfNotExists() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS Batch_Year (
                Batch_ID INT AUTO_INCREMENT PRIMARY KEY,
                BatchYear VARCHAR(20) NOT NULL,
                UNIQUE KEY unique_batch_year (BatchYear)
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create Batch_Year table: " . $this->db->error);
            }
            
            // Check if the table is empty, if so, insert default data
            $result = $this->db->query("SELECT COUNT(*) as count FROM Batch_Year");
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                // Insert default batch year data
                $defaultBatches = [
                    ['2023-2024'],
                    ['2024-2025'],
                    ['2025-2026'],
                    ['2026-2027'],
                    ['2027-2028']
                ];
                
                $stmt = $this->db->prepare("INSERT INTO Batch_Year (BatchYear) VALUES (?)");
                
                foreach ($defaultBatches as $batch) {
                    $stmt->bind_param('s', $batch[0]);
                    $stmt->execute();
                }
                
                $stmt->close();
            } else if ($row['count'] < 4) {
                // Make sure we have at least 4 batch years
                $currentYears = $this->getDistinctValues('Batch_Year', 'BatchYear');
                
                // Generate years based on the latest year
                $latestYear = !empty($currentYears) ? max($currentYears) : '2024-2025';
                
                // Parse the end year
                preg_match('/(\d{4})-(\d{4})/', $latestYear, $matches);
                if (count($matches) === 3) {
                    $endYear = (int)$matches[2];
                    
                    // Generate 4 additional years
                    $additionalYears = [];
                    for ($i = 1; $i <= 4; $i++) {
                        $startYear = $endYear + $i - 1;
                        $newEndYear = $endYear + $i;
                        $batchYear = $startYear . '-' . $newEndYear;
                        
                        // Check if this year already exists
                        if (!in_array($batchYear, $currentYears)) {
                            $additionalYears[] = [$batchYear];
                        }
                    }
                    
                    // Insert additional years
                    if (!empty($additionalYears)) {
                        $stmt = $this->db->prepare("INSERT INTO Batch_Year (BatchYear) VALUES (?)");
                        foreach ($additionalYears as $batch) {
                            $stmt->bind_param('s', $batch[0]);
                            $stmt->execute();
                        }
                        $stmt->close();
                    }
                }
            }
            
            // Check if timetable table has Batch_ID column, add if not
            $result = $this->db->query("SHOW COLUMNS FROM timetable LIKE 'Batch_ID'");
            if ($result->num_rows == 0) {
                $addColumnSql = "ALTER TABLE timetable ADD COLUMN Batch_ID INT, 
                                 ADD FOREIGN KEY (Batch_ID) REFERENCES Batch_Year(Batch_ID)";
                if (!$this->db->query($addColumnSql)) {
                    throw new Exception("Failed to add Batch_ID column to timetable: " . $this->db->error);
                }
                
                // Set default Batch_ID value
                $defaultBatchId = 2; // 2024-2025
                $updateSql = "UPDATE timetable SET Batch_ID = ? WHERE Batch_ID IS NULL";
                $stmt = $this->db->prepare($updateSql);
                $stmt->bind_param('i', $defaultBatchId);
                $stmt->execute();
                $stmt->close();
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in createSessionsTableIfNotExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets counts of records in the specified table
     * 
     * @param string $tableName The table to count from
     * @param string $columnName The column to count by
     * @return int The number of records
     */
    public function getCount($tableName, $columnName) {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE '$tableName'");
            if ($tableExists->num_rows == 0) {
                $this->logger->error("Table $tableName does not exist");
                return 0;
            }
            
            $sql = "SELECT COUNT($columnName) as count FROM $tableName";
            $result = $this->db->query($sql);
            
            if (!$result) {
                $this->logger->error("Failed to get count: " . $this->db->error);
                return 0;
            }
            
            $row = $result->fetch_assoc();
            return (int)$row['count'];
        } catch (Exception $e) {
            $this->logger->error("Error fetching count from $tableName: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Gets all faculty members
     * 
     * @return array Faculty members data
     */
    public function getAllFaculty() {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'faculty'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createFacultyTableIfNotExists();
            }
            
            $sql = "SELECT faculty_id, faculty_name FROM faculty ORDER BY faculty_name";
            $result = $this->db->query($sql);
            
            if (!$result) {
                $this->logger->error("Failed to fetch faculty members: " . $this->db->error);
                return [];
            }
            
            $faculty = [];
            while ($row = $result->fetch_assoc()) {
                $faculty[] = $row;
            }
            
            return $faculty;
        } catch (Exception $e) {
            $this->logger->error("Error fetching faculty members: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new faculty member
     * 
     * @param string $facultyName The name of the faculty member
     * @return bool Whether the faculty member was added successfully
     */
    public function addFaculty($facultyName) {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'faculty'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createFacultyTableIfNotExists();
            }
            
            $sql = "INSERT INTO faculty (faculty_name) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $facultyName);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error adding faculty member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing faculty member
     * 
     * @param int $facultyId The ID of the faculty member
     * @param string $facultyName The new name of the faculty member
     * @return bool Whether the faculty member was updated successfully
     */
    public function updateFaculty($facultyId, $facultyName) {
        try {
            $sql = "UPDATE faculty SET faculty_name = ? WHERE faculty_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $facultyName, $facultyId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error updating faculty member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a faculty member
     * 
     * @param int $facultyId The ID of the faculty member
     * @return bool Whether the faculty member was deleted successfully
     */
    public function deleteFaculty($facultyId) {
        try {
            // Start transaction - we'll need to handle associated records
            $this->db->begin_transaction();
            
            // First check if there are subjects associated with this faculty
            $checkSql = "SELECT COUNT(*) as count FROM subjects WHERE faculty_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param("i", $facultyId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                // If there are subjects linked to this faculty, update them to NULL or remove them
                // Here we're setting the faculty_id to NULL for simplicity
                $updateSql = "UPDATE subjects SET faculty_id = NULL WHERE faculty_id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bind_param("i", $facultyId);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            // Now delete the faculty
            $sql = "DELETE FROM faculty WHERE faculty_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $facultyId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            // Commit the transaction
            $this->db->commit();
            
            return $result;
        } catch (Exception $e) {
            // Rollback the transaction if something fails
            $this->db->rollback();
            $this->logger->error("Error deleting faculty member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create faculty table if it doesn't exist
     */
    public function createFacultyTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS faculty (
            faculty_id INT AUTO_INCREMENT PRIMARY KEY,
            faculty_name VARCHAR(100) NOT NULL,
            UNIQUE KEY unique_faculty_name (faculty_name)
        )";
        if (!$this->db->query($sql)) {
            throw new Exception("Failed to create faculty table: " . $this->db->error);
        }
    }

    /**
     * Gets all subjects
     * 
     * @return array Subjects data
     */
    public function getAllSubjects() {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'subjects'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createSubjectsTableIfNotExists();
            }
            
            $sql = "SELECT s.subject_id, s.subject_code, s.subject_name, 
                    s.faculty_id, IFNULL(f.faculty_name, 'Not Assigned') as faculty_name 
                    FROM subjects s 
                    LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
                    ORDER BY s.subject_name";
            $result = $this->db->query($sql);
            
            if (!$result) {
                $this->logger->error("Failed to fetch subjects: " . $this->db->error);
                return [];
            }
            
            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }
            
            return $subjects;
        } catch (Exception $e) {
            $this->logger->error("Error fetching subjects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new subject
     * 
     * @param string $subjectName The name of the subject
     * @param string $subjectCode The code of the subject
     * @param int $facultyId The ID of the faculty
     * @return bool Whether the subject was added successfully
     */
    public function addSubject($subjectName, $subjectCode, $facultyId) {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'subjects'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createSubjectsTableIfNotExists();
            }
            
            $sql = "INSERT INTO subjects (subject_name, subject_code, faculty_id) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssi", $subjectName, $subjectCode, $facultyId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error adding subject: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing subject
     * 
     * @param int $subjectId The ID of the subject
     * @param string $subjectName The new name of the subject
     * @param string $subjectCode The new code of the subject
     * @param int $facultyId The new ID of the faculty
     * @return bool Whether the subject was updated successfully
     */
    public function updateSubject($subjectId, $subjectName, $subjectCode, $facultyId) {
        try {
            $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, faculty_id = ? WHERE subject_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssii", $subjectName, $subjectCode, $facultyId, $subjectId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error updating subject: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a subject
     * 
     * @param int $subjectId The ID of the subject
     * @return bool Whether the subject was deleted successfully
     */
    public function deleteSubject($subjectId) {
        try {
            // Start transaction - we'll need to handle associated records
            $this->db->begin_transaction();
            
            // First check if there are timetable entries associated with this subject
            $checkSql = "SELECT COUNT(*) as count FROM timetable WHERE subject_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param("i", $subjectId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                // If there are timetable entries linked to this subject, delete them first
                $deleteTimetableSql = "DELETE FROM timetable WHERE subject_id = ?";
                $deleteTimetableStmt = $this->db->prepare($deleteTimetableSql);
                $deleteTimetableStmt->bind_param("i", $subjectId);
                $deleteTimetableStmt->execute();
                $deleteTimetableStmt->close();
            }
            
            // Now delete the subject
            $sql = "DELETE FROM subjects WHERE subject_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $subjectId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            // Commit the transaction
            $this->db->commit();
            
            return $result;
        } catch (Exception $e) {
            // Rollback the transaction if something fails
            $this->db->rollback();
            $this->logger->error("Error deleting subject: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all semesters for dropdowns
     * 
     * @return array Semesters data
     */
    public function getAllSemesters() {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'semesters'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createSemestersTableIfNotExists();
            }
            
            $sql = "SELECT semester_id, semester_no FROM semesters ORDER BY semester_no";
            $result = $this->db->query($sql);
            
            if (!$result) {
                $this->logger->error("Failed to fetch semesters: " . $this->db->error);
                return [];
            }
            
            $semesters = [];
            while ($row = $result->fetch_assoc()) {
                $semesters[] = $row;
            }
            
            return $semesters;
        } catch (Exception $e) {
            $this->logger->error("Error fetching semesters: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create subjects table if it doesn't exist
     */
    public function createSubjectsTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS subjects (
            subject_id INT AUTO_INCREMENT PRIMARY KEY,
            subject_name VARCHAR(100) NOT NULL,
            subject_code VARCHAR(20) NOT NULL,
            faculty_id INT NOT NULL,
            FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
            UNIQUE KEY unique_subject_code (subject_code),
            UNIQUE KEY unique_subject_faculty (subject_name, faculty_id)
        )";
        if (!$this->db->query($sql)) {
            throw new Exception("Failed to create subjects table: " . $this->db->error);
        }
    }

    /**
     * Create semesters table if it doesn't exist
     */
    public function createSemestersTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS semesters (
            semester_id INT AUTO_INCREMENT PRIMARY KEY,
            semester_no VARCHAR(20) NOT NULL,
            UNIQUE KEY unique_semester_no (semester_no)
        )";
        if (!$this->db->query($sql)) {
            throw new Exception("Failed to create semesters table: " . $this->db->error);
        }
    }

    /**
     * Gets all rooms
     * 
     * @return array Rooms data
     */
    public function getAllRooms() {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'rooms'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createRoomsTableIfNotExists();
            }
            
            $sql = "SELECT room_id, room_number FROM rooms ORDER BY room_number";
            $result = $this->db->query($sql);
            
            if (!$result) {
                $this->logger->error("Failed to fetch rooms: " . $this->db->error);
                return [];
            }
            
            $rooms = [];
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
            
            return $rooms;
        } catch (Exception $e) {
            $this->logger->error("Error fetching rooms: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new room
     * 
     * @param string $roomNumber The number of the room
     * @return bool Whether the room was added successfully
     */
    public function addRoom($roomNumber) {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'rooms'");
            if ($tableExists->num_rows == 0) {
                // Create the table if it doesn't exist
                $this->createRoomsTableIfNotExists();
            }
            
            $sql = "INSERT INTO rooms (room_number) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $roomNumber);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error adding room: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing room
     * 
     * @param int $roomId The ID of the room
     * @param string $roomNumber The new number of the room
     * @return bool Whether the room was updated successfully
     */
    public function updateRoom($roomId, $roomNumber) {
        try {
            $sql = "UPDATE rooms SET room_number = ? WHERE room_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $roomNumber, $roomId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error updating room: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a room
     * 
     * @param int $roomId The ID of the room
     * @return bool Whether the room was deleted successfully
     */
    public function deleteRoom($roomId) {
        try {
            // Start transaction - we'll need to handle associated records
            $this->db->begin_transaction();
            
            // First check if there are timetable entries associated with this room
            $checkSql = "SELECT COUNT(*) as count FROM timetable WHERE room_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param("i", $roomId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                // If there are timetable entries linked to this room, delete them first
                $deleteTimetableSql = "DELETE FROM timetable WHERE room_id = ?";
                $deleteTimetableStmt = $this->db->prepare($deleteTimetableSql);
                $deleteTimetableStmt->bind_param("i", $roomId);
                $deleteTimetableStmt->execute();
                $deleteTimetableStmt->close();
            }
            
            // Now delete the room
            $sql = "DELETE FROM rooms WHERE room_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $roomId);
            
            $result = $stmt->execute();
            $stmt->close();
            
            // Commit the transaction
            $this->db->commit();
            
            return $result;
        } catch (Exception $e) {
            // Rollback the transaction if something fails
            $this->db->rollback();
            $this->logger->error("Error deleting room: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create rooms table if it doesn't exist
     */
    public function createRoomsTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS rooms (
            room_id INT AUTO_INCREMENT PRIMARY KEY,
            room_number VARCHAR(20) NOT NULL,
            UNIQUE KEY unique_room_number (room_number)
        )";
        if (!$this->db->query($sql)) {
            throw new Exception("Failed to create rooms table: " . $this->db->error);
        }
    }

    /**
     * Create a courses table if it doesn't exist yet
     */
    public function createCoursesTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(50) NOT NULL,
            UNIQUE KEY unique_course_name (course_name)
        )";
        if (!$this->db->query($sql)) {
            throw new Exception("Failed to create courses table: " . $this->db->error);
        }
    }
    
    /**
     * Create a timetable table if it doesn't exist yet
     */
    public function createTimetableTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS timetable (
            timetable_id INT AUTO_INCREMENT PRIMARY KEY,
            day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            room_id INT NOT NULL,
            subject_id INT NOT NULL,
            semester_id INT NOT NULL,
            course_id INT NOT NULL,
            Batch_ID INT NOT NULL,
            FOREIGN KEY (room_id) REFERENCES rooms(room_id),
            FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
            FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
            FOREIGN KEY (course_id) REFERENCES courses(course_id),
            FOREIGN KEY (Batch_ID) REFERENCES Batch_Year(Batch_ID),
            UNIQUE KEY unique_schedule (day_of_week, start_time, room_id, Batch_ID)
        )";
        if (!$this->db->query($sql)) {
            throw new Exception("Failed to create timetable table: " . $this->db->error);
        }
    }
    
    /**
     * Get the database connection
     * 
     * @return mysqli|null The database connection
     */
    public function getConnection() {
        return $this->db;
    }

    /**
     * Add a new batch year
     * 
     * @param string $batchYear The batch year to add (e.g., "2026-2027")
     * @return bool|int Returns the ID of the newly added batch year or false on failure
     */
    public function addBatchYear($batchYear) {
        try {
            // Validate the batch year format
            if (!preg_match('/^\d{4}-\d{4}$/', $batchYear)) {
                $this->logger->error("Invalid batch year format: $batchYear");
                return false;
            }
            
            // Check if the batch year already exists
            $sql = "SELECT Batch_ID FROM Batch_Year WHERE BatchYear = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $batchYear);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Batch year already exists, return its ID
                $row = $result->fetch_assoc();
                $stmt->close();
                return $row['Batch_ID'];
            }
            
            // Insert the new batch year
            $insertSql = "INSERT INTO Batch_Year (BatchYear) VALUES (?)";
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->bind_param("s", $batchYear);
            
            if ($insertStmt->execute()) {
                $newId = $this->db->insert_id;
                $insertStmt->close();
                $this->logger->log("Added new batch year: $batchYear with ID: $newId");
                return $newId;
            } else {
                $this->logger->error("Failed to add batch year: " . $insertStmt->error);
                $insertStmt->close();
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error("Error adding batch year: " . $e->getMessage());
            return false;
        }
    }
}
?> 