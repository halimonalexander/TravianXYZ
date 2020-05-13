<?php

namespace GameEngine;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                    			       ## 
##  Filename       Units.php                                                   ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.  		       ##
##  Fixed by:      InCube - double troops				       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                		       ##
##  Source code:   https://github.com/Shadowss/TravianZ		               ## 
##                                                                             ##
#################################################################################

use App\Helpers\ResponseHelper;
use App\Routes;
use App\Sids\MovementTypeSid;
use GameEngine\Database\MysqliModel;

class Units
{
    private $database;
    private $form;
    private $generator;
    private $session;
    private $village;
    
    public $sending = [];
    public $recieving = [];
    public $return = [];
    
    public function __construct(
        MysqliModel $database,
        Form $form,
        MyGenerator $generator,
        Session $session,
        Village $village
    ) {
        $this->database = $database;
        $this->form = $form;
        $this->generator = $generator;
        $this->session = $session;
        $this->village = $village;
    }
    
    public function procUnits($post)
    {
        if (isset($post['c'])) {
            switch ($post['c']) {
            
                case "1":
                    if (isset($post['a']) && $post['a'] == 533374) {
                        $this->sendTroops($post);
                    }
                    else {
                        $post = $this->loadUnits($post);
                    
                        return $post;
                    }
                    break;
            
                case "2":
                    if (isset($post['a']) && $post['a'] == 533374 && $post['disabledr'] == "") {
                        $this->sendTroops($post);
                    }
                    else {
                        $post = $this->loadUnits($post);
                    
                        return $post;
                    }
                    break;
            
                case "8":
                    $this->sendTroopsBack($post);
                    break;
            
                case "3":
                    if (isset($post['a']) && $post['a'] == 533374 && $post['disabled'] == "") {
                        $this->sendTroops($post);
                    }
                    else {
                        $post = $this->loadUnits($post);
                    
                        return $post;
                    }
                    break;
            
                case "4":
                    if (isset($post['a']) && $post['a'] == 533374) {
                        $this->sendTroops($post);
                    }
                    else {
                        $post = $this->loadUnits($post);
                    
                        return $post;
                    }
            
                case "5":
                    if (isset($post['a']) && $post['a'] == "new") {
                        $this->Settlers($post);
                    }
                    else {
                        $post = $this->loadUnits($post);
                    
                        return $post;
                    }
                    break;
            }
        }
    }
    
    private function loadUnits($post)
    {
        // Search by town name
        // Coordinates and look confirm name people
        if (isset($post['x']) && isset($post['y']) && $post['x'] != "" && $post['y'] != "") {
            $vid = $this->database->getVilWref($post['x'], $post['y']);
            unset($post['dname']);
            unset($_POST['dname']);
        }
        elseif (isset($post['dname']) && $post['dname'] != "") {
            $vid = $this->database->getVillageByName(stripslashes($post['dname']));
        }
        
        if (!empty($vid)) {
            if ($this->database->isVillageOases($vid) != 0) {
                $too = $this->database->getOasisField($vid, "conqured");
                if ($too == 0) {
                    $disabledr = "disabled=disabled";
                    $disabled = "disabled=disabled";
                }
                else {
                    $disabledr = "";
                    if ($this->session->sit == 0) {
                        $disabled = "";
                    }
                    else {
                        $disabled = "disabled=disabled";
                    }
                }
            }
            else {
                $too = $this->database->getVillage($vid);
                if ($too['owner'] == 3) {
                    $disabledr = "disabled=disabled";
                    $disabled = "";
                }
                else {
                    $disabledr = "";
                    if ($this->session->sit == 0) {
                        $disabled = "";
                    }
                    else {
                        $disabled = "disabled=disabled";
                    }
                }
            }
        }
        else {
            $disabledr = "";
            if ($this->session->sit == 0) {
                $disabled = "";
            }
            else {
                $disabled = "disabled=disabled";
            }
        }
        
        if ($disabledr != "" && $post['c'] == 2) {
            $this->form->addError("error", "You can't reinforce this village/oasis");
        }
        
        if ($disabled != "" && $post['c'] == 3) {
            $this->form->addError("error", "You can't attack this village/oasis with normal attack");
        }
        
        if (!$post['t1'] && !$post['t2'] && !$post['t3'] && !$post['t4'] && !$post['t5'] &&
            !$post['t6'] && !$post['t7'] && !$post['t8'] && !$post['t9'] && !$post['t10'] && !$post['t11']) {
            $this->form->addError("error", "You need to mark min. one troop");
        }
    
        if (!$post['dname'] && !$post['x'] && !$post['y']) {
            $this->form->addError("error", "Insert name or coordinates");
        }
    
        if (isset($post['dname']) && $post['dname'] != "") {
            $id = $this->database->getVillageByName(stripslashes($post['dname']));
            if (!isset($id)) {
                $this->form->addError("error", "Village do not exist");
            }
            else {
                $coor = $this->database->getCoor($id);
            }
        }
    
        // People search by coordinates
        // We confirm and seek coordinate coordinates Village
        if (isset($post['x']) && isset($post['y']) && $post['x'] != "" && $post['y'] != "") {
            $coor = ['x' => $post['x'], 'y' => $post['y']];
            $id = $this->generator->getBaseID($coor['x'], $coor['y']);
            if (!$this->database->getVillageState($id)) {
                $this->form->addError("error", "Coordinates do not exist");
            }
        }
        
        if (!empty($coor)) {
            if ($this->session->tribe == 1) {
                $Gtribe = "";
            }
            elseif ($this->session->tribe == 2) {
                $Gtribe = "1";
            }
            elseif ($this->session->tribe == 3) {
                $Gtribe = "2";
            }
            elseif ($this->session->tribe == 4) {
                $Gtribe = "3";
            }
            elseif ($this->session->tribe == 5) {
                $Gtribe = "4";
            }
            for ($i = 1; $i < 12; $i++) {
                if (isset($post[ 't' . $i ])) {
                    if ($i < 10) $troophave = $this->village->unitarray[ 'u' . $Gtribe . $i ];
                    if ($i == 10) $troophave = $this->village->unitarray[ 'u' . floor(intval($Gtribe) + 1) * $i ];
                    if ($i == 11) $troophave = $this->village->unitarray['hero'];
                
                    if (intval($post[ 't' . $i ]) > $troophave) {
                        $this->form->addError("error", "You can't send more units than you have");
                        break;
                    }
                    if (intval($post[ 't' . $i ]) < 0) {
                        $this->form->addError("error", "You can't send negative units.");
                        break;
                    }
                    if (preg_match('/[^0-9]/', $post[ 't' . $i ])) {
                        $this->form->addError("error", "Special characters can't entered");
                        break;
                    }
                }
            }
        }
        
        if ($this->database->isVillageOases($id) == 0) {
            if ($this->database->hasBeginnerProtection($id) == 1) {
                $this->form->addError("error", "Player is under beginners protection. You can't attack him");
            }
        
            //check if banned/admin:
            $villageOwner = $this->database->getVillageField($id, 'owner');
            $userAccess = $this->database->getUserField($villageOwner, 'access', 0);
            if ($userAccess == '0' or $userAccess == '8' or $userAccess == '9') {
                $this->form->addError("error", "Player is Banned. You can't attack him");
                //break;
            }
            //check if vacation mode:
            if ($this->database->getvacmodexy($id)) {
                $this->form->addError("error", "User is on vacation mode");
                //break;
            }
        
            //check if attacking same village that units are in
            if ($id == $this->village->wid) {
                $this->form->addError("error", "You cant attack same village you are sending from.");
                //break;
            }
            // We process the array with the errors given in the form
            if ($this->form->returnErrors() > 0) {
                $_SESSION['errorarray'] = $this->form->getErrors();
                $_SESSION['valuearray'] = $_POST;
                header("Location: a2b.php");
            }
            else {
                // We must return an array with $ post, which contains all the data more
                // another variable that will define the flag is raised and is being sent and the type of shipping
                $villageName = $this->database->getVillageField($id, 'name');
                $speed = 300;
                $timetaken = $this->generator->procDistanceTime($coor, $this->village->coor, INCREASE_SPEED, 1);
                array_push($post, "$id", "$villageName", "$villageOwner", "$timetaken");
            
                return $post;
            }
        }
        else {
        
            if ($this->form->returnErrors() > 0) {
                $_SESSION['errorarray'] = $this->form->getErrors();
                $_SESSION['valuearray'] = $_POST;
                header("Location: a2b.php");
            }
            else {
            
                $villageName = $this->database->getOasisField($id, "name");
                $speed = 300;
                $timetaken = $this->generator->procDistanceTime($coor, $this->village->coor, INCREASE_SPEED, 1);
                array_push($post, "$id", "$villageName", "2", "$timetaken");
            
                return $post;
            }
        }
    }
    
    public function returnTroops($wref,$mode=0)
    {
        if (!mode) {
            $getenforce = $this->database->getEnforceVillage($wref, 0);
            foreach ($getenforce as $enforce) {
                $this->processReturnTroops($enforce);
            }
        }
        //check oasis
        $getenforce1 = $this->database->getOasisEnforce($wref, 1);
        foreach ($getenforce1 as $enforce) {
            $this->processReturnTroops($enforce);
        }
        //set oasis to default
        if (count($getenforce1) > 0) {
            $q = "DELETE FROM " . TB_PREFIX . "ndata WHERE toWref=" . $getenforce1[0]['vref'];
            $this->database->query($q);
            $this->database->populateOasisUnits($getenforce1[0]['vref'], $getenforce1[0]['high']);
            $q = "UPDATE " . TB_PREFIX . "odata SET conqured=0,wood=800,iron=800,clay=800,maxstore=800,crop=800,maxcrop=800,lastupdated=" . time() . ",lastupdated2=" . time() . ",loyalty=100,owner=2,name='Unoccupied Oasis' WHERE conqured=$wref";
            $this->database->query($q);
        }
    }

    private function processReturnTroops($enforce)
    {
        $to = $this->database->getVillage($enforce['from']);
        
        
        if ($this->database->getUserField($to['owner'], 'tribe', 0) == '2') {
            $Gtribe = "1";
        }
        elseif ($this->database->getUserField($to['owner'], 'tribe', 0) == '3') {
            $Gtribe = "2";
        }
        elseif ($this->database->getUserField($to['owner'], 'tribe', 0) == '4') {
            $Gtribe = "3";
        }
        elseif ($this->database->getUserField($to['owner'], 'tribe', 0) == '5') {
            $Gtribe = "4";
        }
        else {
            $Gtribe = "";
        }
    
        $start = ($this->database->getUserField($to['owner'], 'tribe', 0) - 1) * 10 + 1;
        $end = ($this->database->getUserField($to['owner'], 'tribe', 0) * 10);
    
        $from = $this->database->getVillage($enforce['from']);
        $fromcoor = $this->database->getCoor($enforce['from']);
        $tocoor = $this->database->getCoor($enforce['vref']);
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
            $result = $this->database->query($q);
            $hero_f = $this->database->fetchArray($result);
            $hero_unit = $hero_f['unit'];
            $speeds[] = $GLOBALS[ 'u' . $hero_unit ]['speed'];
        }
        else {
            $enforce['hero'] = '0';
        }
    
        $artefact = count($this->database->getOwnUniqueArtefactInfo2($from['owner'], 2, 3, 0));
        $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($enforce['from'], 2, 1, 1));
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
        
        $time = round($this->generator->procDistanceTime($fromCor, $toCor, min($speeds), $enforce['from']) / $fastertroops);
    
        $foolartefact2 = $this->database->getFoolArtefactInfo(2, $enforce['from'], $from['owner']);
        
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
        
        $reference = $this->database->addAttack($enforce['from'], $enforce[ 'u' . $start ], $enforce[ 'u' . ($start + 1) ], $enforce[ 'u' . ($start + 2) ], $enforce[ 'u' . ($start + 3) ], $enforce[ 'u' . ($start + 4) ], $enforce[ 'u' . ($start + 5) ], $enforce[ 'u' . ($start + 6) ], $enforce[ 'u' . ($start + 7) ], $enforce[ 'u' . ($start + 8) ], $enforce[ 'u' . ($start + 9) ], $enforce['hero'], 2, 0, 0, 0, 0);
        $this->database->addMovement(MovementTypeSid::RETURNING, $enforce['vref'], $enforce['from'], $reference, time(), ($time + time()));
        $this->database->deleteReinf($enforce['id']);
    }
    
    private function sendTroops($post)
    {
        $data = $this->database->getA2b($post['timestamp_checksum'], $post['timestamp']);
    
        if ($this->session->tribe == '2') {
            $Gtribe = "1";
        }
        elseif ($this->session->tribe == '3') {
            $Gtribe = "2";
        }
        elseif ($this->session->tribe == '4') {
            $Gtribe = "3";
        }
        elseif ($this->session->tribe == '5') {
            $Gtribe = "4";
        } else {
            $Gtribe = "";
        }
        
        for ($i = 1; $i < 10; $i++) {
            if (isset($data[ 'u' . $i ])) {
                if ($data[ 'u' . $i ] > $this->village->unitarray[ 'u' . $Gtribe . $i ]) {
                    $this->form->addError("error", "You can't send more units than you have");
                    break;
                }
            
                if ($data[ 'u' . $i ] < 0) {
                    $this->form->addError("error", "You can't send negative units.");
                    break;
                }
            }
        }
        
        if ($data['u11'] > $this->village->unitarray['hero']) {
            $this->form->addError("error", "You can't send more units than you have");
        } elseif ($data['u11'] < 0) {
            $this->form->addError("error", "You can't send negative units.");
        }
        
        if ($this->form->returnErrors() > 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $_POST;
            header("Location: a2b.php");
        }
        else {
            if ($this->session->access != BANNED) {
            
                if ($this->session->tribe == 1) {
                    $u = "";
                }
                elseif ($this->session->tribe == 2) {
                    $u = "1";
                }
                elseif ($this->session->tribe == 3) {
                    $u = "2";
                }
                elseif ($this->session->tribe == 4) {
                    $u = "3";
                }
                else {
                    $u = "4";
                }
            
                $this->database->modifyUnit(
                    $this->village->wid,
                    [$u . "1", $u . "2", $u . "3", $u . "4", $u . "5", $u . "6", $u . "7", $u . "8", $u . "9", $u . $this->session->tribe . "0", "hero"],
                    [$data['u1'], $data['u2'], $data['u3'], $data['u4'], $data['u5'], $data['u6'], $data['u7'], $data['u8'], $data['u9'], $data['u10'], $data['u11']],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                );
            
                $query1 = $this->database->query('SELECT * FROM `' . TB_PREFIX . 'vdata` WHERE `wref` = ' . $this->database->realEscapeString($data['to_vid']));
                $data1 = $this->database->fetchAssoc($query1);
                $query2 = $this->database->query('SELECT * FROM `' . TB_PREFIX . 'users` WHERE `id` = ' . $data1['owner']);
                $data2 = $this->database->fetchAssoc($query2);
                $query11 = $this->database->query('SELECT * FROM `' . TB_PREFIX . 'vdata` WHERE `wref` = ' . $this->database->realEscapeString($this->village->wid));
                $data11 = $this->database->fetchAssoc($query11);
                $query21 = $this->database->query('SELECT * FROM `' . TB_PREFIX . 'users` WHERE `id` = ' . $data11['owner']);
                $data21 = $this->database->fetchAssoc($query21);
            
                $eigen = $this->database->getCoor($this->village->wid);
                $from = ['x' => $eigen['x'], 'y' => $eigen['y']];
                $ander = $this->database->getCoor($data['to_vid']);
                $to = ['x' => $ander['x'], 'y' => $ander['y']];
                $start = ($data21['tribe'] - 1) * 10 + 1;
                $end = ($data21['tribe'] * 10);
            
                $speeds = [];
                $scout = 1;
            
                //find slowest unit.
                for ($i = 1; $i <= 10; $i++) {
                    if (isset($data[ 'u' . $i ])) {
                        if ($data[ 'u' . $i ] != '' && $data[ 'u' . $i ] > 0) {
                            if (isset($unitarray)) {
                                reset($unitarray);
                            }
                            $unitarray = $GLOBALS[ "u" . (($this->session->tribe - 1) * 10 + $i) ];
                            $speeds[] = $unitarray['speed'];
                        }
                    }
                }
                if (isset($data['u11'])) {
                    if ($data['u11'] != '' && $data['u11'] > 0) {
                        $heroarray = $this->database->getHero($this->session->uid);
                        $herodata = $GLOBALS[ "u" . $heroarray[0]['unit'] ];
                        $speeds[] = $herodata['speed'];
                    }
                }
                $artefact = count($this->database->getOwnUniqueArtefactInfo2($this->session->uid, 2, 3, 0));
                $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($this->village->wid, 2, 1, 1));
                $artefact2 = count($this->database->getOwnUniqueArtefactInfo2($this->session->uid, 2, 2, 0));
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
                $time = round($this->generator->procDistanceTime($from, $to, min($speeds), 1) / $fastertroops);
                $foolartefact = $this->database->getFoolArtefactInfo(2, $this->village->wid, $this->session->uid);
                if (count($foolartefact) > 0) {
                    foreach ($foolartefact as $arte) {
                        if ($arte['bad_effect'] == 1) {
                            $time *= $arte['effect2'];
                        }
                        else {
                            $time /= $arte['effect2'];
                            $time = round($time);
                        }
                    }
                }
                $to_owner = $this->database->getVillageField($data['to_vid'], "owner");
                // Check if have WW owner have artefact Rivals great confusion or Artefact of the unique fool with that effect
                // If is a WW village you can target on WW , if is not a WW village catapults will target randomly.
                // Like it says : Exceptions are the WW which can always be targeted and the treasure chamber which can always be targeted, except with the unique artifact.
                // Fixed by Advocaite and Shadow
                $q = $this->database->query("SELECT vref FROM " . TB_PREFIX . "fdata WHERE f99t = '40' AND vref = " . $data['to_vid'] . "");
                $isThere = $this->database->numRows($q);
                if ($isThere > 0) {
                    $iswwvilla = 1;
                    $artefact_2 = count($this->database->getOwnUniqueArtefactInfo2($to_owner, 7, 3, 0));
                    $artefact1_2 = count($this->database->getOwnUniqueArtefactInfo2($data['to_vid'], 7, 1, 1));
                    $artefact2_2 = count($this->database->getOwnUniqueArtefactInfo2($to_owner, 7, 2, 0));
                    $foolartefact2 = $this->database->getFoolArtefactInfo(7, $data['to_vid'], $to_owner);
                    $good_artefact = 0;
                    if (count($foolartefact2) > 0) {
                        foreach ($foolartefact2 as $arte) {
                            if ($arte['bad_effect'] == 0) {
                                $good_artefact = 1;
                            }
                        }
                    }
                }
                else {
                    $artefact_2 = count($this->database->getOwnUniqueArtefactInfo2($to_owner, 7, 3, 0));
                    $artefact1_2 = count($this->database->getOwnUniqueArtefactInfo2($data['to_vid'], 7, 1, 1));
                    $artefact2_2 = count($this->database->getOwnUniqueArtefactInfo2($to_owner, 7, 2, 0));
                    $foolartefact2 = $this->database->getFoolArtefactInfo(7, $data['to_vid'], $to_owner);
                    $iswwvilla = 0;
                    $good_artefact = 0;
                    if (count($foolartefact2) > 0) {
                        foreach ($foolartefact2 as $arte) {
                            if ($arte['bad_effect'] == 0) {
                                $good_artefact = 1;
                            }
                        }
                    }
                }
            
                if (isset($post['ctar1'])) {
                    if ($artefact_2 > 0 or $artefact1_2 > 0 or $artefact2_2 > 0 or $good_artefact == 1) {
                        if ($post['ctar1'] != 40 or $post['ctar1'] != 27 and $iswwvilla == 1) {
                            $post['ctar1'] = 99;
                        }
                        else {
                            $post['ctar1'] = 99;
                        }
                    }
                    else {
                        $post['ctar1'] = $post['ctar1'];
                    }
                }
                else {
                    $post['ctar1'] = 0;
                }
                if (isset($post['ctar2'])) {
                    if ($artefact_2 > 0 or $artefact1_2 > 0 or $artefact2_2 > 0 or $good_artefact == 1) {
                        if ($post['ctar2'] != 40 or $post['ctar2'] != 27 and $iswwvilla == 1) {
                            $post['ctar2'] = 99;
                        }
                        else {
                            $post['ctar2'] = 99;
                        }
                    }
                    else {
                        $post['ctar2'] = $post['ctar2'];
                    }
                }
                else {
                    $post['ctar2'] = 0;
                }
                if (isset($post['spy'])) {
                    $post['spy'] = $post['spy'];
                }
                else {
                    $post['spy'] = 0;
                }
                $abdata = $this->database->getABTech($this->village->wid);
                $reference = $this->database->addAttack(($this->village->wid), $data['u1'], $data['u2'], $data['u3'], $data['u4'], $data['u5'], $data['u6'], $data['u7'], $data['u8'], $data['u9'], $data['u10'], $data['u11'], $data['type'], $post['ctar1'], $post['ctar2'], $post['spy'], $abdata['b1'], $abdata['b2'], $abdata['b3'], $abdata['b4'], $abdata['b5'], $abdata['b6'], $abdata['b7'], $abdata['b8']);
                $checkexist = $this->database->checkVilExist($data['to_vid']);
                $checkoexist = $this->database->checkOasisExist($data['to_vid']);
                if ($checkexist or $checkoexist) {
                    $this->database->addMovement(MovementTypeSid::REINFORCEMENT, $this->village->wid, $data['to_vid'], $reference, time(), ($time + time()));
                    if (($this->database->hasBeginnerProtection($this->village->wid) == 1) && ($checkexist)) {
                        $this->database->query("UPDATE " . TB_PREFIX . "users SET protect = 0 WHERE id = $this->session->uid");
                    }
                }
            
                if ($this->form->returnErrors() > 0) {
                    $_SESSION['errorarray'] = $this->form->getErrors();
                    $_SESSION['valuearray'] = $_POST;
                    header("Location: a2b.php");
                }
                header("Location: build.php?id=39");
            }
            else {
                header("Location: banned.php");
            }
        }
    }
    
    private function sendTroopsBack($post)
    {
        if ($this->session->access != BANNED) {
            $enforce = $this->database->getEnforceArray($post['ckey'], 0);
            $enforceoasis = $this->database->getOasisEnforceArray($post['ckey'], 0);
            
            if (
                ($enforce['from'] == $this->village->wid) ||
                ($enforce['vref'] == $this->village->wid) ||
                ($enforceoasis['conqured'] == $this->village->wid)
            ) {
                $to = $this->database->getVillage($enforce['from']);
                $Gtribe = "";
                if ($this->database->getUserField($to['owner'], 'tribe', 0) == '2') {
                    $Gtribe = "1";
                }
                elseif ($this->database->getUserField($to['owner'], 'tribe', 0) == '3') {
                    $Gtribe = "2";
                }
                elseif ($this->database->getUserField($to['owner'], 'tribe', 0) == '4') {
                    $Gtribe = "3";
                }
                elseif ($this->database->getUserField($to['owner'], 'tribe', 0) == '5') {
                    $Gtribe = "4";
                }
            
                for ($i = 1; $i < 10; $i++) {
                    if (isset($post[ 't' . $i ])) {
                        if ($i != 10) {
                            if ($post[ 't' . $i ] > $enforce[ 'u' . $Gtribe . $i ]) {
                                $this->form->addError("error", "You can't send more units than you have");
                                break;
                            }
                        
                            if ($post[ 't' . $i ] < 0) {
                                $this->form->addError("error", "You can't send negative units.");
                                break;
                            }
                        }
                    }
                    else {
                        $post[ 't' . $i . '' ] = '0';
                    }
                }
                
                if (isset($post['t11'])) {
                    if ($post['t11'] > $enforce['hero']) {
                        $this->form->addError("error", "You can't send more units than you have");
                    }
                
                    if ($post['t11'] < 0) {
                        $this->form->addError("error", "You can't send negative units.");
                    }
                }
                else {
                    $post['t11'] = '0';
                }
            
                if ($this->form->returnErrors() > 0) {
                    $_SESSION['errorarray'] = $this->form->getErrors();
                    $_SESSION['valuearray'] = $_POST;
                    header("Location: a2b.php");
                }
                else {
                
                    //change units
                    $start = ($this->database->getUserField($to['owner'], 'tribe', 0) - 1) * 10 + 1;
                    $end = ($this->database->getUserField($to['owner'], 'tribe', 0) * 10);
                
                    $j = '1';
                    for ($i = $start; $i <= $end; $i++) {
                        $this->database->modifyEnforce($post['ckey'], $i, $post[ 't' . $j . '' ], 0);
                        $j++;
                    }
                    $this->database->modifyEnforce($post['ckey'], 'hero', $post['t11'], 0);
                    $j++;
                    //get cord
                    $from = $this->database->getVillage($enforce['from']);
                    $fromcoor = $this->database->getCoor($enforce['from']);
                    $tocoor = $this->database->getCoor($enforce['vref']);
                    $fromCor = ['x' => $tocoor['x'], 'y' => $tocoor['y']];
                    $toCor = ['x' => $fromcoor['x'], 'y' => $fromcoor['y']];
                
                    $speeds = [];
                
                    //find slowest unit.
                    for ($i = 1; $i <= 10; $i++) {
                        if (isset($post[ 't' . $i ])) {
                            if ($post[ 't' . $i ] != '' && $post[ 't' . $i ] > 0) {
                                if (isset($unitarray)) {
                                    reset($unitarray);
                                }
                                $unitarray = $GLOBALS[ "u" . (($this->session->tribe - 1) * 10 + $i) ];
                                $speeds[] = $unitarray['speed'];
                            }
                            else {
                                $post[ 't' . $i . '' ] = '0';
                            }
                        }
                        else {
                            $post[ 't' . $i . '' ] = '0';
                        }
                    }
                    if (isset($post['t11'])) {
                        if ($post['t11'] != '' && $post['t11'] > 0) {
                            $qh = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $from['owner'] . "";
                            $resulth = $this->database->query($qh);
                            $hero_f = $this->database->fetchArray($resulth);
                            $hero_unit = $hero_f['unit'];
                            $speeds[] = $GLOBALS[ 'u' . $hero_unit ]['speed'];
                        }
                        else {
                            $post['t11'] = '0';
                        }
                    }
                    else {
                        $post['t11'] = '0';
                    }
                    $artefact = count($this->database->getOwnUniqueArtefactInfo2($this->session->uid, 2, 3, 0));
                    $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($this->village->wid, 2, 1, 1));
                    $artefact2 = count($this->database->getOwnUniqueArtefactInfo2($this->session->uid, 2, 2, 0));
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
                    $time = round($this->generator->procDistanceTime($fromCor, $toCor, min($speeds), 1) / $fastertroops);
                    $foolartefact2 = $this->database->getFoolArtefactInfo(2, $this->village->wid, $this->session->uid);
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
                    $reference = $this->database->addAttack($enforce['from'], $post['t1'], $post['t2'], $post['t3'], $post['t4'], $post['t5'], $post['t6'], $post['t7'], $post['t8'], $post['t9'], $post['t10'], $post['t11'], 2, 0, 0, 0, 0);
                    $this->database->addMovement(MovementTypeSid::RETURNING, $this->village->wid, $enforce['from'], $reference, time(), ($time + time()));
                    $technology->checkReinf($post['ckey']);
                
                    header("Location: build.php?id=39");
                }
            }
            else {
                $this->form->addError("error", "You cant change someones troops.");
                if ($this->form->returnErrors() > 0) {
                    $_SESSION['errorarray'] = $this->form->getErrors();
                    $_SESSION['valuearray'] = $_POST;
                    header("Location: a2b.php");
                }
            }
        }
        else {
            header("Location: banned.php");
        }
    }
    
    public function Settlers($post)
    {
        if ($this->session->access != BANNED) {
            $mode = CP;
            $total = count($this->database->getProfileVillages($this->session->uid));
            $need_cps = ${'cp' . $mode}[ $total + 1 ];
            $cps = $this->session->cp;
            $rallypoint = $this->database->getResourceLevel($this->village->wid);
            if ($rallypoint['f39'] > 0) {
                if ($cps >= $need_cps) {
                    $unit = ($this->session->tribe * 10);
                    $this->database->modifyResource($this->village->wid, 750, 750, 750, 750, 0);
                    $this->database->modifyUnit($this->village->wid, [$unit], [3], [0]);
                    $this->database->addMovement(MovementTypeSid::SETTLERS, $this->village->wid, $post['s'], 0, time(), time() + $post['timestamp']);
                    header("Location: build.php?id=39");
                
                    if ($this->form->returnErrors() > 0) {
                        $_SESSION['errorarray'] = $this->form->getErrors();
                        $_SESSION['valuearray'] = $_POST;
                        header("Location: a2b.php");
                    }
                }
                else {
                    header("Location: build.php?id=39");
                }
            }
            else {
                ResponseHelper::redirect(Routes::DORF1);
            }
        }
        else {
            header("Location: banned.php");
        }
    }
    
    public function Hero($uid,$all=0)
    {
        $heroarray = $this->database->getHero($uid, $all);
        $herodata = $GLOBALS[ "h" . $heroarray[0]['unit'] ];
    
        $h_atk = $herodata['atk'] + 5 * floor($heroarray[0]['attack'] * $herodata['atkp'] / 5);
        $h_di = $herodata['di'] + 5 * floor($heroarray[0]['defence'] * $herodata['dip'] / 5);
        $h_dc = $herodata['dc'] + 5 * floor($heroarray[0]['defence'] * $herodata['dcp'] / 5);
        $h_ob = 1 + 0.002 * $heroarray[0]['attackbonus'];
        $h_db = 1 + 0.002 * $heroarray[0]['defencebonus'];
    
        return ['heroid' => $heroarray[0]['heroid'], 'unit' => $heroarray[0]['unit'], 'atk' => $h_atk, 'di' => $h_di, 'dc' => $h_dc, 'ob' => $h_ob, 'db' => $h_db, 'health' => $heroarray[0]['health']];
    }
}
