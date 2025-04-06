<?php
require_once __DIR__ . '/utils/SessionUtils.php';

// Start the session
SessionUtils::startSessionIfNeeded();

// Check if user is already logged in
if (SessionUtils::isLoggedIn()) {
    // Redirect to appropriate dashboard
    if (SessionUtils::isAdmin()) {
        header("Location: admin-dashboard.php");
    } else {
        header("Location: user-dashboard.php");
    }
    exit;
}

// Check for login form submission
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password';
    } else {
        // Dummy authentication - replace with actual database check
        // For testing purposes only
        if ($username === 'admin' && $password === 'admin123') {
            SessionUtils::setUserSession(1, $username, 'admin');
            header("Location: admin-dashboard.php");
            exit;
        } elseif ($username === 'user' && $password === 'user123') {
            SessionUtils::setUserSession(2, $username, 'user');
            header("Location: user-dashboard.php");
            exit;
        } else {
            $error_message = 'Invalid username or password';
        }
    }
}

// Check for session timeout message
$timeout_message = '';
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $timeout_message = 'Your session has expired. Please log in again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowledgeTime@iips - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --primary-dark: #1e3a8a;
            --secondary-color: #334155;
            --accent-color: #0f766e;
            --light-color: #f1f5f9;
            --border-color: #e2e8f0;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --error-bg: #fee2e2;
            --error-text: #b91c1c;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            height: 100vh;
            display: flex;
            overflow: hidden;
            color: var(--text-dark);
            position: relative;
        }
        
        .page-left {
            width: 35%;
            background: linear-gradient(135deg, var(--primary-dark), var(--accent-color));
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        
        .page-right {
            width: 65%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .institution-info {
            color: white;
            margin-bottom: 2rem;
        }
        
        .institution-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .institution-info h2 {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .app-branding {
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .app-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 60px;
            height: 60px;
            background-color: rgba(255,255,255,0.2);
            color: white;
            font-size: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .app-name {
            display: flex;
            flex-direction: column;
        }
        
        .app-name h1 {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .app-name p {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .login-header h1 {
            color: var(--text-dark);
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }
        
        .login-header p {
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        .login-form .form-group {
            margin-bottom: 1.25rem;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 0.375rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .login-form .input-group {
            position: relative;
        }
        
        .login-form .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .login-form input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
            background-color: #f8fafc;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.15);
            background-color: white;
        }
        
        .login-form button {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        .login-form button:hover {
            background: var(--primary-dark);
        }
        
        .error-message {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .timeout-message {
            background: var(--warning-bg);
            color: var(--warning-text);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.25rem 0;
            color: var(--text-light);
            font-size: 0.75rem;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }
        
        .login-footer {
            text-align: center;
            color: var(--text-light);
            font-size: 0.8rem;
        }
        
        .login-footer p {
            margin-bottom: 0.5rem;
        }
        
        .copyright {
            margin-top: 1rem;
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
                overflow-y: auto;
            }
            
            .page-left {
                width: 100%;
                height: auto;
                padding: 1.5rem;
            }
            
            .page-right {
                width: 100%;
                padding: 1.5rem;
            }
            
            .login-container {
                box-shadow: none;
                padding: 1.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="page-left">
        <div class="institution-info">
            <h1>International Institute of Professional Studies</h1>
            <h2>Devi Ahilya Vishwavidyalaya (DAVV)</h2>
        </div>
        
        <div class="app-branding">
            <div class="app-logo">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="app-name">
                <h1>KnowledgeTime@iips</h1>
                <p>Timetable Management System</p>
            </div>
        </div>
    </div>
    
    <div class="page-right">
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome</h1>
                <p>Please sign in to your account</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($timeout_message)): ?>
                <div class="timeout-message">
                    <i class="fas fa-clock"></i>
                    <?php echo $timeout_message; ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                
                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="divider">demo accounts</div>
            
            <div class="login-footer">
                <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
                <p>User: <strong>user</strong> / <strong>user123</strong></p>
                <p class="copyright">© <?php echo date('Y'); ?> KnowledgeTime@iips - IIPS DAVV</p>
            </div>
        </div>
    </div>
</body>
</html> 