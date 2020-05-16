<?php

namespace GameEngine;

################################################################################# 
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ## 
## --------------------------------------------------------------------------- ## 
##  Filename       Market.php                                                  ## 
##  Developed by:  Dzoki                                                       ## 
##  Some fixes:    aggenkeech                                                  ## 
##  License:       TravianX Project                                            ## 
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ## 
##                                                                             ## 
################################################################################# 

use App\Helpers\GlobalVariablesHelper;
use App\Helpers\ResponseHelper;
use App\Sids\Buildings;
use App\Sids\MovementTypeSid;
use GameEngine\Database\MysqliModel;

class Market
{
    private $building;
    private $database;
    private $generator;
    private $logging;
    private $multiSort;
    private $village;
    private $session;
    
    public $onsale,$onmarket,$sending,$recieving,$return = array(); 
    public $maxcarry,$merchant,$used;
    
    public function __construct(
        Building $building,
        MysqliModel $database,
        MyGenerator $generator,
        Logging $logging,
        Multisort $multiSort,
        Session $session,
        Village $village
    ) {
        $this->building = $building;
        $this->database = $database;
        $this->generator = $generator;
        $this->logging = $logging;
        $this->multiSort = $multiSort;
        $this->village = $village;
        $this->session = $session;
    }
    
    public function procMarket($post) 
    { 
        $this->loadMarket(); 
        if(isset($_SESSION['loadMarket'])) 
        { 
            $this->loadOnsale(); 
            unset($_SESSION['loadMarket']); 
        } 
        if(isset($post['ft'])) 
        { 
            switch($post['ft']) 
            { 
                case "mk1": $this->sendResource($post); break; 
                case "mk2": $this->addOffer($post); break; 
                case "mk3": $this->tradeResource($post); break; 
            } 
        } 
    } 

    public function procRemove($get) 
    { 
        if(isset($get['t']) && $get['t'] == 1)
        { 
            $this->filterNeed($get); 
        } 
        else if(isset($get['t']) && $get['t'] ==2 && isset($get['a']) && $get['a'] == 5 && isset($get['del'])) 
        { 
            //GET ALL FIELDS FROM MARKET 
            $type = $this->database->getMarketField($this->village->wid,"gtype");
            $amt = $this->database->getMarketField($this->village->wid,"gamt");
            $vref = $this->village->wid;
            $this->database->getResourcesBack($vref,$type,$amt);
            $this->database->addMarket($this->village->wid,$get['del'],0,0,0,0,0,0,1);
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$get['id']."&t=2");
        } 
        if(isset($get['t']) && $get['t'] == 1 && isset($get['a']) && $get['a'] == $this->session->mchecker && !isset($get['del']))
        { 
            $this->session->changeChecker();
            $this->acceptOffer($get); 
        } 
    } 

    public function merchantAvail() 
    { 
        return $this->merchant - $this->used; 
    } 

    private function loadMarket() 
    { 
        $bid28 = GlobalVariablesHelper::getBuilding(Buildings::TRADE_OFFICE);
        $bid17 = GlobalVariablesHelper::getBuilding(Buildings::MARKETPLACE);
         
        $this->recieving = $this->database->getMovement(0,$this->village->wid,1);
        $this->sending = $this->database->getMovement(0,$this->village->wid,0);
        $this->return  = $this->database->getMovement(2,$this->village->wid,1);
        $this->merchant = ($this->building->getTypeLevel(17) > 0)? $bid17[$this->building->getTypeLevel(17)]['attri'] : 0;
        $this->used = $this->database->totalMerchantUsed($this->village->wid);
        $this->onmarket = $this->database->getMarket($this->village->wid,0);
        $this->maxcarry = ($this->session->tribe == 1)? 500 : (($this->session->tribe == 2)? 1000 : 750);
        $this->maxcarry *= TRADER_CAPACITY;
        
        if ($this->building->getTypeLevel(Buildings::TRADE_OFFICE) != 0)
        { 
            $this->maxcarry *= $bid28[$this->building->getTypeLevel(Buildings::TRADE_OFFICE)]['attri'] / 100;
        } 
    } 

    private function sendResource($post) 
    {
        $wtrans = (isset($post['r1']) && $post['r1'] != "")? $post['r1'] : 0; 
        $ctrans = (isset($post['r2']) && $post['r2'] != "")? $post['r2'] : 0; 
        $itrans = (isset($post['r3']) && $post['r3'] != "")? $post['r3'] : 0; 
        $crtrans = (isset($post['r4']) && $post['r4'] != "")? $post['r4'] : 0;
        
        $wtrans = str_replace("-", "", $wtrans); 
        $ctrans = str_replace("-", "", $ctrans); 
        $itrans = str_replace("-", "", $itrans); 
        $crtrans = str_replace("-", "", $crtrans);
        
        $availableWood = $this->database->getWoodAvailable($this->village->wid);
        $availableClay = $this->database->getClayAvailable($this->village->wid);
        $availableIron = $this->database->getIronAvailable($this->village->wid);
        $availableCrop = $this->database->getCropAvailable($this->village->wid);
        
        if ($this->session->access == BANNED)
        { 
            ResponseHelper::redirect("banned.php");
        } 
        else if($availableWood >= $post['r1'] AND $availableClay >= $post['r2'] AND $availableIron >= $post['r3'] AND $availableCrop >= $post['r4']) 
        { 
            $resource = array($wtrans,$ctrans,$itrans,$crtrans); 
            $reqMerc = ceil((array_sum($resource)-0.1)/$this->maxcarry); 

            if($this->merchantAvail() != 0 && $reqMerc <= $this->merchantAvail()) 
            { 
                $id = $post['getwref']; 
                $coor = $this->database->getCoor($id);
                if($this->database->getVillageState($id))
                { 
                    $timetaken = $this->generator->procDistanceTime($coor,$this->village->coor,$this->session->tribe,0);
                    $res = $resource[0]+$resource[1]+$resource[2]+$resource[3]; 
                    if($res!=0) 
                    { 
                        $reference = $this->database->sendResource($resource[0],$resource[1],$resource[2],$resource[3],$reqMerc,0);
                        $this->database->modifyResource($this->village->wid,$resource[0],$resource[1],$resource[2],$resource[3],0);
                        $this->database->addMovement(MovementTypeSid::MERCHANTS,$this->village->wid,$id,$reference,time(),time()+$timetaken,$post['send3']);
                        $this->logging->addMarketLog($this->village->wid,1,array($resource[0],$resource[1],$resource[2],$resource[3],$id));
                    } 
                } 
            } 
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']);
        }
    } 

    private function addOffer($post) 
    { 
        if($post['rid1'] == $post['rid2'])
        { 
            // Trading res for res of same type (invalid) 
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=2&e2");
        } 
        elseif($post['m1'] > (2 * $post['m2'])) 
        { 
            // Trade is for more than 2x (invalid) 
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=2&e2");
        } 
        elseif($post['m2'] > (2 * $post['m1'])) 
        { 
            // Trade is for less than 0.5x (invalid) 
            ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=2&e2");
        } 
        else 
        { 
            $wood = ($post['rid1'] == 1)? $post['m1'] : 0; 
            $clay = ($post['rid1'] == 2)? $post['m1'] : 0; 
            $iron = ($post['rid1'] == 3)? $post['m1'] : 0; 
            $crop = ($post['rid1'] == 4)? $post['m1'] : 0; 
            $availableWood = $this->database->getWoodAvailable($this->village->wid);
            $availableClay = $this->database->getClayAvailable($this->village->wid);
            $availableIron = $this->database->getIronAvailable($this->village->wid);
            $availableCrop = $this->database->getCropAvailable($this->village->wid);
             
            if($this->session->access == BANNED)
            { 
                ResponseHelper::redirect("banned.php");
            } 
             
            elseif($availableWood >= $wood AND $availableClay >= $clay AND $availableIron >= $iron AND $availableCrop >= $crop) 
            { 
                $reqMerc = 1; 
                 
                if(($wood+$clay+$iron+$crop) > $this->maxcarry) 
                { 
                    $reqMerc = round(($wood+$clay+$iron+$crop)/$this->maxcarry); 
                     
                    if(($wood+$clay+$iron+$crop) > $this->maxcarry*$reqMerc) 
                    { 
                        $reqMerc += 1; 
                    } 
                } 
                if($this->merchantAvail() != 0 && $reqMerc <= $this->merchantAvail()) 
                { 
                    if($this->database->modifyResource($this->village->wid,$wood,$clay,$iron,$crop,0))
                    { 
                        $time = 0; 
                        if(isset($_POST['d1'])) 
                        { 
                            $time = $_POST['d2'] * 3600; 
                        } 
                        $alliance = (isset($post['ally']) && $post['ally'] == 1)? $this->session->userinfo['alliance'] : 0;
                        $this->database->addMarket($this->village->wid,$post['rid1'],$post['m1'],$post['rid2'],$post['m2'],$time,$alliance,$reqMerc,0);
                    } 
                    // Enough merchants 
                    ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=2");
                } 
                else 
                { 
                    // Not enough merchants 
                    ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=2&e3");
                } 
            } 
            else 
            { 
                // not enough resources 
                ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=2&e1");
            } 
        } 
    } 

    private function acceptOffer($get) 
    { 
        $infoarray = $this->database->getMarketInfo($get['g']);
        $reqMerc = 1; 
        if($infoarray['wamt'] > $this->maxcarry) 
        { 
            $reqMerc = round($infoarray['wamt']/$this->maxcarry); 
            if($infoarray['wamt'] > $this->maxcarry*$reqMerc) 
            { 
                $reqMerc += 1; 
            } 
        } 
        $myresource = $hisresource = array(1=>0,0,0,0); 
        $myresource[$infoarray['wtype']] = $infoarray['wamt']; 
        $mysendid = $this->database->sendResource($myresource[1],$myresource[2],$myresource[3],$myresource[4],$reqMerc,0);
        $hisresource[$infoarray['gtype']] = $infoarray['gamt']; 
        $hissendid = $this->database->sendResource($hisresource[1],$hisresource[2],$hisresource[3],$hisresource[4],$infoarray['merchant'],0);
        $hiscoor = $this->database->getCoor($infoarray['vref']);
        $mytime = $this->generator->procDistanceTime($hiscoor,$this->village->coor,$this->session->tribe,0);
        $targettribe = $this->database->getUserField($this->database->getVillageField($infoarray['vref'],"owner"),"tribe",0);
        $histime = $this->generator->procDistanceTime($this->village->coor,$hiscoor,$targettribe,0);
        $this->database->addMovement(MovementTypeSid::MERCHANTS,$this->village->wid,$infoarray['vref'],$mysendid,time(),$mytime+time());
        $this->database->addMovement(MovementTypeSid::MERCHANTS,$infoarray['vref'],$this->village->wid,$hissendid,time(),$histime+time());
        $resource = array(1=>0,0,0,0); 
        $resource[$infoarray['wtype']] = $infoarray['wamt']; 
        $this->database->modifyResource($this->village->wid,$resource[1],$resource[2],$resource[3],$resource[4],0);
        $this->database->setMarketAcc($get['g']);
        $this->database->removeAcceptedOffer($get['g']);
        $this->logging->addMarketLog($this->village->wid,2,array($infoarray['vref'],$get['g']));
        ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$get['id']);
    } 

    private function loadOnsale() 
    {
        $displayarray = $this->database->getMarket($this->village->wid,1);
        $holderarray = array(); 
        foreach($displayarray as $value) 
        { 
            $targetcoor = $this->database->getCoor($value['vref']);
            $duration = $this->generator->procDistanceTime($targetcoor,$this->village->coor,$this->session->tribe,0);
            if($duration <= $value['maxtime'] || $value['maxtime'] == 0) 
            { 
                $value['duration'] = $duration; 
                array_push($holderarray,$value); 
            } 
        } 
        $this->onsale = $this->multiSort->sorte($holderarray, "'duration'", true, 2);
    } 

    private function filterNeed($get) 
    { 
        if(isset($get['v']) || isset($get['s']) || isset($get['b'])) 
        { 
            $holder = $holder2 = array(); 
            if(isset($get['v']) && $get['v'] == "1:1") 
            { 
                foreach($this->onsale as $equal) 
                { 
                    if($equal['wamt'] <= $equal['gamt']) 
                    { 
                        array_push($holder,$equal); 
                    } 
                } 
            } 
            else 
            { 
                $holder = $this->onsale; 
            } 
            foreach($holder as $sale) 
            { 
                if(isset($get['s']) && isset($get['b'])) 
                { 
                    if($sale['gtype'] == $get['s'] && $sale['wtype'] == $get['b']) 
                    { 
                        array_push($holder2,$sale); 
                    } 
                } 
                else if(isset($get['s']) && !isset($get['b'])) 
                { 
                    if($sale['gtype'] == $get['s']) 
                    { 
                        array_push($holder2,$sale); 
                    } 
                } 
                else if(isset($get['b']) && !isset($get['s']))  
                { 
                    if($sale['wtype'] == $get['b']) 
                    { 
                        array_push($holder2,$sale); 
                    } 
                } 
                else 
                { 
                    $holder2 = $holder; 
                } 
            } 
            $this->onsale = $holder2; 
        } 
        else 
        { 
            $this->loadOnsale(); 
        } 
    } 

    private function tradeResource($post) 
    {
        $wwvillage = $this->database->getResourceLevel($this->village->wid);
        if($wwvillage['f99t']!=40) 
        { 
            if($this->session->userinfo['gold'] >= 3)
            { 
                //kijken of ze niet meer gs invoeren dan ze hebben 
                if ($this->session->access == BANNED)
                { 
                    ResponseHelper::redirect("banned.php");
                } 
                else if (($post['m2'][0]+$post['m2'][1]+$post['m2'][2]+$post['m2'][3])<=(round($this->village->awood)+round($this->village->aclay)+round($this->village->airon)+round($this->village->acrop)))
                { 
                    $this->database->setVillageField($this->village->wid,"wood",$post['m2'][0]);
                    $this->database->setVillageField($this->village->wid,"clay",$post['m2'][1]);
                    $this->database->setVillageField($this->village->wid,"iron",$post['m2'][2]);
                    $this->database->setVillageField($this->village->wid,"crop",$post['m2'][3]);
                    $this->database->modifyGold($this->session->uid,3,0);
                    ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=3&c");;
                } 
                else 
                { 
                    ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=3");
                } 
            } 
            else 
            { 
                ResponseHelper::redirect(\App\Routes::BUILD . "?id=".$post['id']."&t=3");
            } 
        } 
    } 
}; 


