<?php
require('../database/config.php');
require('../function.php');

header('Content-Type:application/json');

foreach (getallheaders() as $key => $value) {
    if ($key == "token" || $key == "Token") {
        $token = $value;
    } 
}

$apiDetails = getapiDetails();

//API key add
if(isset($token) && $token==$apiDetails['apikey']){
    http_response_code(200);

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data) {
        
        foreach($data as $arr){
            extract($arr);
            
            $jodi = $open_sd.$close_sd;

            $marketDataExec = $db->prepare('SELECT * FROM `market_list` WHERE `name`=?');
            $marketDataExec->execute([$market_name]);  $marketData = $marketDataExec->fetch(PDO::FETCH_ASSOC);
            
            $gameID = $marketData['id'] ?? '';
            
            if(isset($gameID) && $gameID){
                
                $existTodayResultExec = $db->prepare('SELECT * FROM `market_result` WHERE `game_id`=? AND `date`=?');
                $existTodayResultExec->execute([$gameID, $result_date]);  $existTodayResultData = $existTodayResultExec->fetch(PDO::FETCH_ASSOC);
                
                if($existTodayResultExec->rowCount()){
                    
                    $resultUpdateExec = $db->prepare("UPDATE `market_result` SET `open_patti`=?, `open_sd`=?, `open_update_time`=?, `close_patti`=?, `close_sd`=?, `close_update_time`=?, `jodi`=? WHERE `date`=? AND `game_id`=?"); 
                }else{
                    $resultUpdateExec = $db->prepare("INSERT INTO `market_result`(`open_patti`, `open_sd`, `open_update_time`, `close_patti`, `close_sd`, `close_update_time`, `jodi`, `date`, `game_id`) VALUES (?,?,?,?,?,?,?,?,?)"); 
                }
                
                $resultUpdateExec->execute([$open, $open_sd, $open_result_time, $close, $close_sd, $close_result_time, $jodi, $result_date, $gameID]);
            }
        }
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
}

?>