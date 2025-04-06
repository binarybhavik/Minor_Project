<?php
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/utils/Logger.php';
require_once __DIR__ . '/utils/SchemaCheck.php';

// Enable full error reporting during initialization
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize logger
$logger = new Logger('Initialization');
$logger->info('Starting application initialization');

try {
    // Check database connection
    $db = Database::getInstance()->getConnection();
    $logger->info('Database connection successful');
    
    // Check and create database schema
    $schemaCheck = new SchemaCheck();
    $schemaCheck->checkAndCreateTables();
    $logger->info('Database schema check completed successfully');
    
    echo "Initialization completed successfully!";
    
} catch (Exception $e) {
    $logger->error('Initialization failed: ' . $e->getMessage());
    echo "Initialization failed: " . $e->getMessage();
}
?> 