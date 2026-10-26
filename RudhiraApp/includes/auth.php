<?php
// includes/auth.php
// Include this at the top of every page that needs a logged-in user.
// A "session" is how PHP remembers who is logged in between page loads.

session_start();

// If no user id is stored in the session, the visitor is not logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');   // send them to the login page
    exit;                            // stop running this page
}
