<?php
// Set title
$pageTitle = "Enable MySQLi Extension - IIPS TTMS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        pre {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
        }
        .step {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        .step h3 {
            margin-top: 0;
            color: #007bff;
        }
        .alert {
            margin: 20px 0;
        }
        img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Enable MySQLi Extension</h1>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> The mysqli extension is not loaded. This extension is required for the IIPS TTMS application to work properly.
        </div>
        
        <p>The MySQLi extension is required for PHP to connect to MySQL databases. Follow these steps to enable it:</p>
        
        <div class="step">
            <h3><i class="fas fa-1"></i> Locate your php.ini file</h3>
            <p>The php.ini file is typically located in:</p>
            <pre>C:\xampp\php\php.ini</pre>
            <p>Open this file in a text editor like Notepad or VS Code.</p>
        </div>
        
        <div class="step">
            <h3><i class="fas fa-2"></i> Enable the mysqli extension</h3>
            <p>In the php.ini file, find the line:</p>
            <pre>;extension=mysqli</pre>
            <p>Remove the semicolon (;) at the beginning of the line to uncomment it:</p>
            <pre>extension=mysqli</pre>
            <p>This enables the MySQLi extension.</p>
        </div>
        
        <div class="step">
            <h3><i class="fas fa-3"></i> Save the file</h3>
            <p>Save the php.ini file after making the change.</p>
        </div>
        
        <div class="step">
            <h3><i class="fas fa-4"></i> Restart Apache server</h3>
            <p>Open the XAMPP Control Panel and restart the Apache server:</p>
            <ol>
                <li>Click the Stop button next to Apache</li>
                <li>Once it stops, click the Start button to restart it</li>
            </ol>
        </div>
        
        <div class="step">
            <h3><i class="fas fa-5"></i> Verify the extension is enabled</h3>
            <p>You can verify that the extension is enabled by visiting:</p>
            <a href="phpinfo.php" class="btn btn-info">Check PHP Info</a>
            <p class="mt-2">Look for the "mysqli" section in the PHP info page.</p>
        </div>
        
        <div class="alert alert-success">
            <p><strong>Once completed:</strong> Return to the <a href="index.php">homepage</a> and try again.</p>
        </div>
        
        <h2>Common Issues</h2>
        
        <div class="accordion" id="troubleshootingAccordion">
            <div class="card">
                <div class="card-header" id="whiteScreenHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#whiteScreenCollapse">
                            White Screen When Creating Timetable
                        </button>
                    </h2>
                </div>
                <div id="whiteScreenCollapse" class="collapse" aria-labelledby="whiteScreenHeading" data-parent="#troubleshootingAccordion">
                    <div class="card-body">
                        <p>If you're seeing a white screen when creating a timetable, it's likely due to:</p>
                        <ul>
                            <li>The mysqli extension not being enabled (follow the steps above)</li>
                            <li>Database connection issues</li>
                            <li>PHP errors that are not being displayed</li>
                        </ul>
                        <p>After enabling mysqli, check the application logs in the logs/ directory for more specific error messages.</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header" id="databaseHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#databaseCollapse">
                            Database Connection Problems
                        </button>
                    </h2>
                </div>
                <div id="databaseCollapse" class="collapse" aria-labelledby="databaseHeading" data-parent="#troubleshootingAccordion">
                    <div class="card-body">
                        <p>If you're still experiencing database connection issues after enabling mysqli:</p>
                        <ol>
                            <li>Make sure MySQL/MariaDB is running in XAMPP Control Panel</li>
                            <li>Check database credentials in utils/Database.php</li>
                            <li>Ensure the database exists (default is usually 'ttms')</li>
                            <li>Check that your database user has proper permissions</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 