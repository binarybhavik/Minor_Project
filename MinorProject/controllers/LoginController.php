<?php
// Note: SessionUtils.php is already included from the controller.php proxy file
// require_once __DIR__ . '/../utils/SessionUtils.php';

// Define admin and user credentials (hardcoded for now, same as in the Java version)
define('ADMIN_USERNAME', 'admin@123.iips');
define('ADMIN_PASSWORD', 'admin@123');
define('USER_USERNAME', 'user@123.iips');
define('USER_PASSWORD', 'user@123');

// Process login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['userId'] ?? '';
    $password = $_POST['password'] ?? '';
    $errorMessage = '';
    
    if (!empty($username) && !empty($password)) {
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            setUserSession($username, 'admin');
            header('Location: admin-dashboard.php');
            exit;
        } elseif ($username === USER_USERNAME && $password === USER_PASSWORD) {
            setUserSession($username, 'user');
            header('Location: user-dashboard.php');
            exit;
        } else {
            $errorMessage = 'Invalid username or password!';
        }
    } else {
        $errorMessage = 'Username and password are required!';
    }
    
    // If login failed, return to login page with error message
    $_SESSION['error_message'] = $errorMessage;
    header('Location: index.php');
    exit;
}
?> 