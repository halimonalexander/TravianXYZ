<?php

if($session->access == BANNED) {
    header("Location: banned.php");
} elseif ($session->gold < 5) {
    $done1 = "You need more gold";
    
    header("Location: plus.php?id=3");
} else {
    $MyGold = $database->query("SELECT * FROM " . TB_PREFIX . "users WHERE `id`='" . $session->uid . "'");
    $golds = $database->fetchArray($MyGold);
    
    $MyId = $database->query("SELECT * FROM " . TB_PREFIX . "users WHERE `id`='" . $session->uid . "'");
    $uuid = $database->fetchArray($MyId);
    
    $MyVilId = $database->query("SELECT * FROM " . TB_PREFIX . "bdata WHERE `wid`='" . $village->wid . "'");
    $uuVilid = $database->fetchArray($MyVilId);
    
    $goldlog = $database->query("SELECT * FROM " . TB_PREFIX . "gold_fin_log");
    
    $today = date("mdHi");
    if ($session->sit == 0) {
        if ($database->numRows($MyGold)) {
            if ($golds['6'] > 2) {
                
                if ($database->numRows($MyGold)) {
                    
                    if ($golds['b4'] < time()) {
                        $database->query("UPDATE " . TB_PREFIX . "users set b4 = '" . (time() + PLUS_PRODUCTION) . "' where `id`='" . $session->uid . "'");
                    }
                    else {
                        $database->query("UPDATE " . TB_PREFIX . "users set b4 = '" . ($golds['b4'] + PLUS_PRODUCTION) . "' where `id`='" . $session->uid . "'");
                    }
                    
                    $done1 = "+25% Production: Crop";
                    $database->query("UPDATE " . TB_PREFIX . "users set gold = " . ($session->gold - 5) . " where `id`='" . $session->uid . "'");
                    $database->query("INSERT INTO " . TB_PREFIX . "gold_fin_log VALUES ('" . ($database->numRows($goldlog) + 1) . "', '" . $village->wid . "', '+25%  Production: Crop')");
                }
                else {
                    $done1 = "nothing has been done";
                    $database->query("INSERT INTO " . TB_PREFIX . "gold_fin_log VALUES ('" . ($database->numRows($goldlog) + 1) . "', '" . $village->wid . "', 'Failed +25%  Production: Crop')");
                }
            }
            else {
                $done1 = "You need more gold";
            }
        }
    }
    
    header("Location: plus.php?id=3");
}
