<?php

namespace GameEngine\Automation\Helpers;

use App\Helpers\GlobalVariablesHelper;
use GameEngine\Database\MysqliModel;

class VillageRecalculationHelper
{
    private $database;

    public function __construct(MysqliModel $database)
    {
         $this->database = $database;
    }

    public function recountVillage(int $villageId): int
    {
        $newPopulation = $this->recountPopulation($villageId);
        $this->recountCulturePoints($villageId);

        return $newPopulation;
    }

    private function recountPopulation(int $villageId): int
    {
        $totalPopulation = $this->getTotalPopulation($villageId);

        $q = "UPDATE " . TB_PREFIX . "vdata set pop = {$totalPopulation} where wref = {$villageId}";
        $this->database->query($q);

        return $totalPopulation;
    }

    private function getTotalPopulation(int $villageId): int
    {
        $fieldsData = $this->database->getResourceLevel($villageId);

        $population = 0;
        for ($i = 1; $i <= 40; $i++) {
            $buildingId = $fieldsData["f" . $i . "t"];
            if ($buildingId) {
                $population += $this->getPopulation($buildingId, $fieldsData["f" . $i]);
            }
        }

        return $population;
    }

    private function getPopulation($buildingId, $buildingCurrentLevel): int
    {
        $population = 0;

        for ($level = 0; $level <= $buildingCurrentLevel; $level++) {
            $population += GlobalVariablesHelper::getBuilding($buildingId)[$level]['pop'];
        }

        return $population;
    }

    private function recountCulturePoints(int $villageId): void
    {
        $totalCulturePoints = $this->getTotalCulturePoints($villageId);

        $q = "UPDATE " . TB_PREFIX . "vdata set cp = {$totalCulturePoints} where wref = {$villageId}";
        $this->database->query($q);
    }

    private function getTotalCulturePoints(int $villageId): int
    {
        $fieldsData = $this->database->getResourceLevel($villageId);

        $culturePoints = 0;
        for ($i = 1; $i <= 40; $i++) {
            $buildingId = $fieldsData["f" . $i . "t"];
            if ($buildingId) {
                $culturePoints += $this->getCulturePoints($buildingId, $fieldsData["f" . $i]);
            }
        }

        return $culturePoints;
    }

    private function getCulturePoints($buildingId, int $buildingCurrentLevel): int
    {
        $culturePoints = 0;

        for ($level = 0; $level <= $buildingCurrentLevel; $level++) {
            $culturePoints += GlobalVariablesHelper::getBuilding($buildingId)[$level]['cp'];
        }

        return $culturePoints;
    }

    public function pruneResource()
    {
        $this->fixSmallStorage();
        $this->fixResources();
    }

    private function fixSmallStorage(): void
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE maxstore < 800 OR maxcrop < 800";
        foreach($this->database->query($q)->fetch_all() as $village) {
            $maxstore = $this->normalizeCap($village['maxstore'], 800);
            $maxcrop  = $this->normalizeCap($village['maxcrop'], 800);

            $this->database->query("UPDATE " . TB_PREFIX . "vdata SET maxstore = {$maxstore}, maxcrop = {$maxcrop} WHERE wref = {$village['wref']}");
        }
    }

    private function fixResources(): void
    {
        $q = "SELECT * FROM ".TB_PREFIX."vdata 
            WHERE wood > maxstore OR wood < 0 
               OR clay > maxstore OR clay < 0
               OR iron > maxstore OR iron < 0
               OR crop > maxcrop  OR crop < 0";

        foreach($this->database->query($q)->fetch_all() as $village) {
            $wood = $this->normalizeCap($village['wood'], $village['maxstore']);
            $clay = $this->normalizeCap($village['wood'], $village['maxstore']);
            $iron = $this->normalizeCap($village['wood'], $village['maxstore']);
            $crop = $this->normalizeCap($village['wood'], $village['maxcrop']);

            $this->database->query("UPDATE " . TB_PREFIX . "vdata SET wood = {$wood}, clay = {$clay}, iron = {$iron}, crop = {$crop} WHERE wref = {$village['wref']}");
        }
    }

    public function pruneOasisResource()
    {
        $this->fixOasisSmallStorage();
        $this->fixOasisResources();
    }

    private function fixOasisSmallStorage()
    {
        // todo:
        // 1. Oasises maximum is not 800 but 1000
        // 2. Oasises can have maximum x2 (2000)
        $q = "SELECT * FROM " . TB_PREFIX . "odata WHERE maxstore < 800 OR maxcrop < 800";
        foreach($this->database->query($q)->fetch_all() as $getoasis) {
            $maxstore = $this->normalizeCap($getoasis['maxstore'], 800);
            $maxcrop  = $this->normalizeCap($getoasis['maxcrop'], 800);

            $this->database->query("UPDATE " . TB_PREFIX . "odata SET maxstore = {$maxstore}, maxcrop = {$maxcrop} WHERE wref = {$getoasis['wref']}");
        }
    }

    private function fixOasisResources()
    {
        $q = "SELECT * FROM ".TB_PREFIX."odata WHERE wood < 0 OR wood > 800 OR clay < 0 OR clay > 800 OR iron < 0 OR iron > 800 OR crop < 0 OR crop > 800";
        foreach($this->database->query($q)->fetch_all() as $getoasis) {
            $wood = $this->normalizeCap($getoasis['wood'], 800);
            $clay = $this->normalizeCap($getoasis['clay'], 800);
            $iron = $this->normalizeCap($getoasis['iron'], 800);
            $crop = $this->normalizeCap($getoasis['crop'], 800);

            $this->database->query("UPDATE " . TB_PREFIX . "odata SET wood = {$wood}, clay = {$clay}, iron = {$iron}, crop = {$crop} where wref = {$getoasis['wref']}");
        }
    }

    private function normalizeCap(float $value, int $maximum): float
    {
        if ($value < 0) {
            return 0;
        }

        if ($value > $maximum) {
            return  $maximum;
        }

        return $value;
    }
}
