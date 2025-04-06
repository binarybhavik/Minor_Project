<?php
// Note: SessionUtils.php is already included from the controller.php proxy file
// require_once __DIR__ . '/../utils/SessionUtils.php';

// Process logout
clearSession();

// Redirect to login page
header('Location: index.php');
exit;
?> 