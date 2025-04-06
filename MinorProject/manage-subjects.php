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

// Handle Add Subject
if (isset($_POST['add_subject'])) {
    $subjectName = trim($_POST['subject_name']);
    $subjectCode = trim($_POST['subject_code']);
    $facultyId = !empty($_POST['faculty_id']) ? $_POST['faculty_id'] : null;
    
    if (!empty($subjectName) && !empty($subjectCode) && !empty($facultyId)) {
        try {
            $result = $dao->addSubject($subjectName, $subjectCode, $facultyId);
            if ($result) {
                $alertMessage = "Subject added successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to add subject.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error adding subject: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Subject name, code, and faculty are required.";
        $alertType = "warning";
    }
}

// Handle Edit Subject
if (isset($_POST['edit_subject'])) {
    $subjectId = $_POST['subject_id'];
    $subjectName = trim($_POST['subject_name']);
    $subjectCode = trim($_POST['subject_code']);
    $facultyId = !empty($_POST['faculty_id']) ? $_POST['faculty_id'] : null;
    
    if (!empty($subjectId) && !empty($subjectName) && !empty($subjectCode) && !empty($facultyId)) {
        try {
            $result = $dao->updateSubject($subjectId, $subjectName, $subjectCode, $facultyId);
            if ($result) {
                $alertMessage = "Subject updated successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to update subject.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error updating subject: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Subject ID, name, code, and faculty are required.";
        $alertType = "warning";
    }
}

// Handle Delete Subject
if (isset($_POST['delete_subject'])) {
    $subjectId = $_POST['subject_id'];
    
    if (!empty($subjectId)) {
        try {
            $result = $dao->deleteSubject($subjectId);
            if ($result) {
                $alertMessage = "Subject deleted successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to delete subject.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error deleting subject: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Subject ID is required.";
        $alertType = "warning";
    }
}

// Fetch all subjects, faculty, and semesters
$subjectList = $dao->getAllSubjects();
$facultyList = $dao->getAllFaculty();
$semesterList = $dao->getAllSemesters();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - IIPS TTMS</title>
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
                        <h5 class="mb-0">Manage Subjects</h5>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addSubjectModal">
                            Add New Subject
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subjectList)): ?>
                            <div class="empty-state">
                                <div class="icon">📚</div>
                                <h4>No Subjects Found</h4>
                                <p>No subjects have been added yet. Click 'Add New Subject' to get started.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Faculty</th>
                                            <th>Semester</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjectList as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['faculty_name'] ?? 'Not Assigned'); ?></td>
                                                <td><?php echo htmlspecialchars($subject['semester_no'] ?? 'Not Assigned'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info edit-subject-btn" 
                                                        data-id="<?php echo $subject['subject_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                                        data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                        data-faculty="<?php echo $subject['faculty_id'] ?? ''; ?>"
                                                        data-toggle="modal" data-target="#editSubjectModal">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-subject-btn"
                                                        data-id="<?php echo $subject['subject_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                                        data-toggle="modal" data-target="#deleteSubjectModal">
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
    
    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="subject_name">Subject Name</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                        </div>
                        <div class="form-group">
                            <label for="subject_code">Subject Code</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                        </div>
                        <div class="form-group">
                            <label for="faculty_id">Faculty (Optional)</label>
                            <select class="form-control" id="faculty_id" name="faculty_id">
                                <option value="">-- Select Faculty --</option>
                                <?php foreach ($facultyList as $faculty): ?>
                                    <option value="<?php echo $faculty['faculty_id']; ?>">
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_subject">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_subject_id" name="subject_id">
                        <div class="form-group">
                            <label for="edit_subject_name">Subject Name</label>
                            <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_subject_code">Subject Code</label>
                            <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_faculty_id">Faculty (Optional)</label>
                            <select class="form-control" id="edit_faculty_id" name="faculty_id">
                                <option value="">-- Select Faculty --</option>
                                <?php foreach ($facultyList as $faculty): ?>
                                    <option value="<?php echo $faculty['faculty_id']; ?>">
                                        <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_subject">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" role="dialog" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubjectModalLabel">Delete Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="delete_subject_id" name="subject_id">
                        <p>Are you sure you want to delete <strong id="delete_subject_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone and will remove all data associated with this subject.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_subject">Delete Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Edit Subject Modal
            $('.edit-subject-btn').click(function() {
                $('#edit_subject_id').val($(this).data('id'));
                $('#edit_subject_name').val($(this).data('name'));
                $('#edit_subject_code').val($(this).data('code'));
                $('#edit_faculty_id').val($(this).data('faculty'));
            });
            
            // Delete Subject Modal
            $('.delete-subject-btn').click(function() {
                $('#delete_subject_id').val($(this).data('id'));
                $('#delete_subject_name').text($(this).data('name'));
            });
        });
    </script>
</body>
</html> 