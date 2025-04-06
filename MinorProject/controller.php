<?php
// This file acts as a proxy for all controller requests

// Start session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/utils/SessionUtils.php';

// Create log file for debugging
$logFile = fopen(__DIR__ . '/controller_log.txt', 'a');
fwrite($logFile, date('Y-m-d H:i:s') . " - Controller started\n");

// Get the action from the URL (controller name)
$action = $_GET['action'] ?? '';

// Log the action
fwrite($logFile, "Action: " . $action . "\n");
fwrite($logFile, "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n");
fwrite($logFile, "GET parameters: " . print_r($_GET, true) . "\n");

// Default action is login
if (empty($action)) {
    $action = 'Login';
    fwrite($logFile, "Using default action: Login\n");
}

// Validate the controller name to prevent directory traversal
if (!preg_match('/^[a-zA-Z0-9_]+$/', $action)) {
    fwrite($logFile, "Invalid controller name: " . $action . "\n");
    header("HTTP/1.0 400 Bad Request");
    exit('Invalid controller name');
}

// Controller file path
$controllerFile = __DIR__ . '/controllers/' . $action . 'Controller.php';

// Check if controller exists
if (file_exists($controllerFile)) {
    fwrite($logFile, "Controller file found: " . $controllerFile . "\n");
    
    try {
        // Include the controller file
        require_once $controllerFile;
        
        // Instantiate the controller and process the request
        $controllerClass = $action . 'Controller';
        
        if (class_exists($controllerClass)) {
            fwrite($logFile, "Controller class exists: " . $controllerClass . "\n");
            $controller = new $controllerClass();
            
            if (method_exists($controller, 'processRequest')) {
                fwrite($logFile, "Process request method exists, calling it\n");
                $controller->processRequest();
                fwrite($logFile, "Controller method executed successfully\n");
            } else {
                $error = "Controller method 'processRequest' not found in " . $controllerClass;
                fwrite($logFile, $error . "\n");
                echo $error;
            }
        } else {
            $error = "Controller class '$controllerClass' not found in " . $controllerFile;
            fwrite($logFile, $error . "\n");
            echo $error;
        }
    } catch (Throwable $e) {
        $error = "Error processing controller: " . $e->getMessage() . "\n" . 
                 "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n" .
                 "Stack trace: " . $e->getTraceAsString();
        fwrite($logFile, $error . "\n");
        echo "<h1>Application Error</h1>";
        echo "<p>An error occurred while processing your request:</p>";
        echo "<pre>" . htmlspecialchars($error) . "</pre>";
    }
    
} else {
    fwrite($logFile, "Controller not found: " . $controllerFile . "\n");
    header("HTTP/1.0 404 Not Found");
    exit('Controller not found: ' . htmlspecialchars($action));
}

fwrite($logFile, "Controller processing complete\n");
fclose($logFile);
?> 