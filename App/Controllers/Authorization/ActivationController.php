<?php

namespace App\Controllers\Authorization;

use HalimonAlexander\Registry\Registry;
use App\{
    Helpers\ResponseHelper,
    Models\User\User,
    Models\User\UserActivation,
    Routes
};

class ActivationController extends AbstractAuthorizationController
{
    use AccountInitiateTrait;
    
    public function activateAction()
    {
        $variables = [
            'title' => SERVER_NAME,
            'gpLocation' => GP_LOCATE,

        ];
        
        if (!$this->isServerActive()) {
            $variables['template'] = 'serverClosed';
            $this->loadTemplate('activation', $variables);
            return;
        }
        
        if (isset($_GET['e'])) {
            switch ($_GET['e']) {
                case 1:
                    $template = 'delete';
                    break;
                case 2:
                    $template = 'activated';
                    break;
                case 3:
                    unset($_SESSION['errorarray']);
                    
                    $template = 'cantfind';
                    break;
                default:
                    $template = '';
            }
            
            if (!empty($template)) {
                $variables['template'] = $template;
                $this->loadTemplate('activation', $variables);
                return;
            }
        }
        
        if (isset($_GET['id']) && isset($_GET['c'])) {
            $c = $this->database->getActivateField($_GET['id'], "email", 0);
            
            if ($_GET['c'] == (Registry::getInstance())->get('generator')->encodeStr($c, 5)) {
                $template = 'delete';
            } else {
                $template = 'activate';
            }
        } else {
            $template = 'activate';
        }
    
        $variables['template'] = $template;
        
        if ($template == 'activate') {
            if (isset($_GET['id']) && isset($_GET['q'])) {
                $act2 = $this->database->getActivateField($_GET['id'], "act2", 0);
                if ($act2 == $_GET['q']){
                    $variables['name'] = $this->database->getActivateField($_GET['id'], "username", 0);
                    $variables['email'] = $this->database->getActivateField($_GET['id'], "email", 0);
                } else {
                    $variables['template'] = 'activateInvalideSecureCode';
                }
            } else {
                $variables['template'] = 'activateInvalideSecureCode';
            }
        }
    
        $this->loadTemplate('activation', $variables);
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
