<?php

session_start();
$isSigned = isset($_SESSION["user_id"]) && $_SESSION["user_id"] >= 0;

//Redirect to dashboard if user is signed
if ($isSigned) {
    header("location: dashboard.php");
    die();
}

?>

<html>
    <head>
        <title>Eve</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        ... TBD ... Login and registration ...
    </body>
</html>