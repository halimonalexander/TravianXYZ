<?php

use App\Helpers\ResponseHelper;
use GameEngine\{
    Alliance,
    Automation,
    Battle,
    Building,
    Form,
    Logging,
    Mailer,
    Market,
    Message,
    Multisort,
    MyGenerator,
    Database\MysqliModel,
    Profile,
    Ranking,
    Session,
    Technology,
    Units,
    Village,
};

ob_start(); // Enesure, that no more header already been sent error not showing up again
mb_internal_encoding("UTF-8"); // Add for utf8 varriables.

if (!file_exists('GameEngine/config.php') &&
    !file_exists('../GameEngine/config.php') &&
    !file_exists('../../GameEngine/config.php') &&
    !file_exists('../../config.php')
) {
    ResponseHelper::redirect('install/');
}

// loader
require_once "GameEngine/Data/buidata.php";
require_once "GameEngine/Data/cp.php";
require_once "GameEngine/Data/cel.php";
require_once "GameEngine/Data/resdata.php";
require_once "GameEngine/Data/unitdata.php";
require_once "GameEngine/Data/hero_full.php";

require_once "GameEngine/config.php";
require_once "GameEngine/Lang/" . LANG . ".php";

// classes
require_once 'autoloader.php';


$battle = new Battle();
$database = new MysqliModel();

// Protection is not a class, but depends on database. And as most of classes have business logic in constructor,
// so we should call it asap. For now let it bee so
require_once "GameEngine/Protection.php";

$mailer = new Mailer();
$form = new Form();
$generator = new MyGenerator();
$multisort = new Multisort();
$ranking = new Ranking();
$logging = new Logging();
$session = new Session();
$message = new Message();
$alliance = new Alliance();
$profile = new Profile();

if (isset($loadVillage)) {
    $technology = new Technology();
    $village = new Village();
    $building = new Building();
    $market = new Market();
    $units = new Units();
}

if (isset($loadVillage) || isset($loadAutomation)) {
    $automation = new Automation();
}