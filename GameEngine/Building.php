<?php

namespace GameEngine;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Project:       TravianZ                                                    ##
##  Version:       22.06.2015                    			       ## 
##  Filename       Building.php                                                ##
##  Developed by:  Mr.php , Advocaite , brainiacX , yi12345 , Shadow , ronix   ## 
##  Fixed by:      Shadow - STARVATION , HERO FIXED COMPL.  		       ##
##  Fixed by:      InCube - double troops				       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2015. All rights reserved.                ##
##  URLs:          http://travian.shadowss.ro                		       ##
##  Source code:   https://github.com/Shadowss/TravianZ		               ## 
##                                                                             ##
#################################################################################

use App\Helpers\GlobalVariablesHelper;
use App\Helpers\ResponseHelper;
use App\Sids\Buildings;
use App\Sids\TribeSid;
use GameEngine\Database\MysqliModel;

class Building
{
    private $database;
    private $logging;
    private $generator;
    private $session;
    private $technology;
    private $village;

	public $NewBuilding = false;
	private $maxConcurrent;
	private $allocated;
	private $basic,$inner,$plus = 0;
	public $buildArray = array();

	public function __construct(
	    MysqliModel $database,
        MyGenerator $generator,
        Logging $logging,
        Session $session,
        Technology $technology,
        Village $village
    ) {
        $this->database   = $database;
        $this->logging    = $logging;
        $this->generator  = $generator;
        $this->session    = $session;
        $this->technology = $technology;
        $this->village    = $village;

		$this->init();
	}
	
	private function init()
    {
        $this->maxConcurrent = BASIC_MAX;
        
        if (ALLOW_ALL_TRIBE || $this->session->tribe == 1) {
            $this->maxConcurrent += INNER_MAX;
        }
        
        if ($this->session->plus) {
            $this->maxConcurrent += PLUS_MAX;
        }
        
        $this->LoadBuilding();
        foreach($this->buildArray as $build) {
            if ($build['master'] == 1) {
                $this->maxConcurrent += 1;
            }
        }
    }
	
	public function canProcess($id,$tid)
    {
        if ($this->session->access == BANNED) {
            ResponseHelper::redirect("banned.php");
        }
        
        if ($this->checkResource($id,$tid) != 4) {
            if ($tid >= 19) {
                ResponseHelper::redirect("dorf2.php");
            } else {
                ResponseHelper::redirect("dorf1.php");
            }
        }
    }

    public function procBuild($get)
    {
        if (isset($get['a']) && $get['c'] == $this->session->checker && !isset($get['id'])) {
            if ($get['a'] == 0) {
                $this->removeBuilding($get['d']);
            } else {
                $this->session->changeChecker();
                $this->canProcess($this->village->resarray[ 'f' . $get['a'] . 't' ], $get['a']);
                $this->upgradeBuilding($get['a']);
            }
        }
        
        if (
            isset($get['master']) &&
            isset($get['id']) &&
            isset($get['time']) &&
            $this->session->gold >= 1 &&
            $this->session->goldclub &&
            $this->village->master == 0 &&
            (isset($get['c']) && $get['c'] == $this->session->checker)
        ) {
            $m = $get['master'];
            $master = $_GET;
            $this->session->changeChecker();
            if ($this->session->access == BANNED) {
                ResponseHelper::redirect("banned.php");
            }
            
            $level = $this->database->getResourceLevel($this->village->wid);
            $this->database->addBuilding($this->village->wid, $get['id'], $get['master'], 1, $get['time'], 1, $level[ 'f' . $get['id'] ] + 1 + count($this->database->getBuildingByField($this->village->wid, $get['id'])));
            $this->database->modifyGold($this->session->uid, 1, 0);
            if ($get['id'] > 18) {
                ResponseHelper::redirect("dorf2.php");
            } else {
                ResponseHelper::redirect("dorf1.php");
            }
        }
        
        if (isset($get['a']) && $get['c'] == $this->session->checker && isset($get['id'])) {
            if ($get['id'] > 18 && ($get['id'] < 41 || $get['id'] == 99)) {
                $this->session->changeChecker();
                $this->canProcess($get['a'], $get['id']);
                $this->constructBuilding($get['id'], $get['a']);
            }
        }
        
        if (isset($get['buildingFinish']) && $this->session->plus) {
            if ($this->session->gold >= 2 && $this->session->sit == 0) {
                $this->finishAll();
            }
        }
    }

	public function canBuild($id,$tid)
    {
		$demolition = $this->database->getDemolition($this->village->wid);
		if (!empty($demolition) && $demolition[0]['buildnumber']==$id) {
		    return 11;
		}
		if($this->isMax($tid,$id)) {
			return 1;
		} else if($this->isMax($tid,$id,1) && ($this->isLoop($id) || $this->isCurrent($id))) {
			return 10;
		} else if($this->isMax($tid,$id,2) && $this->isLoop($id) && $this->isCurrent($id)) {
			return 10;
		} else if($this->isMax($tid,$id,3) && $this->isLoop($id) && $this->isCurrent($id) && count($this->database->getMasterJobs($this->village->wid)) > 0) {
			return 10;
		}
		else {
			if($this->allocated <= $this->maxConcurrent) {
				$resRequired = $this->resourceRequired($id,$this->village->resarray['f'.$id.'t']);
				$resRequiredPop = $resRequired['pop'];
				if ($resRequiredPop == "") {
					$buildarray = $GLOBALS["bid".$tid];
					$resRequiredPop = $buildarray[1]['pop'];
				}
				$jobs = $this->database->getJobs($this->village->wid);
				if ($jobs > 0) {
					$soonPop = 0;
					foreach ($jobs as $j) {
						$buildarray = $GLOBALS["bid".$j['type']];
						$soonPop += $buildarray[$this->database->getFieldLevel($this->village->wid,$j['field'])+1]['pop'];
					}
				}
				if(($this->village->allcrop - $this->village->pop - $soonPop - $resRequiredPop) <= 1 && $this->village->resarray['f'.$id.'t'] <> 4) {
					return 4;
				}
				else {
					switch($this->checkResource($tid,$id)) {
						case 1:
						return 5;
						break;
						case 2:
						return 6;
						break;
						case 3:
						return 7;
						break;
						case 4:
						if($id >= 19) {
							if ($this->session->tribe == 1 || ALLOW_ALL_TRIBE) {
								if($this->inner == 0) {
									return 8;
								}
								else {
									if ($this->session->plus or $tid==40) {
										if($this->plus == 0) {
											return 9;
										}
										else {
											return 3;
										}
									}
									else {
										return 2;
									}
								}
							}
							else {
								if($this->basic == 0) {
									return 8;
								}
								else {
									if ($this->session->plus or $tid==40) {
										if($this->plus == 0) {
											return 9;
										}
										else {
											return 3;
										}
									}
									else {
										return 2;
									}
								}
							}
						}
						else {
							if($this->basic == 1) {
								if(($this->session->plus or $tid==40) && $this->plus == 0) {
									return 9;
								}
								else {
									return 3;
								}
							}
							else {
								return 8;
							}
						}
						break;
					}
				}
			}
			else {
				return 2;
			}
		}
	}

	public function walling()
    {
		$wall = array(31,32,33);
		foreach($this->buildArray as $job) {
			if(in_array($job['type'],$wall)) {
				return "3".$this->session->tribe;
			}
		}
		return false;
	}

	public function rallying()
    {
		foreach($this->buildArray as $job) {
			if($job['type'] == 16) {
				return true;
			}
		}
		return false;
	}

	public function procResType($ref)
    {
		switch($ref) {
			case 1: $build = "Woodcutter"; break;
			case 2: $build = "Clay Pit"; break;
			case 3: $build = "Iron Mine"; break;
			case 4: $build = "Cropland"; break;
			case 5: $build = "Sawmill"; break;
			case 6: $build = "Brickyard"; break;
			case 7: $build = "Iron Foundry"; break;
			case 8: $build = "Grain Mill"; break;
			case 9: $build = "Bakery"; break;
			case 10: $build = "Warehouse"; break;
			case 11: $build = "Granary"; break;
			case 12: $build = "Blacksmith"; break;
			case 13: $build = "Armoury"; break;
			case 14: $build = "Tournament Square"; break;
			case 15: $build = "Main Building"; break;
			case 16: $build = "Rally Point"; break;
			case 17: $build = "Marketplace"; break;
			case 18: $build = "Embassy"; break;
			case 19: $build = "Barracks"; break;
			case 20: $build = "Stable"; break;
			case 21: $build = "Workshop"; break;
			case 22: $build = "Academy"; break;
			case 23: $build = "Cranny"; break;
			case 24: $build = "Town Hall"; break;
			case 25: $build = "Residence"; break;
			case 26: $build = "Palace"; break;
			case 27: $build = "Treasury"; break;
			case 28: $build = "Trade Office"; break;
			case 29: $build = "Great Barracks"; break;
			case 30: $build = "Great Stable"; break;
			case 31: $build = "City Wall"; break;
			case 32: $build = "Earth Wall"; break;
			case 33: $build = "Palisade"; break;
			case 34: $build = "Stonemason's Lodge"; break;
			case 35: $build = "Brewery"; break;
			case 36: $build = "Trapper"; break;
			case 37: $build = "Hero's Mansion"; break;
			case 38: $build = "Great Warehouse"; break;
			case 39: $build = "Great Granary"; break;
			case 40: $build = "Wonder of the World"; break;
			case 41: $build = "Horse Drinking Trough"; break;
			case 42: $build = "Great Workshop"; break;
			default: $build = "Error"; break;
		}
		return $build;
	}

	private function loadBuilding() {
		$this->buildArray = $this->database->getJobs($this->village->wid);
		$this->allocated = count($this->buildArray);
		if($this->allocated > 0) {
			foreach($this->buildArray as $build) {
				if($build['loopcon'] == 1) {
					$this->plus = 1;
				}
				else {
					if($build['field'] <= 18) {
						$this->basic += 1;
					}
					else {
						if($this->session->tribe == 1 || ALLOW_ALL_TRIBE) {
							$this->inner += 1;
						}
						else {
							$this->basic += 1;
						}
					}
				}
			}
			$this->NewBuilding = true;
		}
	}

	private function removeBuilding($d)
    {
		foreach($this->buildArray as $jobs) {
			if ($jobs['id'] == $d) {
				$uprequire = $this->resourceRequired($jobs['field'],$jobs['type']);
				
				if ($this->database->removeBuilding($d)) {
				    if ($jobs['master'] == 0){
					    $this->database->modifyResource($this->village->wid, $uprequire['wood'], $uprequire['clay'], $uprequire['iron'], $uprequire['crop'], 1);
					}
				
                    if ($jobs['field'] >= 19) {
                        ResponseHelper::redirect("dorf2.php");
                    } else {
                        ResponseHelper::redirect("dorf1.php");
                    }
				}
			}
		}
	}

	private function upgradeBuilding($id) {
		global $database,$village,$session,$logging;
		if($this->allocated < $this->maxConcurrent) {
			$uprequire = $this->resourceRequired($id,$village->resarray['f'.$id.'t']);
			$time = time() + $uprequire['time'];
			$bindicate = $this->canBuild($id,$village->resarray['f'.$id.'t']);
			$loop = ($bindicate == 9 ? 1 : 0);
			$loopsame = 0;
			if($loop == 1) {
				foreach($this->buildArray as $build) {
					if($build['field']==$id) {
						$loopsame += 1;
						$uprequire = $this->resourceRequired($id,$village->resarray['f'.$id.'t'],($loopsame>0?2:1));
					}
				}
				if($session->tribe == 1 || ALLOW_ALL_TRIBE) {
					if($id >= 19) {
						foreach($this->buildArray as $build) {
							if($build['field'] >= 19) {
								$time = $build['timestamp'] + $uprequire['time'];
							}
						}
					}
					else {
						foreach($this->buildArray as $build) {
							if($build['field'] <= 18) {
								$time = $build['timestamp'] + $uprequire['time'];
							}
						}
					}
				}
				else {
					$time = $this->buildArray[0]['timestamp'] + $uprequire['time'];
				}
			}
			$level = $database->getResourceLevel($village->wid);
			if($session->access!=BANNED){
                if($database->addBuilding(
                    $village->wid,
                    $id,
                    $village->resarray['f'.$id.'t'],
                    $loop,
                    $time+($loop==1?ceil(60/SPEED):0),
                    0,
                    $level['f'.$id] + 1 + count($database->getBuildingByField($village->wid,$id))
                )) {
                    $database->modifyResource($village->wid,$uprequire['wood'],$uprequire['clay'],$uprequire['iron'],$uprequire['crop'],0);
                    $logging->addBuildLog($village->wid,$this->procResType($village->resarray['f'.$id.'t']),($village->resarray['f'.$id]+($loopsame>0?2:1)),0);
                    if($id >= 19) {
                        header("Location: dorf2.php");
                    }
                    else {
                        header("Location: dorf1.php");
                    }
                }
			}else{
			header("Location: banned.php");
			}
		}
	}

    private function downgradeBuilding($id)
    {
		if ($this->allocated > $this->maxConcurrent) {
            ResponseHelper::redirect("banned.php");
        }
		
        $dataarray = GlobalVariablesHelper::getBuilding($this->village->resarray['f'.$id.'t']);
        $time = time() + round($dataarray[$this->village->resarray['f'.$id]-1]['time'] / 4);
        $loop = 0;
        
        if ($this->inner == 1 || $this->basic == 1) {
            if (($this->session->plus || $this->village->resarray['f'.$id.'t']==40) && $this->plus == 0) {
                $loop = 1;
            }
        }
			
        if ($loop == 1) {
            if ($this->session->tribe == 1 || ALLOW_ALL_TRIBE) {
                if ($id >= 19) {
                    foreach($this->buildArray as $build) {
                        if($build['field'] >= 19) {
                            $time = $build['timestamp'] + round($dataarray[$this->village->resarray['f'.$id]-1]['time'] / 4);
                        }
                    }
                }
            } else {
                $time = $this->buildArray[0]['timestamp'] + round($dataarray[$this->village->resarray['f'.$id]-1]['time'] / 4);
            }
        }
			
        if ($this->session->access == BANNED){
            ResponseHelper::redirect("banned.php");
        }
			
        $level = $this->database->getResourceLevel($this->village->wid);
			
        if ($this->database->addBuilding(
            $this->village->wid,
            $id,
            $this->village->resarray['f'.$id.'t'],
            $loop,
            $time,
            0,
            0,
            $level['f'.$id] + 1 + count($this->database->getBuildingByField($this->village->wid,$id))
        )) {
            $this->logging->addBuildLog(
                $this->village->wid,
                $this->procResType($this->village->resarray['f'.$id.'t']),
                $this->village->resarray['f'.$id] - 1,
                2
            );
            
            ResponseHelper::redirect("dorf2.php");
        }
    }
    
	private function constructBuilding($id,$tid)
    {
		if ($this->allocated > $this->maxConcurrent) {
		    return;
        }
		
        if ($tid == 16) {
            $id = 39;
        } else if($tid == 31 || $tid == 32 || $tid == 33) {
            $id = 40;
        }
        
        $uprequire = $this->resourceRequired($id, $tid);
        $time = time() + $uprequire['time'];
        $bindicate = $this->canBuild($id, $this->village->resarray['f'.$id.'t']);
        $loop = $bindicate == 9 ? 1 : 0;
        
        if ($loop == 1) {
            foreach($this->buildArray as $build) {
                if($build['field'] >= 19 || ($this->session->tribe <> 1 && !ALLOW_ALL_TRIBE)) {
                    $time = $build['timestamp'] + ceil(60/SPEED) + $uprequire['time'];
                }
            }
        }
        
        if ($this->meetRequirement($tid)) {
			if ($this->session->access == BANNED) {
                ResponseHelper::redirect("banned.php");
            }
			
			$level = $this->database->getResourceLevel($this->village->wid);
            if ($this->database->addBuilding(
                $this->village->wid,$id,$tid,$loop,$time,0,$level['f'.$id] + 1 + count($this->database->getBuildingByField($this->village->wid,$id))
            )) {
                $this->logging->addBuildLog($this->village->wid,$this->procResType($tid),($this->village->resarray['f'.$id]+1),1);
                $this->database->modifyResource($this->village->wid,$uprequire['wood'],$uprequire['clay'],$uprequire['iron'],$uprequire['crop'],0);
                
                ResponseHelper::redirect("dorf2.php");
            }
        }
	}

	private function meetRequirement($id)
    {
		switch($id) {
			case 1:
			case 2:
			case 3:
			case 4:
			case 11:
			case 15:
			case 16:
			case 18:
			case 23:
			case 31:
			case 32:
			case 33:
                return true;
                break;
			case 10:
			case 20:
                return ($this->getTypeLevel(15) >= 1)? true : false;
                break;
			case 5:
                if ($this->getTypeLevel(1) >= 10 && $this->getTypeLevel(15) >= 5) { return true; } else { return false; }
                break;
			case 6:
                if ($this->getTypeLevel(2) >= 10 && $this->getTypeLevel(15) >= 5) { return true; } else { return false; }
                break;
			case 7:
                if ($this->getTypeLevel(3) >= 10 && $this->getTypeLevel(15) >= 5) { return true; } else { return false; }
                break;
			case 8:
                if ($this->getTypeLevel(4) >= 5) { return true; } else { return false; }
                break;
			case 9:
                if ($this->getTypeLevel(15) >= 5 && $this->getTypeLevel(4) >= 10 && $this->getTypeLevel(8) >= 5) { return true; } else { return false; }
                break;
			case 12:
                if ($this->getTypeLevel(22) >= 3 && $this->getTypeLevel(15) >= 3) { return true; } else { return false; }
                break;
			case 13:
                if ($this->getTypeLevel(15) >= 3 && $this->getTypeLevel(22) >= 1) { return true; } else { return false; }
                break;
			case 14:
                if ($this->getTypeLevel(16) >= 15) { return true; } else { return false; }
                break;
			case 17:
                if ($this->getTypeLevel(15) >= 3 && $this->getTypeLevel(10) >= 1 && $this->getTypeLevel(11) >= 1) { return true; } else { return false; }
                break;
			case 19:
                if ($this->getTypeLevel(15) >= 3 && $this->getTypeLevel(16) >= 1) { return true; } else { return false; }
                break;
			case 20:
                if ($this->getTypeLevel(12) >= 3 && $this->getTypeLevel(22) >= 5) { return true; } else { return false; }
                break;
			case 21:
                if ($this->getTypeLevel(22) >= 10 && $this->getTypeLevel(15) >= 5) { return true; } else { return false; }
                break;
			case 22:
                if ($this->getTypeLevel(15) >= 3 && $this->getTypeLevel(16) >= 1) { return true; } else { return false; }
                break;
			case 24:
                if ($this->getTypeLevel(22) >= 10 && $this->getTypeLevel(15) >= 10) { return true; } else { return false; }
                break;
			case 25:
                if ($this->getTypeLevel(15) >= 5 && $this->getTypeLevel(26) == 0) { return true; } else { return false; }
                break;
			case 26:
                if ($this->getTypeLevel(18) >= 1 && $this->getTypeLevel(15) >= 5 && $this->getTypeLevel(25) == 0) { return true; } else { return false; }
                break;
			case 27:
                if ($this->getTypeLevel(15) >= 10) { return true; } else { return false; }
                break;
			case 28:
                if ($this->getTypeLevel(17) == 20 && $this->getTypeLevel(20) >= 10) { return true; } else { return false; }
                break;
			case 29:
                if ($this->getTypeLevel(19) == 20 && $this->village->capital == 0) { return true; } else { return false; }
                break;
			case 30:
                if ($this->getTypeLevel(20) == 20 && $this->village->capital == 0) { return true; } else { return false; }
                break;
			case 34:
                if ($this->getTypeLevel(26) >= 3 && $this->getTypeLevel(15) >= 5 && $this->getTypeLevel(25) == 0) { return true; } else { return false; }
                break;
			case 35:
                if ($this->getTypeLevel(16) >= 10 && $this->getTypeLevel(11) == 20) { return true; } else { return false; }
                break;
			case 36:
                if ($this->getTypeLevel(16) >= 1) { return true; } else { return false; }
                break;
			case 37:
                if ($this->getTypeLevel(15) >= 3 && $this->getTypeLevel(16) >= 1) { return true; } else { return false; }
                break;
			case 38:
                if ($this->getTypeLevel(15) >= 10) { return true; } else { return false; }
                break;
            case 39:
                if ($this->getTypeLevel(15) >= 10) { return true; } else { return false; }
                break;
			case 40:
                $wwlevel = $this->village->resarray['f99'];
                
                if ($wwlevel > 50) {
                    $needed_plan = 1;
                } else {
                    $needed_plan = 0;
                }
                
                $wwbuildingplan = 0;
                $villages = $this->database->getVillagesID($this->session->uid);
                
                foreach ($villages as $village1) {
                    $plan = count($this->database->getOwnArtefactInfoByType2($village1, 11));
                    
                    if ($plan > 0) {
                        $wwbuildingplan = 1;
                    }
                }
                
                if ($this->session->alliance != 0) {
                    $alli_users = $this->database->getUserByAlliance($this->session->alliance);
                    foreach ($alli_users as $users) {
                        $villages = $this->database->getVillagesID($users['id']);
                        if ($users['id'] != $this->session->uid) {
                            foreach ($villages as $village1) {
                                $plan = count($this->database->getOwnArtefactInfoByType2($village1, 11));
                                if ($plan > 0) {
                                    $wwbuildingplan += 1;
                                }
                            }
                        }
                    }
                }
                
                if ($this->village->natar == 1 && $wwbuildingplan > $needed_plan) { return true; } else { return false; }
                
                break;
			case 41:
                if($this->getTypeLevel(16) >= 10 && $this->getTypeLevel(20) == 20) { return true; } else { return false; }
                break;
			case 42:
                if($this->getTypeLevel(21) == 20 && $this->village->capital == 0) { return true; } else { return false; }
                break;
		}
	}

	private function checkResource($tid,$id): int
    {
		$plus = 1;
		
		foreach ($this->buildArray as $job) {
			if($job['type'] == $tid && $job['field'] == $id) {
				$plus = 2;
			}
		}
		
        $dataarray = GlobalVariablesHelper::getBuilding($tid);
		
		$wood = $dataarray[$this->village->resarray['f'.$id]+$plus]['wood'];
		$clay = $dataarray[$this->village->resarray['f'.$id]+$plus]['clay'];
		$iron = $dataarray[$this->village->resarray['f'.$id]+$plus]['iron'];
		$crop = $dataarray[$this->village->resarray['f'.$id]+$plus]['crop'];
		
		if ($wood > $this->village->maxstore || $clay > $this->village->maxstore || $iron > $this->village->maxstore) {
			return 1;
		}

        if ($crop > $this->village->maxcrop) {
            return 2;
        }

        if (
            $wood > $this->village->awood ||
            $clay > $this->village->aclay ||
            $iron > $this->village->airon ||
            $crop > $this->village->acrop
        ) {
            return 3;
        }

        if (
            $this->village->awood >= $wood &&
            $this->village->aclay >= $clay &&
            $this->village->airon >= $iron &&
            $this->village->acrop >= $crop
        ){
            return 4;
        }

        return 3;
	}

	public function isMax($id, $field, $loop=0): bool
    {
		$dataarray = GlobalVariablesHelper::getBuilding($id);
		if ($id > 4) {
            return $this->village->resarray['f'.$field] == count($dataarray) - $loop;
        }
		
        if ($this->village->capital == 1) {
            return ($this->village->resarray['f'.$field] == (count($dataarray) - 1 - $loop));
        }
        
        return ($this->village->resarray['f'.$field] == (count($dataarray) - 11 - $loop));
	}

	public function getTypeLevel($tid, $vid=0)
    {
        $keyholder = [];
        
        if ($vid == 0) {
			$resourcearray = $this->village->resarray;
		} else {
			$resourcearray = $this->database->getResourceLevel($vid);
		}
  
		foreach (array_keys($resourcearray,$tid) as $key) {
			if (strpos($key,'t')) {
				$key = preg_replace("/[^0-9]/", '', $key);
				array_push($keyholder, $key);
			}
		}
		
		$element = count($keyholder);
		
		if ($element >= 2) {
			if ($tid <= 4) {
				$temparray = [];
                for ($i=0;$i<=$element-1;$i++) {
					array_push($temparray,$resourcearray['f'.$keyholder[$i]]);
				}
				foreach ($temparray as $key => $val) {
					if ($val == max($temparray))
					$target = $key;
				}
			}
			else {
				$target = 0;
				for ($i=1;$i<=$element-1;$i++) {
					if ($resourcearray['f'.$keyholder[$i]] > $resourcearray['f'.$keyholder[$target]]) {
						$target = $i;
					}
				}
			}
		}
		elseif ($element == 1) {
			$target = 0;
		}
		else {
			return 0;
		}
		
		if ($keyholder[$target] != "") {
			return $resourcearray['f'.$keyholder[$target]];
		}
		else {
			return 0;
		}
	}
	
	public function isCurrent($id)
    {
		foreach ($this->buildArray as $build) {
			if ($build['field'] == $id && $build['loopcon'] <> 1) {
				return true;
			}
		}
		
		return false;
	}

	public function isLoop($id=0)
    {
		foreach ($this->buildArray as $build) {
			if (($build['field'] == $id && $build['loopcon']) || ($build['loopcon'] == 1 && $id == 0)) {
				return true;
			}
		}
		
		return false;
	}

    public function finishAll()
    {
        $bid18 = GlobalVariablesHelper::getBuilding(Buildings::EMBASSY);
        
        if ($this->session->access == BANNED){
            ResponseHelper::redirect("banned.php");
        }
        
        $finish = 0;
        
        foreach ($this->buildArray as $jobs) {
            if ($jobs['wid'] == $this->village->wid) {
                $finish = 2;
                $wwvillage = $this->database->getResourceLevel($jobs['wid']);
                
                if ($wwvillage['f99t'] != 40) {
                    $level = $jobs['level'];
                    
                    if ($jobs['type'] != 25 AND $jobs['type'] != 26 AND $jobs['type'] != 40) {
                        $finish = 1;
                        $resource = $this->resourceRequired($jobs['field'], $jobs['type']);
    
                        if ($jobs['master'] == 0) {
                            $q = "UPDATE ".TB_PREFIX."fdata
                                SET f".$jobs['field']." = ".$jobs['level'].",
                                    f".$jobs['field']."t = ".$jobs['type']."
                                WHERE vref = ".$jobs['wid'];
                        } else {
                            $villwood = $this->database->getVillageField($jobs['wid'],'wood');
                            $villclay = $this->database->getVillageField($jobs['wid'],'clay');
                            $villiron = $this->database->getVillageField($jobs['wid'],'iron');
                            $villcrop = $this->database->getVillageField($jobs['wid'],'crop');
                            
                            $type = $jobs['type'];
                            $buildarray = $GLOBALS["bid".$type];
                            
                            $buildwood = $buildarray[$level]['wood'];
                            $buildclay = $buildarray[$level]['clay'];
                            $buildiron = $buildarray[$level]['iron'];
                            $buildcrop = $buildarray[$level]['crop'];
                            
                            if (
                                $buildwood < $villwood &&
                                $buildclay < $villclay &&
                                $buildiron < $villiron &&
                                $buildcrop < $villcrop
                            ) {
                                $newgold = $this->session->gold - 1;
                                $this->database->updateUserField($this->session->uid, "gold", $newgold, 1);
                                $enought_res = 1;
                                
                                $q = "UPDATE ".TB_PREFIX."fdata
                                    SET f".$jobs['field']." = ".$jobs['level'].",
                                        f".$jobs['field']."t = ".$jobs['type']."
                                    WHERE vref = ".$jobs['wid'];
                            }
                        }
                        
                        if ($this->database->query($q) && ($enought_res == 1 or $jobs['master'] == 0)) {
                            $this->database->modifyPop($jobs['wid'],$resource['pop'],0);
                            $this->database->addCP($jobs['wid'],$resource['cp']);
                            $q = "DELETE FROM ".TB_PREFIX."bdata where id = ".$jobs['id'];
                            $this->database->query($q);
                            
                            if($jobs['type'] == 18) {
                                $owner = $this->database->getVillageField($jobs['wid'],"owner");
                                $max = $bid18[$level]['attri'];
                                $q = "UPDATE ".TB_PREFIX."alidata set max = $max where leader = $owner";
                                $this->database->query($q);
                            }
                        }
                        
                        if (
                            ($jobs['field'] >= 19 && ($this->session->tribe == TribeSid::ROMANS || ALLOW_ALL_TRIBE)) ||
                            (!ALLOW_ALL_TRIBE && $this->session->tribe != TribeSid::ROMANS)
                        ) {
                            $innertimestamp = $jobs['timestamp'];
                        }
                    }
                }
            }
        }
        
        if ($finish != 2) {
            $demolition = $this->database->finishDemolition($this->village->wid);
            $tech = $this->technology->finishTech();
            
            if ($finish == 1 || $demolition > 0 || $tech > 0) {
                $this->logging->goldFinLog($this->village->wid);
                $this->database->modifyGold($this->session->uid,2,0);
            }
            
            $stillbuildingarray = $this->database->getJobs($this->village->wid);
            
            if (count($stillbuildingarray) == 1) {
                if ($stillbuildingarray[0]['loopcon'] == 1) {
                    //$q = "UPDATE ".TB_PREFIX."bdata SET loopcon=0,timestamp=".(time()+$stillbuildingarray[0]['timestamp']-$innertimestamp)." WHERE id=".$stillbuildingarray[0]['id'];
                    $q = "UPDATE ".TB_PREFIX."bdata SET loopcon=0 WHERE id=".$stillbuildingarray[0]['id'];
                    $this->database->query($q);
                }
            }
        }
        
        ResponseHelper::redirect($this->session->referrer);
    }  

	public function resourceRequired($id, $tid, $plus=1)
    {
		$dataarray = GlobalVariablesHelper::getBuilding($tid);
		$bid15 = GlobalVariablesHelper::getBuilding(Buildings::MAIN_BUILDING);
		
		$wood = $dataarray[$this->village->resarray['f'.$id] + $plus]['wood'];
		$clay = $dataarray[$this->village->resarray['f'.$id] + $plus]['clay'];
		$iron = $dataarray[$this->village->resarray['f'.$id] + $plus]['iron'];
		$crop = $dataarray[$this->village->resarray['f'.$id] + $plus]['crop'];
		$pop  = $dataarray[$this->village->resarray['f'.$id] + $plus]['pop'];
        $cp   = $dataarray[$this->village->resarray['f'.$id] + $plus]['cp'];
		
		if ($tid == Buildings::MAIN_BUILDING) {
            $time =  $this->getTypeLevel(Buildings::MAIN_BUILDING) == 0 ?
				round($dataarray[$this->village->resarray['f'.$id] + $plus]['time'] / SPEED * 5) :
				round($dataarray[$this->village->resarray['f'.$id] + $plus]['time'] / SPEED);
		} else {
            $time =  $this->getTypeLevel(Buildings::MAIN_BUILDING) == 0 ?
                round($dataarray[$this->village->resarray['f'.$id] + $plus]['time'] * 5 / SPEED) :
				round(
				    $dataarray[$this->village->resarray['f'.$id] + $plus]['time'] *
                    ($bid15[$this->getTypeLevel(Buildings::MAIN_BUILDING)]['attri'] / 100) /
                    SPEED
                );
		}
		
		return [
		    "wood" => $wood,
            "clay" => $clay,
            "iron" => $iron,
            "crop" => $crop,
            "pop" => $pop,
            "time" => $time,
            "cp" => $cp,
        ];
    }
    
    public function getTypeField($type)
    {
		for ($i = 19; $i <= 40; $i++) {
			if ($this->village->resarray['f'.$i.'t'] == $type) {
				return $i;
			}
		}
	}

	public function calculateAvaliable($id, $tid, $plus=1)
    {
		$uprequire = $this->resourceRequired($id, $tid, $plus);
		
		$rwood = $uprequire['wood'] - $this->village->awood;
		$rclay = $uprequire['clay'] - $this->village->aclay;
		$rcrop = $uprequire['crop'] - $this->village->acrop;
		$riron = $uprequire['iron'] - $this->village->airon;
		
		$rwtime  = $rwood / $this->village->getProd("wood") * 3600;
		$rcltime = $rclay / $this->village->getProd("clay") * 3600;
		$rctime  = $rcrop / $this->village->getProd("crop") * 3600;
		$ritime  = $riron / $this->village->getProd("iron") * 3600;
		
		$reqtime = max($rwtime, $rctime, $rcltime, $ritime);
		$reqtime += time();
		
		return $this->generator->procMtime($reqtime);
	}
}
