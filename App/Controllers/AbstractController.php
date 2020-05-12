<?php

namespace App\Controllers;

use HalimonAlexander\Input\Input;

abstract class AbstractController
{
    protected $bundle;
    protected $input;
    
    public function __construct()
    {
        $this->input = new Input();
    }
    
    /**
     * @param string $__templateName Variable name with __ to ensure that will be not overrided by extract($variables)
     * @param array  $variables
     */
    protected function loadTemplate(string $__templateName, array $variables = [])
    {
        extract($variables);
        
        require_once join(
            DIRECTORY_SEPARATOR,
            [
                realpath(__DIR__ . "/../"),
                "Templates",
                $this->bundle,
                "{$__templateName}.php"
            ]
        );
    }
}
