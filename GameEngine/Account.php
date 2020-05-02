<?php

use App\Helpers\ResponseHelper;
use App\Models\User\User;
use App\Models\User\UserActivation;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                    			       ## 
##  Filename       Account.php                                                 ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.  		       ##
##  Fixed by:      InCube - double troops				       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                		       ##
##  Source code:   https://github.com/Shadowss/TravianZ		               ## 
##                                                                             ##
#################################################################################

class Account
{
    private $database;
    private $form;
    private $message;
    private $session;
    
    private $usernameFormat = '/[^0-9A-Za-z]/';

	public function __construct(\MYSQLi_DB $database, \Form $form, \Message $message, \Session $session)
    {
        $this->database = $database;
        $this->form     = $form;
        $this->message  = $message;
        $this->session  = $session;
	}

	public function Signup(Mailer $mailer, MyGenerator $generator)
    {
        $this->validateSignupData();

		if ($this->form->returnErrors() > 0) {
            $this->form->addError("invt", $_POST['invited']); // wft? todo check why
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $_POST;
            
            ResponseHelper::redirect('anmelden.php');
        }
		
        if (AUTH_EMAIL){
            $activationCode = $generator->generateRandStr(10);
            $verificationCode = $generator->generateRandStr(5);
            $activationId = (new UserActivation())
                ->insert(
                    $_POST['name'],
                    md5($_POST['pw']),
                    $_POST['email'],
                    $_POST['vid'],
                    $_POST['kid'],
                    $activationCode,
                    $verificationCode
                );
            if (!$activationId) {
                // todo handle db insert error if needed
            }
            
            $mailer->sendActivationMail($_POST['email'], $_POST['name'], $_POST['pw'], $activationCode);
            
            ResponseHelper::redirect("activate.php?id={$activationId}&q={$verificationCode}");
        } else {
            try {
                $uid = (new User())->create(
                    $_POST['name'],
                    md5($_POST['pw']),
                    $_POST['email'],
                    $_POST['vid']
                );
            } catch (\RuntimeException $exception) {
                //
            }
            
            // todo chech why cookies and `invided` is not set during activation
            setcookie("COOKUSR", $_POST['name'], time() + COOKIE_EXPIRE, COOKIE_PATH);
            setcookie("COOKEMAIL", $_POST['email'], time() + COOKIE_EXPIRE, COOKIE_PATH);
            $this->database->updateUserField($uid, "invited", $_POST['invited'], 1);

            $this->generateBase($_POST['kid'], $uid, $_POST['name']);
            
            ResponseHelper::redirect('login.php');
        }
	}
	
	private function validateSignupData(): void
    {
        if (!isset($_POST['name']) || trim($_POST['name']) == "") {
            $this->form->addError("name", USRNM_EMPTY);
        }
        
        if (!isset($_POST['pw']) || trim($_POST['pw']) == "") {
            $this->form->addError("pw", PW_EMPTY);
        }
    
        if (!isset($_POST['email'])) {
            $this->form->addError("email", EMAIL_EMPTY);
        }
    
        if(!isset($_POST['vid'])) {
            $this->form->addError("tribe", TRIBE_EMPTY);
        }
    
        if (!isset($_POST['agb'])) {
            $this->form->addError("agree",AGREE_ERROR);
        }
    
        $username = trim($_POST['name']);
        $password = trim($_POST['pw']);
        $email    = trim($_POST['email']);
        
        $this->validateSignupUsername($username);
        $this->validateSignupPassword($password, $username);
        $this->validateSignupEmail($email);
    }
    
    private function validateSignupUsername(string $username)
    {
        if (strlen($username) < USRNM_MIN_LENGTH) {
            $this->form->addError("name",USRNM_SHORT);
            return;
        }
        
        if (!USRNM_SPECIAL && preg_match($this->usernameFormat, $username)) {
            $this->form->addError("name",USRNM_CHAR);
            return;
        }
        
        if (USRNM_SPECIAL && preg_match("/[:,\\. \\n\\r\\t\\s\\<\\>]+/", $username)) {
            $this->form->addError("name",USRNM_CHAR);
            return;
        }
        
        if ($this->database->checkExist($username, 0) ||
            (new UserActivation())
                ->usernameExists($username)
        ) {
            $this->form->addError("name", USRNM_TAKEN);
            return;
        }
    }
    
    private function validateSignupPassword(string $password, string $username)
    {
        if (strlen($password) < PW_MIN_LENGTH) {
            $this->form->addError("pw", PW_SHORT);
            return;
        }
        
        if ($password == $username) {
            $this->form->addError("pw", PW_INSECURE);
            return;
        }
    }
    
    private function validateSignupEmail(string $email)
    {
        if (!$this->isValidEmail($email)) {
            $this->form->addError("email", EMAIL_INVALID);
            return;
        }
        
        if ($this->database->checkExist($email,1) ||
            (new UserActivation())
                ->emailExists($email)
        ) {
            $this->form->addError("email",EMAIL_TAKEN);
            return;
        }
    }
    
    private function isValidEmail($email)
    {
        $regexp="/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
        
        return (bool) preg_match($regexp, $email);
    }

	public function Activate()
    {
        if (!$this->isServerActive()) {
            ResponseHelper::redirect('activate.php'); // todo should redirect to new page `server_start_at`
        }
        
        $activationModel = new UserActivation();
        
        $activation = $activationModel->getActivation($_POST['id']); // непонятка, тут не факт что $_POST['id'], либо надо делать ->getById todo надо разобратся, что приезжает
        
        if ($activation['act'] != $_POST['id']) {
            ResponseHelper::redirect('activate.php?e=3');
        }
        
        try {
        $uid = (new User)->create(
            $activation['username'],
            $activation['password'],
            $activation['email'],
            $activation['tribe']
        );
        } catch (\RuntimeException $exception) {
            // todo handle db insert error if needed
        }
        
        $activationModel->delete($activation['username']);
        $this->generateBase($activation['kid'], $uid, $activation['username']);

        ResponseHelper::redirect('activate.php?e=2');
    }
    
    private function isServerActive()
    {
        return START_DATE < date('m/d/Y') ||
            (START_DATE == date('m/d/Y') && START_TIME <= date('H:i'));
    }

	public function Unreg()
    {
        $activationModel = new UserActivation();
        
        $activation = $activationModel->getById($_POST['id']);
        
        if (md5($_POST['pw']) != $activation['password']) {
            ResponseHelper::redirect('activate.php?e=3');
        }
        
        $activationModel->delete($activation['username']);
        
        ResponseHelper::redirect('anmelden.php');
    }

	public function Login()
    {
        $this->validateLoginData();
        if ($this->form->returnErrors() > 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $_POST;
            
            ResponseHelper::redirect('login.php');
        }
    
        $username = $this->database->realEscapeString($_POST['user']);
        $password = $_POST['pw'];
        
        $userId = $this->database->getUserField($username, 'id', 0);
        (new User())
            ->getVocationMode()
            ->remove($userId);

        if ($this->database->login($username, $password)) {
            $this->database->UpdateOnline("login", $username, time(), $userId);
        } elseif ($this->database->sitterLogin($username, $password)) {
            $this->database->UpdateOnline("sitter", $username, time(), $userId);
        }
        
        setcookie("COOKUSR", $username, time() + COOKIE_EXPIRE, COOKIE_PATH);
        $this->session->login($username);
    }
    
    private function validateLoginData(): void
    {
        if (!isset($_POST['user']) || $_POST['user'] == "") {
            $this->form->addError("user", LOGIN_USR_EMPTY);
        }
    
        if (!isset($_POST['pw']) || $_POST['pw'] == "") {
            $this->form->addError("pw", LOGIN_PASS_EMPTY);
        }
    
        $username = $this->database->realEscapeString($_POST['user']);
        $password = $_POST['pw'];
        
        if (!$this->database->checkExist($username, 0)) {
            $this->form->addError("user", USR_NT_FOUND);
            return;
        }
    
        if (!$this->database->login($username, $password) &&
            !$this->database->sitterLogin($username, $password)
        ) {
            $this->form->addError("pw", LOGIN_PW_ERROR);
            return;
        }
    
        if ($this->database->getUserField($username, "act", 1) != "") {
            $this->form->addError("activate", $username);
            return;
        }
    
        if ($this->database->getUserField($username, "vac_mode", 1) == 1 &&
            $this->database->getUserField($username, "vac_time", 1) > time()
        ) {
            $this->form->addError("vacation", "Vacation mode is still enabled");
            return;
        }
    }

	public function Logout()
    {
		unset($_SESSION['wid']);
		$this->database->activeModify(addslashes($this->session->username),1);
		$this->database->UpdateOnline("logout");
		$this->session->Logout();
	}
	
	private function generateBase($kid, $uid, $username)
    {
	
		if ($kid == 0) {
			$kid = rand(1,4);
		} else {
			$kid = $_POST['kid']; // $_POST['kid'] не факт что есть, если рега после активации, todo проверить кейс
		}

		$wid = $this->database->generateBase($kid,0);
		$this->database->setFieldTaken($wid);
		$this->database->addVillage($wid,$uid,$username,1);
		$this->database->addResourceFields($wid, $this->database->getVillageType($wid));
		$this->database->addUnits($wid);
		$this->database->addTech($wid);
		$this->database->addABTech($wid);
		$this->database->updateUserField($uid,"access",USER,1);
		
		$this->message->sendWelcome($uid,$username);
	}
}

$account = new Account($database, $form, $message,$session);

// routing
if (isset($_POST['ft'])) {
    switch($_POST['ft']) {
        case "a1":
            $account->Signup($mailer, $generator);
            break;
        case "a2":
            $account->Activate();
            break;
        case "a3":
            $account->Unreg();
            break;
        case "a4":
            $account->Login();
            break;
    }
}

if (isset($_GET['code'])) {
    $_POST['id'] = $_GET['code'];
    $this->Activate();
} else {
    if ($session->logged_in &&
        in_array("logout.php", explode("/",$_SERVER['PHP_SELF']))
    ) {
        $account->Logout();
    }
}
