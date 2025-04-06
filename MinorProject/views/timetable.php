<?php
// Basic error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a simple log
$logFile = fopen(__DIR__ . '/../timetable_view.log', 'a');
fwrite($logFile, date('Y-m-d H:i:s') . " - Timetable view accessed\n");

// Include required files
require_once __DIR__ . '/../utils/SessionUtils.php';
require_once __DIR__ . '/../models/TimetableEntry.php';

// Ensure user is logged in
requireLogin();

// Get timetable entries from session
$timetableEntries = $_SESSION['timetable_entries'] ?? [];
$filterParams = $_SESSION['filter_params'] ?? [];

// Get the filter parameters for display
$semester = $filterParams['semester'] ?? '';
$course = $filterParams['course'] ?? '';
$day = $filterParams['day'] ?? '';
$faculty = $filterParams['faculty'] ?? '';
$subject = $filterParams['subject'] ?? '';
$timeSlot = $filterParams['timeSlot'] ?? '';
$room = $filterParams['room'] ?? '';

// Create a readable filter summary
$filterSummary = [];
if (!empty($semester)) $filterSummary[] = "Semester: $semester";
if (!empty($course)) $filterSummary[] = "Course: $course";
if (!empty($day)) $filterSummary[] = "Day: $day";
if (!empty($faculty)) $filterSummary[] = "Faculty: $faculty";
if (!empty($subject)) $filterSummary[] = "Subject: $subject";
if (!empty($timeSlot)) $filterSummary[] = "Time: $timeSlot";
if (!empty($room)) $filterSummary[] = "Room: $room";

$filterText = empty($filterSummary) ? "" : " (" . implode(", ", $filterSummary) . ")";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Timetable</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1a4d7c;
            color: white;
            padding: 15px 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            margin-bottom: 20px;
        }
        .timetable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-button {
            background-color: #1a4d7c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .download-bar {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .download-btn {
            background-color: #1a4d7c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .download-btn:hover {
            background-color: #155d9e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1a4d7c;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e2e2e2;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .loading-spinner {
            display: none;
            margin: 20px auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Academic Timetable<?php echo $filterText; ?></h1>
        </div>
        
        <div class="timetable-header">
            <a href="user-dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Search</a>
        </div>
        
        <div class="download-bar">
            <div>
                <button onclick="downloadPDF()" class="download-btn"><i class="fas fa-file-pdf"></i> PDF</button>
                <button onclick="downloadExcel()" class="download-btn"><i class="fas fa-file-excel"></i> Excel</button>
                <button onclick="downloadCSV()" class="download-btn"><i class="fas fa-file-csv"></i> CSV</button>
            </div>
        </div>
        
        <?php if (empty($timetableEntries)): ?>
            <div class="no-results">
                <h3>No timetable entries available</h3>
                <p>Please adjust your search criteria and try again.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="timetableTable">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Faculty</th>
                            <th>Semester</th>
                            <th>Course</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timetableEntries as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry->getDay()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getTimeStart()) . ' - ' . htmlspecialchars($entry->getTimeEnd()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getSubjectCode()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getSubjectName()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getFacultyName()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getSemester()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getCourse()); ?></td>
                                <td><?php echo htmlspecialchars($entry->getRoomNumber()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div id="loading-spinner" class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x"></i>
            <p>Processing your request...</p>
        </div>
    </div>
    
    <!-- Include libraries for export functionality -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // Function to download as PDF
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(16);
            doc.text('Academic Timetable<?php echo addslashes($filterText); ?>', 14, 15);
            
            // Create the table
            doc.autoTable({
                html: '#timetableTable',
                startY: 25,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [26, 77, 124],
                    textColor: 255
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                }
            });
            
            // Save the PDF
            doc.save('timetable.pdf');
        }
        
        // Function to download as Excel
        function downloadExcel() {
            const table = document.getElementById('timetableTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: 'Timetable'});
            XLSX.writeFile(wb, 'timetable.xlsx');
        }
        
        // Function to download as CSV
        function downloadCSV() {
            const table = document.getElementById('timetableTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            const csv = rows.map(row => {
                const cells = Array.from(row.querySelectorAll('th, td'));
                return cells.map(cell => `"${cell.textContent.trim()}"`).join(',');
            }).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'timetable.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>

    <?php
    fwrite($logFile, "Timetable view rendered successfully\n");
    fclose($logFile);
    ?>
</body>
</html> 