<?php

namespace App\Models;

use HalimonAlexander\{
    PDODecorator\PDODecorator,
    Registry\Registry
};

abstract class AbstractModel
{
    /** @var PDODecorator */
    protected $db;
    
    /** @var string */
    protected $tablePrefix;
    
    public function __construct()
    {
        $registry = Registry::getInstance();
        
        $this->db = $registry->get('db');
        $this->tablePrefix = $registry->get('tablePrefix');
    }
}
