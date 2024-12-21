<?php
include('../database/config.php');
include('../function.php');
loadEnv('../.env');
set_time_limit(0);
$domain = getenv('domain');
$api_key = getenv('api_key');
$domain_key = getenv('domain_key');
$api_url = getenv('api_url');


// $game_name = "all"; 
$game_name = "MAIN BAZAR,KALYAN"; // you can add more with comma

function oldchart($market) {
    global $domain,$api_key,$domain_key,$api_url;
    $data = array(
        'domain' => $domain,
        'api_key' => $api_key,
        'domain_key' => $domain_key,
        'market' => $market,
        'old' => true
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "$api_url/apis/market_api.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    if(curl_errno($curl)) {
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return json_decode($response);
}
function marketdata($market) {
    global $domain,$api_key,$domain_key,$api_url;
    $data = array(
        'domain' => $domain,
        'api_key' => $api_key,
        'domain_key' => $domain_key,
        'market' => $market
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "$api_url/apis/market_api.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    if(curl_errno($curl)) {
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    $arrs = json_decode($response);
    $result=array();
    foreach($arrs->data as $row){
        $result [] = $row->name;
    }
    return $result; 
}

if($game_name=="all"){
    $market_arr = marketdata("all");
}else{
    $market_arr = explode(',',$game_name);
}
foreach($market_arr as $market_name){
    $response = oldchart($market_name);
    foreach($response->data as $row){
        
        $date = date("Y-m-d",strtotime($row->date));
        $open_patti = $row->open;
        $jodi = $row->jodi;
    
        $close_patti='';
        $open_sd='';
        $close_sd='';
    
        if(strlen($jodi) >1 ){
            $close_patti = $row->close;
            $open_arr = str_split($jodi);
            $open_sd =$open_arr[0]; 
            $close_sd =$open_arr[1]; 
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM `market_result` WHERE `game_id` = :name AND `date`='$date'");
        $stmt->execute([':name' => $market_name]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $stmt = $db->prepare("UPDATE `market_result` SET `open_patti` = :open_patti, `close_patti` = :close_patti,`open_sd`=:open_sd,`close_sd`=:close_sd,`jodi`=:jodi WHERE `game_id` = :name");
            $stmt->execute([
                ':open_patti' => $open_patti,
                ':close_patti' => $close_patti,
                ':open_sd' => $open_sd,
                ':close_sd' => $close_sd,
                ':jodi' => $jodi,
                ':name' => $market_name
            ]);
        }else{
            $stmt = $db->prepare("INSERT INTO `market_result` (`game_id`,`date`, `open_patti`, `close_patti`,`open_sd`,`close_sd`,`jodi`) VALUES (:name,:date,:open_patti, :close_patti,:open_sd,:close_sd,:jodi)");
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
}
echo "Old Chart Uploaded !";

?>