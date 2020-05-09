<?php

namespace App\Controllers\Authorization;

use GameEngine\Mailer;
use GameEngine\MyGenerator;
use HalimonAlexander\Registry\Registry;
use App\{Controllers\AbstractController, Helpers\ResponseHelper, Models\User\User, Models\User\UserActivation, Routes};

class RegistrationController extends AbstractController
{
    use AccountInitiateTrait;
    
    /** @var \GameEngine\Database\MysqliModel $database */
    private $database;
    
    /** @var \GameEngine\Form $form */
    private $form;
    
    private $usernameFormat = '/[^0-9A-Za-z]/';
    
    
    public function __construct()
    {
        parent::__construct();
    
        $registry = (Registry::getInstance());
    
        $this->database = $registry->get('database');
        $this->form     = $registry->get('form');
    }
    
    public function registerAction()
    {
        $invited = (isset($_GET['uid'])) ?
            filter_var($_GET['uid'], FILTER_SANITIZE_NUMBER_INT) :
            $this->form->getError('invt');
        
        $this->loadTemplate('register', [
            'invited' => $invited,
            'form' => $this->form,
            'title' => SERVER_NAME,
            'gpLocation' => GP_LOCATE,
        ]);
    }
    
    public function registerHandler(Mailer $mailer, MyGenerator $generator)
    {
        $this->validateSignupData();
        
        if ($this->form->returnErrors() > 0) {
            $this->form->addError("invt", $_POST['invited']); // wft? todo check why
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $_POST;
            
            ResponseHelper::redirect(Routes::REGISTER);
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
            
            ResponseHelper::redirect("activate?id={$activationId}&q={$verificationCode}");
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
            
            ResponseHelper::redirect(Routes::LOGIN);
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
}
