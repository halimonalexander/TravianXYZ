<?php

namespace GameEngine\Automation\Helpers;

use App\Helpers\GlobalVariablesHelper;
use App\Sids\Buildings;
use App\Sids\MovementTypeSid;
use App\Sids\RomansSid;
use GameEngine\Database\MysqliModel;
use GameEngine\Units;

class VillageHelper
{
    private $database;

    public function __construct(MysqliModel $database)
    {
        $this->database = $database;
    }

    public function getAllUnitsUpkeep($array, $type, $vid, $prisoners)
    {
        $buildarray = [];
        if ($vid != 0) {
            $buildarray = $this->database->getResourceLevel($vid);
        }

        $horseDrinkingFieldId = $this->getHorseDrinkingFieldId($buildarray);

        switch ($type) {
            case 0:
                $start = 1;
                $end = 50;
                break;
            case 1:
                $start = 1;
                $end = 10;
                break;
            case 2:
                $start = 11;
                $end = 20;
                break;
            case 3:
                $start = 21;
                $end = 30;
                break;
            case 4:
                $start = 31;
                $end = 40;
                break;
            case 5:
                $start = 41;
                $end = 50;
                break;
        }
        $upkeep = 0;

        for ($i = $start; $i <= $end; $i++) {
            $k = $i - $start + 1;
            $unitIndex = $prisoners == 0 ? "u" . $i : "t" . $k;

            $upkeep +=
                $this->getUnitUpkeep(
                    GlobalVariablesHelper::getUnit($i),
                    $buildarray['f' . $horseDrinkingFieldId],
                    $unitIndex
                ) *
                $array[$unitIndex];
        }

        $upkeep += ($prisoners == 0 ? $array['hero'] : $array['t11']) * 6;

        // Artefacts effects
        $who = $this->database->getVillageField($vid, "owner");
        $artefact  = count($this->database->getOwnUniqueArtefactInfo2($who, 4, 3, 0));
        $artefact1 = count($this->database->getOwnUniqueArtefactInfo2($vid, 4, 1, 1));
        $artefact2 = count($this->database->getOwnUniqueArtefactInfo2($who, 4, 2, 0));
        $foolartefact = $this->database->getFoolArtefactInfo(4, $vid, $who);

        if ($artefact > 0 || $artefact1 > 0) {
            $upkeep = round($upkeep / 2);
        } else if ($artefact2 > 0) {
            $upkeep = round($upkeep / 4 * 3);
        }

        if (count($foolartefact) > 0) {
            foreach ($foolartefact as $arte) {
                if ($arte['bad_effect'] == 1) {
                    $upkeep *= $arte['effect2'];
                } else {
                    $upkeep = round($upkeep / $arte['effect2']);
                }
            }
        }

        return $upkeep;
    }

    private function getHorseDrinkingFieldId(array $builds): ?int
    {
        for ($j = 19; $j <= 38; $j++) {
            if ($builds['f' . $j . 't'] == Buildings::HORSE_DRINKING_TROUGH) {
                return $j;
            }
        }

        return null;
    }

    private function getUnitUpkeep(array $unitData, ?int $horseDrinkingLevel, string $unitId): int
    {
        if (
            ($unitId == 'u'.RomansSid::U4 && $horseDrinkingLevel >= 10) ||
            ($unitId == 'u'.RomansSid::U5 && $horseDrinkingLevel >= 15) ||
            ($unitId == 'u'.RomansSid::U6 && $horseDrinkingLevel == 20)
        ) {
            return $unitData['pop'] - 1;
        }

        return $unitData['pop'];
    }

    public function getAllUnits(int $villageId)
    {
        $ownunit = $this->database->getUnit($villageId);

        $enforcementarray = $this->database->getEnforceVillage($villageId,0);
        if(count($enforcementarray) > 0) {
            foreach($enforcementarray as $enforce) {
                for($i=1;$i<=50;$i++) {
                    $ownunit['u'.$i] += $enforce['u'.$i];
                }
            }
        }

        $enforceoasis=$this->database->getOasisEnforce($villageId,0);
        if(count($enforceoasis) > 0) {
            foreach($enforceoasis as $enforce) {
                for($i=1;$i<=50;$i++) {
                    $ownunit['u'.$i] += $enforce['u'.$i];
                }
            }
        }

        $enforceoasis1=$this->database->getOasisEnforce($villageId,1);

        if(count($enforceoasis1) > 0) {
            foreach($enforceoasis1 as $enforce) {
                for($i=1;$i<=50;$i++) {
                    $ownunit['u'.$i] += $enforce['u'.$i];
                }
            }
        }
        $movement = $this->database->getVillageMovement($villageId);
        if(!empty($movement)) {
            for($i=1;$i<=50;$i++) {
                $ownunit['u'.$i] += $movement['u'.$i];
            }
        }

        $prisoners = $this->database->getPrisoners($villageId,1);
        if(!empty($prisoners)) {
            foreach($prisoners as $prisoner){
                $owner = $this->database->getVillageField($villageId,"owner");
                $ownertribe = $this->database->getUserField($owner,"tribe",0);
                $start = ($ownertribe-1)*10+1;
                $end = ($ownertribe*10);
                for($i=$start;$i<=$end;$i++) {
                    $j = $i-$start+1;
                    $ownunit['u'.$i] += $prisoner['t'.$j];
                }
                $ownunit['hero'] += $prisoner['t11'];
            }
        }

        return $ownunit;
    }

    public function updateMaxAllyMembers($leader)
    {
        $embassy = GlobalVariablesHelper::getBuilding(Buildings::EMBASSY);

        if ($this->database->numRows($this->database->query("SELECT * FROM " . TB_PREFIX . "alidata where leader = $leader")) == 0) {
            return;
        }

        $maxMembers = [];
        foreach ($this->database->getVillagesID2($leader) as $idx => $village) {
            $embassyLevel = $this->getEmbassyLevel($village['wref']);
            if ($embassyLevel !== null) {
                $maxMembers[] = $embassy[$embassyLevel]['attri'];
            }
        }

        $max = !empty($maxMembers) ? max($maxMembers) : 0;

        $this->database->query("UPDATE " . TB_PREFIX . "alidata set max = {$max} where leader = {$leader}");
    }

    private function getEmbassyLevel(int $villageId): ?int
    {
        $field = $this->database->getResourceLevel($villageId);

        for ($i = 19; $i <= 40; $i++) {
            if ($field['f' . $i . 't'] == Buildings::EMBASSY) {
                return $field['f' . $i];
            }
        }

        return null;
    }

    public function DelVillage($wref, Units $units)
    {
        $this->database->clearExpansionSlot($wref);

        $q = "DELETE FROM " . TB_PREFIX . "abdata where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."bdata where wid = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."market where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."odata where wref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."research where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."tdata where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."fdata where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."training where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."units where vref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."farmlist where wref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."raidlist where towref = $wref";
        $this->database->query($q);
        $q = "DELETE FROM ".TB_PREFIX."movement where proc = 0 AND ((`to` = $wref AND sort_type=4) OR (`from` = $wref AND sort_type=3))";
        $this->database->query($q);

        $getmovement = $this->database->getMovement(3,$wref,1);
        foreach($getmovement as $movedata) {
            $time = microtime(true);
            $time2 = $time - $movedata['starttime'];
            $this->database->setMovementProc($movedata['moveid']);
            $this->database->addMovement(MovementTypeSid::RETURNING,$movedata['to'],$movedata['from'],$movedata['ref'],$time,$time+$time2);
        }

        $q = "DELETE FROM ".TB_PREFIX."enforcement WHERE `from` = $wref";
        $this->database->query($q);

        //check return enforcement from del village
        $units->returnTroops($wref);

        $q = "DELETE FROM ".TB_PREFIX."vdata WHERE `wref` = $wref";
        $this->database->query($q);

        if ($this->database->affectedRows()>0) {
            $q = "UPDATE ".TB_PREFIX."wdata set occupied = 0 where id = $wref";
            $this->database->query($q);

            $getprisoners = $this->database->getPrisoners($wref);
            foreach($getprisoners as $pris) {
                $troops = 0;
                for($i=1;$i<12;$i++){
                    $troops += $pris['t'.$i];
                }
                $this->database->modifyUnit($pris['wref'],array("99o"),array($troops),array(0));
                $this->database->deletePrisoners($pris['id']);
            }
            $getprisoners = $this->database->getPrisoners3($wref);
            foreach($getprisoners as $pris) {
                $troops = 0;
                for($i=1;$i<12;$i++){
                    $troops += $pris['t'.$i];
                }
                $this->database->modifyUnit($pris['wref'],array("99o"),array($troops),array(0));
                $this->database->deletePrisoners($pris['id']);
            }
        }
    }
}
