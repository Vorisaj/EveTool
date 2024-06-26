<?php

session_start();

$isAdmin = isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"] == 1;

if (!$isAdmin) {
    die("GTFO!");
}

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
                <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 5px;">Admin</div>
                <div style="font-size: 0.7rem;">Admin Section</div>
            </div>
        </header>

        <main class="layout-content">
            <div class="container">
                <div class="card" id="main_content">
                    <div class="center">
                        <h1>Import SQLs</h1>
                        <a href="https://www.fuzzwork.co.uk/dump/latest/">https://www.fuzzwork.co.uk/dump/latest/</a><br/>
                        <form action="admin_api.php" method="POST" enctype="multipart/form-data">
                            <input type="file" name="fileToUpload" id="fileToUpload"/><br/>
                            <input type="submit" value="invTypes.sql.bz2" name="invTypes"/><br/>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>