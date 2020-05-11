<?php

namespace App\Controllers\Authorization;

use HalimonAlexander\Registry\Registry;
use App\{Controllers\AbstractController, Helpers\ResponseHelper, Models\User\User, Models\User\UserActivation, Routes};
use RuntimeException;

class LoginController extends AbstractController
{
    /** @var \GameEngine\Database\MysqliModel */
    private $database;
    
    /** @var \GameEngine\Form */
    private $form;
    
    /** @var \GameEngine\Session */
    private $session;
    
    public function __construct()
    {
        parent::__construct();
        
        $registry = (Registry::getInstance());
    
        $this->database = $registry->get('database');
        $this->form     = $registry->get('form');
        $this->session  = $registry->get('session');
    }
    
    public function loginAction()
    {
        if (isset($_GET['del_cookie'])) {
            setcookie("COOKUSR","",time()-3600*24,"/");
            header(Routes::LOGIN);
        }
    
        if (!isset($_COOKIE['COOKUSR'])) {
            $_COOKIE['COOKUSR'] = "";
        }
    
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_SESSION['csrf']) || $_SESSION['csrf'] !== $_POST['csrf'])
                throw new RuntimeException( 'CSRF attack' );
        }
    
        $_SESSION['csrf'] = sha1(microtime());
    
        $stime = strtotime(START_DATE) - strtotime(date('m/d/Y')) + strtotime(START_TIME);
    
        $this->loadTemplate('login', [
            'form' => $this->form,
            'title' => SERVER_NAME,
            'gpLocation' => GP_LOCATE,
        ]);
    }
    
    public function loginHandler()
    {
        $this->validateLoginData();
        if ($this->form->returnErrors() > 0) {
            $_SESSION['errorarray'] = $this->form->getErrors();
            $_SESSION['valuearray'] = $_POST;
        
            ResponseHelper::redirect(Routes::LOGIN);
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
        if (empty($this->input->post('user'))) {
            $this->form->addError("user", LOGIN_USR_EMPTY);
        }
        
        if (empty($this->input->post('pw'))) {
            $this->form->addError('pw', LOGIN_PASS_EMPTY);
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
    
    public function logoutAction()
    {
        unset($_SESSION['wid']);
        $this->database->activeModify(addslashes($this->session->username),1);
        $this->database->UpdateOnline("logout");
        $this->session->Logout();
        
        $this->loadTemplate('logout', [
            'start' => \App\Helpers\TraceHelper::getTimer(),
            'form' => $this->form,
            'title' => SERVER_NAME,
            'gpLocation' => GP_LOCATE,
            'gpLocationExtra' => ($this->session->gpack == null || GP_ENABLE == false) ? GP_LOCATE : $this->session->gpack,
        ]);
    }
}
