<?php
require('../database/config.php');
require('../function.php');

header('Content-Type: application/json');

// Get the token from headers
$headers = getallheaders();
$token = null;

foreach ($headers as $key => $value) {
    if (strtolower($key) === "token") {
        $token = $value;
        break;
    }
}

$apiDetails = getapiDetails();

// Validate the token
if (isset($token) && $token === $apiDetails['apikey']) {
    http_response_code(200);

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data) {
        foreach ($data as $arr) {
            // Extract data from the input array
            $name = $arr['name'] ?? null;
            $open_time = $arr['open_time'] ?? null;
            $close_time = $arr['close_time'] ?? null;

            if ($name && $open_time && $close_time) {
                // Check if the market already exists
                $existMarketExec = $db->prepare('SELECT * FROM `market_list` WHERE `name` = ?');
                $existMarketExec->execute([$name]);
                $existMarketData = $existMarketExec->fetch(PDO::FETCH_ASSOC);

                if ($existMarketExec->rowCount() > 0) {
                    // Update the existing market
                    $updateMarketExec = $db->prepare(
                        'UPDATE `market_list` SET `open_time` = ?, `close_time` = ? WHERE `name` = ?'
                    );
                } else {
                    // Insert a new market
                    $updateMarketExec = $db->prepare(
                        'INSERT INTO `market_list` (`open_time`, `close_time`, `name`) VALUES (?, ?, ?)'
                    );
                }

                $updateMarketExec->execute([$open_time, $close_time, $name]);
            } else {
                // Skip if required fields are missing
                echo json_encode(['status' => 'error', 'message' => 'Invalid market data']);
                exit;
            }
        }

        // Success response
        echo json_encode(['status' => 'success', 'message' => 'Market data processed successfully']);
    } else {
        // Invalid input data
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
} else {
    // Unauthorized access
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
}
?>