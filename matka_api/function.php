<?php

function compressResult(...$arg){
    $result = [];
    foreach($arg as $value){
        if($value){
            $result[] = $value;
        }
    }
    return implode('-',$result);
}




/*-------------------------X-----------------------------*/




function isResult($result){
    if(preg_match('/(\d{3}+)\-(\d{2}+)\-(\d{3}+)$/', $result)){
        return true;
    }elseif(preg_match('/(\d{3}+)\-(\d{1}+)$/', $result)){
        return true;
    }else{
        return false;
    }
}



/*-------------------------X-----------------------------*/


function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("The .env file does not exist.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove surrounding quotes if present
        $value = trim($value, '"\'');
        
        // Set the environment variable
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}


function getapiDetails(){
    loadEnv(__DIR__ . '/.env');
    $apiKey = htmlspecialchars(getenv('api_key'));
    $domain_key = htmlspecialchars(getenv('domain_key'));
    $api_url = htmlspecialchars(getenv('api_url'));
    return ['apikey'=>$apiKey, 'domain_key'=>$domain_key, 'api_url'=>$api_url];
}
?>