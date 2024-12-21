<?php
// Database configuration
define('DB_SERVER', 'localhost'); // Database host
define('DB_USER', 'your_db_user'); // Database username
define('DB_PASSWORD', 'your_db_password'); // Database password
define('DB_NAME', 'your_db_name'); // Database name

try {
    $db = new PDO(
        'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASSWORD
    );
    // Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
?>