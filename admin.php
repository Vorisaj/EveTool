<?php

session_start();
$isAdmin = isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"];

if (!$isAdmin) {
    die("GTFO!");
}

?>

<html>
    ... Admin stuff ... TBD ...
</html>