<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

$isAdmin = isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"] == 1;

if (!$isAdmin) {
    die("GTFO!");
}

require "lib/functions.php";

if (isset($_POST["invTypes"])) {
    move_uploaded_file($_FILES["fileToUpload"]["tmp_name"],'/tmp/' . $_FILES["fileToUpload"]["name"]) or die ("Failure to upload content");
    $file = '/tmp/' . $_FILES["fileToUpload"]["name"];
    $tempSqlFile = "/tmp/temp.sql";
    //TODO: Fix the code injection vulnerability here... :)
    echo exec("bzip2 -d $file -c > $tempSqlFile");

    exec($command = "mysql -u ".getenv("EVE_DB_USER")." -p".getenv("EVE_DB_PASSWORD")." eve < $tempSqlFile");

    exec("rm /tmp/temp.sql");
    exec("rm /tmp/". $_FILES["fileToUpload"]["name"]);

    header("location: admin.php");
}

?>
