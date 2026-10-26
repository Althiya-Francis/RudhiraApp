<?php
// index.php - the first page. It only decides where to send the visitor.
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');   // already logged in
} else {
    header('Location: login.php');       // not logged in
}
exit;
