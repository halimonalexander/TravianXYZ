<?php

use \App\Sids\{
    FieldTypeSid,
    OasisTypeSid,
};

//////////////////////////////////////////////////////////////////////////////////////////////////////
//                                             TRAVIANX                                             //
//            Only for advanced users, do not edit if you dont know what are you doing!             //
//                                Made by: Dzoki & Dixie (TravianX)                                 //
//                              - TravianX = Travian Clone Project -                                //
//                                 DO NOT REMOVE COPYRIGHT NOTICE!                                  //
//////////////////////////////////////////////////////////////////////////////////////////////////////

include 'database.php';
include '../../App/Sids/FieldTypeSid.php';
include '../../App/Sids/OasisTypeSid.php';

class WorldGenerator
{
    private $database;
    
    public function __construct(MYSQLi_DB $database)
    {
        $this->database = $database;
    }
    
    protected function getCellType(int $x, int $y): array
    {
        if (
            ($x == 0 && $y == 0) ||
            ($x == WORLD_MAX && $y == WORLD_MAX)
        ) {
            return [FieldTypeSid::FIELD_4_4_4_6, 0];
        }
        
        $rand = rand(1, 1000);
        
        if ($rand <= 900)
            return $this->getFieldType($rand);
        else
            return $this->getOasisType($rand);
    }
    
    protected function getFieldType(int $rand)
    {
        if ("10" >= $rand) { // 1%
            return [FieldTypeSid::FIELD_3_3_3_9, 0];
        }
        elseif ("90" >= $rand) { // 8%
            return [FieldTypeSid::FIELD_3_4_5_6, 0];
        }
        elseif ("400" >= $rand) { // 31%
            return [FieldTypeSid::FIELD_4_4_4_6, 0];
        }
        elseif ("480" >= $rand) { // 8%
            return [FieldTypeSid::FIELD_4_5_3_6, 0];
        }
        elseif ("560" >= $rand) { // 8%
            return [FieldTypeSid::FIELD_5_3_4_6, 0];
        }
        elseif ("570" >= $rand) { // 1%
            return [FieldTypeSid::FIELD_1_1_1_15, 0];
        }
        elseif ("600" >= $rand) { // 3%
            return [FieldTypeSid::FIELD_4_4_3_7, 0];
        }
        elseif ("630" >= $rand) { // 3%
            return [FieldTypeSid::FIELD_3_4_4_7, 0];
        }
        elseif ("660" >= $rand) { // 3%
            return [FieldTypeSid::FIELD_4_3_4_7, 0];
        }
        elseif ("740" >= $rand) { // 8%
            return [FieldTypeSid::FIELD_3_5_4_6, 0];
        }
        elseif ("820" >= $rand) { // 8%
            return [FieldTypeSid::FIELD_4_3_5_6, 0];
        }
        else { // %8
            return [FieldTypeSid::FIELD_5_4_3_6, 0];
        }
    }
    
    protected function getOasisType(int $rand)
    {
        $rand -= 900;
        $rand = ceil((float) $rand / 8);
        if ($rand > 12)
            $rand = 12;
        return [0, $rand];
    }
    
    protected function getFieldImage(int $oasisType): string
    {
        if ($oasisType == '0') {
            return $image = "t" . rand(0, 9) . "";
        } else {
            return $image = "o" . $oasisType . "";
        }
    }
    
    public function create($x, $y)
    {
        list($fieldType, $oasisType) = $this->getCellType($x, $y);
        
        $image = $this->getFieldImage($oasisType);
        
        $this->database->query(
            "INSERT INTO " . TB_PREFIX . "wdata
            VALUES (0, '{$fieldType}', '{$oasisType}', '{$x}', '{$y}', 0, '{$image}');"
        );
    }
}

class ExtendedWorldGenerator extends WorldGenerator
{
    private $burnedArea = 10;
    
    protected function getCellType(int $x, int $y): array
    {
        if (
            ($x == 0 && $y == 0) ||
            ($x == WORLD_MAX && $y == WORLD_MAX)
        ) {
            return [FieldTypeSid::FIELD_4_4_4_6, 0];
        }
        
        $rand = rand(1, 1000);
        
        if (abs($x) > $this->burnedArea && abs($y) > $this->burnedArea) {
            if ($rand <= 900)
                return $this->getFieldType($rand);
            else
                return $this->getOasisType($rand);
        } else {
            if ($rand <= 800)
                return $this->getBurnedFieldType($rand);
            else
                return $this->getBurnedOasisType($rand);
        }
    }
    
    protected function getBurnedFieldType(int $rand)
    {
        if ("40" >= $rand) { // 4%
            return [FieldTypeSid::FIELD_3_3_3_9, 0];
        }
        elseif ("80" >= $rand) { // 4%
            return [FieldTypeSid::FIELD_1_1_1_15, 0];
        }

        elseif ("180" >= $rand) { // 10%
            return [FieldTypeSid::FIELD_4_4_3_7, 0];
        }
        elseif ("280" >= $rand) { // 10%
            return [FieldTypeSid::FIELD_3_4_4_7, 0];
        }
        elseif ("380" >= $rand) { // 10%
            return [FieldTypeSid::FIELD_4_3_4_7, 0];
        }
        
        elseif ("450" >= $rand) { // 7%
            return [FieldTypeSid::FIELD_3_4_5_6, 0];
        }
        elseif ("520" >= $rand) { // 7%
            return [FieldTypeSid::FIELD_4_5_3_6, 0];
        }
        elseif ("590" >= $rand) { // 7%
            return [FieldTypeSid::FIELD_5_3_4_6, 0];
        }
        elseif ("660" >= $rand) { // 7%
            return [FieldTypeSid::FIELD_3_5_4_6, 0];
        }
        elseif ("730" >= $rand) { // 7%
            return [FieldTypeSid::FIELD_4_3_5_6, 0];
        }
        else { // 7%
            return [FieldTypeSid::FIELD_5_4_3_6, 0];
        }
    }
    
    protected function getBurnedOasisType(int $rand)
    {
        $rand -= 800; // 1..200
        
        if ($rand <= 80) { // 40% of oasises, 8% of total
            return [0, OasisTypeSid::CROP_50];
        } elseif ($rand <= 120) { // 20% of oasises, 4% of total
            return [0, OasisTypeSid::LUMBER_25_CROP_25];
        } elseif ($rand <= 160) {
            return [0, OasisTypeSid::CLAY_25_CROP_25];
        } else {
            return [0, OasisTypeSid::IRON_25_CROP_25];
        }
    }
    
    protected function getFieldImage(int $oasisType): string
    {
        if ($oasisType == '0') {
            return $image = "t" . rand(0, 9) . "";
        } else {
            return $image = "o" . $oasisType . "";
        }
    }
}

$worldGenerator = new ExtendedWorldGenerator($database);

for ($i = -1 * WORLD_MAX; $i <= WORLD_MAX; $i++) {
	for ($j = -1 * WORLD_MAX; $j <= WORLD_MAX; $j++) {
        $worldGenerator->create($i, $j);
	}
}

header("Location: ../index.php?s=4");
