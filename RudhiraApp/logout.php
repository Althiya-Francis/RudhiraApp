<?php
// logout.php - ends the session and returns to the login page.
session_start();
$_SESSION = [];        // forget everything stored in the session
session_destroy();     // destroy the session itself
header('Location: login.php');
exit;
