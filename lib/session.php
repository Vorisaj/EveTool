<?php

function LoginFull() {
    $params = "response_type=code";
    $params .= "&redirect_uri=".urlencode("https://evetool.baldhead.eu/dashboard.php");
    $params .= "&client_id=2e4fe1e15299454f94270fbac6683468";
    $params .= "&scope=".urlencode("esi-contracts.read_character_contracts.v1")."%20".urlencode("esi-planets.manage_planets.v1")."%20".urlencode("esi-assets.read_assets.v1")."%20".urlencode("esi-industry.read_character_mining.v1")."%20".urlencode("esi-wallet.read_character_wallet.v1")."%20".urlencode("esi-markets.structure_markets.v1")."%20".urlencode("esi-markets.read_character_orders.v1")."%20".urlencode("esi-ui.write_waypoint.v1")."%20".urlencode("esi-contracts.read_corporation_contracts.v1");
    $params .= "&state=123";
    header("location: https://login.eveonline.com/v2/oauth/authorize?".$params);
    die();
}

function LoginGuest() {
    $params = "response_type=code";
    $params .= "&redirect_uri=".urlencode("https://evetool.baldhead.eu/dashboard.php");
    $params .= "&client_id=2e4fe1e15299454f94270fbac6683468";
    $params .= "&state=123";
    header("location: https://login.eveonline.com/v2/oauth/authorize?".$params);
    die();
}

function HandleLoginCallback($code) {
    global $conn;
    $url = "https://login.eveonline.com/v2/oauth/token";
    $data = array('grant_type' => 'authorization_code', 'code' => $code);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
                         "Authorization: Basic ".base64_encode($_ENV["EVE_APP_SECRET"])."\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    $json = json_decode($result);
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Accept-language: en\r\n" .
                "authorization: Bearer ".$json->{'access_token'}."\r\n"
        ]
    ];
    $info = GetCharacterInfo($opts);

    //check if character already added
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE char_id = ?");
    $stmt->bind_param("i", $info->{'CharacterID'});
    $rc = $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        //character not in DB
        $stmt = $conn->prepare("INSERT INTO sessions VALUES (?, ?, ?,?, ?,?)");
        $stmt->bind_param("iissss", $_SESSION["user_id"] ,$info->{'CharacterID'},$info->{'CharacterName'}, $info->{'ExpiresOn'},$json->{'access_token'},$json->{'refresh_token'});
        $rc = $stmt->execute();
    } else {
        //character in DB, update values
        $stmt = $conn->prepare("UPDATE sessions SET user_id=?, expires=?, token=?, refresh_token=? WHERE char_id = ?");
        $stmt->bind_param("isssi", $_SESSION["user_id"], $info->{'ExpiresOn'},$json->{'access_token'},$json->{'refresh_token'}, $info->{'CharacterID'});
        $rc = $stmt->execute();
    }
    if ( false===$rc ) {
        die('some error');
    }
    header("location: dashboard.php");
    die();
}

?>