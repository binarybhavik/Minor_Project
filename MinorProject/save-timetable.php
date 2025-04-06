<?php
// Start output buffering to prevent any PHP notices from corrupting JSON output
ob_start();

require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/dao/TimetableDAO.php';
require_once __DIR__ . '/utils/Logger.php';

// Enable error reporting for logging but not for display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start the session if not started
SessionUtils::startSessionIfNeeded();

// Enforce admin role access
SessionUtils::requireAdmin();

// Initialize logger
$logger = new Logger('SaveTimetable');

// Check for mysqli extension
if (!extension_loaded('mysqli')) {
    $logger->error('The mysqli extension is not loaded. Please enable it in your php.ini file.');
    sendResponse(false, 'Database error: mysqli extension not loaded. Please see logs for details.');
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
    exit;
}

// Get and decode JSON data
$jsonData = file_get_contents('php://input');
$timetableData = json_decode($jsonData, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    $errorMsg = 'Invalid JSON data: ' . json_last_error_msg();
    $logger->error($errorMsg);
    sendResponse(false, $errorMsg);
    exit;
}

if (!$timetableData || !is_array($timetableData)) {
    sendResponse(false, 'Invalid or empty timetable data');
    exit;
}

// Validate required fields
if (empty($timetableData['session']) || 
    empty($timetableData['semester']) || 
    empty($timetableData['course']) || 
    empty($timetableData['room'])) { 
    // Only the basic timetable info is required (not the entries)
    sendResponse(false, 'Missing required timetable data: session, semester, course, or room');
    exit;
}

// Ensure entries array exists, even if empty
if (!isset($timetableData['entries']) || !is_array($timetableData['entries'])) {
    $timetableData['entries'] = [];
}

try {
    // Initialize DAO
    $dao = TimetableDAO::getInstance();
    
    // Process timetable entries
    $savedEntries = saveTimetableEntries($dao, $timetableData);
    
    // Return success response
    sendResponse(true, 'Timetable saved successfully', [
        'savedEntries' => $savedEntries,
        'details' => [
            'sessionId' => $timetableData['session'],
            'semesterId' => $timetableData['semester'],
            'courseId' => $timetableData['course'],
            'roomId' => $timetableData['room'],
            'savedCount' => $savedEntries['saved'],
            'skippedCount' => $savedEntries['skipped'],
            'totalCount' => $savedEntries['total']
        ]
    ]);
    
} catch (Exception $e) {
    // Log error
    $logger->error('Error saving timetable: ' . $e->getMessage());
    
    // Return error response
    sendResponse(false, 'Error saving timetable: ' . $e->getMessage(), [
        'errorDetails' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    // Clean any output that might have been generated
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    // Ensure the message is properly escaped for JSON
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    // Set proper JSON encoding options to avoid character encoding issues
    $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    
    // Handle JSON encoding errors
    $jsonResponse = json_encode($response, $jsonOptions);
    if ($jsonResponse === false) {
        // Log JSON error
        error_log("JSON encoding error: " . json_last_error_msg());
        // Send a simpler response that won't have encoding issues
        echo '{"success":false,"message":"Error encoding response to JSON"}';
    } else {
        echo $jsonResponse;
    }
    
    // End script execution to ensure no additional output
    exit;
}

/**
 * Process and save timetable entries
 */
function saveTimetableEntries($dao, $data) {
    global $logger;
    
    $session = $data['session'];
    $semester = $data['semester'];
    $course = $data['course'];
    $room = $data['room'];
    $entries = $data['entries'];
    
    // Get the database connection
    $db = Database::getInstance()->getConnection();
    
    // Check if database connection is valid
    if ($db === null || $db->connect_error) {
        throw new Exception("Database connection error: " . 
            ($db ? $db->connect_error : "Could not establish database connection"));
    }
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Check if it's a new batch year (in YYYY-YYYY format)
        if (preg_match('/^\d{4}-\d{4}$/', $session) && !isExistingBatchYear($db, $session)) {
            $logger->log("Adding new batch year: $session");
            // Add the new batch year
            if (!$dao->addBatchYear($session)) {
                throw new Exception("Failed to add new batch year: $session");
            }
        }
        
        // Get IDs for session, semester, course, and room
        $sessionId = getOrCreateId($db, 'Batch_Year', 'Batch_ID', 'BatchYear', $session);
        $semesterId = getOrCreateId($db, 'semesters', 'semester_id', 'semester_no', $semester);
        $courseId = getOrCreateId($db, 'courses', 'course_id', 'course_name', $course);
        $roomId = getOrCreateId($db, 'rooms', 'room_id', 'room_number', $room);
        
        // Process each entry
        $savedCount = 0;
        $skippedCount = 0;
        
        foreach ($entries as $cellId => $entry) {
            try {
                // Skip entries that don't have all required fields
                if (empty($entry['day']) || empty($entry['timeSlot']) || 
                    empty($entry['subject']) || empty($entry['subjectCode']) || 
                    empty($entry['faculty'])) {
                    $logger->log("Skipping incomplete entry for cell: $cellId");
                    $skippedCount++;
                    continue;
                }
                
                // Extract entry data
                $day = $entry['day'];
                $timeSlot = $entry['timeSlot'];
                $subject = $entry['subject'];
                $subjectCode = $entry['subjectCode'];
                $faculty = $entry['faculty'];
                
                // Parse time slot to get start and end times
                list($startTime, $endTime) = parseTimeSlot($timeSlot);
                
                // First check if faculty exists, if not add them
                $facultyId = getFacultyId($db, $faculty);
                
                // Then check if subject exists with that faculty
                $subjectId = getOrUpdateSubject($db, $subject, $subjectCode, $facultyId);
                
                // Delete any existing entries for this day, time slot, semester, and course
                $stmt = $db->prepare("DELETE FROM timetable 
                                   WHERE day_of_week = ? 
                                   AND start_time = ? 
                                   AND end_time = ? 
                                   AND semester_id = ? 
                                   AND course_id = ? 
                                   AND room_id = ?
                                   AND Batch_ID = ?");
                
                $stmt->bind_param('sssiiii', $day, $startTime, $endTime, $semesterId, $courseId, $roomId, $sessionId);
                $stmt->execute();
                $stmt->close();
                
                // Insert new entry
                $stmt = $db->prepare("INSERT INTO timetable 
                                   (day_of_week, start_time, end_time, semester_id, course_id, 
                                    room_id, subject_id, Batch_ID) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param('sssiiiii', $day, $startTime, $endTime, $semesterId, $courseId, 
                                         $roomId, $subjectId, $sessionId);
                
                if ($stmt->execute()) {
                    $savedCount++;
                } else {
                    throw new Exception("Failed to save entry: " . $stmt->error);
                }
                
                $stmt->close();
            } catch (Exception $e) {
                // Log the error for this specific entry but continue with others
                $logger->error("Error processing entry for cell $cellId: " . $e->getMessage());
                continue;
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $logger->log("Successfully saved $savedCount timetable entries, skipped $skippedCount entries");
        return [
            'saved' => $savedCount,
            'skipped' => $skippedCount,
            'total' => count($entries)
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
}

/**
 * Check if a batch year exists in the database
 */
function isExistingBatchYear($db, $batchYear) {
    $sql = "SELECT COUNT(*) as count FROM Batch_Year WHERE BatchYear = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $batchYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Get faculty ID, create if not exists
 */
function getFacultyId($db, $facultyName) {
    // Check if faculty exists
    $stmt = $db->prepare("SELECT faculty_id FROM faculty WHERE faculty_name = ?");
    $stmt->bind_param('s', $facultyName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $facultyId = $row['faculty_id'];
        $stmt->close();
        return $facultyId;
    }
    
    $stmt->close();
    
    // Create new faculty
    $stmt = $db->prepare("INSERT INTO faculty (faculty_name) VALUES (?)");
    $stmt->bind_param('s', $facultyName);
    $stmt->execute();
    $facultyId = $db->insert_id;
    $stmt->close();
    
    return $facultyId;
}

/**
 * Get or update subject with faculty
 */
function getOrUpdateSubject($db, $subjectName, $subjectCode, $facultyId) {
    // Check if subject exists with this code
    $stmt = $db->prepare("SELECT subject_id, faculty_id FROM subjects WHERE subject_code = ?");
    $stmt->bind_param('s', $subjectCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $subjectId = $row['subject_id'];
        $currentFacultyId = $row['faculty_id'];
        $stmt->close();
        
        // Update faculty if changed
        if ($currentFacultyId != $facultyId) {
            $stmt = $db->prepare("UPDATE subjects SET faculty_id = ? WHERE subject_id = ?");
            $stmt->bind_param('ii', $facultyId, $subjectId);
            $stmt->execute();
            $stmt->close();
        }
        
        return $subjectId;
    }
    
    $stmt->close();
    
    // Create new subject
    $stmt = $db->prepare("INSERT INTO subjects (subject_name, subject_code, faculty_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $subjectName, $subjectCode, $facultyId);
    $stmt->execute();
    $subjectId = $db->insert_id;
    $stmt->close();
    
    return $subjectId;
}

/**
 * Get ID from table by value, or create if it doesn't exist
 */
function getOrCreateId($db, $table, $idColumn, $valueColumn, $value) {
    // Check if value exists
    $stmt = $db->prepare("SELECT $idColumn FROM $table WHERE $valueColumn = ?");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id = $row[$idColumn];
        $stmt->close();
        return $id;
    }
    
    $stmt->close();
    
    // Insert new value
    $stmt = $db->prepare("INSERT INTO $table ($valueColumn) VALUES (?)");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();
    
    return $id;
}

/**
 * Parse time slot string into start and end times
 */
function parseTimeSlot($timeSlot) {
    $parts = explode('-', $timeSlot);
    
    if (count($parts) !== 2) {
        // Default to 1 hour slot if format is invalid
        return ['00:00:00', '01:00:00'];
    }
    
    $startTime = formatTime(trim($parts[0]));
    $endTime = formatTime(trim($parts[1]));
    
    return [$startTime, $endTime];
}

/**
 * Format time string to MySQL time format
 */
function formatTime($time) {
    // Remove any non-numeric or colon characters first to clean the input
    $time = preg_replace('/[^0-9:]/', '', $time);
    
    // Split into hours and minutes
    $parts = explode(':', $time);
    
    if (count($parts) === 1) {
        // If only hours provided, add :00 for minutes
        $time = $parts[0] . ':00';
    }
    
    // Ensure proper format with seconds
    if (count(explode(':', $time)) === 2) {
        $time .= ':00';
    }
    
    // Make sure hours are valid (0-23)
    $parts = explode(':', $time);
    if (isset($parts[0]) && is_numeric($parts[0])) {
        $hours = (int)$parts[0];
        if ($hours > 23) {
            $hours = $hours % 24;
        }
        $parts[0] = sprintf('%02d', $hours);
    } else {
        $parts[0] = '00';
    }
    
    // Make sure minutes are valid (0-59)
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $minutes = (int)$parts[1];
        if ($minutes > 59) {
            $minutes = $minutes % 60;
        }
        $parts[1] = sprintf('%02d', $minutes);
    } else {
        $parts[1] = '00';
    }
    
    // Make sure seconds are valid (0-59)
    if (isset($parts[2]) && is_numeric($parts[2])) {
        $seconds = (int)$parts[2];
        if ($seconds > 59) {
            $seconds = $seconds % 60;
        }
        $parts[2] = sprintf('%02d', $seconds);
    } else {
        $parts[2] = '00';
    }
    
    return implode(':', $parts);
}
?> 