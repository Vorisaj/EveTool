<html>
    <head>
        <title>Buyback sheet</title>
        <link rel="stylesheet" href="market/style.css"/>
        <link rel="stylesheet" href="market/new_style.css"/>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    </head>

    <body>

    <header onclick="location.href='index.html'" class="layout-header" style="line-height: 1; color: white; text-align: center; padding: 0px;">
          <div style="padding: 12px; cursor: pointer;">
            <img style="margin-left: -165px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="market/logo.webp">
            <img style="margin-left: 120px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="market/logo.webp">
            <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 5px;">Buyback sheet</div>
            <div style="font-size: 0.7rem;">Overview of buyback items for sell</div>
        </div>
    </header>

    <div class="center">
    <?php
        require 'Constants.php';
        require "assets.php";
        require "market.php";
        require "functions.php";
        require 'esi_api.php';

        echo "Generated at ".date('Y-m-d H:i:s')." pm EVE time<br/>";

        $assets = GetContainerAssets(1044537417598);
    ?>
    </div>
        <main class="layout-content">
            <div class="container">
                <div class="card" id="main_content">
                    <table class="markettable center">
                        <tr><th></th><th>Item name</th><th>Availability</th></tr>
                        <?php
                            foreach($assets as $asset) {
                                echo '<tr><td><img src="https://images.evetech.net/Type/'.$asset['type_id'].'_32.png"/></td><td>'.GetItemNameFromID($asset['type_id']).'</td><td>'.number_format($asset['quantity'],0,'',',').'</td></tr>';
                            }
                        ?>
                    </table>
                </div>
            </div>
        </main>

</body>
</html>
