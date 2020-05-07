<?php

namespace GameEngine;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       Profile.php                                                 ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################

use App\Helpers\ResponseHelper;
use GameEngine\Database\MysqliModel;

class Profile 
{
    private $database;
    private $form;
    private $session;
    
    public function __construct(MysqliModel $database, Form $form, Session $session)
    {
        $this->database = $database;
        $this->form = $form;
        $this->session = $session;
    }
    
    public function procProfile($post)
    {
		if (isset($post['ft'])) {
            if ($this->session->access != BANNED) {
                ResponseHelper::redirect("banned.php");
            }
            
            switch ($post['ft']) {
                case "p1":
                    $this->updateProfile($post);
                    break;
                case "p3":
                    $this->updateAccount($post);
                    break;
                case "p4":
                    // Vacation mode - by advocaite and Shadow
                    $this->setvactionmode($post);
                    break;
            }
		}
		
		if (isset($post['s'])) {
			switch($post['s']) {
                case "4":
                    if ($this->session->access != BANNED) {
                        $this->gpack($post);
                    }
                    else {
                        ResponseHelper::redirect("banned.php");
                    }
                    break;
            }
		}
	}

	public function procSpecial($get)
    {
		if (isset($get['e'])) {
            if ($this->session->access == BANNED) {
                ResponseHelper::redirect("banned.php");
            }
			switch ($get['e']) {
				case 2:
                    $this->removeMeSit($get);
                    break;
				case 3:
                    $this->removeSitter($get);
                    break;
				case 4:
                    $this->cancelDeleting($get);
                    break;
			}
		}
	}

	private function updateProfile($post)
    {
		$birthday = $post['jahr'].'-'.$post['monat'].'-'.$post['tag'];
		$this->database->submitProfile($this->database->RemoveXSS($post['uid']),$this->database->RemoveXSS($post['mw']),$this->database->RemoveXSS($post['ort']),$this->database->RemoveXSS($birthday),$this->database->RemoveXSS($post['be2']),$this->database->RemoveXSS($post['be1']));
		$varray = $this->database->getProfileVillages($post['uid']);
			for($i=0;$i<=count($varray)-1;$i++) {
				$k = trim($post['dname'.$i]);
				$name = preg_replace("/[^a-zA-Z0-9_-\s]/", "", $k);
				$this->database->setVillageName($this->database->RemoveXSS($varray[$i]['wref']),$name);
        $this->database->setVillageName($this->database->RemoveXSS($varray[$i]['wref']),$k);
		}  
		ResponseHelper::redirect("spieler.php?uid=".$post['uid']);
	}

	private function gpack($post)
    {
		$this->database->gpack($this->database->RemoveXSS($this->session->uid),$this->database->RemoveXSS($post['custom_url']));
		
		ResponseHelper::redirect("spieler.php?uid=".$this->session->uid);
	}
	
		/*******************************************************
		Function to vacation mode - by advocaite and Shadow
		References:
		********************************************************/

	private function setvactionmode($post)
    {
		$set =false;
		if($post['vac'] && $post['vac_days'] >=2 && $post['vac_days'] <=14) {
		$this->database->setvacmode($post['uid'],$post['vac_days']);
		$set =true;
		}
		else {
		echo "Minimum days is 2";die();exit();
		}
		if($set){
        unset($_SESSION['wid']);
		$this->database->activeModify(addslashes($this->session->username),1);
		$this->database->UpdateOnline("logout");
		$this->session->Logout();
		ResponseHelper::redirect("login.php");
		}else{
		ResponseHelper::redirect("spieler.php?s=5");
		}
    }

		/*******************************************************
		Function to vacation mode - by advocaite and Shadow
		References:
		********************************************************/

	private function updateAccount($post)
    {
		if($post['pw2'] == $post['pw3']) {
			if($this->database->login($this->session->username,$post['pw1'])) {
				if ($_POST['uid'] != $this->session->uid){
                      			die("Hacking Attempr");
                		} else {
				$this->database->updateUserField($post['uid'],"password",md5($post['pw2']),1);
			}
			}
			else {
				$this->form->addError("pw",LOGIN_PW_ERROR);
			}
		}
		else {
			$this->form->addError("pw",PASS_MISMATCH);
		}
		if($post['email_alt'] == $this->session->userinfo['email']) {
			$this->database->updateUserField($post['uid'],"email",$post['email_neu'],1);
		}
		else {
			$this->form->addError("email",EMAIL_ERROR);
		}
		if($post['del'] && md5($post['del_pw']) == $this->session->userinfo['password']) {
				$this->database->setDeleting($post['uid'],0);
		}
		else {
			$this->form->addError("del",PASS_MISMATCH);
		}
		
		if($post['v1'] != "") {
			$sitid = $this->database->getUserField($post['v1'],"id",1);
			if($sitid == $this->session->userinfo['sit1'] || $sitid == $this->session->userinfo['sit2']) {
				$this->form->addError("sit",SIT_ERROR);
			}
			else if($sitid != $this->session->uid){
				if($this->session->userinfo['sit1'] == 0) {
					$this->database->updateUserField($post['uid'],"sit1",$sitid,1);
				}
				else if($this->session->userinfo['sit2'] == 0) {
					$this->database->updateUserField($post['uid'],"sit2",$sitid,1);
				}
			}
		}
		$_SESSION['errorarray'] = $this->form->getErrors();
		ResponseHelper::redirect("spieler.php?s=3");
	}

	private function removeSitter($get)
    {
		if($get['a'] == $this->session->checker) {
			if($this->session->userinfo['sit'.$get['type']] == $get['id']) {
				$this->database->updateUserField($this->session->uid,"sit".$get['type'],0,1);
			}
			$this->session->changeChecker();
		}
		ResponseHelper::redirect("spieler.php?s=".$get['s']);
	}

	private function cancelDeleting($get)
    {
		$this->database->setDeleting($get['id'],1);
		ResponseHelper::redirect("spieler.php?s=".$get['s']);
	}

	private function removeMeSit($get)
    {
		if($get['a'] == $this->session->checker) {
			$this->database->removeMeSit($get['id'],$this->session->uid);
			$this->session->changeChecker();
		}
		ResponseHelper::redirect("spieler.php?s=".$get['s']);
	}
}


