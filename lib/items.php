<?php

//Global Vars
$ItemVolumes = array();
$ItemNames = array();
$ItemIDs = array();

LoadItemVolumes();

function LoadItemVolumes() {
  global $ItemVolumes;
  global $conn;
  if (isset($ItemVolumes[0])) {
      return;
  }
  $ItemVolumes[0] = 0;
  $stmt = $conn->prepare("SELECT * FROM volumes");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
      $ItemVolumes[$row["type_id"]] = $row["volume"];
  }
}

function GetVolumeOf($type_id) {
  global $ItemVolumes;
  //Check if it is cached
  if (isset($ItemVolumes[$type_id])) {
      return $ItemVolumes[$type_id];
  } else {
      $volume = AddItemVolume($type_id);
      $ItemVolumes[$type_id] = $volume;
      return $volume;
  }
}

function GetItemNameFromID($type_id) {
    global $conn;
    global $ItemNames;

    //Check cache
    if (isset($ItemNames[$type_id])) return $ItemNames[$type_id];

    $stmt = $conn->prepare("SELECT typeName FROM invTypes WHERE typeID=?");
    $stmt->bind_param("i",$type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $name = $result->fetch_assoc()["typeName"];
      $ItemNames[$type_id] = $name;
      return $name;
    } else {
      return "Name not found";
    }
  }

  function GetGroupIDFromID($type_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT groupID FROM invTypes WHERE typeID=?");
    $stmt->bind_param("i",$type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      return $result->fetch_assoc()["groupID"];
    } else {
      return -1;
    }
  }

  function GetItemIDFromName($name) {
    global $conn;
    global $ItemIDs;

    //Check cache
    if (isset($ItemIDs[$name])) return $ItemIDs[$name];
    $stmt = $conn->prepare("SELECT typeID FROM invTypes WHERE typeName=?");
    $stmt->bind_param("s",$name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $id = $result->fetch_assoc()["typeID"];
        $ItemIDs[$name] = $id;
      return $id;
    } else {
      return "Name not found";
    }
  }

  function IsBlueprint($typeID) {
    global $blueprintIds;
    global $conn;
  
    if (count($blueprintIds) == 0) {
      $stmt = $conn->prepare("select typeID from industryActivityProducts where activityID=1;");
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) array_push($blueprintIds, $row["typeID"]);
    }
  
    return in_array($typeID, $blueprintIds);
  }

  $blueprintProductIds = array();
function GetBlueprintProductId($typeID) {
  global $blueprintProductIds;
  global $conn;

  if (count($blueprintProductIds) == 0) { 
    $stmt = $conn->prepare("select typeID,productTypeID from industryActivityProducts where activityID=1;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $blueprintProductIds[$row["typeID"]] = $row["productTypeID"];
  }

  if (isset($blueprintProductIds[$typeID])) {
    return $blueprintProductIds[$typeID];
  }
  return -1;
}

function GetBlueprintProduct($BlueprintID) {
    global $blueprintProducts;
    global $conn;
  
    if (count($blueprintProducts) == 0) {
      $stmt = $conn->prepare("select typeID,productTypeID from industryActivityProducts where activityID=1;");
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) $blueprintProducts[$row["typeID"]] = $row["productTypeID"];
    }
  
    return $blueprintProducts[$BlueprintID];
  }
  
  function IsT1($type_id) {
    global $blueprints;
    GetProductInfo($type_id);
    $T1minerals = GetMineralsIDs();
    if (!isset($blueprints[$type_id])) return false;
    foreach($blueprints[$type_id] as $blueprint) {
        if (!in_array($blueprint["material_id"], $T1minerals)) return false;
    }
    return true;
  }
  
  $blueprints = array();
  function GetBlueprints() {
    global $conn;
    global $blueprints;
    if (count($blueprints) > 0) return;
  
    //Get blueprint details
    $stmt = $conn->prepare("select * from manufacture_recipes;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($blueprints[$row["product_id"]])) {
            $blueprints[$row["product_id"]] = array();
        }
        array_push($blueprints[$row["product_id"]], $row);
    }
  }

  function isOre($id) {
    if (!isset($groups[$id])) {
        global $conn;
        $stmt = $conn->prepare("select groupid from invTypes where typeID = ?");
        $stmt->bind_param("i",$id); 
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $groups[$id] = $row["groupid"];
    }
            //veldespar               Plagioclase              scordide                pyroxeres
    return $groups[$id] == 462 || $groups[$id] == 458 || $groups[$id] == 460 || $groups[$id] == 459
         || $groups[$id] == 34 || $groups[$id] == 35 || $groups[$id] == 36;
}

function isIce($id) {
    if (!isset($groups[$id])) {
        global $conn;
        $stmt = $conn->prepare("select groupid from invTypes where typeID = ?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $groups[$id] = $row["groupid"];
    }
    return $groups[$id] == 465;
}

?>