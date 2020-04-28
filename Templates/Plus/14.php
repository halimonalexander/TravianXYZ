<?php

if($session->access == BANNED) {
    header("Location: banned.php");
} elseif ($session->gold < 3) {
    $done1 = "You need more gold";
    
    header("Location: plus.php?id=3");
} else {
    $MyGold = $database->query("SELECT * FROM " . TB_PREFIX . "users WHERE `id`='" . $session->uid . "'");
    $golds = $database->fetchArray($MyGold);
    
    $MyVilId = $database->query("SELECT * FROM " . TB_PREFIX . "vdata WHERE `wref`='" . $village->wid . "'");
    $uuVilid = $database->fetchArray($MyVilId);
    
    $totalT = ($T1 + $T2 + $T3 + $T4);
    $totalR = ($uuVilid['6'] + $uuVilid['7'] + $uuVilid['8'] + $uuVilid['10']);
    
    $goldlog = $database->query("SELECT * FROM " . TB_PREFIX . "gold_fin_log");
    
    if ($totalT <= $totalR) {
        $database->query("UPDATE " . TB_PREFIX . "vdata set wood = '" . $T1 . "' where `wref`='" . $village->wid . "'");
        $database->query("UPDATE " . TB_PREFIX . "vdata set clay = '" . $T2 . "' where `wref`='" . $village->wid . "'");
        $database->query("UPDATE " . TB_PREFIX . "vdata set iron = '" . $T3 . "' where `wref`='" . $village->wid . "'");
        $database->query("UPDATE " . TB_PREFIX . "vdata set crop = '" . $T4 . "' where `wref`='" . $village->wid . "'");
        $database->query("UPDATE " . TB_PREFIX . "users set gold = " . ($session->gold - 3) . " where `id`='" . $session->uid . "'");
        $database->query("INSERT INTO " . TB_PREFIX . "gold_fin_log VALUES ('" . ($database->numRows($goldlog) + 1) . "', '" . $village->wid . "', 'trade 1:1')");
        echo "done";
    }
    else {
        echo "failed";
        $database->query("INSERT INTO " . TB_PREFIX . "gold_fin_log VALUES ('" . ($database->numRows($goldlog) + 1) . "', '" . $village->wid . "', 'Failed trade 1:1')");
    }
    
    header("Location: plus.php?id=3");
}