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
    
    // Get faculty and subject data for autocomplete
    $faculty = $dao->getAllFaculty();
    $subjects = $dao->getAllSubjects();
} catch (Exception $e) {
    $error = "Error loading dropdown values: " . $e->getMessage();
    error_log($error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Timetable - Admin Panel</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            border: 1px solid #e2e8f0;
        }

        .timetable-grid th,
        .timetable-grid td {
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            text-align: center;
            min-width: 150px;
        }

        .timetable-grid th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--text-dark);
            position: relative;
        }

        .time-slot-actions {
            display: flex;
            gap: 5px;
            margin-left: 8px;
        }

        .time-slot-actions button {
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            background-color: transparent;
            border: 1px solid;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        .edit-time-slot {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .edit-time-slot:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .remove-time-slot {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .remove-time-slot:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .timetable-grid .time-slot {
            background-color: #f1f5f9;
            font-weight: 500;
        }

        .timetable-cell {
            min-height: 100px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .timetable-cell:hover {
            background-color: #f1f5f9;
        }

        .timetable-cell.has-entry {
            background-color: #e6f7ff;
        }

        .cell-content {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .subject-code {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .subject-name {
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .cell-faculty {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: auto;
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

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-1px);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Entry Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger-color);
        }

        .modal-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 1.25rem;
            color: var(--text-dark);
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
        }

        .toast {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease, fadeOut 0.5s ease 2.5s forwards;
            max-width: 350px;
        }

        .toast-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .toast-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        .toast-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning-color);
        }

        .toast i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .timetable-grid th,
            .timetable-grid td {
                padding: 0.5rem;
                min-width: 120px;
            }

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1><i class="fas fa-calendar-alt"></i> Timetable Management</h1>
        <div class="navbar-actions">
            <a href="admin-dashboard.php" class="btn btn-primary"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-plus-circle"></i> Create New Timetable</span>
            </div>
            <div class="card-body">
                <form id="timetableForm" class="timetable-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="session">Academic Session:</label>
                            <select name="session" id="session" required>
                                <option value="">Select Session</option>
                                <?php foreach ($sessions as $session): ?>
                                    <option value="<?php echo htmlspecialchars($session['session_year']); ?>">
                                        <?php echo htmlspecialchars($session['session_year']); ?>
                                        <?php if ($session['session_year'] === '2024-2025'): ?>
                                            <span class="current-badge">(Current)</span>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new">+ Add New Batch Year</option>
                            </select>
                        </div>
                        <div class="form-group" id="newBatchYearGroup" style="display: none;">
                            <label for="newBatchYear">New Batch Year:</label>
                            <input type="text" id="newBatchYear" name="newBatchYear" placeholder="e.g., 2026-2027" pattern="\d{4}-\d{4}">
                        </div>
                        <div class="form-group">
                            <label for="semester">Semester:</label>
                            <select name="semester" id="semester" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($allSemesters as $sem): ?>
                                    <option value="<?php echo htmlspecialchars($sem['semester_no']); ?>">
                                        <?php echo htmlspecialchars($sem['semester_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="course">Course:</label>
                            <select name="course" id="course" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>">
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room">Room:</label>
                            <select name="room" id="room" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room); ?>">
                                        <?php echo htmlspecialchars($room); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Generate Timetable Grid</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="timetableGridContainer"></div>
    </div>

    <!-- Entry Modal -->
    <div id="entryModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 class="modal-title">Add/Edit Timetable Entry</h3>
            <form id="entryForm">
                <input type="hidden" id="dayInput" name="day">
                <input type="hidden" id="timeSlotInput" name="timeSlot">
                
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required placeholder="Enter subject name" list="subjectDatalist">
                    <datalist id="subjectDatalist">
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject['subject_name']); ?>" data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group">
                    <label for="subjectCode">Subject Code:</label>
                    <input type="text" id="subjectCode" name="subjectCode" required placeholder="E.g., IT-201">
                </div>
                
                <div class="form-group">
                    <label for="faculty">Faculty:</label>
                    <input type="text" id="faculty" name="faculty" required placeholder="Enter faculty name" list="facultyDatalist">
                    <datalist id="facultyDatalist">
                        <?php foreach ($faculty as $f): ?>
                            <option value="<?php echo htmlspecialchars($f['faculty_name']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Entry</button>
                    <button type="button" id="deleteEntry" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                    <button type="button" class="btn btn-warning cancel-modal"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast container for notifications -->
    <div class="toast-container" id="toastContainer"></div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM Elements
        const timetableForm = document.getElementById('timetableForm');
        const timetableGridContainer = document.getElementById('timetableGridContainer');
        const entryModal = document.getElementById('entryModal');
        const entryForm = document.getElementById('entryForm');
        const closeModal = document.querySelector('.close-modal');
        const cancelModal = document.querySelector('.cancel-modal');
        const deleteEntry = document.getElementById('deleteEntry');
        const toastContainer = document.getElementById('toastContainer');
        
        // Time slots for the timetable
        let timeSlots = [
            '9:00-10:00', '10:00-11:00', '11:00-12:00', 
            '12:00-1:00', '1:00-2:00', '2:00-3:00', 
            '3:00-4:00', '4:00-5:00'
        ];
        
        // Days of the week
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // Store timetable data
        let timetableData = {};
        
        // Autocomplete subject code when subject is selected
        const subjectInput = document.getElementById('subject');
        const subjectCodeInput = document.getElementById('subjectCode');
        const subjectDatalist = document.getElementById('subjectDatalist');
        
        subjectInput.addEventListener('input', function() {
            // Look for matching option in datalist
            const options = subjectDatalist.querySelectorAll('option');
            const value = this.value;
            
            // Find matching option
            for (const option of options) {
                if (option.value === value) {
                    // Get subject code from data attribute
                    const subjectCode = option.getAttribute('data-code');
                    if (subjectCode) {
                        subjectCodeInput.value = subjectCode;
                    }
                    break;
                }
            }
        });
        
        // Handle batch year selection
        const sessionSelect = document.getElementById('session');
        const newBatchYearGroup = document.getElementById('newBatchYearGroup');
        const newBatchYearInput = document.getElementById('newBatchYear');
        
        sessionSelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newBatchYearGroup.style.display = 'block';
                newBatchYearInput.required = true;
            } else {
                newBatchYearGroup.style.display = 'none';
                newBatchYearInput.required = false;
            }
        });
        
        // Validate batch year format
        newBatchYearInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^\d{4}-\d{4}$/.test(value);
            
            if (isValid) {
                // Check the years are consecutive
                const years = value.split('-');
                const firstYear = parseInt(years[0]);
                const secondYear = parseInt(years[1]);
                
                if (secondYear !== firstYear + 1) {
                    this.setCustomValidity('The second year should be one year after the first year');
                } else {
                    this.setCustomValidity('');
                }
            } else {
                this.setCustomValidity('Please enter a valid batch year in format YYYY-YYYY');
            }
        });
        
        // Handle timetable form submission
        timetableForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            let session = document.getElementById('session').value;
            const semester = document.getElementById('semester').value;
            const course = document.getElementById('course').value;
            const room = document.getElementById('room').value;
            
            // Check if adding a new batch year
            if (session === 'new') {
                session = document.getElementById('newBatchYear').value;
                if (!session || !/^\d{4}-\d{4}$/.test(session)) {
                    showToast('Please enter a valid batch year in format YYYY-YYYY', 'error');
                    return;
                }
                
                // Show message about new batch year
                showToast(`Creating new batch year: ${session}`, 'success');
            }
            
            if (!session || !semester || !course || !room) {
                showToast('Please fill all required fields', 'error');
                return;
            }
            
            // Initialize timetable data
            timetableData = {
                session: session,
                semester: semester,
                course: course,
                room: room,
                entries: {}
            };
            
            // Generate timetable grid
            generateTimetableGrid();
            
            showToast('Timetable grid generated successfully', 'success');
        });
        
        // Generate timetable grid
        function generateTimetableGrid() {
            // Create table
            let tableHTML = `
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-table"></i> Timetable Grid: ${timetableData.course} - Semester ${timetableData.semester}</span>
                        <div>
                            <button type="button" class="btn btn-success" id="saveTimetable"><i class="fas fa-save"></i> Save Timetable</button>
                            <button type="button" class="btn btn-warning" id="addTimeSlot"><i class="fas fa-plus"></i> Add Time Slot</button>
                            <button type="button" class="btn btn-warning" id="addDay"><i class="fas fa-plus"></i> Add Day</button>
                            <button type="button" class="btn btn-danger" id="resetGrid"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="timetable-grid-container">
                            <table class="timetable-grid">
                                <thead>
                                    <tr>
                                        <th>Day / Time</th>
            `;
            
            // Add time slot headers with edit and remove options
            timeSlots.forEach((slot, index) => {
                tableHTML += `
                    <th>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${slot}</span>
                            <div class="time-slot-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-time-slot" data-index="${index}" title="Edit Time Slot">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-time-slot" data-index="${index}" title="Remove Time Slot">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </th>`;
            });
            
            tableHTML += `
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            // Add rows for each day
            days.forEach(day => {
                tableHTML += `
                    <tr>
                        <td class="time-slot">${day}</td>
                `;
                
                // Add cells for each time slot
                timeSlots.forEach(slot => {
                    const cellId = `${day.toLowerCase()}-${slot.replace(':', '').replace('-', '')}`;
                    tableHTML += `
                        <td class="timetable-cell" data-day="${day}" data-timeslot="${slot}" id="${cellId}">
                            <div class="cell-content">
                                <!-- Cell content will be added dynamically -->
                            </div>
                        </td>
                    `;
                });
                
                tableHTML += `</tr>`;
            });
            
            tableHTML += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            timetableGridContainer.innerHTML = tableHTML;
            
            // Add event listeners to cells
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.addEventListener('click', function() {
                    openEntryModal(this);
                });
            });
            
            // Add event listener to save button
            document.getElementById('saveTimetable').addEventListener('click', saveTimetable);
            
            // Add event listener to reset button
            document.getElementById('resetGrid').addEventListener('click', resetTimetableGrid);
            
            // Add event listener to add time slot button
            document.getElementById('addTimeSlot').addEventListener('click', addTimeSlot);

            // Add event listener to add day button
            document.getElementById('addDay').addEventListener('click', addDayRow);
            
            // Add event listeners for time slot edit buttons
            document.querySelectorAll('.edit-time-slot').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent cell click event
                    const index = parseInt(this.dataset.index);
                    editTimeSlot(index);
                });
            });
            
            // Add event listeners for time slot remove buttons
            document.querySelectorAll('.remove-time-slot').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent cell click event
                    const index = parseInt(this.dataset.index);
                    removeTimeSlot(index);
                });
            });
        }
        
        // Open entry modal
        function openEntryModal(cell) {
            const day = cell.dataset.day;
            const timeSlot = cell.dataset.timeslot;
            
            // Set form fields
            document.getElementById('dayInput').value = day;
            document.getElementById('timeSlotInput').value = timeSlot;
            
            // Check if there's existing data for this cell
            const cellId = `${day.toLowerCase()}-${timeSlot.replace(':', '').replace('-', '')}`;
            const existingEntry = timetableData.entries[cellId];
            
            if (existingEntry) {
                document.getElementById('subject').value = existingEntry.subject;
                document.getElementById('subjectCode').value = existingEntry.subjectCode;
                document.getElementById('faculty').value = existingEntry.faculty;
                deleteEntry.style.display = 'inline-flex';
            } else {
                // Clear form
                entryForm.reset();
                document.getElementById('dayInput').value = day;
                document.getElementById('timeSlotInput').value = timeSlot;
                deleteEntry.style.display = 'none';
            }
            
            // Show modal
            entryModal.style.display = 'block';
        }
        
        // Close entry modal
        function closeEntryModal() {
            entryModal.style.display = 'none';
        }
        
        // Handle entry form submission
        entryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const day = document.getElementById('dayInput').value;
            const timeSlot = document.getElementById('timeSlotInput').value;
            const subject = document.getElementById('subject').value;
            const subjectCode = document.getElementById('subjectCode').value;
            const faculty = document.getElementById('faculty').value;
            
            if (!day || !timeSlot || !subject || !subjectCode || !faculty) {
                showToast('Please fill all required fields', 'error');
                return;
            }
            
            // Create cell ID
            const cellId = `${day.toLowerCase()}-${timeSlot.replace(':', '').replace('-', '')}`;
            
            // Save entry data
            timetableData.entries[cellId] = {
                day: day,
                timeSlot: timeSlot,
                subject: subject,
                subjectCode: subjectCode,
                faculty: faculty
            };
            
            // Update cell content
            updateCellContent(cellId);
            
            // Close modal
            closeEntryModal();
            
            showToast('Entry saved successfully', 'success');
        });
        
        // Update cell content with entry data
        function updateCellContent(cellId) {
            const cell = document.getElementById(cellId);
            const entry = timetableData.entries[cellId];
            
            if (cell && entry) {
                cell.classList.add('has-entry');
                cell.querySelector('.cell-content').innerHTML = `
                    <div class="subject-code">${entry.subjectCode}</div>
                    <div class="subject-name">${entry.subject}</div>
                    <div class="cell-faculty">${entry.faculty}</div>
                `;
            }
        }
        
        // Delete entry
        deleteEntry.addEventListener('click', function() {
            const day = document.getElementById('dayInput').value;
            const timeSlot = document.getElementById('timeSlotInput').value;
            const cellId = `${day.toLowerCase()}-${timeSlot.replace(':', '').replace('-', '')}`;
            
            // Remove entry from data
            delete timetableData.entries[cellId];
            
            // Clear cell content
            const cell = document.getElementById(cellId);
            if (cell) {
                cell.classList.remove('has-entry');
                cell.querySelector('.cell-content').innerHTML = '';
            }
            
            // Close modal
            closeEntryModal();
            
            showToast('Entry deleted successfully', 'success');
        });
        
        // Close modal events
        closeModal.addEventListener('click', closeEntryModal);
        cancelModal.addEventListener('click', closeEntryModal);
        window.addEventListener('click', function(e) {
            if (e.target === entryModal) {
                closeEntryModal();
            }
        });
        
        // Save timetable to database
        function saveTimetable() {
            // Check if there are any entries
            if (Object.keys(timetableData.entries).length === 0) {
                showToast('Please add at least one timetable entry', 'error');
                return;
            }
            
            // Disable save button to prevent double submission
            const saveButton = document.getElementById('saveTimetable');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Send data to server
            fetch('save-timetable.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(timetableData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }
                
                // First try to get the response text
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Failed to parse JSON response:", text);
                        console.error("Parse error:", e);
                        // Return a formatted error response
                        return {
                            success: false,
                            message: "Invalid JSON response from server: " + text.substring(0, 100) + "..."
                        };
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const details = data.data.details || {};
                    const savedMsg = `Timetable saved successfully! Saved ${details.savedCount || 0} entries`;
                    
                    if (details.skippedCount > 0) {
                        showToast(savedMsg + `, skipped ${details.skippedCount} incomplete entries.`, 'success');
                    } else {
                        showToast(savedMsg, 'success');
                    }
                    
                    // Optionally redirect to view page after successful save
                    // setTimeout(() => {
                    //     window.location.href = 'admin-dashboard.php';
                    // }, 2000);
                } else {
                    showToast(data.message || 'Unknown error occurred', 'error');
                    console.error('Server error:', data.message);
                    if (data.errorDetails) {
                        console.error('Error details:', data.errorDetails);
                    }
                }
            })
            .catch(error => {
                console.error('Error saving timetable:', error);
                
                // Provide a user-friendly error message
                let errorMessage = 'An error occurred while saving the timetable';
                
                if (error.message) {
                    // Display more specific error message if available
                    errorMessage += ': ' + error.message;
                }
                
                if (errorMessage.length > 150) {
                    // Truncate very long error messages for display
                    errorMessage = errorMessage.substring(0, 150) + '...';
                }
                
                showToast(errorMessage, 'error');
            })
            .finally(() => {
                // Re-enable save button
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save Timetable';
            });
        }
        
        // Reset timetable grid
        function resetTimetableGrid() {
            if (confirm('Are you sure you want to reset the timetable grid? All unsaved entries will be lost.')) {
                // Clear timetable data
                timetableData.entries = {};
                
                // Clear all cells
                document.querySelectorAll('.timetable-cell').forEach(cell => {
                    cell.classList.remove('has-entry');
                    cell.querySelector('.cell-content').innerHTML = '';
                });
                
                showToast('Timetable grid has been reset', 'warning');
            }
        }
        
        // Add new time slot
        function addTimeSlot() {
            const newTimeSlot = prompt('Enter new time slot (format: HH:MM-HH:MM):');
            
            if (newTimeSlot && /^\d{1,2}:\d{2}-\d{1,2}:\d{2}$/.test(newTimeSlot)) {
                // Check if time slot already exists
                if (timeSlots.includes(newTimeSlot)) {
                    showToast('This time slot already exists in the timetable', 'error');
                    return;
                }
                
                // Add new time slot
                timeSlots.push(newTimeSlot);
                
                // Regenerate grid
                regenerateTimetableGrid();
                
                showToast('New time slot added', 'success');
            } else if (newTimeSlot) {
                showToast('Invalid time slot format. Please use HH:MM-HH:MM', 'error');
            }
        }
        
        // Edit time slot
        function editTimeSlot(index) {
            const currentTimeSlot = timeSlots[index];
            const newTimeSlot = prompt('Edit time slot (format: HH:MM-HH:MM):', currentTimeSlot);
            
            if (newTimeSlot && /^\d{1,2}:\d{2}-\d{1,2}:\d{2}$/.test(newTimeSlot)) {
                // Check if time slot already exists and it's not the same one being edited
                if (timeSlots.includes(newTimeSlot) && newTimeSlot !== currentTimeSlot) {
                    showToast('This time slot already exists in the timetable', 'error');
                    return;
                }
                
                // Save old time slot for entry updates
                const oldTimeSlot = timeSlots[index];
                
                // Update time slot
                timeSlots[index] = newTimeSlot;
                
                // Update any entries with this time slot
                updateTimeSlotInEntries(oldTimeSlot, newTimeSlot);
                
                // Regenerate grid
                regenerateTimetableGrid();
                
                showToast('Time slot updated', 'success');
            } else if (newTimeSlot) {
                showToast('Invalid time slot format. Please use HH:MM-HH:MM', 'error');
            }
        }
        
        // Remove time slot
        function removeTimeSlot(index) {
            if (confirm(`Are you sure you want to remove the time slot "${timeSlots[index]}"?`)) {
                // Get time slot being removed
                const removedTimeSlot = timeSlots[index];
                
                // Remove entries for this time slot
                removeEntriesForTimeSlot(removedTimeSlot);
                
                // Remove time slot
                timeSlots.splice(index, 1);
                
                // Check if there are any time slots left
                if (timeSlots.length === 0) {
                    showToast('Cannot remove all time slots. At least one must remain.', 'error');
                    timeSlots.push(removedTimeSlot); // Add it back
                    return;
                }
                
                // Regenerate grid
                regenerateTimetableGrid();
                
                showToast('Time slot removed', 'success');
            }
        }
        
        // Helper function to update time slot in entries
        function updateTimeSlotInEntries(oldTimeSlot, newTimeSlot) {
            // Create new entries object
            const newEntries = {};
            
            // Iterate through existing entries
            Object.entries(timetableData.entries).forEach(([cellId, entry]) => {
                if (entry.timeSlot === oldTimeSlot) {
                    // Update time slot
                    entry.timeSlot = newTimeSlot;
                    
                    // Create new cell ID
                    const day = entry.day;
                    const newCellId = `${day.toLowerCase()}-${newTimeSlot.replace(':', '').replace('-', '')}`;
                    
                    // Add to new entries
                    newEntries[newCellId] = entry;
                } else {
                    // Keep existing entry
                    newEntries[cellId] = entry;
                }
            });
            
            // Replace entries
            timetableData.entries = newEntries;
        }
        
        // Helper function to remove entries for a time slot
        function removeEntriesForTimeSlot(timeSlot) {
            // Create new entries object
            const newEntries = {};
            
            // Keep only entries for other time slots
            Object.entries(timetableData.entries).forEach(([cellId, entry]) => {
                if (entry.timeSlot !== timeSlot) {
                    newEntries[cellId] = entry;
                }
            });
            
            // Replace entries
            timetableData.entries = newEntries;
        }
        
        // Regenerate timetable grid preserving entries
        function regenerateTimetableGrid() {
            // Store the current entries
            const currentEntries = timetableData.entries;
            
            // Regenerate grid
            generateTimetableGrid();
            
            // Restore entries
            timetableData.entries = currentEntries;
            
            // Update cell contents
            Object.keys(currentEntries).forEach(cellId => {
                updateCellContent(cellId);
            });
        }
        
        // Add a new day row
        function addDayRow() {
            const newDay = prompt('Enter day name:');
            
            if (newDay && newDay.trim() !== '') {
                // Check if day already exists
                if (days.includes(newDay)) {
                    showToast('This day already exists in the timetable', 'error');
                    return;
                }
                
                // Add new day
                days.push(newDay);
                
                // Regenerate grid
                generateTimetableGrid();
                
                showToast('New day added', 'success');
            }
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            let icon = 'check-circle';
            if (type === 'error') icon = 'exclamation-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            
            toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
            
            toastContainer.appendChild(toast);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    });
    </script>
</body>
</html> 