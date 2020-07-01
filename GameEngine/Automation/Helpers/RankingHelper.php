<?php

namespace GameEngine\Automation\Helpers;

use GameEngine\Database\MysqliModel;
use GameEngine\Ranking;

class RankingHelper
{
    private $database;
    private $ranking;

    public function __construct(MysqliModel $database, Ranking $ranking)
    {
        $this->database = $database;
        $this->ranking  = $ranking;
    }

    public function procNewClimbers()
    {
        $this->ranking->procRankArray();
        $climbers = $this->ranking->getRank();
        if (count($this->ranking->getRank()) > 0) {
            $q = "SELECT * FROM " . TB_PREFIX . "medal order by week DESC LIMIT 0, 1";
            $result = $this->database->connection->query($q);
            if ($this->database->numRows($result)) {
                $row = $this->database->fetchAssoc($result);
                $week = ($row['week'] + 1);
            }
            else {
                $week = '1';
            }

            $q = "SELECT * FROM " . TB_PREFIX . "users where oldrank = 0 and id > 5";
            $array = $this->database->query_return($q);
            foreach ($array as $user) {
                $newrank = $this->ranking->getUserRank($user['id']);
                if ($week > 1) {
                    for ($i = $newrank + 1; $i < count($this->ranking->getRank()); $i++) {
                        $oldrank = $this->ranking->getUserRank($climbers[ $i ]['userid']);
                        $totalpoints = $oldrank - $climbers[ $i ]['oldrank'];
                        $this->database->removeclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                    $this->database->updateoldrank($user['id'], $newrank);
                }
                else {
                    $totalpoints = count($this->ranking->getRank()) - $newrank;
                    $this->database->setclimberrankpop($user['id'], $totalpoints);
                    $this->database->updateoldrank($user['id'], $newrank);
                    for ($i = 1; $i < $newrank; $i++) {
                        $oldrank = $this->ranking->getUserRank($climbers[ $i ]['userid']);
                        $totalpoints = count($this->ranking->getRank()) - $oldrank;
                        $this->database->setclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                    for ($i = $newrank + 1; $i < count($this->ranking->getRank()); $i++) {
                        $oldrank = $this->ranking->getUserRank($climbers[ $i ]['userid']);
                        $totalpoints = count($this->ranking->getRank()) - $oldrank;
                        $this->database->setclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                }
            }
        }
    }

    public function procClimbers($uid)
    {
        $this->ranking->procRankArray();
        $climbers = $this->ranking->getRank();
        if (count($this->ranking->getRank()) > 0) {
            $q = "SELECT * FROM " . TB_PREFIX . "medal order by week DESC LIMIT 0, 1";
            $result = $this->database->query($q);
            if ($this->database->numRows($result)) {
                $row = $this->database->fetchAssoc($result);
                $week = ($row['week'] + 1);
            }
            else {
                $week = '1';
            }
            $myrank = $this->ranking->getUserRank($uid);
            if ($climbers[ $myrank ]['oldrank'] > $myrank) {
                for ($i = $myrank + 1; $i <= $climbers[ $myrank ]['oldrank']; $i++) {
                    $oldrank = $this->ranking->getUserRank($climbers[ $i ]['userid']);
                    if ($week > 1) {
                        $totalpoints = $oldrank - $climbers[ $i ]['oldrank'];
                        $this->database->removeclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                    else {
                        $totalpoints = count($this->ranking->getRank()) - $oldrank;
                        $this->database->setclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                }
                if ($week > 1) {
                    $totalpoints = $climbers[ $myrank ]['oldrank'] - $myrank;
                    $this->database->addclimberrankpop($climbers[ $myrank ]['userid'], $totalpoints);
                    $this->database->updateoldrank($climbers[ $myrank ]['userid'], $myrank);
                }
                else {
                    $totalpoints = count($this->ranking->getRank()) - $myrank;
                    $this->database->setclimberrankpop($climbers[ $myrank ]['userid'], $totalpoints);
                    $this->database->updateoldrank($climbers[ $myrank ]['userid'], $myrank);
                }
            }
            elseif ($climbers[ $myrank ]['oldrank'] < $myrank) {
                for ($i = $climbers[ $myrank ]['oldrank']; $i < $myrank; $i++) {
                    $oldrank = $this->ranking->getUserRank($climbers[ $i ]['userid']);
                    if ($week > 1) {
                        $totalpoints = $climbers[ $i ]['oldrank'] - $oldrank;
                        $this->database->addclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                    else {
                        $totalpoints = count($this->ranking->getRank()) - $oldrank;
                        $this->database->setclimberrankpop($climbers[ $i ]['userid'], $totalpoints);
                        $this->database->updateoldrank($climbers[ $i ]['userid'], $oldrank);
                    }
                }
                if ($week > 1) {
                    $totalpoints = $myrank - $climbers[ $myrank - 1 ]['oldrank'];
                    $this->database->removeclimberrankpop($climbers[ $myrank - 1 ]['userid'], $totalpoints);
                    $this->database->updateoldrank($climbers[ $myrank - 1 ]['userid'], $myrank);
                }
                else {
                    $totalpoints = count($this->ranking->getRank()) - $myrank;
                    $this->database->setclimberrankpop($climbers[ $myrank - 1 ]['userid'], $totalpoints);
                    $this->database->updateoldrank($climbers[ $myrank - 1 ]['userid'], $myrank);
                }
            }
        }
        $this->ranking->procARankArray();
        $aid = $this->database->getUserField($uid, "alliance", 0);
        if (count($this->ranking->getRank()) > 0 && $aid != 0) {
            $ally = $this->database->getAlliance($aid);
            $memberlist = $this->database->getAllMember($ally['id']);
            $oldrank = 0;
            foreach ($memberlist as $member) {
                $oldrank += $this->database->getVSumField($member['id'], "pop");
            }
            if ($ally['oldrank'] != $oldrank) {
                if ($ally['oldrank'] < $oldrank) {
                    $totalpoints = $oldrank - $ally['oldrank'];
                    $this->database->addclimberrankpopAlly($ally['id'], $totalpoints);
                    $this->database->updateoldrankAlly($ally['id'], $oldrank);
                }
                elseif ($ally['oldrank'] > $oldrank) {
                    $totalpoints = $ally['oldrank'] - $oldrank;
                    $this->database->removeclimberrankpopAlly($ally['id'], $totalpoints);
                    $this->database->updateoldrankAlly($ally['id'], $oldrank);
                }
            }
        }
    }

    public function medals()
    {
        //we may give away ribbons

        $giveMedal = false;
        $q = "SELECT * FROM " . TB_PREFIX . "config";
        $result = $this->database->query($q);
        if ($result) {
            $row = $this->database->fetchAssoc($result);
            $stime = strtotime(START_DATE) - strtotime(date('m/d/Y')) + strtotime(START_TIME);
            if ($row['lastgavemedal'] == 0 && $stime < time()) {
                $newtime = time() + MEDALINTERVAL;
                $q = "UPDATE " . TB_PREFIX . "config SET lastgavemedal=" . $newtime;
                $this->database->query($q);
                $row['lastgavemedal'] = time() + MEDALINTERVAL;
            }

            $time = $row['lastgavemedal'] + MEDALINTERVAL;
            if ($time < time())
                $giveMedal = true;
        }

        if ($giveMedal && MEDALINTERVAL > 0) {

            //determine which week we are

            $q = "SELECT * FROM " . TB_PREFIX . "medal order by week DESC LIMIT 0, 1";
            $result = $this->database->query($q);
            if ($this->database->numRows($result)) {
                $row = $this->database->fetchAssoc($result);
                $week = ($row['week'] + 1);
            }
            else {
                $week = '1';
            }

            //Do same for ally week

            $q = "SELECT * FROM " . TB_PREFIX . "allimedal order by week DESC LIMIT 0, 1";
            $result = $this->database->query($q);
            if ($this->database->numRows($result)) {
                $row = $this->database->fetchAssoc($result);
                $allyweek = ($row['week'] + 1);
            }
            else {
                $allyweek = '1';
            }

            //Attackers of the week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY ap DESC, id DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "t2_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '1', '" . ($i) . "', '" . $week . "', '" . $row['ap'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Defender of the week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY dp DESC, id DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "t3_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '2', '" . ($i) . "', '" . $week . "', '" . $row['dp'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Climbers of the week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY Rc DESC, id DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "t1_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '3', '" . ($i) . "', '" . $week . "', '" . $row['Rc'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Rank climbers of the week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY clp DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "t6_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '10', '" . ($i) . "', '" . $week . "', '" . $row['clp'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Robbers of the week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY RR DESC, id DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "t4_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '4', '" . ($i) . "', '" . $week . "', '" . $row['RR'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Part of the bonus for top 10 attack + defense out
            //Top10 attackers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY ap DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                //Top 10 defenders
                $result2 = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY dp DESC, id DESC Limit 10");
                while ($row2 = $this->database->fetchArray($result2)) {
                    if ($row['id'] == $row2['id']) {

                        $query3 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 5";
                        $result3 = $this->database->query($query3);
                        $row3 = $this->database->fetchRow($result3);

                        //Look what color the ribbon must have
                        if ($row3[0] <= '2') {
                            $img = "t22" . $row3[0] . "_1";
                            switch ($row3[0]) {
                                case "0":
                                    $tekst = "";
                                    break;
                                case "1":
                                    $tekst = "twice ";
                                    break;
                                case "2":
                                    $tekst = "three times ";
                                    break;
                            }
                            $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '5', '0', '" . $week . "', '" . $tekst . "', '" . $img . "')";
                            $this->database->query($quer);
                        }
                    }
                }
            }

            //you stand for 3rd / 5th / 10th time in the top 3 strikers
            //top10 attackers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY ap DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 1 AND plaats<=3";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x at present as it is so ribbon 3rd (bronze)
                if ($row1[0] == '3') {
                    $img = "t120_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '6', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x at present as it is so 5th medal (silver)
                if ($row1[0] == '5') {
                    $img = "t121_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '6', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t122_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '6', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }
            //you stand for 3rd / 5th / 10th time in the top 10 attackers
            //top10 attackers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY ap DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 1 AND plaats<=10";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t130_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '12', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t131_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '12', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t132_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '12', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }
            //je staat voor 3e / 5e / 10e keer in de top 3 verdedigers
            //Pak de top10 verdedigers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY dp DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 2 AND plaats<=3";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t140_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '7', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t141_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '7', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t142_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '7', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }
            //je staat voor 3e / 5e / 10e keer in de top 3 verdedigers
            //Pak de top10 verdedigers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY dp DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 2 AND plaats<=10";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t150_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '13', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t151_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '13', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t152_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '13', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }

            //je staat voor 3e / 5e / 10e keer in de top 3 klimmers
            //Pak de top10 klimmers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY Rc DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 3 AND plaats<=3";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t100_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '8', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t101_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '8', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t102_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '8', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }
            //je staat voor 3e / 5e / 10e keer in de top 3 klimmers
            //Pak de top10 klimmers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY Rc DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 3 AND plaats<=10";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t110_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '14', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t111_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '14', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t112_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '14', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }

            //je staat voor 3e / 5e / 10e keer in de top 3 klimmers
            //Pak de top3 rank climbers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY clp DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 10 AND plaats<=3";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t200_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '11', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t201_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '11', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t202_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '11', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }
            //je staat voor 3e / 5e / 10e keer in de top 10klimmers
            //Pak de top3 rank climbers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY clp DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 10 AND plaats<=10";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t210_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '16', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t211_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '16', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t212_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '16', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }

            //je staat voor 3e / 5e / 10e keer in de top 10 overvallers
            //Pak de top10 overvallers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY RR DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 4 AND plaats<=3";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t160_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '9', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t161_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '9', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t162_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '9', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }
            //je staat voor 3e / 5e / 10e keer in de top 10 overvallers
            //Pak de top10 overvallers
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "users ORDER BY RR DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                $query1 = "SELECT count(*) FROM " . TB_PREFIX . "medal WHERE userid='" . $row['id'] . "' AND categorie = 4 AND plaats<=10";
                $result1 = $this->database->query($query1);
                $row1 = $this->database->fetchRow($result1);

                //2x in gestaan, dit is 3e dus lintje (brons)
                if ($row1[0] == '3') {
                    $img = "t170_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '15', '0', '" . $week . "', 'Three', '" . $img . "')";
                    $this->database->query($quer);
                }
                //4x in gestaan, dit is 5e dus lintje (zilver)
                if ($row1[0] == '5') {
                    $img = "t171_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '15', '0', '" . $week . "', 'Five', '" . $img . "')";
                    $this->database->query($quer);
                }
                //9x at present as it is so 10th medal (gold)
                if ($row1[0] == '10') {
                    $img = "t172_1";
                    $quer = "insert into " . TB_PREFIX . "medal(userid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '15', '0', '" . $week . "', 'Ten', '" . $img . "')";
                    $this->database->query($quer);
                }
            }

            //Put all true dens to 0
            $query = "SELECT * FROM " . TB_PREFIX . "users ORDER BY id+0 DESC";
            $result = $this->database->query($query);
            for ($i = 0; $row = $this->database->fetchRow($result); $i++) {
                $this->database->query("UPDATE " . TB_PREFIX . "users SET ap=0, dp=0,Rc=0,clp=0, RR=0 WHERE id = " . $row[0] . "");
            }

            //Start alliance Medals wooot

            //Aanvallers v/d Week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata ORDER BY ap DESC, id DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "a2_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "allimedal(allyid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '1', '" . ($i) . "', '" . $allyweek . "', '" . $row['ap'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Verdediger v/d Week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata ORDER BY dp DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "a3_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "allimedal(allyid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '2', '" . ($i) . "', '" . $allyweek . "', '" . $row['dp'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Overvallers v/d Week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata ORDER BY RR DESC, id DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "a4_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "allimedal(allyid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '4', '" . ($i) . "', '" . $allyweek . "', '" . $row['RR'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            //Rank climbers of the week
            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata ORDER BY clp DESC Limit 10");
            $i = 0;
            while ($row = $this->database->fetchArray($result)) {
                $i++;
                $img = "a1_" . ($i) . "";
                $quer = "insert into " . TB_PREFIX . "allimedal(allyid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '3', '" . ($i) . "', '" . $allyweek . "', '" . $row['clp'] . "', '" . $img . "')";
                $this->database->query($quer);
            }

            $result = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata ORDER BY ap DESC, id DESC Limit 10");
            while ($row = $this->database->fetchArray($result)) {

                //Pak de top10 verdedigers
                $result2 = $this->database->query("SELECT * FROM " . TB_PREFIX . "alidata ORDER BY dp DESC, id DESC Limit 10");
                while ($row2 = $this->database->fetchArray($result2)) {
                    if ($row['id'] == $row2['id']) {

                        $query3 = "SELECT count(*) FROM " . TB_PREFIX . "allimedal WHERE allyid='" . $row['id'] . "' AND categorie = 5";
                        $result3 = $this->database->query($query3);
                        $row3 = $this->database->fetchRow($result3);

                        //Look what color the ribbon must have
                        if ($row3[0] <= '2') {
                            $img = "t22" . $row3[0] . "_1";
                            switch ($row3[0]) {
                                case "0":
                                    $tekst = "";
                                    break;
                                case "1":
                                    $tekst = "twice ";
                                    break;
                                case "2":
                                    $tekst = "three times ";
                                    break;
                            }
                            $quer = "insert into " . TB_PREFIX . "allimedal(allyid, categorie, plaats, week, points, img) values('" . $row['id'] . "', '5', '0', '" . $allyweek . "', '" . $tekst . "', '" . $img . "')";
                            $this->database->query($quer);
                        }
                    }
                }
            }

            $query = "SELECT * FROM " . TB_PREFIX . "alidata ORDER BY id+0 DESC";
            $result = $this->database->query($query);
            for ($i = 0; $row = $this->database->fetchRow($result); $i++) {
                $this->database->query("UPDATE " . TB_PREFIX . "alidata SET ap=0, dp=0, RR=0, clp=0 WHERE id = " . $row[0] . "");
            }

            $q = "UPDATE " . TB_PREFIX . "config SET lastgavemedal=" . $time;
            $this->database->query($q);
        }
    }
}
