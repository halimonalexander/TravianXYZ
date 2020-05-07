<?php

namespace GameEngine;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       Logging.php                                                 ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################

use GameEngine\Database\MysqliModel;

class Logging
{
    private $database;
    
    public function __construct(MysqliModel $database)
    {
        $this->database = $database;
    }
    
    public function addIllegal($uid,$ref,$type)
    {
		if (!LOG_ILLEGAL) {
		    return;
        }
		
        $log = "Attempted to ";
        switch($type) {
            case 1:
            $log .= "access village $ref";
            break;
        }
        
        $q = "Insert into ".TB_PREFIX."illegal_log values (0,$uid,'$log')";
        $this->database->query($q);
	}

	public function addLoginLog($id,$ip)
    {
		if (!LOG_LOGIN) {
		    return;
        }
		
        $q = "Insert into ".TB_PREFIX."login_log values (0,$id,'$ip')";
        $this->database->query($q);
	}

	public function addBuildLog($wid,$building,$level,$type)
    {
		if (!LOG_BUILD) {
		    return;
        }
        
        $log = $type ? "Start Construction of " : "Start Upgrade of ";
		$log .= $building." at level ".$level;
		
        $q = "Insert into ".TB_PREFIX."build_log values (0,$wid,'$log')";
        $this->database->query($q);
	}

	public function addTechLog($wid,$tech,$level)
    {
		if (!LOG_TECH) {
		    return;
        }
		
        $log = "Upgrading of tech ".$tech." to level ".$level;
		
        $q = "Insert into ".TB_PREFIX."tech_log values (0,$wid,'$log')";
        $this->database->query($q);
	}

	public function goldFinLog($wid)
    {
		if (!LOG_GOLD_FIN) {
		    return;
        }
		
        $log = "Finish construction and research with gold";
		
        $q = "Insert into ".TB_PREFIX."gold_fin_log values (0,$wid,'$log')";
        $this->database->query($q);
	}

	public function addAdminLog()
    {
	}

	public function addMarketLog($wid,$type,$data)
    {
		if (!LOG_MARKET) {
		    return;
        }
    
        $log = $type == 1 ?
            "Sent ".$data[0].",".$data[1].",".$data[2].",".$data[3]." to village ".$data[4] :
            "Traded resource between ".$wid." and ".$data[0]." market ref is ".$data[1];

        $q = "Insert into ".TB_PREFIX."market_log values (0,$wid,'$log')";
        $this->database->query($q);
	}

	public function addWarLog()
    {
	}

	public function clearLogs()
    {
	}

	public function debug($time,$uid,$debug_info)
    {
		$q = "INSERT INTO ".TB_PREFIX."debug_log (time,uid,debug_info) VALUES ($time,$uid,'$debug_info')";
		$this->database->query($q);
	}
}
