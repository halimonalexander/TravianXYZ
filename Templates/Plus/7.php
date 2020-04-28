<?php

if($session->access == BANNED){
    header("Location: banned.php");
} else {
    $building->finishAll();
    
    header("Location: plus.php?id=3");
}
