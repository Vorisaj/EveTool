<html>
    <head>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="new_style.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    </head>
    <body>
    <header onclick="location.href='index.html'" class="layout-header" style="line-height: 1; color: white; text-align: center; padding: 0px;">
          <div style="padding: 12px; cursor: pointer;">
            <img style="margin-left: -165px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="logo.webp">
            <img style="margin-left: 120px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="logo.webp">
            <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 5px;">Market tracker</div>
            <div style="font-size: 0.7rem;">Market and contract tracking</div>
        </div>
    </header>
    <div class="center">

<?php
require '../lib/base.php';
require "market.php";
require 'market_tracker_functions.php';
require 'contracts.php';

$d7stats = GetSellStats(7);
$d30stats = GetSellStats(30);
echo "Generated at ".date('Y-m-d H:i:s')." pm EVE time</br>";
echo "<i>Note: traded volumes also includes orders that were canceled :( It seems to be impossible to get 100% accurate number.</i><br/>";
?>
<div class="button" onclick='$(".nonzero").hide()'>Hide Satisfied comodities</div>
</div>
<script>
    var options = {
        responsive: true,
        scales: {
          y: {beginAtZero: true,
            color: function(context) {
                return Utils.CHART_COLORS.grey;
            }},
        }
    };
</script>
<div class="row">
    <div class="column center">
        <h1>Contracts Stats</h1>
        <div style="height: 10px;margin-top: -26px;">Satisfaction: <span id='contract_satis' class='totalsell'>xx%</span></div>
        <?php PrintContractStats(FALSE); ?>
    </div>
    <div class="column center">
        <h1>Market Stats</h1>
        <div style="height: 10px;margin-top: -26px;">Satisfaction: <span id='market_satis' class='totalsell'>xx%</span></div>
        <?php
            PrintMarketStats(FALSE);
        ?>
    </div>
</div>

<script>
function sortTable(sender) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = sender.parentElement.parentElement.parentElement;

  switching = true;
  dir = "asc";

    var n = 0;
    for(i = 0; i < table.rows[0].getElementsByTagName("TD").length; i++)
        if (table.rows[0].getElementsByTagName("TD")[i] == sender) {
            n = i;
            break;
        }

  while (switching) {
    switching = false;
    rows = table.rows;

    for (i = 1; i < (rows.length - 1); i++) {

      shouldSwitch = false;

      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      var num1 = getISK(x.innerHTML);
      var num2 = getISK(y.innerHTML);
      if (dir == "asc") {
        
        if (num1 > num2) {
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (num1 < num2) {
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      switchcount ++;
    } else {
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}

function getISK(isk) {
    var res = isk.split(" ")[0];
    if (res.endsWith("k")) {
        res = res.substring(0, res.length-1);
        return parseFloat(res)*1000;
    }
    if (res.endsWith("M")) {
        res = res.substring(0, res.length-1);
        return parseFloat(res)*1000000;
    }
    if (res.endsWith("B")) {
        res = res.substring(0, res.length-1);
        return parseFloat(res)*1000000;
    }
    return parseFloat(res);
}
            </script>
</body>
</html>