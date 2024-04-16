<?php

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

?>
