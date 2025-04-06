<?php
require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/dao/TimetableDAO.php';

// Start the session if not started
SessionUtils::startSessionIfNeeded();

// Enforce admin role access
SessionUtils::requireAdmin();

// Initialize DAO
$dao = TimetableDAO::getInstance();

// Get db connection
$db = $dao->getConnection();

// Message variables
$success = '';
$error = '';

// Handle form submission for force refresh
if (isset($_POST['refresh_semesters'])) {
    try {
        // First, check if we need to truncate (recreate) the table
        if (isset($_POST['recreate_table']) && $_POST['recreate_table'] == 1) {
            // Truncate the table to start fresh
            $db->query("TRUNCATE TABLE semesters");
            $success .= "Semesters table cleared successfully. ";
        }
        
        // All semester options to ensure they exist
        $requiredSemesters = [
            'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X',
            'I-Odd', 'III-Odd', 'V-Odd', 'VII-Odd', 'IX-Odd',
            'II-Even', 'IV-Even', 'VI-Even', 'VIII-Even', 'X-Even'
        ];
        
        // Insert semester options, skipping existing ones
        $stmt = $db->prepare("INSERT IGNORE INTO semesters (semester_no) VALUES (?)");
        $insertCount = 0;
        
        foreach ($requiredSemesters as $sem) {
            $stmt->bind_param('s', $sem);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $insertCount++;
                }
            }
        }
        $stmt->close();
        
        $success .= "$insertCount new semester options added.";
        
    } catch (Exception $e) {
        $error = "Error updating semesters: " . $e->getMessage();
    }
}

// Get current semester list
$semesterList = $dao->getAllSemesters();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Semesters - Admin Panel</title>
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
        
        /* Alerts */
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Forms */
        form {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .form-check input {
            margin-right: 0.5rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            font-weight: 400;
            color: #212529;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            background-color: transparent;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-danger {
            color: #fff;
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        th, td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
            text-align: left;
        }
        
        thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: var(--secondary-color);
            color: white;
        }
        
        tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.05);
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
                <span><i class="fas fa-list"></i> Update Semester Options</span>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <p>This page allows you to update the list of semesters available in the dropdown menus.</p>
                
                <form method="post" action="">
                    <div class="form-check">
                        <input type="checkbox" id="recreate_table" name="recreate_table" value="1">
                        <label for="recreate_table">Recreate table (This will remove all existing semester entries first)</label>
                    </div>
                    
                    <button type="submit" name="refresh_semesters" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Refresh Semester Options
                    </button>
                </form>
                
                <h3>Current Semester Options</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($semesterList)): ?>
                                <tr>
                                    <td colspan="2">No semesters found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($semesterList as $sem): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sem['semester_id']); ?></td>
                                        <td><?php echo htmlspecialchars($sem['semester_no']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <a href="admin-timetable-create.php" class="btn btn-primary" onclick="window.history.back(); return false;">
                        <i class="fas fa-arrow-left"></i> Back to Create Timetable
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 