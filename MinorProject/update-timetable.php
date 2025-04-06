<?php
require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/dao/TimetableDAO.php';
require_once __DIR__ . '/utils/Logger.php';

// Start output buffering to prevent any PHP notices from corrupting JSON output
ob_start();

// Enable error reporting for logging but not for display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start the session if not started
SessionUtils::startSessionIfNeeded();

// Enforce admin role access
SessionUtils::requireAdmin();

// Initialize logger
$logger = new Logger('UpdateTimetable');

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
if (empty($timetableData['semester']) || 
    empty($timetableData['course']) || 
    empty($timetableData['entries'])) {
    sendResponse(false, 'Missing required timetable data: semester, course, or entries');
    exit;
}

try {
    // Initialize DAO
    $dao = TimetableDAO::getInstance();
    
    // Get the database connection
    $db = Database::getInstance()->getConnection();
    
    // Start transaction
    $db->begin_transaction();
    
    $semester = $timetableData['semester'];
    $course = $timetableData['course'];
    $session = $timetableData['session'] ?? '';
    $entries = $timetableData['entries'];
    
    // Get IDs for session, semester, and course
    $semesterId = getOrCreateId($db, 'semesters', 'semester_id', 'semester_no', $semester);
    $courseId = getOrCreateId($db, 'courses', 'course_id', 'course_name', $course);
    
    // Get session ID or create if it's a new batch year
    $sessionId = null;
    if (!empty($session)) {
        if (preg_match('/^\d{4}-\d{4}$/', $session) && !isExistingBatchYear($db, $session)) {
            // Add the new batch year
            $logger->log("Adding new batch year: $session");
            if (!$dao->addBatchYear($session)) {
                throw new Exception("Failed to add new batch year: $session");
            }
        }
        $sessionId = getOrCreateId($db, 'Batch_Year', 'Batch_ID', 'BatchYear', $session);
    }
    
    // Process each entry
    $savedCount = 0;
    $skippedCount = 0;
    
    // First, delete all existing entries for this semester and course
    $deleteStmt = $db->prepare("DELETE FROM timetable 
                             WHERE semester_id = ? 
                             AND course_id = ?
                             " . (!empty($sessionId) ? "AND Batch_ID = ?" : ""));
    
    if (!empty($sessionId)) {
        $deleteStmt->bind_param('iii', $semesterId, $courseId, $sessionId);
    } else {
        $deleteStmt->bind_param('ii', $semesterId, $courseId);
    }
    
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Now insert all the entries
    foreach ($entries as $entry) {
        try {
            // Skip entries without required data
            if (empty($entry['day']) || empty($entry['timeSlot']) || 
                empty($entry['subject']) || empty($entry['subjectCode']) || 
                empty($entry['faculty']) || empty($entry['room'])) {
                $skippedCount++;
                continue;
            }
            
            // Extract entry data
            $day = $entry['day'];
            $timeSlot = $entry['timeSlot'];
            $subject = $entry['subject'];
            $subjectCode = $entry['subjectCode'];
            $faculty = $entry['faculty'];
            $room = $entry['room'];
            
            // Parse time slot if not already parsed
            if (!isset($entry['startTime']) || !isset($entry['endTime'])) {
                list($startTime, $endTime) = parseTimeSlot($timeSlot);
            } else {
                $startTime = $entry['startTime'];
                $endTime = $entry['endTime'];
            }
            
            // Get the room ID
            $roomId = getOrCreateId($db, 'rooms', 'room_id', 'room_number', $room);
            
            // First check if faculty exists, if not add them
            $facultyId = getFacultyId($db, $faculty);
            
            // Then check if subject exists with that faculty
            $subjectId = getOrUpdateSubject($db, $subject, $subjectCode, $facultyId);
            
            // Insert the entry
            $stmt = $db->prepare("INSERT INTO timetable 
                               (day_of_week, start_time, end_time, semester_id, course_id, 
                                room_id, subject_id, Batch_ID) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param('sssiiiii', $day, $startTime, $endTime, $semesterId, $courseId, 
                                    $roomId, $subjectId, $sessionId);
            
            if ($stmt->execute()) {
                $savedCount++;
            } else {
                $logger->error("Failed to save entry: " . $stmt->error);
                $skippedCount++;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $logger->error("Error processing entry: " . $e->getMessage());
            $skippedCount++;
        }
    }
    
    // Commit transaction
    $db->commit();
    
    $logger->log("Successfully updated timetable: $savedCount entries saved, $skippedCount entries skipped");
    sendResponse(true, 'Timetable updated successfully', [
        'saved' => $savedCount,
        'skipped' => $skippedCount,
        'total' => count($entries)
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->connect_errno === 0) {
        $db->rollback();
    }
    
    $logger->error('Error updating timetable: ' . $e->getMessage());
    sendResponse(false, 'Error updating timetable: ' . $e->getMessage());
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    // Clean any output that might have been generated
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response);
    exit;
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
    
    // Create new entry
    $stmt = $db->prepare("INSERT INTO $table ($valueColumn) VALUES (?)");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();
    
    return $id;
}

/**
 * Parse time slot string to get start and end times
 */
function parseTimeSlot($timeSlot) {
    $parts = explode(' - ', $timeSlot);
    return [$parts[0], $parts[1]];
}
?> 