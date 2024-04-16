<?php

$conn = new mysqli($_ENV["EVE_DB_IP"], $_ENV["EVE_DB_USER"], $_ENV["EVE_DB_PASSWORD"], "eve");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function ToMillions($number,$decimals) {
  if ($number > 1000000000 || $number < -1000000000) {
    return number_format((round($number/1000000)/1000),$decimals,'.',",")."B ISK";
  } else if ($number > 1000000 || $number < -1000000)  {
    return number_format((round($number/1000)/1000),$decimals,'.',",")."M ISK";
  } else if ($number > 1000 || $number < -1000) {
    return number_format((round($number)/1000),$decimals,'.',",")."k ISK";
  }else {
    return number_format((round($number)),0,'.',",")." ISK";
  }
}

function gen_uuid() {
  return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
      mt_rand( 0, 0xffff ),
      mt_rand( 0, 0x0fff ) | 0x4000,
      mt_rand( 0, 0x3fff ) | 0x8000,
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
  );
}

function DownloadAllAPIPages($url, $char_id) {
  $page = 1;
  $trials = 0;
  $pages = array();
  $opts = null;
  if ($char_id != null) $opts = GetOptsForCharacterId($char_id);

  while(true) {
    
    $furl = $url."page=".$page;
    $json = http_request($furl, $opts);
    $json = json_decode($json); 
    if(isset($json->error)) {
        if ($json->error == "token is expired") {
          RefreshTokens();
          $opts = GetOptsForCharacterId($char_id);
          $trials = $trials + 1;
          if ($trials > 3) {
            echo "[DownloadAllAPIPages] Token refresh error!";
            return FALSE;
          }
          continue;
        }
        if (strpos($json->error, "Undefined 404 response") === false && $json->error != "Requested page does not exist!") {
            echo "[DownloadAllAPIPages] ".$furl." Error: ".$json->error."\n";
            return FALSE;
        }
        break;
    }
    if($json == null) {
        continue;
    }
    for($i = 0; $i < count($json); $i++) {
        array_push($pages, $json[$i]);
    }
    $page = $page + 1;
  }
  return $pages;
}

function FetchAllEntries($query) {
    global $conn;
    $r = array();
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($r, $row);
    }
    return $r;
}

function FetchAllEntriesByKey($query, $key) {
  global $conn;
  $r = array();
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $r[$row[$key]] = $row;
  }
  return $r;
}

function ExecuteQuery($query) {
  global $conn;
  $stmt = $conn->prepare($query);
  $stmt->execute();
}

function CheckExpiredToken($json) {
  if (!isset($json->error)) return;
  if ($json->error == "token is expired") {
      RefreshTokens();
  }
}

function http_request($uri, $opts = null, $time_out = 10, $headers = 0)
{
    usleep( 100 * 1000 ); //ESI throttling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, trim($uri));
    curl_setopt($ch, CURLOPT_HEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    if ($opts != null)
        curl_setopt($ch, CURLOPT_HTTPHEADER, [explode("\n",$opts['http']['header'])[1]]);
    $result = curl_exec($ch);

  if ($result === false) {
      throw new Exception(curl_error($ch), curl_errno($ch));
      die();
  }
    curl_close($ch);
    return $result;
}

function DownloadSingleAPIPage($url, $char_id) {
  $page = 1;
  $trials = 0;
  $pages = array();
  $opts = null;
  if ($char_id != null) $opts = GetOptsForCharacterId($char_id);

    $json = http_request($url, $opts);
    $json = json_decode($json); 
    if(isset($json->error)) {
        if ($json->error == "token is expired") {
          RefreshTokens();
          $opts = GetOptsForCharacterId($char_id);
          $trials = $trials + 1;
          if ($trials > 3) {
            echo "[DownloadSingleAPIPage] Token refresh error!";
            return FALSE;
          }

        }
        if (strpos($json->error, "Undefined 404 response") === false && $json->error != "Requested page does not exist!") {
            if (strpos($json->error, "ConStopSpamming") !== false) {
              $time = explode(":",$json->error)[2];
              $time = (float)explode("}", $time)[0];
              $time = $time /1000000 + 0.5;
              sleep($time);
              return DownloadSingleAPIPage($url, $char_id);
            }
            echo "[DownloadSingleAPIPage] ".$url." Error: ".$json->error."\n";
            return FALSE;
        }
    }

    for($i = 0; $i < count($json); $i++) {
        array_push($pages, $json[$i]);
    }
  

  return $pages;
}

?>
