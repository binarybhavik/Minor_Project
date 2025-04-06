<?php
// We don't need to include these files if they're already loaded by the proxy
// require_once __DIR__ . '/../dao/TimetableDAO.php';
// require_once __DIR__ . '/../utils/SessionUtils.php';

require_once __DIR__ . '/../dao/TimetableDAO.php';
require_once __DIR__ . '/../utils/SessionUtils.php';
require_once __DIR__ . '/../utils/Logger.php';

// TimetableController class definition
class TimetableController {
    private $timetableDAO;
    private $logger;

    public function __construct() {
        try {
            $this->timetableDAO = TimetableDAO::getInstance();
            $this->logger = new Logger('TimetableController');
        } catch (Exception $e) {
            throw new Exception("Failed to initialize TimetableController: " . $e->getMessage());
        }
    }

    public function handleRequest() {
        try {
            // Get filter parameters
            $filters = [
                'semester' => $_GET['semester'] ?? '',
                'course' => $_GET['course'] ?? '',
                'day' => $_GET['day'] ?? '',
                'faculty' => $_GET['faculty'] ?? '',
                'subject' => $_GET['subject'] ?? '',
                'time' => $_GET['time'] ?? '',
                'room' => $_GET['room'] ?? ''
            ];

            // Validate required fields
            if (empty($filters['semester']) || empty($filters['course'])) {
                $this->sendJsonResponse(false, 'Semester and Course are required fields', []);
                return;
            }

            // Get timetable entries
            $entries = $this->timetableDAO->getTimetableEntries($filters);

            // Convert entries to array format
            $timetableData = array_map(function($entry) {
                return [
                    'id' => $entry->getId(),
                    'semester' => $entry->getSemester(),
                    'course' => $entry->getCourse(),
                    'day' => $entry->getDay(),
                    'timeStart' => $entry->getTimeStart(),
                    'timeEnd' => $entry->getTimeEnd(),
                    'subjectCode' => $entry->getSubjectCode(),
                    'subjectName' => $entry->getSubjectName(),
                    'facultyName' => $entry->getFacultyName(),
                    'roomNumber' => $entry->getRoomNumber()
                ];
            }, $entries);

            $this->sendJsonResponse(true, '', $timetableData);

        } catch (Exception $e) {
            $this->logger->error("Error in TimetableController: " . $e->getMessage());
            $this->sendJsonResponse(false, 'An error occurred while fetching the timetable', []);
        }
    }

    private function sendJsonResponse($success, $message, $data) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'timetable' => $data
        ]);
    }

    public function getDistinctValues($column) {
        try {
            return $this->timetableDAO->getDistinctValues($column);
        } catch (Exception $e) {
            $this->logger->error("Error getting distinct values: " . $e->getMessage());
            return [];
        }
    }
}
?> 