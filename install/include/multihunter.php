<?php

$gameinstall = 1;

include ("../../GameEngine/config.php");
include("../../GameEngine/Database/MysqliModel.php");
include("../../GameEngine/Protection.php");
include ("../../GameEngine/Admin/database.php");
include ("../../GameEngine/Lang/" . LANG . ".php");

if(isset($_POST['mhpw'])) {
    $password = $_POST['mhpw'];
    $database->setMHpass($password);
    
    $wid = $admin->getWref(0, 0);
    $uid = 5;
    $status = $database->getVillageState($wid);
    if($status == 0) {
        $database->setFieldTaken($wid);
        $database->addVillage($wid, $uid, 'Multihunter', '0');
        $database->addResourceFields($wid, $database->getVillageType($wid));
        $database->addUnits($wid);
        $database->addTech($wid);
        $database->addABTech($wid);
    }
}

$gameinstall = 0;

header("Location: ../index.php?s=5");
