<?php

namespace GameEngine;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                                       ## 
##  Filename       Alliance.php                                                ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.                 ##
##  Fixed by:      InCube - double troops                       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                               ##
##  Source code:   https://github.com/Shadowss/TravianZ                       ## 
##                                                                             ##
#################################################################################

use App\Helpers\GlobalVariablesHelper;
use App\Helpers\ResponseHelper;
use App\Sids\Buildings;
use App\Sids\UserAccessSid;
use GameEngine\Database\MysqliModel;

class Alliance
{
    private $database;
    private $form;
    private $session;
    private $village;
    
    public $gotInvite = false;
    public $inviteArray = array();
    public $allianceArray = array();
    public $userPermArray = array();
    
    public function __construct(MysqliModel $database, Form $form, Session $session)
    {
        $this->database = $database;
        $this->form = $form;
        $this->session = $session;
    }
    
    public function setVillage(Village $village)
    {
        $this->village = $village;
    }
    
    public function procAlliance($get)
    {
        if($this->session->alliance != 0) {
            $this->allianceArray = $this->database->getAlliance($this->session->alliance);
            // Permissions Array
            // [id] => id [uid] => uid [alliance] => alliance [opt1] => X [opt2] => X [opt3] => X [opt4] => X [opt5] => X [opt6] => X [opt7] => X [opt8] => X
            $this->userPermArray = $this->database->getAlliPermissions($this->session->uid, $this->session->alliance);
        } else {
            $this->inviteArray = $this->database->getInvitation($this->session->uid);
            $this->gotInvite = count($this->inviteArray) == 0 ? false : true;
        }
        if(isset($get['a'])) {
            switch($get['a']) {
                case 2:
                    $this->rejectInvite($get);
                    break;
                case 3:
                    $this->acceptInvite($get);
                    break;
                default:
                    break;
            }
        }
        if(isset($get['o'])) {
            switch($get['o']) {
                case 4:
                    $this->delInvite($get);
                    break;
                default:
                    break;
            }
        }
    }
    
    public function procAlliForm($post)
    {
        if(isset($post['ft'])) {
            switch($post['ft']) {
                case "ali1":
                    $this->createAlliance($post);
                    break;
            }
            
        }
        if(isset($_POST['dipl']) and isset($_POST['a_name'])) {
            $this->changediplomacy($post);
        }
        
        if(isset($post['s'])) {
            if(isset($post['o'])) {
                switch($post['o']) {
                    case 1:
                        if(isset($_POST['a'])) {
                            $this->changeUserPermissions($post);
                        }
                        break;
                    case 2:
                        if(isset($_POST['a_user'])) {
                            $this->kickAlliUser($post);
                        }
                        break;
                    case 4:
                        if(isset($_POST['a']) && $_POST['a'] == 4) {
                            $this->sendInvite($post);
                        }
                        break;
                    case 3:
                        $this->updateAlliProfile($post);
                        break;
                    case 11:
                        $this->quitally($post);
                        break;
                    case 100:
                        $this->changeAliName($post);
                        break;
                }
            }
        }
    }
    
    /*****************************************
    Function to process of sending invitations
     *****************************************/
    public function sendInvite($post)
    {
        if($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        $UserData = $this->database->getUserArray(stripslashes($post['a_name']), 0);
        if($this->userPermArray['opt4'] == 0) {
            $this->form->addError("perm", NO_PERMISSION);
        }elseif(!isset($post['a_name']) || $post['a_name'] == "") {
            $this->form->addError("name1", NAME_EMPTY);
        }elseif(!$this->database->checkExist(stripslashes($post['a_name']), 0)) {
            $this->form->addError("name2", NAME_NO_EXIST."".stripslashes(stripslashes($post['a_name'])));
        }elseif($UserData['id'] == $this->session->uid) {
            $this->form->addError("name3", SAME_NAME);
        }elseif($this->database->getInvitation2($UserData['id'],$this->session->alliance)) {
            $this->form->addError("name4", $post['a_name'].ALREADY_INVITED);
        }elseif($UserData['alliance'] == $this->session->alliance) {
            $this->form->addError("name5", $post['a_name'].ALREADY_IN_ALLY);
        }else{
            // Obtenemos la informacion necesaria
            $aid = $this->session->alliance;
            // Insertamos invitacion
            $this->database->sendInvitation($UserData['id'], $aid, $this->session->uid);
            // Log the notice
            $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has invited  <a href="spieler.php?uid=' . $UserData['id'] . '">' . addslashes($UserData['username']) . '</a> into the alliance.');
        }
    }
    
    /*****************************************
    Function to reject an invitation
     *****************************************/
    private function rejectInvite($get)
    {
        if ($this->session->access === UserAccessSid::BANNED){
            ResponseHelper::redirect("banned.php");
        }
        
        foreach ($this->inviteArray as $invite) {
            if ($invite['id'] == $get['d']) {
                $this->database->removeInvitation($get['d']);
                $this->database->insertAlliNotice($invite['alliance'], '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has rejected the invitation.');
            }
        }
        
        ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$get['id']);
    }
    
    /*****************************************
    Function to del an invitation
     *****************************************/
    private function delInvite($get)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        $inviteArray = $this->database->getAliInvitations($this->session->alliance);
        foreach($inviteArray as $invite) {
            if($invite['id'] == $get['d']) {
                $invitename = $this->database->getUserArray($invite['uid'], 1);
                $this->database->removeInvitation($get['d']);
                $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has deleted the invitation for <a href="spieler.php?uid=' . $invitename['id'] . '">' . addslashes($invitename['username']) . '</a>.');
            }
        }
        
        ResponseHelper::redirect("allianz.php?delinvite");
    }
    
    /*****************************************
    Function to accept an invitation
     *****************************************/
    private function acceptInvite($get)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        foreach($this->inviteArray as $invite) {
            if($this->session->alliance == 0){
                if($invite['id'] == $get['d'] && $invite['uid'] == $this->session->uid) {
                    $memberlist = $this->database->getAllMember($invite['alliance']);
                    $alliance_info = $this->database->getAlliance($invite['alliance']);
                    if (count($memberlist) < $alliance_info['max']) {
                        $this->database->removeInvitation($get['d']);
                        $this->database->updateUserField($invite['uid'], "alliance", $invite['alliance'], 1);
                        $this->database->createAlliPermissions($invite['uid'], $invite['alliance'], '', '0', '0', '0', '0', '0', '0', '0', '0');
                        // Log the notice
                        $this->database->insertAlliNotice($invite['alliance'], '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has joined the alliance.');
                    } else {
                        $accept_error = 1;
                        $max = $alliance_info['max'];
                    }
                }
            }
        }
        
        if ($accept_error == 1) {
            $this->form->addError("ally_accept", "The alliance can contain only ".$max." peoples right now.");
        } else {
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=" . $get['id']);
        }
    }
    
    /*****************************************
    Function to create an alliance
     *****************************************/
    private function createAlliance($post)
    {
        $bid18 = GlobalVariablesHelper::getBuilding(Buildings::EMBASSY);
        
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if (!isset($post['ally1']) || $post['ally1'] == "") {
            $this->form->addError("ally1", ATAG_EMPTY);
        }
        
        if (!isset($post['ally2']) || $post['ally2'] == "") {
            $this->form->addError("ally2", ANAME_EMPTY);
        }
        
        if ($this->database->aExist($post['ally1'], "tag")) {
            $this->form->addError("ally1", ATAG_EXIST);
        }
        
        if ($this->database->aExist($post['ally2'], "name")) {
            $this->form->addError("ally2", ANAME_EXIST);
        }
        
        if ($this->form->returnErrors() != 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $post;
    
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=" . $post['id']);
        } else {
            $max = $bid18[$this->village->resarray['f' . $post['id']]]['attri'];
            $aid = $this->database->createAlliance($post['ally1'], $post['ally2'], $this->session->uid, $max);
            $this->database->updateUserField($this->session->uid, "alliance", $aid, 1);
            $this->database->procAllyPop($aid);
            // Asign Permissions
            $this->database->createAlliPermissions($this->session->uid, $aid, 'Alliance founder', '1', '1', '1', '1', '1', '1', '1', '1');
            // log the notice
            $this->database->insertAlliNotice($aid, 'The alliance has been founded by <a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a>.');
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=" . $post['id']);
        }
    }
    
    /*****************************************
    Function to change the alliance name
     *****************************************/
    private function changeAliName($get)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if(!isset($get['ally1']) || $get['ally1'] == "") {
            $this->form->addError("ally1", ATAG_EMPTY);
        }
        
        if(!isset($get['ally2']) || $get['ally2'] == "") {
            $this->form->addError("ally2", ANAME_EMPTY);
        }
        
        if($this->database->aExist($get['ally1'], "tag")) {
            $this->form->addError("tag", ATAG_EXIST);
        }
        
        if($this->database->aExist($get['ally2'], "name")) {
            $this->form->addError("name", ANAME_EXIST);
        }
        
        if($this->userPermArray['opt3'] == 0) {
            $this->form->addError("perm", NO_PERMISSION);
        }
        
        if($this->form->returnErrors() != 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $post;
            // ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']);
        } else {
            $this->database->setAlliName($this->session->alliance, $get['ally2'], $get['ally1']);
            // log the notice
            $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has changed the alliance name.');
        }
    }
    
    /*****************************************
    Function to create/change the alliance description
     *****************************************/
    private function updateAlliProfile($post)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if($this->userPermArray['opt3'] == 0) {
            $this->form->addError("perm", NO_PERMISSION);
        }
        
        if($this->form->returnErrors() != 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $post;
            // ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']);
        } else {
            $this->database->submitAlliProfile($this->session->alliance, $post['be2'], $post['be1']);
            // log the notice
            $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has changed the alliance description.');
        }
    }
    
    /*****************************************
    Function to change the user permissions
     *****************************************/
    private function changeUserPermissions($post)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if($this->userPermArray['opt1'] == 0) {
            $this->form->addError("perm", NO_PERMISSION);
        }
        
        if($this->form->returnErrors() != 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $post;
            // ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']);
        } else {
            $this->database->updateAlliPermissions($post['a_user'], $this->session->alliance, $post['a_titel'], $post['e1'], $post['e2'], $post['e3'], $post['e4'], $post['e5'], $post['e6'], $post['e7']);
            // log the notice
            $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has changed permissions.');
        }
    }
    
    /*****************************************
    Function to kick a user from alliance
     *****************************************/
    private function kickAlliUser($post)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        $UserData = $this->database->getUserArray($post['a_user'], 0);
        
        if($this->userPermArray['opt2'] == 0) {
            $this->form->addError("perm", NO_PERMISSION);
        } else if($UserData['id'] != $this->session->uid) {
            $this->database->updateUserField($post['a_user'], 'alliance', 0, 1);
            $this->database->deleteAlliPermissions($post['a_user']);
            $this->database->deleteAlliance($this->session->alliance);
            // log the notice
            $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $UserData['id'] . '">' . addslashes($post['a_user']) . '</a> has quit the alliance.');
            if ($this->database->isAllianceOwner($UserData['id'])) {
                $newowner = $this->database->getAllMember2($this->session->alliance);
                $newleader = $newowner['id'];
                $q = "UPDATE " . TB_PREFIX . "alidata set leader = " . $newleader . " where id = " . $this->session->alliance . "";
                $this->database->query($q);
                $this->database->updateAlliPermissions($newleader, 1, 1, 1, 1, 1, 1, 1, 1, 1);
                $this->updateMax($newleader);
            }
        }
    }
    
    /*****************************************
    Function to set forum link
     *****************************************/
    public function setForumLink($post)
    {
        if ($this->session->access == UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if (isset($post['f_link'])){
            $this->database->setAlliForumLink($this->session->alliance, $post['f_link']);
            ResponseHelper::redirect("allianz.php?s=5");
        }
    }
    
    /*****************************************
    Function to vote on forum survey
     *****************************************/
    public function Vote($post)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if (
            $this->database->checkSurvey($post['tid']) &&
            !$this->database->checkVote($post['tid'], $this->session->uid)
        ) {
            $survey = $this->database->getSurvey($post['tid']);
            $text = ''.$survey['voted'].','.$this->session->uid.',';
            $this->database->Vote($post['tid'], $post['vote'], $text);
        }
    
        ResponseHelper::redirect("allianz.php?s=2&fid2=".$post['fid2']."&pid=".$post['pid']."&tid=".$post['tid']);
    }
    
    /*****************************************
    Function to quit from alliance
     *****************************************/
    private function quitally($post)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if (!isset($post['pw']) || $post['pw'] == "") {
            $this->form->addError("pw1", PW_EMPTY);
        } elseif(md5($post['pw']) !== $this->session->userinfo['password']) {
            $this->form->addError("pw2", PW_ERR);
        } else {
            $this->database->updateUserField($this->session->uid, 'alliance', 0, 1);
            if($this->database->isAllianceOwner($this->session->uid)){
                $newowner = $this->database->getAllMember2($this->session->alliance);
                $newleader = $newowner['id'];
                $q = "UPDATE " . TB_PREFIX . "alidata set leader = ".$newleader." where id = ".$this->session->alliance."";
                $this->database->query($q);
                $this->database->updateAlliPermissions($newleader, 1, 1, 1, 1, 1, 1, 1, 1, 1);
                $this->updateMax($newleader);
            }
            $this->database->deleteAlliPermissions($this->session->uid);
            // log the notice
            $this->database->deleteAlliance($this->session->alliance);
            $this->database->insertAlliNotice($this->session->alliance, '<a href="spieler.php?uid=' . $this->session->uid . '">' . addslashes($this->session->username) . '</a> has quit the alliance.');
    
            ResponseHelper::redirect("spieler.php?uid=".$this->session->uid);
        }
    }
    
    private function changediplomacy($post)
    {
        if ($this->session->access === UserAccessSid::BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        $aName = $_POST['a_name'];
        $aType = (int)intval($_POST['dipl']);
        if (!$this->database->aExist($aName, "tag")) {
            $this->form->addError("name", "Alliance does not exist");
            return;
        }
        
        if ($this->database->getAllianceID($aName) == $this->session->alliance) {
            $this->form->addError("name", "You can not invite your own alliance");
            return;
        }
        
        if ($aType < 1 and $aType > 3) {
            $this->form->addError("name", "wrong choice made");
            return;
        }
        
        if ($this->database->diplomacyInviteCheck2($this->session->alliance, $this->database->getAllianceID($aName))) {
            $this->form->addError("name", "You have already sended them a invite");
        }
        
        $this->database->diplomacyInviteAdd($this->session->alliance, $this->database->getAllianceID($aName), $aType);
        if ($aType == 1) {
            $notice = "offer a confederation to";
        } elseif($aType == 2) {
            $notice = "offer non-aggression pact to";
        } elseif($aType == 3) {
            $notice = "declare war on";
        }
        
        $this->database->insertAlliNotice($this->session->alliance, '<a href="allianz.php?aid=' . $this->session->alliance . '">' . $this->database->getAllianceName($this->session->alliance) . '</a> '. $notice .' <a href="allianz.php?aid=' . $this->database->getAllianceID($aName) . '">' . $aName . '</a>.');
        $this->form->addError("name", "Invite sended");
    }
    
    private function updateMax($leader)
    {
        $bid18 = GlobalVariablesHelper::getBuilding(Buildings::EMBASSY);
        
        $q = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata where leader = $leader");
        if ($this->database->numRows($q) == 0) {
            return;
        }
        
        $villages = $this->database->getVillagesID2($leader);
        $max = 0;
        foreach ($villages as $village) {
            $field = $this->database->getResourceLevel($village['wref']);
            
            for ($i = 19; $i <= 40; $i++) {
                if ($field['f' . $i . 't'] == 18) {
                    $level = $field['f' . $i];
                    $attri = $bid18[$level]['attri'];
                }
            }
            if ($attri > $max) {
                $max = $attri;
            }
        }
        
        $q = "UPDATE " . TB_PREFIX . "alidata set max = $max where leader = $leader";
        $this->database->query($q);
    }
}


