<?php
/**
 * Session utility class for handling user sessions and authentication
 */
class SessionUtils {
    /**
     * Start session if not already started
     */
    public static function startSessionIfNeeded() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get base URL for the application
     * 
     * @return string The base URL
     */
    public static function getBaseUrl() {
        // Figure out the base URL from server variables
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Get application path
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        // Remove trailing slashes and normalize
        $baseUrl = rtrim($scriptPath, '/');
        
        return $baseUrl;
    }

    /**
     * Check if user is logged in
     * 
     * @return bool Whether the user is logged in
     */
    public static function isLoggedIn() {
        self::startSessionIfNeeded();
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }

    /**
     * Check if user has admin role
     * 
     * @return bool Whether the user is an admin
     */
    public static function isAdmin() {
        self::startSessionIfNeeded();
        return self::isLoggedIn() && $_SESSION['role'] === 'admin';
    }

    /**
     * Check if user is an admin and logged in
     * 
     * @return bool Whether the user is an admin and logged in
     */
    public static function isAdminLoggedIn() {
        self::startSessionIfNeeded();
        return self::isLoggedIn() && self::isAdmin();
    }

    /**
     * Check if user has regular user role
     * 
     * @return bool Whether the user is a regular user
     */
    public static function isUser() {
        self::startSessionIfNeeded();
        return self::isLoggedIn() && $_SESSION['role'] === 'user';
    }

    /**
     * Set user session (after successful login)
     * 
     * @param int $userId The user's ID
     * @param string $username The user's username
     * @param string $role The user's role
     */
    public static function setUserSession($userId, $username, $role) {
        self::startSessionIfNeeded();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['last_activity'] = time();
    }

    /**
     * Get user data from session
     * 
     * @return array User data including name and role
     */
    public static function getUserData() {
        self::startSessionIfNeeded();
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'name' => $_SESSION['username'] ?? 'User',
            'role' => $_SESSION['role'] ?? 'guest',
            'id' => $_SESSION['user_id'] ?? 0
        ];
    }

    /**
     * Clear user session (logout)
     */
    public static function clearSession() {
        self::startSessionIfNeeded();
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }

    /**
     * Redirect if not logged in
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: index.php");
            exit;
        }
        // Check for session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            self::clearSession();
            header("Location: index.php?timeout=1");
            exit;
        }
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }

    /**
     * Redirect if not admin
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header("Location: user-dashboard.php");
            exit;
        }
    }

    /**
     * Redirect if not regular user
     */
    public static function requireUser() {
        self::requireLogin();
        if (!self::isUser()) {
            header("Location: admin-dashboard.php");
            exit;
        }
    }
}
?> 