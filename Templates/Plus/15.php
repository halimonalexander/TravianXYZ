<?php

if($session->access == BANNED) {
    header("Location: banned.php");
} elseif ($session->gold < 100) {
    $done1 = "You need more gold";
    
    header("Location: plus.php?id=3");
} else {
    if ($session->sit == 0 && $session->goldclub == 0) {
        $database->query("UPDATE " . TB_PREFIX . "users set goldclub = 1, gold = gold - 100 where `id`='" . $session->uid . "'");
    }
    
    header("Location: plus.php?id=3");
}
