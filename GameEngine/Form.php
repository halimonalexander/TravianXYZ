<?php

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       Form.php                                                    ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################

class Form
{
    public $values = [];
    private $errors = [];
    
    public function __construct()
    {
        if (isset($_SESSION['errorarray']) && isset($_SESSION['valuearray'])) {
            $this->errors = $_SESSION['errorarray'];
            $this->values = $_SESSION['valuearray'];
            
            unset($_SESSION['errorarray']);
            unset($_SESSION['valuearray']);
        }
    }
    
    public function addError($field, $error): void
    {
        $this->errors[$field] = $error;
    }
    
    public function getError($field): string
    {
        return array_key_exists($field, $this->errors) ? $this->errors[$field] : '';
    }
    
    public function getValue($field): string
    {
        return array_key_exists($field, $this->values) ? $this->values[$field] : '';
    }
    
    public function getDiff($field, $cookie)
    {
        return array_key_exists($field, $this->values) && $this->values[ $field ] != $cookie ?
            $this->values[$field] :
            $cookie;
    }
    
    public function getRadio($field, $value)
    {
        return array_key_exists($field, $this->values) && $this->values[$field] == $value ?
            "checked" :
            '';
    }
    
    public function returnErrors()
    {
        return count($this->errors);
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
}
