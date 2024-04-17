<?php

//Logour
if (isset($_GET["logout"])) {
    session_destroy();
    header("location: index.php");
    die(); 
}

//Add character button
if (isset($_GET["login"])) {
    LoginGuest();
}

//Grand Perms
if (isset($_GET["grantperm"])) {
    LoginFull();
}

//Handle Login callback
if (isset($_GET["state"]) && isset($_GET["code"])) {
    if ($_GET["state"] == "123") HandleLoginCallback($_GET["code"],0);
    if ($_GET["state"] == "456") HandleLoginCallback($_GET["code"],1);
}

function PrintSignedCharacters() {
    for($i = 0; $i < count($_SESSION["characters"]); $i++) {
        echo "<td class='center'><img src='https://images.evetech.net/characters/".$_SESSION["characters"][$i]["char_id"]."/portrait?size=128'/><br/>".$_SESSION["characters"][$i]["char_name"];
        if ($_SESSION["characters"][$i]["is_full_scope"] == 0) {
            echo '<br/><div class="button"><a href="?grantperm=1">Grant perms</a></div>';
        }
        echo "</td>";
    }
}

?>