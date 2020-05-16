<?php
if ($building->walling()) {
    $wtitle = $building->procResType($building->walling())." Level ".$village->resarray['f40'];
} else {
    $wtitle = ($village->resarray['f40'] == 0)? "Outer building site" : $building->procResType($village->resarray['f40t'],0)." Level ".$village->resarray['f40'];
}

$data = [];
for ($t=19; $t<=39; $t++) {
    if ($village->natar == 1 && in_array($t, [25, 26, 29, 30, 33])) {
        if ($t == 33) {
            $data[$t]['title'] = $building->procResType(40);
            if ($village->resarray['f99'] != 0) {
                $data[$t]['title'] .= " Level " . $village->resarray['f99'];
            }
            $data[$t]['coords'] = '190,170,80';
            $data[$t]['shape'] = 'circle';
            $data[$t]['id'] = 99;
        }
    } else {
        if ($village->resarray['f'.$t.'t'] != 0) {
            $data[$t]['title'] = $building->procResType($village->resarray['f'.$t.'t']). " Level ".$village->resarray['f'.$t];
        } else {
            if ($t == 39 && $village->resarray['f'.$t] == 0) {
                $data[$t]['title'] = "Rally Point building site";
            } else {
                $data[$t]['title'] = "Building site";
            }
        }
        $data[$t]['coords'] = $coords[$t];
        $data[$t]['shape'] = 'poly';
        $data[$t]['id'] = $t;
    }
}
?>
<map name="map1" id="map1">
    <area href="<?=\App\Routes::BUILD?>?id=40" title="<?=$wtitle; ?>" coords="325,225,180" shape="circle" alt="" />
    <area href="<?=\App\Routes::BUILD?>?id=40" title="<?=$wtitle; ?>" coords="220,230,185" shape="circle" alt="" />
</map>

<map name="map2" id="map2">
    <?php foreach ($data as $item): ?>
    <area href="<?=\App\Routes::BUILD?>?id=<?=$item['id']?>" title="<?=$item['title']?>" coords="<?=$item['coords']?>" shape="<?=$item['shape']?>"/>
    <?php endforeach;?>
	
    <area href="<?=\App\Routes::BUILD?>?id=40" title="<?=$wtitle?>" coords="312,338,347,338,377,320,406,288,421,262,421,222,396,275,360,311" shape="poly" alt="" />
    <area href="<?=\App\Routes::BUILD?>?id=40" title="<?=$wtitle?>" coords="49,338,0,274,0,240,33,286,88,338" shape="poly" alt="" />
    <area href="<?=\App\Routes::BUILD?>?id=40" title="<?=$wtitle?>" coords="0,144,34,88,93,39,181,15,252,15,305,31,358,63,402,106,421,151,421,93,378,47,280,0,175,0,78,28,0,92" shape="poly" alt="" />
</map>

<?php
if($building->walling()) {
    $vmapc = "d2_1" . ($this->session->tribe == 3 ? $this->session->tribe : '');
} else {
    $vmapc = ($village->resarray['f40'] == 0)? "d2_0" : "d2_1" . ($this->session->tribe == 3 ? $this->session->tribe : '');
}
?>
<div id="village_map" class="<?php echo $vmapc; ?>">
    <?php
    for ($i=1; $i<=20; $i++) {
        if ($village->natar == 1 && in_array($i + 18, [25,26,29,30,33])) {
        } else {
            $text = "Building site";
            $img = "iso";
            
            if ($village->resarray['f'.($i+18).'t'] != 0) {
                $img = "g".$village->resarray['f'.($i+18).'t'];
                $text = $building->procResType($village->resarray['f'.($i+18).'t'])." Level ".$village->resarray['f'.($i+18)];
            }
            
            foreach ($building->buildArray as $job) {
                if ($job['field'] == $i + 18) {
                    $img = 'g'.$job['type'].'b';
                    $text = $building->procResType($job['type'])." Level ".$village->resarray['f'.$job['field']];
                }
            }
            ?>
            <img src="/img/x.gif" class="building d<?=$i?> <?=$img?>" alt="<?=$text?>" />
            <?php
            

        }
    }

    //set event last quest
    if (
        ($_SESSION['qst'] == 38 && QTYPE == 37) ||
        ($_SESSION['qst'] == 31 && QTYPE == 25)
    ) {
        $dte = ["tur", "purp", "yell", "oran", "green", "red", "dark"];
        
        for ($i = 0; $i <= 7; $i++) { ?>
            <img src="/img/x.gif" class="building e<?=$i?> rocket <?=$dte[$i-1]?>" alt="<?=$text?>" />
        <?php }
    }
    
    if (
        ($_SESSION['qst'] == 38 && QTYPE == 37) ||
        ($_SESSION['qst'] == 31 && QTYPE == 25)
    ){
        $this->database->updateUserField($_SESSION['username'], 'quest', '40', 0);
        $_SESSION['qst'] = 40;
    }
    
    if ($village->resarray['f39'] == 0) {
        if ($building->rallying()) { ?>
            <img src="/img/x.gif" class="dx1 g16b" alt="Rally Point Level <?=$village->resarray['f39']?>" />
        <?php } else { ?>
            <img src="/img/x.gif" class="dx1 g16e" alt="Rally Point building site" />
        <?php }
    } else { ?>
        <img src="/img/x.gif" class="dx1 g16" alt="Rally Point Level <?=$village->resarray['f39']?>" />
    <?php }
    
    if ($village->resarray['f99t'] == 40) {
        if ($village->resarray['f99'] >= 0 && $village->resarray['f99'] <= 19) {
           echo '<img class="ww g40" src="/img/x.gif" alt="Worldwonder">';
        }
        if ($village->resarray['f99'] >= 20 && $village->resarray['f99'] <= 39) {
            echo '<img class="ww g40_1" src="/img/x.gif" alt="Worldwonder">';
        }
        if ($village->resarray['f99'] >= 40 && $village->resarray['f99'] <= 59) {
           echo '<img class="ww g40_2" src="/img/x.gif" alt="Worldwonder">';
        }
        if ($village->resarray['f99'] >= 60 && $village->resarray['f99'] <= 79) {
           echo '<img class="ww g40_3" src="/img/x.gif" alt="Worldwonder">';
        }
        if ($village->resarray['f99'] >= 80 && $village->resarray['f99'] <= 99) {
            echo '<img class="ww g40_4" src="/img/x.gif" alt="Worldwonder">';
        }
        if ($village->resarray['f99'] == 100) {
            echo '<img class="ww g40_5" src="/img/x.gif" alt="Worldwonder">';
        }
    }
    ?>
  
    <div id="levels" <?=isset($_COOKIE['t3l']) ? 'class="on"' : ''?>>
        <?php
        for($i=1; $i<=20; $i++) {
            $ttt = ['', 'lvl-tooltip-available', 'lvl-tooltip-unavailable', 'lvl-tooltip-prohibited', 'lvl-tooltip-maxlevel'];
            $ttb = ['', 'lvl-tooltip-underConstruction'];
            
            $buildingType  = $village->resarray['f' . ($i + 18) . 't'];
            $buildingLevel = $village->resarray['f' . ($i + 18)];
            
            if ($buildingType != 0) {
                // todo алгоритм не учитывает очередь постройки
                $class = "";
                $buildingData = \App\Helpers\GlobalVariablesHelper::getBuilding($buildingType);
                if (count($buildingData) == $buildingLevel) {
                    $class .= "lvl-tooltip-maxlevel";
                } elseif (
                    $buildingData[$buildingLevel + 1]['wood'] > $village->maxstore ||
                    $buildingData[$buildingLevel + 1]['clay'] > $village->maxstore ||
                    $buildingData[$buildingLevel + 1]['iron'] > $village->maxstore ||
                    $buildingData[$buildingLevel + 1]['crop'] > $village->maxcrop
                ) {
                    $class .= "lvl-tooltip-prohibited";
                } elseif (
                    $buildingData[$buildingLevel + 1]['wood'] > $village->awood ||
                    $buildingData[$buildingLevel + 1]['clay'] > $village->aclay ||
                    $buildingData[$buildingLevel + 1]['iron'] > $village->airon ||
                    $buildingData[$buildingLevel + 1]['crop'] > $village->acrop
                ) {
                    $class .= "lvl-tooltip-unavailable";
                } else {
                    $class .= "lvl-tooltip-available";
                }
                
                echo "<div class=\"lvl-tooltip {$class} d$i\">".$village->resarray['f'.($i+18)]."</div>";
            }
        }
        
        if ($village->resarray['f39t'] != 0) { ?>
            <div class="lvl-tooltip l39"><?=$village->resarray['f39']?></div>
        <?php }
        
        if ($village->resarray['f40t'] != 0) { ?>
            <div class="lvl-tooltip l40"><?=$village->resarray['f40']?></div>
        <?php }
        
        if ($village->resarray['f99t'] != 0) { ?>
            <div class="lvl-tooltip d40"><?=$village->resarray['f99']?></div>
        <?php } ?>
    </div>
    
    <img class="map1" usemap="#map1" src="/img/x.gif" alt="" />
    <img class="map2" usemap="#map2" src="/img/x.gif" alt="" />
</div>

<img src="/img/x.gif" id="lswitch" <?php if(isset($_COOKIE['t3l'])) { echo "class=\"on\""; } ?> onclick="vil_levels_toggle()" />
