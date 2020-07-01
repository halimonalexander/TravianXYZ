<?php

namespace GameEngine\Automation\Helpers;

use GameEngine\Database\MysqliModel;

class OasisHelper
{
    private $database;

    public function __construct(MysqliModel $database)
    {
        $this->database = $database;
    }

    public function oasisResourcesProduce()
    {
        $time = time();
        $q = "SELECT * FROM ".TB_PREFIX."odata WHERE wood < 800 OR clay < 800 OR iron < 800 OR crop < 800";
        $array = $this->database->query($q)->fetch_all(\MYSQLI_ASSOC);
        foreach ($array as $getoasis) {
            $oasiswood = $getoasis['wood'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);
            $oasisclay = $getoasis['clay'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);
            $oasisiron = $getoasis['iron'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);
            $oasiscrop = $getoasis['crop'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);

            if ($oasiswood > $getoasis['maxstore']) {
                $oasiswood = $getoasis['maxstore'];
            }
            if ($oasisclay > $getoasis['maxstore']) {
                $oasisclay = $getoasis['maxstore'];
            }
            if ($oasisiron > $getoasis['maxstore']) {
                $oasisiron = $getoasis['maxstore'];
            }
            if ($oasiscrop > $getoasis['maxcrop']) {
                $oasiscrop = $getoasis['maxcrop'];
            }

            $q = "UPDATE " . TB_PREFIX . "odata SET wood = {$oasiswood}, clay = {$oasisclay}, iron = {$oasisiron}, crop = {$oasiscrop} WHERE wref = {$getoasis['wref']}";
            $this->database->query($q);
            $this->database->updateOasis($getoasis['wref']);
        }
    }

    public function regenerateOasisTroops()
    {
        $time = time();
        $time2 = \NATURE_REGTIME * $this->getNatureRegenerationKoef();
        $q = "SELECT * FROM " . \TB_PREFIX . "odata where conqured = 0 and lastupdated2 + {$time2} < {$time}";
        $array = $this->database->query($q)->fetch_all(\MYSQLI_ASSOC);
        foreach($array as $oasis) {
            $this->database->populateOasisUnits($oasis['wref'],$oasis['high']);
            $this->database->updateOasis2($oasis['wref'], $time2);
        }
    }

    private function getNatureRegenerationKoef(): int
    {
        $gamesday = time() - \COMMENCE;
        if ($gamesday < 3600*24*10) { //10 day
            return 20;
        } elseif ($gamesday < 3600*24*20) { //20 day
            return 15;
        } elseif ($gamesday < 3600*24*30) { //30 day
            return 10;
        } elseif ($gamesday < 3600*24*40) { //40 day
            return 5;
        } elseif ($gamesday < 3600*24*50) { //50 day
            return 3;
        } elseif ($gamesday < 3600*24*60) { //60 day
            return 2;
        } else {
            return 1;
        }
    }
}
