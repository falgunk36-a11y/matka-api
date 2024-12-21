<?php
require('../database/config.php');
require('../function.php');

header('Content-Type: application/json');
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
            $open = $arr['open_patti'] ?? null;
            $open_sd = $arr['open_sd'] ?? null;
            $open_result_time = $arr['open_update_time'] ?? null;
            $close = $arr['close_patti'] ?? null;
            $close_sd = $arr['close_sd'] ?? null;
            $close_result_time = $arr['close_update_time'] ?? null;
            $result_date = $arr['date'] ?? null;
            $market_name = $arr['market_name'] ?? null;

            if (!$market_name || !$result_date) {
                continue;
            }

            $jodi = $open_sd . $close_sd;

            // Fetch market data
            $marketDataExec = $db->prepare('SELECT * FROM `market_list` WHERE `name` = ?');
            $marketDataExec->execute([$market_name]);
            $marketData = $marketDataExec->fetch(PDO::FETCH_ASSOC);

            $gameID = $marketData['id'] ?? null;

            if ($gameID) {
                $existTodayResultExec = $db->prepare('SELECT * FROM `market_result` WHERE `game_id` = ? AND `date` = ?');
                $existTodayResultExec->execute([$gameID, $result_date]);

                if ($existTodayResultExec->rowCount()) {
                    $resultUpdateExec = $db->prepare(
                        "UPDATE `market_result` 
                         SET `open_patti` = ?, `open_sd` = ?, `open_update_time` = ?, 
                             `close_patti` = ?, `close_sd` = ?, `close_update_time` = ?, 
                             `jodi` = ? 
                         WHERE `date` = ? AND `game_id` = ?"
                    );
                    $resultUpdateExec->execute([
                        $open, $open_sd, $open_result_time,
                        $close, $close_sd, $close_result_time,
                        $jodi, $result_date, $gameID
                    ]);
                } else {
                    $resultUpdateExec = $db->prepare(
                        "INSERT INTO `market_result`
                         (`open_patti`, `open_sd`, `open_update_time`, 
                          `close_patti`, `close_sd`, `close_update_time`, 
                          `jodi`, `date`, `game_id`) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $resultUpdateExec->execute([
                        $open, $open_sd, $open_result_time,
                        $close, $close_sd, $close_result_time,
                        $jodi, $result_date, $gameID
                    ]);
                }
            }
        }
        echo json_encode(['status' => 'success', 'message' => 'Data processed successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
} else {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
}
?>