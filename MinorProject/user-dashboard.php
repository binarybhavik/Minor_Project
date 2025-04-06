<?php
// Enable full error reporting to diagnose blank screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/dao/TimetableDAO.php';
require_once __DIR__ . '/utils/Logger.php';

// Initialize logger
$logger = new Logger();

// Start session
SessionUtils::startSessionIfNeeded();

// Check if user is logged in
if (!SessionUtils::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user data
$userData = SessionUtils::getUserData();
$userName = $userData['name'];
$userType = $userData['role'];

// Initialize DAO
$dao = TimetableDAO::getInstance();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'logout':
            SessionUtils::clearSession();
            header("Location: login.php");
            exit();
            break;
        default:
            // No action
            break;
    }
}

// Initialize variables
$semesters = [];
$courses = [];
$rooms = [];
$sessions = [];

try {
    // Create sessions table if it doesn't exist yet
    $dao->createSessionsTableIfNotExists();
    
    // Get all necessary dropdown values
    $semesters = $dao->getDistinctValues('semesters', 'semester_no');
    $courses = $dao->getDistinctValues('courses', 'course_name');
    $rooms = $dao->getDistinctValues('rooms', 'room_number');
    
    // Get sessions for the dropdown
    $sessions = $dao->getSessions();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage(); // Display error on screen
    error_log("Error loading dropdown values: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Global Styles */
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --light-color: #f5f6fa;
            --danger-color: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #718096;
            --shadow: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: var(--light-color);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            background: var(--secondary-color);
            padding: 1rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar h1 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .navbar h1 i {
            margin-right: 0.5rem;
        }

        .navbar-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        /* Session selector in navbar */
        .session-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            margin-right: 0.5rem;
        }

        .session-selector label {
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .session-selector select {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .session-selector select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
        }

        .session-selector select option {
            background-color: var(--secondary-color);
            color: white;
        }

        .session-badge {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
            font-weight: 500;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .card-header {
            background: var(--secondary-color);
            color: white;
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header i {
            margin-right: 0.75rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Forms */
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #f9fafc;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
        }

        .btn-group {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            justify-content: center;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        /* Timetable */
        .timetable-container {
            background: white;
            border-radius: 10px;
            padding: 0;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .day-section {
            margin-bottom: 2rem;
        }

        .day-section:last-child {
            margin-bottom: 0;
        }

        .day-section h3 {
            background: var(--secondary-color);
            color: white;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .day-section h3 i {
            margin-right: 0.75rem;
        }

        .timetable-table {
            width: 100%;
            border-collapse: collapse;
        }

        .timetable-table th,
        .timetable-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }

        .timetable-table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-dark);
            white-space: nowrap;
        }

        .timetable-table tr:last-child td {
            border-bottom: none;
        }

        /* Messages */
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .error-message i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2.5rem;
        }

        .loading i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .no-data {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-light);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Session info in timetable header */
        .timetable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
        }

        .timetable-header-info {
            display: flex;
            align-items: center;
        }

        .timetable-header-info i {
            margin-right: 0.5rem;
        }

        .session-info {
            margin-left: auto;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .session-info i {
            margin-right: 0.5rem;
        }

        /* Download buttons */
        .download-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-left: auto;
        }

        .download-btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            color: var(--text-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .download-btn:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        .download-btn i {
            margin-right: 0.4rem;
            font-size: 0.9rem;
        }

        /* Print-specific styles */
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-timetable, #printable-timetable * {
                visibility: visible;
            }
            #printable-timetable {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }

        /* Formatted timetable for export */
        .export-timetable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .export-timetable th, 
        .export-timetable td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        
        .export-timetable th {
            background-color: #f8fafc;
            font-weight: 600;
        }
        
        .export-timetable tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .export-timetable .subject-code {
            font-weight: bold;
        }
        
        .export-timetable .subject-name {
            display: block;
        }
        
        .export-keys {
            margin-top: 2rem;
            width: 100%;
        }
        
        .export-keys caption {
            text-align: left;
            font-weight: bold;
            padding: 0.5rem 0;
        }

        /* Timetable info summary */
        .timetable-info-summary {
            background-color: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .timetable-info-summary .info-item {
            padding: 5px 10px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        
        .timetable-info-summary .info-item strong {
            color: var(--secondary-color);
        }
        
        /* Subject details styling */
        .subject-details {
            display: flex;
            flex-direction: column;
        }
        
        .subject-name {
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .subject-code {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 3px;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .timetable-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .download-options {
                margin-left: 0;
                margin-top: 0.5rem;
                width: 100%;
                justify-content: flex-end;
            }
            
            .simple-timetable .timetable-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .navbar-actions {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            
            .session-selector {
                margin-right: 0;
                width: 100%;
            }
            
            .session-selector select {
                flex-grow: 1;
            }

            .navbar h1 {
                font-size: 1.25rem;
            }

            .container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }

            .search-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .timetable-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                min-width: 100%;
                width: max-content;
            }
            
            .timetable-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .session-info {
                margin-left: 0;
                margin-top: 0.5rem;
            }
            
            .download-options {
                width: 100%;
                margin-top: 0.75rem;
            }
            
            .timetable-info-summary {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .timetable-info-summary .info-item {
                width: 100%;
            }
            
            .day-section h3 {
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }
        }

        @media (max-width: 480px) {
            .navbar-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .session-selector {
                width: 100%;
                margin-bottom: 0.5rem;
                justify-content: space-between;
            }
            
            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .navbar-actions .btn {
                flex: 1;
                min-width: 48%;
                text-align: center;
                padding: 0.6rem 0.5rem;
            }

            .card-header {
                padding: 1rem;
                font-size: 1.1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .timetable-table th,
            .timetable-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
            
            .download-options {
                justify-content: center;
            }
            
            .download-btn {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            
            .subject-details {
                font-size: 0.85rem;
            }
            
            .timetable-info-summary {
                padding: 0.75rem;
            }
            
            .timetable-info-summary .info-item {
                font-size: 0.8rem;
                padding: 0.4rem 0.6rem;
            }
        }
        
        /* Mobile-specific table styles */
        @media (max-width: 767px) {
            /* Add horizontal scrolling for all tables on mobile */
            .timetable-table {
                width: max-content;
                min-width: 100%;
            }
            
            /* Make sure table headers stay compact */
            .timetable-table th {
                white-space: nowrap;
                padding: 0.6rem 0.5rem;
                font-size: 0.85rem;
            }
            
            /* Compact table cells on mobile */
            .timetable-table td {
                white-space: normal;
                word-break: break-word;
                padding: 0.6rem 0.5rem;
                font-size: 0.85rem;
            }
            
            /* Ensure container has scrolling */
            .day-section {
                overflow-x: auto;
            }
            
            /* Simple table for fallback view */
            .simple-timetable {
                overflow-x: auto;
            }
        }

        /* Table column styling */
        .timetable-table .time-column {
            width: 15%;
            min-width: 80px;
        }
        
        .timetable-table .day-column {
            width: 12%;
            min-width: 80px;
        }
        
        .timetable-table .subject-column {
            width: 25%;
            min-width: 150px;
        }
        
        .timetable-table .faculty-column {
            width: 20%;
            min-width: 120px;
        }
        
        .timetable-table .room-column {
            width: 10%;
            min-width: 60px;
        }
        
        .timetable-table .semester-column {
            width: 10%;
            min-width: 70px;
        }
        
        .timetable-table .course-column {
            width: 15%;
            min-width: 100px;
        }

        /* Make search submit button full width on all screens */
        .btn-search-submit {
            width: 100%;
            justify-content: center;
        }
        
        /* Prevent horizontal overflow in container */
        .timetable-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Ensure tables can scroll horizontally within their containers */
        .day-section {
            overflow-x: auto;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1><i class="fas fa-calendar-alt"></i> Timetable Management</h1>
        <div class="navbar-actions">
            <div class="session-selector">
                <label for="navSession">Session:</label>
                <select name="session" id="navSession" onchange="updateSession(this.value)">
                    <option value="">Select Session</option>
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?php echo htmlspecialchars($session['BatchYear'] ?? ''); ?>">
                            <?php echo htmlspecialchars($session['BatchYear'] ?? ''); ?>
                            <?php if (($session['BatchYear'] ?? '') === '2024-2025'): ?>
                                <span class="session-badge">Current</span>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('searchForm').reset()"><i class="fas fa-undo"></i> Reset</button>
            <a href="?action=logout" class="btn btn-primary"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-search"></i> Search Timetable</span>
            </div>
            <div class="card-body">
                <form id="searchForm" class="search-form">
                    <input type="hidden" name="session" id="hiddenSession" value="">
                
                <div class="form-group">
                    <label for="semester">Semester:</label>
                        <select name="semester" id="semester">
                        <option value="">Select Semester</option>
                            <?php foreach ($semesters as $semester): ?>
                                <?php 
                                // Skip any semesters containing "odd" or "even" in their name
                                if (stripos($semester, 'odd') !== false || stripos($semester, 'even') !== false) {
                                    continue;
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($semester); ?>">
                                    Semester <?php echo htmlspecialchars($semester); ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="course">Course:</label>
                        <select name="course" id="course">
                        <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>">
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="day">Day:</label>
                        <select name="day" id="day">
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
                
                <div class="form-group">
                        <label for="room">Room:</label>
                        <select name="room" id="room">
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room); ?>">
                                    <?php echo htmlspecialchars($room); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                </div>
                
                <div class="form-group">
                        <label for="faculty">Faculty Name:</label>
                        <input type="text" name="faculty" id="faculty" placeholder="Enter faculty name">
                </div>
                
                <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" name="subject" id="subject" placeholder="Enter subject name">
                </div>
                
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary btn-search-submit">
                            <i class="fas fa-search"></i> Search Timetable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading timetable...</p>
        </div>

        <div id="timetableContainer" class="timetable-container"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/docx/7.8.2/docx.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const loadingElement = document.getElementById('loading');
            const timetableContainer = document.getElementById('timetableContainer');

            // Function to update hidden session input
            window.updateSession = function(value) {
                document.getElementById('hiddenSession').value = value;
            };

            // Function to format time in a more readable format
            function formatTime(time) {
                if (!time) return '';
                
                // Check if time is in HH:MM:SS format and convert to HH:MM
                if (time.split(':').length === 3) {
                    time = time.split(':').slice(0, 2).join(':');
                }
                
                // Parse time and format with AM/PM
                const [hours, minutes] = time.split(':');
                const h = parseInt(hours);
                const period = h >= 12 ? 'PM' : 'AM';
                const formattedHour = h % 12 === 0 ? 12 : h % 12;
                
                return `${formattedHour}:${minutes} ${period}`;
            }

            // Add reset event listener to clear timetable
            document.querySelector('.btn-danger').addEventListener('click', function() {
                timetableContainer.innerHTML = '';
                // Clear any hidden export tables
                const existingExport = document.getElementById('export-timetable-container');
                if (existingExport) {
                    existingExport.remove();
                }
            });

            searchForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Check if session is selected
                const sessionValue = document.getElementById('hiddenSession').value;
                if (!sessionValue) {
                    timetableContainer.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> 
                            Please select a batch year from the dropdown at the top first
                        </div>`;
                    return;
                }
                
                // Show loading
                loadingElement.style.display = 'block';
                timetableContainer.innerHTML = '';
                
                try {
                    const formData = new FormData(this);
                    const searchParams = new URLSearchParams();
                    
                    for (const pair of formData.entries()) {
                        if (pair[1]) { // Only add non-empty values
                            searchParams.append(pair[0], pair[1]);
                        }
                    }
                    
                    // Log the search parameters
                    console.log('Search parameters:', Object.fromEntries(searchParams));
                    
                    const response = await fetch('get_timetable.php?' + searchParams.toString());
                    
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    
                    const data = await response.json();
                    
                    // Log data structure for debugging
                    console.log('Data received:', data);
                    
                    if (data.error) {
                        timetableContainer.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${data.error}</div>`;
                        return;
                    }
                    
                    if (!data.length) {
                        timetableContainer.innerHTML = `
                            <div class="no-data">
                                <i class="fas fa-calendar-times"></i>
                                <h3>No Timetable Found</h3>
                                <p>No timetable entries match your search criteria. Try different parameters.</p>
                            </div>
                        `;
                        return;
                    }
                    
                    // Group by days
                    const groupedByDay = {};
                    const weekdayOrder = {
                        'Monday': 1,
                        'Tuesday': 2,
                        'Wednesday': 3,
                        'Thursday': 4,
                        'Friday': 5,
                        'Saturday': 6,
                        'Sunday': 7
                    };
                    
                    // Populate the groupedByDay object with the data
                    data.forEach(entry => {
                        console.log('Processing entry:', entry);
                        if (!entry.day) {
                            console.error('Entry missing day:', entry);
                            return; // Skip entries without day
                        }
                        
                        if (!groupedByDay[entry.day]) {
                            groupedByDay[entry.day] = [];
                        }
                        groupedByDay[entry.day].push(entry);
                    });
                    
                    // Log the groupedByDay structure
                    console.log('Grouped data by day:', groupedByDay);
                    
                    // Sort each day's entries by time
                    for (const day in groupedByDay) {
                        groupedByDay[day].sort((a, b) => {
                            const timeA = a.timeStart.split(':').map(Number);
                            const timeB = b.timeStart.split(':').map(Number);
                            
                            if (timeA[0] !== timeB[0]) {
                                return timeA[0] - timeB[0];
                            }
                            return timeA[1] - timeB[1];
                        });
                    }
                    
                    // Get session information
                    const session = data[0].session || 'N/A';
                    
                    // Create timetable header with session info
                    let html = `
                        <div class="timetable-header">
                            <div class="timetable-header-info">
                                <i class="fas fa-table"></i>
                                <span>Timetable</span>
                            </div>
                            <div class="session-info">
                                <i class="fas fa-calendar-week"></i>
                                <span>Session: ${session}</span>
                            </div>
                            <div class="download-options">
                                <button class="download-btn" onclick="downloadPDF()">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="download-btn" onclick="downloadWord()">
                                    <i class="fas fa-file-word"></i> Word
                                </button>
                                <button class="download-btn" onclick="downloadCSV()">
                                    <i class="fas fa-file-csv"></i> CSV
                                </button>
                            </div>
                        </div>
                        <div id="printable-timetable">
                            <div class="timetable-info-summary">
                                <div class="info-item"><strong>Session:</strong> ${session}</div>
                                <div class="info-item"><strong>Total Classes:</strong> ${data.length}</div>
                            </div>
                        </div>
                    `;
                    
                    // Get days in order
                    const orderedDays = Object.keys(groupedByDay).sort((a, b) => {
                        return weekdayOrder[a] - weekdayOrder[b];
                    });
                    
                    // Check if there are any days to display
                    if (orderedDays.length === 0) {
                        html += `
                            <div class="no-data" style="padding: 2rem; text-align: center;">
                                <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: #f39c12; margin-bottom: 1rem;"></i>
                                <h3>No day information available</h3>
                                <p>The timetable data doesn't contain valid day information.</p>
                            </div>
                        `;
                    } else {
                        // Continue with day-by-day display
                        for (const day of orderedDays) {
                            if (!groupedByDay[day] || !Array.isArray(groupedByDay[day])) {
                                continue; // Skip if day data is invalid
                            }
                            
                            let dayIcon;
                            switch(day) {
                                case 'Monday': dayIcon = 'calendar-day'; break;
                                case 'Tuesday': dayIcon = 'calendar-day'; break;
                                case 'Wednesday': dayIcon = 'calendar-day'; break;
                                case 'Thursday': dayIcon = 'calendar-day'; break;
                                case 'Friday': dayIcon = 'calendar-day'; break;
                                case 'Saturday': dayIcon = 'calendar-day'; break;
                                case 'Sunday': dayIcon = 'calendar-day'; break;
                                default: dayIcon = 'calendar-day';
                            }
                            
                            html += `
                                <div class="day-section">
                                    <h3><i class="fas fa-${dayIcon}"></i> ${day}</h3>
                                    <table class="timetable-table">
                                        <thead>
                                            <tr>
                                                <th class="time-column"><i class="fas fa-clock"></i> Time</th>
                                                <th class="subject-column"><i class="fas fa-book"></i> Subject</th>
                                                <th class="faculty-column"><i class="fas fa-user"></i> Faculty</th>
                                                <th class="room-column"><i class="fas fa-door-open"></i> Room</th>
                                                <th class="semester-column"><i class="fas fa-graduation-cap"></i> Semester</th>
                                                <th class="course-column"><i class="fas fa-graduation-cap"></i> Course</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            
                            groupedByDay[day].forEach(entry => {
                                html += `
                                    <tr>
                                        <td>${formatTime(entry.timeStart)} - ${formatTime(entry.timeEnd)}</td>
                                        <td>
                                            <div class="subject-details">
                                                <span class="subject-name">${entry.subjectName}</span>
                                            </div>
                                        </td>
                                        <td>${entry.facultyName}</td>
                                        <td>${entry.roomNumber}</td>
                                        <td>${entry.semester}</td>
                                        <td>${entry.course}</td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }
                    }
                    
                    // If no days to display but we have data, add a fallback table
                    if (orderedDays.length === 0 && data.length > 0) {
                        html += `
                            <div class="simple-timetable">
                                <table class="timetable-table">
                                    <thead>
                                        <tr>
                                            <th class="time-column"><i class="fas fa-clock"></i> Time</th>
                                            <th class="day-column"><i class="fas fa-calendar"></i> Day</th>
                                            <th class="subject-column"><i class="fas fa-book"></i> Subject</th>
                                            <th class="faculty-column"><i class="fas fa-user"></i> Faculty</th>
                                            <th class="room-column"><i class="fas fa-door-open"></i> Room</th>
                                            <th class="semester-column"><i class="fas fa-graduation-cap"></i> Semester</th>
                                            <th class="course-column"><i class="fas fa-graduation-cap"></i> Course</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        // Sort by day and time
                        const sortedData = [...data].sort((a, b) => {
                            // First sort by day if available
                            if (a.day && b.day && weekdayOrder[a.day] !== weekdayOrder[b.day]) {
                                return weekdayOrder[a.day] - weekdayOrder[b.day];
                            }
                            
                            // Then by time if available
                            if (a.timeStart && b.timeStart) {
                                const timeA = a.timeStart.split(':').map(Number);
                                const timeB = b.timeStart.split(':').map(Number);
                                
                                if (timeA[0] !== timeB[0]) {
                                    return timeA[0] - timeB[0];
                                }
                                return timeA[1] - timeB[1];
                            }
                            
                            return 0;
                        });
                        
                        sortedData.forEach(entry => {
                            html += `
                                <tr>
                                    <td>${entry.timeStart && entry.timeEnd ? formatTime(entry.timeStart) + ' - ' + formatTime(entry.timeEnd) : 'N/A'}</td>
                                    <td>${entry.day || 'N/A'}</td>
                                    <td>
                                        <div class="subject-details">
                                            <span class="subject-name">${entry.subjectName || 'N/A'}</span>
                                        </div>
                                    </td>
                                    <td>${entry.facultyName || 'N/A'}</td>
                                    <td>${entry.roomNumber || 'N/A'}</td>
                                    <td>${entry.semester || 'N/A'}</td>
                                    <td>${entry.course || 'N/A'}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                    
                    timetableContainer.innerHTML = html;
                    
                    // Create the formatted version for export
                    createExportTable(data, { session });
                    
                } catch (error) {
                    console.error('Error:', error);
                    timetableContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> An error occurred while fetching the timetable</div>';
                } finally {
                    loadingElement.style.display = 'none';
                }
            });
            
            // Functions for downloading timetable
            window.downloadPDF = function() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                
                const element = document.getElementById('export-timetable-container');
                if (!element) {
                    alert('No timetable data available to download. Please search for a timetable first.');
                    return;
                }
                
                html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    logging: false
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = 280;
                    const pageHeight = 190;
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    let heightLeft = imgHeight;
                    let position = 10;
                    
                    doc.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                    
                    while (heightLeft >= 0) {
                        position = heightLeft - imgHeight;
                        doc.addPage();
                        doc.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }
                    
                    doc.save(`Timetable_${document.getElementById('course').value}_${document.getElementById('semester').value}.pdf`);
                });
            };
            
            window.downloadWord = function() {
                // Use plain HTML-to-DOCX approach
                const element = document.getElementById('export-timetable-container');
                if (!element) {
                    alert('No timetable data available to download. Please search for a timetable first.');
                    return;
                }
                
                // Create a clone to modify for better Word format
                const clone = element.cloneNode(true);
                
                // Add some basic Word-friendly styling
                const style = document.createElement('style');
                style.textContent = `
                    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                    th, td { border: 1px solid #000; padding: 8px; }
                    th { background-color: #f2f2f2; }
                    .subject-code { font-weight: bold; }
                    h1, h2 { text-align: center; margin: 10px 0; }
                    h2.keys-title { text-align: left; }
                `;
                clone.prepend(style);
                
                // Get HTML content
                const html = `
                    <html xmlns:o='urn:schemas-microsoft-com:office:office' 
                          xmlns:w='urn:schemas-microsoft-com:office:word'
                          xmlns='http://www.w3.org/TR/REC-html40'>
                    <head>
                        <meta charset="utf-8">
                        <title>Timetable</title>
                        ${style.outerHTML}
                    </head>
                    <body>
                        ${clone.innerHTML}
                    </body>
                    </html>
                `;
                
                // Use msSaveBlob for IE and Edge
                if (window.navigator && window.navigator.msSaveOrOpenBlob) {
                    const blob = new Blob(['\ufeff', html], {
                        type: 'application/vnd.ms-word;charset=utf-8'
                    });
                    window.navigator.msSaveOrOpenBlob(blob, `Timetable_${document.getElementById('course').value}_${document.getElementById('semester').value}.doc`);
                    return;
                }
                
                // For other browsers
                const url = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(html);
                const downloadLink = document.createElement('a');
                document.body.appendChild(downloadLink);
                downloadLink.href = url;
                downloadLink.download = `Timetable_${document.getElementById('course').value}_${document.getElementById('semester').value}.doc`;
                downloadLink.click();
                document.body.removeChild(downloadLink);
            };
            
            window.downloadCSV = function() {
                const exportTable = document.getElementById('export-timetable');
                if (!exportTable) {
                    alert('No timetable data available to download. Please search for a timetable first.');
                    return;
                }
                
                let csv = [];
                
                // Add header row
                const headerRow = exportTable.querySelector('thead tr');
                let header = [];
                Array.from(headerRow.cells).forEach(cell => {
                    header.push('"' + cell.textContent.trim() + '"');
                });
                csv.push(header.join(','));
                
                // Add data rows
                const rows = exportTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    let rowData = [];
                    Array.from(row.cells).forEach(cell => {
                        rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
                    });
                    csv.push(rowData.join(','));
                });
                
                // Create and download the CSV file
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                saveAs(blob, `Timetable_${document.getElementById('course').value}_${document.getElementById('semester').value}.csv`);
            };
            
            // Function to create the formatted export table
            function createExportTable(data, info) {
                // Remove any existing export table
                const existingExport = document.getElementById('export-timetable-container');
                if (existingExport) {
                    existingExport.remove();
                }
                
                // Create container for the export table
                const container = document.createElement('div');
                container.id = 'export-timetable-container';
                container.className = 'hidden-export-container';
                container.style.position = 'absolute';
                container.style.left = '-9999px';
                container.style.top = '-9999px';
                
                // Create header info with proper formatting
                const sessionTitle = document.createElement('h1');
                sessionTitle.innerHTML = `Session: ${info.session}`;
                container.appendChild(sessionTitle);
                
                // Create a comprehensive table showing all classes
                const table = document.createElement('table');
                table.id = 'export-timetable';
                table.className = 'export-timetable';
                table.border = "1";
                table.style.borderCollapse = "collapse";
                table.style.width = "100%";
                table.style.marginTop = "20px";
                
                // Create header row
                const thead = document.createElement('thead');
                const headerRow = document.createElement('tr');
                
                ['Day', 'Time', 'Subject', 'Faculty', 'Room', 'Semester', 'Course'].forEach(header => {
                    const th = document.createElement('th');
                    th.textContent = header;
                    th.style.backgroundColor = "#f2f2f2";
                    headerRow.appendChild(th);
                });
                
                thead.appendChild(headerRow);
                table.appendChild(thead);
                
                // Create table body
                const tbody = document.createElement('tbody');
                
                // Sort data by day of week and time
                const sortedData = [...data].sort((a, b) => {
                    const dayOrder = {
                        'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 
                        'Friday': 5, 'Saturday': 6, 'Sunday': 7
                    };
                    
                    // Sort by day first
                    if (dayOrder[a.day] !== dayOrder[b.day]) {
                        return dayOrder[a.day] - dayOrder[b.day];
                    }
                    
                    // Then sort by time
                    const timeA = a.timeStart.split(':').map(Number);
                    const timeB = b.timeStart.split(':').map(Number);
                    
                    if (timeA[0] !== timeB[0]) {
                        return timeA[0] - timeB[0];
                    }
                    return timeA[1] - timeB[1];
                });
                
                // Add entries to table
                sortedData.forEach(entry => {
                    const row = document.createElement('tr');
                    
                    // Day
                    const dayCell = document.createElement('td');
                    dayCell.textContent = entry.day;
                    row.appendChild(dayCell);
                    
                    // Time
                    const timeCell = document.createElement('td');
                    timeCell.textContent = `${formatTime(entry.timeStart)} - ${formatTime(entry.timeEnd)}`;
                    row.appendChild(timeCell);
                    
                    // Subject
                    const subjectCell = document.createElement('td');
                    subjectCell.textContent = entry.subjectName;
                    row.appendChild(subjectCell);
                    
                    // Faculty
                    const facultyCell = document.createElement('td');
                    facultyCell.textContent = entry.facultyName;
                    row.appendChild(facultyCell);
                    
                    // Room
                    const roomCell = document.createElement('td');
                    roomCell.textContent = entry.roomNumber;
                    row.appendChild(roomCell);
                    
                    // Semester
                    const semesterCell = document.createElement('td');
                    semesterCell.textContent = entry.semester;
                    row.appendChild(semesterCell);
                    
                    // Course
                    const courseCell = document.createElement('td');
                    courseCell.textContent = entry.course;
                    row.appendChild(courseCell);
                    
                    tbody.appendChild(row);
                });
                
                table.appendChild(tbody);
                container.appendChild(table);
                
                // Create the subject details table
                const keysTitle = document.createElement('h2');
                keysTitle.textContent = 'SUBJECT & FACULTY DETAILS:';
                keysTitle.className = 'keys-title';
                keysTitle.style.marginTop = "30px";
                keysTitle.style.textAlign = "left";
                container.appendChild(keysTitle);
                
                const keysTable = document.createElement('table');
                keysTable.id = 'export-keys';
                keysTable.className = 'export-timetable export-keys';
                keysTable.border = "1";
                keysTable.style.borderCollapse = "collapse";
                keysTable.style.width = "100%";
                
                const keysThead = document.createElement('thead');
                const keysHeaderRow = document.createElement('tr');
                
                ['Subject', 'Faculty', 'Semester', 'Course'].forEach(header => {
                    const th = document.createElement('th');
                    th.textContent = header;
                    th.style.backgroundColor = "#f2f2f2";
                    keysHeaderRow.appendChild(th);
                });
                
                keysThead.appendChild(keysHeaderRow);
                keysTable.appendChild(keysThead);
                
                // Add subject and faculty details
                const keysTbody = document.createElement('tbody');
                
                // Track unique combinations
                const uniqueEntries = {};
                
                data.forEach(entry => {
                    // Create a unique key
                    const uniqueKey = `${entry.subjectName}_${entry.course}_${entry.semester}`;
                    
                    if (!uniqueEntries[uniqueKey]) {
                        uniqueEntries[uniqueKey] = {
                            subject: entry.subjectName,
                            faculty: entry.facultyName,
                            semester: entry.semester,
                            course: entry.course
                        };
                    }
                });
                
                Object.values(uniqueEntries).forEach(item => {
                    const row = document.createElement('tr');
                    
                    const subjectCell = document.createElement('td');
                    subjectCell.textContent = item.subject;
                    row.appendChild(subjectCell);
                    
                    const facultyCell = document.createElement('td');
                    facultyCell.textContent = item.faculty;
                    row.appendChild(facultyCell);
                    
                    const semesterCell = document.createElement('td');
                    semesterCell.textContent = item.semester;
                    row.appendChild(semesterCell);
                    
                    const courseCell = document.createElement('td');
                    courseCell.textContent = item.course;
                    row.appendChild(courseCell);
                    
                    keysTbody.appendChild(row);
                });
                
                keysTable.appendChild(keysTbody);
                container.appendChild(keysTable);
                
                // Add to document
                document.body.appendChild(container);
            }
        });
    </script>
</body>
</html> 