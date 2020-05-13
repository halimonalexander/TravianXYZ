<?php

namespace App\Controllers\Village;

use App\Helpers\DatetimeHelper;
use App\Helpers\TraceHelper;
use App\Sids\MovementModeSid;
use App\Sids\MovementTypeSid;
use HalimonAlexander\Registry\Registry;

class VillageOutskirtsController extends  AbstractVillageController
{
    private $village;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->village = (Registry::getInstance())->get('village');
    }
    
    public function displayAction()
    {
        $this->loadTemplate('villageOutskirts', [
            'start'      => TraceHelper::getTimer(),
            'building'   => (Registry::getInstance())->get('building'),
            'technology' => (Registry::getInstance())->get('technology'),
            'village'    => $this->village,
            'movements'  => $this->getMoves(),
            
            'coors' => [
                1 => "101,33,28",
                "165,32,28",
                "224,46,28",
                "46,63,28",
                "138,74,28",
                "203,94,28",
                "262,86,28",
                "31,117,28",
                "83,110,28",
                "214,142,28",
                "269,146,28",
                "42,171,28",
                "93,164,28",
                "160,184,28",
                "239,199,28",
                "87,217,28",
                "140,231,28",
                "190,232,28",
            ],
        ]);
    }
    
    private function getMoves()
    {
        $returnData = [];
        
        /*
        $oases = 0;
        foreach($this->database->getOasis($this->village->wid) as $conqured){
            $oases += count($this->database->getMovement(MovementTypeSid::CHEF_TAKEN, $conqured['wref'], MovementModeSid::FROM));
        }
    
        $aantal = (
            count($this->database->getMovement(MovementTypeSid::RETURNING, $this->village->wid, MovementModeSid::TO)) +
            count($this->database->getMovement(MovementTypeSid::REINFORCEMENT, $this->village->wid, MovementModeSid::TO)) +
            count($this->database->getMovement(MovementTypeSid::REINFORCEMENT, $this->village->wid, MovementModeSid::FROM)) +
            count($this->database->getMovement(MovementTypeSid::UNKNOWN_TYPE_7, $this->village->wid, MovementModeSid::TO)) +
            count($this->database->getMovement(MovementTypeSid::SETTLERS, $this->village->wid, MovementModeSid::FROM)) +
            $oases -
            count($this->database->getMovement(MovementTypeSid::UNKNOWN_TYPE_8, $this->village->wid, MovementModeSid::TO)) -
            count($this->database->getMovement(MovementTypeSid::UNKNOWN_TYPE_9, $this->village->wid, MovementModeSid::FROM)));
        */
        
        // Units coming back from Reinf, attack, raid, evasion or reinf to my town
        $aantal2 = $this->database->getMovement(MovementTypeSid::RETURNING,      $this->village->wid, MovementModeSid::TO);
        $aantal3 = $this->database->getMovement(MovementTypeSid::UNKNOWN_TYPE_7, $this->village->wid, MovementModeSid::TO);
        $aantal5 = $this->database->getMovement(MovementTypeSid::REINFORCEMENT,  $this->village->wid, MovementModeSid::TO);
        $movementsCount = count($aantal2) + count($aantal3) + count($aantal5);
        
        $arrivals = [];
        if ($movementsCount > 0) {
            foreach ($aantal2 as $receive) {
                $arrivals[] = $receive['endtime'];
            }
    
            foreach ($aantal3 as $receive) {
                $arrivals[] = $receive['endtime'];
            }
    
            foreach ($aantal5 as $receive) {
                if (in_array($receive['attack_type'], [1,3,4])) {
                    $movementsCount -= 1;
                } elseif ($receive['attack_type'] == 2) {
                    $arrivals[] = $receive['endtime'];
                }
            }
    
            if ($movementsCount > 0) {
                $returnData[] = [
                    'action'   => 'def1',
                    'aclass'   => 'd1',
                    'title'    => ARRIVING_REINF_TROOPS,
                    'short'    => ARRIVING_REINF_TROOPS_SHORT,
                    'quantity' => $movementsCount,
                    'timerId'  => 1,
                    'leftTime' => DatetimeHelper::secondsToTime(min($arrivals) - time()),
                ];
            }
        }
        
        // Attack/raid on you!
        $aantal2 = $this->database->getMovement(MovementTypeSid::REINFORCEMENT, $this->village->wid, MovementModeSid::TO);
        $movementsCount = count($aantal2);
    
        $arrivals = [];
        if ($movementsCount > 0) {
            foreach ($aantal2 as $receive) {
                if (in_array($receive['attack_type'], [1,2])) {
                    $movementsCount -= 1;
                } else {
                    $arrivals[] = $receive['endtime'];
                }
            }
    
            if ($movementsCount > 0) {
                $returnData[] = [
                    'action'   => 'att1',
                    'aclass'   => 'a1',
                    'title'    => UNDERATTACK,
                    'short'    => ATTACK,
                    'quantity' => $movementsCount,
                    'timerId'  => 2,
                    'leftTime' => DatetimeHelper::secondsToTime(min($arrivals) - time()),
                ];
            }
        }
    
        // on attack, raid
        $aantal2 = $this->database->getMovement(MovementTypeSid::REINFORCEMENT, $this->village->wid, MovementModeSid::FROM);
        $movementsCount = count($aantal2);

        if ($movementsCount > 0) {
            foreach ($aantal2 as $receive) {
                if ($receive['attack_type'] == 2) {
                    $movementsCount -= 1;
                } else {
                    $arrivals[] = $receive['endtime'];
                }
            }

            if ($movementsCount > 0) {
                $returnData[] = [
                    'action'   => 'att2',
                    'aclass'   => 'a2',
                    'title'    => OWN_ATTACKING_TROOPS,
                    'short'    => ATTACK,
                    'quantity' => $movementsCount,
                    'timerId'  => 3,
                    'leftTime' => DatetimeHelper::secondsToTime(min($arrivals) - time()),
                ];
            }
        }

        // Units send to reinf. (to other town)
        $aantal2 = $this->database->getMovement(MovementTypeSid::REINFORCEMENT, $this->village->wid, MovementModeSid::FROM);
        $movementsCount = count($aantal2);
        
        if ($movementsCount > 0) {
            foreach ($aantal2 as $receive) {
                if (in_array($receive['attack_type'], [1,3,4])) {
                    $movementsCount -= 1;
                } else {
                    $arrivals[] = $receive['endtime'];
                }
            }
    
            if ($movementsCount > 0) {
                $returnData[] = [
                    'action'   => 'def2',
                    'aclass'   => 'd2',
                    'title'    => OWN_REINFORCING_TROOPS,
                    'short'    => ARRIVING_REINF_TROOPS_SHORT,
                    'quantity' => $movementsCount,
                    'timerId'  => 4,
                    'leftTime' => DatetimeHelper::secondsToTime(min($arrivals) - time()),
                ];
            }
        }
        
        // Settlers
        $aantal2 = $this->database->getMovement(MovementTypeSid::SETTLERS, $this->village->wid, MovementModeSid::FROM);
        $movementsCount = count($aantal2);
    
        if ($movementsCount > 0) {
            foreach ($aantal2 as $receive) {
                $arrivals[] = $receive['endtime'];
            }
    
            $returnData[] = [
                'action'   => 'att3',
                'aclass'   => 'a3',
                'title'    => FOUNDNEWVILLAGE,
                'short'    => NEWVILLAGE,
                'quantity' => $movementsCount,
                'timerId'  => 5,
                'leftTime' => DatetimeHelper::secondsToTime(min($arrivals) - time()),
            ];
        }
        
        // Attacks on Oasis (to my oasis) by Shadow
        $movementsCount = 0;
        $aantal2 = [];
        $oasises = $this->database->getOasis($this->village->wid);
        foreach ($oasises as $conqured) {
            $data = $this->database->getMovement(MovementTypeSid::CHEF_TAKEN, $conqured['wref'], MovementModeSid::FROM);
    
            $movementsCount += count($data);
            $aantal2 = array_merge($data, $aantal2);
        }
        
        if ($movementsCount > 0) {
            foreach ($aantal2 as $receive) {
                $arrivals[] = $receive['endtime'];
            }
            
            $returnData[] = [
                'action'   => $receive['attack_type'] == 2 ? 'def3' : 'att3',
                'aclass'   => $receive['attack_type'] == 2 ? 'd3' : 'a3',
                'title'    => $receive['attack_type'] == 2 ? ARRIVING_REINF_TROOPS : OASISATTACK,
                'short'    => $receive['attack_type'] == 2 ? ARRIVING_REINF_TROOPS_SHORT : OASISATTACKS,
                'quantity' => $movementsCount,
                'timerId'  => 5,
                'leftTime' => DatetimeHelper::secondsToTime(min($arrivals) - time()),
            ];
        }
        
        return $returnData;
    }
}
