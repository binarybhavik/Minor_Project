<?php
// Include required files
require_once 'utils/SessionUtils.php';
require_once 'dao/TimetableDAO.php';
require_once 'utils/Logger.php';

// Start session
SessionUtils::startSessionIfNeeded();

// Check if admin is logged in
if (!SessionUtils::isAdminLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Initialize DAO and logger
$dao = TimetableDAO::getInstance();
$logger = new Logger();

// Process form submissions
$alertMessage = '';
$alertType = '';

// Handle Add Faculty
if (isset($_POST['add_faculty'])) {
    $facultyName = trim($_POST['faculty_name']);
    
    if (!empty($facultyName)) {
        try {
            $result = $dao->addFaculty($facultyName);
            if ($result) {
                $alertMessage = "Faculty added successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to add faculty.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error adding faculty: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Faculty name is required.";
        $alertType = "warning";
    }
}

// Handle Edit Faculty
if (isset($_POST['edit_faculty'])) {
    $facultyId = $_POST['faculty_id'];
    $facultyName = trim($_POST['faculty_name']);
    
    if (!empty($facultyId) && !empty($facultyName)) {
        try {
            $result = $dao->updateFaculty($facultyId, $facultyName);
            if ($result) {
                $alertMessage = "Faculty updated successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to update faculty.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error updating faculty: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Faculty ID and name are required.";
        $alertType = "warning";
    }
}

// Handle Delete Faculty
if (isset($_POST['delete_faculty'])) {
    $facultyId = $_POST['faculty_id'];
    
    if (!empty($facultyId)) {
        try {
            $result = $dao->deleteFaculty($facultyId);
            if ($result) {
                $alertMessage = "Faculty deleted successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to delete faculty.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error deleting faculty: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Faculty ID is required.";
        $alertType = "warning";
    }
}

// Fetch all faculty
$facultyList = $dao->getAllFaculty();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - IIPS TTMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #f8f9fa;
            --text-color: #333;
            --border-radius: 5px;
        }
        
        body {
            background-color: var(--background-color);
            color: var(--text-color);
        }
        
        .header {
            background-color: var(--secondary-color);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        
        .navbar-brand {
            color: white;
            font-weight: bold;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .nav-link:hover {
            color: white;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .modal-header {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            background-color: #f9f9f9;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .empty-state .icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1>IIPS Timetable Management System</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="admin-dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <span class="navbar-brand">Admin Panel</span>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-dashboard.php">Dashboard</a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- Alert Messages -->
        <?php if (!empty($alertMessage)): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alertMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Manage Faculty</h5>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addFacultyModal">
                            Add New Faculty
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($facultyList)): ?>
                            <div class="empty-state">
                                <div class="icon">📚</div>
                                <h4>No Faculty Found</h4>
                                <p>No faculty members have been added yet. Click 'Add New Faculty' to get started.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($facultyList as $faculty): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($faculty['faculty_id']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['faculty_name']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info edit-faculty" 
                                                            data-id="<?php echo $faculty['faculty_id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-faculty" 
                                                            data-id="<?php echo $faculty['faculty_id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Faculty Modal -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1" role="dialog" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFacultyModalLabel">Add New Faculty</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="faculty_name">Faculty Name</label>
                            <input type="text" class="form-control" id="faculty_name" name="faculty_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_faculty" class="btn btn-primary">Add Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Faculty Modal -->
    <div class="modal fade" id="editFacultyModal" tabindex="-1" role="dialog" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFacultyModalLabel">Edit Faculty</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <input type="hidden" id="edit_faculty_id" name="faculty_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_faculty_name">Faculty Name</label>
                            <input type="text" class="form-control" id="edit_faculty_name" name="faculty_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_faculty" class="btn btn-primary">Update Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Faculty Modal -->
    <div class="modal fade" id="deleteFacultyModal" tabindex="-1" role="dialog" aria-labelledby="deleteFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFacultyModalLabel">Delete Faculty</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <input type="hidden" id="delete_faculty_id" name="faculty_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the faculty member: <span id="delete_faculty_name"></span>?</p>
                        <p class="text-danger">This action cannot be undone and may affect associated subjects.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_faculty" class="btn btn-danger">Delete Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Faculty
            document.querySelectorAll('.edit-faculty').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    document.getElementById('edit_faculty_id').value = id;
                    document.getElementById('edit_faculty_name').value = name;
                    
                    $('#editFacultyModal').modal('show');
                });
            });
            
            // Delete Faculty
            document.querySelectorAll('.delete-faculty').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    document.getElementById('delete_faculty_id').value = id;
                    document.getElementById('delete_faculty_name').textContent = name;
                    
                    $('#deleteFacultyModal').modal('show');
                });
            });
        });
    </script>
</body>
</html> 