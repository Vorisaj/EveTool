<?php

function LoginFull() {
    $params = "response_type=code";
    $params .= "&redirect_uri=".urlencode("https://".getenv("EVE_HOST")."/dashboard.php");
    $params .= "&client_id=".getenv("EVE_CLIENT_ID");
    $params .= "&scope=".urlencode("esi-contracts.read_character_contracts.v1")."%20".urlencode("esi-planets.manage_planets.v1")."%20".urlencode("esi-assets.read_assets.v1")."%20".urlencode("esi-industry.read_character_mining.v1")."%20".urlencode("esi-wallet.read_character_wallet.v1")."%20".urlencode("esi-markets.structure_markets.v1")."%20".urlencode("esi-markets.read_character_orders.v1")."%20".urlencode("esi-ui.write_waypoint.v1")."%20".urlencode("esi-contracts.read_corporation_contracts.v1");
    $params .= "&state=456";
    header("location: https://login.eveonline.com/v2/oauth/authorize?".$params);
    die();
}

function LoginGuest() {
    $params = "response_type=code";
    $params .= "&redirect_uri=".urlencode("https://".getenv("EVE_HOST")."/dashboard.php");
    $params .= "&client_id=".getenv("EVE_CLIENT_ID");
    $params .= "&state=123";
    header("location: https://login.eveonline.com/v2/oauth/authorize?".$params);
    die();
}

function HandleLoginCallback($code, $isFullScope) {
    global $conn;
    $url = "https://login.eveonline.com/v2/oauth/token";
    $data = array('grant_type' => 'authorization_code', 'code' => $code);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
                         "Authorization: Basic ".base64_encode(getenv("EVE_CLIENT_ID").":".getenv("EVE_APP_SECRET"))."\r\n",
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
        $stmt = $conn->prepare("INSERT INTO sessions VALUES (?, ?, ?,?, ?,?,?)");
        $stmt->bind_param("iissssi", $_SESSION["user_id"] ,$info->{'CharacterID'},$info->{'CharacterName'}, $info->{'ExpiresOn'},$json->{'access_token'},$json->{'refresh_token'},$isFullScope);
        $rc = $stmt->execute();
    } else {
        //character in DB, update values
        $stmt = $conn->prepare("UPDATE sessions SET user_id=?, expires=?, token=?, refresh_token=?, is_full_scope=? WHERE char_id = ?");
        $stmt->bind_param("isssii", $_SESSION["user_id"], $info->{'ExpiresOn'},$json->{'access_token'},$json->{'refresh_token'}, $isFullScope, $info->{'CharacterID'});
        $rc = $stmt->execute();
    }
    if ( false===$rc ) {
        die('some error');
    }
    header("location: dashboard.php");
    die();
}

function GetCharacterInfo($opts) {
    $context = stream_context_create($opts);
    $result = array();
    $url = "https://login.eveonline.com/oauth/verify";
    $json = file_get_contents($url, false, $context);
    $json = json_decode($json);
    return $json;
}

function RefreshTokens() {
    global $conn;
    $stmt = $conn->prepare("SELECT char_id,refresh_token FROM sessions WHERE expires <= UTC_TIMESTAMP();");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $url = "https://login.eveonline.com/v2/oauth/token";
            $data = array('grant_type' => 'refresh_token', 'refresh_token' => $row["refresh_token"]);
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
                                "Authorization: Basic ".base64_encode(getenv("EVE_CLIENT_ID").":".getenv("EVE_APP_SECRET"))."\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );
        
            $context  = stream_context_create($options);
            $f = file_get_contents($url, false, $context);
            $json = json_decode($f);
            if(isset($json->error)) { 
                echo "[RefreshTokens] Error: ".$json->error."\n";
            }
            $stmt = $conn->prepare("UPDATE sessions SET expires= UTC_TIMESTAMP() + INTERVAL 19 MINUTE, token=?, refresh_token=? WHERE char_id=?");
            $stmt->bind_param("ssi", $json->{'access_token'},$json->{'refresh_token'},$row["char_id"]);
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('[RefreshTokens] DB Error: ' . htmlspecialchars($stmt->error));
            }
        }
        GetAllUserCharacters();
    }
}

?>