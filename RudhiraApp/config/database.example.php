<?php
/*
 * config/database.php
 * ONE place for all database settings (PRD section 3).
 * Change these values to match your XAMPP/WAMP/Laragon MySQL.
 * XAMPP default: user = root, password = empty.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'rudhira_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_mysql_password_here');

/*
 * getDB() returns a PDO connection. "static" means the connection is made
 * only once per page load and then re-used.
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw errors so we can catch them
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements (safer)
                ]
            );
        } catch (PDOException $ex) {
            // SECURITY: log the real error on the server, show only a safe message to the user.
            error_log('RudhiraApp DB connection failed: ' . $ex->getMessage());
            http_response_code(500);
            die('Sorry, the database is not available right now. Please contact the administrator.');
        }
    }
    return $pdo;
}
