<?php
require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/dao/TimetableDAO.php';

// Start the session if not started
SessionUtils::startSessionIfNeeded();

// Enforce admin role access
SessionUtils::requireAdmin();

// Initialize DAO
$dao = TimetableDAO::getInstance();

// Hard-code all semester options to ensure they appear
$allSemesters = [
    ['semester_id' => 1, 'semester_no' => 'I'],
    ['semester_id' => 2, 'semester_no' => 'II'],
    ['semester_id' => 3, 'semester_no' => 'III'],
    ['semester_id' => 4, 'semester_no' => 'IV'],
    ['semester_id' => 5, 'semester_no' => 'V'],
    ['semester_id' => 6, 'semester_no' => 'VI'],
    ['semester_id' => 7, 'semester_no' => 'VII'],
    ['semester_id' => 8, 'semester_no' => 'VIII'],
    ['semester_id' => 9, 'semester_no' => 'IX'],
    ['semester_id' => 10, 'semester_no' => 'X']
];

// Get dropdown values for the form
try {
    $courses = $dao->getDistinctValues('courses', 'course_name');
    $rooms = $dao->getDistinctValues('rooms', 'room_number');
    $sessions = $dao->getSessions();
    
    // DEBUG: Check if sessions data is correctly loaded
    if (empty($sessions)) {
        error_log("WARNING: No sessions loaded from database");
        // Add a fallback dummy session for testing
        $sessions = [
            ['Batch_ID' => 1, 'BatchYear' => '2023-2024'],
            ['Batch_ID' => 2, 'BatchYear' => '2022-2023']
        ];
    } else {
        error_log("INFO: " . count($sessions) . " sessions loaded from database");
    }
    
    // Get faculty and subject data for autocomplete
    $faculty = $dao->getAllFaculty();
    $subjects = $dao->getAllSubjects();
} catch (Exception $e) {
    $error = "Error loading dropdown values: " . $e->getMessage();
    error_log($error);
}

// Get filter values from query string (for selecting a timetable to edit)
$selectedSemester = $_GET['semester'] ?? '';
$selectedCourse = $_GET['course'] ?? '';
$selectedSession = $_GET['session'] ?? '';
// Room option has been removed
$selectedRoom = '';

// Check if we have enough parameters to load a timetable
$canLoadTimetable = !empty($selectedSemester) && !empty($selectedCourse);

// Fetch timetable data if we have sufficient parameters
$timetableEntries = [];
if ($canLoadTimetable) {
    $filters = [
        'semester' => $selectedSemester,
        'course' => $selectedCourse
    ];
    
    if (!empty($selectedSession)) {
        $filters['session'] = $selectedSession;
    }
    
    // Room filtering has been removed
    
    try {
        $timetableEntries = $dao->getTimetableEntries($filters);
    } catch (Exception $e) {
        $error = "Error loading timetable: " . $e->getMessage();
        error_log($error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Timetable - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --light-color: #f5f6fa;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
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
        }

        /* Container */
        .container {
            max-width: 1400px;
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
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        #timetableFilterForm {
            position: relative;
            z-index: 100;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
            z-index: 50;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        /* Replace form styles for inputs/selects with simple browser-native styles */
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #999;
            border-radius: 4px;
            font-size: 16px;
            background-color: white;
            color: black;
            -webkit-appearance: listbox;
            -moz-appearance: listbox;
            appearance: listbox;
        }

        .form-group select {
            background-image: none; /* Remove any custom dropdown arrows */
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: 2px solid #3498db;
            border-color: #3498db;
        }
        
        /* Basic select element reset */
        select {
            background-color: white;
            color: black;
            border: 1px solid #999;
            padding: 8px;
            font-size: 16px;
            box-shadow: none; 
            text-shadow: none;
            -webkit-appearance: listbox;
            -moz-appearance: listbox;
            appearance: listbox;
        }
        
        /* Override any browser-specific styling */
        select::-ms-expand {
            display: inline; /* Default MS expand arrow */
        }
        
        /* Plain styling for options */
        select option {
            background-color: white;
            color: black;
            padding: 8px;
            font-size: 16px;
        }

        .current-badge {
            display: inline-block;
            background-color: var(--success-color);
            color: white;
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
            font-weight: 500;
        }

        /* Timetable Grid */
        .timetable-grid-container {
            overflow-x: auto;
            margin-top: 2rem;
        }

        .timetable-grid {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
            border: 1px solid #e2e8f0;
        }

        .timetable-grid th,
        .timetable-grid td {
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            text-align: center;
            position: relative;
        }

        .timetable-grid th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: var(--text-dark);
        }

        .timetable-grid tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .day-header {
            font-weight: 600;
            background-color: #f1f5f9;
            text-align: left;
        }

        .timetable-cell {
            height: 150px;
            min-width: 180px;
            padding: 0;
            position: relative;
            background-color: #fff;
            vertical-align: top;
        }

        .timetable-cell.has-entry {
            background-color: rgba(238, 242, 255, 0.5);
            border: 1px solid #c7d2fe;
        }

        .cell-actions {
            display: flex;
            position: absolute;
            top: 5px;
            right: 5px;
            gap: 5px;
            z-index: 10;
        }

        .cell-action-btn {
            background: white;
            border: 1px solid #e2e8f0;
            width: 25px;
            height: 25px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.75rem;
            transition: var(--transition);
        }

        .cell-action-btn:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .cell-action-btn.delete-btn:hover {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn i {
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #d35400;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            color: white;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: white;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(20px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--text-dark);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-state-icon {
            font-size: 3.5rem;
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .empty-state-desc {
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Entry card */
        .entry-card {
            padding: 0.5rem;
            height: 100%;
            width: 100%;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            border-radius: 4px;
        }

        .entry-card:hover {
            background-color: #e6efff;
        }

        .entry-subject {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
            padding-right: 40px;
        }

        .entry-code {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .entry-faculty {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: auto;
        }

        .entry-faculty i {
            color: var(--primary-color);
        }

        /* Autocomplete */
        .autocomplete-container {
            position: relative;
        }

        .autocomplete-results {
            position: absolute;
            z-index: 100;
            background: white;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .autocomplete-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .autocomplete-item:hover {
            background-color: #f1f5f9;
        }

        /* Alert messages */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-warning {
            background-color: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Loading indicator */
        .loading-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: var(--transition);
        }

        .loading-container.show {
            visibility: visible;
            opacity: 1;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Plain select styling for maximum visibility and compatibility */
        .plain-select {
            display: block;
            width: 100%;
            padding: 8px 10px;
            font-size: 16px;
            font-weight: normal;
            line-height: 1.5;
            color: #000;
            background-color: #fff;
            background-image: none;
            border: 1px solid #999;
            border-radius: 4px;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
            appearance: menulist;
        }
        
        .plain-select:focus {
            border-color: #3498db;
            outline: 0;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(52, 152, 219, 0.6);
        }
        
        .plain-select option {
            padding: 8px;
            color: #000;
            background-color: #fff;
        }
    </style>
    <!-- Immediate styling to ensure dropdowns are visible -->
    <script>
        // Force styles on dropdowns before any other scripts run
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var selects = document.querySelectorAll('select');
                for (var i = 0; i < selects.length; i++) {
                    selects[i].style.color = '#000';
                    selects[i].style.backgroundColor = '#fff';
                    selects[i].style.border = '1px solid #999';
                    
                    // Force native appearance
                    selects[i].style.WebkitAppearance = 'menulist';
                    selects[i].style.MozAppearance = 'menulist';
                    selects[i].style.appearance = 'menulist';
                    
                    // Apply to all options
                    var options = selects[i].querySelectorAll('option');
                    for (var j = 0; j < options.length; j++) {
                        options[j].style.color = '#000';
                        options[j].style.backgroundColor = '#fff';
                    }
                }
            }, 0);
        });
    </script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <h1>
            <i class="fas fa-calendar-alt"></i>
            Update Timetable
        </h1>
        <div class="navbar-actions">
            <a href="admin-dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
        </div>
    </nav>

    <!-- Main container -->
    <div class="container">
        <!-- Debug section - only visible when debug mode is enabled -->
        <?php if (isset($_GET['debug']) && $_GET['debug'] === 'true'): ?>
        <div class="card" style="margin-bottom: 15px">
            <div class="card-header">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Debug Info (Only visible to admin)
                </div>
            </div>
            <div class="card-body">
                <p><strong>Available Academic Sessions:</strong></p>
                <pre><?php var_dump($sessions); ?></pre>
                
                <p><strong>Selected Session:</strong> <?php echo htmlspecialchars($selectedSession); ?></p>
                <p><strong>Selected Semester:</strong> <?php echo htmlspecialchars($selectedSemester); ?></p>
                <p><strong>Selected Course:</strong> <?php echo htmlspecialchars($selectedCourse); ?></p>
                
                <p><strong>Available Sessions as List:</strong></p>
                <ul>
                    <?php foreach ($sessions as $session): ?>
                        <li>ID: <?php echo $session['Batch_ID']; ?> - 
                            BatchYear: <?php echo $session['BatchYear']; ?> - 
                            Selected: <?php echo ($selectedSession == $session['BatchYear']) ? 'YES' : 'NO'; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Timetable Selection Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <i class="fas fa-search"></i>
                    Select Timetable to Update
                </div>
            </div>
            <div class="card-body">
                <form id="timetableFilterForm" method="GET" action="admin-timetable-update.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="session">Academic Session:</label>
                            <select id="session" name="session" class="plain-select">
                                <option value="">All Sessions</option>
                                <?php foreach ($sessions as $session): ?>
                                    <option value="<?php echo htmlspecialchars($session['BatchYear']); ?>" 
                                            <?php echo ($selectedSession == $session['BatchYear']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($session['BatchYear']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester">Semester:</label>
                            <select id="semester" name="semester" required class="plain-select">
                                <option value="">Select Semester</option>
                                <?php foreach ($allSemesters as $semester): ?>
                                    <option value="<?php echo htmlspecialchars($semester['semester_no']); ?>" <?php echo ($selectedSemester == $semester['semester_no']) ? 'selected' : ''; ?>>
                                        Semester <?php echo htmlspecialchars($semester['semester_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="course">Course:</label>
                            <select id="course" name="course" required class="plain-select">
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($selectedCourse == $course) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group action-buttons" style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter Timetable
                        </button>
                        <a href="admin-timetable-update.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($canLoadTimetable): ?>
            <!-- Timetable Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <i class="fas fa-table"></i>
                        <?php echo htmlspecialchars("$selectedCourse - Semester $selectedSemester"); ?>
                        <?php if (!empty($selectedSession)): ?>
                            <span class="current-badge"><?php echo htmlspecialchars($selectedSession); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button id="addTimeSlotBtn" class="btn btn-warning">
                            <i class="fas fa-plus"></i>
                            Add Time Slot
                        </button>
                        <button id="addDayBtn" class="btn btn-warning">
                            <i class="fas fa-plus"></i>
                            Add Day
                        </button>
                        <button id="saveChangesBtn" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <button id="deleteTimetableBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i>
                            Delete Timetable
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($timetableEntries)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times empty-state-icon"></i>
                            <h3 class="empty-state-title">No timetable entries found</h3>
                            <p class="empty-state-desc">
                                No entries exist for the selected criteria. You can create a new timetable or add entries using the "Add Time Slot" and "Add Day" buttons.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Process entries and organize by day and time -->
                        <?php 
                            // Get unique days and time slots
                            $days = [];
                            $timeSlots = [];
                            $organizedEntries = [];
                            
                            foreach ($timetableEntries as $entry) {
                                if (!in_array($entry['day'], $days)) {
                                    $days[] = $entry['day'];
                                }
                                
                                $timeSlot = $entry['timeStart'] . ' - ' . $entry['timeEnd'];
                                if (!in_array($timeSlot, $timeSlots)) {
                                    $timeSlots[] = $timeSlot;
                                }
                                
                                // Organize entries by day and time
                                $key = $entry['day'] . '|' . $timeSlot;
                                $organizedEntries[$key] = $entry;
                            }
                            
                            // Sort days (Monday to Sunday)
                            $dayOrder = [
                                'Monday' => 1, 
                                'Tuesday' => 2, 
                                'Wednesday' => 3, 
                                'Thursday' => 4, 
                                'Friday' => 5, 
                                'Saturday' => 6, 
                                'Sunday' => 7
                            ];
                            
                            usort($days, function($a, $b) use ($dayOrder) {
                                return $dayOrder[$a] <=> $dayOrder[$b];
                            });
                            
                            // Sort time slots
                            usort($timeSlots, function($a, $b) {
                                $startTimeA = explode(' - ', $a)[0];
                                $startTimeB = explode(' - ', $b)[0];
                                return strtotime($startTimeA) <=> strtotime($startTimeB);
                            });
                        ?>
                        
                        <div class="timetable-grid-container">
                            <table class="timetable-grid" id="timetableGrid">
                                <thead>
                                    <tr>
                                        <th>Day / Time</th>
                                        <?php foreach ($timeSlots as $timeSlot): ?>
                                            <th><?php echo htmlspecialchars($timeSlot); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($days as $day): ?>
                                        <tr>
                                            <td class="day-header"><?php echo htmlspecialchars($day); ?></td>
                                            <?php foreach ($timeSlots as $timeSlot): ?>
                                                <?php 
                                                    $key = $day . '|' . $timeSlot;
                                                    $hasEntry = isset($organizedEntries[$key]);
                                                    $entry = $hasEntry ? $organizedEntries[$key] : null;
                                                    
                                                    // Parse time slot for cell data attributes
                                                    $times = explode(' - ', $timeSlot);
                                                    $startTime = $times[0];
                                                    $endTime = $times[1];
                                                ?>
                                                <td class="timetable-cell <?php echo $hasEntry ? 'has-entry' : ''; ?>" 
                                                    data-day="<?php echo htmlspecialchars($day); ?>"
                                                    data-time-slot="<?php echo htmlspecialchars($timeSlot); ?>"
                                                    data-start-time="<?php echo htmlspecialchars($startTime); ?>"
                                                    data-end-time="<?php echo htmlspecialchars($endTime); ?>"
                                                    data-room="<?php echo htmlspecialchars($selectedRoom); ?>"
                                                    data-semester="<?php echo htmlspecialchars($selectedSemester); ?>"
                                                    data-course="<?php echo htmlspecialchars($selectedCourse); ?>"
                                                    data-session="<?php echo htmlspecialchars($selectedSession); ?>">
                                                    
                                                    <div class="cell-actions">
                                                        <button class="cell-action-btn edit-entry" 
                                                                <?php if ($hasEntry): ?>data-entry-id="<?php echo $entry['id'] ?? ''; ?>"<?php endif; ?>>
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($hasEntry): ?>
                                                            <button class="cell-action-btn delete-btn delete-entry" data-entry-id="<?php echo $entry['id'] ?? ''; ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($hasEntry): ?>
                                                        <div class="entry-card" data-entry-id="<?php echo $entry['id'] ?? ''; ?>">
                                                            <div class="entry-subject"><?php echo htmlspecialchars($entry['subjectName']); ?></div>
                                                            <div class="entry-code"><?php echo htmlspecialchars($entry['subjectCode'] ?? ''); ?></div>
                                                            <div class="entry-faculty">
                                                                <i class="fas fa-user"></i>
                                                                <?php echo htmlspecialchars($entry['facultyName']); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Entry Modal -->
    <div class="modal" id="editEntryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-edit"></i>
                    <span id="modalTitle">Edit Timetable Entry</span>
                </h2>
                <button class="close-modal" id="closeEditModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="entryForm">
                    <input type="hidden" id="entryId" name="entryId">
                    <input type="hidden" id="day" name="day">
                    <input type="hidden" id="timeSlot" name="timeSlot">
                    <input type="hidden" id="startTime" name="startTime">
                    <input type="hidden" id="endTime" name="endTime">
                    <input type="hidden" id="semester" name="semester">
                    <input type="hidden" id="course" name="course">
                    <input type="hidden" id="session" name="session">
                    
                    <div class="form-group">
                        <label for="room">Room:</label>
                        <select id="roomSelect" name="room" required>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room); ?>">
                                    <?php echo htmlspecialchars($room); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <div class="autocomplete-container">
                            <input type="text" id="subject" name="subject" placeholder="Enter subject name" required>
                            <div class="autocomplete-results" id="subjectResults"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subjectCode">Subject Code:</label>
                        <input type="text" id="subjectCode" name="subjectCode" placeholder="Enter subject code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="faculty">Faculty:</label>
                        <div class="autocomplete-container">
                            <input type="text" id="faculty" name="faculty" placeholder="Enter faculty name" required>
                            <div class="autocomplete-results" id="facultyResults"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" id="cancelEditBtn">Cancel</button>
                <button class="btn btn-success" id="saveEntryBtn">Save Entry</button>
            </div>
        </div>
    </div>

    <!-- Add Time Slot Modal -->
    <div class="modal" id="addTimeSlotModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-plus"></i>
                    Add Time Slot
                </h2>
                <button class="close-modal" id="closeTimeSlotModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="timeSlotForm">
                    <div class="form-group">
                        <label for="startTimeInput">Start Time:</label>
                        <input type="time" id="startTimeInput" name="startTime" required>
                    </div>
                    <div class="form-group">
                        <label for="endTimeInput">End Time:</label>
                        <input type="time" id="endTimeInput" name="endTime" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" id="cancelTimeSlotBtn">Cancel</button>
                <button class="btn btn-success" id="addTimeSlotConfirmBtn">Add Time Slot</button>
            </div>
        </div>
    </div>

    <!-- Add Day Modal -->
    <div class="modal" id="addDayModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-plus"></i>
                    Add Day
                </h2>
                <button class="close-modal" id="closeDayModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="dayForm">
                    <div class="form-group">
                        <label for="daySelect">Select Day:</label>
                        <select id="daySelect" name="day" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" id="cancelDayBtn">Cancel</button>
                <button class="btn btn-success" id="addDayConfirmBtn">Add Day</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteConfirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-trash"></i>
                    Confirm Deletion
                </h2>
                <button class="close-modal" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this timetable entry? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="cancelDeleteBtn">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Delete Entire Timetable Confirmation Modal -->
    <div class="modal" id="deleteTimetableModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Delete Entire Timetable
                </h2>
                <button class="close-modal" id="closeTimetableDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Warning:</strong> You are about to delete the entire timetable for:
                </div>
                <div style="margin: 15px 0; padding: 10px; border: 1px solid #eee; background-color: #f9f9f9;">
                    <p><strong>Course:</strong> <span id="deleteCourseName"></span></p>
                    <p><strong>Semester:</strong> <span id="deleteSemesterName"></span></p>
                    <p><strong>Session:</strong> <span id="deleteSessionName"></span></p>
                </div>
                <p>This action <strong>cannot be undone</strong>. Are you absolutely sure?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="cancelTimetableDeleteBtn">Cancel</button>
                <button class="btn btn-danger" id="confirmTimetableDeleteBtn">Delete Entire Timetable</button>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div class="loading-container" id="loadingIndicator">
        <div class="loading-spinner"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Subject and faculty data for autocomplete
            const subjectsData = <?php echo json_encode($subjects) ?>;
            const facultyData = <?php echo json_encode($faculty) ?>;
            
            // DOM Elements
            const editEntryModal = document.getElementById('editEntryModal');
            const addTimeSlotModal = document.getElementById('addTimeSlotModal');
            const addDayModal = document.getElementById('addDayModal');
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            const deleteTimetableModal = document.getElementById('deleteTimetableModal');
            const loadingIndicator = document.getElementById('loadingIndicator');
            
            const entryForm = document.getElementById('entryForm');
            const timeSlotForm = document.getElementById('timeSlotForm');
            const dayForm = document.getElementById('dayForm');
            
            // Current entry being edited or deleted
            let currentEntryId = null;
            let currentCell = null;
            let isNewEntry = false;
            
            // Autocomplete functionality
            setupAutocomplete('subject', 'subjectResults', subjectsData, 'subject_name', function(item) {
                document.getElementById('subject').value = item.subject_name;
                document.getElementById('subjectCode').value = item.subject_code;
                document.getElementById('faculty').value = item.faculty_name || '';
            });
            
            setupAutocomplete('faculty', 'facultyResults', facultyData, 'faculty_name', function(item) {
                document.getElementById('faculty').value = item.faculty_name;
            });
            
            // Event Listeners for edit buttons in cells
            document.querySelectorAll('.edit-entry').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const cell = this.closest('.timetable-cell');
                    currentCell = cell;
                    
                    // Check if this is a new entry or existing
                    const entryCard = cell.querySelector('.entry-card');
                    isNewEntry = !entryCard;
                    
                    if (isNewEntry) {
                        // New entry
                        document.getElementById('modalTitle').textContent = 'Add New Entry';
                        resetEntryForm();
                    } else {
                        // Edit existing entry
                        document.getElementById('modalTitle').textContent = 'Edit Timetable Entry';
                        currentEntryId = entryCard.dataset.entryId;
                        
                        // Fill the form with existing data
                        document.getElementById('entryId').value = currentEntryId;
                        document.getElementById('subject').value = entryCard.querySelector('.entry-subject').textContent.trim();
                        document.getElementById('subjectCode').value = entryCard.querySelector('.entry-code').textContent.trim();
                        document.getElementById('faculty').value = entryCard.querySelector('.entry-faculty').textContent.trim();
                    }
                    
                    // Fill in the cell data
                    document.getElementById('day').value = cell.dataset.day;
                    document.getElementById('timeSlot').value = cell.dataset.timeSlot;
                    document.getElementById('startTime').value = cell.dataset.startTime;
                    document.getElementById('endTime').value = cell.dataset.endTime;
                    document.getElementById('semester').value = cell.dataset.semester;
                    document.getElementById('course').value = cell.dataset.course;
                    document.getElementById('session').value = cell.dataset.session;
                    
                    // Set room if available
                    const roomSelect = document.getElementById('roomSelect');
                    if (cell.dataset.room) {
                        Array.from(roomSelect.options).forEach(option => {
                            if (option.value === cell.dataset.room) {
                                option.selected = true;
                            }
                        });
                    }
                    
                    // Show the modal
                    openModal(editEntryModal);
                });
            });
            
            // Cell entry click events
            document.querySelectorAll('.entry-card').forEach(card => {
                card.addEventListener('click', function() {
                    const editBtn = this.closest('.timetable-cell').querySelector('.edit-entry');
                    if (editBtn) {
                        editBtn.click();
                    }
                });
            });
            
            // Handle empty cell clicks
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.addEventListener('click', function(e) {
                    // Only handle clicks on the cell itself, not on children
                    if (e.target === cell) {
                        const editBtn = cell.querySelector('.edit-entry');
                        if (editBtn) {
                            editBtn.click();
                        }
                    }
                });
            });
            
            // Handle delete buttons
            document.querySelectorAll('.delete-entry').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    currentEntryId = this.dataset.entryId;
                    currentCell = this.closest('.timetable-cell');
                    openModal(deleteConfirmModal);
                });
            });
            
            // Add time slot button
            document.getElementById('addTimeSlotBtn')?.addEventListener('click', function() {
                resetTimeSlotForm();
                openModal(addTimeSlotModal);
            });
            
            // Add day button
            document.getElementById('addDayBtn')?.addEventListener('click', function() {
                resetDayForm();
                openModal(addDayModal);
            });
            
            // Save changes button
            document.getElementById('saveChangesBtn')?.addEventListener('click', function() {
                // Get all entry data from the grid
                const entries = [];
                document.querySelectorAll('.timetable-cell.has-entry').forEach(cell => {
                    const entryCard = cell.querySelector('.entry-card');
                    if (entryCard) {
                        entries.push({
                            entryId: entryCard.dataset.entryId,
                            day: cell.dataset.day,
                            timeSlot: cell.dataset.timeSlot,
                            startTime: cell.dataset.startTime,
                            endTime: cell.dataset.endTime,
                            room: cell.dataset.room,
                            semester: cell.dataset.semester,
                            course: cell.dataset.course,
                            session: cell.dataset.session,
                            subject: entryCard.querySelector('.entry-subject').textContent.trim(),
                            subjectCode: entryCard.querySelector('.entry-code').textContent.trim(),
                            faculty: entryCard.querySelector('.entry-faculty').textContent.trim()
                        });
                    }
                });
                
                if (entries.length === 0) {
                    alert('No entries to save. Add some entries first.');
                    return;
                }
                
                // Show loading indicator
                showLoading();
                
                // Save all entries to the server
                fetch('update-timetable.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        entries: entries,
                        semester: document.getElementById('semester').value,
                        course: document.getElementById('course').value,
                        session: document.getElementById('session').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        alert('Timetable updated successfully.');
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Error updating timetable: ' + data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    alert('An error occurred while saving the timetable.');
                });
            });
            
            // Modal actions
            document.getElementById('closeEditModal').addEventListener('click', () => closeModal(editEntryModal));
            document.getElementById('cancelEditBtn').addEventListener('click', () => closeModal(editEntryModal));
            
            document.getElementById('closeTimeSlotModal').addEventListener('click', () => closeModal(addTimeSlotModal));
            document.getElementById('cancelTimeSlotBtn').addEventListener('click', () => closeModal(addTimeSlotModal));
            
            document.getElementById('closeDayModal').addEventListener('click', () => closeModal(addDayModal));
            document.getElementById('cancelDayBtn').addEventListener('click', () => closeModal(addDayModal));
            
            document.getElementById('closeDeleteModal').addEventListener('click', () => closeModal(deleteConfirmModal));
            document.getElementById('cancelDeleteBtn').addEventListener('click', () => closeModal(deleteConfirmModal));
            
            // Save entry
            document.getElementById('saveEntryBtn').addEventListener('click', function() {
                if (!validateEntryForm()) {
                    return;
                }
                
                // Get form data
                const formData = {
                    entryId: document.getElementById('entryId').value,
                    day: document.getElementById('day').value,
                    timeSlot: document.getElementById('timeSlot').value,
                    startTime: document.getElementById('startTime').value,
                    endTime: document.getElementById('endTime').value,
                    room: document.getElementById('roomSelect').value,
                    semester: document.getElementById('semester').value,
                    course: document.getElementById('course').value,
                    session: document.getElementById('session').value,
                    subject: document.getElementById('subject').value,
                    subjectCode: document.getElementById('subjectCode').value,
                    faculty: document.getElementById('faculty').value
                };
                
                // Update the UI
                if (currentCell) {
                    // Update cell data attributes
                    currentCell.dataset.room = formData.room;
                    
                    // Make the cell show it has an entry
                    currentCell.classList.add('has-entry');
                    
                    // Check if we already have an entry card
                    let entryCard = currentCell.querySelector('.entry-card');
                    
                    if (!entryCard) {
                        // Create new entry card
                        entryCard = document.createElement('div');
                        entryCard.className = 'entry-card';
                        entryCard.dataset.entryId = 'new_' + Date.now(); // Temporary ID for new entries
                        
                        entryCard.innerHTML = `
                            <div class="entry-subject">${formData.subject}</div>
                            <div class="entry-code">${formData.subjectCode}</div>
                            <div class="entry-faculty">
                                <i class="fas fa-user"></i>
                                ${formData.faculty}
                            </div>
                        `;
                        
                        // Add delete button if not exist
                        if (!currentCell.querySelector('.delete-entry')) {
                            const actions = currentCell.querySelector('.cell-actions');
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'cell-action-btn delete-btn delete-entry';
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                            deleteBtn.dataset.entryId = entryCard.dataset.entryId;
                            
                            // Add delete event listener
                            deleteBtn.addEventListener('click', function(e) {
                                e.stopPropagation();
                                currentEntryId = this.dataset.entryId;
                                currentCell = this.closest('.timetable-cell');
                                openModal(deleteConfirmModal);
                            });
                            
                            actions.appendChild(deleteBtn);
                        }
                        
                        // Add click event to the new card
                        entryCard.addEventListener('click', function() {
                            const editBtn = this.closest('.timetable-cell').querySelector('.edit-entry');
                            if (editBtn) {
                                editBtn.click();
                            }
                        });
                        
                        currentCell.appendChild(entryCard);
                    } else {
                        // Update existing entry card
                        entryCard.querySelector('.entry-subject').textContent = formData.subject;
                        entryCard.querySelector('.entry-code').textContent = formData.subjectCode;
                        entryCard.querySelector('.entry-faculty').textContent = formData.faculty;
                    }
                }
                
                closeModal(editEntryModal);
            });
            
            // Add new time slot
            document.getElementById('addTimeSlotConfirmBtn').addEventListener('click', function() {
                if (!validateTimeSlotForm()) {
                    return;
                }
                
                const startTime = document.getElementById('startTimeInput').value;
                const endTime = document.getElementById('endTimeInput').value;
                const timeSlot = startTime + ' - ' + endTime;
                
                // Check if this time slot already exists
                const headers = document.querySelectorAll('#timetableGrid th');
                for (let i = 1; i < headers.length; i++) {
                    if (headers[i].textContent.trim() === timeSlot) {
                        alert('This time slot already exists in the timetable.');
                        return;
                    }
                }
                
                // Add new column to the timetable
                const table = document.getElementById('timetableGrid');
                
                // Add header
                const headerRow = table.querySelector('thead tr');
                const newHeader = document.createElement('th');
                newHeader.textContent = timeSlot;
                headerRow.appendChild(newHeader);
                
                // Add cells for each day
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const day = row.querySelector('.day-header').textContent.trim();
                    
                    const newCell = document.createElement('td');
                    newCell.className = 'timetable-cell';
                    newCell.dataset.day = day;
                    newCell.dataset.timeSlot = timeSlot;
                    newCell.dataset.startTime = startTime;
                    newCell.dataset.endTime = endTime;
                    newCell.dataset.room = document.getElementById('roomSelect').value;
                    newCell.dataset.semester = document.querySelector('#semester').value;
                    newCell.dataset.course = document.querySelector('#course').value;
                    newCell.dataset.session = document.querySelector('#session').value;
                    
                    // Add cell actions
                    const cellActions = document.createElement('div');
                    cellActions.className = 'cell-actions';
                    
                    const editBtn = document.createElement('button');
                    editBtn.className = 'cell-action-btn edit-entry';
                    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    
                    // Add edit event listener
                    editBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        currentCell = this.closest('.timetable-cell');
                        document.getElementById('modalTitle').textContent = 'Add New Entry';
                        resetEntryForm();
                        
                        document.getElementById('day').value = currentCell.dataset.day;
                        document.getElementById('timeSlot').value = currentCell.dataset.timeSlot;
                        document.getElementById('startTime').value = currentCell.dataset.startTime;
                        document.getElementById('endTime').value = currentCell.dataset.endTime;
                        document.getElementById('semester').value = currentCell.dataset.semester;
                        document.getElementById('course').value = currentCell.dataset.course;
                        document.getElementById('session').value = currentCell.dataset.session;
                        
                        // Show the modal
                        openModal(editEntryModal);
                    });
                    
                    cellActions.appendChild(editBtn);
                    newCell.appendChild(cellActions);
                    
                    // Add click event to the cell
                    newCell.addEventListener('click', function(e) {
                        if (e.target === newCell) {
                            const editBtn = newCell.querySelector('.edit-entry');
                            if (editBtn) {
                                editBtn.click();
                            }
                        }
                    });
                    
                    row.appendChild(newCell);
                });
                
                closeModal(addTimeSlotModal);
            });
            
            // Add new day
            document.getElementById('addDayConfirmBtn').addEventListener('click', function() {
                const day = document.getElementById('daySelect').value;
                
                // Check if this day already exists
                const dayHeaders = document.querySelectorAll('.day-header');
                for (let i = 0; i < dayHeaders.length; i++) {
                    if (dayHeaders[i].textContent.trim() === day) {
                        alert('This day already exists in the timetable.');
                        return;
                    }
                }
                
                // Add new row to the timetable
                const table = document.getElementById('timetableGrid');
                const timeSlots = [];
                
                // Get all time slots
                const headers = table.querySelectorAll('thead th');
                for (let i = 1; i < headers.length; i++) {
                    const timeSlot = headers[i].textContent.trim();
                    const times = timeSlot.split(' - ');
                    timeSlots.push({
                        slot: timeSlot,
                        startTime: times[0],
                        endTime: times[1]
                    });
                }
                
                // Create new row
                const newRow = document.createElement('tr');
                
                // Add day header
                const dayHeader = document.createElement('td');
                dayHeader.className = 'day-header';
                dayHeader.textContent = day;
                newRow.appendChild(dayHeader);
                
                // Add cells for each time slot
                timeSlots.forEach(timeSlot => {
                    const newCell = document.createElement('td');
                    newCell.className = 'timetable-cell';
                    newCell.dataset.day = day;
                    newCell.dataset.timeSlot = timeSlot.slot;
                    newCell.dataset.startTime = timeSlot.startTime;
                    newCell.dataset.endTime = timeSlot.endTime;
                    newCell.dataset.room = document.getElementById('roomSelect').value;
                    newCell.dataset.semester = document.querySelector('#semester').value;
                    newCell.dataset.course = document.querySelector('#course').value;
                    newCell.dataset.session = document.querySelector('#session').value;
                    
                    // Add cell actions
                    const cellActions = document.createElement('div');
                    cellActions.className = 'cell-actions';
                    
                    const editBtn = document.createElement('button');
                    editBtn.className = 'cell-action-btn edit-entry';
                    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    
                    // Add edit event listener
                    editBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        currentCell = this.closest('.timetable-cell');
                        document.getElementById('modalTitle').textContent = 'Add New Entry';
                        resetEntryForm();
                        
                        document.getElementById('day').value = currentCell.dataset.day;
                        document.getElementById('timeSlot').value = currentCell.dataset.timeSlot;
                        document.getElementById('startTime').value = currentCell.dataset.startTime;
                        document.getElementById('endTime').value = currentCell.dataset.endTime;
                        document.getElementById('semester').value = currentCell.dataset.semester;
                        document.getElementById('course').value = currentCell.dataset.course;
                        document.getElementById('session').value = currentCell.dataset.session;
                        
                        // Show the modal
                        openModal(editEntryModal);
                    });
                    
                    cellActions.appendChild(editBtn);
                    newCell.appendChild(cellActions);
                    
                    // Add click event to the cell
                    newCell.addEventListener('click', function(e) {
                        if (e.target === newCell) {
                            const editBtn = newCell.querySelector('.edit-entry');
                            if (editBtn) {
                                editBtn.click();
                            }
                        }
                    });
                    
                    newRow.appendChild(newCell);
                });
                
                // Add the row in the correct position based on day order
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                
                const dayOrder = {
                    'Monday': 1, 
                    'Tuesday': 2, 
                    'Wednesday': 3, 
                    'Thursday': 4, 
                    'Friday': 5, 
                    'Saturday': 6, 
                    'Sunday': 7
                };
                
                let inserted = false;
                for (let i = 0; i < rows.length; i++) {
                    const rowDay = rows[i].querySelector('.day-header').textContent.trim();
                    if (dayOrder[day] < dayOrder[rowDay]) {
                        tbody.insertBefore(newRow, rows[i]);
                        inserted = true;
                        break;
                    }
                }
                
                if (!inserted) {
                    tbody.appendChild(newRow);
                }
                
                closeModal(addDayModal);
            });
            
            // Delete entry
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (currentCell) {
                    // Remove the entry card
                    const entryCard = currentCell.querySelector('.entry-card');
                    if (entryCard) {
                        entryCard.remove();
                    }
                    
                    // Remove the has-entry class
                    currentCell.classList.remove('has-entry');
                    
                    // Remove the delete button
                    const deleteBtn = currentCell.querySelector('.delete-entry');
                    if (deleteBtn) {
                        deleteBtn.remove();
                    }
                }
                
                closeModal(deleteConfirmModal);
            });
            
            // Delete Timetable button
            document.getElementById('deleteTimetableBtn')?.addEventListener('click', function() {
                // Populate the confirmation modal with timetable details
                document.getElementById('deleteCourseName').textContent = document.querySelector('#course').value || '<?php echo htmlspecialchars($selectedCourse); ?>';
                document.getElementById('deleteSemesterName').textContent = document.querySelector('#semester').value || '<?php echo htmlspecialchars($selectedSemester); ?>';
                document.getElementById('deleteSessionName').textContent = document.querySelector('#session').value || '<?php echo htmlspecialchars($selectedSession); ?>';
                
                // Show the modal
                openModal(deleteTimetableModal);
            });
            
            // Delete timetable confirmation
            document.getElementById('confirmTimetableDeleteBtn')?.addEventListener('click', function() {
                // Show loading indicator
                showLoading();
                
                // Get timetable details
                const semester = document.querySelector('#semester').value || '<?php echo htmlspecialchars($selectedSemester); ?>';
                const course = document.querySelector('#course').value || '<?php echo htmlspecialchars($selectedCourse); ?>';
                const session = document.querySelector('#session').value || '<?php echo htmlspecialchars($selectedSession); ?>';
                
                // Send delete request to the server
                fetch('delete-timetable.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        semester: semester,
                        course: course,
                        session: session
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        alert('Timetable deleted successfully.');
                        // Redirect to admin dashboard
                        window.location.href = 'admin-dashboard.php';
                    } else {
                        alert('Error deleting timetable: ' + data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    alert('An error occurred while deleting the timetable.');
                });
                
                // Close the modal
                closeModal(deleteTimetableModal);
            });
            
            // Delete timetable modal actions
            document.getElementById('closeTimetableDeleteModal')?.addEventListener('click', () => closeModal(deleteTimetableModal));
            document.getElementById('cancelTimetableDeleteBtn')?.addEventListener('click', () => closeModal(deleteTimetableModal));
            
            // Utility functions
            function openModal(modal) {
                modal.classList.add('show');
                setTimeout(() => {
                    modal.querySelector('.modal-content').style.transform = 'translateY(0)';
                    modal.querySelector('.modal-content').style.opacity = '1';
                }, 10);
            }
            
            function closeModal(modal) {
                modal.querySelector('.modal-content').style.transform = 'translateY(20px)';
                modal.querySelector('.modal-content').style.opacity = '0';
                setTimeout(() => {
                    modal.classList.remove('show');
                }, 300);
            }
            
            function resetEntryForm() {
                entryForm.reset();
                document.getElementById('entryId').value = '';
            }
            
            function resetTimeSlotForm() {
                timeSlotForm.reset();
            }
            
            function resetDayForm() {
                dayForm.reset();
            }
            
            function validateEntryForm() {
                const subject = document.getElementById('subject').value.trim();
                const subjectCode = document.getElementById('subjectCode').value.trim();
                const faculty = document.getElementById('faculty').value.trim();
                
                if (!subject) {
                    alert('Please enter a subject name.');
                    return false;
                }
                
                if (!subjectCode) {
                    alert('Please enter a subject code.');
                    return false;
                }
                
                if (!faculty) {
                    alert('Please enter a faculty name.');
                    return false;
                }
                
                return true;
            }
            
            function validateTimeSlotForm() {
                const startTime = document.getElementById('startTimeInput').value;
                const endTime = document.getElementById('endTimeInput').value;
                
                if (!startTime) {
                    alert('Please enter a start time.');
                    return false;
                }
                
                if (!endTime) {
                    alert('Please enter an end time.');
                    return false;
                }
                
                if (startTime >= endTime) {
                    alert('Start time must be before end time.');
                    return false;
                }
                
                return true;
            }
            
            function showLoading() {
                loadingIndicator.classList.add('show');
            }
            
            function hideLoading() {
                loadingIndicator.classList.remove('show');
            }
            
            function setupAutocomplete(inputId, resultsId, data, displayField, selectCallback) {
                const input = document.getElementById(inputId);
                const resultsDiv = document.getElementById(resultsId);
                
                input.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();
                    if (query.length < 2) {
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    
                    // Filter data
                    const filtered = data.filter(item => {
                        return item[displayField].toLowerCase().includes(query);
                    }).slice(0, 5); // Limit to 5 results
                    
                    if (filtered.length === 0) {
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    
                    // Display results
                    resultsDiv.innerHTML = '';
                    filtered.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.textContent = item[displayField];
                        div.addEventListener('click', function() {
                            selectCallback(item);
                            resultsDiv.style.display = 'none';
                        });
                        resultsDiv.appendChild(div);
                    });
                    
                    resultsDiv.style.display = 'block';
                });
                
                // Hide results when clicking outside
                document.addEventListener('click', function(e) {
                    if (e.target !== input && !resultsDiv.contains(e.target)) {
                        resultsDiv.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html> 