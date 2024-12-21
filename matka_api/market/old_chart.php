<?php
include('../database/config.php');
include('../function.php');

loadEnv('../.env');
set_time_limit(0);

$domain = getenv('domain');
$api_key = getenv('api_key');
$domain_key = getenv('domain_key');
$api_url = getenv('api_url');

// Set the game name
$game_name = "MAIN BAZAR,KALYAN"; // You can add more markets separated by commas

/**
 * Fetch old chart data for a specific market.
 */
function fetchOldChart($market) {
    global $domain, $api_key, $domain_key, $api_url;

    $data = [
        'domain' => $domain,
        'api_key' => $api_key,
        'domain_key' => $domain_key,
        'market' => $market,
        'old' => true
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "$api_url/apis/market_api.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return json_decode($response);
}

/**
 * Fetch market data.
 */
function fetchMarketData($market) {
    global $domain, $api_key, $domain_key, $api_url;

    $data = [
        'domain' => $domain,
        'api_key' => $api_key,
        'domain_key' => $domain_key,
        'market' => $market
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "$api_url/apis/market_api.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    $arrs = json_decode($response);
    $result = [];
    foreach ($arrs->data as $row) {
        $result[] = $row->name;
    }
    return $result;
}

// Determine markets to process
if ($game_name === "all") {
    $market_arr = fetchMarketData("all");
} else {
    $market_arr = explode(',', $game_name);
}

// Process each market
foreach ($market_arr as $market_name) {
    $response = fetchOldChart($market_name);
    if (!$response || !isset($response->data)) {
        continue; // Skip if response is invalid
    }

    foreach ($response->data as $row) {
        $date = date("Y-m-d", strtotime($row->date));
        $open_patti = $row->open ?? '';
        $jodi = $row->jodi ?? '';

        $close_patti = '';
        $open_sd = '';
        $close_sd = '';

        if (strlen($jodi) > 1) {
            $close_patti = $row->close ?? '';
            $open_arr = str_split($jodi);
            $open_sd = $open_arr[0] ?? '';
            $close_sd = $open_arr[1] ?? '';
        }

        // Check if the record already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM `market_result` WHERE `game_id` = :name AND `date` = :date");
        $stmt->execute([':name' => $market_name, ':date' => $date]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Update the existing record
            $stmt = $db->prepare(
                "UPDATE `market_result` 
                 SET `open_patti` = :open_patti, `close_patti` = :close_patti, 
                     `open_sd` = :open_sd, `close_sd` = :close_sd, `jodi` = :jodi 
                 WHERE `game_id` = :name AND `date` = :date"
            );
        } else {
            // Insert a new record
            $stmt = $db->prepare(
                "INSERT INTO `market_result` 
                 (`game_id`, `date`, `open_patti`, `close_patti`, `open_sd`, `close_sd`, `jodi`) 
                 VALUES (:name, :date, :open_patti, :close_patti, :open_sd, :close_sd, :jodi)"
            );
        }

        $stmt->execute([
            ':name' => $market_name,
            ':date' => $date,
            ':open_patti' => $open_patti,
            ':close_patti' => $close_patti,
            ':open_sd' => $open_sd,
            ':close_sd' => $close_sd,
            ':jodi' => $jodi,
        ]);
    }
}

echo "Old Chart Uploaded!";
?>