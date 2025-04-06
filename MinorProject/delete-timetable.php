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
$logger = new Logger('DeleteTimetable');

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

// Validate required fields
if (empty($timetableData['semester']) || empty($timetableData['course'])) {
    sendResponse(false, 'Missing required timetable data: semester or course');
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
    
    // Get IDs for semester and course
    $semesterId = getSemesterId($db, $semester);
    $courseId = getCourseId($db, $course);
    
    if (!$semesterId || !$courseId) {
        throw new Exception("Could not find semester or course");
    }
    
    // Get session ID if provided
    $sessionId = null;
    if (!empty($session)) {
        $sessionId = getSessionId($db, $session);
    }
    
    // Prepare delete query
    $sql = "DELETE FROM timetable WHERE semester_id = ? AND course_id = ?";
    $params = [$semesterId, $courseId];
    $types = "ii";
    
    if ($sessionId) {
        $sql .= " AND Batch_ID = ?";
        $params[] = $sessionId;
        $types .= "i";
    }
    
    // Execute delete query
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete timetable entries: " . $stmt->error);
    }
    
    $deletedCount = $stmt->affected_rows;
    $stmt->close();
    
    // Commit transaction
    $db->commit();
    
    $logger->log("Successfully deleted timetable: $deletedCount entries removed");
    sendResponse(true, 'Timetable deleted successfully', [
        'deleted' => $deletedCount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->connect_errno === 0) {
        $db->rollback();
    }
    
    $logger->error('Error deleting timetable: ' . $e->getMessage());
    sendResponse(false, 'Error deleting timetable: ' . $e->getMessage());
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
 * Get semester ID from name
 */
function getSemesterId($db, $semesterName) {
    $stmt = $db->prepare("SELECT semester_id FROM semesters WHERE semester_no = ?");
    $stmt->bind_param("s", $semesterName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id = $row['semester_id'];
        $stmt->close();
        return $id;
    }
    
    $stmt->close();
    return null;
}

/**
 * Get course ID from name
 */
function getCourseId($db, $courseName) {
    $stmt = $db->prepare("SELECT course_id FROM courses WHERE course_name = ?");
    $stmt->bind_param("s", $courseName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id = $row['course_id'];
        $stmt->close();
        return $id;
    }
    
    $stmt->close();
    return null;
}

/**
 * Get session ID from name
 */
function getSessionId($db, $sessionName) {
    $stmt = $db->prepare("SELECT Batch_ID FROM Batch_Year WHERE BatchYear = ?");
    $stmt->bind_param("s", $sessionName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id = $row['Batch_ID'];
        $stmt->close();
        return $id;
    }
    
    $stmt->close();
    return null;
}
?> 