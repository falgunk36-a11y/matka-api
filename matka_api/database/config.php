<?php
define('DB_SERVER', 'localhost');
define('DB_USER', 'add db user');
define('DB_PASSWORD', 'add password');
define('DB_NAME', 'add db name');

try {
    $db = new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}
?>