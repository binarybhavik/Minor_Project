<?php
// This is a bridge file to properly include the timetable view with session initialization

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log access to this file
$logFile = fopen(__DIR__ . '/timetable_access.log', 'a');
$timestamp = date('Y-m-d H:i:s');
fwrite($logFile, "[$timestamp] Timetable.php accessed\n");

// Check if timetable entries exist in session
if (isset($_SESSION['timetable_entries'])) {
    fwrite($logFile, "[$timestamp] Found " . count($_SESSION['timetable_entries']) . " timetable entries in session\n");
} else {
    fwrite($logFile, "[$timestamp] No timetable entries found in session\n");
}

// Print all session variables for debugging
fwrite($logFile, "[$timestamp] Session variables: " . print_r($_SESSION, true) . "\n");

// Include required utilities
require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/models/TimetableEntry.php';

// Ensure user is logged in
requireLogin();

// Include the actual timetable view
include_once __DIR__ . '/views/timetable.php';

// Close log file
fwrite($logFile, "[$timestamp] Timetable view process completed\n");
fclose($logFile);
?> 