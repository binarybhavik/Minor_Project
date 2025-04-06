<?php
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/dao/TimetableDAO.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Test Results</h1>";

try {
    // Get database instance
    $db = Database::getInstance();
    if ($db->hasError()) {
        throw new Exception("Database connection error: " . $db->getError());
    }
    $conn = $db->getConnection();
    
    echo "<h2>Database Connection</h2>";
    echo "<p style='color: green;'>✓ Successfully connected to database</p>";
    
    // Get DAO instance
    $dao = TimetableDAO::getInstance();
    
    // Test table existence and contents
    $tables = ['sessions', 'semesters', 'courses', 'rooms', 'faculty', 'subjects', 'timetable'];
    
    echo "<h2>Table Status</h2>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            echo "<p>✓ Table '$table' exists with $count records</p>";
            
            // Show sample data
            $sample = $conn->query("SELECT * FROM $table LIMIT 1");
            if ($sample->num_rows > 0) {
                echo "<pre>";
                print_r($sample->fetch_assoc());
                echo "</pre>";
            }
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    // Test a sample timetable query
    echo "<h2>Sample Timetable Query</h2>";
    $filters = [
        'session' => '2024-2025'
    ];
    $entries = $dao->getTimetableEntries($filters);
    echo "<p>Found " . count($entries) . " entries with no filters except session</p>";
    if (count($entries) > 0) {
        echo "<pre>";
        print_r($entries[0]);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 