<?php
class Database {
    private static $instance = null;
    private $connection;
    private $dbname = "FTT_iips";

    private function __construct() {
        $servername = "localhost";
        $username = "root";
        $password = "";

        // Create connection to the existing database
        $this->connection = new mysqli($servername, $username, $password, $this->dbname);

        // Check connection
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        // Create tables if not exist
        $this->createTablesIfNotExist();
    }

    private function createTablesIfNotExist() {
        // Create timetable_entries table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS `timetable_entries` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `day_of_week` varchar(20) NOT NULL,
            `start_time` varchar(10) NOT NULL,
            `end_time` varchar(10) NOT NULL,
            `room_number` varchar(20) DEFAULT NULL,
            `subject_name` varchar(100) NOT NULL,
            `subject_code` varchar(20) NOT NULL,
            `faculty_name` varchar(100) NOT NULL,
            `semester_no` int(11) NOT NULL,
            `course_name` varchar(100) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->connection->query($createTableQuery);
        
        // Check if table is empty
        $result = $this->connection->query("SELECT COUNT(*) as count FROM timetable_entries");
        $row = $result->fetch_assoc();
        
        // If empty, add some sample data
        if ($row['count'] == 0) {
            $this->insertSampleData();
        }
    }
    
    private function insertSampleData() {
        $sampleData = [
            ["Monday", "11:00:00", "12:00:00", "Room-201", "Database Management Systems", "CS-101", "Dr. Smith", 2, "1"],
            ["Monday", "12:00:00", "13:00:00", "Lab-1", "Web Development", "CS-102", "Prof. Johnson", 2, "1"],
            ["Tuesday", "11:00:00", "12:00:00", "Room-202", "Operating Systems", "CS-103", "Dr. Williams", 2, "1"],
            ["Tuesday", "14:00:00", "15:00:00", "Room-101", "Data Structures", "CS-104", "Prof. Brown", 2, "1"],
            ["Wednesday", "13:00:00", "14:00:00", "Lab-2", "Algorithms", "CS-105", "Dr. Davis", 2, "1"],
            ["Thursday", "16:00:00", "17:00:00", "Room-203", "Computer Networks", "CS-106", "Prof. Miller", 2, "1"],
            ["Friday", "11:00:00", "12:00:00", "Room-204", "Artificial Intelligence", "CS-107", "Dr. Wilson", 2, "1"],
            ["Friday", "15:00:00", "16:00:00", "Lab-3", "Machine Learning", "CS-108", "Prof. Moore", 2, "1"],
            ["Monday", "14:00:00", "15:00:00", "Room-301", "Software Engineering", "CS-201", "Dr. Taylor", 4, "2"],
            ["Tuesday", "13:00:00", "14:00:00", "Room-302", "Mobile Development", "CS-202", "Prof. Anderson", 4, "2"],
            ["Wednesday", "16:00:00", "17:00:00", "Lab-4", "Cloud Computing", "CS-203", "Dr. Thomas", 4, "2"],
            ["Thursday", "12:00:00", "13:00:00", "Room-303", "Big Data Analytics", "CS-204", "Prof. Jackson", 4, "2"]
        ];
        
        $insertQuery = "INSERT INTO timetable_entries 
            (day_of_week, start_time, end_time, room_number, subject_name, subject_code, faculty_name, semester_no, course_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        $stmt = $this->connection->prepare($insertQuery);
        
        foreach ($sampleData as $entry) {
            $stmt->bind_param("sssssssss", 
                $entry[0], $entry[1], $entry[2], $entry[3], $entry[4], 
                $entry[5], $entry[6], $entry[7], $entry[8]
            );
            $stmt->execute();
        }
        
        $stmt->close();
    }

    public static function getConnection() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}
?> 