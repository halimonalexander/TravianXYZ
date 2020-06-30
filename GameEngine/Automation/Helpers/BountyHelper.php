<?php

namespace GameEngine\Automation\Helpers;

use App\Helpers\GlobalVariablesHelper;
use App\Sids\Buildings;
use GameEngine\Database\MysqliModel;

class BountyHelper
{
    private $database;

    private $fields = [];
    private $village = [];
    private $villagePopulation;
    private $oasisOwned = [];
    private $oasisCounter = [];

    private $production = [];
    private $oasisProduction = [];

    public function __construct(MysqliModel $database)
    {
        $this->database = $database;
    }

    public function loadTown(int $villageId)
    {
        $this->village = $this->database->getVillage($villageId);
        $this->villagePopulation = $this->village['pop'];

        $this->fields = $this->database->getResourceLevel($villageId);

        $this->oasisOwned = $this->database->getOasis($villageId);
        $this->oasisCounter = $this->calculateAllOasisesBonuses();
    }

    private function calculateAllOasisesBonuses()
    {
        $crop = $clay = $wood = $iron = 0;
        foreach ($this->oasisOwned as $oasis) {
            switch($oasis['type']) {
                case 1:
                case 2:
                    $wood += 1;
                    break;
                case 3:
                    $wood += 1;
                    $crop += 1;
                    break;
                case 4:
                case 5:
                    $clay += 1;
                    break;
                case 6:
                    $clay += 1;
                    $crop += 1;
                    break;
                case 7:
                case 8:
                    $iron += 1;
                    break;
                case 9:
                    $iron += 1;
                    $crop += 1;
                    break;
                case 10:
                case 11:
                    $crop += 1;
                    break;
                case 12:
                    $crop += 2;
                    break;
            }
        }
        return [$wood,$clay,$iron,$crop];
    }

    public function calculateProduction(int $villageId, $uid)
    {
        $this->production = [
            'wood' => $this->getWoodProduction(),
            'clay' => $this->getClayProduction(),
            'iron' => $this->getIronProduction(),
            'crop' => $this->getCropProduction() - $this->villagePopulation - $this->getUpkeepReal($villageId, $uid),
        ];
    }

    private function getWoodProduction()
    {
        return $this->getProduction(
            Buildings::WOODCUTTER,
            [Buildings::SAWMILL],
            "b1"
        );
    }

    private function getClayProduction()
    {
        return $this->getProduction(
            Buildings::CLAY_PIT,
            [Buildings::BRICKYARD],
            "b2"
        );
    }

    private function getIronProduction()
    {
        return $this->getProduction(
            Buildings::IRON_MINE,
            [Buildings::IRON_FOUNDRY],
            "b3"
        );
    }

    private function getCropProduction()
    {
        return $this->getProduction(
            Buildings::CROPLAND,
            [Buildings::GRAIN_MILL, Buildings::BAKERY],
            "b4"
        );
    }

    public function processProduction(int $villageId)
    {
        $timepast = time() - $this->village['lastupdate'];

        $this->database->modifyResource(
            $villageId,
            ($this->production['wood'] / 3600) * $timepast,
            ($this->production['clay'] / 3600) * $timepast,
            ($this->production['iron'] / 3600) * $timepast,
            ($this->production['crop'] / 3600) * $timepast,
            1
        );
        $this->database->updateVillage($villageId);
    }

    public function loadOasisTown(int $villageId)
    {
        $this->village = $this->database->getOasisV($villageId);
        $this->fields = $this->database->getResourceLevel($villageId);
        $this->villagePopulation = 2;
    }

    public function calculateOasisProduction()
    {
        $this->oasisProduction = [
            'wood' => $this->getOasisWoodProduction(),
            'clay' => $this->getOasisClayProduction(),
            'iron' => $this->getOasisIronProduction(),
            'crop' => $this->getOasisCropProduction(),
        ];
    }

    private function getOasisWoodProduction()
    {
        return 40 * \SPEED;
    }

    private function getOasisClayProduction()
    {
        return 40 * \SPEED;
    }

    private function getOasisIronProduction()
    {
        return 40 * \SPEED;
    }

    private function getOasisCropProduction()
    {
        return 40 * \SPEED;
    }

    public function processOasisProduction(int $villageId)
    {
        $timepast = time() - $this->village['lastupdated'];

        $this->database->modifyOasisResource(
            $villageId,
            ($this->oasisProduction['wood'] / 3600) * $timepast,
            ($this->oasisProduction['clay'] / 3600) * $timepast,
            ($this->oasisProduction['iron'] / 3600) * $timepast,
            ($this->oasisProduction['crop'] / 3600) * $timepast,
            1
        );

        $this->database->updateOasis($villageId);
    }

    private function getUpkeepReal(int $villageId, int $uid)
    {
        $villageHelper = new VillageHelper($this->database);

        $upkeep = $villageHelper->getAllUnitsUpkeep(
            $villageHelper->getAllUnits($villageId),
            0,
            $this->village->wid,
            0
        );

        // todo getAllUnitsUpkeep counts some artefacts, need to check if this is not duplicating
        $normalA = $this->database->getOwnArtefactInfoByType($villageId,4);
        $largeA = $this->database->getOwnUniqueArtefactInfo($uid,4,2);
        $uniqueA = $this->database->getOwnUniqueArtefactInfo($uid,4,3);

        if ($uniqueA['size'] == 3 && $uniqueA['owner'] == $uid) {
            return $upkeep - round($upkeep * 0.50);
        } elseif (
            ($normalA['type'] == 4 && $normalA['size'] == 1 && $normalA['owner'] == $uid) ||
            ($largeA['size'] == 2 && $largeA['owner'] == $uid)
        ) {
            return $upkeep - round($upkeep * 0.25);
        } else {
            return $upkeep;
        }
    }

    private function getProduction(int $baseBuildingId, array $bonusBuildingIds, string $bonusField)
    {
        $baseProduction = 0;
        for ($i = 1; $i <= 18; $i++) {
            if ($this->fields['f' . $i . 't'] == $baseBuildingId) {
                $baseProduction += GlobalVariablesHelper::getBuilding($baseBuildingId)[$this->fields['f' . $i]]['prod'];
            }
        }

        $bonus = 1;

        for ($i = 19; $i <= 38; $i++) {
            $buildingId = $this->fields['f' . $i . 't'];

            if (in_array($buildingId, $bonusBuildingIds)) {
                $bonus += GlobalVariablesHelper::getBuilding($buildingId)[$this->fields['f' . $i]]['attri'] / 100;
            }
        }

        $bonus += 0.25 * $this->oasisCounter[0];

        if ($this->hasResourcesGoldBonus($bonusField)) {
            $bonus += 0.25;
        }

        $production = round($baseProduction * $bonus);
        $production *= \SPEED;

        return $production;
    }

    private function hasResourcesGoldBonus(string $bonusField): bool
    {
        // how can it be than empty vref ???
        if (empty($this->fields['vref']) || is_numeric($this->fields['vref'])) {
            return false;
        }

        // TODO
        // logic bellow is get from `bountyGetCropProd`,
        // consider if can change $this->fields['vref'] to $this->village->wid
        $who = $this->database->getVillageField($this->fields['vref'], "owner");

        if ($this->database->getUserField($who, $bonusField, 0) > time()) {
            return true;
        }

        return false;
    }
}
