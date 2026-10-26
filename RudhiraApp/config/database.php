<?php
// config/database.php
// This file connects PHP to MySQL. Every page that needs data includes it.
// Default XAMPP settings: user "root", empty password.

$db_host = 'sql201.infinityfree.com
';
$db_name = 'if0_43275261_rudhira';
$db_user = 'if0_43275261';
$db_pass = 'ohsl1kYdc1Xt';          // XAMPP default is empty. Change it only if you set a MySQL password.

try {
    // PDO is PHP's safe way to talk to MySQL.
    // $pdo is the "connection" object we use in every page.
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // show errors as exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // rows come back as arrays: $row['name']
        ]
    );
} catch (PDOException $e) {
    // If MySQL is off or the database is missing, show a friendly message.
    die('Cannot connect to the database. Is MySQL running in XAMPP, and did you import database.sql?');
}
