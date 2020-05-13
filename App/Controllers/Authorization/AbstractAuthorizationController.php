<?php

namespace App\Controllers\Authorization;

use App\Controllers\AbstractController;
use HalimonAlexander\Registry\Registry;

class AbstractAuthorizationController extends AbstractController
{
    protected $bundle = 'Authorization';
    
    /** @var \GameEngine\Database\MysqliModel $database */
    protected $database;
    
    public function __construct()
    {
        parent::__construct();
    
        $registry = (Registry::getInstance());
    
        $this->database = $registry->get('database');
    }
}
