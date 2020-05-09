<?php

namespace App\Controllers;

use HalimonAlexander\Input\Input;

abstract class AbstractController
{
    protected $input;
    
    public function __construct()
    {
        $this->input = new Input();
    }
    
    protected function loadTemplate(string $template, array $variables = [])
    {
        extract($variables);
        
        require_once __DIR__ . "/../Templates/{$template}.php";
    }
}
