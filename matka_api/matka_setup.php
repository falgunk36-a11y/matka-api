<?php
$env_file_path = __DIR__ . '/.env';
$config_file_path = __DIR__ . '/database/config.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['action'] === 'update_env') {
        $api_key = htmlspecialchars($_POST['api_key']);
        $domain_key = htmlspecialchars($_POST['domain_key']);


        $env_contents = file_get_contents($env_file_path);
        $env_contents = preg_replace('/^api_key=.*$/m', 'api_key=' . $api_key, $env_contents);
        $env_contents = preg_replace('/^domain_key=.*$/m', 'domain_key=' . $domain_key, $env_contents);
        
        if (file_put_contents($env_file_path, $env_contents) !== false) {
            echo 'Environment file updated successfully.';
        } else {
            echo 'Failed to update the environment file.';
        }
    } elseif ($_POST['action'] === 'update_config') {
        $db_name = htmlspecialchars($_POST['db_name']);
        $db_user = htmlspecialchars($_POST['db_user']);
        $db_password = htmlspecialchars($_POST['db_password']);
        
        
        $config_content = "<?php
        define('DB_SERVER', 'localhost');
        define('DB_USER', '$db_user');
        define('DB_PASSWORD', '$db_password');
        define('DB_NAME', '$db_name');

try {
    \$db = new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Connection failed: ' . \$e->getMessage());
}
?>";

        // Write the config file
        if (file_put_contents($config_file_path, $config_content) !== false) {
            echo 'Database configuration file updated successfully.';
        } else {
            echo 'Failed to update the configuration file.';
        }
    }
} else {
    echo 'Invalid request.';
}
?>