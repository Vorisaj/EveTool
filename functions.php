<?php

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

?>
