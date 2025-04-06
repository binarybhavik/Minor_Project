<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/dao/TimetableDAO.php';

// Start session if needed
SessionUtils::startSessionIfNeeded();

// Check if user is logged in
if (!SessionUtils::isLoggedIn()) {
    echo json_encode(['error' => 'You must be logged in to access this data']);
    exit;
}

try {
    // Use getInstance instead of new constructor
    $dao = TimetableDAO::getInstance();
    
    // Ensure sessions table exists
    $dao->createSessionsTableIfNotExists();
    
    // Get filter parameters
    $filters = [
        'semester' => $_GET['semester'] ?? '',
        'course' => $_GET['course'] ?? '',
        'day' => $_GET['day'] ?? '',
        'faculty' => $_GET['faculty'] ?? '',
        'subject' => $_GET['subject'] ?? '',
        'room' => $_GET['room'] ?? '',
        'time' => $_GET['time'] ?? '',
        'session' => $_GET['session'] ?? ''
    ];

    // Log the received filters
    error_log("Received filters: " . print_r($filters, true));

    // Check if session is specified (now required)
    if (empty($filters['session'])) {
        echo json_encode(['error' => 'Please select a batch year']);
        exit;
    }

    // Check if at least one filter is provided besides session
    $hasFilter = false;
    foreach ($filters as $key => $value) {
        if (!empty($value) && $key !== 'session') {  // Session doesn't count as a filter since it's always required
            $hasFilter = true;
            break;
        }
    }

    if (!$hasFilter) {
        echo json_encode(['error' => 'Please provide at least one search criteria besides batch year']);
        exit;
    }

    // Get timetable entries
    $entries = $dao->getTimetableEntries($filters);
    
    // Log the number of entries found
    error_log("Number of entries found: " . count($entries));
    
    // Return JSON response
    echo json_encode($entries);

} catch (Exception $e) {
    // Log error
    error_log("Error in get_timetable.php: " . $e->getMessage());
    
    // Return error message
    echo json_encode(['error' => 'An error occurred while fetching the timetable: ' . $e->getMessage()]);
} 