<?php
// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Create connection without database
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    
    // Split the SQL file into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each query
    foreach ($queries as $query) {
        if (!empty($query)) {
            if (!$conn->query($query)) {
                echo "Error executing query: " . $conn->error . "\n";
            }
        }
    }
    
    echo "Database setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 