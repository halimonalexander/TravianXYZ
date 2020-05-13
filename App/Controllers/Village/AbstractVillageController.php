<?php

namespace App\Controllers\Village;

use App\Controllers\AbstractController;
use App\Helpers\ResponseHelper;
use App\Models\User\User;
use HalimonAlexander\Registry\Registry;

class AbstractVillageController extends AbstractController
{
    protected $bundle = 'village';
    
    /** @var \GameEngine\Database\MysqliModel  */
    protected $database;
    
    /** @var \GameEngine\Session  */
    protected $session;
    
    public function __construct()
    {
        parent::__construct();
    
        $registry = Registry::getInstance();
        
        $this->database = $registry->get('database');
        $this->session  = $registry->get('session');
        
        if (isset($_GET['ok'])) {
            $this->database->updateUserField($this->session->uid, 'ok', '0', '1');
            $_SESSION['ok'] = '0';
        }
    
        if (isset($_GET['newdid'])) {
            $newVillageId = (int) $_GET['newdid'];
            $_SESSION['wid'] = $newVillageId;
        
            (new User())
                ->setSelectedVillage($newVillageId, $this->session->uid);
        
            ResponseHelper::redirect($_SERVER['PHP_SELF']);
        }
        
        /** @var \GameEngine\Building $building */
        $building = $registry->get('building');
        $building->procBuild($_GET);
    }
}
