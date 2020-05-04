<?php

use App\Helpers\ResponseHelper;

ob_start(); // Enesure, that no more header already been sent error not showing up again
mb_internal_encoding("UTF-8"); // Add for utf8 varriables.

if (!file_exists('GameEngine/config.php') &&
    !file_exists('../GameEngine/config.php') &&
    !file_exists('../../GameEngine/config.php') &&
    !file_exists('../../config.php')
) {
    ResponseHelper::redirect('install/');
}

// `auto`loader
require_once "GameEngine/Data/buidata.php";
require_once "GameEngine/Data/cp.php";
require_once "GameEngine/Data/cel.php";
require_once "GameEngine/Data/resdata.php";
require_once "GameEngine/Data/unitdata.php";
require_once "GameEngine/Data/hero_full.php";

require_once "GameEngine/config.php";
require_once "GameEngine/Lang/" . LANG . ".php";

// classes
require_once "GameEngine/Battle.php";
if (class_exists(Battle::class)) {
    $battle = new Battle();
}
require_once "GameEngine/Database/db_MYSQLi.php";
$database = new MYSQLi_DB();

// Protection is not a class, but depends on database. And as most of classes have business logic in constructor,
// so we should call it asap. For now let it bee so
require_once "GameEngine/Protection.php";

require_once "GameEngine/Mailer.php";
if (class_exists(Mailer::class)) {
    $mailer = new Mailer();
}

require_once "GameEngine/Form.php";
if (class_exists(Form::class)) {
    $form = new Form();
}

require_once "GameEngine/Generator.php";
if (class_exists(MyGenerator::class)) {
    $generator = new MyGenerator();
}

require_once "GameEngine/Multisort.php";
if (class_exists(multiSort::class)) {
    $multisort = new multiSort();
}

require_once "GameEngine/Ranking.php";
if (class_exists(Ranking::class)) {
    $ranking = new Ranking();
}

require_once "GameEngine/Logging.php";
if (class_exists(Logging::class)) {
    $logging = new Logging;
}

require_once "GameEngine/Session.php";
if (class_exists(Session::class)) {
    $session = new Session();
}

require_once "GameEngine/Message.php";
if (class_exists(Message::class)) {
    $message = new Message();
}

require_once "GameEngine/Alliance.php";
if (class_exists(Alliance::class)) {
    $alliance = new Alliance();
}

require_once "GameEngine/Profile.php";
if (class_exists(Profile::class)) {
    $profile = new Profile();
}


if (isset($loadVillage)) {
    require_once "GameEngine/Technology.php";
    if (class_exists(Technology::class)) {
        $technology = new Technology();
    }
    
    require_once "GameEngine/Village.php";
    if (class_exists(Village::class)) {
        $village = new Village();
    }
    
    require_once "GameEngine/Building.php";
    if (class_exists(Building::class)) {
        $building = new Building();
    }
    
    require_once "GameEngine/Market.php";
    if (class_exists(Market::class)) {
        $market = new Market();
    }
    
    require_once "GameEngine/Units.php";
    if (class_exists(Units::class)) {
        $units = new Units();
    }
    

}

if (isset($loadVillage) || isset($loadAutomation)) {
    require_once "GameEngine/Automation.php";
}
if (class_exists(Automation::class)) {
    $automation = new Automation();
}