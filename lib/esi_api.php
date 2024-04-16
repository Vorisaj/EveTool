<?php

$islocal = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1');

if ($islocal) {
    GetAllUserCharacters();
} else if (isset($_SESSION["user_id"])) GetUsersCharacters();


//Logged-in via web
function GetUsersCharacters() {
        global $conn;
        $_SESSION["characters"] = array();
        $_SESSION["chars_ids"] = array();
        $stmt = $conn->prepare("SELECT * FROM sessions WHERE user_id=?");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            array_push($_SESSION["characters"] , $row);
            array_push($_SESSION["chars_ids"] , $row["char_id"]);
        }
    }

function GetOptsForCharacterId($id) {
    for($i = 0; $i < count($_SESSION["characters"]); $i++) {
        if ($_SESSION["characters"][$i]["char_id"] == $id) {
            return GetOptsForCharacter($i);
        }
    }
}

function GetOptsForCharacter($index) {
    return $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Accept-language: en\r\n" .
                "authorization: Bearer ".$_SESSION["characters"][$index]["token"]."\r\n"
        ]
    ];
}

function GetAllUserCharacters() {
    global $conn;
    $_SESSION["characters"] = array();
    $_SESSION["chars_ids"] = array();
    $stmt = $conn->prepare("SELECT * FROM sessions");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($_SESSION["characters"] , $row);
        array_push($_SESSION["chars_ids"] , $row["char_id"]);
    }
}

?>
