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

// Handle Add Room
if (isset($_POST['add_room'])) {
    $roomNumber = trim($_POST['room_number']);
    
    if (!empty($roomNumber)) {
        try {
            $result = $dao->addRoom($roomNumber);
            if ($result) {
                $alertMessage = "Room added successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to add room.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error adding room: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Room number is required.";
        $alertType = "warning";
    }
}

// Handle Edit Room
if (isset($_POST['edit_room'])) {
    $roomId = $_POST['room_id'];
    $roomNumber = trim($_POST['room_number']);
    
    if (!empty($roomId) && !empty($roomNumber)) {
        try {
            $result = $dao->updateRoom($roomId, $roomNumber);
            if ($result) {
                $alertMessage = "Room updated successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to update room.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error updating room: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Room ID and number are required.";
        $alertType = "warning";
    }
}

// Handle Delete Room
if (isset($_POST['delete_room'])) {
    $roomId = $_POST['room_id'];
    
    if (!empty($roomId)) {
        try {
            $result = $dao->deleteRoom($roomId);
            if ($result) {
                $alertMessage = "Room deleted successfully!";
                $alertType = "success";
            } else {
                $alertMessage = "Failed to delete room.";
                $alertType = "danger";
            }
        } catch (Exception $e) {
            $logger->error("Error deleting room: " . $e->getMessage());
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    } else {
        $alertMessage = "Room ID is required.";
        $alertType = "warning";
    }
}

// Fetch all rooms
$roomList = $dao->getAllRooms();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - IIPS TTMS</title>
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
                        <h5 class="mb-0">Manage Rooms</h5>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRoomModal">
                            Add New Room
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($roomList)): ?>
                            <div class="empty-state">
                                <div class="icon">🏢</div>
                                <h4>No Rooms Found</h4>
                                <p>No rooms have been added yet. Click 'Add New Room' to get started.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Room Number</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($roomList as $room): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($room['room_id']); ?></td>
                                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info edit-room" 
                                                            data-id="<?php echo $room['room_id']; ?>" 
                                                            data-number="<?php echo htmlspecialchars($room['room_number']); ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-room" 
                                                            data-id="<?php echo $room['room_id']; ?>" 
                                                            data-number="<?php echo htmlspecialchars($room['room_number']); ?>">
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
    
    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" role="dialog" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoomModalLabel">Add New Room</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="room_number">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <input type="hidden" id="edit_room_id" name="room_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_room_number">Room Number</label>
                            <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_room" class="btn btn-primary">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Room Modal -->
    <div class="modal fade" id="deleteRoomModal" tabindex="-1" role="dialog" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRoomModalLabel">Delete Room</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <input type="hidden" id="delete_room_id" name="room_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete room <span id="delete_room_number"></span>?</p>
                        <p class="text-danger">This action cannot be undone and may affect timetable entries using this room.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_room" class="btn btn-danger">Delete Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Room
            document.querySelectorAll('.edit-room').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const number = this.getAttribute('data-number');
                    
                    document.getElementById('edit_room_id').value = id;
                    document.getElementById('edit_room_number').value = number;
                    
                    $('#editRoomModal').modal('show');
                });
            });
            
            // Delete Room
            document.querySelectorAll('.delete-room').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const number = this.getAttribute('data-number');
                    
                    document.getElementById('delete_room_id').value = id;
                    document.getElementById('delete_room_number').textContent = number;
                    
                    $('#deleteRoomModal').modal('show');
                });
            });
        });
    </script>
</body>
</html> 