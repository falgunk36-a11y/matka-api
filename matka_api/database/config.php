<?php
define('DB_SERVER', 'localhost');
define('DB_USER', 'raju khanna');
define('DB_PASSWORD', 'admin@123');
define('DB_NAME', 'utaam');

try {
    $db = new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}
?>
