<?php

if(!isset($_SESSION))
    session_start();

$gameinstall = 1;

include ("../../GameEngine/config.php");
include '../../vendor/autoload.php';
include '../../autoloader.php';
$database = new \GameEngine\Database\MysqliModel();
include("../../GameEngine/Protection.php");
include ("../../GameEngine/Admin/database.php");

// create db record on `odata`
$database->populateOasisdata();

// init record for oasis' units in `units`
$database->populateOasis();

// fill with animals
$database->populateOasisUnits2();

header("Location: ../index.php?s=6");
