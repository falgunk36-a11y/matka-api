<?php
// Define file paths
$env_file_path = __DIR__ . '/.env';
$config_file_path = __DIR__ . '/database/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_env') {
        // Sanitize and retrieve input
        $api_key = htmlspecialchars($_POST['api_key'] ?? '');
        $domain_key = htmlspecialchars($_POST['domain_key'] ?? '');

        if ($api_key && $domain_key) {
            // Read and update the .env file
            if (file_exists($env_file_path)) {
                $env_contents = file_get_contents($env_file_path);

                // Update or append environment variables
                $env_contents = preg_replace('/^api_key=.*$/m', 'api_key=' . $api_key, $env_contents);
                $env_contents = preg_replace('/^domain_key=.*$/m', 'domain_key=' . $domain_key, $env_contents);

                if (file_put_contents($env_file_path, $env_contents) !== false) {
                    echo json_encode(['status' => 'success', 'message' => 'Environment file updated successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update the environment file.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => '.env file not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input for API key or domain key.']);
        }
    } elseif ($action === 'update_config') {
        // Sanitize and retrieve input
        $db_name = htmlspecialchars($_POST['db_name'] ?? '');
        $db_user = htmlspecialchars($_POST['db_user'] ?? '');
        $db_password = htmlspecialchars($_POST['db_password'] ?? '');

        if ($db_name && $db_user && $db_password) {
            // Prepare database config content
            $config_content = "<?php
define('DB_SERVER', 'localhost');
define('DB_USER', '" . addslashes($db_user) . "');
define('DB_PASSWORD', '" . addslashes($db_password) . "');
define('DB_NAME', '" . addslashes($db_name) . "');

try {
    \$db = new PDO('mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Connection failed: ' . \$e->getMessage());
}
?>";

            // Write the config file
            if (file_put_contents($config_file_path, $config_content) !== false) {
                echo json_encode(['status' => 'success', 'message' => 'Database configuration file updated successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update the configuration file.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input for database configuration.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>