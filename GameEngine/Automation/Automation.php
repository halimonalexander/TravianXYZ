<?php

namespace GameEngine\Automation;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                                                  ##
##  Filename       Automation.php                                              ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.                     ##
##  Fixed by:      InCube - double troops                                      ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                                  ##
##  Source code:   https://github.com/Shadowss/TravianZ                        ##
##                                                                             ##
#################################################################################

use App\Helpers\GlobalVariablesHelper;
use App\Sids\Buildings;
use App\Sids\MovementTypeSid;
use App\Sids\TribeSid;
use GameEngine\Automation\Helpers\BountyHelper;
use GameEngine\Automation\Helpers\NatarHelper;
use GameEngine\Automation\Helpers\OasisHelper;
use GameEngine\Automation\Helpers\RankingHelper;
use GameEngine\Automation\Helpers\VillageHelper;
use GameEngine\Automation\Helpers\VillageRecalculationHelper;
use GameEngine\Battle;
use GameEngine\Database\MysqliModel;
use GameEngine\Form;
use GameEngine\MyGenerator;
use GameEngine\Ranking;
use GameEngine\Session;
use GameEngine\Technology;
use GameEngine\Units;
use GameEngine\Village;

class Automation
{
    private $prevention;
    
    private $battle;
    private $database;
    private $form;
    private $generator;
    private $ranking;
    private $session;
    private $technology;
    private $units;
    private $village;
    
    public function __construct(
        Battle $battle,
        MysqliModel $database,
        Form $form,
        MyGenerator $generator,
        Ranking $ranking,
        Session $session,
        Technology $technology,
        Units $units,
        Village $village
    ) {
        $this->database = $database;
        $this->battle = $battle;
        $this->form = $form;
        $this->generator = $generator;
        $this->ranking = $ranking;
        $this->session = $session;
        $this->technology = $technology;
        $this->units = $units;
        $this->village = $village;
    
        $this->prevention = new Preventions();
        
        $this->init();
    }
    
    public function isWinner()
    {
        $q = $this->database->query("SELECT vref FROM " . TB_PREFIX . "fdata WHERE f99 = '100' and f99t = '40'");
        $isThere = mysqli_num_rows($q);
        if ($isThere > 0) {
            header('Location: /winner.php');
        }
    }
    
    private function procResType($ref,$mode=0,$isoasis=0)
    {
        switch($ref) {
            case 1: $build = "Woodcutter"; break;
            case 2: $build = "Clay Pit"; break;
            case 3: $build = "Iron Mine"; break;
            case 4: $build = "Cropland"; break;
            case 5: $build = "Sawmill"; break;
            case 6: $build = "Brickyard"; break;
            case 7: $build = "Iron Foundry"; break;
            case 8: $build = "Grain Mill"; break;
            case 9: $build = "Bakery"; break;
            case 10: $build = "Warehouse"; break;
            case 11: $build = "Granary"; break;
            case 12: $build = "Blacksmith"; break;
            case 13: $build = "Armoury"; break;
            case 14: $build = "Tournament Square"; break;
            case 15: $build = "Main Building"; break;
            case 16: $build = "Rally Point"; break;
            case 17: $build = "Marketplace"; break;
            case 18: $build = "Embassy"; break;
            case 19: $build = "Barracks"; break;
            case 20: $build = "Stable"; break;
            case 21: $build = "Workshop"; break;
            case 22: $build = "Academy"; break;
            case 23: $build = "Cranny"; break;
            case 24: $build = "Town Hall"; break;
            case 25: $build = "Residence"; break;
            case 26: $build = "Palace"; break;
            case 27: $build = "Treasury"; break;
            case 28: $build = "Trade Office"; break;
            case 29: $build = "Great Barracks"; break;
            case 30: $build = "Great Stable"; break;
            case 31: $build = "City Wall"; break;
            case 32: $build = "Earth Wall"; break;
            case 33: $build = "Palisade"; break;
            case 34: $build = "Stonemason's Lodge"; break;
            case 35: $build = "Brewery"; break;
            case 36: $build = "Trapper"; break;
            case 37: $build = "Hero's Mansion"; break;
            case 38: $build = "Great Warehouse"; break;
            case 39: $build = "Great Granary"; break;
            case 40: $build = "Wonder of the World"; break;
            case 41: $build = "Horse Drinking Trough"; break;
            case 42: $build = "Great Workshop"; break;
                //default: $build = "Nothing had"; break;
            }
        if ($build=="") {
        if ($mode) { //true to destroy village
            $build="The village has been";
        }else{ //capital or only 1 village left.. not destroy
            $build="Village can't be";
        }
        }
        if ($isoasis!=0) $build = "Oasis had";
        return addslashes($build);
        }

    private function init()
    {
        if ($this->prevention->can('climbers')) {
            $this->prevention->delete('climbers');
            $this->prevention->add('climbers');

            (new RankingHelper($this->database, $this->ranking))
                ->procNewClimbers();
        }
        
        $this->ClearUser();
        $this->ClearInactive();
        
        (new OasisHelper($this->database))
            ->oasisResourcesProduce();
        $villageRecalculator = new VillageRecalculationHelper($this->database);
        $villageRecalculator->pruneResource();
        $villageRecalculator->pruneOasisResource();

        $this->checkWWAttacks();
        
        if ($this->prevention->can('culturepoints')) {
            $this->prevention->delete('culturepoints');
            $this->prevention->add('culturepoints');
            
            $this->culturePoints();
        }
        
        if ($this->prevention->can('updatehero')) {
            $this->prevention->delete('updatehero');
            $this->prevention->add('updatehero');
            
            $this->updateHero();
        }
        
        if ($this->prevention->can('cleardeleting')) {
            $this->prevention->delete('cleardeleting');
            $this->prevention->add('cleardeleting');
    
            $this->clearDeleting();
        }
        
        if ($this->prevention->can('build')) {
            $this->prevention->delete('build');
            $this->prevention->add('build');
    
            $this->buildComplete();
        }
        
        $this->MasterBuilder();
        
        if ($this->prevention->can('demolition'))
        {
            $this->prevention->delete('demolition');
            $this->prevention->add('demolition');
    
            $this->demolitionComplete();
        }

        if ($this->prevention->can('store')) {
            $this->prevention->delete('store');
            $this->prevention->add('store');

            $this->updateStore();
        }

        $this->delTradeRoute();
        $this->TradeRoute();
        
        if ($this->prevention->can('market')) {
            $this->prevention->delete('market');
            $this->prevention->add('market');
            
            $this->marketComplete();
        }
        
        if ($this->prevention->can('research')) {
            $this->prevention->delete('research');
            $this->prevention->add('research');
    
            $this->researchComplete();
        }
        
        if ($this->prevention->can('training')) {
            $this->prevention->delete('training');
            $this->prevention->add('training');
    
            $this->trainingComplete();
        }
        
        if ($this->prevention->can('starvation')) {
            $this->prevention->delete('starvation');
            $this->prevention->add('starvation');
    
            $this->starvation();
        }
        
        if ($this->prevention->can('celebration')) {
            $this->prevention->delete('celebration');
            $this->prevention->add('celebration');
    
            $this->celebrationComplete();
        }
        
        if ($this->prevention->can('sendunits')) {
            $this->prevention->delete('sendunits');
            $this->prevention->add('sendunits');
    
            $reload = $this->sendunitsComplete();
            if ($reload) {
                header("Location: " . $_SERVER['PHP_SELF']);
            }
        }
        
        if ($this->prevention->can('loyalty')) {
            $this->prevention->delete('loyalty');
            $this->prevention->add('loyalty');
    
            $this->loyaltyRegeneration();
        }
        
        if ($this->prevention->can('sendreinfunits')) {
            $this->prevention->delete('sendreinfunits');
            $this->prevention->add('sendreinfunits');
    
            $reload = $this->sendreinfunitsComplete();
            if ($reload)
                header("Location: ".$_SERVER['PHP_SELF']);
        }
        
        if ($this->prevention->can('returnunits')) {
            $this->prevention->delete('returnunits');
            $this->prevention->add('returnunits');
    
            $this->returnunitsComplete();
        }
        
        if ($this->prevention->can('settlers')) {
            $this->prevention->delete('settlers');
            $this->prevention->add('settlers');
    
            $reload = $this->sendSettlersComplete();
            if ($reload)
                header("Location: ".$_SERVER['PHP_SELF']);
        }
        
        $this->updateGeneralAttack();
        $this->checkInvitedPlayes();
//        $this->updateStore(); // duplicate ??
        $this->CheckBan();

        if ($this->prevention->can('regenerate-oasis')) {
            $this->prevention->delete('regenerate-oasis');
            $this->prevention->add('regenerate-oasis');

            (new OasisHelper($this->database))
                ->regenerateOasisTroops();
        }

        (new RankingHelper($this->database, $this->ranking))
            ->medals();

        $this->artefactOfTheFool();
    }

    private function loyaltyRegeneration()
    {
                $array = array();
                $q = "SELECT * FROM ".TB_PREFIX."vdata WHERE loyalty<>100";
                $array = $this->database->query_return($q);
                if(!empty($array)) {
                        foreach($array as $loyalty) {
                                if($this->getTypeLevel(25,$loyalty['wref']) >= 1){
                                        $value = $this->getTypeLevel(25,$loyalty['wref']);
                                }elseif($this->getTypeLevel(26,$loyalty['wref']) >= 1){
                                        $value = $this->getTypeLevel(26,$loyalty['wref']);
                                } else {
                                        $value = 0;
                                }
                                $newloyalty = min(100,$loyalty['loyalty']+$value*(time()-$loyalty['lastupdate2'])/(60*60));
                                $q = "UPDATE ".TB_PREFIX."vdata SET loyalty = $newloyalty, lastupdate2=".time()." WHERE wref = '".$loyalty['wref']."'";
                                $this->database->query($q);
                        }
                }
                $array = array();
                $q = "SELECT * FROM ".TB_PREFIX."odata WHERE loyalty<>100";
                $array = $this->database->query_return($q);
                if(!empty($array)) {
                        foreach($array as $loyalty) {
                                if($this->getTypeLevel(25,$loyalty['conqured']) >= 1){
                                        $value = $this->getTypeLevel(25,$loyalty['conqured']);
                                }elseif($this->getTypeLevel(26,$loyalty['conqured']) >= 1){
                                        $value = $this->getTypeLevel(26,$loyalty['conqured']);
                                } else {
                                        $value = 0;
                                }
                                $newloyalty = min(100,$loyalty['loyalty']+$value*(time()-$loyalty['lastupdated'])/(60*60));
                                $q = "UPDATE ".TB_PREFIX."odata SET loyalty = $newloyalty, lastupdated=".time()." WHERE wref = '".$loyalty['wref']."'";
                                $this->database->query($q);
                        }
                }
    }

    private function getfieldDistance($coorx1, $coory1, $coorx2, $coory2) {
           $max = 2 * WORLD_MAX + 1;
           $x1 = intval($coorx1);
           $y1 = intval($coory1);
           $x2 = intval($coorx2);
           $y2 = intval($coory2);
           $distanceX = min(abs($x2 - $x1), abs($max - abs($x2 - $x1)));
           $distanceY = min(abs($y2 - $y1), abs($max - abs($y2 - $y1)));
           $dist = sqrt(pow($distanceX, 2) + pow($distanceY, 2));
          return round($dist, 1);
       }    

    public function getTypeLevel($tid,$vid) {
        $keyholder = array();

            $resourcearray = $this->database->getResourceLevel($vid);

        foreach(array_keys($resourcearray,$tid) as $key) {
            if(strpos($key,'t')) {
                $key = preg_replace("/[^0-9]/", '', $key);
                array_push($keyholder, $key);
            }
        }
        $element = count($keyholder);
        if($element >= 2) {
            if($tid <= 4) {
                $temparray = array();
                for($i=0;$i<=$element-1;$i++) {
                    array_push($temparray,$resourcearray['f'.$keyholder[$i]]);
                }
                foreach ($temparray as $key => $val) {
                    if ($val == max($temparray))
                    $target = $key;
                }
            }
            else {
                $target = 0;
                for($i=1;$i<=$element-1;$i++) {
                    if($resourcearray['f'.$keyholder[$i]] > $resourcearray['f'.$keyholder[$target]]) {
                        $target = $i;
                    }
                }
            }
        }
        else if($element == 1) {
            $target = 0;
        }
        else {
            return 0;
        }
        if($keyholder[$target] != "") {
            return $resourcearray['f'.$keyholder[$target]];
        }
        else {
            return 0;
        }
    }

    private function clearDeleting()
    {
        $needDelete = $this->database->getNeedDelete();
        if(count($needDelete) > 0) {
            foreach($needDelete as $need) {
                $needVillage = $this->database->getVillagesID($need['uid']);
                foreach($needVillage as $village) {
                    $q = "DELETE FROM ".TB_PREFIX."abdata where vref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."bdata where wid = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."enforcement where `from` = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."fdata where vref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."market where vref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."odata where wref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."research where vref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."tdata where vref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."training where vref =".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."units where vref =".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."farmlist where wref =".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."raidlist where towref = ".$village;
                    $this->database->query($q);
                    $q = "DELETE FROM ".TB_PREFIX."vdata where wref = ".$village;
                    $this->database->query($q);
                    $q = "UPDATE ".TB_PREFIX."wdata set occupied = 0 where id = ".$village;
                    $this->database->query($q);
                    $getmovement = $this->database->getMovement(3,$village,1);
                    foreach($getmovement as $movedata) {
                    $time = microtime(true);
                    $time2 = $time - $movedata['starttime'];
                    $this->database->addMovement(MovementTypeSid::RETURNING,$movedata['to'],$movedata['from'],$movedata['ref'],$time,$time+$time2);
                    $this->database->setMovementProc($movedata['moveid']);
                    }
                    $q = "DELETE FROM ".TB_PREFIX."movement where proc = 0 AND ((`to` = $village AND sort_type=4) OR (`from` = $village AND sort_type=3))";
                    $this->database->query($q);
                    $getprisoners = $this->database->getPrisoners($village);
                    foreach($getprisoners as $pris) {
                    $troops = 0;
                    for($i=1;$i<12;$i++){
                    $troops += $pris['t'.$i];
                    }
                    $this->database->modifyUnit($pris['wref'],array("99o"),array($troops),array(0));
                    $this->database->deletePrisoners($pris['id']);
                    }
                    $getprisoners = $this->database->getPrisoners3($village);
                    foreach($getprisoners as $pris) {
                    $troops = 0;
                    for($i=1;$i<12;$i++){
                    $troops += $pris['t'.$i];
                    }
                    $this->database->modifyUnit($pris['wref'],array("99o"),array($troops),array(0));
                    $this->database->deletePrisoners($pris['id']);
                    }
                    $enforcement = $this->database->getEnforceVillage($village,0);
                    foreach($enforcement as $enforce) {
                    $time = microtime(true);
                    $fromcoor = $this->database->getCoor($enforce['vref']);
                    $tocoor = $this->database->getCoor($enforce['from']);
                    $targettribe = $this->database->getUserField($this->database->getVillageField($enforce['from'],"owner"),"tribe",0);
                    $time2 = $this->procDistanceTime($tocoor,$fromcoor,$targettribe,0);
                    $start = 10*($targettribe-1);
                    for($i=1;$i<11;$i++){
                    $unit = $start + $i;
                    $post['t'.$i] = $enforce['u'.$unit];
                    }
                    $post['t11'] = $enforce['hero'];
                    $reference = $this->database->addAttack($enforce['from'],$post['t1'],$post['t2'],$post['t3'],$post['t4'],$post['t5'],$post['t6'],$post['t7'],$post['t8'],$post['t9'],$post['t10'],$post['t11'],2,0,0,0,0);
                    $this->database->addMovement(MovementTypeSid::RETURNING,$enforce['vref'],$enforce['from'],$reference,$time,$time+$time2);
                    $q = "DELETE FROM ".TB_PREFIX."enforcement where id = ".$enforce['id'];
                    $this->database->query($q);
                    }
                }
                for($i=0;$i<20;$i++){
                $q = "SELECT * FROM ".TB_PREFIX."users where friend".$i." = ".$need['uid']." or friend".$i."wait = ".$need['uid']."";
                $array = $this->database->query_return($q);
                foreach($array as $friend){
                $this->database->deleteFriend($friend['id'],"friend".$i);
                $this->database->deleteFriend($friend['id'],"friend".$i."wait");
                }
                }
                $this->database->updateUserField($need['uid'], 'alliance', 0, 1);
                if($this->database->isAllianceOwner($need['uid'])){
                $alliance = $this->database->getUserAllianceID($need['uid']);
                $newowner = $this->database->getAllMember2($alliance);
                $newleader = $newowner['id'];
                $q = "UPDATE " . TB_PREFIX . "alidata set leader = ".$newleader." where id = ".$alliance."";
                $this->database->query($q);
                $this->database->updateAlliPermissions($newleader, $alliance, "Leader", 1, 1, 1, 1, 1, 1, 1);
                (new VillageHelper($this->database))
                    ->updateMaxAllyMembers($newleader);
                }
                $this->database->deleteAlliance($alliance);
                $q = "DELETE FROM ".TB_PREFIX."hero where uid = ".$need['uid'];
                $this->database->query($q);
                $q = "DELETE FROM ".TB_PREFIX."mdata where target = ".$need['uid']." or owner = ".$need['uid'];
                $this->database->query($q);
                $q = "DELETE FROM ".TB_PREFIX."ndata where uid = ".$need['uid'];
                $this->database->query($q);
                $q = "DELETE FROM ".TB_PREFIX."users where id = ".$need['uid'];
                $this->database->query($q);
                $q = "DELETE FROM ".TB_PREFIX."deleting where uid = ".$need['uid'];
                $this->database->query($q);
            }
        }
    }

    private function ClearUser()
    {
        if (!defined('AUTO_DEL_INACTIVE') || !AUTO_DEL_INACTIVE) {
            return;
        }
        
        $time = time() + UN_ACT_TIME;
        $q = "DELETE from ".TB_PREFIX."users where timestamp >= $time and act != ''";
        $this->database->query($q);
    }

    private function ClearInactive() {
        
        if (!TRACK_USR) {
            return;
        }
        
        $timeout = time()-USER_TIMEOUT*60;
        $q = "DELETE FROM ".TB_PREFIX."active WHERE timestamp < $timeout";
        $this->database->query($q);
    }

    private function culturePoints()
    {
        //fix by ronix
        if (SPEED > 10)
            $speed = 10;
        else
            $speed = SPEED;
        
        $dur_day = 86400 / $speed; //24 hours/speed
        if ($dur_day < 3600) $dur_day = 3600;
        $time = time() - 600; // recount every 10minutes
    
        $array = [];
        $q = "SELECT id, lastupdate FROM " . TB_PREFIX . "users WHERE lastupdate < $time";
        $array = $this->database->query_return($q);
    
        foreach ($array as $indi) {
            if ($indi['lastupdate'] <= $time && $indi['lastupdate'] > 0) {
                $cp = $this->database->getVSumField($indi['id'], 'cp') * (time() - $indi['lastupdate']) / $dur_day;
            
                $newupdate = time();
                $q = "UPDATE " . TB_PREFIX . "users set cp = cp + $cp, lastupdate = $newupdate where id = '" . $indi['id'] . "'";
                $this->database->query($q);
            }
        }
    }
    
    private function buildComplete()
    {
        $q = "SELECT * FROM ".TB_PREFIX."bdata where timestamp < UNIX_TIMESTAMP(now()) AND master = 0";
        $buildingQueues = $this->database->query($q)->fetch_all(MYSQLI_ASSOC);
        
        foreach ($buildingQueues as $indi) {
            $currentFieldLevel = $this->database->getFieldLevel($indi['wid'], $indi['field']);
            
            if ($indi['level'] !== $currentFieldLevel + 1) {
                $indi['level'] = $currentFieldLevel + 1;
            }
            
            $q = "UPDATE ".TB_PREFIX."fdata
                SET f".$indi['field']." = ".$indi['level'].",
                    f".$indi['field']."t = ".$indi['type']."
                WHERE vref = ".$indi['wid'];
    
            if (!$this->database->query($q)) {
                // todo log failed query
                continue;
            }
            
            (new VillageRecalculationHelper($this->database))
                ->recountVillage($indi['wid']);
            (new RankingHelper($this->database, $this->ranking))
                    ->procClimbers($this->database->getVillageField($indi['wid'], 'owner'));
    
            if ($indi['type'] == Buildings::WAREHOUSE) {
                $bid10 = GlobalVariablesHelper::getBuilding(Buildings::WAREHOUSE);
                
                $max = $this->database->getVillageField($indi['wid'], "maxstore");
                if ($currentFieldLevel == '1' && $max == STORAGE_BASE) {
                    $max = STORAGE_BASE;
                }
                if ($currentFieldLevel != 1) {
                    $max -= $bid10[$currentFieldLevel - 1]['attri'] * STORAGE_MULTIPLIER;
                    $max += $bid10[$currentFieldLevel]['attri'] * STORAGE_MULTIPLIER;
                }
                else {
                    // if it's not the first storage - max will 800 that is wrong
                    $max = $bid10[$currentFieldLevel]['attri'] * STORAGE_MULTIPLIER;
                }
                $this->database->setVillageField($indi['wid'], "maxstore", $max);
            }
    
            if ($indi['type'] == Buildings::GRANARY) {
                $bid11 = GlobalVariablesHelper::getBuilding(Buildings::GRANARY);

                $max = $this->database->getVillageField($indi['wid'], "maxcrop");
                if ($currentFieldLevel == '1' && $max == STORAGE_BASE) {
                    $max = STORAGE_BASE;
                }
                if ($currentFieldLevel != 1) {
                    $max -= $bid11[ $currentFieldLevel - 1 ]['attri'] * STORAGE_MULTIPLIER;
                    $max += $bid11[ $currentFieldLevel ]['attri'] * STORAGE_MULTIPLIER;
                }
                else {
                    $max = $bid11[ $currentFieldLevel ]['attri'] * STORAGE_MULTIPLIER;
                }
                $this->database->setVillageField($indi['wid'], "maxcrop", $max);
            }
    
            if ($indi['type'] == Buildings::EMBASSY) {
                (new VillageHelper($this->database))
                    ->updateMaxAllyMembers(
                        $this->database->getVillageField($indi['wid'], "owner")
                    );
            }
    
            if ($indi['type'] == Buildings::GREAT_WAREHOUSE) {
                $bid38 = GlobalVariablesHelper::getBuilding(Buildings::GREAT_WAREHOUSE);
                
                $max = $this->database->getVillageField($indi['wid'], "maxstore");
                if ($currentFieldLevel == '1' && $max == STORAGE_BASE) {
                    $max = STORAGE_BASE;
                }
                if ($currentFieldLevel != 1) {
                    $max -= $bid38[ $currentFieldLevel - 1 ]['attri'] * STORAGE_MULTIPLIER;
                    $max += $bid38[ $currentFieldLevel ]['attri'] * STORAGE_MULTIPLIER;
                }
                else {
                    $max = $bid38[ $currentFieldLevel ]['attri'] * STORAGE_MULTIPLIER;
                }
                $this->database->setVillageField($indi['wid'], "maxstore", $max);
            }
    
            if ($indi['type'] == Buildings::GREAT_GRANARY) {
                $bid39 = GlobalVariablesHelper::getBuilding(Buildings::GREAT_GRANARY);
                
                $max = $this->database->getVillageField($indi['wid'], "maxcrop");
                if ($currentFieldLevel == '1' && $max == STORAGE_BASE) {
                    $max = STORAGE_BASE;
                }
                if ($currentFieldLevel != 1) {
                    $max -= $bid39[ $currentFieldLevel - 1 ]['attri'] * STORAGE_MULTIPLIER;
                    $max += $bid39[ $currentFieldLevel ]['attri'] * STORAGE_MULTIPLIER;
                }
                else {
                    $max = $bid39[ $currentFieldLevel ]['attri'] * STORAGE_MULTIPLIER;
                }
                $this->database->setVillageField($indi['wid'], "maxcrop", $max);
            }
            
            if (
                $indi['type'] == Buildings::WONDER_OF_THE_WORLD &&
                ($indi['level'] % 5 == 0 && $indi['level'] > 95) &&
                $indi['level'] != 100
            ) {
                (new NatarHelper($this->database))
                    ->startNatarAttack($indi['level'], $indi['wid'], $indi['timestamp']);
            }
            
            if ($indi['type'] == Buildings::WONDER_OF_THE_WORLD && $indi['level'] == 100) {
                //now can't be more than one winners if ww to level 100 is build by 2 users or more on same time
                $this->database->query("DELETE FROM " . TB_PREFIX . "bdata WHERE type = " . Buildings::WONDER_OF_THE_WORLD);
            }
            
            $owner = $this->database->getVillageField($indi['wid'], "owner");
            $ownerTribe = $this->database->getUserField($owner, "tribe", 0);
            if ($ownerTribe != TribeSid::ROMANS) {
                $q4 = "UPDATE " . TB_PREFIX . "bdata SET loopcon = 0 WHERE loopcon = 1 AND master = 0 AND wid = " . $indi['wid'];
            } else {
                if ($indi['field'] > 18) {
                    $q4 = "UPDATE " . TB_PREFIX . "bdata SET loopcon = 0 WHERE loopcon = 1 AND master = 0 AND wid = " . $indi['wid'] . " AND field > 18";
                }
                else {
                    $q4 = "UPDATE " . TB_PREFIX . "bdata set loopcon = 0 WHERE loopcon = 1 AND master = 0 AND wid = " . $indi['wid'] . " AND field < 19";
                }
            }
            $this->database->query($q4);
            
            $q = "DELETE FROM " . TB_PREFIX . "bdata where id = " . $indi['id'];
            $this->database->query($q);
            
            $crop = $this->database->getCropProdstarv($indi['wid']);
            $unitarrays = $this->getAllUnits($indi['wid']);
            $village = $this->database->getVillage($indi['wid']);
            $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
            $starv = $this->database->getVillageField($indi['wid'],"starv");
            if ($crop < $upkeep) {
                // add starv data
                $this->database->setVillageField($indi['wid'], 'starv', $upkeep);
                if ($starv == 0) {
                    $this->database->setVillageField($indi['wid'], 'starvupdate', time());
                }
            }
        }
    }

    private function checkWWAttacks()
    {
        $result = $this->database->query('SELECT * FROM `' . TB_PREFIX . 'ww_attacks` WHERE `attack_time` <= ' . time());
    
        while ($row = $this->database->fetchAssoc($result))
        {
            // delete the attack
            $this->database->query('DELETE FROM `' . TB_PREFIX . 'ww_attacks` WHERE `vid` = ' . $row['vid'] . ' AND `attack_time` = ' . $row['attack_time']);
        }
    }

    private function getPop($tid,$level) {
        $dataarray = GlobalVariablesHelper::getBuilding($tid);
        $pop = $dataarray[($level+1)]['pop'];
        $cp = $dataarray[($level+1)]['cp'];
        return array($pop,$cp);
    }

    private function delTradeRoute() {
        
        $time = time();
        $q = "DELETE from ".TB_PREFIX."route where timeleft < $time";
        $this->database->query($q);
    }

    private function TradeRoute()
    {
        $time = time();
        $q = "SELECT * FROM ".TB_PREFIX."route where timestamp < $time";
        $dataarray = $this->database->query_return($q);
        foreach($dataarray as $data) {
            $this->database->modifyResource($data['from'],$data['wood'],$data['clay'],$data['iron'],$data['crop'],0);
            $targettribe = $this->database->getUserField($this->database->getVillageField($data['from'],"owner"),"tribe",0);
            $this->sendResource2($data['wood'],$data['clay'],$data['iron'],$data['crop'],$data['from'],$data['wid'],$targettribe,$data['deliveries']);
            $this->database->editTradeRoute($data['id'],"timestamp",86400,1);
        }
    }

    private function marketComplete()
    {
        $time = microtime(true);
        $q = "SELECT * FROM ".TB_PREFIX."movement, ".TB_PREFIX."send where ".TB_PREFIX."movement.ref = ".TB_PREFIX."send.id and ".TB_PREFIX."movement.proc = 0 and sort_type = 0 and endtime < $time";
        $dataarray = $this->database->query_return($q);
        foreach($dataarray as $data) {

            if($data['wood'] >= $data['clay'] && $data['wood'] >= $data['iron'] && $data['wood'] >= $data['crop']){ $sort_type = "10"; }
            elseif($data['clay'] >= $data['wood'] && $data['clay'] >= $data['iron'] && $data['clay'] >= $data['crop']){ $sort_type = "11"; }
            elseif($data['iron'] >= $data['wood'] && $data['iron'] >= $data['clay'] && $data['iron'] >= $data['crop']){ $sort_type = "12"; }
            elseif($data['crop'] >= $data['wood'] && $data['crop'] >= $data['clay'] && $data['crop'] >= $data['iron']){ $sort_type = "13"; }

            $to = $this->database->getMInfo($data['to']);
            $from = $this->database->getMInfo($data['from']);
            $this->database->addNotice($to['owner'],$to['wref'],$targetally,$sort_type,''.addslashes($from['name']).' send resources to '.addslashes($to['name']).'',''.$from['owner'].','.$from['wref'].','.$data['wood'].','.$data['clay'].','.$data['iron'].','.$data['crop'].'',$data['endtime']);
            if($from['owner'] != $to['owner']) {
                $this->database->addNotice($from['owner'],$to['wref'],$ownally,$sort_type,''.addslashes($from['name']).' send resources to '.addslashes($to['name']).'',''.$from['owner'].','.$from['wref'].','.$data['wood'].','.$data['clay'].','.$data['iron'].','.$data['crop'].'',$data['endtime']);
            }
            $this->database->modifyResource($data['to'],$data['wood'],$data['clay'],$data['iron'],$data['crop'],1);
            $tocoor = $this->database->getCoor($data['from']);
            $fromcoor = $this->database->getCoor($data['to']);
            $targettribe = $this->database->getUserField($this->database->getVillageField($data['from'],"owner"),"tribe",0);
            $endtime = $this->procDistanceTime($tocoor,$fromcoor,$targettribe,0) + $data['endtime'];
            $this->database->addMovement(MovementTypeSid::RETURNING_MERCHANTS,$data['to'],$data['from'],$data['merchant'],time(),$endtime,$data['send'],$data['wood'],$data['clay'],$data['iron'],$data['crop']);
            $this->database->setMovementProc($data['moveid']);
        }
        $q1 = "SELECT * FROM ".TB_PREFIX."movement where proc = 0 and sort_type = 2 and endtime < $time";
        $dataarray1 = $this->database->query_return($q1);
        foreach($dataarray1 as $data1) {
            $this->database->setMovementProc($data1['moveid']);
            if($data1['send'] > 1){
            $targettribe1 = $this->database->getUserField($this->database->getVillageField($data1['to'],"owner"),"tribe",0);
            $send = $data1['send']-1;
            $this->sendResource2($data1['wood'],$data1['clay'],$data1['iron'],$data1['crop'],$data1['to'],$data1['from'],$targettribe1,$send);
            }
        }
    }

    private function sendResource2($wtrans,$ctrans,$itrans,$crtrans,$from,$to,$tribe,$send)
    {
        $bid28 = GlobalVariablesHelper::getBuilding(Buildings::TRADE_OFFICE);
    
        $availableWood = $this->database->getWoodAvailable($from);
        $availableClay = $this->database->getClayAvailable($from);
        $availableIron = $this->database->getIronAvailable($from);
        $availableCrop = $this->database->getCropAvailable($from);
        if ($availableWood >= $wtrans AND $availableClay >= $ctrans AND $availableIron >= $itrans AND $availableCrop >= $crtrans) {
            $merchant2 = ($this->getTypeLevel(17, $from) > 0) ? $this->getTypeLevel(17, $from) : 0;
            $used2 = $this->database->totalMerchantUsed($from);
            $merchantAvail2 = $merchant2 - $used2;
            $maxcarry2 = ($tribe == 1) ? 500 : (($tribe == 2) ? 1000 : 750);
            $maxcarry2 *= TRADER_CAPACITY;
            if ($this->getTypeLevel(28, $from) != 0) {
                $maxcarry2 *= $bid28[ $this->getTypeLevel(28, $from) ]['attri'] / 100;
            }
            $resource = [$wtrans, $ctrans, $itrans, $crtrans];
            $reqMerc = ceil((array_sum($resource) - 0.1) / $maxcarry2);
            if ($merchantAvail2 != 0 && $reqMerc <= $merchantAvail2) {
                $coor = $this->database->getCoor($to);
                $coor2 = $this->database->getCoor($from);
                if ($this->database->getVillageState($to)) {
                    $timetaken = $this->generator->procDistanceTime($coor, $coor2, $tribe, 0);
                    $res = $resource[0] + $resource[1] + $resource[2] + $resource[3];
                    if ($res != 0) {
                        $reference = $this->database->sendResource($resource[0], $resource[1], $resource[2], $resource[3], $reqMerc, 0);
                        $this->database->modifyResource($from, $resource[0], $resource[1], $resource[2], $resource[3], 0);
                        $this->database->addMovement(MovementTypeSid::MERCHANTS, $from, $to, $reference, microtime(true), microtime(true) + $timetaken, $send);
                    }
                }
            }
        }
    }
    
    private function sendunitsComplete(): bool
    {
        $bid23 = GlobalVariablesHelper::getBuilding(Buildings::CRANNY);
        
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = '0' and " . TB_PREFIX . "movement.sort_type = '3' and " . TB_PREFIX . "attacks.attack_type != '2' and endtime < $time ORDER BY endtime ASC";
        $dataarray = $this->database->query_return($q);

        $totalattackdead = 0;
        $data_num = 0;
        foreach ($dataarray as $data) {
            //set base things
            //$battle->resolveConflict($data);
            $tocoor = $this->database->getCoor($data['from']);
            $fromcoor = $this->database->getCoor($data['to']);
            $isoasis = $this->database->isVillageOases($data['to']);
            $AttackArrivalTime = $data['endtime'];
            $AttackerWref = $data['from'];
            $DefenderWref = $data['to'];
            $NatarCapital = false;
            
            if ($isoasis == 0) { //village
                $Attacker['id'] = $this->database->getUserField($this->database->getVillageField($data['from'], "owner"), "id", 0);
                $Defender['id'] = $this->database->getUserField($this->database->getVillageField($data['to'], "owner"), "id", 0);
                $AttackerID = $Attacker['id'];
                $DefenderID = $Defender['id'];
                
                if ($this->session->uid == $AttackerID || $this->session->uid == $DefenderID) $reload = true;
                $owntribe = $this->database->getUserField($this->database->getVillageField($data['from'], "owner"), "tribe", 0);
                $targettribe = $this->database->getUserField($this->database->getVillageField($data['to'], "owner"), "tribe", 0);
                $ownally = $this->database->getUserField($this->database->getVillageField($data['from'], "owner"), "alliance", 0);
                $targetally = $this->database->getUserField($this->database->getVillageField($data['to'], "owner"), "alliance", 0);
                $to = $this->database->getMInfo($data['to']);
                $from = $this->database->getMInfo($data['from']);
                $toF = $this->database->getVillage($data['to']);
                $fromF = $this->database->getVillage($data['from']);
                $conqureby = 0;
                $NatarCapital = ($toF['owner'] == 3 && $toF['capital'] == 1) ? true : false;
                if (!isset($to['name']) || $to['name'] == '') $to['name'] = "??";
                
                $DefenderUnit = [];
                $DefenderUnit = $this->database->getUnit($data['to']);
                $evasion = $this->database->getVillageField($data['to'], "evasion");
                $maxevasion = $this->database->getUserField($DefenderID, "maxevasion", 0);
                $gold = $this->database->getUserField($DefenderID, "gold", 0);
                $playerunit = ($targettribe - 1) * 10;
                $cannotsend = 0;
                $movements = $this->database->getMovement("34", $data['to'], 1);
                for ($y = 0; $y < count($movements); $y++) {
                    $returntime = $unit[ $y ]['endtime'] - time();
                    if ($unit[ $y ]['sort_type'] == 4 && $unit[ $y ]['from'] != 0 && $returntime <= 10) {
                        $cannotsend = 1;
                    }
                }
                if ($evasion == 1 && $maxevasion > 0 && $gold > 1 && $cannotsend == 0 && $dataarray[ $data_num ]['attack_type'] > 2) {
                    $totaltroops = 0;
                    for ($i = 1; $i <= 10; $i++) {
                        $playerunit += $i;
                        $data[ 'u' . $i ] = $DefenderUnit[ 'u' . $playerunit ];
                        $this->database->modifyUnit($data['to'], [$playerunit], [$DefenderUnit[ 'u' . $playerunit ]], [0]);
                        $playerunit -= $i;
                        $totaltroops += $data[ 'u' . $i ];
                    }
                    $data['u11'] = $DefenderUnit['hero'];
                    $totaltroops += $data['u11'];
                    if ($totaltroops > 0) {
                        $this->database->modifyUnit($data['to'], ["hero"], [$DefenderUnit['hero']], [0]);
                        $attackid = $this->database->addAttack($data['to'], $data['u1'], $data['u2'], $data['u3'], $data['u4'], $data['u5'], $data['u6'], $data['u7'], $data['u8'], $data['u9'], $data['u10'], $data['u11'], 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
                        $this->database->addMovement(MovementTypeSid::RETURNING, 0, $data['to'], $attackid, microtime(true), microtime(true) + (180 / EVASION_SPEED));
                        $newgold = $gold - 2;
                        $newmaxevasion = $maxevasion - 1;
                        $this->database->updateUserField($DefenderID, "gold", $newgold, 1);
                        $this->database->updateUserField($DefenderID, "maxevasion", $newmaxevasion, 1);
                    }
                }
                //get defence units
                $Defender = [];
                $enforDefender = [];
                $rom = $ger = $gal = $nat = $natar = 0;
                $Defender = $this->database->getUnit($data['to']);
                $enforcementarray = $this->database->getEnforceVillage($data['to'], 0);
                if (count($enforcementarray) > 0) {
                    
                    foreach ($enforcementarray as $enforce) {
                        for ($i = 1; $i <= 50; $i++) {
                            $enforDefender[ 'u' . $i ] += $enforce[ 'u' . $i ];
                        }
                        $enforDefender['hero'] += $enforce['hero'];
                    }
                }
                for ($i = 1; $i <= 50; $i++) {
                    $def_ab[ $i ] = 0;
                    if (!isset($Defender[ 'u' . $i ])) {
                        $Defender[ 'u' . $i ] = '0';
                    }
                    else {
                        if ($Defender[ 'u' . $i ] == '' or $Defender[ 'u' . $i ] <= '0') {
                            $Defender[ 'u' . $i ] = '0';
                        }
                        else {
                            if ($i <= 10) {
                                $rom = '1';
                            }
                            elseif ($i <= 20) {
                                $ger = '1';
                            }
                            elseif ($i <= 30) {
                                $gal = '1';
                            }
                            elseif ($i <= 40) {
                                $nat = '1';
                            }
                            elseif ($i <= 50) {
                                $natar = '1';
                            }
                        }
                    }
                }
                if (!isset($Defender['hero'])) {
                    $Defender['hero'] = '0';
                }
                else {
                    if ($Defender['hero'] == '' or $Defender['hero'] <= '0') {
                        $Defender['hero'] = '0';
                    }
                }
                //get attack units
                $Attacker = [];
                $start = ($owntribe - 1) * 10 + 1;
                $end = ($owntribe * 10);
                $u = (($owntribe - 1) * 10);
                $catapult = [8, 18, 28, 48];
                $ram = [7, 17, 27, 47];
                $chief = [9, 19, 29, 49];
                $spys = [4, 14, 23, 44];
                for ($i = $start; $i <= $end; $i++) {
                    $y = $i - $u;
                    $Attacker[ 'u' . $i ] = $dataarray[ $data_num ][ 't' . $y ];
                    //there are catas
                    if (in_array($i, $catapult)) {
                        $catp_pic = $i;
                    }
                    if (in_array($i, $ram)) {
                        $ram_pic = $i;
                    }
                    if (in_array($i, $chief)) {
                        $chief_pic = $i;
                    }
                    if (in_array($i, $spys)) {
                        $spy_pic = $i;
                    }
                }
                $Attacker['uhero'] = $dataarray[ $data_num ]['t11'];
                $hero_pic = "hero";
                //need to set these variables.
                $def_wall = $this->database->getFieldLevel($data['to'], 40);
                $att_tribe = $owntribe;
                $def_tribe = $targettribe;
                $residence = "0";
                $attpop = $fromF['pop'];
                $defpop = $toF['pop'];
                $def_ab = [];
                //get level of palace or residence
                for ($i = 19; $i < 40; $i++) {
                    if ($this->database->getFieldLevel($data['to'], "" . $i . "t") == '25' OR $this->database->getFieldLevel($data['to'], "" . $i . "t") == '26') {
                        $residence = $this->database->getFieldLevel($data['to'], $i);
                        $i = 40;
                    }
                }
                
                //type of attack
                if ($dataarray[ $data_num ]['attack_type'] == 1) {
                    $type = 1;
                    $scout = 1;
                }
                if ($dataarray[ $data_num ]['attack_type'] == 2) {
                    $type = 2;
                }
                if ($dataarray[ $data_num ]['attack_type'] == 3) {
                    $type = 3;
                }
                if ($dataarray[ $data_num ]['attack_type'] == 4) {
                    $type = 4;
                }
                $ud = ($def_tribe - 1) * 10;
                $att_ab = $this->database->getABTech($data['from']); // Blacksmith level
                $att_ab1 = $att_ab['b1'];
                $att_ab2 = $att_ab['b2'];
                $att_ab3 = $att_ab['b3'];
                $att_ab4 = $att_ab['b4'];
                $att_ab5 = $att_ab['b5'];
                $att_ab6 = $att_ab['b6'];
                $att_ab7 = $att_ab['b7'];
                $att_ab8 = $att_ab['b8'];
                $armory = $this->database->getABTech($data['to']); // Armory level
                $def_ab[ $ud + 1 ] = $armory['a1'];
                $def_ab[ $ud + 2 ] = $armory['a2'];
                $def_ab[ $ud + 3 ] = $armory['a3'];
                $def_ab[ $ud + 4 ] = $armory['a4'];
                $def_ab[ $ud + 5 ] = $armory['a5'];
                $def_ab[ $ud + 6 ] = $armory['a6'];
                $def_ab[ $ud + 7 ] = $armory['a7'];
                $def_ab[ $ud + 8 ] = $armory['a8'];
                
                //rams attack
                if (($data['t7'] - $dead7 - $traped7) > 0 and $type == '3') {
                    $basearraywall = $this->database->getMInfo($data['to']);
                    if ($this->database->getFieldLevel($basearraywall['wref'], 40) > '0') {
                        for ($w = 1; $w < 2; $w++) {
                            if ($this->database->getFieldLevel($basearraywall['wref'], 40) != '0') {
                                
                                $walllevel = $this->database->getFieldLevel($basearraywall['wref'], 40);
                                $wallgid = $this->database->getFieldLevel($basearraywall['wref'], "40t");
                                $wallid = 40;
                                $w = '4';
                            }
                            else {
                                $w = $w--;
                            }
                        }
                    }
                    else {
                        $empty = 1;
                    }
                }
                
                $tblevel = '1';
                $stonemason = "1";
                /*--------------------------------
                // End village Battle part
                --------------------------------*/
            }
            else {
                $Attacker['id'] = $this->database->getUserField($this->database->getVillageField($data['from'], "owner"), "id", 0);
                $Defender['id'] = $this->database->getUserField($this->database->getOasisField($data['to'], "owner"), "id", 0);
                $AttackerID = $Attacker['id'];
                $DefenderID = $Defender['id'];
                if ($this->session->uid == $AttackerID || $this->session->uid == $DefenderID) $reload = true;
                $owntribe = $this->database->getUserField($this->database->getVillageField($data['from'], "owner"), "tribe", 0);
                $targettribe = $this->database->getUserField($this->database->getOasisField($data['to'], "owner"), "tribe", 0);;
                $ownally = $this->database->getUserField($this->database->getVillageField($data['from'], "owner"), "alliance", 0);
                $targetally = $this->database->getUserField($this->database->getOasisField($data['to'], "owner"), "alliance", 0);
                $to = $this->database->getOMInfo($data['to']);
                $from = $this->database->getMInfo($data['from']);
                $toF = $this->database->getOasisV($data['to']);
                $fromF = $this->database->getVillage($data['from']);
                $conqureby = $toF['conqured'];
                //get defence units
                $Defender = [];
                $enforDefender = [];
                $rom = $ger = $gal = $nat = $natar = 0;
                $Defender = $this->database->getUnit($data['to']);
                $enforcementarray = $this->database->getEnforceVillage($data['to'], 0);
                
                if (count($enforcementarray) > 0) {
                    foreach ($enforcementarray as $enforce) {
                        for ($i = 1; $i <= 50; $i++) {
                            $enforDefender[ 'u' . $i ] += $enforce[ 'u' . $i ];
                        }
                        $enforDefender['hero'] += $enforce['hero'];
                    }
                }
                for ($i = 1; $i <= 50; $i++) {
                    if (!isset($Defender[ 'u' . $i ])) {
                        $Defender[ 'u' . $i ] = '0';
                    }
                    else {
                        if ($Defender[ 'u' . $i ] == '' or $Defender[ 'u' . $i ] <= '0') {
                            $Defender[ 'u' . $i ] = '0';
                        }
                        else {
                            if ($i <= 10) {
                                $rom = '1';
                            }
                            elseif ($i <= 20) {
                                $ger = '1';
                            }
                            elseif ($i <= 30) {
                                $gal = '1';
                            }
                            elseif ($i <= 40) {
                                $nat = '1';
                            }
                            elseif ($i <= 50) {
                                $natar = '1';
                            }
                        }
                    }
                }
                if (!isset($Defender['hero'])) {
                    $Defender['hero'] = '0';
                }
                else {
                    if ($Defender['hero'] == '' or $Defender['hero'] < '0') {
                        $Defender['hero'] = '0';
                    }
                }
                
                //get attack units
                $Attacker = [];
                $start = ($owntribe - 1) * 10 + 1;
                $end = ($owntribe * 10);
                $u = (($owntribe - 1) * 10);
                $catapult = [8, 18, 28, 38, 48];
                $ram = [7, 17, 27, 37, 47];
                $chief = [9, 19, 29, 39, 49];
                $spys = [4, 14, 23, 44];
                for ($i = $start; $i <= $end; $i++) {
                    $y = $i - $u;
                    $Attacker[ 'u' . $i ] = $dataarray[ $data_num ][ 't' . $y ];
                    //there are catas
                    if (in_array($i, $catapult)) {
                        $catp_pic = $i;
                    }
                    if (in_array($i, $ram)) {
                        $ram_pic = $i;
                    }
                    if (in_array($i, $chief)) {
                        $chief_pic = $i;
                    }
                    if (in_array($i, $spys)) {
                        $spy_pic = $i;
                    }
                }
                $Attacker['uhero'] = $dataarray[ $data_num ]['t11'];
                $hero_pic = "hero";
                //need to set these variables.
                $def_wall = 1;
                $att_tribe = $owntribe;
                $def_tribe = $targettribe;
                $residence = "0";
                $attpop = $fromF['pop'];
                $defpop = 100;
                
                //type of attack
                if ($dataarray[ $data_num ]['attack_type'] == 1) {
                    $type = 1;
                    $scout = 1;
                }
                if ($dataarray[ $data_num ]['attack_type'] == 2) {
                    $type = 2;
                }
                if ($dataarray[ $data_num ]['attack_type'] == 3) {
                    $type = 3;
                }
                if ($dataarray[ $data_num ]['attack_type'] == 4) {
                    $type = 4;
                }
                
                $att_ab1 = $att_ab2 = $att_ab3 = $att_ab4 = $att_ab5 = $att_ab6 = $att_ab7 = $att_ab8 = 0;
                $def_ab[31] = $def_ab[32] = $def_ab[33] = $def_ab[34] = $def_ab[35] = $def_ab[36] = $def_ab[37] = $def_ab[38] = 0;
                
                $empty = '1';
                $tblevel = '0';
                $stonemason = "0";
            }
            
            //fix by ronix
            for ($i = 1; $i <= 50; $i++) $enforDefender[ 'u' . $i ] += $Defender[ 'u' . $i ];
            $defspy = ($enforDefender['u4'] > 0 || $enforDefender['u14'] > 0 || $enforDefender['u23'] > 0 || $enforDefender['u44'] > 0) ? true : false;
            
            if (PEACE == 0 || $targettribe == 4 || $targettribe == 5) {
                if ($targettribe == 1) {
                    $def_spy = $enforDefender['u4'];
                }
                elseif ($targettribe == 2) {
                    $def_spy = $enforDefender['u14'];
                }
                elseif ($targettribe == 3) {
                    $def_spy = $enforDefender['u23'];
                }
                elseif ($targettribe == 5) {
                    $def_spy = $enforDefender['u54'];
                }
                //impossible to attack or scout NATAR Capital Village
                if ($NatarCapital) {
                    for ($i = 1; $i <= 11; $i++) {
                        $varName = 'traped' . $i;
                        $$varName = $data[ 't' . $i ];
                    }
                }
                elseif (!$scout || $def_spy > 0) {
                    $traps = $Defender['u99'] - $Defender['u99o'];
                    if ($traps < 1) $traps = 0;
                    for ($i = 1; $i <= 11; $i++) {
                        $traps1 = $traps;
                        if ($data[ 't' . $i ] < $traps1) {
                            $traps1 = $data[ 't' . $i ];
                        }
                        $varName = 'traped' . $i;
                        $$varName = $traps1;
                        $traps -= $traps1;
                    }
                }
                if (!$scout || $def_spy > 0 || $NatarCapital) {
                    $totaltraped_att = $traped1 + $traped2 + $traped3 + $traped4 + $traped5 + $traped6 + $traped7 + $traped8 + $traped9 + $traped10 + $traped11;
                    $this->database->modifyUnit($data['to'], ["99o"], [$totaltraped_att], [1]);
                    for ($i = $start; $i <= $end; $i++) {
                        $j = $i - $start + 1;
                        $varName = 'traped' . $j;
                        $Attacker[ 'u' . $i ] -= $$varName;
                    }
                    $Attacker['uhero'] -= $traped11;
                    if ($totaltraped_att > 0) {
                        $prisoners2 = $this->database->getPrisoners2($data['to'], $data['from']);
                        if (empty($prisoners2)) {
                            $this->database->addPrisoners($data['to'], $data['from'], $traped1, $traped2, $traped3, $traped4, $traped5, $traped6, $traped7, $traped8, $traped9, $traped10, $traped11);
                        }
                        else {
                            $this->database->updatePrisoners($data['to'], $data['from'], $traped1, $traped2, $traped3, $traped4, $traped5, $traped6, $traped7, $traped8, $traped9, $traped10, $traped11);
                        }
                    }
                }
                //to battle.php
                //fix by ronix
                //MUST TO BE FIX : You need to filter these values
                filter_input_array($battlepart = $this->battle->calculateBattle($Attacker, $Defender, $def_wall, $att_tribe, $def_tribe, $residence, $attpop, $defpop, $type, $def_ab, $att_ab1, $att_ab2, $att_ab3, $att_ab4, $att_ab5, $att_ab6, $att_ab7, $att_ab8, $tblevel, $stonemason, $walllevel, 0, 0, 0, $AttackerID, $DefenderID, $AttackerWref, $DefenderWref, $conqureby));
                
                //units attack string for battleraport
                $unitssend_att = '' . $data['t1'] . ',' . $data['t2'] . ',' . $data['t3'] . ',' . $data['t4'] . ',' . $data['t5'] . ',' . $data['t6'] . ',' . $data['t7'] . ',' . $data['t8'] . ',' . $data['t9'] . ',' . $data['t10'] . '';
                $herosend_att = $data['t11'];
                if ($herosend_att > 0) {
                    $unitssend_att_check = $unitssend_att . ',' . $data['t11'];
                }
                else {
                    $unitssend_att_check = $unitssend_att;
                }
                //units defence string for battleraport
                $DefenderHero = [];
                $d = 0;
                if (isset($Defender['hero'])) {
                    if ($Defender['hero'] > 0) {
                        $d = 1;
                        $DefenderHero[ $d ] = $DefenderID;
                    }
                }
                
                $enforcementarray2 = $this->database->getEnforceVillage($data['to'], 0);
                if (count($enforcementarray2) > 0) {
                    foreach ($enforcementarray2 as $enforce2) {
                        $Defender['hero'] += $enforce2['hero'];
                        if ($enforce2['hero'] > 0) {
                            $d++;
                            $DefenderHero[ $d ] = $this->database->getVillageField($enforce2['from'], "owner");
                        }
                        for ($i = 1; $i <= 50; $i++) {
                            $Defender[ 'u' . $i ] += $enforce2[ 'u' . $i ];
                        }
                    }
                }
                
                $unitssend_def[1] = '' . $Defender['u1'] . ',' . $Defender['u2'] . ',' . $Defender['u3'] . ',' . $Defender['u4'] . ',' . $Defender['u5'] . ',' . $Defender['u6'] . ',' . $Defender['u7'] . ',' . $Defender['u8'] . ',' . $Defender['u9'] . ',' . $Defender['u10'] . '';
                $unitssend_def[2] = '' . $Defender['u11'] . ',' . $Defender['u12'] . ',' . $Defender['u13'] . ',' . $Defender['u14'] . ',' . $Defender['u15'] . ',' . $Defender['u16'] . ',' . $Defender['u17'] . ',' . $Defender['u18'] . ',' . $Defender['u19'] . ',' . $Defender['u20'] . '';
                $unitssend_def[3] = '' . $Defender['u21'] . ',' . $Defender['u22'] . ',' . $Defender['u23'] . ',' . $Defender['u24'] . ',' . $Defender['u25'] . ',' . $Defender['u26'] . ',' . $Defender['u27'] . ',' . $Defender['u28'] . ',' . $Defender['u29'] . ',' . $Defender['u30'] . '';
                $unitssend_def[4] = '' . $Defender['u31'] . ',' . $Defender['u32'] . ',' . $Defender['u33'] . ',' . $Defender['u34'] . ',' . $Defender['u35'] . ',' . $Defender['u36'] . ',' . $Defender['u37'] . ',' . $Defender['u38'] . ',' . $Defender['u39'] . ',' . $Defender['u40'] . '';
                $unitssend_def[5] = '' . $Defender['u41'] . ',' . $Defender['u42'] . ',' . $Defender['u43'] . ',' . $Defender['u44'] . ',' . $Defender['u45'] . ',' . $Defender['u46'] . ',' . $Defender['u47'] . ',' . $Defender['u48'] . ',' . $Defender['u49'] . ',' . $Defender['u50'] . '';
                $herosend_def = $Defender['hero'];
                $totalsend_alldef[1] = $Defender['u1'] + $Defender['u2'] + $Defender['u3'] + $Defender['u4'] + $Defender['u5'] + $Defender['u6'] + $Defender['u7'] + $Defender['u8'] + $Defender['u9'] + $Defender['u10'];
                $totalsend_alldef[2] = $Defender['u11'] + $Defender['u12'] + $Defender['u13'] + $Defender['u14'] + $Defender['u15'] + $Defender['u16'] + $Defender['u17'] + $Defender['u18'] + $Defender['u19'] + $Defender['u20'];
                $totalsend_alldef[3] = $Defender['u21'] + $Defender['u22'] + $Defender['u23'] + $Defender['u24'] + $Defender['u25'] + $Defender['u26'] + $Defender['u27'] + $Defender['u28'] + $Defender['u29'] + $Defender['u30'];
                $totalsend_alldef[4] = $Defender['u31'] + $Defender['u32'] + $Defender['u33'] + $Defender['u34'] + $Defender['u35'] + $Defender['u36'] + $Defender['u37'] + $Defender['u38'] + $Defender['u39'] + $Defender['u40'];
                $totalsend_alldef[5] = $Defender['u41'] + $Defender['u42'] + $Defender['u43'] + $Defender['u44'] + $Defender['u45'] + $Defender['u46'] + $Defender['u47'] + $Defender['u48'] + $Defender['u49'] + $Defender['u50'];
                
                $totalsend_alldef = $totalsend_alldef[1] + $totalsend_alldef[2] + $totalsend_alldef[3] + $totalsend_alldef[4] + $totalsend_alldef[5] + $herosend_def;
                
                $unitssend_deff[1] = '?,?,?,?,?,?,?,?,?,?,';
                $unitssend_deff[2] = '?,?,?,?,?,?,?,?,?,?,';
                $unitssend_deff[3] = '?,?,?,?,?,?,?,?,?,?,';
                $unitssend_deff[4] = '?,?,?,?,?,?,?,?,?,?,';
                $unitssend_deff[5] = '?,?,?,?,?,?,?,?,?,?,';
                //how many troops died? for battleraport
                
                #################################################
                ################FIXED BY SONGER################
                #################################################
                
                for ($i = 1; $i <= 11; $i++) {
                    $varName = 'dead' . $i;
                    //MUST TO BE FIX : This is only for defender and still not properly coded
                    if ($battlepart['casualties_attacker'][ $i ] <= 0) {
                        $$varName = 0;
                    }
                    elseif ($battlepart['casualties_attacker'][ $i ] > $data[ 't' . $i ]) {
                        $$varName = $data[ 't' . $i ];
                    }
                    else {
                        $$varName = $battlepart['casualties_attacker'][ $i ];
                    }
                }
                //if the defender does not have spies, the attacker will not die spies.    FIXED BY Armando
                if ($scout) {
                    $spy_def_Detect = 0;
                    for ($i = 1; $i <= (50); $i++) {
                        if ($i == 4 || $i == 14 || $i == 23 || $i == 34 || $i == 44) {
                            if ($Defender[ 'u' . $i ] > 0) {
                                $spy_def_Detect = $i;
                                break;
                            }
                        }
                    }
                    if ($spy_def_Detect == 0) {
                        $dead3 = 0;
                        $dead4 = 0;
                        $battlepart['casualties_attacker'][3] = 0;
                        $battlepart['casualties_attacker'][4] = 0;
                    }
                }
                
                #################################################
                
                $dead = [];
                $owndead = [];
                $alldead = [];
                $heroAttackDead = $dead11;
                //kill own defence
                $q = "SELECT * FROM " . TB_PREFIX . "units WHERE vref='" . $data['to'] . "'";
                $unitlist = $this->database->query_return($q);
                $start = ($targettribe - 1) * 10 + 1;
                $end = ($targettribe * 10);
                
                if ($targettribe == 1) {
                    $u = "";
                    $rom = '1';
                }
                elseif ($targettribe == 2) {
                    $u = "1";
                    $ger = '1';
                }
                elseif ($targettribe == 3) {
                    $u = "2";
                    $gal = '1';
                }
                elseif ($targettribe == 4) {
                    $u = "3";
                    $nat = '1';
                }
                else {
                    $u = "4";
                    $natar = '1';
                } //FIX
                for ($i = $start; $i <= $end; $i++) {
                    if ($i == $end) {
                        $u = $targettribe;
                    }
                    if ($unitlist) {
                        $owndead[ $i ] = round($battlepart[2] * $unitlist[0][ 'u' . $i ]);
                        $this->database->modifyUnit($data['to'], [$i], [$owndead[ $i ]], [0]);
                    }
                }
                $owndead['hero'] = '0';
                if ($unitlist) {
                    $owndead['hero'] = $battlepart['deadherodef'];
                    $this->database->modifyUnit($data['to'], ["hero"], [$owndead['hero']], [0]);
                }
                //kill other defence in village
                if (count($this->database->getEnforceVillage($data['to'], 0)) > 0) {
                    foreach ($this->database->getEnforceVillage($data['to'], 0) as $enforce) {
                        $life = '';
                        $notlife = '';
                        $wrong = '0';
                        if ($enforce['from'] != 0) {
                            $tribe = $this->database->getUserField($this->database->getVillageField($enforce['from'], "owner"), "tribe", 0);
                        }
                        else {
                            $tribe = 4;
                        }
                        $start = ($tribe - 1) * 10 + 1;
                        $end = ($tribe * 10);
                        unset($dead);
                        if ($tribe == 1) {
                            $rom = '1';
                        }
                        elseif ($tribe == 2) {
                            $ger = '1';
                        }
                        elseif ($tribe == 3) {
                            $gal = '1';
                        }
                        elseif ($tribe == 4) {
                            $nat = '1';
                        }
                        else {
                            $natar = '1';
                        }
                        for ($i = $start; $i <= $end; $i++) { //($i=$start;$i<=($start+9);$i++) {
                            if ($enforce[ 'u' . $i ] > '0') {
                                $this->database->modifyEnforce($enforce['id'], $i, round($battlepart[2] * $enforce[ 'u' . $i ]), 0);
                                $dead[ $i ] = round($battlepart[2] * $enforce[ 'u' . $i ]);
                                $checkpoint = round($battlepart[2] * $enforce[ 'u' . $i ]);
                                $alldead[ $i ] += $dead[ $i ];
                                if ($checkpoint != $enforce[ 'u' . $i ]) {
                                    $wrong = '1';
                                }
                            }
                            else {
                                $dead[ $i ] = '0';
                            }
                        }
                        if ($enforce['hero'] > '0') {
                            $this->database->modifyEnforce($enforce['id'], "hero", $battlepart['deadheroref'][ $enforce['id'] ], 0);
                            $dead['hero'] = $battlepart['deadheroref'][ $enforce['id'] ];
                            $alldead['hero'] += $dead['hero'];
                            if ($dead['hero'] != $enforce['hero']) {
                                $wrong = '1';
                            }
                        }
                        $notlife = '' . $dead[ $start ] . ',' . $dead[ $start + 1 ] . ',' . $dead[ $start + 2 ] . ',' . $dead[ $start + 3 ] . ',' . $dead[ $start + 4 ] . ',' . $dead[ $start + 5 ] . ',' . $dead[ $start + 6 ] . ',' . $dead[ $start + 7 ] . ',' . $dead[ $start + 8 ] . ',' . $dead[ $start + 9 ] . '';
                        $notlife1 = $dead[ $start ] + $dead[ $start + 1 ] + $dead[ $start + 2 ] + $dead[ $start + 3 ] + $dead[ $start + 4 ] + $dead[ $start + 5 ] + $dead[ $start + 6 ] + $dead[ $start + 7 ] + $dead[ $start + 8 ] + $dead[ $start + 9 ];
                        $life = '' . $enforce[ 'u' . $start . '' ] . ',' . $enforce[ 'u' . ($start + 1) . '' ] . ',' . $enforce[ 'u' . ($start + 2) . '' ] . ',' . $enforce[ 'u' . ($start + 3) . '' ] . ',' . $enforce[ 'u' . ($start + 4) . '' ] . ',' . $enforce[ 'u' . ($start + 5) . '' ] . ',' . $enforce[ 'u' . ($start + 6) . '' ] . ',' . $enforce[ 'u' . ($start + 7) . '' ] . ',' . $enforce[ 'u' . ($start + 8) . '' ] . ',' . $enforce[ 'u' . ($start + 9) . '' ] . '';
                        $life1 = $enforce[ 'u' . $start . '' ] + $enforce[ 'u' . ($start + 1) . '' ] + $enforce[ 'u' . ($start + 2) . '' ] + $enforce[ 'u' . ($start + 3) . '' ] + $enforce[ 'u' . ($start + 4) . '' ] + $enforce[ 'u' . ($start + 5) . '' ] + $enforce[ 'u' . ($start + 6) . '' ] + $enforce[ 'u' . ($start + 7) . '' ] + $enforce[ 'u' . ($start + 8) . '' ] + $enforce[ 'u' . ($start + 9) . '' ];
                        $lifehero = $enforce['hero'];
                        $notlifehero = $dead['hero'];
                        $totallife = $enforce['hero'] + $life1;
                        $totalnotlife = $dead['hero'] + $notlife1;
                        $totalsend_att = $data['t1'] + $data['t2'] + $data['t3'] + $data['t4'] + $data['t5'] + $data['t6'] + $data['t7'] + $data['t8'] + $data['t9'] + $data['t10'] + $data['t11'];
                        $totaldead_att = $dead1 + $dead2 + $dead3 + $dead4 + $dead5 + $dead6 + $dead7 + $dead8 + $dead9 + $dead10 + $dead11;
                        //NEED TO SEND A RAPPORTAGE!!!
                        $data2 = '' . $this->database->getVillageField($enforce['from'], "owner") . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $tribe . ',' . $life . ',' . $notlife . ',' . $lifehero . ',' . $notlifehero . ',' . $enforce['from'] . '';
                        if (!$scout) {
                            if ($totalnotlife == 0) {
                                $this->database->addNotice($this->database->getVillageField($enforce['from'], "owner"), $from['wref'], $ownally, 15, 'Reinforcement in ' . addslashes($to['name']) . ' was attacked', $data2, $AttackArrivalTime);
                            }
                            elseif ($totallife > $totalnotlife) {
                                $this->database->addNotice($this->database->getVillageField($enforce['from'], "owner"), $from['wref'], $ownally, 16, 'Reinforcement in ' . addslashes($to['name']) . ' was attacked', $data2, $AttackArrivalTime);
                            }
                            else {
                                $this->database->addNotice($this->database->getVillageField($enforce['from'], "owner"), $from['wref'], $ownally, 17, 'Reinforcement in ' . addslashes($to['name']) . ' was attacked', $data2, $AttackArrivalTime);
                            }
                            //delete reinf sting when its killed all.
                            if ($wrong == '0') {
                                $this->database->deleteReinf($enforce['id']);
                            }
                        }
                    }
                }
                $totalsend_att = $data['t1'] + $data['t2'] + $data['t3'] + $data['t4'] + $data['t5'] + $data['t6'] + $data['t7'] + $data['t8'] + $data['t9'] + $data['t10'] + $data['t11'];
                for ($i = 1; $i <= 50; $i++) {
                    $alldead[ $i ] += $owndead[ $i ];
                }
                $unitsdead_def[1] = '' . $alldead['1'] . ',' . $alldead['2'] . ',' . $alldead['3'] . ',' . $alldead['4'] . ',' . $alldead['5'] . ',' . $alldead['6'] . ',' . $alldead['7'] . ',' . $alldead['8'] . ',' . $alldead['9'] . ',' . $alldead['10'] . '';
                $unitsdead_def[2] = '' . $alldead['11'] . ',' . $alldead['12'] . ',' . $alldead['13'] . ',' . $alldead['14'] . ',' . $alldead['15'] . ',' . $alldead['16'] . ',' . $alldead['17'] . ',' . $alldead['18'] . ',' . $alldead['19'] . ',' . $alldead['20'] . '';
                $unitsdead_def[3] = '' . $alldead['21'] . ',' . $alldead['22'] . ',' . $alldead['23'] . ',' . $alldead['24'] . ',' . $alldead['25'] . ',' . $alldead['26'] . ',' . $alldead['27'] . ',' . $alldead['28'] . ',' . $alldead['29'] . ',' . $alldead['30'] . '';
                $unitsdead_def[4] = '' . $alldead['31'] . ',' . $alldead['32'] . ',' . $alldead['33'] . ',' . $alldead['34'] . ',' . $alldead['35'] . ',' . $alldead['36'] . ',' . $alldead['37'] . ',' . $alldead['38'] . ',' . $alldead['39'] . ',' . $alldead['40'] . '';
                $unitsdead_def[5] = '' . $alldead['41'] . ',' . $alldead['42'] . ',' . $alldead['43'] . ',' . $alldead['44'] . ',' . $alldead['45'] . ',' . $alldead['46'] . ',' . $alldead['47'] . ',' . $alldead['48'] . ',' . $alldead['49'] . ',' . $alldead['50'] . '';
                $unitsdead_deff[1] = '?,?,?,?,?,?,?,?,?,?,';
                $unitsdead_deff[2] = '?,?,?,?,?,?,?,?,?,?,';
                $unitsdead_deff[3] = '?,?,?,?,?,?,?,?,?,?,';
                $unitsdead_deff[4] = '?,?,?,?,?,?,?,?,?,?,';
                $unitsdead_deff[5] = '?,?,?,?,?,?,?,?,?,?,';
                $deadhero = $alldead['hero'] + $owndead['hero'];
                
                $totaldead_alldef[1] = $alldead['1'] + $alldead['2'] + $alldead['3'] + $alldead['4'] + $alldead['5'] + $alldead['6'] + $alldead['7'] + $alldead['8'] + $alldead['9'] + $alldead['10'];
                $totaldead_alldef[2] = $alldead['11'] + $alldead['12'] + $alldead['13'] + $alldead['14'] + $alldead['15'] + $alldead['16'] + $alldead['17'] + $alldead['18'] + $alldead['19'] + $alldead['20'];
                $totaldead_alldef[3] = $alldead['21'] + $alldead['22'] + $alldead['23'] + $alldead['24'] + $alldead['25'] + $alldead['26'] + $alldead['27'] + $alldead['28'] + $alldead['29'] + $alldead['30'];
                $totaldead_alldef[4] = $alldead['31'] + $alldead['32'] + $alldead['33'] + $alldead['34'] + $alldead['35'] + $alldead['36'] + $alldead['37'] + $alldead['38'] + $alldead['39'] + $alldead['40'];
                $totaldead_alldef[5] = $alldead['41'] + $alldead['42'] + $alldead['43'] + $alldead['44'] + $alldead['45'] + $alldead['46'] + $alldead['47'] + $alldead['48'] + $alldead['49'] + $alldead['50'];
                
                $totaldead_alldef = $totaldead_alldef[1] + $totaldead_alldef[2] + $totaldead_alldef[3] + $totaldead_alldef[4] + $totaldead_alldef[5] + $deadhero;
                $totalattackdead += $totaldead_alldef;
                
                // Set units returning from attack
                
                for ($i = 1; $i <= 11; $i++) {
                    $varNameDead = 'dead' . $i;
                    $varNameTraped = 'traped' . $i;
                    $t_units .= "t" . $i . "=t" . $i . " - " . $$varNameDead . (($i > 10) ? '' : ', ');
                    $p_units .= "t" . $i . "=t" . $i . " - " . $$varNameTraped . (($i > 10) ? '' : ', ');
                }
                
                $this->database->modifyAttack3($data['ref'], $t_units);
                $this->database->modifyAttack3($data['ref'], $p_units);
                
                $unitsdead_att = '' . $dead1 . ',' . $dead2 . ',' . $dead3 . ',' . $dead4 . ',' . $dead5 . ',' . $dead6 . ',' . $dead7 . ',' . $dead8 . ',' . $dead9 . ',' . $dead10 . '';
                $unitstraped_att = '' . $traped1 . ',' . $traped2 . ',' . $traped3 . ',' . $traped4 . ',' . $traped5 . ',' . $traped6 . ',' . $traped7 . ',' . $traped8 . ',' . $traped9 . ',' . $traped10 . ',' . $traped11 . '';
                if ($herosend_att > 0) {
                    $unitsdead_att_check = $unitsdead_att . ',' . $dead11;
                }
                else {
                    $unitsdead_att_check = $unitsdead_att;
                }
                //$unitsdead_def = ''.$dead11.','.$dead12.','.$dead13.','.$dead14.','.$dead15.','.$dead16.','.$dead17.','.$dead18.','.$dead19.','.$dead20.'';
                
                //top 10 attack and defence update
                $totaldead_att = $dead1 + $dead2 + $dead3 + $dead4 + $dead5 + $dead6 + $dead7 + $dead8 + $dead9 + $dead10 + $dead11;
                $totalattackdead += $totaldead_att;
                $troopsdead1 = $dead1;
                $troopsdead2 = $dead2;
                $troopsdead3 = $dead3;
                $troopsdead4 = $dead4;
                $troopsdead5 = $dead5;
                $troopsdead6 = $dead6;
                $troopsdead7 = $dead7;
                $troopsdead8 = $dead8;
                $troopsdead9 = $dead9 + 1;
                $troopsdead10 = $dead10;
                $troopsdead11 = $dead11;
                for ($i = 1; $i <= 50; $i++) {
                    if ($unitarray) {
                        reset($unitarray);
                    }
                    $unitarray = $GLOBALS[ "u" . $i ];
                    
                    $totaldead_def += $alldead[ '' . $i . '' ];
                    
                    $totalpoint_att += ($alldead[ '' . $i . '' ] * $unitarray['pop']);
                }
                $totalpoint_att += ($alldead['hero'] * 6);
                
                if ($Attacker['uhero'] != 0) {
                    $heroxp = $totalpoint_att;
                    $this->database->modifyHeroXp("experience", $heroxp, $from['owner']);
                }
                
                for ($i = 1; $i <= 10; $i++) {
                    if ($unitarray) {
                        reset($unitarray);
                    }
                    $unitarray = $GLOBALS[ "u" . (($att_tribe - 1) * 10 + $i) ];
                    $varName = 'dead' . $i;
                    $totalpoint_def += ($$varName * $unitarray['pop']);
                }
                $totalpoint_def += $dead11 * 6;
                if ($Defender['hero'] > 0) {
                    //counting heroxp
                    $defheroxp = intval($totalpoint_def / count($DefenderHero));
                    for ($i = 1; $i <= count($DefenderHero); $i++) {
                        $reinfheroxp = $defheroxp;
                        $this->database->modifyHeroXp("experience", $reinfheroxp, $DefenderHero[ $i ]);
                    }
                }
                
                $this->database->modifyPoints($toF['owner'], 'dpall', $totalpoint_def);
                $this->database->modifyPoints($from['owner'], 'apall', $totalpoint_att);
                $this->database->modifyPoints($toF['owner'], 'dp', $totalpoint_def);
                $this->database->modifyPoints($from['owner'], 'ap', $totalpoint_att);
                $this->database->modifyPointsAlly($targetally, 'Adp', $totalpoint_def);
                $this->database->modifyPointsAlly($ownally, 'Aap', $totalpoint_att);
                $this->database->modifyPointsAlly($targetally, 'dp', $totalpoint_def);
                $this->database->modifyPointsAlly($ownally, 'ap', $totalpoint_att);
                
                if ($isoasis == 0) {
                    // get toatal cranny value:
                    $buildarray = $this->database->getResourceLevel($data['to']);
                    $cranny = 0;
                    for ($i = 19; $i < 39; $i++) {
                        if ($buildarray[ 'f' . $i . 't' ] == 23) {
                            $cranny += $bid23[ $buildarray[ 'f' . $i . '' ] ]['attri'] * CRANNY_CAPACITY;
                        }
                    }
                    
                    //cranny efficiency
                    $atk_bonus = ($owntribe == 2) ? (4 / 5) : 1;
                    $def_bonus = ($targettribe == 3) ? 2 : 1;
                    $to_owner = $this->database->getVillageField($data['to'], "owner");
                    $artefact_2 = count($this->database->getOwnUniqueArtefactInfo2($to_owner, 7, 3, 0));
                    $artefact1_2 = count($this->database->getOwnUniqueArtefactInfo2($data['to'], 7, 1, 1));
                    $artefact2_2 = count($this->database->getOwnUniqueArtefactInfo2($to_owner, 7, 2, 0));
                    if ($artefact_2 > 0) {
                        $artefact_bouns = 6;
                    }
                    elseif ($artefact1_2 > 0) {
                        $artefact_bouns = 3;
                    }
                    elseif ($artefact2_2 > 0) {
                        $artefact_bouns = 2;
                    }
                    else {
                        $artefact_bouns = 1;
                    }
                    $foolartefact = $this->database->getFoolArtefactInfo(7, $vid, $this->session->uid);
                    if (count($foolartefact) > 0) {
                        foreach ($foolartefact as $arte) {
                            if ($arte['bad_effect'] == 1) {
                                $cranny_eff *= $arte['effect2'];
                            }
                            else {
                                $cranny_eff /= $arte['effect2'];
                                $cranny_eff = round($cranny_eff);
                            }
                        }
                    }
                    $cranny_eff = ($cranny * $atk_bonus) * $def_bonus * $artefact_bouns;
                    
                    // work out available resources.
                    $this->updateRes($data['to'], $to['owner']);
                    (new VillageRecalculationHelper($this->database))
                        ->pruneResource();

                    $totclay = $this->database->getVillageField($data['to'], 'clay');
                    $totiron = $this->database->getVillageField($data['to'], 'iron');
                    $totwood = $this->database->getVillageField($data['to'], 'wood');
                    $totcrop = $this->database->getVillageField($data['to'], 'crop');
                }
                else {
                    $cranny_eff = 0;
                    
                    // work out available resources.
                    $this->updateORes($data['to']);
                    (new VillageRecalculationHelper($this->database))
                        ->pruneOasisResource();
                    
                    if ($conqureby > 0) { //10% from owner proc village owner - fix by ronix
                        $totclay = intval($this->database->getVillageField($conqureby, 'clay') / 10);
                        $totiron = intval($this->database->getVillageField($conqureby, 'iron') / 10);
                        $totwood = intval($this->database->getVillageField($conqureby, 'wood') / 10);
                        $totcrop = intval($this->database->getVillageField($conqureby, 'crop') / 10);
                    }
                    else {
                        $totclay = $this->database->getOasisField($data['to'], 'clay');
                        $totiron = $this->database->getOasisField($data['to'], 'iron');
                        $totwood = $this->database->getOasisField($data['to'], 'wood');
                        $totcrop = $this->database->getOasisField($data['to'], 'crop');
                    }
                }
                $avclay = floor($totclay - $cranny_eff);
                $aviron = floor($totiron - $cranny_eff);
                $avwood = floor($totwood - $cranny_eff);
                $avcrop = floor($totcrop - $cranny_eff);
                
                $avclay = ($avclay < 0) ? 0 : $avclay;
                $aviron = ($aviron < 0) ? 0 : $aviron;
                $avwood = ($avwood < 0) ? 0 : $avwood;
                $avcrop = ($avcrop < 0) ? 0 : $avcrop;
                
                $avtotal = [$avwood, $avclay, $aviron, $avcrop];
                
                $av = $avtotal;
                
                // resources (wood,clay,iron,crop)
                $steal = [0, 0, 0, 0];
                
                //bounty variables
                $btotal = $battlepart['bounty'];
                $bmod = 0;
                
                for ($i = 0; $i < 5; $i++) {
                    for ($j = 0; $j < 4; $j++) {
                        if (isset($avtotal[ $j ])) {
                            if ($avtotal[ $j ] < 1)
                                unset($avtotal[ $j ]);
                        }
                    }
                    if (!$avtotal) {
                        // echo 'array empty'; *no resources left to take.
                        break;
                    }
                    if ($btotal < 1 && $bmod < 1)
                        break;
                    if ($btotal < 1) {
                        while ($bmod) {
                            //random select
                            $rs = array_rand($avtotal);
                            if (isset($avtotal[ $rs ])) {
                                $avtotal[ $rs ] -= 1;
                                $steal[ $rs ] += 1;
                                $bmod -= 1;
                            }
                        }
                    }
                    
                    // handle unballanced amounts.
                    $btotal += $bmod;
                    $bmod = $btotal % count($avtotal);
                    $btotal -= $bmod;
                    $bsplit = $btotal / count($avtotal);
                    
                    $max_steal = (min($avtotal) < $bsplit) ? min($avtotal) : $bsplit;
                    
                    for ($j = 0; $j < 4; $j++) {
                        if (isset($avtotal[ $j ])) {
                            $avtotal[ $j ] -= $max_steal;
                            $steal[ $j ] += $max_steal;
                            $btotal -= $max_steal;
                        }
                    }
                }
                
                //work out time of return
                $start = ($owntribe - 1) * 10 + 1;
                $end = ($owntribe * 10);
                
                $unitspeeds = [6, 5, 7, 16, 14, 10, 4, 3, 4, 5,
                    7, 7, 6, 9, 10, 9, 4, 3, 4, 5,
                    7, 6, 17, 19, 16, 13, 4, 3, 4, 5,
                    7, 7, 6, 9, 10, 9, 4, 3, 4, 5,
                    7, 7, 6, 9, 10, 9, 4, 3, 4, 5];
                
                $speeds = [];
                
                //find slowest unit.
                for ($i = 1; $i <= 10; $i++) {
                    if ($data[ 't' . $i ] > $battlepart['casualties_attacker'][ $i ]) {
                        if ($unitarray) {
                            reset($unitarray);
                        }
                        $unitarray = $GLOBALS[ "u" . (($owntribe - 1) * 10 + $i) ];
                        $speeds[] = $unitarray['speed'];
                    }
                }
                if ($herosend_att > 0) {
                    $qh = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $from['owner'] . "";
                    $resulth = $this->database->query($qh);
                    $hero_f = $this->database->fetchArray($resulth);
                    $hero_unit = $hero_f['unit'];
                    $speeds[] = $GLOBALS[ 'u' . $hero_unit ]['speed'];
                }
                
                // Data for when troops return.
                
                //catapults look :D
                $info_cat = $info_chief = $info_ram = $info_hero = ",";
                //check to see if can destroy village
                $varray = $this->database->getProfileVillages($to['owner']);
                if (count($varray) != '1' AND $to['capital'] != '1') {
                    $can_destroy = 1;
                }
                else {
                    $can_destroy = 0;
                }
                if ($isoasis == 1) $can_destroy = 0;
                
                if ($type == '3') {
                    if (($data['t7'] - $traped7) > 0) {
                        if (isset($empty)) {
                            $info_ram = "" . $ram_pic . ",There is no wall to destroy.";
                        }
                        elseif ($battlepart[8] > $battlepart[7]) {
                            $info_ram = "" . $ram_pic . ",Wall destroyed.";
                            $this->database->setVillageLevel($data['to'], "f" . $wallid . "", '0');
                            $this->database->setVillageLevel($data['to'], "f" . $wallid . "t", '0');
                            (new VillageRecalculationHelper($this->database))
                                ->recountVillage($data['to']);
                        }
                        elseif ($battlepart[8] == 0) {
                            
                            $info_ram = "" . $ram_pic . ",Wall was not damaged.";
                        }
                        else {
                            
                            $demolish = $battlepart[8] / $battlepart[7];
                            $totallvl = round(sqrt(pow(($walllevel + 0.5), 2) - ($battlepart[8] * 8)));
                            if ($walllevel == $totallvl) {
                                $info_ram = "" . $ram_pic . ",Wall was not damaged.";
                            }
                            else {
                                $info_ram = "" . $ram_pic . ",Wall damaged from level <b>" . $walllevel . "</b> to level <b>" . $totallvl . "</b>.";
                                $this->database->setVillageLevel($data['to'], "f" . $wallid . "", $totallvl);
                            }
                        }
                    }
                }
                elseif (($data['t7'] - $traped7) > 0) {
                    $info_ram = "" . $ram_pic . ",Hint: The ram does not work during a raid.";
                }
                if ($type == '3') {
                    if (($data['t8'] - $traped8) > 0) {
                        (new VillageRecalculationHelper($this->database))
                            ->recountVillage($data['to']);
                        if ($isoasis == 0) {
                            $pop = (new VillageRecalculationHelper($this->database))
                                ->recountVillage($data['to']);
                        }
                        else $pop = 10; //oasis cannot be destroy bt cata/ram
                        if ($pop <= 0) {
                            if ($can_destroy == 1) {
                                $info_cat = "" . $catp_pic . ", Village already destroyed.";
                            }
                            else {
                                $info_cat = "" . $catp_pic . ", Village can\'t be destroyed.";
                            }
                        }
                        else {
                            $basearray = $data['to'];
                            
                            if ($data['ctar2'] == 0) {
                                $bdo2 = $this->database->query("select * from " . TB_PREFIX . "fdata where vref = $basearray");
                                $bdo = $this->database->fetchArray($bdo2);
                                
                                $rand = $data['ctar1'];
                                
                                if ($rand != 0) {
                                    $_rand = [];
                                    $__rand = [];
                                    $j = 0;
                                    for ($i = 1; $i <= 41; $i++) {
                                        if ($i == 41) $i = 99;
                                        if ($bdo[ 'f' . $i . 't' ] == $rand && $bdo[ 'f' . $i ] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                            $j++;
                                            $_rand[ $j ] = $bdo[ 'f' . $i ];
                                            $__rand[ $j ] = $i;
                                        }
                                    }
                                    if (count($_rand) > 0) {
                                        if (max($_rand) <= 0) $rand = 0;
                                        else {
                                            $rand = rand(1, $j);
                                            $rand = $__rand[ $rand ];
                                        }
                                    }
                                    else {
                                        $rand = 0;
                                    }
                                }
                                
                                if ($rand == 0) {
                                    $list = [];
                                    $j = 1;
                                    for ($i = 1; $i <= 41; $i++) {
                                        if ($i == 41) $i = 99;
                                        if ($bdo[ 'f' . $i ] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                            $list[ $j ] = $i;
                                            $j++;
                                        }
                                    }
                                    $rand = rand(1, $j);
                                    $rand = $list[ $rand ];
                                }
                                
                                $tblevel = $bdo[ 'f' . $rand ];
                                $tbgid = $bdo[ 'f' . $rand . 't' ];
                                $tbid = $rand;
                                if ($battlepart[4] > $battlepart[3]) {
                                    $info_cat = "" . $catp_pic . ", " . $this->procResType($tbgid, $can_destroy, $isoasis) . " destroyed.";
                                    $this->database->setVillageLevel($data['to'], "f" . $tbid . "", '0');
                                    if ($tbid >= 19 && $tbid != 99) {
                                        $this->database->setVillageLevel($data['to'], "f" . $tbid . "t", '0');
                                    }
                                    $buildarray = $GLOBALS[ "bid" . $tbgid ];
                                    if ($tbgid == 10 || $tbgid == 38) {
                                        $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = $this->database->fetchArray($tsql);
                                        $tmaxstore = $t_sql['maxstore'] - $buildarray[ $tblevel ]['attri'];
                                        if ($tmaxstore < 800) $tmaxstore = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "'*32 WHERE wref=" . $data['to'];
                                        $this->database->query($q);
                                    }
                                    if ($tbgid == 11 || $tbgid == 39) {
                                        $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = $this->database->fetchArray($tsql);
                                        $tmaxcrop = $t_sql['maxcrop'] - $buildarray[ $tblevel ]['attri'];
                                        if ($tmaxcrop < 800) $tmaxcrop = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "'*32 WHERE wref=" . $data['to'];
                                        $this->database->query($q);
                                    }
                                    if ($tbgid == 18) {
                                        (new VillageHelper($this->database))
                                            ->updateMaxAllyMembers(
                                                $this->database->getVillageField($data['to'], 'owner')
                                            );
                                    }
                                    if ($isoasis == 0) {
                                        $pop = (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                        $capital = $this->database->getVillage($data['to']);
                                        if ($pop == '0' && $can_destroy == 1) {
                                            $village_destroyed = 1;
                                        }
                                    }
                                }
                                elseif ($battlepart[4] == 0) {
                                    $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . " was not damaged.";
                                }
                                else {
                                    //MUST TO BE FIX : Unless variable
                                    $demolish = $battlepart[4] / $battlepart[3];
                                    //MUST TO BE FIX This part goes also below 0 if u have a lot of catapults
                                    $totallvl = round(sqrt(pow(($tblevel + 0.5), 2) - ($battlepart[4] * 8)));
                                    if ($tblevel == $totallvl)
                                        $info_cata = " was not damaged.";
                                    else {
                                        $info_cata = " damaged from level <b>" . $tblevel . "</b> to level <b>" . $totallvl . "</b>.";
                                        $buildarray = $GLOBALS[ "bid" . $tbgid ];
                                        if ($tbgid == 10 || $tbgid == 38) {
                                            $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                            $t_sql = $this->database->fetchArray($tsql);
                                            $tmaxstore = $t_sql['maxstore'] + $buildarray[ $totallvl ]['attri'] - $buildarray[ $tblevel ]['attri'];
                                            if ($tmaxstore < 800) $tmaxstore = 800;
                                            $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                            $this->database->query($q);
                                        }
                                        if ($tbgid == 11 || $tbgid == 39) {
                                            $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                            $t_sql = $this->database->fetchArray($tsql);
                                            $tmaxcrop = $t_sql['maxcrop'] + $buildarray[ $totallvl ]['attri'] - $buildarray[ $tblevel ]['attri'];
                                            if ($tmaxcrop < 800) $tmaxcrop = 800;
                                            $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                            $this->database->query($q);
                                        }
                                        if ($tbgid == 18) {
                                            (new VillageHelper($this->database))
                                                ->updateMaxAllyMembers(
                                                    $this->database->getVillageField($data['to'], 'owner')
                                                );
                                        }
                                        (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                    }
                                    $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . $info_cata;
                                    $this->database->setVillageLevel($data['to'], "f" . $tbid . "", $totallvl);
                                }
                            }
                            else {
                                $bdo2 = $this->database->query("select * from " . TB_PREFIX . "fdata where vref = $basearray");
                                $bdo = $this->database->fetchArray($bdo2);
                                $rand = $data['ctar1'];
                                if ($rand != 0) {
                                    $_rand = [];
                                    $__rand = [];
                                    $j = 0;
                                    for ($i = 1; $i <= 41; $i++) {
                                        if ($i == 41) $i = 99;
                                        if ($bdo[ 'f' . $i . 't' ] == $rand && $bdo[ 'f' . $i ] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                            $j++;
                                            $_rand[ $j ] = $bdo[ 'f' . $i ];
                                            $__rand[ $j ] = $i;
                                        }
                                    }
                                    if (count($_rand) > 0) {
                                        if (max($_rand) <= 0) $rand = 0;
                                        else {
                                            $rand = rand(1, $j);
                                            $rand = $__rand[ $rand ];
                                        }
                                    }
                                    else {
                                        $rand = 0;
                                    }
                                }
                                
                                if ($rand == 0) {
                                    $list = [];
                                    $j = 0;
                                    for ($i = 1; $i <= 41; $i++) {
                                        if ($i == 41) $i = 99;
                                        if ($bdo[ 'f' . $i ] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                            $j++;
                                            $list[ $j ] = $i;
                                        }
                                    }
                                    $rand = rand(1, $j);
                                    $rand = $list[ $rand ];
                                }
                                
                                $tblevel = $bdo[ 'f' . $rand ];
                                $tbgid = $bdo[ 'f' . $rand . 't' ];
                                $tbid = $rand;
                                if ($battlepart[4] > $battlepart[3]) {
                                    $info_cat = "" . $catp_pic . ", " . $this->procResType($tbgid, $can_destroy, $isoasis) . " destroyed.";
                                    $this->database->setVillageLevel($data['to'], "f" . $tbid . "", '0');
                                    if ($tbid >= 19 && $tbid != 99) {
                                        $this->database->setVillageLevel($data['to'], "f" . $tbid . "t", '0');
                                    }
                                    $buildarray = $GLOBALS[ "bid" . $tbgid ];
                                    if ($tbgid == 10 || $tbgid == 38) {
                                        $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = $this->database->fetchArray($tsql);
                                        $tmaxstore = $t_sql['maxstore'] - $buildarray[ $tblevel ]['attri'];
                                        if ($tmaxstore < 800) $tmaxstore = 800 * 32;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                        $this->database->query($q);
                                    }
                                    if ($tbgid == 11 || $tbgid == 39) {
                                        $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = $this->database->fetchArray($tsql);
                                        $tmaxcrop = $t_sql['maxcrop'] - $buildarray[ $tblevel ]['attri'];
                                        if ($tmaxcrop < 800) $tmaxcrop = 800 * 32;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                        $this->database->query($q);
                                    }
                                    if ($tbgid == 18) {
                                        (new VillageHelper($this->database))
                                            ->updateMaxAllyMembers(
                                                $this->database->getVillageField($data['to'], 'owner')
                                            );
                                    }
                                    if ($isoasis == 0) {
                                        (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                    }
                                    if ($isoasis == 0) {
                                        $pop = (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                        if ($pop == '0') {
                                            if ($can_destroy == 1) {
                                                $village_destroyed = 1;
                                            }
                                        }
                                    }
                                }
                                elseif ($battlepart[4] == 0) {
                                    $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . " was not damaged.";
                                }
                                else {
                                    $demolish = $battlepart[4] / $battlepart[3];
                                    $totallvl = round(sqrt(pow(($tblevel + 0.5), 2) - (($battlepart[4] / 2) * 8)));
                                    if ($tblevel == $totallvl)
                                        $info_cata = " was not damaged.";
                                    else {
                                        $info_cata = " damaged from level <b>" . $tblevel . "</b> to level <b>" . $totallvl . "</b>.";
                                        $buildarray = $GLOBALS[ "bid" . $tbgid ];
                                        if ($tbgid == 10 || $tbgid == 38) {
                                            $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                            $t_sql = $this->database->fetchArray($tsql);
                                            $tmaxstore = $t_sql['maxstore'] + $buildarray[ $totallvl ]['attri'] - $buildarray[ $tblevel ]['attri'];
                                            if ($tmaxstore < 800) $tmaxstore = 800;
                                            $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                            $this->database->query($q);
                                        }
                                        if ($tbgid == 11 || $tbgid == 39) {
                                            $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                            $t_sql = $this->database->fetchArray($tsql);
                                            $tmaxcrop = $t_sql['maxcrop'] + $buildarray[ $totallvl ]['attri'] - $buildarray[ $tblevel ]['attri'];
                                            if ($tmaxcrop < 800) $tmaxcrop = 800;
                                            $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                            $this->database->query($q);
                                        }
                                        if ($tbgid == 18) {
                                            (new VillageHelper($this->database))
                                                ->updateMaxAllyMembers(
                                                    $this->database->getVillageField($data['to'], 'owner')
                                                );
                                        }
                                        (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                    }
                                    $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . $info_cata;
                                    $this->database->setVillageLevel($data['to'], "f" . $tbid . "", $totallvl);
                                }
                                $bdo2 = $this->database->query("select * from " . TB_PREFIX . "fdata where vref = $basearray");
                                $bdo = $this->database->fetchArray($bdo2);
                                $rand = $data['ctar2'];
                                if ($rand != 99) {
                                    $_rand = [];
                                    $__rand = [];
                                    $j = 0;
                                    for ($i = 1; $i <= 41; $i++) {
                                        if ($i == 41) $i = 99;
                                        if ($bdo[ 'f' . $i . 't' ] == $rand && $bdo[ 'f' . $i ] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                            $j++;
                                            $_rand[ $j ] = $bdo[ 'f' . $i ];
                                            $__rand[ $j ] = $i;
                                        }
                                    }
                                    if (count($_rand) > 0) {
                                        if (max($_rand) <= 0) $rand = 99;
                                        else {
                                            $rand = rand(1, $j);
                                            $rand = $__rand[ $rand ];
                                        }
                                    }
                                    else {
                                        $rand = 99;
                                    }
                                }
                                
                                if ($rand == 99) {
                                    $list = [];
                                    $j = 0;
                                    for ($i = 1; $i <= 41; $i++) {
                                        if ($i == 41) $i = 99;
                                        if ($bdo[ 'f' . $i ] > 0) {
                                            $j++;
                                            $list[ $j ] = $i;
                                        }
                                    }
                                    $rand = rand(1, $j);
                                    $rand = $list[ $rand ];
                                }
                                
                                $tblevel = $bdo[ 'f' . $rand ];
                                $tbgid = $bdo[ 'f' . $rand . 't' ];
                                $tbid = $rand;
                                if ($battlepart[4] > $battlepart[3]) {
                                    $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
                    <img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> " . $this->procResType($tbgid, $can_destroy, $isoasis) . " destroyed.</td></tr></tbody>";
                                    $this->database->setVillageLevel($data['to'], "f" . $tbid . "", '0');
                                    if ($tbid >= 19 && $tbid != 99) {
                                        $this->database->setVillageLevel($data['to'], "f" . $tbid . "t", '0');
                                    }
                                    $buildarray = $GLOBALS[ "bid" . $tbgid ];
                                    if ($tbgid == 10 || $tbgid == 38) {
                                        $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = $this->database->fetchArray($tsql);
                                        $tmaxstore = $t_sql['maxstore'] - $buildarray[ $tblevel ]['attri'];
                                        if ($tmaxstore < 800) $tmaxstore = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                        $this->database->query($q);
                                    }
                                    if ($tbgid == 11 || $tbgid == 39) {
                                        $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = $this->database->fetchArray($tsql);
                                        $tmaxcrop = $t_sql['maxcrop'] - $buildarray[ $tblevel ]['attri'];
                                        if ($tmaxcrop < 800) $tmaxcrop = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                        $this->database->query($q);
                                    }
                                    if ($tbgid == Buildings::EMBASSY) {
                                        (new VillageHelper($this->database))
                                            ->updateMaxAllyMembers(
                                                $this->database->getVillageField($data['to'], 'owner')
                                            );
                                    }
                                    if ($isoasis == 0) {
                                        (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                    }
                                    if ($isoasis == 0) {
                                        $pop = (new VillageRecalculationHelper($this->database))
                                            ->recountVillage($data['to']);
                                        if ($pop == '0' && $can_destroy == 1) {
                                            $village_destroyed = 1;
                                        }
                                    }
                                }
                                elseif ($battlepart[4] == 0) {
                                    $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
                    <img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> " . $this->procResType($tbgid, $can_destroy, $isoasis) . " was not damaged.</td></tr></tbody>";
                                }
                                else {
                                    $demolish = $battlepart[4] / $battlepart[3];
                                    $totallvl = round(sqrt(pow(($tblevel + 0.5), 2) - (($battlepart[4] / 2) * 8)));
                                    if ($tblevel == $totallvl)
                                        $info_cata = " was not damaged.";
                                    else {
                                        $info_cata = " damaged from level <b>" . $tblevel . "</b> to level <b>" . $totallvl . "</b>.";
                                        $buildarray = $GLOBALS[ "bid" . $tbgid ];
                                        if ($tbgid == 10 || $tbgid == 38) {
                                            $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                            $t_sql = $this->database->fetchArray($tsql);
                                            $tmaxstore = $t_sql['maxstore'] + $buildarray[ $totallvl ]['attri'] - $buildarray[ $tblevel ]['attri'];
                                            if ($tmaxstore < 800) $tmaxstore = 800;
                                            $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                            $this->database->query($q);
                                        }
                                        if ($tbgid == 11 || $tbgid == 39) {
                                            $tsql = $this->database->query("select `maxstore`,`maxcrop` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                            $t_sql = $this->database->fetchArray($tsql);
                                            $tmaxcrop = $t_sql['maxcrop'] + $buildarray[ $totallvl ]['attri'] - $buildarray[ $tblevel ]['attri'];
                                            if ($tmaxcrop < 800) $tmaxcrop = 800;
                                            $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                            $this->database->query($q);
                                        }
                                        if ($tbgid == Buildings::EMBASSY) {
                                            (new VillageHelper($this->database))
                                                ->updateMaxAllyMembers(
                                                    $this->database->getVillageField($data['to'], 'owner')
                                                );
                                        }
                                        if ($isoasis == 0) {
                                            (new VillageRecalculationHelper($this->database))
                                                ->recountVillage($data['to']);
                                        }
                                    }
                                    
                                    $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
                    <img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> " . $this->procResType($tbgid, $can_destroy, $isoasis) . $info_cata . "</td></tr></tbody>";
                                    $this->database->setVillageLevel($data['to'], "f" . $tbid . "", $totallvl);
                                }
                            }
                        }
                    }
                }
                
                //chiefing village
                //there are senators
                if (($data['t9'] - $dead9 - $traped9) > 0) {
                    if ($type == '3') {
                        
                        $palacelevel = $this->database->getResourceLevel($from['wref']);
                        for ($i = 1; $i <= 40; $i++) {
                            if ($palacelevel[ 'f' . $i . 't' ] == 26) {
                                $plevel = $i;
                            }
                            elseif ($palacelevel[ 'f' . $i . 't' ] == 25) {
                                $plevel = $i;
                            }
                        }
                        if ($palacelevel[ 'f' . $plevel . 't' ] == 26) {
                            if ($palacelevel[ 'f' . $plevel ] < 10) {
                                $canconquer = 0;
                            }
                            elseif ($palacelevel[ 'f' . $plevel ] < 15) {
                                $canconquer = 1;
                            }
                            elseif ($palacelevel[ 'f' . $plevel ] < 20) {
                                $canconquer = 2;
                            }
                            else {
                                $canconquer = 3;
                            }
                        }
                        elseif ($palacelevel[ 'f' . $plevel . 't' ] == 25) {
                            if ($palacelevel[ 'f' . $plevel ] < 10) {
                                $canconquer = 0;
                            }
                            elseif ($palacelevel[ 'f' . $plevel ] < 20) {
                                $canconquer = 1;
                            }
                            else {
                                $canconquer = 2;
                            }
                        }
                        
                        $exp1 = $this->database->getVillageField($from['wref'], 'exp1');
                        $exp2 = $this->database->getVillageField($from['wref'], 'exp2');
                        $exp3 = $this->database->getVillageField($from['wref'], 'exp3');
                        if ($exp1 == 0) {
                            $villexp = 0;
                        }
                        elseif ($exp2 == 0) {
                            $villexp = 1;
                        }
                        elseif ($exp3 == 0) {
                            $villexp = 2;
                        }
                        else {
                            $villexp = 3;
                        }
                        $varray = $this->database->getProfileVillages($to['owner']);
                        $varray1 = count($this->database->getProfileVillages($from['owner']));
                        $mode = CP;
                        $cp_mode = $GLOBALS[ 'cp' . $mode ];
                        $need_cps = $cp_mode[ $varray1 + 1 ];
                        $user_cps = $this->database->getUserField($from['owner'], "cp", 0);
                        //see if last village, or village head
                        if ($user_cps >= $need_cps) {
                            if (count($varray) != '1' AND $to['capital'] != '1' AND $villexp < $canconquer) {
                                if ($to['owner'] != 3 OR $to['name'] != 'WW Buildingplan') {
                                    //if there is no Palace/Residence
                                    for ($i = 18; $i < 39; $i++) {
                                        if ($this->database->getFieldLevel($data['to'], "" . $i . "t") == 25 or $this->database->getFieldLevel($data['to'], "" . $i . "t") == 26) {
                                            $nochiefing = '1';
                                            $info_chief = "" . $chief_pic . ",The Palace/Residence isn\'t destroyed!";
                                        }
                                    }
                                    if (!isset($nochiefing)) {
                                        //$info_chief = "".$chief_pic.",You don't have enought CP to chief a village.";
                                        if ($this->getTypeLevel(35, $data['from']) == 0) {
                                            for ($i = 0; $i < ($data['t9'] - $dead9); $i++) {
                                                if ($owntribe == 1) {
                                                    $rand += rand(20, 30);
                                                }
                                                else {
                                                    $rand += rand(20, 25);
                                                }
                                            }
                                        }
                                        else {
                                            for ($i = 0; $i < ($data['t9'] - $dead9); $i++) {
                                                $rand += rand(5, 15);
                                            }
                                        }
                                        //loyalty is more than 0
                                        if (($toF['loyalty'] - $rand) > 0) {
                                            $info_chief = "" . $chief_pic . ",The loyalty was lowered from <b>" . floor($toF['loyalty']) . "</b> to <b>" . floor($toF['loyalty'] - $rand) . "</b>.";
                                            $this->database->setVillageField($data['to'], loyalty, ($toF['loyalty'] - $rand));
                                        }
                                        else {
                                            //you took over the village
                                            $villname = addslashes($this->database->getVillageField($data['to'], "name"));
                                            $artifact = $this->database->getOwnArtefactInfo($data['to']);
                                            $info_chief = "" . $chief_pic . ",Inhabitants of " . $villname . " village decided to join your empire.";
                                            if ($artifact['vref'] == $data['to']) {
                                                $this->database->claimArtefact($data['to'], $data['to'], $this->database->getVillageField($data['from'], "owner"));
                                            }
                                            $this->database->setVillageField($data['to'], loyalty, 0);
                                            $this->database->setVillageField($data['to'], owner, $this->database->getVillageField($data['from'], "owner"));
                                            //delete upgrades in armory and blacksmith
                                            $q = "DELETE FROM " . TB_PREFIX . "abdata WHERE vref = " . $data['to'] . "";
                                            $this->database->query($q);
                                            $this->database->addABTech($data['to']);
                                            //delete researches in academy
                                            $q = "DELETE FROM " . TB_PREFIX . "tdata WHERE vref = " . $data['to'] . "";
                                            $this->database->query($q);
                                            $this->database->addTech($data['to']);
                                            //delete reinforcement
                                            $q = "DELETE FROM " . TB_PREFIX . "enforcement WHERE `from` = " . $data['to'] . "";
                                            $this->database->query($q);
                                            // check buildings
                                            $pop1 = $this->database->getVillageField($data['from'], "pop");
                                            $pop2 = $this->database->getVillageField($data['to'], "pop");
                                            if ($pop1 > $pop2) {
                                                $buildlevel = $this->database->getResourceLevel($data['to']);
                                                for ($i = 1; $i <= 39; $i++) {
                                                    if ($buildlevel[ 'f' . $i ] != 0) {
                                                        if ($buildlevel[ 'f' . $i . "t" ] != 35 && $buildlevel[ 'f' . $i . "t" ] != 36 && $buildlevel[ 'f' . $i . "t" ] != 41) {
                                                            $leveldown = $buildlevel[ 'f' . $i ] - 1;
                                                            $this->database->setVillageLevel($data['to'], "f" . $i, $leveldown);
                                                        }
                                                        else {
                                                            $this->database->setVillageLevel($data['to'], "f" . $i, 0);
                                                            $this->database->setVillageLevel($data['to'], "f" . $i . "t", 0);
                                                        }
                                                    }
                                                }
                                                if ($buildlevel['f99'] != 0) {
                                                    $leveldown = $buildlevel['f99'] - 1;
                                                    $this->database->setVillageLevel($data['to'], "f99", $leveldown);
                                                }
                                            }
                                            //destroy wall
                                            $this->database->setVillageLevel($data['to'], "f40", 0);
                                            $this->database->setVillageLevel($data['to'], "f40t", 0);
                                            $this->database->clearExpansionSlot($data['to']);
                                            
                                            $exp1 = $this->database->getVillageField($data['from'], 'exp1');
                                            $exp2 = $this->database->getVillageField($data['from'], 'exp2');
                                            $exp3 = $this->database->getVillageField($data['from'], 'exp3');
                                            
                                            if ($exp1 == 0) {
                                                $exp = 'exp1';
                                                $value = $data['to'];
                                            }
                                            elseif ($exp2 == 0) {
                                                $exp = 'exp2';
                                                $value = $data['to'];
                                            }
                                            else {
                                                $exp = 'exp3';
                                                $value = $data['to'];
                                            }
                                            $this->database->setVillageField($data['from'], $exp, $value);
                                            //remove oasis related to village
                                            $this->units->returnTroops($data['to'], 1);
                                            $chiefing_village = 1;
                                        }
                                    }
                                }
                                else {
                                    $info_chief = "" . $chief_pic . ",You cant take over this village.";
                                }
                            }
                            else {
                                $info_chief = "" . $chief_pic . ",You cant take over this village.";
                            }
                        }
                        else {
                            $info_chief = "" . $chief_pic . ",Not enough culture points.";
                        }
                        unset($plevel);
                    }
                    else {
                        $info_chief = "" . $chief_pic . ",Could not reduce cultural points during raid";
                    }
                }
                
                if (($data['t11'] - $dead11 - $traped11) > 0) { //hero
                    if ($heroxp == 0) {
                        $xp = "";
                        $info_hero = $hero_pic . ",Your hero had nothing to kill therefore gains no XP at all";
                    }
                    else {
                        $xp = " and gained " . $heroxp . " XP from the battle";
                        $info_hero = $hero_pic . ",Your hero gained " . $heroxp . " XP";
                    }
                    
                    if ($isoasis != 0) { //oasis fix by ronix
                        if ($to['owner'] != $from['owner']) {
                            $troopcount = $this->database->countOasisTroops($data['to']);
                            $canqured = $this->database->canConquerOasis($data['from'], $data['to']);
                            if ($canqured == 1 && $troopcount == 0) {
                                $this->database->conquerOasis($data['from'], $data['to']);
                                $info_hero = $hero_pic . ",Your hero has conquered this oasis" . $xp;
                            }
                            else {
                                if ($canqured == 3 && $troopcount == 0) {
                                    if ($type == '3') {
                                        $Oloyaltybefore = intval($to['loyalty']);
                                        //$this->database->modifyOasisLoyalty($data['to']);
                                        //$OasisInfo = $this->database->getOasisInfo($data['to']);
                                        $Oloyaltynow = intval($this->database->modifyOasisLoyalty($data['to']));//intval($OasisInfo['loyalty']);
                                        $info_hero = $hero_pic . ",Your hero has reduced oasis loyalty to " . $Oloyaltynow . " from " . $Oloyaltybefore . $xp;
                                    }
                                    else {
                                        $info_hero = $hero_pic . ",Could not reduce loyalty during raid" . $xp;
                                    }
                                }
                            }
                        }
                    }
                    else {
                        if ($heroxp == 0) {
                            $xp = " no XP from the battle";
                        }
                        else {
                            $xp = " gained " . $heroxp . " XP from the battle";
                        }
                        $artifact = $this->database->getOwnArtefactInfo($data['to']);
                        if (!empty($artifact)) {
                            if ($type == '3') {
                                if ($this->database->canClaimArtifact($data['from'], $artifact['vref'], $artifact['size'], $artifact['type'])) {
                                    $this->database->claimArtefact($data['from'], $data['to'], $this->database->getVillageField($data['from'], "owner"));
                                    $info_hero = $hero_pic . ",Your hero is carrying home an artefact" . $xp;
                                }
                                else {
                                    $info_hero = $hero_pic . "," . $this->form->getError("error") . $xp;
                                }
                            }
                            else {
                                $info_hero = $hero_pic . ",Your hero could not claim the artefact during raid" . $xp;
                            }
                        }
                    }
                }
                elseif ($data['t11'] > 0) {
                    if ($heroxp == 0) {
                        $xp = "";
                    }
                    else {
                        $xp = " but gained " . $heroxp . " XP from the battle";
                    }
                    if ($traped11 > 0) {
                        $info_hero = $hero_pic . ",Your hero was trapped" . $xp;
                    }
                    else $info_hero = $hero_pic . ",Your hero died" . $xp;
                }
                if ($DefenderID == 0) {
                    $natar = 0;
                }

                if ($scout) {
                    if ($data['spy'] == 1) {
                        $info_spy = "" . $spy_pic . ",<div class=\"res\"><img class=\"r1\" src=\"img/x.gif\" alt=\"Lumber\" title=\"Lumber\" />" . round($totwood) . " |
                 <img class=\"r2\" src=\"img/x.gif\" alt=\"Clay\" title=\"Clay\" />" . round($totclay) . " |
                 <img class=\"r3\" src=\"img/x.gif\" alt=\"Iron\" title=\"Iron\" />" . round($totiron) . " |
                 <img class=\"r4\" src=\"img/x.gif\" alt=\"Crop\" title=\"Crop\" />" . round($totcrop) . "</div>
                 <div class=\"carry\"><img class=\"car\" src=\"img/x.gif\" alt=\"carry\" title=\"carry\" />Total Resources : " . round($totwood + $totclay + $totiron + $totcrop) . "</div>
    ";
                    }
                    elseif ($data['spy'] == 2) {
                        if ($isoasis == 0) {
                            $resarray = $this->database->getResourceLevel($data['to']);
                            
                            $crannylevel = 0;
                            $rplevel = 0;
                            $walllevel = 0;
                            for ($j = 19; $j <= 40; $j++) {
                                if ($resarray[ 'f' . $j . 't' ] == 25 || $resarray[ 'f' . $j . 't' ] == 26) {
                                    
                                    $rplevel = $this->database->getFieldLevel($data['to'], $j);
                                }
                            }
                            for ($j = 19; $j <= 40; $j++) {
                                if ($resarray[ 'f' . $j . 't' ] == 31 || $resarray[ 'f' . $j . 't' ] == 32 || $resarray[ 'f' . $j . 't' ] == 33) {
                                    
                                    $walllevel = $this->database->getFieldLevel($data['to'], $j);
                                }
                            }
                            for ($j = 19; $j <= 40; $j++) {
                                if ($resarray[ 'f' . $j . 't' ] == 23) {
                                    
                                    $crannylevel = $this->database->getFieldLevel($data['to'], $j);
                                }
                            }
                        }
                        else {
                            $crannylevel = 0;
                            $walllevel = 0;
                            $rplevel = 0;
                        }
                        
                        $palaceimg = "<img src=\"" . GP_LOCATE . "img/g/g26.gif\" height=\"20\" width=\"15\" alt=\"Palace\" title=\"Palace\" />";
                        $crannyimg = "<img src=\"" . GP_LOCATE . "img/g/g23.gif\" height=\"20\" width=\"15\" alt=\"Cranny\" title=\"Cranny\" />";
                        $wallimg = "<img src=\"" . GP_LOCATE . "img/g/g3" . $targettribe . "Icon.gif\" height=\"20\" width=\"15\" alt=\"Wall\" title=\"Wall\" />";
                        $info_spy = "" . $spy_pic . "," . $palaceimg . " Residance/Palace Level : " . $rplevel . "

                <br>" . $crannyimg . " Cranny level: " . $crannylevel . "<br>" . $wallimg . " Wall level : " . $walllevel . "";
                    }
                    
                    $data2 = '' . $from['owner'] . ',' . $from['wref'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $unitsdead_att . ',0,0,0,0,0,' . $to['owner'] . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $targettribe . ',,,' . $rom . ',' . $unitssend_def[1] . ',' . $unitsdead_def[1] . ',' . $ger . ',' . $unitssend_def[2] . ',' . $unitsdead_def[2] . ',' . $gal . ',' . $unitssend_def[3] . ',' . $unitsdead_def[3] . ',' . $nat . ',' . $unitssend_def[4] . ',' . $unitsdead_def[4] . ',' . $natar . ',' . $unitssend_def[5] . ',' . $unitsdead_def[5] . ',' . $info_ram . ',' . $info_cat . ',' . $info_chief . ',' . $info_spy . ',,' . $data['t11'] . ',' . $dead11 . ',' . $herosend_def . ',' . $deadhero . ',' . $unitstraped_att;
                }
                else {
                    if ($village_destroyed == 1 && $can_destroy == 1) {
                        //check if village pop=0 and no info destroy
                        if (strpos($info_cat, "The village has") == 0) {
                            $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
                                          <img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> The village has been destroyed.</td></tr></tbody>";
                        }
                    }
                    $data2 = '' . $from['owner'] . ',' . $from['wref'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $unitsdead_att . ',' . $steal[0] . ',' . $steal[1] . ',' . $steal[2] . ',' . $steal[3] . ',' . $battlepart['bounty'] . ',' . $to['owner'] . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $targettribe . ',,,' . $rom . ',' . $unitssend_def[1] . ',' . $unitsdead_def[1] . ',' . $ger . ',' . $unitssend_def[2] . ',' . $unitsdead_def[2] . ',' . $gal . ',' . $unitssend_def[3] . ',' . $unitsdead_def[3] . ',' . $nat . ',' . $unitssend_def[4] . ',' . $unitsdead_def[4] . ',' . $natar . ',' . $unitssend_def[5] . ',' . $unitsdead_def[5] . ',' . $info_ram . ',' . $info_cat . ',' . $info_chief . ',' . $info_spy . ',,' . $data['t11'] . ',' . $dead11 . ',' . $herosend_def . ',' . $deadhero . ',' . $unitstraped_att;
                }
                
                // When all troops die, sends no info...send info
                $info_troop = "None of your soldiers have returned";
                $data_fail = '' . $from['owner'] . ',' . $from['wref'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $unitsdead_att . ',' . $steal[0] . ',' . $steal[1] . ',' . $steal[2] . ',' . $steal[3] . ',' . $battlepart['bounty'] . ',' . $to['owner'] . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $targettribe . ',,,' . $rom . ',' . $unitssend_deff[1] . ',' . $unitsdead_deff[1] . ',' . $ger . ',' . $unitssend_deff[2] . ',' . $unitsdead_deff[2] . ',' . $gal . ',' . $unitssend_deff[3] . ',' . $unitsdead_deff[3] . ',' . $nat . ',' . $unitssend_deff[4] . ',' . $unitsdead_deff[4] . ',' . $natar . ',' . $unitssend_deff[5] . ',' . $unitsdead_deff[5] . ',,,' . $data['t11'] . ',' . $dead11 . ',' . $unitstraped_att . ',,' . $info_ram . ',' . $info_cat . ',' . $info_chief . ',' . $info_troop . ',' . $info_hero;
                
                //Undetected and detected in here.
                if ($scout) {
                    
                    for ($i = 1; $i <= 10; $i++) {
                        if ($battlepart['casualties_attacker'][ $i ]) {
                            if ($from['owner'] == 3) {
                                $this->database->addNotice($to['owner'], $to['wref'], $targetally, 20, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                                break;
                            }
                            elseif ($unitsdead_att == $unitssend_att && $defspy) { //fix by ronix
                                $this->database->addNotice($to['owner'], $to['wref'], $targetally, 20, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                                break;
                            }
                            elseif ($defspy) { //fix by ronix
                                $this->database->addNotice($to['owner'], $to['wref'], $targetally, 21, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                                break;
                            }
                        }
                    }
                }
                else {
                    if ($type == 3 && $totalsend_att - ($totaldead_att + $totaltraped_att) > 0) {
                        $prisoners = $this->database->getPrisoners($to['wref']);
                        if (count($prisoners) > 0) {
                            $anothertroops = 0;
                            $mytroops = 0;
                            foreach ($prisoners as $prisoner) {
                                $p_owner = $this->database->getVillageField($prisoner['from'], "owner");
                                if ($p_owner == $from['owner']) {
                                    $this->database->modifyAttack2($data['ref'], 1, $prisoner['t1']);
                                    $this->database->modifyAttack2($data['ref'], 2, $prisoner['t2']);
                                    $this->database->modifyAttack2($data['ref'], 3, $prisoner['t3']);
                                    $this->database->modifyAttack2($data['ref'], 4, $prisoner['t4']);
                                    $this->database->modifyAttack2($data['ref'], 5, $prisoner['t5']);
                                    $this->database->modifyAttack2($data['ref'], 6, $prisoner['t6']);
                                    $this->database->modifyAttack2($data['ref'], 7, $prisoner['t7']);
                                    $this->database->modifyAttack2($data['ref'], 8, $prisoner['t8']);
                                    $this->database->modifyAttack2($data['ref'], 9, $prisoner['t9']);
                                    $this->database->modifyAttack2($data['ref'], 10, $prisoner['t10']);
                                    $this->database->modifyAttack2($data['ref'], 11, $prisoner['t11']);
                                    $mytroops += $prisoner['t1'] + $prisoner['t2'] + $prisoner['t3'] + $prisoner['t4'] + $prisoner['t5'] + $prisoner['t6'] + $prisoner['t7'] + $prisoner['t8'] + $prisoner['t9'] + $prisoner['t10'] + $prisoner['t11'];
                                    $this->database->deletePrisoners($prisoner['id']);
                                }
                                else {
                                    $p_alliance = $this->database->getUserField($p_owner, "alliance", 0);
                                    $friendarray = $this->database->getAllianceAlly($p_alliance, 1);
                                    $neutralarray = $this->database->getAllianceAlly($p_alliance, 2);
                                    $friend = (($friendarray[0]['alli1'] > 0 and $friendarray[0]['alli2'] > 0 and $p_alliance > 0) and ($friendarray[0]['alli1'] == $ownally or $friendarray[0]['alli2'] == $ownally) and ($ownally != $p_alliance and $ownally and $p_alliance)) ? '1' : '0';
                                    $neutral = (($neutralarray[0]['alli1'] > 0 and $neutralarray[0]['alli2'] > 0 and $p_alliance > 0) and ($neutralarray[0]['alli1'] == $ownally or $neutralarray[0]['alli2'] == $ownally) and ($ownally != $p_alliance and $ownally and $p_alliance)) ? '1' : '0';
                                    if ($p_alliance == $ownally or $friend == 1 or $neutral == 1) {
                                        $p_tribe = $this->database->getUserField($p_owner, "tribe", 0);
                                        
                                        $p_eigen = $this->database->getCoor($prisoner['wref']);
                                        $p_from = ['x' => $p_eigen['x'], 'y' => $p_eigen['y']];
                                        $p_ander = $this->database->getCoor($prisoner['from']);
                                        $p_to = ['x' => $p_ander['x'], 'y' => $p_ander['y']];
                                        $p_tribe = $this->database->getUserField($p_owner, "tribe", 0);
                                        
                                        $p_speeds = [];
                                        
                                        //find slowest unit.
                                        for ($i = 1; $i <= 10; $i++) {
                                            if ($prisoner[ 't' . $i ]) {
                                                if ($prisoner[ 't' . $i ] != '' && $prisoner[ 't' . $i ] > 0) {
                                                    if ($p_unitarray) {
                                                        reset($p_unitarray);
                                                    }
                                                    $p_unitarray = $GLOBALS[ "u" . (($p_tribe - 1) * 10 + $i) ];
                                                    $p_speeds[] = $p_unitarray['speed'];
                                                }
                                            }
                                        }
                                        
                                        if ($prisoner['t11'] > 0) {
                                            $p_qh = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $p_owner . "";
                                            $p_resulth = $this->database->query($p_qh);
                                            $p_hero_f = $this->database->fetchArray($p_resulth);
                                            $p_hero_unit = $p_hero_f['unit'];
                                            $p_speeds[] = $GLOBALS[ 'u' . $p_hero_unit ]['speed'];
                                        }
                                        
                                        $p_artefact = count($this->database->getOwnUniqueArtefactInfo2($p_owner, 2, 3, 0));
                                        $p_artefact1 = count($this->database->getOwnUniqueArtefactInfo2($prisoner['from'], 2, 1, 1));
                                        $p_artefact2 = count($this->database->getOwnUniqueArtefactInfo2($p_owner, 2, 2, 0));
                                        if ($p_artefact > 0) {
                                            $p_fastertroops = 3;
                                        }
                                        elseif ($p_artefact1 > 0) {
                                            $p_fastertroops = 2;
                                        }
                                        elseif ($p_artefact2 > 0) {
                                            $p_fastertroops = 1.5;
                                        }
                                        else {
                                            $p_fastertroops = 1;
                                        }
                                        $p_time = round($this->procDistanceTime($p_to, $p_from, min($p_speeds), 1) / $p_fastertroops);
                                        $foolartefact1 = $this->database->getFoolArtefactInfo(2, $prisoner['from'], $p_owner);
                                        if (count($foolartefact1) > 0) {
                                            foreach ($foolartefact1 as $arte) {
                                                if ($arte['bad_effect'] == 1) {
                                                    $p_time *= $arte['effect2'];
                                                }
                                                else {
                                                    $p_time /= $arte['effect2'];
                                                    $p_time = round($p_time);
                                                }
                                            }
                                        }
                                        $p_reference = $this->database->addAttack($prisoner['from'], $prisoner['t1'], $prisoner['t2'], $prisoner['t3'], $prisoner['t4'], $prisoner['t5'], $prisoner['t6'], $prisoner['t7'], $prisoner['t8'], $prisoner['t9'], $prisoner['t10'], $prisoner['t11'], 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
                                        $this->database->addMovement(MovementTypeSid::RETURNING, $prisoner['wref'], $prisoner['from'], $p_reference, microtime(true), ($p_time + microtime(true)));
                                        $anothertroops += $prisoner['t1'] + $prisoner['t2'] + $prisoner['t3'] + $prisoner['t4'] + $prisoner['t5'] + $prisoner['t6'] + $prisoner['t7'] + $prisoner['t8'] + $prisoner['t9'] + $prisoner['t10'] + $prisoner['t11'];
                                        $this->database->deletePrisoners($prisoner['id']);
                                    }
                                }
                            }
                            
                            $newtraps = round(($mytroops + $anothertroops) / 3);
                            $this->database->modifyUnit($data['to'], ["99"], [$newtraps], [0]);
                            $this->database->modifyUnit($data['to'], ["99o"], [$mytroops + $anothertroops], [0]);
                            $trapper_pic = "<img src=\"" . GP_LOCATE . "img/u/98.gif\" alt=\"Trap\" title=\"Trap\" />";
                            $p_username = $this->database->getUserField($from['owner'], "username", 0);
                            if ($mytroops > 0 && $anothertroops > 0) {
                                $info_trap = "" . $trapper_pic . " " . $p_username . " released <b>" . $mytroops . "</b> from his troops and <b>" . $anothertroops . "</b> friendly troops.";
                            }
                            elseif ($mytroops > 0) {
                                $info_trap = "" . $trapper_pic . " " . $p_username . " released <b>" . $mytroops . "</b> from his troops.";
                            }
                            elseif ($anothertroops > 0) {
                                $info_trap = "" . $trapper_pic . " " . $p_username . " released <b>" . $anothertroops . "</b> friendly troops.";
                            }
                        }
                    }
                    if ($totalsend_att - ($totaldead_att + $totaltraped_att) > 0) {
                        $info_troop = "";
                    }
                    $data2 = $data2 . ',' . addslashes($info_trap) . ',,' . $info_troop . ',' . $info_hero;

                    if ($totalsend_alldef == 0) {
                        $this->database->addNotice($to['owner'], $to['wref'], $targetally, 7, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    }
                    elseif ($totaldead_alldef == 0) {
                        $this->database->addNotice($to['owner'], $to['wref'], $targetally, 4, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    }
                    elseif ($totalsend_alldef > $totaldead_alldef) {
                        $this->database->addNotice($to['owner'], $to['wref'], $targetally, 5, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    }
                    elseif ($totalsend_alldef == $totaldead_alldef) {
                        $this->database->addNotice($to['owner'], $to['wref'], $targetally, 6, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    }
                }

                //to here
                // If the dead units not equal the ammount sent they will return and report
                if ($totalsend_att - ($totaldead_att + $totaltraped_att) > 0) {
                    $artefact = count($this->database->getOwnUniqueArtefactInfo2($from['owner'], 2, 3, 0));
                    $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($from['wref'], 2, 1, 1));
                    $artefact2 = count($this->database->getOwnUniqueArtefactInfo2($from['owner'], 2, 2, 0));
                    if ($artefact > 0) {
                        $fastertroops = 3;
                    }
                    elseif ($artefact1 > 0) {
                        $fastertroops = 2;
                    }
                    elseif ($artefact2 > 0) {
                        $fastertroops = 1.5;
                    }
                    else {
                        $fastertroops = 1;
                    }
                    $endtime = round($this->procDistanceTime($from, $to, min($speeds), 1) / $fastertroops);
                    $foolartefact2 = $this->database->getFoolArtefactInfo(2, $from['wref'], $from['owner']);
                    if (count($foolartefact2) > 0) {
                        foreach ($foolartefact2 as $arte) {
                            if ($arte['bad_effect'] == 1) {
                                $endtime *= $arte['effect2'];
                            }
                            else {
                                $endtime /= $arte['effect2'];
                                $endtime = round($endtime);
                            }
                        }
                    }
                    $endtime += $AttackArrivalTime;
                    if ($type == 1) {
                        if ($from['owner'] == 3) { //fix natar report by ronix
                            $this->database->addNotice($to['owner'], $to['wref'], $targetally, 20, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                        }
                        elseif ($totaldead_att == 0 && $totaltraped_att == 0) {
                            $this->database->addNotice($from['owner'], $to['wref'], $ownally, 18, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                        }
                        else {
                            $this->database->addNotice($from['owner'], $to['wref'], $ownally, 21, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                        }
                    }
                    else {
                        if ($totaldead_att == 0 && $totaltraped_att == 0) {
                            $this->database->addNotice($from['owner'], $to['wref'], $ownally, 1, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                        }
                        else {
                            $this->database->addNotice($from['owner'], $to['wref'], $ownally, 2, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                        }
                    }
                    
                    $this->database->setMovementProc($data['moveid']);
                    if ($chiefing_village != 1) {
                        $this->database->addMovement(MovementTypeSid::RETURNING, $DefenderWref, $AttackerWref, $data['ref'], $AttackArrivalTime, $endtime);
                        
                        // send the bounty on type 6.
                        if ($type !== 1) {
                            
                            $reference = $this->database->sendResource($steal[0], $steal[1], $steal[2], $steal[3], 0, 0);
                            if ($isoasis == 0) {
                                $this->database->modifyResource($DefenderWref, $steal[0], $steal[1], $steal[2], $steal[3], 0);
                            }
                            else {
                                $this->database->modifyOasisResource($DefenderWref, $steal[0], $steal[1], $steal[2], $steal[3], 0);
                            }
                            $this->database->addMovement(MovementTypeSid::CHEF_TAKEN, $DefenderWref, $AttackerWref, $reference, $AttackArrivalTime, $endtime, 1, 0, 0, 0, 0, $data['ref']);
                            $totalstolengain = $steal[0] + $steal[1] + $steal[2] + $steal[3];
                            $totalstolentaken = ($totalstolentaken - ($steal[0] + $steal[1] + $steal[2] + $steal[3]));
                            $this->database->modifyPoints($from['owner'], 'RR', $totalstolengain);
                            $this->database->modifyPoints($to['owner'], 'RR', $totalstolentaken);
                            $this->database->modifyPointsAlly($targetally, 'RR', $totalstolentaken);
                            $this->database->modifyPointsAlly($ownally, 'RR', $totalstolengain);
                        }
                    }
                    else { //fix by ronix if only 1 chief left to conqured - don't add with zero enforces
                        if ($totalsend_att - ($totaldead_att + $totaltraped_att) > 1) {
                            $this->database->addEnforce2($data, $owntribe, $troopsdead1, $troopsdead2, $troopsdead3, $troopsdead4, $troopsdead5, $troopsdead6, $troopsdead7, $troopsdead8, $troopsdead9, $troopsdead10, $troopsdead11);
                        }
                    }
                }
                else //else they die and don't return or report.
                {
                    $this->database->setMovementProc($data['moveid']);
                    if ($type == 1) {
                        $this->database->addNotice($from['owner'], $to['wref'], $ownally, 19, addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data_fail, $AttackArrivalTime);
                    }
                    else {
                        $this->database->addNotice($from['owner'], $to['wref'], $ownally, 3, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data_fail, $AttackArrivalTime);
                    }
                }

                if ($type == 3 or $type == 4) {
                    $this->database->addGeneralAttack($totalattackdead);
                }
                if ($village_destroyed == 1) {
                    if ($can_destroy == 1) {
                        (new VillageHelper($this->database))
                            ->DelVillage($data['to'], $this->units);
                    }
                }
            }
            else {
                //units attack string for battleraport
                $unitssend_att1 = '' . $data['t1'] . ',' . $data['t2'] . ',' . $data['t3'] . ',' . $data['t4'] . ',' . $data['t5'] . ',' . $data['t6'] . ',' . $data['t7'] . ',' . $data['t8'] . ',' . $data['t9'] . ',' . $data['t10'] . '';
                $herosend_att = $data['t11'];
                $unitssend_att = $unitssend_att1 . ',' . $herosend_att;
                
                $speeds = [];
                
                //find slowest unit.
                for ($i = 1; $i <= 10; $i++) {
                    if ($data[ 't' . $i ] > 0) {
                        if ($unitarray) {
                            reset($unitarray);
                        }
                        $unitarray = $GLOBALS[ "u" . (($owntribe - 1) * 10 + $i) ];
                        $speeds[] = $unitarray['speed'];
                    }
                }
                if ($herosend_att > 0) {
                    $qh = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $from['owner'] . "";
                    $resulth = $this->database->query($qh);
                    $hero_f = $this->database->fetchArray($resulth);
                    $hero_unit = $hero_f['unit'];
                    $speeds[] = $GLOBALS[ 'u' . $hero_unit ]['speed'];
                }
                $artefact = count($this->database->getOwnUniqueArtefactInfo2($from['owner'], 2, 3, 0));
                $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($from['vref'], 2, 1, 1));
                $artefact2 = count($this->database->getOwnUniqueArtefactInfo2($from['owner'], 2, 2, 0));
                if ($artefact > 0) {
                    $fastertroops = 3;
                }
                elseif ($artefact1 > 0) {
                    $fastertroops = 2;
                }
                elseif ($artefact2 > 0) {
                    $fastertroops = 1.5;
                }
                else {
                    $fastertroops = 1;
                }
                $endtime = round($this->procDistanceTime($from, $to, min($speeds), 1) / $fastertroops);
                $foolartefact3 = $this->database->getFoolArtefactInfo(2, $from['wref'], $from['owner']);
                if (count($foolartefact3) > 0) {
                    foreach ($foolartefact3 as $arte) {
                        if ($arte['bad_effect'] == 1) {
                            $endtime *= $arte['effect2'];
                        }
                        else {
                            $endtime /= $arte['effect2'];
                            $endtime = round($endtime);
                        }
                    }
                }
                $endtime += $AttackArrivalTime;
                //$endtime += microtime(true);
                $this->database->setMovementProc($data['moveid']);
                $this->database->addMovement(MovementTypeSid::RETURNING, $to['wref'], $from['wref'], $data['ref'], $AttackArrivalTime, $endtime);
                $peace = PEACE;
                $data2 = '' . $from['owner'] . ',' . $from['wref'] . ',' . $to['owner'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $peace . '';
                $this->database
                    ->addNotice($from['owner'], $to['wref'], $ownally, 22, addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, time());
                $this->database
                    ->addNotice($to['owner'], $to['wref'], $targetally, 22, addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, time());
            }

            //check if not natar tribe
            $getvillage = $this->database->getVillage($to['wref']);
            if ($getvillage['owner'] != 3) {
                $crop = $this->database->getCropProdstarv($to['wref']);
                $unitarrays = $this->getAllUnits($to['wref']);
                $village_upkeep = $getvillage['pop'] + $this->getUpkeep($unitarrays, 0);
                $starv = $getvillage['starv'];
                if ($crop < $village_upkeep) {
                    // add starv data
                    $this->database->setVillageField($to['wref'], 'starv', $village_upkeep);
                    if ($starv == 0)
                        $this->database->setVillageField($to['wref'], 'starvupdate', time());
                }
                unset($crop, $unitarrays, $getvillage, $village_upkeep);
            }
            
            #################################################
            ################FIXED BY SONGER################
            #################################################
            
            ################################################################################
            ##############ISUE: Lag, fixed3####################################################
            #### PHP.NET manual: unset() destroy more than one variable unset($foo1, $foo2, $foo3);######
            ################################################################################
            $data_num++;
            unset(
                $Attacker
                , $Defender
                , $enforce
                , $unitssend_att
                , $unitssend_def
                , $battlepart
                , $unitlist
                , $unitsdead_def
                , $dead
                , $steal
                , $from
                , $data
                , $data2
                , $to
                , $artifact
                , $artifactBig
                , $canclaim
                , $data_fail
                , $owntribe
                , $unitsdead_att
                , $herosend_def
                , $deadhero
                , $heroxp
                , $AttackerID
                , $DefenderID
                , $totalsend_att
                , $totalsend_alldef
                , $totaldead_att
                , $totaltraped_att
                , $totaldead_def
                , $unitsdead_att_check
                , $totalattackdead
                , $enforce1
                , $defheroowner
                , $enforceowner
                , $defheroxp
                , $reinfheroxp
                , $AttackerWref
                , $DefenderWref
                , $troopsdead1
                , $troopsdead2
                , $troopsdead3
                , $troopsdead4
                , $troopsdead5
                , $troopsdead6
                , $troopsdead7
                , $troopsdead8
                , $troopsdead9
                , $troopsdead10
                , $troopsdead11
                , $DefenderUnit);
            #################################################
            
        }
        
        return $reload ?? false;
    }

    private function sendTroopsBack($post)
    {
        $enforce=$this->database->getEnforceArray($post['ckey'],0);
        $to = $this->database->getVillage($enforce['from']);
        $Gtribe = "";
            if ($this->database->getUserField($to['owner'],'tribe',0) == '2'){ $Gtribe = "1"; } else if ($this->database->getUserField($to['owner'],'tribe',0) == '3'){ $Gtribe = "2"; } else if ($this->database->getUserField($to['owner'],'tribe',0) == '4'){ $Gtribe = "3"; }else if ($this->database->getUserField($to['owner'],'tribe',0) == '5'){ $Gtribe = "4"; }

                    for($i=1; $i<10; $i++){
                        if(isset($post['t'.$i])){
                            if($i!=10){
                                if ($post['t'.$i] > $enforce['u'.$Gtribe.$i])
                                {
                                    $this->form->addError("error","You can't send more units than you have");
                                    break;
                                }

                                if($post['t'.$i]<0)
                                {
                                    $this->form->addError("error","You can't send negative units.");
                                    break;
                                }
                            }
                        } else {
                        $post['t'.$i.'']='0';
                        }
                    }
                        if(isset($post['t11'])){
                                if ($post['t11'] > $enforce['hero'])
                                {
                                    $this->form->addError("error","You can't send more units than you have");
                                }

                                if($post['t11']<0)
                                {
                                    $this->form->addError("error","You can't send negative units.");
                                }
                        } else {
                            $post['t11']='0';
                        }

                if($this->form->returnErrors() > 0) {
                    $_SESSION['errorarray'] = $this->form->getErrors();
                    $_SESSION['valuearray'] = $_POST;
                    header("Location: a2b.php");
                } else {

                    //change units
                    $start = ($this->database->getUserField($to['owner'],'tribe',0)-1)*10+1;
                    $end = ($this->database->getUserField($to['owner'],'tribe',0)*10);

                    $j='1';
                    for($i=$start;$i<=$end;$i++){
                        $this->database->modifyEnforce($post['ckey'],$i,$post['t'.$j.''],0); $j++;
                    }

                        //get cord
                        $from = $this->database->getVillage($enforce['from']);
                        $fromcoor = $this->database->getCoor($enforce['from']);
                        $tocoor = $this->database->getCoor($enforce['vref']);
                        $fromCor = array('x'=>$tocoor['x'], 'y'=>$tocoor['y']);
                        $toCor = array('x'=>$fromcoor['x'], 'y'=>$fromcoor['y']);

                $speeds = array();

                //find slowest unit.
                for($i=1;$i<=10;$i++){
                    if (isset($post['t'.$i])){
                        if( $post['t'.$i] != '' && $post['t'.$i] > 0){
                        if($unitarray) { reset($unitarray); }
                        $unitarray = $GLOBALS["u".(($this->session->tribe-1)*10+$i)];
                        $speeds[] = $unitarray['speed'];
                    } else {
                        $post['t'.$i.'']='0';
                        }
                    } else {
                        $post['t'.$i.'']='0';
                    }
                }
                    if (isset($post['t11'])){
                        if( $post['t11'] != '' && $post['t11'] > 0){
                        $qh = "SELECT * FROM ".TB_PREFIX."hero WHERE uid = ".$from['owner']."";
                        $resulth = $this->database->query($qh);
                        $hero_f=$this->database->fetchArray($resulth);
                        $hero_unit=$hero_f['unit'];
                        $speeds[] = $GLOBALS['u'.$hero_unit]['speed'];
                    } else {
                        $post['t11']='0';
                        }
                    } else {
                        $post['t11']='0';
                    }
            $artefact = count($this->database->getOwnUniqueArtefactInfo2($from['owner'],2,3,0));
            $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($from['vref'],2,1,1));
            $artefact2 = count($this->database->getOwnUniqueArtefactInfo2($from['owner'],2,2,0));
            if($artefact > 0){
            $fastertroops = 3;
            }else if($artefact1 > 0){
            $fastertroops = 2;
            }else if($artefact2 > 0){
            $fastertroops = 1.5;
            }else{
            $fastertroops = 1;
            }
                $time = round($this->generator->procDistanceTime($fromCor,$toCor,min($speeds),1)/$fastertroops);
                $foolartefact4 = $this->database->getFoolArtefactInfo(2,$from['wref'],$from['owner']);
                if(count($foolartefact4) > 0){
                foreach($foolartefact4 as $arte){
                if($arte['bad_effect'] == 1){
                $time *= $arte['effect2'];
                }else{
                $time /= $arte['effect2'];
                $time = round($endtime);
                }
                }
                }
                $reference = $this->database->addAttack($enforce['from'],$post['t1'],$post['t2'],$post['t3'],$post['t4'],$post['t5'],$post['t6'],$post['t7'],$post['t8'],$post['t9'],$post['t10'],$post['t11'],2,0,0,0,0);
                $this->database->addMovement(MovementTypeSid::RETURNING,$this->village->wid,$enforce['from'],$reference,$AttackArrivalTime,($time+$AttackArrivalTime));
                $this->technology->checkReinf($post['ckey']);

                        header("Location: ".\App\Routes::BUILD."?id=39");

                }
    }

    private function sendreinfunitsComplete(): bool
    {
            $time = time();
            $q = "SELECT * FROM ".TB_PREFIX."movement, ".TB_PREFIX."attacks where ".TB_PREFIX."movement.ref = ".TB_PREFIX."attacks.id and ".TB_PREFIX."movement.proc = '0' and ".TB_PREFIX."movement.sort_type = '3' and ".TB_PREFIX."attacks.attack_type = '2' and endtime < $time";
            $dataarray = $this->database->query_return($q);
            foreach($dataarray as $data) {
                $isoasis = $this->database->isVillageOases($data['to']);
                if($isoasis == 0){
                    $to = $this->database->getMInfo($data['to']);
                    $toF = $this->database->getVillage($data['to']);
                    $DefenderID = $to['owner'];
                    $targettribe = $this->database->getUserField($DefenderID,"tribe",0);
                    $conqureby=0;
                }else{
                    $to = $this->database->getOMInfo($data['to']);
                    $toF = $this->database->getOasisV($data['to']);
                    $DefenderID = $to['owner'];
                    $targettribe = $this->database->getUserField($DefenderID,"tribe",0);
                    $conqureby=$toF['conqured'];                    
                }
                if($data['from']==0){
                    $DefenderID = $this->database->getVillageField($data['to'],"owner");
                    if ($this->session->uid==$AttackerID || $this->session->uid==$DefenderID) $reload=true;
                    $this->database->addEnforce($data);
                    $reinf = $this->database->getEnforce($data['to'],$data['from']);
                    $this->database->modifyEnforce($reinf['id'],31,1,1);
                    $data_fail = '0,0,4,1,0,0,0,0,0,0,0,0,0,0';
                    $this->database->addNotice($to['owner'],$to['wref'],$targetally,8,'village of the elders reinforcement '.addslashes($to['name']).'',$data_fail,$AttackArrivalTime);
                    $this->database->setMovementProc($data['moveid']);
                    if ($this->session->uid==$DefenderID) $reload=true;
                }else{
                    //set base things
                    $from = $this->database->getMInfo($data['from']);
                    $fromF = $this->database->getVillage($data['from']);
                    $AttackerID = $from['owner'];
                    $owntribe = $this->database->getUserField($AttackerID,"tribe",0);
                    
                    
                    if ($this->session->uid==$AttackerID || $this->session->uid==$DefenderID) $reload=true;

    //check to see if we're sending a hero between own villages and there's a Mansion at target village
                    $HeroTransfer=0;
                    $troopsPresent=0;
                    if($data['t11'] != 0) {
                        if($AttackerID == $DefenderID) {
                            if($this->getTypeLevel(37,$data['to']) > 0) {
                                //don't reinforce, addunit instead
                                $this->database->modifyUnit($data['to'],array("hero"),array(1),array(1));
                                $heroid = $this->database->getHero($DefenderID,1);
                                $this->database->modifyHero("wref",$data['to'],$heroid,0);
                                $HeroTransfer = 1;
                            }
                        }
                    }
                    for($i=1;$i<=10;$i++) { 
                        if($data['t'.$i]>0) {
                            $troopsPresent = 1; break; 
                        }
                    }

                    if($data['t11'] != 0 || $troopsPresent) {
                        $temphero=$data['t11'];
                        if ($HeroTransfer) $data['t11']=0;
                        //check if there is defence from town in to town
                        $check=$this->database->getEnforce($data['to'],$data['from']);
                        if (!isset($check['id'])){
                            //no:
                            $this->database->addEnforce($data);
                        } else{
                            //yes
                            $start = ($owntribe-1)*10+1;
                            $end = ($owntribe*10);
                            //add unit.
                            $j='1';
                            for($i=$start;$i<=$end;$i++){
                                $t_units.="u".$i."=u".$i." + ".$data['t'.$j].(($j > 9) ? '' : ', ');$j++;
                            }    
                            $q = "UPDATE ".TB_PREFIX."enforcement set $t_units where id =".$check['id'];
                            $this->database->query($q);
                            $this->database->modifyEnforce($check['id'],'hero',$data['t11'],1);
                        }
                        $data['t11']=$temphero;
                    }
                    //send rapport
                    $unitssend_att = ''.$data['t1'].','.$data['t2'].','.$data['t3'].','.$data['t4'].','.$data['t5'].','.$data['t6'].','.$data['t7'].','.$data['t8'].','.$data['t9'].','.$data['t10'].','.$data['t11'].'';
                    $data_fail = ''.$from['wref'].','.$from['owner'].','.$owntribe.','.$unitssend_att.'';
                    
                    
                    if($isoasis == 0){
                        $to_name=$to['name'];
                    }else{
                        $to_name="Oasis ".$this->database->getVillageField($to['conqured'],"name");
                    }
                    $this->database->addNotice($from['owner'],$from['wref'],$ownally,8,''.addslashes($from['name']).' reinforcement '.addslashes($to_name).'',$data_fail,$AttackArrivalTime);
                    if($from['owner'] != $to['owner']) {
                        $this->database->addNotice($to['owner'],$to['wref'],$targetally,8,''.addslashes($from['name']).' reinforcement '.addslashes($to_name).'',$data_fail,$AttackArrivalTime);
                    }
                    //update status
                    $this->database->setMovementProc($data['moveid']);
                }
                $crop = $this->database->getCropProdstarv($data['to']);
                $unitarrays = $this->getAllUnits($data['to']);
                $village = $this->database->getVillage($data['to']);
                $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
                $starv = $this->database->getVillageField($data['to'],"starv");
                 if ($crop < $upkeep){
                    // add starv data
                    $this->database->setVillageField($data['to'], 'starv', $upkeep);
                    if($starv==0){
                        $this->database->setVillageField($data['to'], 'starvupdate', $time);
                    }
                }
            
                //check empty reinforcement in rally point
                $e_units='';
                for ($i=1;$i<=50;$i++) {
                    $e_units.='u'.$i.'=0 AND ';
                }
                $e_units.='hero=0';
                $q="DELETE FROM ".TB_PREFIX."enforcement WHERE ".$e_units." AND (vref=".$data['to']." OR `from`=".$data['to'].")";
                $this->database->query($q);
            }
            
            return $reload ?? false;
    }

    private function returnunitsComplete() {
        $reload=false;
        $time = time();
        $q = "SELECT * FROM ".TB_PREFIX."movement, ".TB_PREFIX."attacks where ".TB_PREFIX."movement.ref = ".TB_PREFIX."attacks.id and ".TB_PREFIX."movement.proc = '0' and ".TB_PREFIX."movement.sort_type = '4' and endtime < $time";
        $dataarray = $this->database->query_return($q);

        foreach($dataarray as $data) {

        $tribe = $this->database->getUserField($this->database->getVillageField($data['to'],"owner"),"tribe",0);

        if($tribe == 1){ $u = ""; } elseif($tribe == 2){ $u = "1"; } elseif($tribe == 3){ $u = "2"; } elseif($tribe == 4){ $u = "3"; } else{ $u = "4"; }
        $this->database->modifyUnit(
                $data['to'],
                array($u."1",$u."2",$u."3",$u."4",$u."5",$u."6",$u."7",$u."8",$u."9",$tribe."0","hero"),
                array($data['t1'],$data['t2'],$data['t3'],$data['t4'],$data['t5'],$data['t6'],$data['t7'],$data['t8'],$data['t9'],$data['t10'],$data['t11']),
                array(1,1,1,1,1,1,1,1,1,1,1)
        );
        $this->database->setMovementProc($data['moveid']);
        $crop = $this->database->getCropProdstarv($data['to']);
            $unitarrays = $this->getAllUnits($data['to']);
            $village = $this->database->getVillage($data['to']);
            $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
         $starv = $this->database->getVillageField($data['to'],"starv");
              if ($crop < $upkeep){
              // add starv data
                 $this->database->setVillageField($data['to'], 'starv', $upkeep);
           if($starv==0){
                 $this->database->setVillageField($data['to'], 'starvupdate', $time);
               }
        }
         }

        // Recieve the bounty on type 6.

        $q = "SELECT * FROM ".TB_PREFIX."movement, ".TB_PREFIX."send where ".TB_PREFIX."movement.ref = ".TB_PREFIX."send.id and ".TB_PREFIX."movement.proc = 0 and sort_type = 6 and endtime < $time";
        $dataarray = $this->database->query_return($q);
        foreach($dataarray as $data) {

            if($data['wood'] >= $data['clay'] && $data['wood'] >= $data['iron'] && $data['wood'] >= $data['crop']){ $sort_type = "10"; }
            elseif($data['clay'] >= $data['wood'] && $data['clay'] >= $data['iron'] && $data['clay'] >= $data['crop']){ $sort_type = "11"; }
            elseif($data['iron'] >= $data['wood'] && $data['iron'] >= $data['clay'] && $data['iron'] >= $data['crop']){ $sort_type = "12"; }
            elseif($data['crop'] >= $data['wood'] && $data['crop'] >= $data['clay'] && $data['crop'] >= $data['iron']){ $sort_type = "13"; }

            $to = $this->database->getMInfo($data['to']);
            $from = $this->database->getMInfo($data['from']);
            $this->database->modifyResource($data['to'],$data['wood'],$data['clay'],$data['iron'],$data['crop'],1);
            //$this->database->updateVillage($data['to']);
            $this->database->setMovementProc($data['moveid']);
            $crop = $this->database->getCropProdstarv($data['to']);
                $unitarrays = $this->getAllUnits($data['to']);
                $village = $this->database->getVillage($data['to']);
                $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
             $starv = $this->database->getVillageField($data['to'],"starv");
                  if ($crop < $upkeep){
                  // add starv data
                     $this->database->setVillageField($data['to'], 'starv', $upkeep);
               if($starv==0){
                     $this->database->setVillageField($data['to'], 'starvupdate', $time);
                   }
        }
          }

        (new VillageRecalculationHelper($this->database))
            ->pruneResource();

        // Settlers

        $q = "SELECT * FROM ".TB_PREFIX."movement where ref = 0 and proc = '0' and sort_type = '4' and endtime < $time";
        $dataarray = $this->database->query_return($q);
        foreach($dataarray as $data) {

        $tribe = $this->database->getUserField($this->database->getVillageField($data['to'],"owner"),"tribe",0);

        $this->database->modifyUnit($data['to'],array($tribe."0"),array(3),array(1));
        $this->database->setMovementProc($data['moveid']);

           }
    }

    private function sendSettlersComplete(): bool
    {
        $time = microtime(true);
        $q = "SELECT * FROM ".TB_PREFIX."movement where proc = 0 and sort_type = 5 and endtime < $time";
        $dataarray = $this->database->query_return($q);
            foreach($dataarray as $data) {
                $ownerID = $this->database->getUserField($this->database->getVillageField($data['from'],"owner"),"id",0);
                if ($this->session->uid==$ownerID) $reload=true;
                $to = $this->database->getMInfo($data['from']);
                    $user = addslashes($this->database->getUserField($to['owner'],'username',0));
                    $taken = $this->database->getVillageState($data['to']);
                    if($taken != 1){
                        $this->database->setFieldTaken($data['to']);
                        $this->database->addVillage($data['to'],$to['owner'],$user,'0');
                        $this->database->addResourceFields($data['to'],$this->database->getVillageType($data['to']));
                        $this->database->addUnits($data['to']);
                        $this->database->addTech($data['to']);
                        $this->database->addABTech($data['to']);
                        $this->database->setMovementProc($data['moveid']);

                        $exp1 = $this->database->getVillageField($data['from'],'exp1');
                        $exp2 = $this->database->getVillageField($data['from'],'exp2');
                        $exp3 = $this->database->getVillageField($data['from'],'exp3');

                        if($exp1 == 0){
                            $exp = 'exp1';
                            $value = $data['to'];
                        }
                        elseif($exp2 == 0){
                            $exp = 'exp2';
                            $value = $data['to'];
                        }
                        else{
                            $exp = 'exp3';
                            $value = $data['to'];
                        }
                        $this->database->setVillageField($data['from'],$exp,$value);
                    }
                    else{
                        // here must come movement from returning settlers
                        $this->database->addMovement(MovementTypeSid::RETURNING,$data['to'],$data['from'],$data['ref'],$time,$time+($time-$data['starttime']));
                        $this->database->setMovementProc($data['moveid']);
                    }
            }

        return $reload ?? false;
    }

    private function researchComplete() {
        $time = time();
        $q = "SELECT * FROM ".TB_PREFIX."research where timestamp < $time";
        $dataarray = $this->database->query_return($q);
        foreach($dataarray as $data) {
            $sort_type = substr($data['tech'],0,1);
            switch($sort_type) {
                case "t":
                $q = "UPDATE ".TB_PREFIX."tdata set ".$data['tech']." = 1 where vref = ".$data['vref'];
                break;
                case "a":
                case "b":
                $q = "UPDATE ".TB_PREFIX."abdata set ".$data['tech']." = ".$data['tech']." + 1 where vref = ".$data['vref'];
                break;
            }
            $this->database->query($q);
            $q = "DELETE FROM ".TB_PREFIX."research where id = ".$data['id'];
            $this->database->query($q);
        }
    }

    private function updateRes($bountywid,$uid)
    {
        $bountyHelper = new BountyHelper($this->database);
        $bountyHelper->loadTown($bountywid);
        $bountyHelper->calculateProduction($bountywid,$uid);
        $bountyHelper->processProduction($bountywid);
    }

    private function updateORes($bountywid)
    {
        $bountyHelper = new BountyHelper($this->database);
        $bountyHelper->loadOasisTown($bountywid);
        $bountyHelper->calculateOasisProduction();
        $bountyHelper->processOasisProduction($bountywid);
    }

    private function getAllUnits($base)
    {
        $ownunit = $this->database->getUnit($base);

        $enforcementarray = $this->database->getEnforceVillage($base, 0);
        foreach ($enforcementarray as $enforce) {
            for ($i = 1; $i <= 50; $i++) {
                $ownunit['u' . $i] += $enforce['u' . $i];
            }
        }

        $enforceoasis = $this->database->getOasisEnforce($base, 0);
        foreach ($enforceoasis as $enforce) {
            for ($i = 1; $i <= 50; $i++) {
                $ownunit['u' . $i] += $enforce['u' . $i];
            }
        }

        $enforceoasis1 = $this->database->getOasisEnforce($base, 1);
        foreach ($enforceoasis1 as $enforce) {
            for ($i = 1; $i <= 50; $i++) {
                $ownunit['u' . $i] += $enforce['u' . $i];
            }
        }

        $movement = $this->database->getVillageMovement($base);
        if (!empty($movement)) {
            for ($i = 1; $i <= 50; $i++) {
                $ownunit['u' . $i] += $movement['u' . $i];
            }
        }

        $prisoners = $this->database->getPrisoners($base, 1);
        foreach ($prisoners as $prisoner) {
            $owner = $this->database->getVillageField($base, "owner");
            $ownertribe = $this->database->getUserField($owner, "tribe", 0);

            $start = ($ownertribe - 1) * 10 + 1;
            $end = ($ownertribe * 10);
            for ($i = $start; $i <= $end; $i++) {
                $j = $i - $start + 1;
                $ownunit['u' . $i] += $prisoner['t' . $j];
            }
            $ownunit['hero'] += $prisoner['t11'];
        }

        return $ownunit;
    }

    public function getUpkeep($array, $type, $vid = 0, $prisoners = 0)
    {
        if ($vid == 0) {
            $vid = $this->village->wid;
        }

        return (new VillageHelper($this->database))
            ->getAllUnitsUpkeep($array, $type, $vid, $prisoners);
    }

    private function trainingComplete()
    {
        $time = time();
        $trainlist = $this->database->getTrainingList();
        if (count($trainlist) > 0) {
            foreach ($trainlist as $train) {
                $timepast = $train['timestamp2'] - $time;
                $pop = $train['pop'];
                if ($timepast <= 0 && $train['amt'] > 0) {
                    $timepast2 = $time - $train['timestamp2'];
                    $trained = 1;
                    while ($timepast2 >= $train['eachtime']) {
                        $timepast2 -= $train['eachtime'];
                        $trained += 1;
                    }
                    if ($trained > $train['amt']) {
                        $trained = $train['amt'];
                    }
                    if ($train['unit'] > 60 && $train['unit'] != 99) {
                        $this->database->modifyUnit($train['vref'], [$train['unit'] - 60], [$trained], [1]);
                    } else {
                        $this->database->modifyUnit($train['vref'], [$train['unit']], [$trained], [1]);
                    }
                    $this->database->updateTraining($train['id'], $trained, $trained * $train['eachtime']);
                }
                if ($train['amt'] == 0) {
                    $this->database->trainUnit($train['id'], 0, 0, 0, 0, 1, 1);
                }
                $crop = $this->database->getCropProdstarv($train['vref']);
                $unitarrays = $this->getAllUnits($train['vref']);
                $village = $this->database->getVillage($train['vref']);
                $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
                $starv = $this->database->getVillageField($train['vref'], "starv");
                if ($crop < $upkeep) {
                    // add starv data
                    $this->database->setVillageField($train['vref'], 'starv', $upkeep);
                    if ($starv == 0) {
                        $this->database->setVillageField($train['vref'], 'starvupdate', $time);
                    }
                }
            }
        }
    }

    public function procDistanceTime($coor,$thiscoor,$ref,$mode) {
        $bid14 = GlobalVariablesHelper::getBuilding(Buildings::TOURNAMENT_SQUARE);
        $resarray = $this->database->getResourceLevel($this->generator->getBaseID($coor['x'],$coor['y']));
        $xdistance = ABS($thiscoor['x'] - $coor['x']);
        if($xdistance > WORLD_MAX) {
            $xdistance = (2*WORLD_MAX+1) - $xdistance;
        }
        $ydistance = ABS($thiscoor['y'] - $coor['y']);
        if($ydistance > WORLD_MAX) {
            $ydistance = (2*WORLD_MAX+1) - $ydistance;
        }
        $distance = SQRT(POW($xdistance,2)+POW($ydistance,2));
         if(!$mode) {
            if($ref == 1) {
                $speed = 16;
            }
            else if($ref == 2) {
                $speed = 12;
            }
            else if($ref == 3) {
                $speed = 24;
            }
            else if($ref == 300) {
                $speed = 5;
            }
            else {
                $speed = 1;
            }
        }
        else {
            $speed = $ref;
            if($this->getsort_typeLevel(14,$resarray) != 0 && $distance >= TS_THRESHOLD) {
                $speed = $speed * ($bid14[$this->getsort_typeLevel(14,$resarray)]['attri']/100) ;
            }
        }


        if($speed!=0){
        return round(($distance/$speed) * 3600 / INCREASE_SPEED);
        }else{
        return round($distance * 3600 / INCREASE_SPEED);
        }

    }

    private function getsort_typeLevel($tid,$resarray) {
        
        $keyholder = array();
        foreach(array_keys($resarray,$tid) as $key) {
            if(strpos($key,'t')) {
                $key = preg_replace("/[^0-9]/", '', $key);
                array_push($keyholder, $key);
            }
        }
        $element = count($keyholder);
        if($element >= 2) {
            if($tid <= 4) {
                $temparray = array();
                for($i=0;$i<=$element-1;$i++) {
                    array_push($temparray,$resarray['f'.$keyholder[$i]]);
                }
                foreach ($temparray as $key => $val) {
                    if ($val == max($temparray))
                    $target = $key;
                }
            }
            else {
                /*for($i=0;$i<=$element-1;$i++) {
                    //if($resarray['f'.$keyholder[$i]] != $this->getsort_typeMaxLevel($tid)) {
                    //    $target = $i;
                    //}
                }
                */
            }
        }
        else if($element == 1) {
            $target = 0;
        }
        else {
            return 0;
        }
        if($keyholder[$target] != "") {
            return $resarray['f'.$keyholder[$target]];
        }
        else {
            return 0;
        }
    }

    private function celebrationComplete()
    {
        $varray = $this->database->getCel();
        foreach ($varray as $vil) {
            $id = $vil['wref'];
            $type = $vil['type'];
            $user = $vil['owner'];
            if ($type == 1) {
                $cp = 500;
            } else if ($type == 2) {
                $cp = 2000;
            }

            $this->database->clearCel($id);
            $this->database->setCelCp($user, $cp);
        }
    }

    private function demolitionComplete()
    {
        $varray = $this->database->getDemolition();
        foreach ($varray as $vil) {
            if ($vil['timetofinish'] > time()) {
                continue;
            }

            $type  = $this->database->getFieldType($vil['vref'], $vil['buildnumber']);
            $level = $this->database->getFieldLevel($vil['vref'], $vil['buildnumber']);
            $buildarray = $GLOBALS["bid" . $type];

            if ($type == Buildings::WAREHOUSE || $type == Buildings::GREAT_WAREHOUSE) {
                $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`=`maxstore`-" . $buildarray[$level]['attri'] . " WHERE wref=" . $vil['vref'];
                $this->database->query($q);

                $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`=800 WHERE `maxstore`<= 800 AND wref=" . $vil['vref'];
                $this->database->query($q);
            }

            if ($type == Buildings::GRANARY || $type == Buildings::GREAT_GRANARY) {
                $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`=`maxcrop`-" . $buildarray[$level]['attri'] . " WHERE wref=" . $vil['vref'];
                $this->database->query($q);
                $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`=800 WHERE `maxcrop`<=800 AND wref=" . $vil['vref'];
                $this->database->query($q);
            }

            if ($type == Buildings::EMBASSY) {
                (new VillageHelper($this->database))
                    ->updateMaxAllyMembers(
                        $this->database->getVillageField($vil['vref'], 'owner')
                    );
            }

            if ($level == 1) {
                $clear = ",f" . $vil['buildnumber'] . "t=0";
            } else {
                $clear = "";
            }
            if ($this->village->natar == 1 && $type == 40) {
                $clear = "";
            }

            $q = "UPDATE " . TB_PREFIX . "fdata SET f" . $vil['buildnumber'] . "=" . ($level - 1) . $clear . " WHERE vref=" . $vil['vref'];
            $this->database->query($q);

            $pop = $this->getPop($type, $level - 1);
            $this->database->modifyPop($vil['vref'], $pop[0], 1);

            (new RankingHelper($this->database, $this->ranking))
                ->procClimbers($this->database->getVillageField($vil['vref'], 'owner'));

            $this->database->delDemolition($vil['vref']);
        }
    }

    private function updateHero()
    {
        $hero_levels = GlobalVariablesHelper::getHeroLevels();

        $allHeroesList = $this->database->getHero();
        foreach ($allHeroesList as $hdata) {
            // regeneration
            if ((time() - $hdata['lastupdate']) >= 1) {
                if ($hdata['health'] < 100 and $hdata['health'] > 0) {
                    if (SPEED <= 10) {
                        $speed = SPEED;
                    } else if (SPEED <= 100) {
                        $speed = ceil(SPEED / 10);
                    } else {
                        $speed = ceil(SPEED / 100);
                    }

                    $newHealth = $hdata['health'] + $hdata['regeneration'] * 5 * $speed / 86400 * (time() - $hdata['lastupdate']);
                    if ($newHealth > 100) {
                        $newHealth = 100;
                    }

                    $this->database->modifyHero("health", $newHealth, $hdata['heroid']);
                    $this->database->modifyHero("lastupdate", time(), $hdata['heroid']);
                }
            }

            // levelup
            $herolevel = $hdata['level'];
            for ($i = $herolevel + 1; $i < 100; $i++) {
                if ($hdata['experience'] >= $hero_levels[$i]) {
                    $this->database->query("UPDATE " . TB_PREFIX . "hero SET level = $i WHERE heroid = '{$hdata['heroid']}'");
                    if ($i < 99) {
                        $this->database->query("UPDATE " . TB_PREFIX . "hero SET points = points + 5 WHERE heroid = '{$hdata['heroid']}'");
                    }
                }
            }

            // revive
            $villunits = $this->database->getUnit($hdata['wref']);
            if ($villunits['hero'] == 0 && $hdata['trainingtime'] < time() && $hdata['inrevive'] == 1) {
                $this->database->query("UPDATE " . TB_PREFIX . "units SET hero = 1 WHERE vref = {$hdata['wref']}");
                $this->database->query("UPDATE " . TB_PREFIX . "hero SET `dead` = '0', `inrevive` = '0', `health` = '100', `lastupdate` = {$hdata['trainingtime']} WHERE `uid` = '{$hdata['uid']}'");
            }

            // train (create)
            if ($villunits['hero'] == 0 && $hdata['trainingtime'] < time() && $hdata['intraining'] == 1) {
                $this->database->query("UPDATE " . TB_PREFIX . "units SET hero = 1 WHERE vref = {$hdata['wref']}");
                $this->database->query("UPDATE " . TB_PREFIX . "hero SET `intraining` = '0', `lastupdate` = {$hdata['trainingtime']} WHERE `uid` = '{$hdata['uid']}'");
            }
        }
    }

 // by SlimShady95, aka Manuel Mannhardt < manuel_mannhardt@web.de > UPDATED FROM songeriux < haroldas.snei@gmail.com >
    private function updateStore()
    {
        $bid10 = GlobalVariablesHelper::getBuilding(Buildings::WAREHOUSE);
        $bid11 = GlobalVariablesHelper::getBuilding(Buildings::GRANARY);
        $bid38 = GlobalVariablesHelper::getBuilding(Buildings::GREAT_WAREHOUSE);
        $bid39 = GlobalVariablesHelper::getBuilding(Buildings::GREAT_GRANARY);

        $result = $this->database->query('SELECT * FROM ' . TB_PREFIX . 'fdata');
        while ($row = $this->database->fetchAssoc($result))
        {
            $ress = $crop = 0;
            for ($i = 19; $i < 40; ++$i)
            {
                if ($row['f' . $i . 't'] == 10)
                {
                    $ress += $bid10[$row['f' . $i]]['attri'] * STORAGE_MULTIPLIER;
                }

                if ($row['f' . $i . 't'] == 38)
                {
                    $ress += $bid38[$row['f' . $i]]['attri'] * STORAGE_MULTIPLIER;
                }



                if ($row['f' . $i . 't'] == 11)
                {
                    $crop += $bid11[$row['f' . $i]]['attri'] * STORAGE_MULTIPLIER;
                }

                if ($row['f' . $i . 't'] == 39)
                {
                    $crop += $bid39[$row['f' . $i]]['attri'] * STORAGE_MULTIPLIER;
                }
            }

            if ($ress == 0)
            {
                $ress = 800 * STORAGE_MULTIPLIER;
            }

            if ($crop == 0)
            {
                $crop = 800 * STORAGE_MULTIPLIER;
            }

            $this->database->query('UPDATE `' . TB_PREFIX . 'vdata` SET `maxstore` = ' . $ress . ', `maxcrop` = ' . $crop . ' WHERE `wref` = ' . $row['vref']);
        }
    }

    private function checkInvitedPlayes() {
        
        $q = "SELECT * FROM ".TB_PREFIX."users WHERE invited != 0";
        $array = $this->database->query_return($q);
        foreach($array as $user) {
        $numusers = $this->database->query("SELECT * FROM ".TB_PREFIX."users WHERE id = ".$user['invited']);
        if($this->database->numRows($numusers) > 0){
        $varray = count($this->database->getProfileVillages($user['id']));
        if($varray > 1){
        $usergold = $this->database->getUserField($user['invited'],"gold",0);
        $gold = $usergold+50;
        $this->database->updateUserField($user['invited'],"gold",$gold,1);
        $this->database->updateUserField($user['id'],"invited",0,1);
        }
        }
        }
    }

    private function updateGeneralAttack()
    {
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "general WHERE shown = 1";
        $array = $this->database->query($q)->fetch_all(\MYSQLI_ASSOC);
        foreach ($array as $general) {
            if (time() - (86400 * 8) > $general['time']) {
                $this->database->query("UPDATE " . TB_PREFIX . "general SET shown = 0 WHERE id = " . $general['id'] . "");
            }
        }
    }

    private function MasterBuilder()
    {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata WHERE master = 1";
        foreach ($this->database->query($q)->fetch_all(\MYSQLI_ASSOC) as $master) {
            $owner = $this->database->getVillageField($master['wid'], 'owner');
            $tribe = $this->database->getUserField($owner, 'tribe', 0);

            $villwood = $this->database->getVillageField($master['wid'], 'wood');
            $villclay = $this->database->getVillageField($master['wid'], 'clay');
            $villiron = $this->database->getVillageField($master['wid'], 'iron');
            $villcrop = $this->database->getVillageField($master['wid'], 'crop');

            $type = $master['type'];
            $level = $master['level'];

            $buildarray = $GLOBALS["bid" . $type];
            $buildwood = $buildarray[$level]['wood'];
            $buildclay = $buildarray[$level]['clay'];
            $buildiron = $buildarray[$level]['iron'];
            $buildcrop = $buildarray[$level]['crop'];

            $ww = count($this->database->getBuildingByType($master['wid'], 40));

            if ($tribe == 1) {
                if ($master['field'] < 19) {
                    $bdata = count($this->database->getDorf1Building($master['wid']));
                    $bbdata = count($this->database->getDorf2Building($master['wid']));
                    $bdata1 = $this->database->getDorf1Building($master['wid']);
                } else {
                    $bdata = count($this->database->getDorf2Building($master['wid']));
                    $bbdata = count($this->database->getDorf1Building($master['wid']));
                    $bdata1 = $this->database->getDorf2Building($master['wid']);
                }
            } else {
                $bdata = $bbdata = count($this->database->getDorf1Building($master['wid'])) + count($this->database->getDorf2Building($master['wid']));
                $bdata1 = $this->database->getDorf1Building($master['wid']);
            }

            $usergold = $this->database->getUserField($owner, 'gold', 0);
            $userplus = $this->database->getUserField($owner, 'plus', 0);
            if ($userplus > time() || $ww > 0) {
                $inbuild = $bbdata < 2 ? 2 : 1;
            } else {
                $inbuild = 1;
            }

            if (
                $bdata < $inbuild &&
                $buildwood < $villwood &&
                $buildclay < $villclay &&
                $buildiron < $villiron &&
                $buildcrop < $villcrop &&
                $usergold > 0
            ) {
                $time = $master['timestamp'] + time();
                foreach ($bdata1 as $master1) {
                    $time += ($master1['timestamp'] - time());
                }

                $this->database->updateBuildingWithMaster($master['id'], $time, $bdata == 0 ? 0 : 1);
                $this->database->updateUserField($owner, 'gold', $usergold - 1, 1);
                $this->database->modifyResource($master['wid'], $buildwood, $buildclay, $buildiron, $buildcrop, 0);
            }
        }
    }

    /************************************************
        Function for starvation - by brainiacX and Shadow
        Rework by ronix
        References:
        ************************************************/

    private function starvation()
    {
            $time = time();

            //update starvation
            $getvillage = $this->database->getVillage($this->village->wid);
            $starv = $getvillage['starv'];
            if ($getvillage['owner']!=3 && $starv==0) {
                $crop = $this->database->getCropProdstarv($this->village->wid);
                $unitarrays = $this->getAllUnits($this->village->wid);
                $village_upkeep = $getvillage['pop'] + $this->getUpkeep($unitarrays, 0);
                if ($crop < $village_upkeep){
                    // add starv data
                    $this->database->setVillageField($this->village->wid, 'starv', $village_upkeep);
                    if($starv==0)
                        $this->database->setVillageField($this->village->wid, 'starvupdate', time());
                }
                unset($crop,$unitarrays,$getvillage,$village_upkeep);
            }    
                        
            // load villages with minus prod
            
            $starvarray = array();
            $starvarray = $this->database->getStarvation();
            foreach ($starvarray as $starv){
                $unitarrays = $this->getAllUnits($starv['wref']);
                $howweeating=$this->getUpkeep($unitarrays, 0,$starv['wref']);
                $upkeep = $starv['pop'] + $howweeating;
                
                
                // get enforce other player from oasis
                $q = "SELECT e.*,o.conqured,o.wref,o.high, o.owner as ownero, v.owner as ownerv FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.vref=o.wref LEFT JOIN ".TB_PREFIX."vdata as v ON e.from=v.wref where o.conqured=".$starv['wref']." AND o.owner<>v.owner";
                $enforceoasis = $this->database->query_return($q);
                $maxcount=0;
                $totalunits=0;
                if(count($enforceoasis)>0){
                    foreach ($enforceoasis as $enforce){
                        for($i=1;$i<=50;$i++){
                            $units = $enforce['u'.$i];
                            if($enforce['u'.$i] > $maxcount){
                                $maxcount = $enforce['u'.$i];
                                $maxtype = $i;
                                $enf = $enforce['id'];
                            }
                            $totalunits += $enforce['u'.$i];
                        }
                        if($totalunits == 0){
                            $maxcount = $enforce['hero'];
                            $maxtype = "hero";
                        }
                        $other_reinf=true;
                    }
                }else{ 
        //own troops from oasis
                    $q = "SELECT e.*,o.conqured,o.wref,o.high, o.owner as ownero, v.owner as ownerv FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.vref=o.wref LEFT JOIN ".TB_PREFIX."vdata as v ON e.from=v.wref where o.conqured=".$starv['wref']." AND o.owner=v.owner";
                    $enforceoasis = $this->database->query_return($q);
                    if(count($enforceoasis)>0){
                        foreach ($enforceoasis as $enforce){
                            for($i=1;$i<=50;$i++){
                                $units = $enforce['u'.$i];
                                if($enforce['u'.$i] > $maxcount){
                                    $maxcount = $enforce['u'.$i];
                                    $maxtype = $i;
                                    $enf = $enforce['id'];
                                }
                                $totalunits += $enforce['u'.$i];
                            }
                            if($totalunits == 0){
                                $maxcount = $enforce['hero'];
                                $maxtype = "hero";
                            }
                        }
                    }else{ 
            //get enforce other player from village
                        $q = "SELECT e.*, v.owner as ownerv, v1.owner as owner1 FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."vdata as v ON e.from=v.wref LEFT JOIN ".TB_PREFIX."vdata as v1 ON e.vref=v1.wref where e.vref=".$starv['wref']." AND v.owner<>v1.owner";
                        $enforcearray = $this->database->query_return($q);
                        if(count($enforcearray)>0){
                            foreach ($enforcearray as $enforce){
                                for($i = 0 ; $i <= 50 ; $i++){
                                    $units = $enforce['u'.$i];
                                    if($enforce['u'.$i] > $maxcount){
                                        $maxcount = $enforce['u'.$i];
                                        $maxtype = $i;
                                        $enf = $enforce['id'];
                                    }
                                    $totalunits += $enforce['u'.$i];
                                }
                                if($totalunits == 0){
                                    $maxcount = $enforce['hero'];
                                    $maxtype = "hero";
                                }
                            }    
                        }else{ 
            //get own reinforcement from other village
                            $q = "SELECT e.*, v.owner as ownerv, v1.owner as owner1 FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."vdata as v ON e.from=v.wref LEFT JOIN ".TB_PREFIX."vdata as v1 ON e.vref=v1.wref where e.vref=".$starv['wref']." AND v.owner=v1.owner";
                            $enforcearray = $this->database->query_return($q);
                            if(count($enforcearray)>0){
                                foreach ($enforcearray as $enforce){
                                    for($i = 0 ; $i <= 50 ; $i++){
                                        $units = $enforce['u'.$i];
                                        if($enforce['u'.$i] > $maxcount){
                                            $maxcount = $enforce['u'.$i];
                                            $maxtype = $i;
                                            $enf = $enforce['id'];
                                        }
                                        $totalunits += $enforce['u'.$i];
                                    }
                                    if($totalunits == 0){
                                        $maxcount = $enforce['hero'];
                                        $maxtype = "hero";
                                    }
                                }
                            }else{ 
            //get own unit
                                $unitarray = $this->database->getUnit($starv['wref']);
                                for($i = 0 ; $i <= 50 ; $i++){
                                    $units = $unitarray['u'.$i];
                                    if($unitarray['u'.$i] > $maxcount){
                                        $maxcount = $unitarray['u'.$i];
                                        $maxtype = $i;
                                    }
                                    $totalunits += $unitarray['u'.$i];
                                }
                                if($totalunits == 0){
                                    $maxcount = $unitarray['hero'];
                                    $maxtype = "hero";
                                }
                            }
                        }
                    }
                }            

                // counting

                $timedif = $time-$starv['starvupdate'];
                $skolko=$this->database->getCropProdstarv($starv['wref'])-$starv['starv'];
                if($skolko<0){$golod=true;}
                if($golod){
                    $starvsec = (abs($skolko)/3600);
                    $difcrop = ($timedif*$starvsec); //crop eat up over time
                    $newcrop = 0;
                    $oldcrop = $this->database->getVillageField($starv['wref'], 'crop');
                    if ($oldcrop > 100){ //if the grain is then tries to send all
                        $difcrop = $difcrop-$oldcrop;
                        if($difcrop < 0){
                            $difcrop = 0;
                            $newcrop = $oldcrop-$difcrop;
                            $this->database->setVillageField($starv['wref'], 'crop', $newcrop);
                        }
                    }
                    
                    if($difcrop > 0){
                        if ($maxtype !== 'hero')
                            $hungry = GlobalVariablesHelper::getUnit($maxtype);
                        else
                            $hungry = $hero;
                        if ($hungry['crop']>0 && $oldcrop <=0) {
                            $killunits = intval($difcrop/$hungry['crop']);
                        }else $killunits=0;
                        
                        if($killunits > 0){
                        $pskolko =  abs($skolko);
                        if($killunits > $pskolko && $skolko <0){
                        $killunits = $pskolko;
                        }
                            if (isset($enf)){
                                if($killunits < $maxcount){
                                    $this->database->modifyEnforce($enf, $maxtype, $killunits, 0);
                                    $this->database->setVillageField($starv['wref'], 'starv', $upkeep);
                                    $this->database->setVillageField($starv['wref'], 'starvupdate', $time);
                                    $this->database->modifyResource($starv['wref'],0,0,0,$hungry['crop'],1);
                                    if($maxtype == "hero"){
                                        $heroid = $this->database->getHeroField($this->database->getVillageField($enf,"owner"),"heroid");
                                        $this->database->modifyHero("dead", 1, $heroid);
                                    }
                                }else{
                                    $this->database->deleteReinf($enf);
                                    $this->database->setVillageField($starv['wref'], 'starv', $upkeep);
                                    $this->database->setVillageField($starv['wref'], 'starvupdate', $time);
                                }
                            }else{
                                if($killunits < $maxcount){
                                    $this->database->modifyUnit($starv['wref'], array($maxtype), array($killunits), array(0));
                                    $this->database->setVillageField($starv['wref'], 'starv', $upkeep);
                                    $this->database->setVillageField($starv['wref'], 'starvupdate', $time);
                                    $this->database->modifyResource($starv['wref'],0,0,0,$hungry['crop'],1);
                                    if($maxtype == "hero"){
                                        $heroid = $this->database->getHeroField($starv['owner'],"heroid");
                                        $this->database->modifyHero("dead", 1, $heroid);
                                    }
                                }elseif($killunits > $maxcount){
                                    $killunits = $maxcount;
                                    $this->database->modifyUnit($starv['wref'], array($maxtype), array($killunits), array(0));
                                    $this->database->setVillageField($starv['wref'], 'starv', $upkeep);
                                    $this->database->setVillageField($starv['wref'], 'starvupdate', $time);
                                    if($maxtype == "hero"){
                                        $heroid = $this->database->getHeroField($starv['owner'],"heroid");
                                        $this->database->modifyHero("dead", 1, $heroid);
                                    }
                                }
                            }
                        }
                    }
                }
                $crop = $this->database->getCropProdstarv($starv['wref']);
                if ($crop > $upkeep){
                    $this->database->setVillageField($starv['wref'], 'starv', 0);
                    $this->database->setVillageField($starv['wref'], 'starvupdate', 0);
                }

               
                unset ($starv,$unitarrays,$enforcearray,$enforce,$starvarray);
            }
    }

        /************************************************
        Function for starvation - by brainiacX and Shadow
        Rework by ronix
        References:
        ************************************************/

    private function checkBan()
    {
        $time = time();
        $q = "SELECT * FROM ".TB_PREFIX."banlist WHERE active = 1 and end < $time";
        $array = $this->database->query_return($q);
        foreach($array as $banlist) {
            $this->database->query("UPDATE ".TB_PREFIX."banlist SET active = 0 WHERE id = ".$banlist['id']."");
            $this->database->query("UPDATE ".TB_PREFIX."users SET access = 2 WHERE id = ".$banlist['uid']."");
        }
    }

    private function checkReviveHero(){
        
        $herodata=$this->database->getHero($this->session->uid,1);
        if ($herodata[0]['dead']==1){
            $this->database->query("UPDATE " . TB_PREFIX . "units SET hero = 0 WHERE vref = ".$this->session->villages[0]."");
        }
        if($herodata[0]['trainingtime'] <= time()) {
                if($herodata[0]['trainingtime'] != 0) {
                    if($herodata[0]['dead'] == 0) {
                        $this->database->query("UPDATE " . TB_PREFIX . "hero SET trainingtime = '0' WHERE uid = " . $this->session->uid . "");
                        $this->database->query("UPDATE " . TB_PREFIX . "units SET hero = 1 WHERE vref = ".$this->session->villages[0]."");
                    }
                }
            }
    }

     private function artefactOfTheFool() {
     
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts where type = 8 and active = 1 and $time - lastupdate >= 86400";
        $array = $this->database->query_return($q);
        foreach($array as $artefact) {
        $kind = rand(1,7);
        while($kind == 6){
        $kind = rand(1,7);
        }
        if($artefact['size'] != 3){
        $bad_effect = rand(0,1);
        }else{
        $bad_effect = 0;
        }
        switch($kind) {
                case 1:
                $effect = rand(1,5);
                break;
                case 2:
                $effect = rand(1,3);
                break;
                case 3:
                $effect = rand(3,10);
                break;
                case 4:
                $effect = rand(2,4);
                break;
                case 5:
                $effect = rand(2,4);
                break;
                case 7:
                $effect = rand(1,6);
                break;
            }
        $this->database->query("UPDATE ".TB_PREFIX."artefacts SET kind = $kind, bad_effect = $bad_effect, effect2 = $effect, lastupdate = $time WHERE id = ".$artefact['id']."");
        }
    }
}
