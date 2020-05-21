<?php

namespace App\Controllers\Village;

use App\Helpers\ResponseHelper;
use App\Helpers\TraceHelper;
use App\Routes;
use App\Sids\MovementTypeSid;
use GameEngine\Alliance;
use GameEngine\Automation\Automation;
use GameEngine\Building;
use GameEngine\Market;
use GameEngine\Technology;
use GameEngine\Village;
use HalimonAlexander\Registry\Registry;

class BuildingController extends AbstractVillageController
{
    public function displayAction()
    {
        if (
            !isset($_GET['id']) &&
            !isset($_GET['gid']) &&
            !isset($_GET['routeid']) &&
            !isset($_GET['buildingFinish']) &&
            $route != 1 &&
            empty($_POST)
        ) {
            ResponseHelper::redirect(Routes::DORF1);
        }
    
        if (isset($_GET['buildingFinish']) && $_GET['buildingFinish'] == 1) {
            if ($session->gold >= 2) {
                $building->finishAll();
                header("Location: " . \App\Routes::BUILD . "?gid=" . $_GET['id'] . "&ty=" . $_GET['ty']);
            }
        }
        
        if (isset($_GET['s']) && !ctype_digit($_GET['s'])) {
            $_GET['s'] = null;
        }
    
        if (isset($_GET['t']) && !ctype_digit($_GET['t'])) {
            $_GET['t'] = null;
        }
    
        if (!ctype_digit($_GET['id'])) {
            $_GET['id'] = "1";
        }
        
        $this->old(
            (Registry::getInstance())->get('alliance'),
            (Registry::getInstance())->get('automation'),
            (Registry::getInstance())->get('market'),
            (Registry::getInstance())->get('technology'),
            (Registry::getInstance())->get('village')
        );
        
        $this->loadTemplate('building', [
            'start'      => TraceHelper::getTimer(),
            
            'building'   => (Registry::getInstance())->get('building'),
            'generator'  => (Registry::getInstance())->get('generator'),
            'message'    => (Registry::getInstance())->get('message'),
            'session'    => (Registry::getInstance())->get('session'),
            'technology' => (Registry::getInstance())->get('technology'),
            'village'    => (Registry::getInstance())->get('village'),
            'units'      => (Registry::getInstance())->get('units'),
        ]);
    }
    
    private function old(Alliance $alliance, Automation $automation, Market $market, Technology $technology, Village $village)
    {
        if (isset($_GET['newdid'])) {
            $_SESSION['wid'] = $_GET['newdid'];
            ResponseHelper::redirect($_SERVER['PHP_SELF'] . (isset($_GET['id'])?'?id='.$_GET['id']:(isset($_GET['gid'])?'?gid='.$_GET['gid']:'')));
        }
    
        if ($_GET['id'] == 99 && $village->natar == 0){
            ResponseHelper::redirect(\App\Routes::DORF2);
        }
    
        $alliance->procAlliForm($_POST);
        $technology->procTech($_POST);
        $market->procMarket($_POST);
    
        if (isset($_GET['gid'])) {
            /** @var Building $building */
            $_GET['id'] = strval($building->getTypeField(preg_replace("/[^a-zA-Z0-9_-]/","",$_GET['gid'])));
        } elseif(isset($_POST['id'])) {
            $_GET['id'] = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['id']); // WTF is this?
        }
    
        if(isset($_POST['t'])){
            $_GET['t'] = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['t']);
        }
    
        if (isset($_GET['id'])) {
            if (!ctype_digit(preg_replace("/[^a-zA-Z0-9_-]/","",$_GET['id']))) {
                $_GET['id'] = "1";
            }
        
            $checkBuildings = array(0,16,17,25,26,27);
            if ($_GET['id'] < 19 || !in_array($_GET['gid'], $checkBuildings)) {
                $_GET['t'] = "";
                $_GET['s'] = "";
            }
        
            if($village->resarray['f'.$_GET['id'].'t'] == 17) {
                $market->procRemove($_GET);
            }
        
            if($village->resarray['f'.$_GET['id'].'t'] == 18) {
                $alliance->procAlliance($_GET);
            }
        
            if($village->resarray['f'.$_GET['id'].'t'] == 12 || $village->resarray['f'.$_GET['id'].'t'] == 13 || $village->resarray['f'.$_GET['id'].'t'] == 22) {
                $technology->procTechno($_GET);
            }
        }
    
        if ($session->goldclub == 1 && count($session->villages) > 1){
            if (isset($_GET['routeid'])) {
                $routeid = $_GET['routeid'];
            }
        
            if (isset($_POST['action']) && $_POST['action'] == 'addRoute') {
                if($session->access != BANNED){
                    if($session->gold >= 2) {
                        for($i=1;$i<=4;$i++){
                            if($_POST['r'.$i] == ""){
                                $_POST['r'.$i] = 0;
                            }
                        }
                        $totalres = preg_replace("/[^0-9]/","",$_POST['r1'])+preg_replace("/[^0-9]/","",$_POST['r2'])+preg_replace("/[^0-9]/","",$_POST['r3'])+preg_replace("/[^0-9]/","",$_POST['r4']);
                        $reqMerc = ceil(($totalres-0.1)/$market->maxcarry);
                        $second = date("s");
                        $minute = date("i");
                        $hour = date("G")-$_POST['start'];
                        if(date("G") > $_POST['start']){
                            $day = 1;
                        }else{
                            $day = 0;
                        }
                        $timestamp = strtotime("-$hour hours -$second second -$minute minutes +$day day");
                        if($totalres > 0){
                            $database->createTradeRoute($session->uid,$_POST['tvillage'],$village->wid,$_POST['r1'],$_POST['r2'],$_POST['r3'],$_POST['r4'],$_POST['start'],$_POST['deliveries'],$reqMerc,$timestamp);
                            header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                            $route = 1;
                        }else{
                            header("Location: " . \App\Routes::BUILD . "?gid=17&t=4&create");
                            $route = 1;
                        }
                    }
                }else{
                    $route = 0;
                    header("Location: banned.php");
                }
            }
        
            if(isset($_GET['action']) && $_GET['action'] == 'extendRoute') {
                if($session->access != BANNED){
                    if($session->gold >= 2) {
                        $traderoute = $database->getTradeRouteUid($_GET['routeid']);
                        if($traderoute == $session->uid){
                            $database->editTradeRoute($_GET['routeid'],"timeleft",604800,1);
                            $newgold = $session->gold-2;
                            $database->updateUserField($session->uid,'gold',$newgold,1);
                            header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                            $route = 1;
                            unset($routeid);
                        }else{
                            header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                            $route = 1;
                            unset($routeid);
                        }
                    }else{
                        header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                        $route = 1;
                    }
                }else{
                    $route = 0;
                    header("Location: banned.php");
                }
            }
        
            if(isset($_POST['action']) && $_POST['action'] == 'editRoute') {
                if($session->access != BANNED){
                    $totalres = $_POST['r1']+$_POST['r2']+$_POST['r3']+$_POST['r4'];
                    $reqMerc = ceil(($totalres-0.1)/$market->maxcarry);
                    if($totalres > 0){
                        $database->editTradeRoute($_POST['routeid'],"wood",$_POST['r1'],0);
                        $database->editTradeRoute($_POST['routeid'],"clay",$_POST['r2'],0);
                        $database->editTradeRoute($_POST['routeid'],"iron",$_POST['r3'],0);
                        $database->editTradeRoute($_POST['routeid'],"crop",$_POST['r4'],0);
                        $database->editTradeRoute($_POST['routeid'],"start",$_POST['start'],0);
                        $database->editTradeRoute($_POST['routeid'],"deliveries",$_POST['deliveries'],0);
                        $database->editTradeRoute($_POST['routeid'],"merchant",$reqMerc,0);
                        $second = date("s");
                        $minute = date("i");
                        $hour = date("G")-$_POST['start'];
                        if(date("G") > $_POST['start']){
                            $day = 1;
                        }else{
                            $day = 0;
                        }
                        $timestamp = strtotime("-$hour hours -$second seconds -$minute minutes +$day day");
                        $database->editTradeRoute($_POST['routeid'],"timestamp",$timestamp,0);
                    }
                    header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                    $route = 1;
                    unset($routeid);
                }else{
                    $route = 0;
                    header("Location: banned.php");
                }
            }
        
            if(isset($_GET['action']) && $_GET['action'] == 'delRoute') {
                if($session->access != BANNED){
                    $traderoute = $database->getTradeRouteUid($_GET['routeid']);
                    if($traderoute == $session->uid){
                        $database->deleteTradeRoute($_GET['routeid']);
                        header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                        $route = 1;
                        unset($routeid);
                    }else{
                        header("Location: " . \App\Routes::BUILD . "?gid=17&t=4");
                        $route = 1;
                        unset($routeid);
                    }
                }else{
                    $route = 0;
                    header("Location: banned.php");
                }
            }
        }
    
        if ($session->goldclub == 1){
            if (isset($_GET['t'])==99) {
                if($_GET['action'] == 'addList') {
                    $create = 1;
                }else if($_GET['action'] == 'addraid') {
                    $create = 2;
                }else if($_GET['action'] == 'showSlot' && $_GET['eid']) {
                    $create = 3;
                }else{
                    $create = 0;
                }
            
                if($_GET['slid']) {
                    $FLData = $database->getFLData($_GET['slid']);
                    if($FLData['owner'] == $session->uid){
                        $checked[$_GET['slid']] = 1;
                    }
                }
            
                if($_GET['action'] == 'deleteList') {
                    $database->delFarmList($_GET['lid'], $session->uid);
                    header("Location: " . \App\Routes::BUILD . "?id=39&t=99");
                }elseif($_GET['action'] == 'deleteSlot') {
                    $database->delSlotFarm($_GET['eid']);
                    header("Location: " . \App\Routes::BUILD . "?id=39&t=99");
                }
            
                if($_POST['action'] == 'startRaid'){
                    if($session->access != BANNED){
                        include ("Templates/a2b/startRaid.tpl");
                    }else{
                        header("Location: banned.php");
                    }
                }
            
                if(isset($_GET['slid']) && is_numeric($_GET['slid'])) {
                    $FLData = $database->getFLData($_GET['slid']);
                    if($FLData['owner'] == $session->uid){
                        $checked[$_GET['slid']] = 1;
                    }
                }
            
                if(isset($_GET['evasion']) && is_numeric($_GET['evasion'])) {
                    $evasionvillage = $database->getVillage($_GET['evasion']);
                
                    if($evasionvillage['owner'] == $session->uid){
                        $database->setVillageEvasion($_GET['evasion']);
                    }
                    header("Location: " . \App\Routes::BUILD . "?id=39&t=99");
                }
            
                if(isset($_POST['maxevasion']) && is_numeric($_POST['maxevasion'])) {
                    $database->updateUserField($session->uid, "maxevasion", $_POST['maxevasion'], 1);
                    header("Location: " . \App\Routes::BUILD . "?id=39&t=99");
                }
            }
        }else{
            $create = 0;
        }
    
        if (isset($_POST['a']) == 533374 && isset($_POST['id']) == 39){
            if($session->access != BANNED){
                $units->Settlers($_POST);
            }else{
                header("Location: banned.php");
            }
        }
    
        if($_GET['mode']=='troops' && $_GET['cancel']==1){
            if($session->access != BANNED){
                $oldmovement=$database->getMovementById($_GET['moveid']);
                $now=time();
                if(($now-$oldmovement[0]['starttime'])<90 && $oldmovement[0]['from'] == $village->wid){
                
                    $qc="SELECT * FROM " . TB_PREFIX . "movement where proc = 0 and moveid = ".$_GET['moveid'];
                    $resultc=$database->query($qc);
                
                    if ($database->numRows($resultc)==1){
                    
                        $q = "UPDATE " . TB_PREFIX . "movement set proc  = 1 where proc = 0 and moveid = ".$_GET['moveid'];
                        $database->query($q);
                        $end=$now+($now-$oldmovement[0]['starttime']);
                        //echo "6,".$oldmovement[0]['to'].",".$oldmovement[0]['from'].",0,".$now.",".$end;
                        $q2 = "SELECT id FROM " . TB_PREFIX . "send ORDER BY id DESC";
                        $lastid = $database->fetchArray($database->query($q2));
                        $newid = $lastid['id']+1;
                    
                        $q2 = "INSERT INTO " . TB_PREFIX . "send values ($newid,0,0,0,0,0)";
                        $database->query($q2);
                    
                        $database->addMovement(MovementTypeSid::RETURNING,$oldmovement[0]['to'],$oldmovement[0]['from'],$oldmovement[0]['ref'],$now,$end);
                        $database->addMovement(MovementTypeSid::CHEF_TAKEN,$oldmovement[0]['to'],$oldmovement[0]['from'],$newid,$now,$end);
                    }
                }
                header("Location: ".$_SERVER['PHP_SELF']."?id=".$_GET['id']);
            }else{
                header("Location: banned.php");
            }
        }
    
        if (isset($_GET['id'])) {
            /** @var \GameEngine\Automation\Automation $automation */
            $automation->isWinner();
        }
    }
}
