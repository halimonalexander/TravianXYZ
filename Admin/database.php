<?php
#################################################################################
##                                                                             ##
##              -= YOU MUST NOT REMOVE OR CHANGE THIS NOTICE =-                ##
##                                                                             ##
## --------------------------------------------------------------------------- ##
##                                                                             ##
##  Project:       TravianZ                                                    ##
##  Version:       05.03.2014                                                  ##
##  Filename:      Admin/database.php      			                           ##
##  Developed by:  Dzoki                                                       ##
##  Edited by:     Shadow and ronix                                            ##
##  License:       Creative Commons BY-NC-SA 3.0                               ##
##  Copyright:     TravianZ (c) 2014 - All rights reserved                     ##
##  URLs:          http://travian.shadowss/ro                                  ##
##  Source code:   https://github.com/Shadowss/TravianZ	                       ##
##                                                                             ##
#################################################################################
use App\Sids\MovementTypeSid;

if($gameinstall == 1){
include_once("../../GameEngine/config.php");
include_once("../../GameEngine/Data/buidata.php");
}else{
include_once("../GameEngine/config.php");
include_once("../GameEngine/Data/buidata.php");
include_once("../GameEngine/Data/unitdata.php");
include_once("../GameEngine/Technology.php");
include_once("../GameEngine/Units.php");
}
class adm_DB
{
    /** @var \MYSQLi_DB */
	public $db;

    function __construct($db)
    {
	    $this->db = $db;
	}

	public function Login($username,$password)
    {
		$q = "SELECT password FROM ".TB_PREFIX."users where username = '$username' and access >= ".MULTIHUNTER;
		$result = $this->db->query($q);
		$dbarray = $this->db->fetchArray($result);
    
        if ($dbarray['password'] == md5($password)) {
            $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0,'X','$username logged in (IP: <b>" . $_SERVER['REMOTE_ADDR'] . "</b>)'," . time() . ")");
        
            return true;
        }
        else {
            $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0,'X','<font color=\'red\'><b>IP: " . $_SERVER['REMOTE_ADDR'] . " tried to log in with username <u> $username</u> but access was denied!</font></b>'," . time() . ")");
        
            return false;
        }
	}

	public function recountPopUser($uid)
    {
        $villages = $this->db->getProfileVillages($uid);
        
        for ($i = 0; $i <= count($villages) - 1; $i++) {
            $vid = $villages[ $i ]['wref'];
            $this->recountPop($vid);
            $this->recountCP($vid);
        }
    }

	private function recountPop($vid)
    {
        $fdata = $this->db->getResourceLevel($vid);
        $popTot = 0;
        for ($i = 1; $i <= 40; $i++) {
            $lvl = $fdata[ "f" . $i ];
            $building = $fdata[ "f" . $i . "t" ];
            if ($building) {
                $popTot += $this->buildingPOP($building, $lvl);
            }
        }
        $q = "UPDATE " . TB_PREFIX . "vdata set pop = $popTot where wref = $vid";
        $this->db->query($q);
    }

    private function buildingPOP($f,$lvl)
    {
        $name = "bid" . $f;
        global $$name;
        $popT = 0;
        $dataarray = $$name;
        for ($i = 0; $i <= $lvl; $i++) {
            $popT += $dataarray[ $i ]['pop'];
        }
    
        return $popT;
    }
  
    private function buildingCP($f,$lvl)
    {
        $name = "bid" . $f;
        global $$name;
        $popT = 0;
        $dataarray = $$name;
    
        for ($i = 0; $i <= $lvl; $i++) {
            $popT += $dataarray[ $i ]['cp'];
        }
    
        return $popT;
    }
  
    public function recountCP($vid)
    {
        $fdata = $this->db->getResourceLevel($vid);
        $popTot = 0;
        for ($i = 1; $i <= 40; $i++) {
            $lvl = $fdata[ "f" . $i ];
            $building = $fdata[ "f" . $i . "t" ];
            if ($building) {
                $popTot += $this->buildingCP($building, $lvl);
            }
        }
        
        $q = "UPDATE " . TB_PREFIX . "vdata set cp = $popTot where wref = $vid";
        $this->db->query($q);
    }

	public function getWref($x,$y)
    {
        $q = "SELECT id FROM " . TB_PREFIX . "wdata where x = $x and y = $y";
        $result = $this->db->query($q);
        $r = $this->db->fetchArray($result);
        
        return $r['id'];
    }

	public function AddVillage($post)
    {
        $wid = $this->getWref($post['x'], $post['y']);
        $uid = $post['uid'];
        $status = $this->db->getVillageState($wid);
        $status = 0;
        
        if ($status == 0) {
            $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0," . $_SESSION['id'] . ",'Added new village <b><a href=\'admin.php?p=village&did=$wid\'>$wid</a></b> to user <b><a href=\'admin.php?p=player&uid=$uid\'>$uid</a></b>'," . time() . ")");
            $this->db->setFieldTaken($wid);
            $this->db->addVillage($wid, $uid, 'new village', '0');
            $this->db->addResourceFields($wid, $this->db->getVillageType($wid));
            $this->db->addUnits($wid);
            $this->db->addTech($wid);
            $this->db->addABTech($wid);
        }
    }

	public function Punish($post)
    {
        $villages = $this->db->getProfileVillages($post['uid']);
        $admid = $post['admid'];
        $user = $this->db->getUserArray($post['uid'], 1);
        
        for ($i = 0; $i <= count($villages) - 1; $i++) {
            $vid = $villages[ $i ]['wref'];
            if ($post['punish']) {
                $popOld = $villages[ $i ]['pop'];
                $proc = 100 - $post['punish'];
                $pop = floor(($popOld / 100) * ($proc));
                if ($pop <= 1) {
                    $pop = 2;
                }
                $this->PunishBuilding($vid, $proc, $pop);
            }
            if ($post['del_troop']) {
                if ($user['tribe'] == 1) {
                    $unit = 1;
                }
                elseif ($user['tribe'] == 2) {
                    $unit = 11;
                }
                elseif ($user['tribe'] == 3) {
                    $unit = 21;
                }
                $this->DelUnits($villages[ $i ]['wref'], $unit);
            }
            if ($post['clean_ware']) {
                $time = time();
                $q = "UPDATE " . TB_PREFIX . "vdata SET `wood` = '0', `clay` = '0', `iron` = '0', `crop` = '0', `lastupdate` = '$time' WHERE wref = $vid;";
                $this->db->query($q);
            }
        }
        $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0," . $_SESSION['id'] . ",'Punished user: <a href=\'admin.php?p=player&uid=" . $post['uid'] . "\'>" . $post['uid'] . "</a> with <b>-" . $post['punish'] . "%</b> population'," . time() . ")");
    }

    private function PunishBuilding($vid,$proc,$pop)
    {
        $q = "UPDATE " . TB_PREFIX . "vdata set pop = $pop where wref = $vid;";
        $this->db->query($q);
        $fdata = $this->db->getResourceLevel($vid);
        for ($i = 1; $i <= 40; $i++) {
            if ($fdata[ 'f' . $i ] > 1) {
                $zm = ($fdata[ 'f' . $i ] / 100) * $proc;
                if ($zm < 1) {
                    $zm = 1;
                }
                else {
                    $zm = floor($zm);
                }
                $q = "UPDATE " . TB_PREFIX . "fdata SET `f$i` = '$zm' WHERE `vref` = $vid;";
                $this->db->query($q);
            }
        }
    }

    private function DelUnits($vid,$unit)
    {
        for ($i = $unit; $i <= 9 + $unit; $i++) {
            $this->DelUnits2($vid, $unit);
        }
    }

    private function DelUnits2($vid, $unit)
    {
        $q = "UPDATE " . TB_PREFIX . "units SET `u$unit` = '0' WHERE `vref` = $vid;";
        $this->db->query($q);
    }

    public function DelPlayer($uid,$pass)
    {
        $ID = $_SESSION['id'];
        if ($this->CheckPass($pass, $ID)) {
            $villages = $this->db->getProfileVillages($uid);
            for ($i = 0; $i <= count($villages) - 1; $i++) {
                $this->DelVillage($villages[ $i ]['wref'], 1);
            }
            $q = "DELETE FROM " . TB_PREFIX . "hero where uid = $uid";
            $this->db->query($q);
        
            $name = $this->db->getUserField($uid, "username", 0);
            $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0,$ID,'Deleted user <a>$name</a>'," . time() . ")");
            $q = "DELETE FROM " . TB_PREFIX . "users WHERE `id` = $uid;";
            $this->db->query($q);
        }
    }

    function getUserActive()
    {
        $time = time() - (60 * 5);
        $q = "SELECT * FROM " . TB_PREFIX . "users where timestamp > $time and username != 'support'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }

    private function CheckPass($password,$uid)
    {
        $q = "SELECT password FROM " . TB_PREFIX . "users where id = '$uid' and access = " . ADMIN;
        $result = $this->db->query($q);
        $dbarray = $this->db->fetchArray($result);
        if ($dbarray['password'] == md5($password)) {
            return true;
        }
        else {
            return false;
        }
    }

    public function DelVillage($wref, $mode=0)
    {
        if ($mode == 0) {
            $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE `wref` = $wref and capital = 0";
        }
        else {
            $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE `wref` = $wref";
        }
        $result = $this->db->query($q);
        if ($this->db->numRows($result) > 0) {
            $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0," . $_SESSION['id'] . ",'Deleted village <b>$wref</b>'," . time() . ")");
        
            $this->db->clearExpansionSlot($wref);
        
            $q = "DELETE FROM " . TB_PREFIX . "abdata where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "bdata where wid = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "market where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "odata where wref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "research where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "tdata where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "fdata where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "training where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "units where vref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "farmlist where wref = $wref";
            $this->db->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "raidlist where towref = $wref";
            $this->db->query($q);
        
            $q = "DELETE FROM " . TB_PREFIX . "movement where `from` = $wref and proc=0";
            $this->db->query($q);
        
            $getmovement = $this->db->getMovement(3, $wref, 1);
            foreach ($getmovement as $movedata) {
                $time = microtime(true);
                $time2 = $time - $movedata['starttime'];
                $this->db->setMovementProc($movedata['moveid']);
                $this->db->addMovement(MovementTypeSid::RETURNING, $movedata['to'], $movedata['from'], $movedata['ref'], $time, $time + $time2);
            }
        
            //check    return enforcement from del village
            $this->returnTroops($wref);
        
            $q = "DELETE FROM " . TB_PREFIX . "vdata WHERE `wref` = $wref";
            $this->db->query($q);
        
            if ($this->db->affectedRows() > 0) {
                $q = "UPDATE " . TB_PREFIX . "wdata set occupied = 0 where id = $wref";
                $this->db->query($q);
            
                $getprisoners = $this->db->getPrisoners($wref);
                foreach ($getprisoners as $pris) {
                    $troops = 0;
                    for ($i = 1; $i < 12; $i++) {
                        $troops += $pris[ 't' . $i ];
                    }
                    $this->db->modifyUnit($pris['wref'], ["99o"], [$troops], [0]);
                    $this->db->deletePrisoners($pris['id']);
                }
                $getprisoners = $this->db->getPrisoners3($wref);
                foreach ($getprisoners as $pris) {
                    $troops = 0;
                    for ($i = 1; $i < 12; $i++) {
                        $troops += $pris[ 't' . $i ];
                    }
                    $this->db->modifyUnit($pris['wref'], ["99o"], [$troops], [0]);
                    $this->db->deletePrisoners($pris['id']);
                }
            }
        }
    }
    
    public function returnTroops($wref)
    {
        $getenforce = $this->db->getEnforceVillage($wref, 0);
    
        foreach ($getenforce as $enforce) {
        
            $to = $this->db->getVillage($enforce['from']);
            $Gtribe = "";
            if ($this->db->getUserField($to['owner'], 'tribe', 0) == '2') {
                $Gtribe = "1";
            }
            elseif ($this->db->getUserField($to['owner'], 'tribe', 0) == '3') {
                $Gtribe = "2";
            }
            elseif ($this->db->getUserField($to['owner'], 'tribe', 0) == '4') {
                $Gtribe = "3";
            }
            elseif ($this->db->getUserField($to['owner'], 'tribe', 0) == '5') {
                $Gtribe = "4";
            }
        
            $start = ($this->db->getUserField($to['owner'], 'tribe', 0) - 1) * 10 + 1;
            $end = ($this->db->getUserField($to['owner'], 'tribe', 0) * 10);
        
            $from = $this->db->getVillage($enforce['from']);
            $fromcoor = $this->db->getCoor($enforce['from']);
            $tocoor = $this->db->getCoor($enforce['vref']);
            $fromCor = ['x' => $tocoor['x'], 'y' => $tocoor['y']];
            $toCor = ['x' => $fromcoor['x'], 'y' => $fromcoor['y']];
        
            $speeds = [];
        
            //find slowest unit.
            for ($i = $start; $i <= $end; $i++) {
            
                if (intval($enforce[ 'u' . $i ]) > 0) {
                    if (isset($unitarray)) {
                        reset($unitarray);
                    }
                    $unitarray = $GLOBALS[ "u" . $i ];
                    $speeds[] = $unitarray['speed'];
                }
                else {
                    $enforce[ 'u' . $i ] = '0';
                }
            }
        
            if (intval($enforce['hero']) > 0) {
                $q = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $from['owner'] . "";
                $result = $this->db->query($q);
                $hero_f = $this->db->fetchArray($result);
                $hero_unit = $hero_f['unit'];
                $speeds[] = $GLOBALS[ 'u' . $hero_unit ]['speed'];
            }
            else {
                $enforce['hero'] = '0';
            }
        
            $artefact = count($this->db->getOwnUniqueArtefactInfo2($from['owner'], 2, 3, 0));
            $artefact1 = count($this->db->getOwnUniqueArtefactInfo2($enforce['from'], 2, 1, 1));
            $artefact2 = count($this->db->getOwnUniqueArtefactInfo2($from['owner'], 2, 2, 0));
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
            $time = round($this->procDistanceTime($fromCor, $toCor, min($speeds), $enforce['from']) / $fastertroops);
        
            $foolartefact2 = $this->db->getFoolArtefactInfo(2, $enforce['from'], $from['owner']);
            if (count($foolartefact2) > 0) {
                foreach ($foolartefact2 as $arte) {
                    if ($arte['bad_effect'] == 1) {
                        $time *= $arte['effect2'];
                    }
                    else {
                        $time /= $arte['effect2'];
                        $time = round($time);
                    }
                }
            }
            $reference = $this->db->addAttack($enforce['from'], $enforce[ 'u' . $start ], $enforce[ 'u' . ($start + 1) ], $enforce[ 'u' . ($start + 2) ], $enforce[ 'u' . ($start + 3) ], $enforce[ 'u' . ($start + 4) ], $enforce[ 'u' . ($start + 5) ], $enforce[ 'u' . ($start + 6) ], $enforce[ 'u' . ($start + 7) ], $enforce[ 'u' . ($start + 8) ], $enforce[ 'u' . ($start + 9) ], $enforce['hero'], 2, 0, 0, 0, 0);
            $this->db->addMovement(MovementTypeSid::RETURNING, $wref, $enforce['from'], $reference, time(), ($time + time()));
            $this->db->deleteReinf($enforce['id']);
        }
    }
    
    public function getTypeLevel($tid,$vid)
    {
        global $village;
    
        $keyholder = [];
    
        if ($vid == 0) {
            $resourcearray = $village->resarray;
        }
        else {
            $resourcearray = $this->db->getResourceLevel($vid);
        }
        foreach (array_keys($resourcearray, $tid) as $key) {
            if (strpos($key, 't')) {
                $key = preg_replace("/[^0-9]/", '', $key);
                array_push($keyholder, $key);
            }
        }
        $element = count($keyholder);
        if ($element >= 2) {
            if ($tid <= 4) {
                $temparray = [];
                for ($i = 0; $i <= $element - 1; $i++) {
                    array_push($temparray, $resourcearray[ 'f' . $keyholder[ $i ] ]);
                }
                foreach ($temparray as $key => $val) {
                    if ($val == max($temparray))
                        $target = $key;
                }
            }
            else {
                $target = 0;
                for ($i = 1; $i <= $element - 1; $i++) {
                    if ($resourcearray[ 'f' . $keyholder[ $i ] ] > $resourcearray[ 'f' . $keyholder[ $target ] ]) {
                        $target = $i;
                    }
                }
            }
        }
        elseif ($element == 1) {
            $target = 0;
        }
        else {
            return 0;
        }
        if ($keyholder[ $target ] != "") {
            return $resourcearray[ 'f' . $keyholder[ $target ] ];
        }
        else {
            return 0;
        }
    }
    
    public function procDistanceTime($coor,$thiscoor,$ref,$vid)
    {
        global $bid28, $bid14;
    
        $xdistance = ABS($thiscoor['x'] - $coor['x']);
        if ($xdistance > WORLD_MAX) {
            $xdistance = (2 * WORLD_MAX + 1) - $xdistance;
        }
        $ydistance = ABS($thiscoor['y'] - $coor['y']);
        if ($ydistance > WORLD_MAX) {
            $ydistance = (2 * WORLD_MAX + 1) - $ydistance;
        }
        $distance = SQRT(POW($xdistance, 2) + POW($ydistance, 2));
        $speed = $ref;
        if ($this->getTypeLevel(14, $vid) != 0 && $distance >= TS_THRESHOLD) {
            $speed = $speed * ($bid14[ $this->getTypeLevel(14, $vid) ]['attri'] / 100);
        }
    
        if ($speed != 0) {
            return round(($distance / $speed) * 3600 / INCREASE_SPEED);
        }
        else {
            return round($distance * 3600 / INCREASE_SPEED);
        }
    }

	public function DelBan($uid,$id)
    {
        $name = addslashes($this->db->getUserField($uid, "username", 0));
        $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0," . $_SESSION['id'] . ",'Unbanned user <a href=\'admin.php?p=player&uid=$uid\'>$name</a>'," . time() . ")");
        $q = "UPDATE " . TB_PREFIX . "users SET `access` = '" . USER . "' WHERE `id` = $uid;";
        $this->db->query($q);
        $q = "UPDATE " . TB_PREFIX . "banlist SET `active` = '0' WHERE `id` = $id;";
        $this->db->query($q);
    }
    
    public function AddBan($uid,$end,$reason)
    {
        $name = addslashes($this->db->getUserField($uid, "username", 0));
        $this->db->query("Insert into " . TB_PREFIX . "admin_log values (0," . $_SESSION['id'] . ",'Banned user <a href=\'admin.php?p=player&uid=$uid\'>$name</a>'," . time() . ")");
        $q = "UPDATE " . TB_PREFIX . "users SET `access` = '0' WHERE `id` = $uid;";
        $this->db->query($q);
        $time = time();
        $admin = $_SESSION['id'];  //$database->getUserField($_SESSION['username'],'id',1);
        $name = addslashes($this->db->getUserField($uid, 'username', 0));
        $q = "INSERT INTO " . TB_PREFIX . "banlist (`uid`, `name`, `reason`, `time`, `end`, `admin`, `active`) VALUES ($uid, '$name' , '$reason', '$time', '$end', '$admin', '1');";
        $this->db->query($q);
    }
    
    function search_player($player)
    {
        $q = "SELECT id,username FROM " . TB_PREFIX . "users WHERE `username` LIKE '%$player%' and username != 'support'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }

    function search_email($email)
    {
        $q = "SELECT id,email FROM " . TB_PREFIX . "users WHERE `email` LIKE '%$email%' and username != 'support'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }

    function search_village($village)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE `name` LIKE '%$village%' or `wref` LIKE '%$village%'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }

    function search_alliance($alliance)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "alidata WHERE `name` LIKE '%$alliance%' or `tag` LIKE '%$alliance%' or `id` LIKE '%$alliance%'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }

    function search_ip($ip)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "login_log WHERE `ip` LIKE '%$ip%'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }
    
    function search_banned()
    {
        $q = "SELECT * FROM " . TB_PREFIX . "banlist where active = '1'";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }

    function Del_banned()
    {
        //$q = "SELECT * FROM ".TB_PREFIX."banlist";
        $result = $this->db->query($q);
    
        return $this->db->fetchAll($result);
    }
}

$admin = new adm_DB($database);
include("function.php");
