<?php

require_once "assets.php";

function AddFit($fit_export) {
    global $conn;
    $xml=simplexml_load_string($fit_export) or die("Error: Cannot create object");

    //go through all fits in the XML
    foreach($xml->children() as $fitting) {
        $fit_items = array();
        $attr = $fitting->attributes();
        $fit_name = (string)$attr["name"];
        $ship_type = (string)$fitting->shipType->attributes()["value"];
        //Parse the fit details into array
        foreach($fitting->hardware as $hardware) {
            $item_id = GetItemIDFromName($hardware->attributes()["type"]);
            $item_amount = 1;
            if (isset($hardware->attributes()["qty"])) $item_amount = (int)$hardware->attributes()["qty"];
            if (array_key_exists($item_id, $fit_items)) {
                $fit_items[$item_id] += $item_amount;
            } else {
                $fit_items[$item_id] = $item_amount;
            }
        }

        //Unassigned previous fits
        ExecuteQuery("UPDATE contracts_corporation_all SET fit_id=-1 WHERE status='outstanding'");

        //Check if fit already exists
        $res = FetchAllEntries("SELECT * FROM fits WHERE name='".$fit_name."' and ship='".$ship_type."'");
        
        if (count($res) > 0) {
          //Update fit details
          $last_id = $res[0]["id"];
          ExecuteQuery("DELETE FROM fits_items WHERE fit_id=".$last_id);
        } else {
          //Add fit 
            $stmt = $conn->prepare("INSERT INTO fits VALUES (null, ?, ?,1);");
            $stmt->bind_param("ss",$fit_name,$ship_type);
            $stmt->execute();
            $last_id = $conn->insert_id;
        }

        foreach($fit_items as $item_id => $item_amount) {
            $stmt = $conn->prepare("INSERT INTO fits_items VALUES (?, ?, ?);");
            $stmt->bind_param("iii",$last_id,$item_id,$item_amount);
            $stmt->execute();
        }
    }
}

function GetDoctrineContracts() {
    global $conn;
    global $main_character_id;
    global $main_corp_id;
    $needed_items = array();
    $needed_ammo = array();
    $ammo_types = GetAmmoGroupIDs();
    $ammo_for_min_fits = 10; //have stock ammo for at least 10 fits. Pick the highest number
    $total_required = 0;
    $total_available = 0;

    $stmt = $conn->prepare("select * from fits;");
    $stmt->execute();
    $result = $stmt->get_result();

    $contracts = [];
    while($row = $result->fetch_assoc()) {

        $content = [];
        $needed_ammo_fit = array();
        $color = '';
        $ship_type = $row["ship"];
        $fit_name = $row["name"];
        $fit_id = $row["id"];
       
        //hull
        $id = GetItemIDFromName($ship_type);
        $price = GetJitaSellPriceOf($id);
        $min_manufacturing = GetMJVolumeOf($id);
        $content[$id] = array("amount"=>1,"name"=>$ship_type, "seeded"=>$min_manufacturing);

        //Get fit items
        $stmt2 = $conn->prepare("select * from fits_items where fit_id=".$fit_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while($row2 = $result2->fetch_assoc()) {
            $item_id = $row2["item_id"];
            $item_name = GetItemNameFromID($item_id);
            $item_group_id = GetGroupIDFromID($item_id);
            $item_seed = GetMJVolumeOf($item_id);
            if (isset($content[$item_id])) {
                $content[$item_id]["amount"] += $row2["item_amount"];
            } else {
                $content[$item_id] = array("amount"=>$row2["item_amount"],"name"=>$item_name, "seeded"=>$item_seed);
            }
            if (isset($assets[$item_id]))
                $seeded = $assets[$item_id];
            else
                $seeded = 0;
            $required = $row2["item_amount"];
            $price += GetJitaSellPriceOf($item_id) * $row2["item_amount"];
            if ($min_manufacturing > $seeded/$required) {
                $min_manufacturing = $seeded/$required;
            }
            if (isset($_POST["fit_".$fit_id]) && $_POST["fit_".$fit_id] > 0) {
                if (isset($needed_items[$item_id]))
                    $needed_items[$item_id] += $_POST["fit_".$fit_id] * $required;
                else
                    $needed_items[$item_id] = $_POST["fit_".$fit_id] * $required;
            }

            if (in_array($item_group_id, $ammo_types)) {
                if (!isset($needed_ammo_fit[$item_id]))
                    $needed_ammo_fit[$item_id] = array("amount"=>$required, "name"=>$item_name, "seeded"=>$item_seed); 
                else
                    $needed_ammo_fit[$item_id]['amount'] += $required;
                //item in fit is ammo type
                if (isset($needed_ammo[$item_id])) {
                    if ($required * $ammo_for_min_fits > $needed_ammo[$item_id])
                        $needed_ammo[$item_id] = $required * $ammo_for_min_fits;
                }
                else
                    $needed_ammo[$item_id] = $required * $ammo_for_min_fits;
            }
        }

        //count how many are available on contracts
        $c_available = 0;
        $a_available = 0;
        $mine = 0;
        $min_price = 999999999;

        $fit_contracts = FetchAllEntries("select * from contracts_corporation_all where status='outstanding' and date_expired>now() and fit_id=".$fit_id);
        foreach($fit_contracts as $contract) {
            if ($contract['price'] < $min_price) $min_price = $contract['price'];
            if ($contract['issuer_id'] == $main_character_id) $mine += 1;
            if ($contract['assignee_id'] == $main_corp_id) $c_available += 1; else $a_available += 1;
        }
        $available = $c_available + $a_available;
        $isZero = ($available >= $row['required']) ? "nonzero" : "zero";

        $contract = array("content"=>$content,"needed_ammo_fit"=>$needed_ammo_fit,"min_manufacturing"=>$min_manufacturing,"price"=>$price,"mine"=>$mine,"min_price"=>$min_price,"available"=>$available,"required"=>$row['required'],"isZero"=>$isZero, "c_available"=>$c_available, "a_available"=>$a_available, "ship_type"=>$ship_type, "fit_name"=>$fit_name, "fit_id"=>$fit_id);
        $contracts[] = $contract;
    }
    return $contracts;
}

$needed_ammo = array();
function PrintContractStats($isEnhanced) {
    global $conn;
    global $main_character_id;
    global $main_corp_id;
    global $needed_ammo;

    $assets = GetAllAssets();
    $contracts = GetCorpContracts();
    $needed_items = array();
    $needed_ammo = array();
    $ammo_types = GetAmmoGroupIDs();
    $ammo_for_min_fits = 10; //have stock ammo for at least 10 fits. Pick the highest number
    $total_required = 0;
    $total_available = 0;

    PrintContractStatistics();

    echo '<h3>Doctrine ships</h3><table class="markettable"><tr><td style=\'text-align: center\' width=100>Hull</td><td style=\'text-align: center\' width=130>Fit Name</td>';
    echo '<td width=50>On Contracts<br/><span style=\'font-size:9pt\'>(corp/alliance)</span></td><td width=50>Required</td><td width=50>Stock Level</td><td>7d vol</td>';
    
    if ($isEnhanced) echo '<td width=70>Mine</td><td width=70>Fits ready</td><td width=90>Lowest contract</td><td width=90>Jita Sell</td></tr>';

    $doctrine_contracts = GetDoctrineContracts();
    foreach($doctrine_contracts as $contract) {
        if ($contract["required"] == 0) continue;
        //Check if required amount is achieved
        $class = "totalsell";
        if ($contract["available"] < $contract['required']) $class = "totalcost";
        
        echo "<tr class='".$contract["isZero"]."'><td style='text-align: center'>".$contract['ship_type']."</td><td style='text-align: center'><a href='#fit_".$contract['fit_id']."'>".$contract['fit_name']."</a></td>";
        echo "<td class='".$class."' style='text-align: center'>".$contract['available']." <br/><span style='font-size:9pt'>(".$contract['c_available']." / ".$contract['a_available'].")</span></td>";  //-> how many available on contracts
        echo "<td class='".$class."' style='text-align: center'>".$contract['required']."</td>"; //->how many are required
        echo "<td class='".$class."' style='text-align: center'>".round($contract['available']/$contract['required']*100)."%"."</td>"; //->stock level
        
        $total_val = FetchAllEntries("select count(*) as total from contracts_corporation_all where status='finished' and fit_id=".$contract['fit_id']." and date_accepted > DATE(NOW() - INTERVAL 7 DAY);");
        echo "<td class='".$class."' style='text-align: center'>".$total_val[0]['total']."</td>"; //->7day volume

        $total_required += $contract['required'];
        $total_available += $contract['available'] > $contract['required'] ? $contract['required'] : $contract['available'];

        if ($isEnhanced) {
            echo "<td style='text-align: center'>".$contract["mine"]."</td>"; //-> how many contracts I have
            $class = "totalcost";
            if ($contract['min_manufacturing'] > 0) $class = "orange";
            if ($contract['min_manufacturing'] > 10) $class = "totalsell";
            echo "<td class='".$class."' style='text-align: center'>".$contract['min_manufacturing']."</td>";
            echo "<td style='text-align: center'>".($contract['min_price']==999999999 ? "N/A" : ToMillions($contract['min_price'],1))."</td>";
            echo "<td>".ToMillions($contract['price'],1)."</td>";
            echo "<td><input style='width: 50px' type='text' name='fit_".$contract['fit_id']."' value='0'/></td>";
        }
        echo "</tr>";
    }

    //Calculate how many items are needed
    $custom_multibuy = '';
    foreach($needed_items as $item_id => $amount) {
        $inStock = 0;
        if (isset($assets[$item_id])) $inStock = $assets[$item_id];
        $needed_amount = $amount -  $inStock;
        if ($needed_amount > 0) {
            $custom_multibuy .= GetItemNameFromID($item_id) ."\t".($needed_amount)."\r\n";
        }
    }
    if ($isEnhanced) echo "<tr><td><textarea>".$custom_multibuy."</textarea></td></tr>";
    echo ' </table>';
    if ($isEnhanced) echo '<input type="submit" value="create multibuy"/></br></br>';

    echo '<script>document.getElementById("contract_satis").innerHTML="'.round($total_available/$total_required*100).'%"</script>';
}

function PrintMarketStats($isEnhanced) {
        global $needed_ammo;
        global $d7stats;
        global $d30stats;
        //Print ammo table
        $satis = array();
        PrintMarketStatistics();
        $myOrders = array();

        if ($isEnhanced) $myOrders = FetchAllEntriesByKey("select * from my_orders;","type_id");

        $stats7days = GetSellStatsOrdered(7);

            echo '<h3>TOP Traded Items in last 7 day</h3>';
            echo "<table class=\"markettable\">";
            echo "<tr><td onclick=\"sortTable(this)\">Item</td><td onclick=\"sortTable(this)\">Seeded</td><td onclick=\"sortTable(this)\">Sigga Sell</td><td onclick=\"sortTable(this)\">Jita Sell</td><td onclick=\"sortTable(this)\">7d vol</td><td onclick=\"sortTable(this)\">7d vol</td></tr>"; 
            for($i = 0; $i < 20 && $i < count($stats7days); $i++) {
                $item_id = $stats7days[$i]['type_id'];
                $market_price = GetMJPriceOf($item_id);
                $jita_price = GetJitaSellPriceOf($item_id);
                $market_seeded = GetMJVolumeOf($item_id);

                $class = "";
                if ($market_seeded == 0) {
                    $class = "totalcost";
                }
                
                $background = '';
                if (isset($myOrders[$item_id])) $background = 'style="background: #091c17"';

                echo "<tr ".$background."><td>".GetItemNameFromID($item_id)."</td>";
                echo "<td class='".$class."'>".$market_seeded."</td>";
                echo "<td class='".$class."'>".($market_price == 0 ? "N/A" : ToMillions($market_price,1))."</td>";
                echo "<td>".ToMillions($jita_price,1)."</td>";
                echo "<td>".$stats7days[$i]['total_sell_volume']."</td>";
                echo "<td>".ToMillions($stats7days[$i]['total_sell_value'],1)."</td>";
                echo "</tr>";
            }       
            echo "</table>";

        echo '<h3>Ammo on Market</h3>';
        echo 'Required amount = ammo in fit * 10';
        echo "<table class=\"markettable\">";
        echo "<tr><td onclick=\"sortTable(this)\">Ammo</td><td onclick=\"sortTable(this)\">Seeded</td><td onclick=\"sortTable(this)\">Needed</td><td onclick=\"sortTable(this)\">Sigga Sell</td><td onclick=\"sortTable(this)\">Jita Sell</td><td onclick=\"sortTable(this)\">Markup</td><td onclick=\"sortTable(this)\">7d vol</td><td onclick=\"sortTable(this)\">30d vol</td></tr>";
        $ammo_multibuy = '';
        foreach($needed_ammo as $ammo_id => $ammo_amount) {
            $name = GetItemNameFromID($ammo_id);
            $ammo_on_market = GetMJVolumeOf($ammo_id);
            $class = "totalsell";
            if ($ammo_amount-$ammo_on_market > 0) {
                $ammo_multibuy .= $name . "\t" . ($ammo_amount-$ammo_on_market) . "\r\n";
                $class = "orange";
            }
            if ($ammo_on_market == 0) $class = "totalcost";
            $market_price = GetMJPriceOf($ammo_id);
            $jita_price = GetJitaSellPriceOf($ammo_id);
            $markup = round($market_price/$jita_price*100)-100;
            $isZero = ($ammo_on_market >= $ammo_amount) ? "nonzero" : "zero";

            $background = '';
            if (isset($myOrders[$ammo_id])) $background = 'style="background: #091c17"';

            echo "<tr ".$background." class='".$isZero."'>";
            echo "<td class='".$class."'>".$name."</td>";
            echo "<td class='".$class."'>".$ammo_on_market."</td>";
            echo "<td class='".$class."'>".$ammo_amount."</td>";
            echo "<td>".ToMillions($market_price,1)."</td>";
            echo "<td>".ToMillions($jita_price,1)."</td>";
            echo "<td class='".($markup > 0 ? 'totalsell' : 'totalcost')."'>".$markup."%</td>";
            echo "<td>".(isset($d7stats[$ammo_id])?$d7stats[$ammo_id]:0)."</td>";
            echo "<td>".(isset($d30stats[$ammo_id])?$d30stats[$ammo_id]:0)."</td>";
            echo "</tr>";
        }
        echo "</table></br>";
    
        if ($isEnhanced) {
            echo "Grocery list</br>";
            echo "<textarea>".$ammo_multibuy."</textarea>";
        }

            //print miners supplies
            echo "<h3>Random ammo supplies</h3>";
            $satis[] = PrintMarketTable(GetAmmoIDs(), $d7stats,$d30stats, $myOrders);

            echo "<h3>Mining supplies</h3>";
            $satis[] = PrintMarketTable(GetMinersSuppliesIDs(), $d7stats,$d30stats, $myOrders);

            echo "<h3>Exploration supplies</h3>";
            $satis[] = PrintMarketTable(GetExplorationSuppliesIDs(), $d7stats,$d30stats, $myOrders);

            echo "<h3>Common modules</h3>";
            $satis[] = PrintMarketTable(GetCommonModulesIDs(), $d7stats,$d30stats, $myOrders);

            echo "<h3>Drones</h3>";
            $satis[] = PrintMarketTable(GetDronesIDs(), $d7stats,$d30stats, $myOrders);

            echo "<h3>Hulls</h3>";
            $satis[] = PrintMarketTable(GetHullsIDs(), $d7stats,$d30stats, $myOrders);

            echo "<h3>Misc</h3>";
            $satis[] = PrintMarketTable(GetMiscIDs(), $d7stats,$d30stats, $myOrders);

            $satis[] = PrintMarketTable(GetMineIds(), $d7stats,$d30stats, $myOrders);
            echo "<h3>Weapons</h3>";
            $satis[] = PrintMarketTable(GetWeapons(), $d7stats,$d30stats, $myOrders);
            echo "<h3>Doctrine modules</h3>";
            PrintMarketTable(GetDoctrineModules(), $d7stats,$d30stats, $myOrders);
            echo "<h3>Filaments</h3>";
            PrintMarketTable(GetFilaments(), $d7stats,$d30stats, $myOrders);
            
            $available = 0;
            $required = 0;
            foreach($satis as $sati) {
                $available += $sati[1];
                $required += $sati[0];
            }
            $percentage = round($available/$required*100);
            $color = '#005800';
            if ($percentage < 90) $color = '#ff8f00';
            if ($percentage < 50) $color = '#ff0000';
            echo '<script>document.getElementById("market_satis").innerHTML="'.$percentage.'%";</script>';
}

function PrintFits($isEnhanced) {
    global $conn;
    $stmt = $conn->prepare("select * from fits;");
    $stmt->execute();
    $result = $stmt->get_result();
   
    while($row = $result->fetch_assoc()) {
        $color = '';
        $hull = $row["ship"];
        $fit_name = $row["name"];
        $fit_id = $row["id"];
        echo "<tr id='fit_".$fit_id."'><th colspan=4 style='text-align: center'>".$fit_name."</td></tr>";
        echo "<tr><td></td><td width=300>Name</td><td width=100>Required</td><td width=100>Seeded</td><td>Price</td></tr>";
        //Add hull
        $hull_id = GetItemIDFromName($hull);
        $seeded = GetMJVolumeOf($hull_id);
        if ($seeded / 1 < 5) $color = "#694200";
        if ($seeded < 1) $color = "#550000";
        echo "<tr style='background-color: ".$color."'><td>".$hull_id."</td><td>".$hull."</td><td>1</td><td>".floor($seeded)."</td><td>".ToMillions(GetMJPriceOf($hull_id),1)."</td>";
        if ($isEnhanced && IsT1($hull_id)) {
            echo "<td>T1</td>";
        }
        echo "</tr>";        
        //Get fit items
        $stmt2 = $conn->prepare("select * from fits_items where fit_id=".$fit_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $multibuy = '';
        $multibuy2 = $hull."\r\n";
        while($row2 = $result2->fetch_assoc()) {
            $color = '';
            $seeded = GetMJVolumeOf($row2["item_id"]);
            $required = $row2["item_amount"];
            if ($seeded / $required < 5) $color = "#694200";
            if ($seeded < $required) $color = "#550000";
            $item_name = GetItemNameFromID($row2["item_id"]);
            echo "<tr style='background-color: ".$color."'><td>".$row2["item_id"]."</td><td>".$item_name."</td><td>".$required."</td><td>".$seeded."</td><td>".ToMillions(GetMJPriceOf($row2["item_id"]),1)."</td>";
            if ($isEnhanced && IsT1($row2["item_id"])) {
                echo "<td>T1</td>";
            }
            echo "</tr>";
            if ($seeded / $required < 5) {
                $multibuy .= $item_name."\t".($required*5-$seeded)."\r\n";
            }
            $multibuy2 .= $item_name."\t".($required)."\r\n";
        }
        echo "<tr><td colspan=3 style='text-align: center'><b>";
        
        if ($isEnhanced) {
            echo "<span>multibuy 5 using leftovers</span><br/>";
            echo "<textarea id='multibuy_".$fit_id."'>".$multibuy."</textarea><br/>";
            echo "<span>multibuy 1 full</span><br/>";
            echo "<textarea id='multibuy2_".$fit_id."'>".$multibuy2."</textarea>";
        } else {
            echo "------";
        }

        echo "</b></td></tr>";
    }
}

function PrintMarketTable($ids, $d7stats,$d30stats, $myOrders) {
    echo "<table class=\"markettable\">";
    echo "<tr><td>Item</td><td>Available</td><td onclick=\"sortTable(this)\">Sigga Sell</td><td onclick=\"sortTable(this)\">Jita Sell</td><td onclick=\"sortTable(this)\">Markup</td><td onclick=\"sortTable(this)\">7d volume</td><td onclick=\"sortTable(this)\">30d volume</td></tr>";
    $total = 0;
    $satisfied = 0;
    foreach($ids as $id) {
        $total += 1;
        $name = GetItemNameFromID($id);
        $amount = GetMJVolumeOf($id);
        $class = "totalcost";
        if ($amount > 0) {
            $class = "totalsell";
            $satisfied += 1;
        }
        $market_price = GetMJPriceOf($id);
        $jita_price = GetJitaSellPriceOf($id);
        $markup = round($market_price/$jita_price*100)-100;
        $isZero = ($amount > 0) ? "nonzero" : "zero";

        $background = '';
        if (isset($myOrders[$id])) $background = 'style="background: #091c17"';

        echo "<tr ".$background." class='".$isZero."'>";
        echo "<td class='".$class."'>".$name."</td>";
        echo "<td class='".$class."'>".$amount."</td>";
        echo "<td>".ToMillions($market_price,1)."</td>";
        echo "<td>".ToMillions($jita_price,1)."</td>";
        echo "<td class='".($markup > 0 ? 'totalsell' : 'totalcost')."'>".$markup."%</td>";
        $volume = 0;
        if (isset($d7stats[$id])) $volume = $d7stats[$id];
        echo "<td>".$volume."</td>";
        $volume = 0;
        if (isset($d30stats[$id])) $volume = $d30stats[$id];
        echo "<td>".$volume."</td>";
        echo "</tr>";
    }

    echo "</table></br>";
    return array($total, $satisfied);
}

function PrintContractStatistics() {
    global $main_corp_id;
    echo "<h2>Overview</h2>";
    echo "<table>";
    $total_val = FetchAllEntries("select sum(price) as total from contracts_corporation_all where fit_id>0 and status='outstanding' and date_expired>now()");
    echo "<tr><td>Total value of ships on contracts:</td><td><b>".ToMillions($total_val[0]['total'],1)."</b></td></tr>";
    $total_val = FetchAllEntries("select sum(price) as total from contracts_corporation_all where status='finished' and fit_id > 0 and date_accepted > DATE(NOW() - INTERVAL 7 DAY);");
    echo "<tr><td>Total value of accepted contracts (7d):</td><td><b>".ToMillions($total_val[0]['total'],1)."</b></td></tr>";
    $total_c = FetchAllEntries("select count(contract_id) as val from contracts_corporation_all where fit_id>0 and status='outstanding' and date_expired>now() and assignee_id=".$main_corp_id);
    $total_a = FetchAllEntries("select count(contract_id) as val from contracts_corporation_all where fit_id>0 and status='outstanding' and date_expired>now() and assignee_id!=".$main_corp_id);
    echo "<tr><td>Total number of contracts (all/corp):</td><td><b>".$total_a[0]['val']." / ".$total_c[0]['val']."</b></td></tr>";
    echo "</table>";

    $stats = FetchAllEntries("select * from contracts_corporation_daily_stats");
    $labels = array();
    $datas = array();
    $datas2 = array();
    $datas3 = array();
    foreach($stats as $stat) {
        $date = explode("-", $stat['date']);
        $labels[] = "'".$date[1]."/".$date[2]."'";
        $datas[] = $stat['total_ship_value']/1000000000;
        $datas2[] = $stat['total_sold_value']/1000000000;
    }

    echo '<div style="padding-top:10px;width: 500px; margin: 0 auto;"><canvas id="contractStatistics"></canvas></div>';
    echo "<script>
    const ctx = document.getElementById('contractStatistics');
    const DISPLAY = true;
    const BORDER = true;
    const CHART_AREA = true;
    const TICKS = true;

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: [".implode(",",$labels)."],
        datasets: [{
          label: 'Total ship value',
          data: [".implode(",",$datas)."],
          yAxisID: 'A',
          borderWidth: 1,
          tension: 0.4
        },
        {
            label: 'Value of sold ships',
            data: [".implode(",",$datas2)."],
            yAxisID: 'B',
          borderWidth: 1,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        scales: {
            A: {
                title: {
                    display: true,
                    text: 'Value in B ISK',
                    color: '#36A2EB',
                },
                ticks: {
                    color: '#36A2EB',
                },
                type: 'linear',
                position: 'left',
                grid: {
                    color: function(context) {return '#dddddd2e';},
                }
            },
            B: {
                title: {
                    display: true,
                    text: 'Value in B ISK',
                    color: '#FF6384',
                },
                ticks: {
                    color: '#FF6384',
                },
                type: 'linear',
                position: 'right',
            },
            
          x: {
            border: {
              display: BORDER
            },
            grid: {
              display: DISPLAY,
              drawOnChartArea: CHART_AREA,
              drawTicks: TICKS,
            }
          },
          y: {
            display: false,
            border: {
              display: false
            },
          }
        }
      },
    });
    </script>";
}

function PrintMarketStatistics() {
    echo "<h2>Overview</h2>";
    echo "<table>";
    
    $total_val = FetchAllEntries("select sum(price*volume_remain) as total from tracked_market where is_buy=0;");
    echo "<tr><td>Total value of sell orders:</td><td><b>".ToMillions($total_val[0]['total'],1)."</b></td></tr>";
    $total_val = FetchAllEntries("SELECT SUM(sold_value) AS total_sell_value FROM tracked_market_sell_volumes WHERE date >= DATE_SUB(NOW(), INTERVAL 1 DAY);");
    echo "<tr><td>Total traded volume (today):</td><td><b>".ToMillions($total_val[0]['total_sell_value'],1)."</b></td></tr>";
    $total_val = FetchAllEntries("SELECT SUM(sold_value) AS total_sell_value FROM tracked_market_sell_volumes WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY);");
    echo "<tr><td>Total traded volume (7days):</td><td><b>".ToMillions($total_val[0]['total_sell_value'],1)."</b></td></tr>";
    $total_val = FetchAllEntries("select count(distinct type_id) as total from tracked_market where is_buy = 0;");
    echo "<tr><td>Number of unique items on sale:</td><td><b>".$total_val[0]['total']."</b></td></tr>";
    echo "</table>";
    echo '<div style="padding-top:10px;width: 500px; margin: 0 auto;"><canvas id="marketStatistics"></canvas></div>';
    $market_stats = FetchAllEntries("select * from tracked_market_daily_stats");
    $labels = array();
    $datas = array();
    $datas2 = array();
    $datas3 = array();
    foreach($market_stats as $stat) {
        $date = explode("-", $stat['date']);
        $labels[] = "'".$date[1]."/".$date[2]."'";
        $datas[] = $stat['total_sell_price']/1000000000;
        $datas2[] = $stat['total_sold_value']/1000000000;
        $datas3[] = $stat['num_of_items'];
    }
    echo "
    <script>
    const ctx2 = document.getElementById('marketStatistics');

    new Chart(ctx2, {
      type: 'line',
      data: {
        labels: [".implode(",",$labels)."],
        datasets: [{
          label: 'Sell orders',
          data: [".implode(",",$datas)."],
          yAxisID: 'A',
          borderWidth: 1,
          tension: 0.4
        },
        {
            label: 'Traded volume',
            data: [".implode(",",$datas2)."],
            yAxisID: 'B',
          borderWidth: 1,
          tension: 0.4
        },
        {
            label: 'Unique items',
            data: [".implode(",",$datas3)."],
            yAxisID: 'C',
          borderWidth: 1,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        scales: {
            A: {
                title: {
                    display: true,
                    text: 'Sell orders in B ISK',
                    color: '#36A2EB',
                },
                ticks: {
                    color: '#36A2EB',
                },
                type: 'linear',
                position: 'left',
                grid: {
                    color: function(context) {return '#dddddd2e';},
                }
            },
            B: {
                title: {
                    display: true,
                    text: 'Traded volume in B ISK',
                    color: '#FF6384',
                },
                ticks: {
                    color: '#FF6384',
                },
                type: 'linear',
                position: 'right',
            },
            C: {
                title: {
                    display: true,
                    text: 'Number of unique items on sale',
                    color: '#FF9F40',
                },
                ticks: {
                    color: '#FF9F40',
                },
                type: 'linear',
                position: 'right',
            },
          x: {
            border: {
              display: BORDER
            },
            grid: {
              display: DISPLAY,
              drawOnChartArea: CHART_AREA,
              drawTicks: TICKS,
            }
          },
          y: {
            display: false,
            border: {
              display: false
            },
          }
        }
      },
    });
    </script>
    ";
}

function PrintContractSales($isEnhanced) {
  $sales = FetchAllEntries("select * from contracts_corporation_all where status='outstanding' and date_expired>now() and fit_id=-2 and price>0;");

  echo '<table  class="markettable">';
  echo '<tr><td>Title</td><td>Price</td><td>Jita Sell</td><td>Jita Buy</td><td>Items</td></tr>';

  foreach($sales as $sale) {
    if (strpos($sale['title'], "BR Indy") !== FALSE) continue;
    $items = FetchAllEntries("select * from contracts_corporation_items where contract_id=".$sale['contract_id'].";");
    $item_text = '';
    $jita_sell = 0;
    $jita_buy = 0;
    foreach($items as $item) {
      $item_text .= $item['quantity']."x ".GetItemNameFromID($item['type_id'])."<br/>";
      $jita_sell += $item['quantity'] * GetJitaSellPriceOf($item['type_id']);
      $jita_buy += $item['quantity'] * GetJitaBuyPriceOf($item['type_id']);
    }
    echo '<tr><td>'.$sale['title'].'</td><td>'.ToMillions($sale['price'],1).'</td><td>'.$jita_sell.'</td><td>'.$jita_buy.'</td><td>'.$item_text.'</td></tr>';
  }

  echo '</table>';
}

?>