<?php
if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') die();

session_start();
require '../lib/base.php';
require '../lib/market.php';
require '../lib/session.php';
require '../lib/assets.php';
require '../lib/contracts.php';

RefreshTokens();
UpdateAllOrders_TrackedMarket();
Update_TrackedStation_Prices();
UpdateAllSoldItems();
UpdateAssetsFromESI(GetMainCharID());
UpdateCorpContractsFromAPI($main_corp_id);
FindCorporateFitContracts($main_corp_id);

$stmt = $conn->prepare("UPDATE `timers` SET time=NOW() WHERE name='market'");
$stmt->execute();
?>
