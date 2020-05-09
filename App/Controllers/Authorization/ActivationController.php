<?php

namespace App\Controllers\Authorization;

use HalimonAlexander\Registry\Registry;
use App\{Controllers\AbstractController, Helpers\ResponseHelper, Models\User\User, Models\User\UserActivation, Routes};

class ActivationController extends AbstractController
{
    use AccountInitiateTrait;
    
    /** @var \GameEngine\Database\MysqliModel $database */
    private $database;
    
    public function __construct()
    {
        parent::__construct();
    
        $registry = (Registry::getInstance());
    
        $this->database = $registry->get('database');
    }
    
    public function activateAction()
    {
        $this->loadTemplate('activation', [
            'title' => SERVER_NAME,
            'gpLocation' => GP_LOCATE,
        ]);
    }
    
    public function activateHandler()
    {
        if (!$this->isServerActive()) {
            ResponseHelper::redirect(Routes::ACTIVATE); // todo should redirect to new page `server_start_at`
        }
    
        if (isset($_GET['code'])) {
            $_POST['id'] = $_GET['code'];
        }
        
        $activationModel = new UserActivation();
        
        $activation = $activationModel->getActivation($_POST['id']);
        
        if ($activation['act'] != $_POST['id']) {
            ResponseHelper::redirect(Routes::ACTIVATE . '?e=3');
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
        
        ResponseHelper::redirect(Routes::ACTIVATE . '?e=2');
    }
    
    private function isServerActive()
    {
        return START_DATE < date('m/d/Y') ||
            (START_DATE == date('m/d/Y') && START_TIME <= date('H:i'));
    }
    
    public function deactivateHandler()
    {
        $activationModel = new UserActivation();
        
        $activation = $activationModel->getById($_POST['id']);
        
        if (md5($_POST['pw']) != $activation['password']) {
            ResponseHelper::redirect(Routes::ACTIVATE . '?e=3');
        }
        
        $activationModel->delete($activation['username']);
        
        ResponseHelper::redirect(Routes::REGISTER);
    }
}
