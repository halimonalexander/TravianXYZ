<?php

namespace App\Controllers\Map;

use App\Controllers\AbstractController;
use HalimonAlexander\Registry\Registry;

class AbstractMapController extends AbstractController
{
    protected $bundle = 'map';
    
    /** @var \GameEngine\Database\MysqliModel */
    protected $database;
    
    /** @var \GameEngine\Session */
    protected $session;
    
    public function __construct()
    {
        parent::__construct();
    
        $registry = Registry::getInstance();
    
        $this->database = $registry->get('database');
        $this->session = $registry->get('session');
    }
}
