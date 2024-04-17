<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
$isSigned = isset($_SESSION["user_id"]) && $_SESSION["user_id"] >= 0;

if (!$isSigned) {
    header("location: index.php");
    die();
}

require 'lib/base.php';
require 'lib/session.php';
require 'lib/dashboard_actions.php';

?>

<html>
    <head>
        <title>EveTool</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <header onclick="location.href='index.php'" class="layout-header" style="line-height: 1; color: white; text-align: center; padding: 0px;">
            <div style="padding: 12px; cursor: pointer;">
                <img style="margin-left: -165px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="img/logo.webp">
                <img style="margin-left: 120px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="img/logo.webp">
                <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 5px;">Dashboard</div>
                <div style="font-size: 0.7rem;">Main Dashboard</div>
                <div class="button" style="position: absolute; right: 10px;top:10px;"><a href="?logout=1">Logout</a></div>
            </div>
        </header>

        <main class="layout-content">
            <div class="container">
                <div class="card" id="main_content">
                    <table>
                        <tr><?php PrintSignedCharacters(); ?><td><div class="button"><a href="?login">Add Character</a></div></td></tr>
                    </table>
                </div>
            </div>
        </main>
    </body>
</html>