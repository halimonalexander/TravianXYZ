<?php

namespace GameEngine;

use App\Helpers\ResponseHelper;
use App\Models\User\UserActivity;
use App\Routes;
use GameEngine\Database\MysqliModel;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                                       ## 
##  Filename       Session.php                                                 ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.                 ##
##  Fixed by:      InCube - double troops                       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                               ##
##  Source code:   https://github.com/Shadowss/TravianZ                       ## 
##                                                                             ##
#################################################################################

class Session
{
    private $database;
    private $generator;
    private $logging;
    
    private $time;
    public $logged_in = false;
    public $referrer, $url;
    public $username, $uid, $access, $plus, $tribe, $isAdmin, $alliance, $gold, $oldrank, $gpack;
    public $bonus = 0;
    public $bonus1 = 0;
    public $bonus2 = 0;
    public $bonus3 = 0;
    public $bonus4 = 0;
    public $checker, $mchecker;
    public $userinfo = [];
    private $userarray = [];
    public $villages = [];
    
    /**
     * @var UserActivity
     */
    private $userActivity;
    
    public function __construct(MysqliModel $database, MyGenerator $generator, Logging $logging, bool $allowIndexPage)
    {
        $this->database = $database;
        $this->generator = $generator;
        $this->logging = $logging;
        
        $this->init($allowIndexPage);
    }
    
    private function init(bool $allowIndexPage)
    {
        $this->time = time();
        if (!isset($_SESSION))
            session_start();

        $this->logged_in = $this->checkLogin();

        if ($this->logged_in && TRACK_USR) {
            $this->userActivity = new UserActivity();
            $this->userActivity->setActive($this->username);
        }
    
        $this->referrer = isset($_SESSION['url']) ? $_SESSION['url'] : '/';
        $this->url = $_SESSION['url'] = $_SERVER['PHP_SELF'];
        $this->SurfControl($allowIndexPage);
    }

    public function Login(string $username)
    {
        $this->logged_in = true;
        $_SESSION['sessid'] = $this->generator->generateRandID();
        $_SESSION['username'] = $username;
        $_SESSION['checker'] = $this->generator->generateRandStr(3);
        $_SESSION['mchecker'] = $this->generator->generateRandStr(5);
        $_SESSION['qst'] = $this->database->getUserField($_SESSION['username'], "quest", 1);
        $selected_village = $this->database->getPlayersSelectedVillage($_SESSION['username']);
        
        if (!isset($_SESSION['wid']) || $_SESSION['wid'] == '') {
            $data = $selected_village != '' ?
                $this->database->getVillage($selected_village) :
                $this->database->getFirstPlayersVillage($_SESSION['username']);
            
            $_SESSION['wid'] = $data['wref'];
        }
        
        $this->PopulateVar();

        $this->logging->addLoginLog($this->uid, $_SERVER['REMOTE_ADDR']);
        $this->database->addActiveUser($_SESSION['username'], $this->time);
        $this->database->updateUserField($_SESSION['username'], "sessid", $_SESSION['sessid'], 0);

        header("Location: dorf1.php");
    }

    public function Logout()
    {
        $this->logged_in = false;
        $this->database->updateUserField($_SESSION['username'], "sessid", "", 0);
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        session_start();
    }

    public function changeChecker()
    {
        $this->checker = $_SESSION['checker'] = $this->generator->generateRandStr(3);
        $this->mchecker = $_SESSION['mchecker'] = $this->generator->generateRandStr(5);
    }

    private function checkLogin(): bool
    {
        if (!isset($_SESSION['username']) && !isset($_SESSION['sessid'])) {
            return false;
        }
        
        $this->PopulateVar();
        // $this->userActivity
        //     ->setActive($this->username);
    
        return true;
    }
    
    private function CheckHeroReal()
    {
        foreach ($this->villages as $myvill) {
            // check if hero is send as reinforcement
            if ($this->database->isHeroInReinforcement($myvill))
                return;
    
            // check if hero is on my account
            if ($this->database->isHeroInVillage($myvill))
                return;
    
            // check if hero is prisoner
            if ($this->database->isHeroInPrison($myvill))
                return;
    
            // check if hero is not in village (come back from attack , raid , etc.)
            if ($this->database->HeroNotInVil($myvill))
                return;
        }
        
        if ($this->database->getHeroDead($this->uid)) { // check if hero is already dead
            return;
        }
        elseif ($this->database->getHeroInRevive($this->uid)) { // check if hero is already in revive
            return;
        }
        elseif ($this->database->getHeroInTraining($this->uid)) { // check if hero is in training
            return;
        }
        
        $this->database->KillMyHero($this->uid);
    }

    private function PopulateVar()
    {
        $this->userarray = $this->userinfo = $this->database->getUserArray($_SESSION['username'], 0);
        
        $this->username = $this->userarray['username'];
        $this->uid = $_SESSION['id_user'] =  $this->userarray['id'];
        $this->gpack = $this->userarray['gpack'];
        $this->access = $this->userarray['access'];
        $this->plus = ($this->userarray['plus'] > $this->time);
        $this->goldclub = $this->userarray['goldclub'];
        $this->villages = $this->database->getVillagesID($this->uid);
        $this->tribe = $this->userarray['tribe'];
        $this->isAdmin = $this->access >= MODERATOR;
        $this->alliance = $_SESSION['alliance_user'] = $this->userarray['alliance'];
        $this->checker = $_SESSION['checker'];
        $this->mchecker = $_SESSION['mchecker'];
        $this->sit = $this->database->GetOnline($this->uid);
        $this->sit1 = $this->userarray['sit1'];
        $this->sit2 = $this->userarray['sit2'];
        $this->cp = floor($this->userarray['cp']);
        $this->gold = $this->userarray['gold'];
        $this->oldrank = $this->userarray['oldrank'];
        
        $_SESSION['ok'] = $this->userarray['ok'];
        
        if($this->userarray['b1'] > $this->time) {
            $this->bonus1 = 1;
        }
        if($this->userarray['b2'] > $this->time) {
            $this->bonus2 = 1;
        }
        if($this->userarray['b3'] > $this->time) {
            $this->bonus3 = 1;
        }
        if($this->userarray['b4'] > $this->time) {
            $this->bonus4 = 1;
        }
        
        $this->CheckHeroReal();
    }

    private function SurfControl($allowIndexPage)
    {
        $page = SERVER_WEB_ROOT ?
            $_SERVER['SCRIPT_NAME'] :
            array_pop(explode("/", $_SERVER['SCRIPT_NAME']));
        
        $unauthorisedPlayersAllowedPages = [
            "anleitung.php",
            "tutorial.php",
            // "login.php",
            // "activate.php",
            // "anmelden.php",
            "xaccount.php"
        ];
        if (!$allowIndexPage || !$this->logged_in) {
            $unauthorisedPlayersAllowedPages[] = "index.php";
        }
        
        if (!$this->logged_in && !in_array($page, $unauthorisedPlayersAllowedPages)) {
            ResponseHelper::redirect(Routes::LOGIN);
        } elseif ($this->logged_in && in_array($page, $unauthorisedPlayersAllowedPages)) {
            $gpLocation("dorf1.php");
        }
    }
}
