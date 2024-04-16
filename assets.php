<?php 

function UpdateAssetsFromESI($char_id) {
    global $conn;
    $mineral_ids = GetMineralsIDs();

    $res = DownloadAllAPIPages("https://esi.evetech.net/latest/characters/".$char_id."/assets/?datasource=tranquility&", $char_id);
    if ($res === FALSE) {
        echo "[UpdateAssetsFromESI] FAILED.\n";
        return;
    }

    $stmt = $conn->prepare("delete from assets");
    $stmt->execute();
    
    foreach($res as $asset) {
        $stmt = $conn->prepare("INSERT INTO `assets` VALUES(?, ?, ?,?,?);");
        $iscopy = 0;
        if (isset($asset->is_blueprint_copy)) $iscopy = $asset->is_blueprint_copy ? 1 : 0;
        $stmt->bind_param("iiiii", $asset->item_id, $asset->type_id,$iscopy, $asset->quantity, $asset->location_id);
        $stmt->execute();
    }
}

function GetContainerAssets($id) {
    return FetchAllEntries("SELECT * FROM assets WHERE location=".$id);
}

?>
