<?php

namespace GameEngine\Automation\Helpers;

use App\Sids\MovementTypeSid;
use GameEngine\Database\MysqliModel;

class NatarHelper
{
    private $database;

    public function __construct(MysqliModel $database)
    {
        $this->database = $database;
    }

    public function startNatarAttack($level, $vid, $time)
    {
        // bad, but should work :D
        // I took the data from my first ww (first .org world)
        // todo: get the algo from the real travian with the 100 biggest
        // offs and so on
        $troops = [
            5 => [
                [3412, 2814, 4156, 3553, 9, 0],
                [35, 0, 77, 33, 17, 10],
            ],
            10 => [
                [4314, 3688, 5265, 4621, 13, 0],
                [65, 0, 175, 77, 28, 17],
            ],
            15 => [
                [4645, 4267, 5659, 5272, 15, 0],
                [99, 0, 305, 134, 40, 25],
            ],
            20 => [
                [6207, 5881, 7625, 7225, 22, 0],
                [144, 0, 456, 201, 56, 36],
            ],
            25 => [
                [6004, 5977, 7400, 7277, 23, 0],
                [152, 0, 499, 220, 58, 37],
            ],
            30 => [
                [7073, 7181, 8730, 8713, 27, 0],
                [183, 0, 607, 268, 69, 45],
            ],
            35 => [
                [7090, 7320, 8762, 8856, 28, 0],
                [186, 0, 620, 278, 70, 45],
            ],
            40 => [
                [7852, 6967, 9606, 8667, 25, 0],
                [146, 0, 431, 190, 60, 37],
            ],
            45 => [
                [8480, 8883, 10490, 10719, 35, 0],
                [223, 0, 750, 331, 83, 54],
            ],
            50 => [
                [8522, 9038, 10551, 10883, 35, 0],
                [224, 0, 757, 335, 83, 54],
            ],
            55 => [
                [8931, 8690, 10992, 10624, 32, 0],
                [219, 0, 707, 312, 84, 54],
            ],
            60 => [
                [12138, 13013, 15040, 15642, 51, 0],
                [318, 0, 1079, 477, 118, 76],
            ],
            65 => [
                [13397, 14619, 16622, 17521, 58, 0],
                [345, 0, 1182, 522, 127, 83],
            ],
            70 => [
                [16323, 17665, 20240, 21201, 70, 0],
                [424, 0, 1447, 640, 157, 102],
            ],
            75 => [
                [20739, 22796, 25746, 27288, 91, 0],
                [529, 0, 1816, 803, 194, 127],
            ],
            80 => [
                [21857, 24180, 27147, 28914, 97, 0],
                [551, 0, 1898, 839, 202, 132],
            ],
            85 => [
                [22476, 25007, 27928, 29876, 100, 0],
                [560, 0, 1933, 855, 205, 134],
            ],
            90 => [
                [31345, 35053, 38963, 41843, 141, 0],
                [771, 0, 2668, 1180, 281, 184],
            ],
            95 => [
                [31720, 35635, 39443, 42506, 144, 0],
                [771, 0, 2671, 1181, 281, 184],
            ],
            96 => [
                [32885, 37007, 40897, 44130, 150, 0],
                [795, 0, 2757, 1219, 289, 190],
            ],
            97 => [
                [32940, 37099, 40968, 44235, 150, 0],
                [794, 0, 2755, 1219, 289, 190],
            ],
            98 => [
                [33521, 37691, 41686, 44953, 152, 0],
                [812, 0, 2816, 1246, 296, 194],
            ],
            99 => [
                [36251, 40861, 45089, 48714, 165, 0],
                [872, 0, 3025, 1338, 317, 208],
            ],
        ];

        // select the troops^^
        if (!isset($troops[$level])) {
            return;
        }

        $units = $troops[$level];

        // get the capital village from the natars
        $query = $this->database->query('SELECT wref FROM ' . TB_PREFIX . 'vdata WHERE `owner` = 3 and `capital` = 1 LIMIT 1');
        $row = $this->database->fetchAssoc($query);

        // start the attacks
        $endtime = $time + round((60 * 60 * 24) / INCREASE_SPEED);

        // -.-
        $this->database->query("INSERT INTO `" . TB_PREFIX . "ww_attacks` (`vid`, `attack_time`) VALUES ({$vid}, {$endtime})");
        $this->database->query("INSERT INTO `" . TB_PREFIX . "ww_attacks` (`vid`, `attack_time`) VALUES ({$vid}, {($endtime + 1)}");

        // wave 1
        $ref = $this->database->addAttack(
            $row['wref'], 0,
            $units[0][0], $units[0][1], 0, $units[0][2], $units[0][3], $units[0][4], $units[0][5],
            0, 0, 0, 3, 0, 0, 0, 0, 20, 20, 0, 20, 20, 20, 20
        );
        $this->database->addMovement(MovementTypeSid::REINFORCEMENT, $row['wref'], $vid, $ref, $time, $endtime);

        // wave 2
        $ref2 = $this->database->addAttack(
            $row['wref'], 0,
            $units[1][0], $units[1][1], 0, $units[1][2], $units[1][3], $units[1][4], $units[1][5],
            0, 0, 0, 3, 40, 0, 0, 0, 20, 20, 0, 20, 20, 20, 20,
            ['vid' => $vid, 'endtime' => ($endtime + 1)]
        );
        $this->database->addMovement(MovementTypeSid::REINFORCEMENT, $row['wref'], $vid, $ref2, $time, $endtime + 1);
    }
}
