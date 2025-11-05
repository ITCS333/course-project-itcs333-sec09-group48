<?php
require_once '../common/config.php';

// Destroy all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>