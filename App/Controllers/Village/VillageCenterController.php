<?php

namespace App\Controllers\Village;

use App\Helpers\TraceHelper;
use HalimonAlexander\Registry\Registry;

class VillageCenterController extends AbstractVillageController
{
    public function displayAction()
    {
        $this->loadTemplate('villageCenter', [
            'start'      => TraceHelper::getTimer(),
            'building'   => (Registry::getInstance())->get('building'),
            'technology' => (Registry::getInstance())->get('technology'),
            'village'    => (Registry::getInstance())->get('village'),
    
            'coords' => [
                19 => "53,91,91,71,127,91,91,112",
                "136,66,174,46,210,66,174,87",
                "196,56,234,36,270,56,234,77",
                "270,69,308,49,344,69,308,90",
                "327,117,365,97,401,117,365,138",
                "14,129,52,109,88,129,52,150",
                "97,137,135,117,171,137,135,158",
                "182,119,182,65,257,65,257,119,220,140",
                "337,156,375,136,411,156,375,177",
                "2,199,40,179,76,199,40,220",
                "129,164,167,144,203,164,167,185",
                "92,189,130,169,166,189,130,210",
                "342,216,380,196,416,216,380,237",
                "22,238,60,218,96,238,60,259",
                "167,232,205,212,241,232,205,253",
                "290,251,328,231,364,251,328,272",
                "95,273,133,253,169,273,133,294",
                "222,284,260,264,296,284,260,305",
                "80,306,118,286,154,306,118,327",
                "199,316,237,296,273,316,237,337",
                "270,158,303,135,316,155,318,178,304,211,288,227,263,238,250,215",
            ],
        ]);
    }
}
