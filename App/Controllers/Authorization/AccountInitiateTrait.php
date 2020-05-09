<?php

namespace App\Controllers\Authorization;

/**
 * Trait AccountInitiateTrait
 *
 * @package App\Controllers\Authorization
 *
 * @property-read \MYSQLi_DB $database
 */
trait AccountInitiateTrait
{
    private function generateBase($kid, $uid, $username)
    {
        if ($kid == 0) {
            $kid = rand(1,4);
        } else {
            $kid = $_POST['kid']; // $_POST['kid'] не факт что есть, если рега после активации, todo проверить кейс
        }
        
        $wid = $this->database->generateBase($kid,0);
        $this->database->setFieldTaken($wid);
        $this->database->addVillage($wid,$uid,$username,1);
        $this->database->addResourceFields($wid, $this->database->getVillageType($wid));
        $this->database->addUnits($wid);
        $this->database->addTech($wid);
        $this->database->addABTech($wid);
        $this->database->updateUserField($uid,"access",USER,1);
        
        $message = new \Message();
        $message->sendWelcome($uid,$username);
    }
}
