<?php
require_once __DIR__ . '/utils/SessionUtils.php';
require_once __DIR__ . '/models/TimetableEntry.php';
require_once __DIR__ . '/dao/TimetableDAO.php';

// Get base URL
$baseUrl = SessionUtils::getBaseUrl();

// Ensure user is logged in and has admin role
SessionUtils::requireAdmin();

// Initialize DAO
$dao = TimetableDAO::getInstance();

// Calculate stats
try {
    $facultyCount = $dao->getCount('faculty', 'faculty_id');
    $subjectsCount = $dao->getCount('subjects', 'subject_id');
    $roomsCount = $dao->getCount('rooms', 'room_id');
    
    // If counts are 0, try to check if tables exist and create them if needed
    if ($facultyCount == 0) {
        $dao->createFacultyTableIfNotExists();
        $facultyCount = $dao->getCount('faculty', 'faculty_id');
    }
    
    if ($subjectsCount == 0) {
        $dao->createSubjectsTableIfNotExists();
        $subjectsCount = $dao->getCount('subjects', 'subject_id');
    }
    
    if ($roomsCount == 0) {
        $dao->createRoomsTableIfNotExists();
        $roomsCount = $dao->getCount('rooms', 'room_id');
    }
} catch (Exception $e) {
    error_log("Error calculating stats: " . $e->getMessage());
    $facultyCount = 0;
    $subjectsCount = 0;
    $roomsCount = 0;
}

// Get timetable entries from session (if any)
$timetableEntries = $_SESSION['timetableEntries'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IIPS Timetable Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --light-color: #f5f6fa;
            --light-gray: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --text-dark: #2c3e50;
            --text-light: #718096;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: var(--light-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr;
            grid-template-columns: 250px 1fr;
            grid-template-areas:
                "header header"
                "sidebar main";
        }

        /* Header */
        header {
            grid-area: header;
            background-color: var(--secondary-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            z-index: 1000;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header-logo i {
            font-size: 1.5rem;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: white;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 500;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem 0;
            height: 100%;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-section {
            margin-bottom: 2rem;
            padding: 0 1.5rem;
        }

        .sidebar-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu-item {
            margin-bottom: 0.25rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-dark);
            border-radius: 6px;
            transition: var(--transition);
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-link:hover {
            background-color: var(--light-gray);
        }

        .sidebar-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Main Content */
        .main-content {
            grid-area: main;
            padding: 2rem;
            overflow-y: auto;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .content-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .dashboard-actions {
            display: flex;
            gap: 1rem;
        }

        /* Cards */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-right: 1rem;
            color: white;
        }

        .stat-icon-success {
            background-color: var(--success-color);
        }

        .stat-icon-warning {
            background-color: var(--warning-color);
        }

        .stat-icon-danger {
            background-color: var(--danger-color);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            color: var(--text-dark);
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .action-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .action-description {
            color: var(--text-light);
            font-size: 0.9rem;
            text-align: center;
        }

        /* Recent Timetables */
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background-color: var(--light-gray);
            font-weight: 600;
        }

        .data-table tbody tr {
            transition: var(--transition);
        }

        .data-table tbody tr:hover {
            background-color: var(--light-gray);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .table-btn {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .table-btn-view {
            background-color: var(--info-color);
        }

        .table-btn-edit {
            background-color: var(--warning-color);
        }

        .table-btn-delete {
            background-color: var(--danger-color);
        }

        .table-btn:hover {
            opacity: 0.9;
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border-color);
        }

        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state-desc {
            margin-bottom: 1.5rem;
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
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #34495e;
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

        /* Responsive styles */
        @media (max-width: 992px) {
            body {
                grid-template-rows: auto auto 1fr;
                grid-template-columns: 1fr;
                grid-template-areas:
                    "header"
                    "sidebar"
                    "main";
            }

            .sidebar {
                height: auto;
                display: flex;
                flex-wrap: wrap;
                padding: 1rem;
            }

            .sidebar-section {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <a href="admin-dashboard.php" class="header-logo">
            <i class="fas fa-calendar-alt"></i>
            <span>IIPS Timetable Management</span>
        </a>
        <div class="user-actions">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <h3 class="sidebar-title">Navigation</h3>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="admin-dashboard.php" class="sidebar-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="admin-timetable-create.php" class="sidebar-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create Timetable</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="admin-timetable-update.php" class="sidebar-link">
                        <i class="fas fa-edit"></i>
                        <span>Update Timetable</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage-faculty.php" class="sidebar-link">
                        <i class="fas fa-users"></i>
                        <span>Manage Faculties</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage-subjects.php" class="sidebar-link">
                        <i class="fas fa-book"></i>
                        <span>Manage Subjects</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage-rooms.php" class="sidebar-link">
                        <i class="fas fa-building"></i>
                        <span>Manage Rooms</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1 class="content-title">Admin Dashboard</h1>
            <div class="dashboard-actions">
                <a href="admin-timetable-create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>Create Timetable</span>
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $facultyCount; ?></div>
                    <div class="stat-label">Faculty Members</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-warning">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $subjectsCount; ?></div>
                    <div class="stat-label">Subjects</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-danger">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $roomsCount; ?></div>
                    <div class="stat-label">Rooms</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="admin-timetable-create.php" class="action-card">
                <div class="action-icon" style="background-color: var(--primary-color);">
                    <i class="fas fa-plus"></i>
                </div>
                <h3 class="action-title">Create Timetable</h3>
                <p class="action-description">Create a new timetable with the visual editor</p>
            </a>
            
            <a href="user-dashboard.php" class="action-card">
                <div class="action-icon" style="background-color: var(--success-color);">
                    <i class="fas fa-table"></i>
                </div>
                <h3 class="action-title">View Timetables</h3>
                <p class="action-description">Browse existing timetables</p>
            </a>
            
            <a href="admin-timetable-update.php" class="action-card">
                <div class="action-icon" style="background-color: var(--warning-color);">
                    <i class="fas fa-edit"></i>
                </div>
                <h3 class="action-title">Update Timetable</h3>
                <p class="action-description">Edit or update existing timetables</p>
            </a>
            
            <a href="manage-faculty.php" class="action-card">
                <div class="action-icon" style="background-color: var(--warning-color);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 class="action-title">Manage Faculty</h3>
                <p class="action-description">Add, edit or delete faculty members</p>
            </a>
            
            <a href="manage-subjects.php" class="action-card">
                <div class="action-icon" style="background-color: var(--info-color);">
                    <i class="fas fa-book"></i>
                </div>
                <h3 class="action-title">Manage Subjects</h3>
                <p class="action-description">Add, edit or delete subjects</p>
            </a>
            
            <a href="manage-rooms.php" class="action-card">
                <div class="action-icon" style="background-color: var(--danger-color);">
                    <i class="fas fa-door-open"></i>
                </div>
                <h3 class="action-title">Manage Rooms</h3>
                <p class="action-description">Add, edit or delete rooms</p>
            </a>
        </div>

        <!-- Recent Timetables -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-header-title">
                    <i class="fas fa-calendar-week"></i>
                    <span>Recent Timetables</span>
                </h2>
                <a href="user-dashboard.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i>
                    <span>View All</span>
                </a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <?php if (empty($timetableEntries)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times empty-state-icon"></i>
                            <h3 class="empty-state-title">No timetable entries found</h3>
                            <p class="empty-state-desc">Start by creating a new timetable to see entries here.</p>
                            <a href="admin-timetable-create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <span>Create Timetable</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Room</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timetableEntries as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['course'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['semester'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['roomNumber'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['day'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(($entry['timeStart'] ?? '') . ' - ' . ($entry['timeEnd'] ?? '')); ?></td>
                                    <td>Today</td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="user-dashboard.php?course=<?php echo urlencode($entry['course'] ?? ''); ?>&semester=<?php echo urlencode($entry['semester'] ?? ''); ?>" class="table-btn table-btn-view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="table-btn table-btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="table-btn table-btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Add active class to current menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentLocation = window.location.pathname;
            const menuItems = document.querySelectorAll('.sidebar-link');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (currentLocation.includes(href) && href !== '#') {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html> 