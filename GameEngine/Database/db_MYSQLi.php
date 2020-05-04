<?php

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                                       ## 
##  Filename       db_MYSQL.php                                                ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.                 ##
##  Fixed by:      InCube - double troops                       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                               ##
##  Source code:   https://github.com/Shadowss/TravianZ                       ## 
##                                                                             ##
#################################################################################

class MYSQLi_DB
{
    /**
     * @var \mysqli
     */
    public $connection;
    
    public function __construct()
    {
        $this->connection = \mysqli_connect(SQL_SERVER, SQL_USER, SQL_PASS, SQL_DB);
        if ($this->connection->connect_error) {
            die('Connect Error (' . $this->connection->connect_errno . ') ' . $this->connection->connect_error);
        }
    
        $this->connection->query("SET NAMES 'UTF8'");  //Fix utf8 phpmyadmin by gm4st3r
    }
    
    public function query(string $sql)
    {
        return $this->connection->query($sql);
    }
    
    public function RemoveXSS($val)
    {
        return htmlspecialchars($val, ENT_QUOTES);
    }
    
    public function insertId()
    {
        return $this->connection->insert_id;
    }
    
    public function numRows(\mysqli_result $result): int
    {
        return $result->num_rows;
    }
    
    public function affectedRows(): int
    {
        return $this->connection->affected_rows;
    }
    
    public function fetchArray($result)
    {
        if (!($result instanceof \mysqli_result))
            return false;
        
        return $result->fetch_array();
    }
    
    public function fetchAssoc($result)
    {
        if (!($result instanceof \mysqli_result)) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
    
    public function fetchRow(\mysqli_result $result)
    {
        return $result->fetch_row();
    }
    
    public function fetchAll($result): array
    {
        $all = [];
        
        if ($result) {
            while($row = $this->fetchAssoc($result)) {
                $all[] = $row;
            }
        }
        
        return $all;
    }
    
    public function fetchOne(\mysqli_result $result)
    {
        $value = $result->fetch_array(MYSQLI_NUM);
        
        return $value[0];
    }
    
    public function  realEscapeString(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * @deprecated 
     */
    public function query_return($q)
    {
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }
    
    public function deleteReinf($id): void
    {
        $q = "DELETE from " . TB_PREFIX . "enforcement where id = '$id'";
        $this->query($q);
    }
    
    public function updateResource($vid, $what, $number): void
    {
        $q = "UPDATE " . TB_PREFIX . "vdata set " . $what . "=" . $number . " where wref = $vid";
        $this->query($q);
    }

    public function checkExist($ref, $mode)
    {
        if (!$mode) {
            $q = "SELECT username FROM " . TB_PREFIX . "users where username = '$ref' LIMIT 1";
        } else {
            $q = "SELECT email FROM " . TB_PREFIX . "users where email = '$ref' LIMIT 1";
        }
        
        $result = $this->query($q);
        
        if (mysqli_num_rows($result)) {
            return true;
        } else {
            return false;
        }
    }

    public function hasBeginnerProtection($vid): bool
    {
        $q = "SELECT u.protect FROM ".TB_PREFIX."users u,".TB_PREFIX."vdata v WHERE u.id=v.owner AND v.wref=".$vid;
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        if (!empty($dbarray)) {
            return time() < $dbarray[0] ? true : false; 
        } else {
            return false;
        }
    }

    public function updateUserField($ref, $field, $value, $switch)
    {
        if (!$switch) {
            $q = "UPDATE " . TB_PREFIX . "users set $field = '$value' where username = '$ref'";
        } else {
            $q = "UPDATE " . TB_PREFIX . "users set $field = '$value' where id = '$ref'";
        }
        
        return $this->query($q);
    }

    public function getSitee($uid)
    {
        $q = "SELECT id from " . TB_PREFIX . "users where sit1 = $uid or sit2 = $uid";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    public function getVilWref($x, $y)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata where x = $x AND y = $y";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['id'];
    }
    
    function caststruc($user)
    {
        //loop search village user
        $query = $this->query("SELECT * FROM ".TB_PREFIX."vdata WHERE owner = ".$user."");
        while($villaggi_array = mysqli_fetch_array($query))

        //loop structure village
        $query1 = $this->query("SELECT * FROM ".TB_PREFIX."fdata WHERE vref = ".$villaggi_array['wref']."");
        $strutture= mysqli_fetch_array($query1);
        return $strutture;
    }
    
    public function removeMeSit($uid, $uid2)
    {
        $q = "UPDATE " . TB_PREFIX . "users set sit1 = 0 where id = $uid and sit1 = $uid2";
        $this->query($q);
        
        $q = "UPDATE " . TB_PREFIX . "users set sit2 = 0 where id = $uid and sit2 = $uid2";
        $this->query($q);
    }

    public function getUserField($ref, $field, $mode)
    {
        if (!$mode) {
            $q = "SELECT $field FROM " . TB_PREFIX . "users where id = '$ref'";
        } else {
            $q = "SELECT $field FROM " . TB_PREFIX . "users where username = '$ref'";
        }
        $result = $this->query($q);
        
        if ($result) {
            $dbarray = $this->fetchArray($result);
            
            return $dbarray[$field];
        } elseif($field=="username") {
            return "??";
        } else {
            return 0;
        }
    }

    function getInvitedUser($uid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "users where invited = $uid order by regtime desc";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    public function getVrefField($ref, $field)
    {
        $q = "SELECT $field FROM " . TB_PREFIX . "vdata where wref = '$ref'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray[$field];
    }

    public function getVrefCapital($ref)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where owner = '$ref' and capital = 1";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray;
    }

    public function getStarvation()
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where starv != 0 and owner != 3";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    public function getUnstarvation()
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where starv = 0 and starvupdate = 0";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
      }

    public function getActivateField($ref, $field, $mode)
    {
        if (!$mode) {
            $q = "SELECT $field FROM " . TB_PREFIX . "activate where id = '$ref'";
        } else {
            $q = "SELECT $field FROM " . TB_PREFIX . "activate where username = '$ref'";
        }
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray[$field];
    }

    public function login($username, $password): bool
    {
        $q = "SELECT password,sessid FROM " . TB_PREFIX . "users where username = '$username'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['password'] == md5($password);
    }

    function checkActivate($act)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "activate where act = '$act'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);

        return $dbarray;
    }

    public function sitterLogin($username, $password): bool
    {
        $q = "SELECT sit1,sit2 FROM " . TB_PREFIX . "users where username = '$username' and access != " . BANNED;
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
    
        if ($dbarray['sit1'] != 0) {
            $sitterPassword = $this->getSitterPassword($dbarray['sit1']);
            
            if ($sitterPassword == md5($password)) {
                return true;
            }
        }
        
        if ($dbarray['sit2'] != 0) {
            $sitterPassword = $this->getSitterPassword($dbarray['sit2']);
    
            if ($sitterPassword == md5($password)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getSitterPassword(int $userId)
    {
        $q = "SELECT password FROM " . TB_PREFIX . "users where id = " . $userId . " and access != " . BANNED;
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['password'];
    }

    public function setDeleting($uid, $mode): void
    {
        $time = time() + 72 * 3600;
        if (!$mode) {
            $q = "INSERT into " . TB_PREFIX . "deleting values ($uid,$time)";
        } else {
            $q = "DELETE FROM " . TB_PREFIX . "deleting where uid = $uid";
        }
        
        $this->query($q);
    }

    function isDeleting($uid)
    {
        $q = "SELECT timestamp from " . TB_PREFIX . "deleting where uid = $uid";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['timestamp'];
    }

    public function modifyGold($userid, $amt, $mode)
    {
        if (!$mode) {
            $q = "UPDATE " . TB_PREFIX . "users set gold = gold - $amt where id = $userid";
        } else {
            $q = "UPDATE " . TB_PREFIX . "users set gold = gold + $amt where id = $userid";
        }
        
        return $this->query($q);
    }

    /*****************************************
    Function to retrieve user array via Username or ID
    Mode 0: Search by Username
    Mode 1: Search by ID
    References: Alliance ID
    *****************************************/

    public function getUserArray($ref, $mode)
    {
        if (!$mode) {
            $q = "SELECT * FROM " . TB_PREFIX . "users where username = '$ref'";
        } else {
            $q = "SELECT * FROM " . TB_PREFIX . "users where id = $ref";
        }
        $result = $this->query($q);
        
        return $this->fetchArray($result);
    }

    public function activeModify($username, $mode)
    {
        $time = time();
        if (!$mode) {
            $q = "INSERT into " . TB_PREFIX . "active VALUES ('$username',$time)";
        } else {
            $q = "DELETE FROM " . TB_PREFIX . "active where username = '$username'";
        }
        
        return $this->query($q);
    }
    
    public function addActiveUser($username, $time)
    {
        $q = "REPLACE into " . TB_PREFIX . "active values ('$username',$time)";
        if ($this->query($q)) {
            return true;
        } else {
            return false;
        }
    }

    function checkactiveSession($username, $sessid) {
        $q = "SELECT username FROM " . TB_PREFIX . "users where username = '$username' and sessid = '$sessid' LIMIT 1";
        $result = $this->query($q);
        if ($this->numRows($result) != 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function submitProfile($uid, $gender, $location, $birthday, $des1, $des2)
    {
        $q = "UPDATE " . TB_PREFIX . "users set gender = $gender, location = '$location', birthday = '$birthday', desc1 = '$des1', desc2 = '$des2' where id = $uid";
        return $this->query($q);
    }
    
    public function gpack($uid, $gpack)
    {
        $q = "UPDATE " . TB_PREFIX . "users set gpack = '$gpack' where id = $uid";
        return $this->query($q);
    }
    
    public function GetOnline($uid) {
        $q = "SELECT sit FROM " . TB_PREFIX . "online where uid = $uid";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['sit'];
    }
    
    public function UpdateOnline($mode, $name = "", $time = "", $uid = 0)
    {
        global $session;
        
        if ($mode == "login") {
            $q = "INSERT IGNORE INTO " . TB_PREFIX . "online (name, uid, time, sit) VALUES ('$name', '$uid', " . time() . ", 0)";
            return $this->query($q);
        } elseif ($mode == "sitter") {
            $q = "INSERT IGNORE INTO " . TB_PREFIX . "online (name, uid, time, sit) VALUES ('$name', '$uid', " . time() . ", 1)";
            return $this->query($q);
        } else {
            $q = "DELETE FROM " . TB_PREFIX . "online WHERE name ='" . addslashes($session->username) . "'";
            return $this->query($q);
        }
    }
    
    public function generateBase($sector, $mode=1) {
        $num_rows = 0;
        $count_while = 0;
        
        while (!$num_rows) {
            if (!$mode) {
                $gamesday=time()-COMMENCE;
                if ($gamesday<3600*24*10 && $count_while==0) { //10 day
                    $wide1=1;
                    $wide2=20;
                } elseif ($gamesday<3600*24*20 && $count_while==1) { //20 day
                    $wide1=20;
                    $wide2=40;
                } elseif ($gamesday<3600*24*30 && $count_while==2) { //30 day
                    $wide1=40;
                    $wide2=80;
                } else {        // over 30 day
                    $wide1=80;
                    $wide2=WORLD_MAX;
                }
            } else {
                $wide1=1;
                $wide2=WORLD_MAX;
            }
            
            switch($sector) {
                case 1:
                    $q = "Select * from ".TB_PREFIX."wdata where fieldtype = 3 and (x < -$wide1 and x > -$wide2) and (y > $wide1 and y < $wide2) and occupied = 0"; //x- y+
                    break;
                case 2:
                    $q = "Select * from ".TB_PREFIX."wdata where fieldtype = 3 and (x > $wide1 and x < $wide2) and (y > $wide1 and y < $wide2) and occupied = 0"; //x+ y+
                    break;
                case 3:
                    $q = "Select * from ".TB_PREFIX."wdata where fieldtype = 3 and (x < -$wide1 and x > -$wide2) and (y < -$wide1 and y > -$wide2) and occupied = 0"; //x- y-
                    break;
                case 4:
                    $q = "Select * from ".TB_PREFIX."wdata where fieldtype = 3 and (x > $wide1 and x < $wide2) and (y < -$wide1 and y > -$wide2) and occupied = 0"; //x+ y-
                    break;
            }
            
            $result = $this->query($q);
            $num_rows = $this->numRows($result);
            $count_while++;
        }
        
        $result = $this->fetchAll($result);
        $base = rand(0, ($num_rows-1));
        
        return $result[$base]['id'];
    }

    public function setFieldTaken($id)
    {
        $q = "UPDATE " . TB_PREFIX . "wdata set occupied = 1 where id = $id";
        
        return $this->query($q);
    }

    public function addVillage($wid, $uid, $username, $capital)
    {
        $total = count($this->getVillagesID($uid));
        
        if ($total >= 1) {
            $vname = $username . "\'s village " . ($total + 1);
        } else {
            $vname = $username . "\'s village";
        }
        
        $time = time();
        $q = "INSERT into " . TB_PREFIX . "vdata (wref, owner, name, capital, pop, cp, celebration, wood, clay, iron, maxstore, crop, maxcrop, lastupdate, created) values ('$wid', '$uid', '$vname', '$capital', 2, 1, 0, 750, 750, 750, ".STORAGE_BASE.", 750, ".STORAGE_BASE.", '$time', '$time')";
        
        return $this->query($q);
    }

    public function addResourceFields($vid, $type)
    {
        switch($type) {
            case 1:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,4,4,1,4,4,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 2:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,3,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 3:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,1,4,1,3,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 4:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,1,4,1,2,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 5:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,1,4,1,3,1,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 6:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,4,4,1,3,4,4,4,4,4,4,4,4,4,4,4,2,4,4,1,15)";
                break;
            case 7:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,1,4,4,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 8:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,3,4,4,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 9:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,3,4,4,1,1,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 10:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,3,4,1,2,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
            case 11:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,3,1,1,3,1,4,4,3,3,2,2,3,1,4,4,2,4,4,1,15)";
                break;
            case 12:
                $q = "INSERT into " . TB_PREFIX . "fdata (vref,f1t,f2t,f3t,f4t,f5t,f6t,f7t,f8t,f9t,f10t,f11t,f12t,f13t,f14t,f15t,f16t,f17t,f18t,f26,f26t) values($vid,1,4,1,1,2,2,3,4,4,3,3,4,4,1,4,2,1,2,1,15)";
                break;
        }
        
        return $this->query($q);
    }
    
    public function isVillageOases($wref)
    {
        $q = "SELECT id, oasistype FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        
        if ($result) {
            $dbarray = $this->fetchArray($result);
            return $dbarray['oasistype'];
        } else {
            return 0;
        }
    }

    public function VillageOasisCount($vref)
    {
        $q = "SELECT count(*) FROM `".TB_PREFIX."odata` WHERE conqured=$vref";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        
        return $row[0];
    }

    public function countOasisTroops($vref)
    {
        //count oasis troops: $troops_o
        $troops_o=0;
        $o_unit2 = $this->query("select * from ".TB_PREFIX."units where `vref`='".$vref."'");
        $o_unit = $this->fetchArray($o_unit2);
    
        for ($i=1;$i<51;$i++) {
            $troops_o+=$o_unit[$i];
        }
        $troops_o += $o_unit['hero'];
    
        $o_unit2 = $this->query("select * from ".TB_PREFIX."enforcement where `vref`='".$vref."'");
        while ($o_unit = $this->fetchArray($o_unit2)) {
            for ($i=1;$i<51;$i++) {
                $troops_o+=$o_unit[$i];
            }
            
            $troops_o+=$o_unit['hero'];
        }
        
        return $troops_o;
    }

    public function canConquerOasis($vref,$wref): int
    {
        $AttackerFields = $this->getResourceLevel($vref);
        
        for($i=19;$i<=38;$i++) {
            if ($AttackerFields['f'.$i.'t'] == 37) { $HeroMansionLevel = $AttackerFields['f'.$i]; }
        }
        
        if ($this->VillageOasisCount($vref) < floor(($HeroMansionLevel-5)/5)) {
            $OasisInfo = $this->getOasisInfo($wref);
            
            //fix by ronix
            if (
                $OasisInfo['conqured'] == 0 ||
                $OasisInfo['conqured'] != 0 &&
                intval($OasisInfo['loyalty']) < 99 /
                    min(3, (4 - $this->VillageOasisCount($OasisInfo['conqured'])))
            ) {
                $CoordsVillage = $this->getCoor($vref);
                $CoordsOasis = $this->getCoor($wref);
                $max = 2 * WORLD_MAX + 1;
                $x1 = intval($CoordsOasis['x']);
                $y1 = intval($CoordsOasis['y']);
                $x2 = intval($CoordsVillage['x']);
                $y2 = intval($CoordsVillage['y']);
                $distanceX = min(abs($x2 - $x1), abs($max - abs($x2 - $x1)));
                $distanceY = min(abs($y2 - $y1), abs($max - abs($y2 - $y1)));
                
                if ($distanceX<=3 && $distanceY<=3) {
                    return 1; //can
                } else {
                    return 2; //can but not in 7x7 field
                }
            } else {
                return 3; //loyalty >0
            }
        } else {
            return 0; //req level hero mansion
        }
    }

    public function conquerOasis($vref,$wref)
    {
        $vinfo = $this->getVillage($vref);
        $uid = $vinfo['owner'];
        $q = "UPDATE `".TB_PREFIX."odata` SET conqured=$vref,loyalty=100,lastupdated=".time().",owner=$uid,name='Occupied Oasis' WHERE wref=$wref";
        
        return $this->query($q);
    }

    public function modifyOasisLoyalty($wref)
    {
        if ($this->isVillageOases($wref) == 0) {
            $OasisInfo = $this->getOasisInfo($wref);
            if ($OasisInfo['conqured'] != 0) {
                $LoyaltyAmendment = floor(100 / min(3,(4-$this->VillageOasisCount($OasisInfo['conqured']))));
                $q = "UPDATE `".TB_PREFIX."odata` SET loyalty=loyalty-$LoyaltyAmendment, lastupdated=".time()." WHERE wref=$wref";
                $result=$this->query($q);
                return $OasisInfo['loyalty']-$LoyaltyAmendment;
            }
        }
    }
    
    public function populateOasis()
    {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata where oasistype != 0";
        $result = $this->query($q);
        
        while($row = $this->fetchArray($result)) {
            $wid = $row['id'];

            $this->addUnits($wid);
        }
    }

    public function populateOasisUnits($wid, $high): void
    {
        $basearray = $this->getOasisInfo($wid);
        if ($high == 0){
            $max = rand(15,30);
        }elseif($high == 1){
            $max = rand(50,70);
        }elseif($high == 2){
            $max = rand(90,120);
        }
        
        $max2 = 0;
        $rand = rand(0,3);
        if ($rand == 1){
            $max2 = 3;
        }
        
        //each Troop is a Set for oasis type like mountains have rats spiders and snakes fields tigers elphants clay wolves so on stonger one more not so less
        switch($basearray['type']) {
            case 1:
            case 2:
              //+25% lumber per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u35 = u35 + '".rand(0,5)."',
                        u36 = u36 + '".rand(0,5)."',
                        u37 = u37 + '".rand(0,5)."'
                    WHERE vref = '" . $wid . "'
                      AND (u35 <= ".$max." OR u36 <= ".$max." OR u37 <= ".$max.")";
              break;
            case 3:
              //+25% lumber and +25% crop per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u35 = u35 + '".rand(0,5)."',
                        u36 = u36 + '".rand(0,5)."',
                        u37 = u37 + '".rand(0,5)."',
                        u38 = u38 + '".rand(0,5)."',
                        u40 = u40 + '".rand(0,$max2)."'
                    WHERE vref = '" . $wid . "'
                      AND (u36 <= ".$max." OR u37 <= ".$max." OR u38 <= ".$max.")";
              break;
            case 4:
            case 5:
              //+25% clay per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u31 = u31 + '".rand(0,5)."',
                        u32 = u32 + '".rand(0,5)."',
                        u35 = u35 + '".rand(0,5)."'
                    WHERE vref = '" . $wid . "'
                      AND (u31 <= ".$max." OR u32 <= ".$max." OR u35 <= ".$max.")";
              break;
            case 6:
              //+25% clay and +25% crop per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u31 = u31 + '".rand(0,5)."',
                        u32 = u32 + '".rand(0,5)."',
                        u35 = u35 + '".rand(0,5)."',
                        u40 = u40 + '".rand(0,$max2)."'
                    WHERE vref = '" . $wid . "'
                      AND (u31 <= ".$max." OR u32 <= ".$max." OR u35 <= ".$max.")";
              break;
            case 7:
            case 8:
              //+25% iron per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u31 = u31 + '".rand(0,5)."',
                        u32 = u32 + '".rand(0,5)."',
                        u34 = u34 + '".rand(0,5)."'
                    WHERE vref = '" . $wid . "'
                      AND (u31 <= ".$max." OR u32 <= ".$max." OR u34 <= ".$max.")";
              break;
            case 9:
              //+25% iron and +25% crop
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u31 = u31 + '".rand(0,5)."',
                        u32 = u32 + '".rand(0,5)."',
                        u34 = u34 + '".rand(0,5)."',
                        u39 = u39 + '".rand(0,$max2)."'
                    WHERE vref = '" . $wid . "'
                      AND (u31 <= ".$max." OR u32 <= ".$max." OR u34 <= ".$max.")";
              break;
            case 10:
            case 11:
              //+25% crop per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u33 = u33 + '".rand(0,5)."',
                        u37 = u37 + '".rand(0,5)."',
                        u38 = u38 + '".rand(0,5)."',
                        u39 = u39 + '".rand(0,$max2)."'
                    WHERE vref = '" . $wid . "'
                      AND (u33 <= ".$max." OR u37 <= ".$max." OR u38 <= ".$max.")";
              break;
            case 12:
              //+50% crop per hour
              $q = "UPDATE " . TB_PREFIX . "units
                    SET u33 = u33 + '".rand(0,5)."',
                        u37 = u37 + '".rand(0,5)."',
                        u38 = u38 + '".rand(0,5)."',
                        u39 = u39 + '".rand(0,5)."',
                        u40 = u40 + '".rand(0,$max2)."'
                    WHERE vref = '" . $wid . "'
                      AND (u33 <= ".$max." OR u37 <= ".$max." OR u38 <= ".$max." OR u39 <= ".$max.")";
              break;
        }
        $result = $this->query($q);
    }

    public function populateOasisUnits2()
    {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata where oasistype != 0";
        $result = $this->query($q);
        while($row = $this->fetchArray($result)) {
            $wid = $row['id'];
            
            switch($row['oasistype']) {
                case 1:
                case 2:
                    //+25% lumber oasis
                    $q = "UPDATE " . TB_PREFIX . "units
                          SET  u35 = u35 + '".rand(5,10)."',
                               u36 = u36 + '".rand(0,5)."',
                               u37 = u37 + '".rand(0,5)."'
                          WHERE vref = '" . $wid . "'
                            AND u35 <= '10' AND u36 <= '10' AND u37 <= '10'";
                    break;
                case 3:
                    //+25% lumber and +25% crop oasis
                    $q = "UPDATE " . TB_PREFIX . "units
                          SET u35 = u35 + '".rand(5,15)."',
                              u36 = u36 + '".rand(0,5)."',
                              u37 = u37 + '".rand(0,5)."'
                          WHERE vref = '" . $wid . "'
                            AND u35 <= '10' AND u36 <= '10' AND u37 <='10'";
                    break;
                case 4:
                case 5:
                    //+25% clay oasis
                    $q = "UPDATE " . TB_PREFIX . "units
                          SET u31 = u31 + '".rand(10,15)."',
                              u32 = u32 + '".rand(5,15)."',
                              u35 = u35 + '".rand(0,10)."'
                          WHERE vref = '" . $wid . "'
                            AND u31 <= '10' AND u32 <= '10' AND u35 <= '10'";
                    break;
                case 6:
                    //+25% clay and +25% crop oasis
                    $q = "UPDATE " . TB_PREFIX . "units
                          SET u31 = u31 + '".rand(15,20)."',
                              u32 = u32 + '".rand(10,15)."',
                              u35 = u35 + '".rand(0,10)."'
                          WHERE vref = '" . $wid . "'
                            AND u31 <= '10' AND u32 <= '10' AND u35 <='10'";
                    break;
                case 7:
                case 8:
                    //+25% iron oasis
                    $q = "UPDATE " . TB_PREFIX . "units
                          SET u31 = u31 + '".rand(10,15)."',
                              u32 = u32 + '".rand(5,15)."',
                              u34 = u34 + '".rand(0,10)."'
                          WHERE vref = '" . $wid . "'
                            AND u31 <= '10' AND u32 <= '10' AND u34 <= '10'";
                    break;
                case 9:
                    //+25% iron and +25% crop oasis
                    $q = "UPDATE " . TB_PREFIX . "units 
                          SET u31 = u31 + '".rand(15,20)."',
                              u32 = u32 + '".rand(10,15)."',
                              u34 = u34 + '".rand(0,10)."'
                          WHERE vref = '" . $wid . "'
                            AND u31 <= '10' AND u32 <= '10' AND u34 <='10'";
                    break;
                case 10:
                case 11:
                    //+25% crop oasis
                    $q = "UPDATE " . TB_PREFIX . "units 
                          SET u31 = u31 + '".rand(5,15)."',
                              u33 = u33 + '".rand(5,10)."',
                              u37 = u37 + '".rand(0,10)."',
                              u39 = u39 + '".rand(0,5)."'
                          WHERE vref = '" . $wid . "' 
                            AND u31 <= '10' AND u33 <= '10' AND u37 <='10' AND u39 <='10'";
                    $result = $this->query($q);
                    break;
                case 12:
                    //+50% crop oasis
                    $q = "UPDATE " . TB_PREFIX . "units
                          SET u31 = u31 + '".rand(10,15)."',
                              u33 = u33 + '".rand(5,10)."',
                              u38 = u38 + '".rand(0,5)."',
                              u39 = u39 + '".rand(0,5)."'
                          WHERE vref = '" . $wid . "'
                            AND u31 <= '10' AND u33 <= '10' AND u38 <='10'AND u39 <='10'";
                    break;
            }
            
            $this->query($q);
        }
    }

    function removeOases($wref) {
        $q = "UPDATE ".TB_PREFIX."odata SET conqured = 0, owner = 2, name = 'Unoccupied Oasis' WHERE wref = $wref";
        return $this->query($q);
    }

    /**
     * Function to retrieve type of village via ID
     * References: Village ID
     */
    public function getVillageType($wref)
    {
        $q = "SELECT id, fieldtype FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['fieldtype'];
    }

    /**
     * Function to retrieve if is ocuped via ID
     * References: Village ID
     */
    public function getVillageState($wref)
    {
        $q = "SELECT oasistype,occupied FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        
        $dbarray = $this->fetchArray($result);
        return $dbarray['occupied'] != 0 || $dbarray['oasistype'] != 0;
    }

    public function getProfileVillages($uid)
    {
        $q = "SELECT capital,wref,name,pop,created from " . TB_PREFIX . "vdata where owner = $uid order by pop desc";
        $result = $this->query($q);
    
        return $this->fetchAll($result);
    }

    function getProfileMedal($uid) {
        $q = "SELECT id,categorie,plaats,week,img,points from " . TB_PREFIX . "medal where userid = $uid and del = 0 order by id desc";
        $result = $this->query($q);
        
        return $this->fetchAll($result);

    }

    function getProfileMedalAlly($uid)
    {
        $q = "SELECT id,categorie,plaats,week,img,points from " . TB_PREFIX . "allimedal where allyid = $uid and del = 0 order by id desc";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    public function getVillageID($uid)
    {
        $q = "SELECT wref FROM " . TB_PREFIX . "vdata WHERE owner = $uid";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['wref'];
    }

    public function getVillagesID($uid)
    {
        $q = "SELECT wref from " . TB_PREFIX . "vdata where owner = $uid order by capital DESC,pop DESC";
        $result = $this->query($q);
        $array = $this->fetchAll($result);
        
        $newarray = [];
        for($i = 0; $i < count($array); $i++) {
            array_push($newarray, $array[$i]['wref']);
        }
        return $newarray;
    }
    
    public function getVillagesID2($uid)
    {
        $q = "SELECT wref from " . TB_PREFIX . "vdata where owner = $uid order by capital DESC,pop DESC";
        $result = $this->query($q);
        $array = $this->fetchAll($result);
        
        return $array;
    }

    public function getVillage($vid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where wref = $vid";
        $result = $this->query($q);
        
        return $this->fetchArray($result);
    }

    public function getVillageBattleData($vid)
    {
        $q = "SELECT u.id,u.tribe,v.capital,f.f40 AS wall FROM ".TB_PREFIX."users u,".TB_PREFIX."fdata f,".TB_PREFIX."vdata v WHERE u.id=v.owner AND f.vref=v.wref AND v.wref=".$vid;
        $result = $this->query($q);
        
        return $this->fetchArray($result);
    }

    public function getPopulation($uid) {
        $q = "SELECT sum(pop) AS pop FROM ".TB_PREFIX."vdata WHERE owner=".$uid;
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['pop'];
    }

    public function getOasisV($vid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "odata where wref = $vid";
        $result = $this->query($q);
        
        return $this->fetchArray($result);
    }

    public function getMInfo($id)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata left JOIN " . TB_PREFIX . "vdata ON " . TB_PREFIX . "vdata.wref = " . TB_PREFIX . "wdata.id where " . TB_PREFIX . "wdata.id = $id";
        $result = $this->query($q);
        
        return $this->fetchArray($result);
    }

    public function getOMInfo($id)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata left JOIN " . TB_PREFIX . "odata ON " . TB_PREFIX . "odata.wref = " . TB_PREFIX . "wdata.id where " . TB_PREFIX . "wdata.id = $id";
        $result = $this->query($q);
        
        return $this->fetchArray($result);
    }

    public function getOasis($vid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "odata where conqured = $vid";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    function getOasisInfo($wid) {
        $q = "SELECT * FROM " . TB_PREFIX . "odata where wref = $wid";
        $result = $this->query($q);
        
        return $this->fetchAssoc($result);
    }

    function getVillageField($ref, $field) {
        $q = "SELECT $field FROM " . TB_PREFIX . "vdata where wref = $ref";
        $result = $this->query($q);
        if ($result){
            $dbarray = $this->fetchArray($result);
            return $dbarray[$field];
         }elseif($field=="name"){
            return "??";
        }else return 0;
    }

    function getOasisField($ref, $field) {
        $q = "SELECT $field FROM " . TB_PREFIX . "odata where wref = $ref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray[$field];
    }

    function setVillageField($ref, $field, $value) {
        $q = "UPDATE " . TB_PREFIX . "vdata set $field = '$value' where wref = $ref";
        return $this->query($q);
    }

    function setVillageLevel($ref, $field, $value) {
        $q = "UPDATE " . TB_PREFIX . "fdata set " . $field . " = '" . $value . "' where vref = " . $ref . "";
        return $this->query($q);
    }

    function getResourceLevel($vid) {
        $q = "SELECT * from " . TB_PREFIX . "fdata where vref = $vid";
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }

    function getAdminLog() {
        $q = "SELECT id,user,log,time from " . TB_PREFIX . "admin_log where id != 0 ORDER BY id ASC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    //fix market log
    function getMarketLog() {
            $q = "SELECT id,wid,log from " . TB_PREFIX . "market_log where id != 0 ORDER BY id ASC";
            $result = $this->query($q);
            return $this->fetchAll($result);
        }
    function getMarketLogVillage($village) {
        $q = "SELECT wref,owner,name from " . TB_PREFIX . "vdata where wref =$village ";
            $result = $this->query($q);
            return $this->fetchAll($result);
        }
    function getMarketLogUsers($id_user) {
            $q = "SELECT id,username from " . TB_PREFIX . "users where id =$id_user ";
            $result = $this->query($q);
            return $this->fetchAll($result);
        }
    //end fix

    function getCoor($wref) {
        if ($wref !=""){
        $q = "SELECT x,y FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        return $this->fetchArray($result);
        }
    }

    function CheckForum($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_cat where alliance = '$id'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function CountCat($id) {
        $q = "SELECT count(id) FROM " . TB_PREFIX . "forum_topic where cat = '$id'";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        return $row[0];
    }

    function LastTopic($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_topic where cat = '$id' order by post_date";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function CheckLastTopic($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_topic where cat = '$id'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function CheckLastPost($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_post where topic = '$id'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function LastPost($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_post where topic = '$id'";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function CountTopic($id)
    {
        $q = "SELECT count(id) FROM " . TB_PREFIX . "forum_post where owner = '$id'";
        $result = $this->query($q);
        $row = $this->fetchRow($result);

        $qs = "SELECT count(id) FROM " . TB_PREFIX . "forum_topic where owner = '$id'";
        $results = $this->query($qs);
        $rows = $this->fetchRow($results);
        return $row[0] + $rows[0];
    }

    function CountPost($id)
    {
        $q = "SELECT count(id) FROM " . TB_PREFIX . "forum_post where topic = '$id'";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        return $row[0];
    }

    function ForumCat($id)
    {
        $q = "SELECT * from " . TB_PREFIX . "forum_cat where alliance = '$id' ORDER BY id";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function ForumCatEdit($id)
    {
        $q = "SELECT * from " . TB_PREFIX . "forum_cat where id = '$id'";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function ForumCatAlliance($id)
    {
        $q = "SELECT alliance from " . TB_PREFIX . "forum_cat where id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['alliance'];
    }

    function ForumCatName($id)
    {
        $q = "SELECT forum_name from " . TB_PREFIX . "forum_cat where id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['forum_name'];
    }

    function CheckCatTopic($id)
    {
        $q = "SELECT * from " . TB_PREFIX . "forum_topic where cat = '$id'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function CheckResultEdit($alli) {
        $q = "SELECT * from " . TB_PREFIX . "forum_edit where alliance = '$alli'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function CheckCloseTopic($id) {
        $q = "SELECT close from " . TB_PREFIX . "forum_topic where id = '$id'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['close'];
    }

    function CheckEditRes($alli) {
        $q = "SELECT result from " . TB_PREFIX . "forum_edit where alliance = '$alli'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['result'];
    }

    function CreatResultEdit($alli, $result) {
        $q = "INSERT into " . TB_PREFIX . "forum_edit values (0,'$alli','$result')";
        $this->query($q);
        return $this->insertId();
    }

    function UpdateResultEdit($alli, $result) {
        $date = time();
        $q = "UPDATE " . TB_PREFIX . "forum_edit set result = '$result' where alliance = '$alli'";
        return $this->query($q);
    }

    function getVillageType2($wref) {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['oasistype'];
    }

    function getVillageType3($wref) {
        $q = "SELECT * FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray;
    }

    function getFLData($id) {
        $q = "SELECT * FROM " . TB_PREFIX . "farmlist where id = $id";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }

    function checkVilExist($wref) {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where wref = '$wref'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function checkOasisExist($wref) {
        $q = "SELECT * FROM " . TB_PREFIX . "odata where wref = '$wref'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function UpdateEditTopic($id, $title, $cat) {
        $q = "UPDATE " . TB_PREFIX . "forum_topic set title = '$title', cat = '$cat' where id = $id";
        return $this->query($q);
    }

    function UpdateEditForum($id, $name, $des) {
        $q = "UPDATE " . TB_PREFIX . "forum_cat set forum_name = '$name', forum_des = '$des' where id = $id";
        return $this->query($q);
    }

    function StickTopic($id, $mode) {
        $q = "UPDATE " . TB_PREFIX . "forum_topic set stick = '$mode' where id = '$id'";
        return $this->query($q);
    }

    function ForumCatTopic($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_topic where cat = '$id' AND stick = '' ORDER BY post_date desc";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function ForumCatTopicStick($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_topic where cat = '$id' AND stick = '1' ORDER BY post_date desc";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function ShowTopic($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_topic where id = '$id'";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function ShowPost($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_post where topic = '$id'";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function ShowPostEdit($id) {
        $q = "SELECT * from " . TB_PREFIX . "forum_post where id = '$id'";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function CreatForum($owner, $alli, $name, $des, $area) {
        $q = "INSERT into " . TB_PREFIX . "forum_cat values (0,'$owner','$alli','$name','$des','$area')";
        $this->query($q);
        return $this->insertId();
    }

    function CreatTopic($title, $post, $cat, $owner, $alli, $ends, $alliance, $player, $coor, $report) {
        $date = time();
        $q = "INSERT into " . TB_PREFIX . "forum_topic values (0,'$title','$post','$date','$date','$cat','$owner','$alli','$ends','','','$alliance','$player','$coor','$report')";
        $this->query($q);
        return $this->insertId();
    }

    /*************************
            FORUM SUREY
    *************************/

  function createSurvey($topic, $title, $option1, $option2, $option3, $option4, $option5, $option6, $option7, $option8, $ends) {
        $q = "INSERT into " . TB_PREFIX . "forum_survey (topic,title,option1,option2,option3,option4,option5,option6,option7,option8,ends) values ('$topic','$title','$option1','$option2','$option3','$option4','$option5','$option6','$option7','$option8','$ends')";
        return $this->query($q);
    }

  function getSurvey($topic) {
    $q = "SELECT * FROM " . TB_PREFIX . "forum_survey where topic = $topic";
    $result = $this->query($q);
    return $this->fetchArray($result);
  }

  function checkSurvey($topic) {
    $q = "SELECT * FROM " . TB_PREFIX . "forum_survey where topic = $topic";
    $result = $this->query($q);
    if ($this->numRows($result)) {
    return true;
    } else {
    return false;
    }
    }

  function Vote($topic, $num, $text) {
    $q = "UPDATE " . TB_PREFIX . "forum_survey set vote".$num." = vote".$num." + 1, voted = '$text' where topic = ".$topic."";
    return $this->query($q);
  }

  function checkVote($topic, $uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "forum_survey where topic = $topic";
    $result = $this->query($q);
    $array = $this->fetchArray($result);
    $text = $array['voted'];
    if (preg_match('/,'.$uid.',/',$text)) {
      return true;
    } else {
      return false;
    }
    }

  function getVoteSum($topic) {
        $q = "SELECT * FROM " . TB_PREFIX . "forum_survey where topic = $topic";
    $result = $this->query($q);
    $array = $this->fetchArray($result);
    $sum = 0;
    for($i=1;$i<=8;$i++){
    $sum += $array['vote'.$i];
    }
    return $sum;
    }


    /*************************
            FORUM SUREY
    *************************/

    function CreatPost($post, $tids, $owner, $alliance, $player, $coor, $report) {
        $date = time();
        $q = "INSERT into " . TB_PREFIX . "forum_post values (0,'$post','$tids','$owner','$date','$alliance','$player','$coor','$report')";
        $this->query($q);
        return $this->insertId();
    }

    function UpdatePostDate($id) {
        $date = time();
        $q = "UPDATE " . TB_PREFIX . "forum_topic set post_date = '$date' where id = $id";
        return $this->query($q);
    }

    function EditUpdateTopic($id, $post, $alliance, $player, $coor, $report) {
        $q = "UPDATE " . TB_PREFIX . "forum_topic set post = '$post', alliance0 = '$alliance', player0 = '$player', coor0 = '$coor', report0 = '$report' where id = $id";
        return $this->query($q);
    }

    function EditUpdatePost($id, $post, $alliance, $player, $coor, $report) {
           $q = "UPDATE " . TB_PREFIX . "forum_post set post = '$post', alliance0 = '$alliance', player0 = '$player', coor0 = '$coor', report0 = '$report' where id = $id";
        return $this->query($q);
    }

    function LockTopic($id, $mode) {
        $q = "UPDATE " . TB_PREFIX . "forum_topic set close = '$mode' where id = '$id'";
        return $this->query($q);
    }

    function DeleteCat($id) {
        $qs = "DELETE from " . TB_PREFIX . "forum_cat where id = '$id'";
        $q = "DELETE from " . TB_PREFIX . "forum_topic where cat = '$id'";
        $q2="SELECT id from ".TB_PREFIX."forum_topic where cat ='$id'";
        $result = $this->query($q2);
        if (!empty($result)) {
            $array=$this->fetchAll($result);
            foreach($array as $ss) {
                $this->DeleteSurvey($ss['id']);
            }
        }
        $this->query($qs);
        
        return $this->query($q);
    }
    
    function DeleteSurvey($id)
    {
        $qs = "DELETE from " . TB_PREFIX . "forum_survey where topic = '$id'";
        
        return $this->query($qs);
    }

    function DeleteTopic($id)
    {
        $qs = "DELETE from " . TB_PREFIX . "forum_topic where id = '$id'";
        //  $q = "DELETE from ".TB_PREFIX."forum_post where topic = '$id'";//
        
        return $this->query($qs);
    }

    function DeletePost($id)
    {
        $q = "DELETE from " . TB_PREFIX . "forum_post where id = '$id'";
        
        return $this->query($q);
    }

    function getAllianceName($id) {
        $q = "SELECT tag from " . TB_PREFIX . "alidata where id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['tag'];
    }

    function getAlliancePermission($ref, $field, $mode)
    {
        if (!$mode) {
            $q = "SELECT $field FROM " . TB_PREFIX . "ali_permission where uid = '$ref'";
        } else {
            $q = "SELECT $field FROM " . TB_PREFIX . "ali_permission where username = '$ref'";
        }
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray[$field];
    }

    function getAlliance($id)
    {
        $q = "SELECT * from " . TB_PREFIX . "alidata where id = $id";
        $result = $this->query($q);
        
        return $this->fetchAssoc($result);
    }

    function setAlliName($aid, $name, $tag)
    {
        $q = "UPDATE " . TB_PREFIX . "alidata set name = '$name', tag = '$tag' where id = $aid";
        
        return $this->query($q);
    }

    function isAllianceOwner($id)
    {
        $q = "SELECT * from " . TB_PREFIX . "alidata where leader = '$id'";
        $result = $this->query($q);
        
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function aExist($ref, $type)
    {
        $q = "SELECT $type FROM " . TB_PREFIX . "alidata where $type = '$ref'";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    function modifyPoints($aid, $points, $amt)
    {
        $q = "UPDATE " . TB_PREFIX . "users set $points = $points + $amt where id = $aid";
        
        return $this->query($q);
    }

    function modifyPointsAlly($aid, $points, $amt)
    {
        $q = "UPDATE " . TB_PREFIX . "alidata set $points = $points + $amt where id = $aid";
        
        return $this->query($q);
    }

    /*****************************************
    Function to create an alliance
    References:
    *****************************************/
    function createAlliance($tag, $name, $uid, $max)
    {
        $q = "INSERT into " . TB_PREFIX . "alidata values (0,'$name','$tag',$uid,0,0,0,'','',$max,'','','','','','','','','')";
        $this->query($q);
        
        return $this->insertId();
    }
    
    function procAllyPop($aid)
    {
        $ally = $this->getAlliance($aid);
        $memberlist = $this->getAllMember($ally['id']);
        $oldrank = 0;
        
        foreach($memberlist as $member) {
            $oldrank += $this->getVSumField($member['id'],"pop");
        }
        
        if ($ally['oldrank'] != $oldrank){
            if ($ally['oldrank'] < $oldrank) {
                $totalpoints = $oldrank - $ally['oldrank'];
                $this->addclimberrankpopAlly($ally['id'], $totalpoints);
                $this->updateoldrankAlly($ally['id'], $oldrank);
            } elseif ($ally['oldrank'] > $oldrank) {
                $totalpoints = $ally['oldrank'] - $oldrank;
                $this->removeclimberrankpopAlly($ally['id'], $totalpoints);
                $this->updateoldrankAlly($ally['id'], $oldrank);
            }
        }
    }

    /*****************************************
    Function to insert an alliance new
    References:
    *****************************************/
    function insertAlliNotice($aid, $notice) {
        $time = time();
        $q = "INSERT into " . TB_PREFIX . "ali_log values (0,'$aid','$notice',$time)";
        $this->query($q);
        return $this->insertId();
    }

    /*****************************************
    Function to delete alliance if empty
    References:
    *****************************************/
    function deleteAlliance($aid)
    {
        $result = $this->query("SELECT * FROM " . TB_PREFIX . "users where alliance = $aid");
        $num_rows = $this->numRows($result);
        if ($num_rows == 0) {
            $q = "DELETE FROM " . TB_PREFIX . "alidata WHERE id = $aid";
        }
        $this->query($q);
        return $this->insertId();
    }

    /*****************************************
    Function to read all alliance news
    References:
    *****************************************/
    function readAlliNotice($aid)
    {
        $q = "SELECT * from " . TB_PREFIX . "ali_log where aid = $aid ORDER BY date DESC";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    /*****************************************
    Function to create alliance permissions
    References: ID, notice, description
    *****************************************/
    function createAlliPermissions($uid, $aid, $rank, $opt1, $opt2, $opt3, $opt4, $opt5, $opt6, $opt7, $opt8)
    {
        $q = "INSERT into " . TB_PREFIX . "ali_permission values(0,'$uid','$aid','$rank','$opt1','$opt2','$opt3','$opt4','$opt5','$opt6','$opt7','$opt8')";
        $this->query($q);
        
        return $this->insertId();
    }

    /*****************************************
    Function to update alliance permissions
    References:
    *****************************************/
    function deleteAlliPermissions($uid)
    {
        $q = "DELETE from " . TB_PREFIX . "ali_permission where uid = '$uid'";
        
        return $this->query($q);
    }
    
    /*****************************************
    Function to update alliance permissions
    References:
    *****************************************/
    function updateAlliPermissions($uid, $aid, $rank, $opt1, $opt2, $opt3, $opt4, $opt5, $opt6, $opt7)
    {

        $q = "UPDATE " . TB_PREFIX . "ali_permission SET rank = '$rank', opt1 = '$opt1', opt2 = '$opt2', opt3 = '$opt3', opt4 = '$opt4', opt5 = '$opt5', opt6 = '$opt6', opt7 = '$opt7' where uid = $uid && alliance =$aid";
        return $this->query($q);
    }

    /*****************************************
    Function to read alliance permissions
    References: ID, notice, description
    *****************************************/
    function getAlliPermissions($uid, $aid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "ali_permission where uid = $uid && alliance = $aid";
        $result = $this->query($q);
        
        return $this->fetchAssoc($result);
    }

    /*****************************************
    Function to update an alliance description and notice
    References: ID, notice, description
    *****************************************/
    function submitAlliProfile($aid, $notice, $desc)
    {
        $q = "UPDATE " . TB_PREFIX . "alidata SET `notice` = '$notice', `desc` = '$desc' where id = $aid";
        
        return $this->query($q);
    }

    function diplomacyInviteAdd($alli1, $alli2, $type)
    {
        $q = "INSERT INTO " . TB_PREFIX . "diplomacy (alli1,alli2,type,accepted) VALUES ($alli1,$alli2," . (int)intval($type) . ",0)";
        return $this->query($q);
    }

    function diplomacyOwnOffers($session_alliance) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE alli1 = $session_alliance AND accepted = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getAllianceID($name) {
        $q = "SELECT id FROM " . TB_PREFIX . "alidata WHERE tag ='" . $this->RemoveXSS($name) . "'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['id'];
    }

    function getDiplomacy($aid) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE id = $aid";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function diplomacyCancelOffer($id) {
        $q = "DELETE FROM " . TB_PREFIX . "diplomacy WHERE id = $id";
        return $this->query($q);
    }

    function diplomacyInviteAccept($id, $session_alliance) {
        $q = "UPDATE " . TB_PREFIX . "diplomacy SET accepted = 1 WHERE id = $id AND alli2 = $session_alliance";
        return $this->query($q);
    }

    function diplomacyInviteDenied($id, $session_alliance) {
        $q = "DELETE FROM " . TB_PREFIX . "diplomacy WHERE id = $id AND alli2 = $session_alliance";
        return $this->query($q);
    }

    function diplomacyInviteCheck($session_alliance) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE alli2 = $session_alliance AND accepted = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function diplomacyInviteCheck2($ally1, $ally2) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE alli1 = $ally1 AND alli2 = $ally2 accepted = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getAllianceDipProfile($aid, $type)
    {
        $q = "SELECT * FROM ".TB_PREFIX."diplomacy WHERE alli1 = '$aid' AND type = '$type' AND accepted = '1' OR alli2 = '$aid' AND type = '$type' AND accepted = '1'";
        $text = '';
        $array = $this->query_return($q);
            foreach($array as $row){
                if ($row['alli1'] == $aid){
                $alliance = $this->getAlliance($row['alli2']);
                }elseif($row['alli2'] == $aid){
                $alliance = $this->getAlliance($row['alli1']);
                }
                $text .= "";
                $text .= "<a href=allianz.php?aid=".$alliance['id'].">".$alliance['tag']."</a><br> ";
            }
        if (strlen($text) == 0){
            $text = "-<br>";
        }
        return $text;
    }

    function getAllianceWar($aid){
        $q = "SELECT * FROM ".TB_PREFIX."diplomacy WHERE alli1 = '$aid' AND type = '3' OR alli2 = '$aid' AND type = '3' AND accepted = '1'";
        $text = '';
        $array = $this->query_return($q);
            foreach($array as $row){
                if ($row['alli1'] == $aid){
                $alliance = $this->getAlliance($row['alli2']);
                }elseif($row['alli2'] == $aid){
                $alliance = $this->getAlliance($row['alli1']);
                }
                $text .= "";
                $text .= "<a href=allianz.php?aid=".$alliance['id'].">".$alliance['tag']."</a><br> ";
            }
        if (strlen($text) == 0){
            $text = "-<br>";
        }
        return $text;
    }

    function getAllianceAlly($aid, $type){
        $q = "SELECT * FROM ".TB_PREFIX."diplomacy WHERE (alli1 = '$aid' or alli2 = '$aid') AND (type = '$type' AND accepted = '1')";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getAllianceWar2($aid){
        $q = "SELECT * FROM ".TB_PREFIX."diplomacy WHERE alli1 = '$aid' AND type = '3' OR alli2 = '$aid' AND type = '3' AND accepted = '1'";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function diplomacyExistingRelationships($session_alliance) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE alli2 = $session_alliance AND accepted = 1";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function diplomacyExistingRelationships2($session_alliance) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE alli1 = $session_alliance AND accepted = 1";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function diplomacyCancelExistingRelationship($id, $session_alliance) {
        $q = "DELETE FROM " . TB_PREFIX . "diplomacy WHERE id = $id AND alli2 = $session_alliance OR id = $id AND alli1 = $session_alliance";
        return $this->query($q);
    }
    
    function checkDiplomacyInviteAccept($aid, $type) {
        $q = "SELECT * FROM " . TB_PREFIX . "diplomacy WHERE alli1 = $aid AND type = $type AND accepted = 1 OR alli2 = $aid AND type = $type AND accepted = 1";
        $result = $this->query($q);
        if ($type == 3){
            return true;
        } else {
        if ($this->numRows($result) < 4) {
            return true;
        } else {
            return false;
        }
        }
    }

    function setAlliForumLink($aid, $link) {
        $q = "UPDATE " . TB_PREFIX . "alidata SET `forumlink` = '$link' WHERE id = $aid";
        return $this->query($q);
    }

    function getUserAlliance($id) {
        $q = "SELECT " . TB_PREFIX . "alidata.tag from " . TB_PREFIX . "users join " . TB_PREFIX . "alidata where " . TB_PREFIX . "users.alliance = " . TB_PREFIX . "alidata.id and " . TB_PREFIX . "users.id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        if ($dbarray['tag'] == "") {
            return "-";
        } else {
            return $dbarray['tag'];
        }
    }

    /////////////ADDED BY BRAINIAC - THANK YOU

    function modifyResource($vid, $wood, $clay, $iron, $crop, $mode)
    {
        $q="SELECT wood,clay,iron,crop,maxstore,maxcrop from " . TB_PREFIX . "vdata where wref = ".$vid."";
        $result = $this->query($q);
        $checkres= $this->fetchAll($result);
        if (!$mode){
            $nwood=$checkres[0]['wood']-$wood;
            $nclay=$checkres[0]['clay']-$clay;
            $niron=$checkres[0]['iron']-$iron;
            $ncrop=$checkres[0]['crop']-$crop;
            if ($nwood<0 or $nclay<0 or $niron<0 or $ncrop<0){$shit=true;}
            $dwood=($nwood<0)?0:$nwood;
            $dclay=($nclay<0)?0:$nclay;
            $diron=($niron<0)?0:$niron;
            $dcrop=($ncrop<0)?0:$ncrop;
        } else {
            $nwood=$checkres[0]['wood']+$wood;
            $nclay=$checkres[0]['clay']+$clay;
            $niron=$checkres[0]['iron']+$iron;
            $ncrop=$checkres[0]['crop']+$crop;
            $dwood=($nwood>$checkres[0]['maxstore'])?$checkres[0]['maxstore']:$nwood;
            $dclay=($nclay>$checkres[0]['maxstore'])?$checkres[0]['maxstore']:$nclay;
            $diron=($niron>$checkres[0]['maxstore'])?$checkres[0]['maxstore']:$niron;
            $dcrop=($ncrop>$checkres[0]['maxcrop'])?$checkres[0]['maxcrop']:$ncrop;
        }
        
        if (!$shit){
            $q = "UPDATE " . TB_PREFIX . "vdata set wood = $dwood, clay = $dclay, iron = $diron, crop = $dcrop where wref = ".$vid;
                return $this->query($q); } else {return false;}
    }

    function modifyOasisResource($vid, $wood, $clay, $iron, $crop, $mode) {
        $q="SELECT wood,clay,iron,crop,maxstore,maxcrop from " . TB_PREFIX . "odata where wref = ".$vid."";
                $result = $this->query($q);
            $checkres= $this->fetchAll($result);
                if (!$mode){
            $nwood=$checkres[0]['wood']-$wood;
            $nclay=$checkres[0]['clay']-$clay;
            $niron=$checkres[0]['iron']-$iron;
            $ncrop=$checkres[0]['crop']-$crop;
            if ($nwood<0 or $nclay<0 or $niron<0 or $ncrop<0){$shit=true;}
            $dwood=($nwood<0)?0:$nwood;
            $dclay=($nclay<0)?0:$nclay;
            $diron=($niron<0)?0:$niron;
            $dcrop=($ncrop<0)?0:$ncrop;
        } else {
            $nwood=$checkres[0]['wood']+$wood;
            $nclay=$checkres[0]['clay']+$clay;
            $niron=$checkres[0]['iron']+$iron;
            $ncrop=$checkres[0]['crop']+$crop;
            $dwood=($nwood>$checkres[0]['maxstore'])?$checkres[0]['maxstore']:$nwood;
            $dclay=($nclay>$checkres[0]['maxstore'])?$checkres[0]['maxstore']:$nclay;
            $diron=($niron>$checkres[0]['maxstore'])?$checkres[0]['maxstore']:$niron;
            $dcrop=($ncrop>$checkres[0]['maxcrop'])?$checkres[0]['maxcrop']:$ncrop;
            }
        if (!$shit){
            $q = "UPDATE " . TB_PREFIX . "odata set wood = $dwood, clay = $dclay, iron = $diron, crop = $dcrop where wref = ".$vid;
                return $this->query($q); } else {return false;}
       }

    function getFieldLevel($vid, $field)
    {
        $q = "SELECT f" . $field . " from " . TB_PREFIX . "fdata where vref = $vid";
        $result = $this->query($q);
        
        return $this->fetchOne($result);
    }

    function getFieldType($vid, $field)
    {
        $q = "SELECT f" . $field . "t from " . TB_PREFIX . "fdata where vref = $vid";
        $result = $this->query($q);
    
        return $this->fetchOne($result);
    }
    
    function getFieldDistance($wid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where owner > 4 and wref != $wid";
        $array = $this->query_return($q);
        $coor = $this->getCoor($wid);
        $x1 = intval($coor['x']);
        $y1 = intval($coor['y']);
        $prevdist = 0;
        
        $q2 = "SELECT * FROM " . TB_PREFIX . "vdata where owner = 4";
        $array2 = $this->fetchArray($this->query($q2));
        $vill = $array2['wref'];
        
        $result = $this->query($q);
        if ($this->numRows($result) > 0){
            foreach($array as $village){
                $coor2 = $this->getCoor($village['wref']);
                $max = 2 * WORLD_MAX + 1;
                $x2 = intval($coor2['x']);
                $y2 = intval($coor2['y']);
                $distanceX = min(abs($x2 - $x1), abs($max - abs($x2 - $x1)));
                $distanceY = min(abs($y2 - $y1), abs($max - abs($y2 - $y1)));
                $dist = sqrt(pow($distanceX, 2) + pow($distanceY, 2));
                if ($dist < $prevdist or $prevdist == 0){
                    $prevdist = $dist;
                    $vill = $village['wref'];
                }
            }
        }
        return $vill;
        }

    function getVSumField($uid, $field) {
        if ($field != "cp"){
        $q = "SELECT sum(" . $field . ") FROM " . TB_PREFIX . "vdata where owner = $uid";
        } else {
        $q = "SELECT sum(" . $field . ") FROM " . TB_PREFIX . "vdata where owner = $uid and natar = 0";
        }
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        return $row[0];
    }

    function updateVillage($vid) {
        $time = time();
        $q = "UPDATE " . TB_PREFIX . "vdata set lastupdate = $time where wref = $vid";
        return $this->query($q);
    }
    
    function updateOasis($vid) {
        $time = time();
        $q = "UPDATE " . TB_PREFIX . "odata set lastupdated = $time where wref = $vid";
        return $this->query($q);
    }

    function updateOasis2($vid, $time) {
        $time = time();
        $time2 = NATURE_REGTIME;
        $q = "UPDATE " . TB_PREFIX . "odata set lastupdated2 = $time + $time2 where wref = $vid";
        return $this->query($q);
    }

    function setVillageName($vid, $name) {
        if (!empty($name))
        {
        $q = "UPDATE " . TB_PREFIX . "vdata set name = '$name' where wref = $vid";
        return $this->query($q);
        }
    }

    function modifyPop($vid, $pop, $mode) {
        if (!$mode) {
            $q = "UPDATE " . TB_PREFIX . "vdata set pop = pop + $pop where wref = $vid";
        } else {
            $q = "UPDATE " . TB_PREFIX . "vdata set pop = pop - $pop where wref = $vid";
        }
        return $this->query($q);
    }

    function addCP($ref, $cp) {
        $q = "UPDATE " . TB_PREFIX . "vdata set cp = cp + $cp where wref = $ref";
        return $this->query($q);
    }

    function addCel($ref, $cel, $type) {
        $q = "UPDATE " . TB_PREFIX . "vdata set celebration = $cel, type= $type where wref = $ref";
        return $this->query($q);
    }
    function getCel() {
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "vdata where celebration < $time AND celebration != 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function clearCel($ref) {
        $q = "UPDATE " . TB_PREFIX . "vdata set celebration = 0, type = 0 where wref = $ref";
        return $this->query($q);
    }
    function setCelCp($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set cp = cp + $cp where id = $user";
        return $this->query($q);
    }

    function clearExpansionSlot($id) {
        for($i = 1; $i <= 3; $i++) {
            $q = "UPDATE " . TB_PREFIX . "vdata SET exp" . $i . "=0 WHERE exp" . $i . "=" . $id;
            $this->query($q);
        }
    }

    function getInvitation($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "ali_invite where uid = $uid";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function getInvitation2($uid, $aid) {
        $q = "SELECT * FROM " . TB_PREFIX . "ali_invite where uid = $uid and alliance = $aid";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getAliInvitations($aid) {
        $q = "SELECT * FROM " . TB_PREFIX . "ali_invite where alliance = $aid && accept = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function sendInvitation($uid, $alli, $sender) {
        $time = time();
        $q = "INSERT INTO " . TB_PREFIX . "ali_invite values (0,$uid,$alli,$sender,$time,0)";
        return $this->query($q);
    }

    function removeInvitation($id) {
        $q = "DELETE FROM " . TB_PREFIX . "ali_invite where id = $id";
        return $this->query($q);
    }

    function sendMessage($client, $owner, $topic, $message, $send, $alliance, $player, $coor, $report)
    {
        $time = time();
        $q = "INSERT INTO " . TB_PREFIX . "mdata values (0,$client,$owner,'$topic',\"$message\",0,0,$send,$time,0,0,$alliance,$player,$coor,$report)";
        return $this->query($q);
    }

    function setArchived($id) {
        $q = "UPDATE " . TB_PREFIX . "mdata set archived = 1 where id = $id";
        return $this->query($q);
    }

    function setNorm($id) {
        $q = "UPDATE " . TB_PREFIX . "mdata set archived = 0 where id = $id";
        return $this->query($q);
    }

    /***************************
    Function to get messages
    Mode 1: Get inbox
    Mode 2: Get sent
    Mode 3: Get message
    Mode 4: Set viewed
    Mode 5: Remove message
    Mode 6: Retrieve archive
    References: User ID/Message ID, Mode
    ***************************/
    function getMessage($id, $mode) {
        global $session;
        switch($mode) {
            case 1:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE target = $id and send = 0 and archived = 0 ORDER BY time DESC";
                break;
            case 2:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE owner = $id ORDER BY time DESC";
                break;
            case 3:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata where id = $id";
                break;
            case 4:
                $q = "UPDATE " . TB_PREFIX . "mdata set viewed = 1 where id = $id AND target = $session->uid";
                break;
            case 5:
                $q = "UPDATE " . TB_PREFIX . "mdata set deltarget = 1,viewed = 1 where id = $id";
                break;
            case 6:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata where target = $id and send = 0 and archived = 1";
                break;
            case 7:
                $q = "UPDATE " . TB_PREFIX . "mdata set delowner = 1 where id = $id";
                break;
            case 8:
                $q = "UPDATE " . TB_PREFIX . "mdata set deltarget = 1,delowner = 1,viewed = 1 where id = $id";
                break;
            case 9:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE target = $id and send = 0 and archived = 0 and deltarget = 0 ORDER BY time DESC";
                break;
            case 10:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE owner = $id and delowner = 0 ORDER BY time DESC";
                break;
            case 11:
                $q = "SELECT * FROM " . TB_PREFIX . "mdata where target = $id and send = 0 and archived = 1 and deltarget = 0";
                break;
        }
        if ($mode <= 3 || $mode == 6 || $mode > 8) {
            $result = $this->query($q);
            return $this->fetchAll($result);
        } else {
            return $this->query($q);
        }
    }

    function getDelSent($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE owner = $uid and delowner = 1 ORDER BY time DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getDelInbox($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE target = $uid and deltarget = 1 ORDER BY time DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getDelArchive($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "mdata WHERE target = $uid and archived = 1 and deltarget = 1 OR owner = $uid and archived = 1 and delowner = 1 ORDER BY time DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function unarchiveNotice($id) {
        $q = "UPDATE " . TB_PREFIX . "ndata set ntype = archive, archive = 0 where id = $id";
        return $this->query($q);
    }

    function archiveNotice($id) {
        $q = "update " . TB_PREFIX . "ndata set archive = ntype, ntype = 9 where id = $id";
        return $this->query($q);
    }

    function removeNotice($id) {
        $q = "UPDATE " . TB_PREFIX . "ndata set del = 1,viewed = 1 where id = $id";
        return $this->query($q);
    }

    function noticeViewed($id) {
        $q = "UPDATE " . TB_PREFIX . "ndata set viewed = 1 where id = $id";
        return $this->query($q);
    }

        function addNotice($uid, $toWref, $ally, $type, $topic, $data, $time = 0) {
            if ($time == 0) {
            $time = time();
            }
            $q = "INSERT INTO " . TB_PREFIX . "ndata (id, uid, toWref, ally, topic, ntype, data, time, viewed) values (0,'$uid','$toWref','$ally','$topic',$type,'$data',$time,0)";
            return $this->query($q);
        }

    function getNotice($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "ndata where uid = $uid and del = 0 ORDER BY time DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getNotice2($id, $field) {
        $q = "SELECT ".$field." FROM " . TB_PREFIX . "ndata where `id` = '$id'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray[$field];
    }

    function getNotice3($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "ndata where uid = $uid ORDER BY time DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getNotice4($id) {
        $q = "SELECT * FROM " . TB_PREFIX . "ndata where id = $id ORDER BY time DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    function getUnViewNotice($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "ndata where uid = $uid AND viewed=0";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }
    
    function createTradeRoute($uid,$wid,$from,$r1,$r2,$r3,$r4,$start,$deliveries,$merchant,$time)
    {
        $x = "UPDATE " . TB_PREFIX . "users SET gold = gold - 2 WHERE id = ".$uid."";
        $this->query($x);
        $timeleft = time()+604800;
        $q = "INSERT into " . TB_PREFIX . "route values (0,$uid,$wid,$from,$r1,$r2,$r3,$r4,$start,$deliveries,$merchant,$time,$timeleft)";
        
        return $this->query($q);
    }

    function getTradeRoute($uid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "route where uid = $uid ORDER BY timestamp ASC";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    function getTradeRoute2($id)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "route where id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray;
    }

    function getTradeRouteUid($id)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "route where id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        return $dbarray['uid'];
    }

    function editTradeRoute($id, $column, $value, $mode)
    {
        if (!$mode){
            $q = "UPDATE " . TB_PREFIX . "route set $column = $value where id = $id";
        } else {
            $q = "UPDATE " . TB_PREFIX . "route set $column = $column + $value where id = $id";
        }
        
        return $this->query($q);
    }

    function deleteTradeRoute($id)
    {
        $q = "DELETE FROM " . TB_PREFIX . "route where id = $id";
        
        return $this->query($q);
    }

    function addBuilding($wid, $field, $type, $loop, $time, $master, $level)
    {
        $x = "UPDATE " . TB_PREFIX . "fdata SET f" . $field . "t=" . $type . " WHERE vref=" . $wid;
        $this->query($x);
        
        $q = "INSERT into " . TB_PREFIX . "bdata values (0,$wid,$field,$type,$loop,$time,$master,$level)";
        return $this->query($q);
    }

    function removeBuilding($d)
    {
        global $building, $village;
        
        $jobLoopconID = -1;
        $SameBuildCount = 0;
        $jobs = $building->buildArray;
        for($i = 0; $i < sizeof($jobs); $i++) {
            if ($jobs[$i]['id'] == $d) {
                $jobDeleted = $i;
            }
            if ($jobs[$i]['loopcon'] == 1) {
                $jobLoopconID = $i;
            }
            if ($jobs[$i]['master'] == 1) {
                $jobMaster = $i;
            }
        }
        if (count($jobs) > 1 && ($jobs[0]['field'] == $jobs[1]['field'])) {
            $SameBuildCount = 1;
        }
        if (count($jobs) > 2 && ($jobs[0]['field'] == $jobs[2]['field'])) {
            $SameBuildCount = 2;
        }
        if (count($jobs) > 2 && ($jobs[1]['field'] == $jobs[2]['field'])) {
            $SameBuildCount = 3;
        }
        if (count($jobs) > 3 && ($jobs[0]['field'] == $jobs[3]['field'])) {
            $SameBuildCount = 8;
        }
        if (count($jobs) > 3 && ($jobs[1]['field'] == $jobs[3]['field'])) {
            $SameBuildCount = 9;
        }
        if (count($jobs) > 3 && ($jobs[2]['field'] == $jobs[3]['field'])) {
            $SameBuildCount = 10;
        }
        if (count($jobs) > 2 && ($jobs[0]['field'] == $jobs[1]['field'] && $jobs[1]['field'] == $jobs[2]['field'])) {
            $SameBuildCount = 4;
        }
        if (count($jobs) > 3 && ($jobs[0]['field'] == $jobs[1]['field'] && $jobs[1]['field'] == $jobs[3]['field'])) {
            $SameBuildCount = 5;
        }
        if (count($jobs) > 3 && ($jobs[0]['field'] == $jobs[2]['field'] && $jobs[2]['field'] == $jobs[3]['field'])) {
            $SameBuildCount = 6;
        }
        if (count($jobs) > 3 && ($jobs[1]['field'] == $jobs[2]['field'] && $jobs[2]['field'] == $jobs[3]['field'])) {
            $SameBuildCount = 7;
        }
    
        if ($SameBuildCount > 0) {
            if ($SameBuildCount > 3) {
                if ($SameBuildCount == 4 or $SameBuildCount == 5) {
                    if ($jobDeleted == 0) {
                        $uprequire = $building->resourceRequired($jobs[1]['field'], $jobs[1]['type'], 1);
                        $time = $uprequire['time'];
                        $timestamp = $time + time();
                        $q = "UPDATE " . TB_PREFIX . "bdata SET loopcon=0,level=level-1,timestamp=" . $timestamp . " WHERE id=" . $jobs[1]['id'] . "";
                        $this->query($q);
                    }
                }
                elseif ($SameBuildCount == 6) {
                    if ($jobDeleted == 0) {
                        $uprequire = $building->resourceRequired($jobs[2]['field'], $jobs[2]['type'], 1);
                        $time = $uprequire['time'];
                        $timestamp = $time + time();
                        $q = "UPDATE " . TB_PREFIX . "bdata SET loopcon=0,level=level-1,timestamp=" . $timestamp . " WHERE id=" . $jobs[2]['id'] . "";
                        $this->query($q);
                    }
                }
                elseif ($SameBuildCount == 7) {
                    if ($jobDeleted == 1) {
                        $uprequire = $building->resourceRequired($jobs[2]['field'], $jobs[2]['type'], 1);
                        $time = $uprequire['time'];
                        $timestamp = $time + time();
                        $q = "UPDATE " . TB_PREFIX . "bdata SET loopcon=0,level=level-1,timestamp=" . $timestamp . " WHERE id=" . $jobs[2]['id'] . "";
                        $this->query($q);
                    }
                }
                if ($SameBuildCount < 8) {
                    $uprequire1 = $building->resourceRequired($jobs[ $jobMaster ]['field'], $jobs[ $jobMaster ]['type'], 2);
                    $time1 = $uprequire1['time'];
                    $timestamp1 = $time1;
                    $q1 = "UPDATE " . TB_PREFIX . "bdata SET level=level-1,timestamp=" . $timestamp1 . " WHERE id=" . $jobs[ $jobMaster ]['id'] . "";
                    $this->query($q1);
                }
                else {
                    $uprequire1 = $building->resourceRequired($jobs[ $jobMaster ]['field'], $jobs[ $jobMaster ]['type'], 1);
                    $time1 = $uprequire1['time'];
                    $timestamp1 = $time1;
                    $q1 = "UPDATE " . TB_PREFIX . "bdata SET level=level-1,timestamp=" . $timestamp1 . " WHERE id=" . $jobs[ $jobMaster ]['id'] . "";
                    $this->query($q1);
                }
            }
            elseif ($d == $jobs[ floor($SameBuildCount / 3) ]['id'] || $d == $jobs[ floor($SameBuildCount / 2) + 1 ]['id']) {
                $q = "UPDATE " . TB_PREFIX . "bdata SET loopcon=0,level=level-1,timestamp=" . $jobs[ floor($SameBuildCount / 3) ]['timestamp'] . " WHERE master = 0 AND id > " . $d . " and (ID=" . $jobs[ floor($SameBuildCount / 3) ]['id'] . " OR ID=" . $jobs[ floor($SameBuildCount / 2) + 1 ]['id'] . ")";
                $this->query($q);
            }
        }
        else {
            if ($jobs[ $jobDeleted ]['field'] >= 19) {
                $x = "SELECT f" . $jobs[ $jobDeleted ]['field'] . " FROM " . TB_PREFIX . "fdata WHERE vref=" . $jobs[ $jobDeleted ]['wid'];
                $result = $this->query($x);
                $fieldlevel = $this->fetchRow($result);
                if ($fieldlevel[0] == 0) {
                    if ($village->natar == 1 && $jobs[ $jobDeleted ]['field'] == 99) { //fix by ronix
                    }
                    else {
                        $x = "UPDATE " . TB_PREFIX . "fdata SET f" . $jobs[ $jobDeleted ]['field'] . "t=0 WHERE vref=" . $jobs[ $jobDeleted ]['wid'];
                        $this->query($x);
                    }
                }
            }
            if (($jobLoopconID >= 0) && ($jobs[ $jobDeleted ]['loopcon'] != 1)) {
                if (($jobs[ $jobLoopconID ]['field'] <= 18 && $jobs[ $jobDeleted ]['field'] <= 18) || ($jobs[ $jobLoopconID ]['field'] >= 19 && $jobs[ $jobDeleted ]['field'] >= 19) || sizeof($jobs) < 3) {
                    $uprequire = $building->resourceRequired($jobs[ $jobLoopconID ]['field'], $jobs[ $jobLoopconID ]['type']);
                    $x = "UPDATE " . TB_PREFIX . "bdata SET loopcon=0,timestamp=" . (time() + $uprequire['time']) . " WHERE wid=" . $jobs[ $jobDeleted ]['wid'] . " AND loopcon=1 AND master=0";
                    $this->query($x);
                }
            }
        }
        
        $q = "DELETE FROM " . TB_PREFIX . "bdata where id = $d";
        
        return $this->query($q);
    }

    function addDemolition($wid, $field) {
        global $building, $village;
        $q = "DELETE FROM ".TB_PREFIX."bdata WHERE field=$field AND wid=$wid";
        $this->query($q);
        $uprequire = $building->resourceRequired($field,$village->resarray['f'.$field.'t'],0);
        $q = "INSERT INTO ".TB_PREFIX."demolition VALUES (".$wid.",".$field.",".($this->getFieldLevel($wid,$field)-1).",".(time()+floor($uprequire['time']/2)).")";
        return $this->query($q);
    }


    function getDemolition($wid = 0) {
        if ($wid) {
            $q = "SELECT * FROM " . TB_PREFIX . "demolition WHERE vref=" . $wid;
        } else {
            $q = "SELECT * FROM " . TB_PREFIX . "demolition WHERE timetofinish<=" . time();
        }
        $result = $this->query($q);
        if (!empty($result)) {
            return $this->fetchAll($result);
        } else {
            return NULL;
        }
    }

    function finishDemolition($wid)
    {
        $q = "UPDATE " . TB_PREFIX . "demolition SET timetofinish=" . time() . " WHERE vref=" . $wid;
        $this->query($q);
        
        return $this->affectedRows();
    }

    function delDemolition($wid)
    {
        $q = "DELETE FROM " . TB_PREFIX . "demolition WHERE vref=" . $wid;
        
        return $this->query($q);
    }

    function getJobs($wid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid order by master,timestamp ASC";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    function FinishWoodcutter($wid)
    {
        $time = time()-1;
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and type = 1 order by master,timestamp ASC";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        
        $q = "UPDATE ".TB_PREFIX."bdata SET timestamp = $time WHERE id = '".$dbarray['id']."'";
        $this->query($q);
        
        $tribe = $this->getUserField($this->getVillageField($wid, "owner"), "tribe", 0);
        
        if ($tribe == 1) {
            $q2 = "SELECT * FROM " . TB_PREFIX . "bdata WHERE wid = $wid and loopcon = 1 and field >= 19 order by master, timestamp ASC";
        } else {
            $q2 = "SELECT * FROM " . TB_PREFIX . "bdata WHERE wid = $wid and loopcon = 1 order by master, timestamp ASC";
        }
        $result2 = $this->query($q2);
        
        if ($this->numRows($result2) > 0){
            $dbarray2 = mysqli_fetch_array($result2);
            $wc_time = $dbarray['timestamp'];
            $q2 = "UPDATE ".TB_PREFIX."bdata SET timestamp = timestamp - $wc_time WHERE id = '".$dbarray2['id']."'";
            $this->query($q2);
        }
    }

    function getMasterJobs($wid)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and master = 1 order by master,timestamp ASC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getMasterJobsByField($wid,$field) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and field = $field and master = 1 order by master,timestamp ASC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getBuildingByField($wid,$field) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and field = $field and master = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function getBuildingByField2($wid,$field) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and field = $field and master = 0";
        $result = $this->query($q);
        return $this->numRows($result);
    }

    function getBuildingByType($wid,$type) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and type = $type and master = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function getBuildingByType2($wid,$type) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and type = $type and master = 0";
        $result = $this->query($q);
        return $this->numRows($result);
    }

    function getDorf1Building($wid) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and field < 19 and master = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getDorf2Building($wid) {
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where wid = $wid and field > 18 and master = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function updateBuildingWithMaster($id, $time,$loop) {
        $q = "UPDATE " . TB_PREFIX . "bdata SET master = 0, timestamp = ".$time.",loopcon = ".$loop." WHERE id = ".$id."";
        return $this->query($q);
    }

    function getVillageByName($name) {
        $q = "SELECT wref FROM " . TB_PREFIX . "vdata where name = '$name' limit 1";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['wref'];
    }

    /***************************
    Function to set accept flag on market
    References: id
    ***************************/
    function setMarketAcc($id) {
        $q = "UPDATE " . TB_PREFIX . "market set accept = 1 where id = $id";
        return $this->query($q);
    }

    /***************************
    Function to send resource to other village
    Mode 0: Send
    Mode 1: Cancel
    References: Wood/ID, Clay, Iron, Crop, Mode
    ***************************/
    function sendResource($ref, $clay, $iron, $crop, $merchant, $mode) {
        if (!$mode) {
            $q = "INSERT INTO " . TB_PREFIX . "send values (0,$ref,$clay,$iron,$crop,$merchant)";
            $this->query($q);
            return $this->insertId();
        } else {
            $q = "DELETE FROM " . TB_PREFIX . "send where id = $ref";
            return $this->query($q);
        }
    }

    /***************************
    Function to get resources back if you delete offer
    References: VillageRef (vref)
    Made by: Dzoki
    ***************************/

    function getResourcesBack($vref, $gtype, $gamt) {
        //Xtype (1) = wood, (2) = clay, (3) = iron, (4) = crop
        if ($gtype == 1) {
            $q = "UPDATE " . TB_PREFIX . "vdata SET `wood` = `wood` + '$gamt' WHERE wref = $vref";
            return $this->query($q);
        } else
            if ($gtype == 2) {
                $q = "UPDATE " . TB_PREFIX . "vdata SET `clay` = `clay` + '$gamt' WHERE wref = $vref";
                return $this->query($q);
            } else
                if ($gtype == 3) {
                    $q = "UPDATE " . TB_PREFIX . "vdata SET `iron` = `iron` + '$gamt' WHERE wref = $vref";
                    return $this->query($q);
                } else
                    if ($gtype == 4) {
                        $q = "UPDATE " . TB_PREFIX . "vdata SET `crop` = `crop` + '$gamt' WHERE wref = $vref";
                        return $this->query($q);
                    }
    }

    /***************************
    Function to get info about offered resources
    References: VillageRef (vref)
    Made by: Dzoki
    ***************************/

    function getMarketField($vref, $field) {
        $q = "SELECT $field FROM " . TB_PREFIX . "market where vref = '$vref'";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray[$field];
    }

    function removeAcceptedOffer($id) {
        $q = "DELETE FROM " . TB_PREFIX . "market where id = $id";
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }

    /***************************
    Function to add market offer
    Mode 0: Add
    Mode 1: Cancel
    References: Village, Give, Amt, Want, Amt, Time, Alliance, Mode
    ***************************/
    function addMarket($vid, $gtype, $gamt, $wtype, $wamt, $time, $alliance, $merchant, $mode) {
        if (!$mode) {
            $q = "INSERT INTO " . TB_PREFIX . "market values (0,$vid,$gtype,$gamt,$wtype,$wamt,0,$time,$alliance,$merchant)";
            $this->query($q);
            return $this->insertId();
        } else {
            $q = "DELETE FROM " . TB_PREFIX . "market where id = $gtype and vref = $vid";
            return $this->query($q);
        }
    }

    /***************************
    Function to get market offer
    References: Village, Mode
    ***************************/
    function getMarket($vid, $mode) {
        $alliance = $this->getUserField($this->getVillageField($vid, "owner"), "alliance", 0);
        if (!$mode) {
            $q = "SELECT * FROM " . TB_PREFIX . "market where vref = $vid and accept = 0";
        } else {
            $q = "SELECT * FROM " . TB_PREFIX . "market where vref != $vid and alliance = $alliance or vref != $vid and alliance = 0 and accept = 0";
        }
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    /***************************
    Function to get market offer
    References: ID
    ***************************/
    function getMarketInfo($id) {
        $q = "SELECT * FROM " . TB_PREFIX . "market where id = $id";
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }

    function setMovementProc($moveid) {
        $q = "UPDATE " . TB_PREFIX . "movement set proc = 1 where moveid = $moveid";
        return $this->query($q);
    }

    /***************************
    Function to retrieve used merchant
    References: Village
    ***************************/
    function totalMerchantUsed($vid)
    {
        $time = time();
        $q = "SELECT sum(" . TB_PREFIX . "send.merchant)
              FROM " . TB_PREFIX . "send, " . TB_PREFIX . "movement
              WHERE " . TB_PREFIX . "movement.from = $vid
                AND " . TB_PREFIX . "send.id = " . TB_PREFIX . "movement.ref
                AND " . TB_PREFIX . "movement.proc = 0
                AND sort_type = 0";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        
        $q2 = "SELECT sum(ref) from " . TB_PREFIX . "movement where sort_type = 2 and " . TB_PREFIX . "movement.to = $vid and proc = 0";
        $result2 = $this->query($q2);
        $row2 = $this->fetchRow($result2);
        
        $q3 = "SELECT sum(merchant) from " . TB_PREFIX . "market where vref = $vid and accept = 0";
        $result3 = $this->query($q3);
        $row3 = $this->fetchRow($result3);
        
        return $row[0] + $row2[0] + $row3[0];
    }

    function getMovement($type, $village, $mode)
    {
        $time = time();
        if (!$mode) {
            $where = "from";
        } else {
            $where = "to";
        }
        
        switch($type) {
            case 0:
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "send where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "send.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 0 ORDER BY endtime ASC";
                break;
            case 1:
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "send where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "send.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 6 ORDER BY endtime ASC";
                break;
            case 2:
                $q = "SELECT * FROM " . TB_PREFIX . "movement where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 2 ORDER BY endtime ASC";
                break;
            case 3:
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 ORDER BY endtime ASC";
                break;
            case 4:
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 4 ORDER BY endtime ASC";
                break;
            case 5:
                $q = "SELECT * FROM " . TB_PREFIX . "movement where " . TB_PREFIX . "movement." . $where . " = $village and sort_type = 5 and proc = 0 ORDER BY endtime ASC";
                break;
            case 6:
                $q = "SELECT * FROM " . TB_PREFIX . "movement," . TB_PREFIX . "odata, " . TB_PREFIX . "attacks where " . TB_PREFIX . "odata.wref = $village and " . TB_PREFIX . "movement.to = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "attacks.attack_type != 1 and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 ORDER BY endtime ASC";
                //$q = "SELECT * FROM " . TB_PREFIX . "movement," . TB_PREFIX . "odata, " . TB_PREFIX . "attacks where " . TB_PREFIX . "odata.conqured = $village and " . TB_PREFIX . "movement.to = " . TB_PREFIX . "odata.wref and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 ORDER BY endtime ASC";
                break;
            case 7:
                $q = "SELECT * FROM " . TB_PREFIX . "movement where " . TB_PREFIX . "movement." . $where . " = $village and sort_type = 4 and ref = 0 and proc = 0 ORDER BY endtime ASC";
                break;
            case 8:
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 and " . TB_PREFIX . "attacks.attack_type = 1 ORDER BY endtime ASC";
                break;
            case 34:
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 or " . TB_PREFIX . "movement." . $where . " = $village and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 4 ORDER BY endtime ASC";
                break;
        }
        
        if (empty($q)) {
            return [];
        }
        
        $result = $this->query($q);
        $array = $this->fetchAll($result);
        return $array;
    }

    function addA2b($ckey, $timestamp, $to, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10, $t11, $type) {
        $q = "INSERT INTO " . TB_PREFIX . "a2b (ckey,time_check,to_vid,u1,u2,u3,u4,u5,u6,u7,u8,u9,u10,u11,type) VALUES ('$ckey', '$timestamp', '$to', '$t1', '$t2', '$t3', '$t4', '$t5', '$t6', '$t7', '$t8', '$t9', '$t10', '$t11', '$type')";
        $this->query($q);
        return $this->insertId();
    }

    function getA2b($ckey, $check) {
        $q = "SELECT * from " . TB_PREFIX . "a2b where ckey = '" . $ckey . "' AND time_check = '" . $check . "'";
        $result = $this->query($q);
        if ($result) {
            return $this->fetchAssoc($result);
        } else {
            return false;
        }
    }

    function addMovement($type, $from, $to, $ref, $time, $endtime, $send = 1, $wood = 0, $clay = 0, $iron = 0, $crop = 0, $ref2 = 0) {
        $q = "INSERT INTO " . TB_PREFIX . "movement values (0,$type,$from,$to,$ref,$ref2,$time,$endtime,0,$send,$wood,$clay,$iron,$crop)";
        return $this->query($q);
    }

    function addAttack($vid, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10, $t11, $type, $ctar1, $ctar2, $spy,$b1=0,$b2=0,$b3=0,$b4=0,$b5=0,$b6=0,$b7=0,$b8=0) {
        $q = "INSERT INTO " . TB_PREFIX . "attacks values (0,$vid,$t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9,$t10,$t11,$type,$ctar1,$ctar2,$spy,$b1,$b2,$b3,$b4,$b5,$b6,$b7,$b8)";
        $this->query($q);
        return $this->insertId();
    }

    function modifyAttack($aid, $unit, $amt) {
        $unit = 't' . $unit;
        $q = "UPDATE " . TB_PREFIX . "attacks set $unit = $unit - $amt where id = $aid";
        return $this->query($q);
    }
    
    function modifyAttack2($aid, $unit, $amt) {
        $unit = 't' . $unit;
        $q = "UPDATE " . TB_PREFIX . "attacks set $unit = $unit + $amt where id = $aid";
        return $this->query($q);
    }
    
    function modifyAttack3($aid, $units) {
        $q = "UPDATE ".TB_PREFIX."attacks set $units WHERE id = $aid";
        return $this->query($q);
    }

    function getRanking() {
        $q = "SELECT id,username,alliance,ap,apall,dp,dpall,access FROM " . TB_PREFIX . "users WHERE tribe<=3 AND access<" . (INCLUDE_ADMIN ? "10" : "8");
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getVRanking() {
        $q = "SELECT v.wref,v.name,v.owner,v.pop FROM " . TB_PREFIX . "vdata AS v," . TB_PREFIX . "users AS u WHERE v.owner=u.id AND u.tribe<=3 AND v.wref != '' AND u.access<" . (INCLUDE_ADMIN ? "10" : "8");
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getARanking() {
        $q = "SELECT id,name,tag,oldrank,Aap,Adp FROM " . TB_PREFIX . "alidata where id != '' ORDER BY id DESC";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getUserByTribe($tribe){
        $q = "SELECT * FROM " . TB_PREFIX . "users where tribe = $tribe";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getUserByAlliance($aid){
        $q = "SELECT * FROM " . TB_PREFIX . "users where alliance = $aid";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getHeroRanking() {
        $q = "SELECT * FROM " . TB_PREFIX . "hero WHERE dead = 0";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getAllMember($aid) {
        $q = "SELECT * FROM " . TB_PREFIX . "users where alliance = $aid order  by (SELECT sum(pop) FROM " . TB_PREFIX . "vdata WHERE owner =  " . TB_PREFIX . "users.id) desc, " . TB_PREFIX . "users.id desc";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function getAllMember2($aid) {
        $q = "SELECT * FROM " . TB_PREFIX . "users where alliance = $aid order  by (SELECT sum(pop) FROM " . TB_PREFIX . "vdata WHERE owner =  " . TB_PREFIX . "users.id) desc, " . TB_PREFIX . "users.id desc LIMIT 1";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }

    function addUnits($vid) {
        $q = "INSERT into " . TB_PREFIX . "units (vref) values ($vid)";
        return $this->query($q);
    }

    function getUnit($vid) {
        $q = "SELECT * from " . TB_PREFIX . "units where vref = $vid";
        $result = $this->query($q);
        if (!empty($result)) {
            return $this->fetchAssoc($result);
        } else {
            return NULL;
        }
    }

    function getUnitsNumber($vid) {
        $q = "SELECT * from " . TB_PREFIX . "units where vref = $vid";
        $result = $this->query($q);
        $dbarray = $this->fetchAssoc($result);
        $totalunits = 0;
        $movingunits = $this->getVillageMovement($vid);
        for($i=1;$i<=50;$i++){
        $totalunits += $dbarray['u'.$i];
        }
        $totalunits += $dbarray['hero'];
        $movingunits = $this->getVillageMovement($vid);
        $reinforcingunits = $this->getEnforceArray($vid,1);
        $owner = $this->getVillageField($vid,"owner");
        $ownertribe = $this->getUserField($owner,"tribe",0);
        $start = ($ownertribe-1)*10+1;
        $end = ($ownertribe*10);
        for($i=$start;$i<=$end;$i++){
        $totalunits += $movingunits['u'.$i];
        $totalunits += $reinforcingunits['u'.$i];
        }
        $totalunits += $movingunits['hero'];
        $totalunits += $reinforcingunits['hero'];
        return $totalunits;
    }

    function getHero($uid=0,$all=0) {
        if ($all) {
            $q = "SELECT * FROM ".TB_PREFIX."hero WHERE uid=$uid";
        } elseif (!$uid) {
            $q = "SELECT * FROM ".TB_PREFIX."hero";
        } else {
            $q = "SELECT * FROM ".TB_PREFIX."hero WHERE dead=0 AND uid=$uid LIMIT 1";
        }
        $result = $this->query($q);
        if (!empty($result)) {
            return $this->fetchAll($result);
        } else {
            return NULL;
        }
    }

    function getHeroField($uid,$field)
    {
        $q = "SELECT * FROM ".TB_PREFIX."hero WHERE uid = $uid";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }

    function modifyHero($column,$value,$heroid,$mode=0)
    {
        if (!$mode) {
            $q = "UPDATE `".TB_PREFIX."hero` SET $column = $value WHERE heroid = $heroid";
        } elseif($mode=1) {
            $q = "UPDATE `".TB_PREFIX."hero` SET $column = $column + $value WHERE heroid = $heroid";
        } else {
            $q = "UPDATE `".TB_PREFIX."hero` SET $column = $column - $value WHERE heroid = $heroid";
        }
        
        return $this->query($q);
    }

    function modifyHeroByOwner($column,$value,$uid,$mode=0) {
        if (!$mode) {
            $q = "UPDATE `".TB_PREFIX."hero` SET $column = $value WHERE uid = $uid";
        } elseif($mode=1) {
            $q = "UPDATE `".TB_PREFIX."hero` SET $column = $column + $value WHERE uid = $uid";
        } else {
            $q = "UPDATE `".TB_PREFIX."hero` SET $column = $column - $value WHERE uid = $uid";
        }
        return $this->query($q);
    }

    function modifyHeroXp($column,$value,$heroid) {
        $q = "UPDATE ".TB_PREFIX."hero SET $column = $column + $value WHERE uid=$heroid";
        return $this->query($q);
    }

    function addTech($vid) {
        $q = "INSERT into " . TB_PREFIX . "tdata (vref) values ($vid)";
        return $this->query($q);
    }

    function addABTech($vid) {
        $q = "INSERT into " . TB_PREFIX . "abdata (vref) values ($vid)";
        return $this->query($q);
    }

    function getABTech($vid) {
        $q = "SELECT * FROM " . TB_PREFIX . "abdata where vref = $vid";
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }

    function addResearch($vid, $tech, $time) {
        $q = "INSERT into " . TB_PREFIX . "research values (0,$vid,'$tech',$time)";
        return $this->query($q);
    }

    function getResearching($vid) {
        $q = "SELECT * FROM " . TB_PREFIX . "research where vref = $vid";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function checkIfResearched($vref, $unit) {
        $q = "SELECT $unit FROM " . TB_PREFIX . "tdata WHERE vref = $vref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray[$unit];
    }

    function getTech($vid) {
        $q = "SELECT * from " . TB_PREFIX . "tdata where vref = $vid";
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }

    function getTraining($vid) {
        $q = "SELECT * FROM " . TB_PREFIX . "training where vref = $vid ORDER BY id";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function countTraining($vid) {
        $q = "SELECT * FROM " . TB_PREFIX . "training WHERE vref = $vid";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        return $row[0];
    }

    function trainUnit($vid, $unit, $amt, $pop, $each, $time, $mode) {
        global $village, $building, $session, $technology;

        if (!$mode) {
            $barracks = array(1,2,3,11,12,13,14,21,22,31,32,33,34,35,36,37,38,39,40,41,42,43,44);
            // fix by brainiac - THANK YOU
            $greatbarracks = array(61,62,63,71,72,73,74,81,82,91,92,93,94,95,96,97,98,99,100,101,102,103,104);
            $stables = array(4,5,6,15,16,23,24,25,26,45,46);
            $greatstables = array(64,65,66,75,76,83,84,85,86,105,106);
            $workshop = array(7,8,17,18,27,28,47,48);
            $greatworkshop = array(67,68,77,78,87,88,107,108);
            $residence = array(9,10,19,20,29,30,49,50);
            $trapper = array(99);

            if (in_array($unit, $barracks)) {
                $queued = $technology->getTrainingList(1);
            } elseif(in_array($unit, $stables)) {
                $queued = $technology->getTrainingList(2);
            } elseif(in_array($unit, $workshop)) {
                $queued = $technology->getTrainingList(3);
            } elseif(in_array($unit, $residence)) {
                $queued = $technology->getTrainingList(4);
            } elseif(in_array($unit, $greatstables)) {
                $queued = $technology->getTrainingList(6);
            } elseif(in_array($unit, $greatbarracks)) {
                $queued = $technology->getTrainingList(5);
            } elseif(in_array($unit, $greatworkshop)) {
                $queued = $technology->getTrainingList(7);
            } elseif(in_array($unit, $trapper)) {
                $queued = $technology->getTrainingList(8);
            }
            $now = time();

    $uid = $this->getVillageField($vid, "owner");
    $artefact = count($this->getOwnUniqueArtefactInfo2($uid,5,3,0));
    $artefact1 = count($this->getOwnUniqueArtefactInfo2($vid,5,1,1));
    $artefact2 = count($this->getOwnUniqueArtefactInfo2($uid,5,2,0));
    if ($artefact > 0){
    $time = $now+round(($time-$now)/2);
    $each /= 2;
    $each = round($each);
    }elseif ($artefact1 > 0){
    $time = $now+round(($time-$now)/2);
    $each /= 2;
    $each = round($each);
    }elseif ($artefact2 > 0){
    $time = $now+round(($time-$now)/4*3);
    $each /= 4;
    $each = round($each);
    $each *= 3;
    $each = round($each);
    }
    $foolartefact = $this->getFoolArtefactInfo(5,$vid,$uid);
    if (count($foolartefact) > 0){
    foreach($foolartefact as $arte){
    if ($arte['bad_effect'] == 1){
    $each *= $arte['effect2'];
    } else {
    $each /= $arte['effect2'];
    $each = round($each);
    }
    }
    }
    if ($each == 0){ $each = 1; }
    $time2 = $now+$each;
    if (count($queued) > 0) {
    $time += $queued[count($queued) - 1]['timestamp'] - $now;
    $time2 += $queued[count($queued) - 1]['timestamp'] - $now;
    }
    // TROOPS MAKE SUM IN BARAKS , ETC
    //if($queued[count($queued) - 1]['unit'] == $unit){
    //$time = $amt*$queued[count($queued) - 1]['eachtime'];
    //$q = "UPDATE " . TB_PREFIX . "training SET amt = amt + $amt, timestamp = timestamp + $time WHERE id = ".$queued[count($queued) - 1]['id']."";
    //} else {
            $q = "INSERT INTO " . TB_PREFIX . "training values (0,$vid,$unit,$amt,$pop,$time,$each,$time2)";
    //}
        } else {
            $q = "DELETE FROM " . TB_PREFIX . "training where id = $vid";
        }
        return $this->query($q);
    }

    function updateTraining($id, $trained, $each) {
        $q = "UPDATE " . TB_PREFIX . "training set amt = amt - $trained, timestamp2 = timestamp2 + $each where id = $id";
        return $this->query($q);
    }

    function modifyUnit($vref, $array_unit, $array_amt, $array_mode){
        $i = -1;
        $units='';
        $number = count($array_unit);
        foreach($array_unit as $unit){
            if ($unit == 230){$unit = 30;}
            if ($unit == 231){$unit = 31;}
            if ($unit == 120){$unit = 20;}
            if ($unit == 121){$unit = 21;}
            if ($unit =="hero"){$unit = 'hero';}
            else{$unit = 'u' . $unit;}
        ++$i;
        //Fixed part of negativ troops (double troops) - by InCube
        $array_amt[$i] = $array_amt[$i] < 0 ? 0 : $array_amt[$i];
        //Fixed part of negativ troops (double troops) - by InCube
        $units .= $unit.' = '.$unit.' '.(($array_mode[$i] == 1)? '+':'-').'  '.$array_amt[$i].(($number > $i+1) ? ', ' : '');
        }
        $q = "UPDATE ".TB_PREFIX."units set $units WHERE vref = $vref";
        return $this->query($q);
    }

    function getEnforce($vid, $from) {
        $q = "SELECT * from " . TB_PREFIX . "enforcement where `from` = $from and vref = $vid";
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }
    
        function getOasisEnforce($ref, $mode=0) {
        if (!$mode) {
            $q = "SELECT e.*,o.conqured FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.vref=o.wref where o.conqured = $ref AND e.from !=$ref";
        } else {
            $q = "SELECT e.*,o.conqured FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.vref=o.wref where o.conqured = $ref";
        }
        $result = $this->query($q);
        return $this->fetchAll($result);
        }
        
        function getOasisEnforceArray($id, $mode=0) {
        if (!$mode) {
            $q = "SELECT e.*,o.conqured FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.vref=o.wref where e.id = $id";
        } else {
            $q = "SELECT e.*,o.conqured FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.from=o.wref where e.id =$id";
        }
        $result = $this->query($q);
        return $this->fetchAssoc($result);
        }
    
    function getEnforceControllTroops($vid) {
          $q = "SELECT * from " . TB_PREFIX . "enforcement where  vref = $vid";
          $result = $this->query($q);
          return $this->fetchAssoc($result);
     }

    function addEnforce($data) {
        $q = "INSERT into " . TB_PREFIX . "enforcement (vref,`from`) values (" . $data['to'] . "," . $data['from'] . ")";
        $this->query($q);
        $id = $this->insertId();
        $owntribe = $this->getUserField($this->getVillageField($data['from'], "owner"), "tribe", 0);
        $start = ($owntribe - 1) * 10 + 1;
        $end = ($owntribe * 10);
        //add unit
        $j = '1';
        for($i = $start; $i <= $end; $i++) {
            $this->modifyEnforce($id, $i, $data['t' . $j . ''], 1);
            $j++;
        }
        $this->modifyEnforce($id,'hero',$data['t11'],1);
        return $this->insertId();
    }

    function addEnforce2($data,$tribe,$dead1,$dead2,$dead3,$dead4,$dead5,$dead6,$dead7,$dead8,$dead9,$dead10,$dead11) {
        $q = "INSERT into " . TB_PREFIX . "enforcement (vref,`from`) values (" . $data['to'] . "," . $data['from'] . ")";
        $this->query($q);
        $id = $this->insertId();
        $owntribe = $this->getUserField($this->getVillageField($data['from'], "owner"), "tribe", 0);
        $start = ($owntribe - 1) * 10 + 1;
        $end = ($owntribe * 10);
        $start2 = ($tribe - 1) * 10 + 1;
        $start3 = ($tribe - 1) * 10;
        if ($start3 == 0){
        $start3 = "";
        }
        $end2 = ($tribe * 10);
        //add unit
        $j = '1';
        for($i = $start; $i <= $end; $i++) {
            $varName = 'dead'.$j;
            $this->modifyEnforce($id, $i, $data['t' . $j . ''], 1);
            $this->modifyEnforce($id, $i, $$varName, 0);
            $j++;
        }
        $this->modifyEnforce($id,'hero',$data['t11'],1);
        $this->modifyEnforce($id,'hero',$dead11,0);
        return $this->insertId();
    }

    function modifyEnforce($id, $unit, $amt, $mode) {
        if ($unit != 'hero') { $unit = 'u' . $unit; }
        if (!$mode) {
            $q = "UPDATE " . TB_PREFIX . "enforcement set $unit = $unit - $amt where id = $id";
        } else {
            $q = "UPDATE " . TB_PREFIX . "enforcement set $unit = $unit + $amt where id = $id";
        }
        $this->query($q);
    }

    function getEnforceArray($id, $mode) {
        if (!$mode) {
            $q = "SELECT * from " . TB_PREFIX . "enforcement where id = $id";
        } else {
            $q = "SELECT * from " . TB_PREFIX . "enforcement where `from` = $id";
        }
        $result = $this->query($q);
        return $this->fetchAssoc($result);
    }

    function getEnforceVillage($id, $mode) {
        if (!$mode) {
            $q = "SELECT * from " . TB_PREFIX . "enforcement where vref = $id";
        } else {
            $q = "SELECT * from " . TB_PREFIX . "enforcement where `from` = $id";
        }
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getVillageMovement($id) {
        $vinfo = $this->getVillage($id);
        $vtribe = $this->getUserField($vinfo['owner'], "tribe", 0);
        $movingunits = array();
        $outgoingarray = $this->getMovement(3, $id, 0);
        if (!empty($outgoingarray)) {
            foreach($outgoingarray as $out) {
                for($i = 1; $i <= 10; $i++) {
                    $movingunits['u' . (($vtribe - 1) * 10 + $i)] += $out['t' . $i];
                }
                $movingunits['hero'] += $out['t11'];
            }
        }
        $returningarray = $this->getMovement(4, $id, 1);
        if (!empty($returningarray)) {
            foreach($returningarray as $ret) {
                if ($ret['attack_type'] != 1) {
                    for($i = 1; $i <= 10; $i++) {
                        $movingunits['u' . (($vtribe - 1) * 10 + $i)] += $ret['t' . $i];
                    }
                    $movingunits['hero'] += $ret['t11'];
                }
            }
        }
        $settlerarray = $this->getMovement(5, $id, 0);
        if (!empty($settlerarray)) {
            $movingunits['u' . ($vtribe * 10)] += 3 * count($settlerarray);
        }
        return $movingunits;
    }

    ################# -START- ##################
    ##   WORLD WONDER STATISTICS FUNCTIONS!   ##
    ############################################

    /***************************
    Function to get all World Wonders
    Made by: Dzoki
    ***************************/

    function getWW() {
        $q = "SELECT * FROM " . TB_PREFIX . "fdata WHERE f99t = 40";
        $result = $this->query($q);
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }

    /***************************
    Function to get world wonder level!
    Made by: Dzoki
    ***************************/

    function getWWLevel($vref) {
        $q = "SELECT f99 FROM " . TB_PREFIX . "fdata WHERE vref = $vref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['f99'];
    }

    /***************************
    Function to get world wonder owner ID!
    Made by: Dzoki
    ***************************/

    function getWWOwnerID($vref) {
        $q = "SELECT owner FROM " . TB_PREFIX . "vdata WHERE wref = $vref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['owner'];
    }

    /***************************
    Function to get user alliance name!
    Made by: Dzoki
    ***************************/

    function getUserAllianceID($id) {
        $q = "SELECT alliance FROM " . TB_PREFIX . "users where id = $id";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['alliance'];
    }

    /***************************
    Function to get WW name
    Made by: Dzoki
    ***************************/

    function getWWName($vref) {
        $q = "SELECT wwname FROM " . TB_PREFIX . "fdata WHERE vref = $vref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['wwname'];
    }

    /***************************
    Function to change WW name
    Made by: Dzoki
    ***************************/

    function submitWWname($vref, $name) {
        $q = "UPDATE " . TB_PREFIX . "fdata SET `wwname` = '$name' WHERE " . TB_PREFIX . "fdata.`vref` = $vref";
        return $this->query($q);
    }

    //medal functions
    function addclimberpop($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set Rc = Rc + '$cp' where id = $user";
        return $this->query($q);
    }
    function addclimberrankpop($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set clp = clp + '$cp' where id = $user";
        return $this->query($q);
    }
    function removeclimberrankpop($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set clp = clp - '$cp' where id = $user";
        return $this->query($q);
    }
    function setclimberrankpop($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set clp = '$cp' where id = $user";
        return $this->query($q);
    }
    function updateoldrank($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set oldrank = '$cp' where id = $user";
        return $this->query($q);
    }
    function removeclimberpop($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "users set Rc = Rc - '$cp' where id = $user";
        return $this->query($q);
    }
    // ALLIANCE MEDAL FUNCTIONS
    function addclimberpopAlly($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "alidata set Rc = Rc + '$cp' where id = $user";
        return $this->query($q);
    }
    function addclimberrankpopAlly($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "alidata set clp = clp + '$cp' where id = $user";
        return $this->query($q);
    }
    function removeclimberrankpopAlly($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "alidata set clp = clp - '$cp'' where id = $user";
        return $this->query($q);
    }
    function updateoldrankAlly($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "alidata set oldrank = '$cp' where id = $user";
        return $this->query($q);
    }
    function removeclimberpopAlly($user, $cp) {
        $q = "UPDATE " . TB_PREFIX . "alidata set Rc = Rc - '$cp' where id = $user";
        return $this->query($q);
    }

    function getTrainingList() {
        $q = "SELECT * FROM " . TB_PREFIX . "training where vref != ''";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getNeedDelete() {
        $time = time();
        $q = "SELECT uid FROM " . TB_PREFIX . "deleting where timestamp < $time";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function countUser() {
        $q = "SELECT count(id) FROM " . TB_PREFIX . "users where id > 5";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        return $row[0];
    }

    function countAlli() {
        $q = "SELECT count(id) FROM " . TB_PREFIX . "alidata where id != 0";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        return $row[0];
    }

    /***************************
    Function to process MYSQLi->fetch_all (Only exist in MYSQL)
    References: Result
    ***************************/


    /***************************
    Function to do free query
    References: Query
    ***************************/




    //MARKET FIXES
    function getWoodAvailable($wref) {
        $q = "SELECT wood FROM " . TB_PREFIX . "vdata WHERE wref = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['wood'];
    }

    function getClayAvailable($wref) {
        $q = "SELECT clay FROM " . TB_PREFIX . "vdata WHERE wref = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['clay'];
    }

    function getIronAvailable($wref) {
        $q = "SELECT iron FROM " . TB_PREFIX . "vdata WHERE wref = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['iron'];
    }

    function getCropAvailable($wref) {
        $q = "SELECT crop FROM " . TB_PREFIX . "vdata WHERE wref = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        return $dbarray['crop'];
    }

    function Getowner($vid) {
        $s = "SELECT owner FROM " . TB_PREFIX . "vdata where wref = $vid";
        $result1 = $this->query($s);
        $row1 = $this->fetchRow($result1);
        return $row1[0];
    }

    public function debug($time, $uid, $debug_info) {
        $q = "INSERT INTO " . TB_PREFIX . "debug_info (time,uid,debug_info) VALUES ($time,$uid,$debug_info)";
        if ($this->query($q)) {
            return $this->insertId();
        } else {
            return false;
        }
    }

    function populateOasisdata()
    {
        $q2 = "SELECT * FROM " . TB_PREFIX . "wdata where oasistype != 0";
        $result2 = $this->query($q2);
        while($row = mysqli_fetch_array($result2)) {
            $wid = $row['id'];
            $basearray = $this->getOMInfo($wid);
            if ($basearray['oasistype'] < 4) {
                             $high = 1;
                         } elseif ($basearray['oasistype'] < 10){
                             $high = 2;
                          }else {
                 $high = 0;
                          }
            //We switch type of oasis and instert record with apropriate infomation.
            $q = "INSERT into " . TB_PREFIX . "odata VALUES ('" . $basearray['id'] . "'," . $basearray['oasistype'] . ",0,800,800,800,800,800,800," . time() . "," . time() . ",100,2,'Unoccupied Oasis',".$high.")";
            $this->query($q);
        }
    }

    public function getAvailableExpansionTraining()
    {
        global $building, $session, $technology, $village;
        $q = "SELECT (IF(exp1=0,1,0)+IF(exp2=0,1,0)+IF(exp3=0,1,0)) FROM " . TB_PREFIX . "vdata WHERE wref = $village->wid";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        $maxslots = $row[0];
        $residence = $building->getTypeLevel(25);
        $palace = $building->getTypeLevel(26);
        if ($residence > 0) {
            $maxslots -= (3 - floor($residence / 10));
        }
        if ($palace > 0) {
            $maxslots -= (3 - floor(($palace - 5) / 5));
        }

        $q = "SELECT (u10+u20+u30) FROM " . TB_PREFIX . "units WHERE vref = $village->wid";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        $settlers = $row[0];
        $q = "SELECT (u9+u19+u29) FROM " . TB_PREFIX . "units WHERE vref = $village->wid";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        $chiefs = $row[0];

        $settlers += 3 * count($this->getMovement(5, $village->wid, 0));
        $current_movement = $this->getMovement(3, $village->wid, 0);
        if (!empty($current_movement)) {
            foreach($current_movement as $build) {
                $settlers += $build['t10'];
                $chiefs += $build['t9'];
            }
        }
        $current_movement = $this->getMovement(3, $village->wid, 1);
        if (!empty($current_movement)) {
            foreach($current_movement as $build) {
                $settlers += $build['t10'];
                $chiefs += $build['t9'];
            }
        }
        $current_movement = $this->getMovement(4, $village->wid, 0);
        if (!empty($current_movement)) {
            foreach($current_movement as $build) {
                $settlers += $build['t10'];
                $chiefs += $build['t9'];
            }
        }
        $current_movement = $this->getMovement(4, $village->wid, 1);
        if (!empty($current_movement)) {
            foreach($current_movement as $build) {
                $settlers += $build['t10'];
                $chiefs += $build['t9'];
            }
        }
        $q = "SELECT (u10+u20+u30) FROM " . TB_PREFIX . "enforcement WHERE `from` = $village->wid";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        if (!empty($row)) {
            foreach($row as $reinf) {
                $settlers += $reinf[0];
            }
        }
        $q = "SELECT (u9+u19+u29) FROM " . TB_PREFIX . "enforcement WHERE `from` = $village->wid";
        $result = $this->query($q);
        $row = $this->fetchRow($result);
        if (!empty($row)) {
            foreach($row as $reinf) {
                $chiefs += $reinf[0];
            }
        }
        $trainlist = $technology->getTrainingList(4);
        if (!empty($trainlist)) {
            foreach($trainlist as $train) {
                if ($train['unit'] % 10 == 0) {
                    $settlers += $train['amt'];
                }
                if ($train['unit'] % 10 == 9) {
                    $chiefs += $train['amt'];
                }
            }
        }
        // trapped settlers/chiefs calculation required

        $settlerslots = $maxslots * 3 - $settlers - $chiefs * 3;
        $chiefslots = $maxslots - $chiefs - floor(($settlers + 2) / 3);

        if (!$technology->getTech(($session->tribe - 1) * 10 + 9)) {
            $chiefslots = 0;
        }
        $slots = array("chiefs" => $chiefslots, "settlers" => $settlerslots);
        return $slots;
    }

    function addArtefact($vref, $owner, $type, $size, $name, $desc, $effect, $img) {
        $q = "INSERT INTO `" . TB_PREFIX . "artefacts` (`vref`, `owner`, `type`, `size`, `conquered`, `name`, `desc`, `effect`, `img`, `active`) VALUES ('$vref', '$owner', '$type', '$size', '" . time() . "', '$name', '$desc', '$effect', '$img', '0')";
        return $this->query($q);
    }

    function getOwnArtefactInfo($vref) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE vref = $vref";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }
    
    function getOwnArtefactInfo2($vref) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE vref = $vref";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function getOwnArtefactInfo3($uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE owner = $uid";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getOwnArtefactInfoByType($vref, $type) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE vref = $vref AND type = $type order by size";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }

    function getOwnArtefactInfoByType2($vref, $type) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE vref = $vref AND type = $type";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getOwnUniqueArtefactInfo($id, $type, $size) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE owner = $id AND type = $type AND size=$size";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }

    function getOwnUniqueArtefactInfo2($id, $type, $size, $mode) {
    if (!$mode){
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE owner = $id AND active = 1 AND type = $type AND size=$size";
    } else {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE vref = $id AND active = 1 AND type = $type AND size=$size";
    }
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getFoolArtefactInfo($type,$vid,$uid) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE vref = $vid AND type = 8 AND kind = $type OR owner = $uid AND size > 1 AND active = 1 AND type = 8 AND kind = $type";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function claimArtefact($vref, $ovref, $id) {
        $time = time();
        $q = "UPDATE " . TB_PREFIX . "artefacts SET vref = $vref, owner = $id, conquered = $time, active = 1 WHERE vref = $ovref";
        return $this->query($q);
    }

    public function canClaimArtifact($from,$vref,$size,$type) {
    //fix by Ronix
    global $session, $form;
    $size1 = $size2 = $size3 = 0;
    
    $artifact = $this->getOwnArtefactInfo($from);
    if (!empty($artifact)) {
        $form->addError("error","Treasury is full. Your hero could not claim the artefact");
        return false;
    }
    $uid=$session->uid;
    $q="SELECT Count(size) AS totals,
    SUM(IF(size = '1',1,0)) small,
    SUM(IF(size = '2',1,0)) great,
    SUM(IF(size = '3',1,0)) `unique`
    FROM ".TB_PREFIX."artefacts WHERE owner = ".$uid;
    $result = $this->query($q);
    $artifact= $this->fetchAll($result);

    if ($artifact['totals'] < 3 || $type==11) {
        $DefenderFields = $this->getResourceLevel($vref);
        $defcanclaim = TRUE;
        for($i=19;$i<=38;$i++) {
            if ($DefenderFields['f'.$i.'t'] == 27) {
                $defTresuaryLevel = $DefenderFields['f'.$i];
                if ($defTresuaryLevel > 0) {
                    $defcanclaim = FALSE;
                    $form->addError("error","Treasury has not been destroyed. Your hero could not claim the artefact");
                    return false;
                } else {
                    $defcanclaim = TRUE;
                }
            }
        }
        $AttackerFields = $this->getResourceLevel($from,2);
        for($i=19;$i<=38;$i++) {
            if ($AttackerFields['f'.$i.'t'] == 27) {
                $attTresuaryLevel = $AttackerFields['f'.$i];
                if ($attTresuaryLevel >= 10) {
                    $villageartifact = TRUE;
                } else {
                    $villageartifact = FALSE;
                }
                if ($attTresuaryLevel >= 20){
                    $accountartifact = TRUE;
                } else {
                    $accountartifact = FALSE;
                }
            }
        }
        if (($artifact['great']>0 || $artifact['unique']>0) && $size>1) {
            $form->addError("error","Max num. of great/unique artefacts. Your hero could not claim the artefact");
            return FALSE;
        }
        if (($size == 1 && ($villageartifact || $accountartifact)) || (($size == 2 || $size == 3)&& $accountartifact)) {
            return true;
/*
    if ($this->getVillageField($from,"capital")==1 && $type==11) {
                $form->addError("error","Ancient Construction Plan cannot kept in capital village");
                return FALSE;
            } else {
                return TRUE;
            }
*/
        } else {
                $form->addError("error","Your level treasury is low. Your hero could not claim the artefact");
                return FALSE;
        }
    } else {
        $form->addError("error","Max num. of artefacts. Your hero could not claim the artefact");
        return FALSE;
    }
}

    function getArtefactDetails($id) {
        $q = "SELECT * FROM " . TB_PREFIX . "artefacts WHERE id = " . $id . "";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }

    function getMovementById($id){
        $q = "SELECT * FROM ".TB_PREFIX."movement WHERE moveid = ".$id."";
        $result = $this->query($q);
        $array = $this->fetchAll($result);
        
        return $array;
    }

    function getLinks($id){
        $q = 'SELECT * FROM `' . TB_PREFIX . 'links` WHERE `userid` = ' . $id . ' ORDER BY `pos` ASC';
        return $this->query($q);
    }

    function removeLinks($id,$uid){
        $q = "DELETE FROM " . TB_PREFIX . "links WHERE `id` = ".$id." and `userid` = ".$uid."";
        return $this->query($q);
    }

    function getVilFarmlist($wref){
        $q = 'SELECT * FROM ' . TB_PREFIX . 'farmlist WHERE wref = ' . $wref . ' ORDER BY wref ASC';
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);

        if ($dbarray['id']!=0) {
                return true;
            } else {
                return false;
            }

    }

    function getRaidList($id) {
        $q = "SELECT * FROM " . TB_PREFIX . "raidlist WHERE id = ".$id."";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }

    function delFarmList($id, $owner) {
        $q = "DELETE FROM " . TB_PREFIX . "farmlist where id = $id and owner = $owner";
        return $this->query($q);
    }

    function delSlotFarm($id) {
        $q = "DELETE FROM " . TB_PREFIX . "raidlist where id = $id";
        return $this->query($q);
    }

    function createFarmList($wref, $owner, $name) {
        $q = "INSERT INTO " . TB_PREFIX . "farmlist (`wref`, `owner`, `name`) VALUES ('$wref', '$owner', '$name')";
        return $this->query($q);
    }

    function addSlotFarm($lid, $towref, $x, $y, $distance, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) {
        $q = "INSERT INTO " . TB_PREFIX . "raidlist (`lid`, `towref`, `x`, `y`, `distance`, `t1`, `t2`, `t3`, `t4`, `t5`, `t6`, `t7`, `t8`, `t9`, `t10`) VALUES ('$lid', '$towref', '$x', '$y', '$distance', '$t1', '$t2', '$t3', '$t4', '$t5', '$t6', '$t7', '$t8', '$t9', '$t10')";
        return $this->query($q);
    }

    function editSlotFarm($eid, $lid, $wref, $x, $y, $dist, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10) {
        $q = "UPDATE " . TB_PREFIX . "raidlist set lid = '$lid', towref = '$wref', x = '$x', y = '$y', t1 = '$t1', t2 = '$t2', t3 = '$t3', t4 = '$t4', t5 = '$t5', t6 = '$t6', t7 = '$t7', t8 = '$t8', t9 = '$t9', t10 = '$t10' WHERE id = $eid";
        return $this->query($q);
    }

    function getArrayMemberVillage($uid){
        $q = 'SELECT a.wref, a.name, b.x, b.y from '.TB_PREFIX.'vdata AS a left join '.TB_PREFIX.'wdata AS b ON b.id = a.wref where owner = '.$uid.' order by capital DESC,pop DESC';
        $result = $this->query($q);
        $array = $this->fetchAll($result);
        return $array;
    }

    function addPassword($uid, $npw, $cpw){
        $q = "REPLACE INTO `" . TB_PREFIX . "password`(uid, npw, cpw) VALUES ($uid, '$npw', '$cpw')";
        $this->query($q);
    }

    function resetPassword($uid, $cpw){
        $q = "SELECT npw FROM `" . TB_PREFIX . "password` WHERE uid = $uid AND cpw = '$cpw' AND used = 0";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);

        if (!empty($dbarray)) {
            if (!$this->updateUserField($uid, 'password', md5($dbarray['npw']), 1)) return false;
            $q = "UPDATE `" . TB_PREFIX . "password` SET used = 1 WHERE uid = $uid AND cpw = '$cpw' AND used = 0";
            $this->query($q);
            return true;
        }

        return false;
    }

    function getCropProdstarv($wref)
    {
        global $bid4,$bid8,$bid9,$sesion,$technology;

        $basecrop = $grainmill = $bakery = 0;
        $owner = $this->getVrefField($wref, 'owner');
        $bonus = $this->getUserField($owner, 'b4', 0);

        $buildarray = $this->getResourceLevel($wref);
        $cropholder = array();
        for($i=1;$i<=38;$i++) {
            if ($buildarray['f'.$i.'t'] == 4) {
                array_push($cropholder,'f'.$i);
            }
            if ($buildarray['f'.$i.'t'] == 8) {
                $grainmill = $buildarray['f'.$i];
            }
            if ($buildarray['f'.$i.'t'] == 9) {
                $bakery = $buildarray['f'.$i];
            }
        }
        $q = "SELECT type FROM `" . TB_PREFIX . "odata` WHERE conqured = $wref";
        $oasis = $this->query_return($q);
        foreach($oasis as $oa){
            switch($oa['type']) {
                case 1:
                case 2:
                $wood += 1;
                break;
                case 3:
                $wood += 1;
                $cropo += 1;
                break;
                case 4:
                case 5:
                $clay += 1;
                break;
                case 6:
                $clay += 1;
                $cropo += 1;
                break;
                case 7:
                case 8:
                $iron += 1;
                break;
                case 9:
                $iron += 1;
                $cropo += 1;
                break;
                case 10:
                case 11:
                $cropo += 1;
                break;
                case 12:
                $cropo += 2;
                break;
            }
        }
        for($i=0;$i<=count($cropholder)-1;$i++) { $basecrop+= $bid4[$buildarray[$cropholder[$i]]]['prod']; }
        $crop = $basecrop + $basecrop * 0.25 * $cropo;
        if ($grainmill >= 1 || $bakery >= 1) {
            $crop += $basecrop /100 * ($bid8[$grainmill]['attri'] + $bid9[$bakery]['attri']);
        }
        if ($bonus > time()) {
            $crop *= 1.25;
        }
        $crop *= SPEED;
        return $crop;
    }

    //general statistics

    function addGeneralAttack($casualties) {
        $time = time();
        $q = "INSERT INTO " . TB_PREFIX . "general values (0,'$casualties','$time',1)";
        return $this->query($q);
    }

    function getAttackByDate($time) {
        $q = "SELECT * FROM " . TB_PREFIX . "general where shown = 1";
        $result = $this->query_return($q);
        $attack = 0;
        foreach($result as $general){
        if (date("j. M",$time) == date("j. M",$general['time'])){
        $attack += 1;
        }
        }
        return $attack;
    }

    function getAttackCasualties($time) {
        $q = "SELECT * FROM " . TB_PREFIX . "general where shown = 1";
        $result = $this->query_return($q);
        $casualties = 0;
        foreach($result as $general){
        if (date("j. M",$time) == date("j. M",$general['time'])){
        $casualties += $general['casualties'];
        }
        }
        return $casualties;
    }

    //end general statistics

    function addFriend($uid, $column, $friend) {
        $q = "UPDATE " . TB_PREFIX . "users SET $column = $friend WHERE id = $uid";
        return $this->query($q);
    }

    function deleteFriend($uid, $column) {
        $q = "UPDATE " . TB_PREFIX . "users SET $column = 0 WHERE id = $uid";
        return $this->query($q);
    }

    function checkFriends($uid) {
        $user = $this->getUserArray($uid, 1);
        for($i=0;$i<=19;$i++) {
        if ($user['friend'.$i] == 0 && $user['friend'.$i.'wait'] == 0){
        for($j=$i+1;$j<=19;$j++) {
        $k = $j-1;
        if ($user['friend'.$j] != 0){
        $friend = $this->getUserField($uid, "friend".$j, 0);
        $this->addFriend($uid,"friend".$k,$friend);
        $this->deleteFriend($uid,"friend".$j);
        }
        if ($user['friend'.$j.'wait'] == 0){
        $friendwait = $this->getUserField($uid, "friend".$j."wait", 0);
        $this->addFriend($sessionuid,"friend".$k."wait",$friendwait);
        $this->deleteFriend($uid,"friend".$j."wait");
        }
        }
        }
        }
    }

    function setVillageEvasion($vid)
    {
        $village = $this->getVillage($vid);
        if ($village['evasion'] == 0){
            $q = "UPDATE " . TB_PREFIX . "vdata SET evasion = 1 WHERE wref = $vid";
        } else {
            $q = "UPDATE " . TB_PREFIX . "vdata SET evasion = 0 WHERE wref = $vid";
        }
        
        return $this->query($q);
    }
    
    function addPrisoners($wid,$from,$t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9,$t10,$t11)
    {
        $q = "INSERT INTO " . TB_PREFIX . "prisoners values (0,$wid,$from,$t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9,$t10,$t11)";
        $this->query($q);
        return $this->insertId();
    }
    
    function updatePrisoners($wid,$from,$t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9,$t10,$t11)
    {
        $q = "UPDATE " . TB_PREFIX . "prisoners set t1 = t1 + $t1, t2 = t2 + $t2, t3 = t3 + $t3, t4 = t4 + $t4, t5 = t5 + $t5, t6 = t6 + $t6, t7 = t7 + $t7, t8 = t8 + $t8, t9 = t9 + $t9, t10 = t10 + $t10, t11 = t11 + $t11 where wref = $wid and ".TB_PREFIX."prisoners.from = $from";
        return $this->query($q);
    }
    
    function getPrisoners($wid,$mode=0)
    {
        if (!$mode) {
            $q = "SELECT * FROM " . TB_PREFIX . "prisoners where wref = $wid";
        }else {
            $q = "SELECT * FROM " . TB_PREFIX . "prisoners where `from` = $wid";
        }
        $result = $this->query($q);
        return $this->fetchAll($result);
    }

    function getPrisoners2($wid,$from)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "prisoners where wref = $wid and " . TB_PREFIX . "prisoners.from = $from";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function getPrisonersByID($id) {
        $q = "SELECT * FROM " . TB_PREFIX . "prisoners where id = $id";
        $result = $this->query($q);
        return $this->fetchArray($result);
    }
    
    function getPrisoners3($from) {
        $q = "SELECT * FROM " . TB_PREFIX . "prisoners where " . TB_PREFIX . "prisoners.from = $from";
        $result = $this->query($q);
        return $this->fetchAll($result);
    }
    
    function deletePrisoners($id) {
        $q = "DELETE from " . TB_PREFIX . "prisoners where id = '$id'";
        $this->query($q);
    }
    
    /**
     * Function to vacation mode - by advocaite
     * References:
     */
    function setvacmode($uid,$days)
    {
        $days1 = 60*60*24*$days;
        $time = time() + $days1;
        $q ="UPDATE ".TB_PREFIX."users SET vac_mode = '1' , vac_time=".$time." WHERE id=".$uid."";
        $this->query($q);
    }
     


    function getvacmodexy($wref)
    {
        $q = "SELECT id,oasistype,occupied FROM " . TB_PREFIX . "wdata where id = $wref";
        $result = $this->query($q);
        $dbarray = $this->fetchArray($result);
        if ($dbarray['occupied'] != 0 && $dbarray['oasistype'] == 0) {
            $q1 = "SELECT owner FROM " . TB_PREFIX . "vdata where wref = ".$dbarray['id']."";
            $result1 = $this->query($q1);
            $dbarray1 = mysqli_fetch_array($result1);
            if ($dbarray1['owner'] != 0){
                $q2 = "SELECT vac_mode,vac_time FROM " . TB_PREFIX . "users where id = ".$dbarray1['owner']."";
                $result2 = $this->query($q2);
                $dbarray2 = mysqli_fetch_array($result2);
                if ($dbarray2['vac_mode'] ==1){
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
 
    /*****************************************
    Function to vacation mode - by advocaite
    References:
    *****************************************/

    /***************************
    Function to get Hero Dead
    Made by: Shadow and brainiacX
    ***************************/

     function getHeroDead($id) {
            $q = "SELECT dead FROM " . TB_PREFIX . "hero WHERE `uid` = $id";
            $result = $this->query($q);
            $notend= $this->fetchArray($result);
             return $notend['dead'];
       }
    
    /***************************
    Function to get Hero In Revive
    Made by: Shadow
    ***************************/

     function getHeroInRevive($id) {
            $q = "SELECT inrevive FROM " . TB_PREFIX . "hero WHERE `uid` = $id";
            $result = $this->query($q);
            $notend= $this->fetchArray($result);
             return $notend['inrevive'];
       }
    
    /***************************
    Function to get Hero In Training
    Made by: Shadow
    ***************************/

     function getHeroInTraining($id) {
            $q = "SELECT intraining FROM " . TB_PREFIX . "hero WHERE `uid` = $id";
            $result = $this->query($q);
            $notend= $this->fetchArray($result);
             return $notend['intraining'];
       }

    /***************************
    Function to check Hero Not in Village
    Made by: Shadow and brainiacX
    ***************************/

    public function HeroNotInVil($id)
    {
        $heronum = 0;
        $outgoingarray = $this->getMovement(3, $id, 0);
        if (!empty($outgoingarray)) {
            foreach ($outgoingarray as $out) {
                $heronum += $out['t11'];
            }
        }
        $returningarray = $this->getMovement(4, $id, 1);
        if (!empty($returningarray)) {
            foreach ($returningarray as $ret) {
                if ($ret['attack_type'] != 1) {
                    $heronum += $ret['t11'];
                }
            }
        }
    
        return $heronum;
    }

    /***************************
    Function to Kill hero if not found
    Made by: Shadow and brainiacX
    ***************************/

       function KillMyHero($id) {
             $q = "UPDATE " . TB_PREFIX . "hero set dead = 1 where uid = ".$id;
               return $this->query($q);
       }
       
    /***************************
        Function to find Hero place
        Made by: ronix
        ***************************/
        function FindHeroInVil($wid) {
            $result = $this->query("SELECT * FROM ".TB_PREFIX."units WHERE hero>0 AND vref='".$wid."'");
            if (!empty($result)) {
                $dbarray = $this->fetchArray($result);
                if (isset($dbarray['hero'])) {
                    $this->query("UPDATE ".TB_PREFIX."units SET hero=0 WHERE vref='".$wid."'");
                    unset($dbarray);
                    return true;
                }
            }
            return false;
        }
        function FindHeroInDef($wid) {
            $delDef=true;
            $result = $this->query_return("SELECT * FROM ".TB_PREFIX."enforcement WHERE hero>0 AND `from` = ".$wid);
            if (!empty($result)) {
                $dbarray = $this->fetchArray($result);
                if (isset($dbarray['hero'])) {
                    $this->query("UPDATE ".TB_PREFIX."enforcement SET hero=0 WHERE `from` = ".$wid);
                    for ($i=0;$i<50;$i++) {
                        if ($dbarray['u'.$i]>0) {
                            $delDef=false;
                            break;
                        }
                    }
                    if ($delDef) $this->deleteReinf($wid);
                    unset($dbarray);
                    return true;
                }
            }
            return false;
        }
        function FindHeroInOasis($uid) {
            $delDef=true;
            $dbarray = $this->query_return("SELECT e.*,o.conqured,o.owner FROM ".TB_PREFIX."enforcement as e LEFT JOIN ".TB_PREFIX."odata as o ON e.vref=o.wref where o.owner=".$uid." AND e.hero>0");
            if (!empty($dbarray)) {
                foreach($dbarray as $defoasis) {
                    if ($defoasis['hero']>0) {
                        $this->query("UPDATE ".TB_PREFIX."enforcement SET hero=0 WHERE `from` = ".$defoasis['from']);
                        for ($i=0;$i<50;$i++) {
                            if ($dbarray['u'.$i]>0) {
                                $delDef=false;
                                break;
                            }
                        }
                        if ($delDef) $this->deleteReinf($defoasis['from']);
                        unset($dbarray);
                        return true;
                    }
                }
            }
            return 0;
        }
    
        function FindHeroInMovement($wid) {
            $outgoingarray = $this->getMovement(3, $wid, 0);
            if (!empty($outgoingarray)) {
                foreach($outgoingarray as $out) {
                    if ($out['t11']>0) {
                        $dbarray = $this->query("UPDATE ".TB_PREFIX."attacks SET t11=0 WHERE `id` = ".$out['ref']);
                        return true;
                        break;
                    }
                }
            }
            $returningarray = $this->getMovement(4, $wid, 1);
            if (!empty($returningarray)) {
                foreach($returningarray as $ret) {
                    if ($ret['attack_type'] != 1 && $ret['t11']>0) {
                        $dbarray = $this->query("UPDATE ".TB_PREFIX."attacks SET t11=0 WHERE `id` = ".$ret['ref']);
                        return true;
                        break;
                    }
                }
            }
            return false;
        }

    /***************************
    Function checkAttack
    Made by: Shadow
    ***************************/

       function checkAttack($wref, $toWref){
                $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.from = $wref and " . TB_PREFIX . "movement.to = $toWref and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 and (" . TB_PREFIX . "attacks.attack_type = 3 or " . TB_PREFIX . "attacks.attack_type = 4) ORDER BY endtime ASC";
        $result = $this->query($q);
        if ($this->numRows($result)) {
        return true;
        } else {
        return false;
        }
            }

    /***************************
    Function checkEnforce
    Made by: Shadow
    ***************************/

    function checkEnforce($wref, $toWref) {
            $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.from = $wref and " . TB_PREFIX . "movement.to = $toWref and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 and " . TB_PREFIX . "attacks.attack_type = 2 ORDER BY endtime ASC";
            $result = $this->query($q);
            if ($this->numRows($result)) {
            return true;
             } else {
            return false;
            }
      }

    /***************************
      Function checkScout
      Made by: yi12345
      ***************************/

    function checkScout($wref, $toWref)
    {
        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.from = $wref and " . TB_PREFIX . "movement.to = $toWref and " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = 0 and " . TB_PREFIX . "movement.sort_type = 3 and " . TB_PREFIX . "attacks.attack_type = 1 ORDER BY endtime ASC";
        $result = $this->query($q);
        
        if ($this->numRows($result)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function setMHpass(string $password)
    {
        $q = "UPDATE " . TB_PREFIX . "users SET password = '" . md5($password) . "' WHERE username = 'Multihunter'";
        $this->query($q);
    }
    
    public function getGameStatTotalPlayers(): int
    {
        $result = $this->query("SELECT count(*) as q FROM " . TB_PREFIX . "users WHERE tribe!=0 AND tribe!=4 AND tribe!=5");
        
        return $result->fetch_assoc()['q'] ?? 0;
    }
    
    public function  getGameStatPlayersByRank(int $maxRank)
    {
        $result = $this->query("SELECT count(*) as q FROM " . TB_PREFIX . "users WHERE access < {$maxRank}");
        
        return $result->fetch_assoc()['q'] ?? 0;
    }
    
    public function getGameStatActivePlayers(): int
    {
        $result = $this->query("SELECT count(*) FROM " . TB_PREFIX . "users WHERE " . time() . "- timestamp < (3600*24) AND tribe!=0 AND tribe!=4 AND tribe!=5");
        
        return $result->fetch_assoc()['q'] ?? 0;
    }
    
    public function getGameStatOnlinePlayers(): int
    {
        $result = $this->query("SELECT count(*) FROM " . TB_PREFIX . "users WHERE " . time() . "- timestamp < (60*10) AND tribe!=0 AND tribe!=4 AND tribe!=5");
        
        return $result->fetch_assoc()['q'] ?? 0;
    }
    
    public function getRankStat($maxAccess, $maxTribe = null, $tribeId = null)
    {
        $q = "SELECT
                  " . TB_PREFIX . "users.id userid,
                  " . TB_PREFIX . "users.username username,
                  " . TB_PREFIX . "users.oldrank oldrank,
                  " . TB_PREFIX . "users.alliance alliance,
                  (
                      SELECT SUM( " . TB_PREFIX . "vdata.pop )
                      FROM " . TB_PREFIX . "vdata
                      WHERE " . TB_PREFIX . "vdata.owner = userid
                  ) totalpop,
                  (
                      SELECT COUNT( " . TB_PREFIX . "vdata.wref )
                      FROM " . TB_PREFIX . "vdata
                      WHERE " . TB_PREFIX . "vdata.owner = userid AND type != 99
                  ) totalvillages,
                  (
                      SELECT " . TB_PREFIX . "alidata.tag
                      FROM " . TB_PREFIX . "alidata, " . TB_PREFIX . "users
                      WHERE " . TB_PREFIX . "alidata.id = " . TB_PREFIX . "users.alliance
                      AND " . TB_PREFIX . "users.id = userid
                  ) allitag
              FROM " . TB_PREFIX . "users
              WHERE " . TB_PREFIX . "users.access < {$maxAccess}
                AND " . TB_PREFIX . "users.tribe  " . ( $maxTribe !== null ? "<= {$maxTribe}" : "= {$tribeId}" ) . "
              ORDER BY totalpop DESC, totalvillages DESC, userid DESC";
        $result = $this->query($q);
        
        return $this->fetchAll($result);
    }
    
    public function getRankAttackStat($maxAccess)
    {
        $q = "SELECT
                  " . TB_PREFIX . "users.id userid,
                  " . TB_PREFIX . "users.username username,
                  " . TB_PREFIX . "users.apall,
                  (
                      SELECT COUNT( " . TB_PREFIX . "vdata.wref )
                      FROM " . TB_PREFIX . "vdata
                      WHERE " . TB_PREFIX . "vdata.owner = userid AND type != 99
                  ) totalvillages,
                  (
                      SELECT SUM( " . TB_PREFIX . "vdata.pop )
                      FROM " . TB_PREFIX . "vdata
                      WHERE " . TB_PREFIX . "vdata.owner = userid
                  ) pop
              FROM " . TB_PREFIX . "users
              WHERE " . TB_PREFIX . "users.apall >= 0
                AND " . TB_PREFIX . "users.access < {$maxAccess}
                AND " . TB_PREFIX . "users.tribe <= 3
              ORDER BY " . TB_PREFIX . "users.apall DESC, pop DESC, userid DESC";
        
        $result = $this->query($q);
    
        return $this->fetchAll($result);
    }
    
    public function getRankDefStat($maxAccess)
    {
        $q = "SELECT
                  " . TB_PREFIX . "users.id userid,
                  " . TB_PREFIX . "users.username username,
                  " . TB_PREFIX . "users.dpall,
                  (
                      SELECT COUNT( " . TB_PREFIX . "vdata.wref )
                      FROM " . TB_PREFIX . "vdata
                      WHERE " . TB_PREFIX . "vdata.owner = userid AND type != 99
                  ) totalvillages,
                  (
                      SELECT SUM( " . TB_PREFIX . "vdata.pop )
                      FROM " . TB_PREFIX . "vdata
                      WHERE " . TB_PREFIX . "vdata.owner = userid
                  ) pop
              FROM " . TB_PREFIX . "users
              WHERE " . TB_PREFIX . "users.dpall >= 0
                AND " . TB_PREFIX . "users.access < {$maxAccess}
                AND " . TB_PREFIX . "users.tribe <= 3
              ORDER BY " . TB_PREFIX . "users.dpall DESC, pop DESC, userid DESC";
        
        $result = $this->query($q);
    
        return $this->fetchAll($result);
    }
    
    public function getPlayersSelectedVillage(string $username)
    {
        $result = $this->query("SELECT village_select FROM ". TB_PREFIX . "users WHERE username = '{$username}'");
        
        return $this->fetchOne($result);
    }
    
    public function getFirstPlayersVillage(string $username)
    {
        $userId = $this->getUserField($username, "id", 1);
        
        $result = $this->query('SELECT * FROM `' . TB_PREFIX . 'vdata` WHERE `owner` = ' . $userId . ' LIMIT 1');
        
        return $this->fetchAssoc($result);
    }
    
    public function isHeroInReinforcement($villageId): bool
    {
        $q = "SELECT SUM(hero) from " . TB_PREFIX . "enforcement where `from` = " . $villageId;
        $result = $this->query($q);
        
        return (bool) $this->fetchOne($result);
    }
    
    public function isHeroInVillage($villageId): bool
    {
        $q = "SELECT SUM(hero) from " . TB_PREFIX . "units where `vref` = " . $villageId;
        $result = $this->query($q);
    
        return (bool) $this->fetchOne($result);
    }
    
    public function isHeroInPrison($villageId): bool
    {
        $q = "SELECT SUM(t11) from " . TB_PREFIX . "prisoners where `from` = " . $villageId;
        $result = $this->query($q);
    
        return (bool) $this->fetchOne($result);
    }
}


