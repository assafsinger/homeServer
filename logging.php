<?php
ini_set('precision', 20);
$fp = fopen('/home/assafs/loggingPHP.log', 'a');
$pathToState = '/home/assafs/loggingState.state';
$serverUrl = 'http://18.216.107.149:8900';


function parseRequest(){
    $res = array();
    $res['measurements'] = array();
    $res['delays'] = array();
    $res['dId'] = getReqParamOrNA('dId');
    $res['type'] = getReqParamOrNA('type');
    foreach($_GET as $key => $value){
        if (substr( $key, 0, 2 ) === "m_"){
            $res['measurements'][substr( $key, 2, strlen($key))] = $value;
        }
        if (substr( $key, 0, 2 ) === "d_"){
            $res['delays'][substr( $key, 2, strlen($key))] = $value;
        }
    }
    return $res;
}

function getReqParamOrNA($param){
    if (!empty($_GET[$param])){
            return $_GET[$param];
        } else {
            return 'NA';
        }
}

function processPayload($parsedReq){
    $payload = array();
    $payload['dId'] = $parsedReq['dId'];
    $payload['type'] = $parsedReq['type'];
    foreach($parsedReq['measurements'] as $key => $value){
        $payload[$key] = $value;
    }
    foreach($parsedReq['delays'] as $key => $value){
        $payload['d_'.$key] = $value;
    }
    return $payload;
}


function reportMetrics(){
    $req = parseRequest();
    $payload = processPayload($req);
    echo json_encode($payload);
}


//this needs more work - aggregate wattage
function reportPowerAggregated(){
    $state = readLockState();
    $req = parseRequest();
    $payload = processPayload($req);
    $key = 'wattage';
    $wattage = $req['measurements'][$key];
    if (empty($wattage)){
        echo json_encode($req);
        commitReleaseState($state);
        return;
    }
    $currentTime = millis();
    if (!empty($wattage) && (null !== $state[$key])){
        $lastValue = $state[$key]['lastValue'];
        $lastUpdated = (float)$state[$key]['lastUpdated'];

        if (!empty($lastValue) && !empty($lastUpdated)){
        /*
            kwh = ([wattage usage in interval]/1000) * (intervalSize/hour)

        */
            $averageUsageInterval = (($lastValue + $wattage)/2.0)/1000;
            $currentTime = millis();
            $intervalSizeMillis = round($currentTime-$lastUpdated);
            $conversionToHour = 1/(3600*1000);

            $payload['KwH'] = $averageUsageInterval * $intervalSizeMillis * $conversionToHour;
            $payload['intervalSizeMillis'] = $intervalSizeMillis;
        }
    }

    $state[$key] = array('lastValue' => $wattage, 'lastUpdated' => $currentTime);
    commitReleaseState($state);
    $payloadJsonStr = json_encode($payload, JSON_NUMERIC_CHECK);
    sendPayload($payloadJsonStr);
    echo $payloadJsonStr;
}

function commitReleaseState($state){
    file_put_contents($GLOBALS['pathToState'], json_encode($state));
}


//should probably lock and protect for syncroniation issues.
function readLockState(){
    $state = array();
    if (file_exists($GLOBALS['pathToState'])) {
        $stateFile = file_get_contents($GLOBALS['pathToState']);
        $state = json_decode($stateFile, true);
    }
    return $state;
}

function millis(){
    return round(microtime(true)*1000);
}

function sendPayload($payloadStr){
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $payloadStr,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payloadStr)),
        CURLOPT_URL => $GLOBALS['serverUrl']
        ));
    // Send the request & save response to $resp
    $resp = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);
}

reportPowerAggregated();

?>
