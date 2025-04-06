<?php
// Include the SessionUtils class
require_once 'utils/SessionUtils.php';

// Use the clearSession method for consistent logout handling
SessionUtils::clearSession();

// Redirect to the login page
header("Location: login.php");
exit;
?> 