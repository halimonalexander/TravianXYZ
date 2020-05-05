<?php
#########################################################
## Filename addUsers.php                               ##
## Created by: KFCSpike                                ##
## Contributors: KFCSpike                              ##
## Improve by: ronix                                   ##
## License: TravianZ Project                           ##
## Copyright: TravianZ (c) 2014. All rights reserved.  ##
#########################################################

use App\Models\User\User;
use App\Sids\Buildings;
use App\Sids\Tribe;

include_once("../../config.php");
$loadAutomation = true;
include_once("../../../tempOldLoader.php");

$wgarray = [800, 1200, 1700, 2300, 3100, 4000, 5000, 6300, 7800, 9600, 11800, 14400, 17600, 21400, 25900, 31300, 37900, 45700, 55100, 66400, 80000];

$id = $_POST['id'];
$baseName = $_POST['users_base_name'];
$amount = (int) $_POST['users_amount'];
$beginnersProtection = $_POST['users_protection'];
$postTribe = $_POST['tribe'];

// Some basic error checking
if (strlen($baseName) < 4) {
    header("Location: ../../../Admin/admin.php?p=addUsers&e=BN2S&bn=$baseName&am=$amount");
} elseif (strlen($baseName) > 20) {
    // Might be needed if older browers don't respect form maxlength
    header("Location: ../../../Admin/admin.php?p=addUsers&e=BN2L&bn=$baseName&am=$amount");
} elseif ($amount < 1) {
    header("Location: ../../../Admin/admin.php?p=addUsers&e=AMLO&bn=$baseName&am=$amount");
} elseif ($amount > 200) {
    // TODO: Make this a config variable?
    header("Location: ../../../Admin/admin.php?p=addUsers&e=AMHI&bn=$baseName&am=$amount");
}

include_once '../../../tempGlobalLoader.php';

// Looks OK, let's go for it
$created = 0;
$skipped = 0;
for ($i= 1; $i <= $amount; $i++) {
    $userName = $baseName . $i;
    
    // Random passwords disallow admin logging in to use the accounts
    // $password = $generator->generateRandStr(20);
    $password = 'mx~' . $baseName . $i . 'PASS';
    $email = $baseName . $i . '@example.com';
    $tribe = $postTribe != 0 ? $postTribe : rand(1, 3);
    // Create in a random quad
    $kid = rand(1,4);
    
    if ($database->checkExist($userName,0)) {
        $skipped ++;
        continue;
    }
    
    $userDb = new User();
    
    try {
        $uid = $userDb->create($userName, md5($password), $email, $tribe);
    } catch (\RuntimeException $exception) {
        $skipped ++;
        continue;
    }
    
    $userDb->updateProfileSetBeginnerProtectionNote($uid);
   
    if (!$beginnersProtection) {
        $userDb->removeBeginnerProtection($uid);
    }
    
    $wid = $database->generateBase($kid, 0);
    $database->setFieldTaken($wid);
    
    //calculate random generate value and level building
    $gamesday = time() - COMMENCE;
    if ($gamesday < 3600 * 24 * 10) { //10 day
        $maxDifficulty = 1;
    } elseif ($gamesday < 3600 * 24 * 20) {
        $maxDifficulty = 2;
    } elseif ($gamesday < 3600 * 24 * 30) {
        $maxDifficulty = 3;
    } elseif ($gamesday < 3600 * 24 * 60) {
        $maxDifficulty = 4;
    } else {
        $maxDifficulty = 5;
    }
    
    $difficulty = rand(1, $maxDifficulty);
    
    switch ($difficulty) {
        case 1:
            $warehouseStorageLevel = rand(0,3);
            $granaryStorageLevel = ($warehouseStorageLevel - 2 > 0) ? $warehouseStorageLevel - 2 : 0;
            break;
        case 2:
            $warehouseStorageLevel = rand(2,6);
            $granaryStorageLevel = ($warehouseStorageLevel - 2 > 0) ? $warehouseStorageLevel - 2 : 0;
            break;
        case 3:
            $warehouseStorageLevel = rand(5,10);
            $granaryStorageLevel = ($warehouseStorageLevel - 3 > 0) ? $warehouseStorageLevel - 3 : 0;
            break;
        case 4:
            $warehouseStorageLevel = rand(8,15);
            $granaryStorageLevel = ($warehouseStorageLevel - 3 > 0) ? $warehouseStorageLevel - 3 : 0;
            break;
        case 5:
            $warehouseStorageLevel = rand(14,20);
            $granaryStorageLevel = ($warehouseStorageLevel - 4 > 0) ? $warehouseStorageLevel - 4 : 0;
            break;
        default:
            break;
    }
    
    $warehouseStorageCap = $wgarray[$warehouseStorageLevel] * STORAGE_MULTIPLIER;
    $granaryStorageCap = $wgarray[$granaryStorageLevel] * STORAGE_MULTIPLIER;
    
    $minWarehouseStorageLevel = ($warehouseStorageLevel - 2 > 0) ? $warehouseStorageLevel - 2 : 0;
    $minGranaryStorageLevel = ($granaryStorageLevel - 2 > 0) ? $granaryStorageLevel - 2 : 0;
    
    $resource[1] = rand($wgarray[$minWarehouseStorageLevel], $wgarray[$warehouseStorageLevel]);
    $resource[2] = rand($wgarray[$minWarehouseStorageLevel], $wgarray[$warehouseStorageLevel]);
    $resource[3] = rand($wgarray[$minWarehouseStorageLevel], $wgarray[$warehouseStorageLevel]);
    $resource[4] = rand($wgarray[$minGranaryStorageLevel], $wgarray[$granaryStorageLevel]);
    
    //insert village
    $time = time();
    $villageName = "Village of {$userName}";
    $q = "INSERT INTO ".TB_PREFIX."vdata (
        `wref`,`owner`,`name`,`capital`,`pop`,`cp`,`celebration`,`type`,
        `wood`,`clay`,`iron`,`maxstore`,`crop`,`maxcrop`,
        `lastupdate`,`loyalty`,`exp1`,`exp2`,`exp3`,`created`
    )
    VALUES (
        '$wid', '$uid', '{$villageName}', 1, 200, 1, 0, 0,
        {$resource[1]}, {$resource[2]}, {$resource[3]}, {$warehouseStorageCap}, {$resource[4]}, {$granaryStorageCap},
        $time, 100, 0, 0, 0, $time
    )";
    $database->query($q);
    
    // and building with random level
    switch ($difficulty) {
        case 1:
        case 2: // todo create separate rules for each difficulty
        case 3:
        case 4:
        case 5:
            $fields = [
                1 => ['level' => rand(1,3), 'type' => Buildings::WOODCUTTER],
                ['level' => rand(1,3), 'type' => Buildings::CROPLAND],
                ['level' => rand(1,3), 'type' => Buildings::WOODCUTTER],
                ['level' => rand(1,3), 'type' => Buildings::IRON_MINE],
                ['level' => rand(1,3), 'type' => Buildings::CLAY_PIT],
                ['level' => rand(1,3), 'type' => Buildings::CLAY_PIT],
                ['level' => rand(1,3), 'type' => Buildings::IRON_MINE],
                ['level' => rand(1,3), 'type' => Buildings::CROPLAND],
                ['level' => rand(1,3), 'type' => Buildings::CROPLAND],
                ['level' => rand(1,3), 'type' => Buildings::IRON_MINE],
                ['level' => rand(1,3), 'type' => Buildings::IRON_MINE],
                ['level' => rand(1,3), 'type' => Buildings::CROPLAND],
                ['level' => rand(1,3), 'type' => Buildings::CROPLAND],
                ['level' => rand(1,3), 'type' => Buildings::WOODCUTTER],
                ['level' => rand(1,3), 'type' => Buildings::CROPLAND],
                ['level' => rand(1,3), 'type' => Buildings::CLAY_PIT],
                ['level' => rand(1,3), 'type' => Buildings::WOODCUTTER],
                ['level' => rand(1,3), 'type' => Buildings::CLAY_PIT],
                
                19 => ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                ['level' => rand(0,1), 'type' => Buildings::ACADEMY],
                ['level' => rand(1,3), 'type' => Buildings::BARRACKS],
                ['level' => 0, 'type' => 0],
                ['level' => $granaryStorageLevel, 'type' => Buildings::GRANARY],
                26 => ['level' => rand(3,4), 'type' => Buildings::MAIN_BUILDING],
                ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                ['level' => rand(1,3), 'type' => Buildings::MARKETPLACE],
                ['level' => $warehouseStorageLevel, 'type' => Buildings::WAREHOUSE],
                ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                ['level' => rand(1,5), 'type' => Buildings::CRANNY],
                ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                ['level' => 0, 'type' => 0],
                38 => ['level' => rand(1,3), 'type' => Buildings::EMBASSY],
                
                39 => ['level' => rand(1,2), 'type' => Buildings::RALLY_POINT],
            ];
            switch ($tribe) {
                case Tribe::ROMANS:
                    $fields[40] = ['level' => rand(1,3), 'type' => Buildings::CITY_WALL];
                    break;
                case Tribe::TEUTONS:
                    $fields[40] = ['level' => rand(0,1), 'type' => Buildings::EARTH_WALL];
                    break;
                case Tribe::GAULS:
                    $fields[40] = ['level' => rand(0,1), 'type' => Buildings::PALISADE];
                    break;
            }
            break;
    }
    
    $q = "insert into ".TB_PREFIX."fdata (`vref`, " . join(", ", array_map(function($id) {return '`f'.$id.'`, `f'.$id.'t`';}, array_keys($fields))). ", `f99`,`f99t`,`wwname`)
     values ($wid ,
            " . join(", ", array_map(function($field) {return "{$field['level']}, {$field['type']}";}, $fields)) . ",
            0, 0,
            'World Wonder'
     )";
    $database->query($q);
    /** @var \GameEngine\Automation $automation */
    $automation->recountPop($wid);

    $database->addUnits($wid);
    $database->addTech($wid);
    $database->addABTech($wid);
    $database->updateUserField($uid,"access",USER,1);
    
    //insert units randomly generate the number of troops

    // for future
    // $role = [
    //     'houseBuilder',
    //     'defensive',
    //     'mixed',
    //     'scouter',
    //     'offensive',
    // ];
    
    switch ($difficulty) {
        case 1:
            $units = [
                ['id' => ($tribe-1) * 10 + 1, 'count' => rand(1, 20)],
                ['id' => ($tribe-1) * 10 + 2, 'count' => rand(0, 15)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(0, 10)],
            ];
            break;
        case 2:
            $units = [
                ['id' => ($tribe-1) * 10 + 1, 'count' => rand(10, 50)],
                ['id' => ($tribe-1) * 10 + 2, 'count' => rand(7, 40)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(5, 30)],
            ];
            break;
        case 3:
            $units = [
                ['id' => ($tribe-1) * 10 + 1, 'count' => rand(25, 100)],
                ['id' => ($tribe-1) * 10 + 2, 'count' => rand(20, 75)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(15, 50)],
            ];
            break;
        case 4:
            $units = [
                ['id' => ($tribe-1) * 10 + 1, 'count' => rand(50, 500)],
                ['id' => ($tribe-1) * 10 + 2, 'count' => rand(40, 450)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(30, 400)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(20, 200)],
            ];
            break;
        case 5:
            $units = [
                ['id' => ($tribe-1) * 10 + 1, 'count' => rand(100, 1000)],
                ['id' => ($tribe-1) * 10 + 2, 'count' => rand(80, 900)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(60, 800)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(50, 500)],
                ['id' => ($tribe-1) * 10 + 3, 'count' => rand(45, 400)],
            ];
            break;
        default:
            break;
    }
    
    $q = "UPDATE " . TB_PREFIX . "units
        SET " . join(', ', array_map(function($unit) {return "u{$unit['id']} = {$unit['count']}";}, $units)) . "
        WHERE vref = '".$wid."'";
    $database->query($q);

    $created ++;
}

header("Location: ../../../Admin/admin.php?p=addUsers&g=OK&bn=$baseName&am=$created&sk=$skipped&bp=$beginnersProtection&tr=$postTribe");
