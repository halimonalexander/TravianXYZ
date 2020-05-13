<map name="rx" id="rx">
  <?php for($i=1;$i<=18;$i++): ?>
      <area
          href="/build.php?id=<?=$i?>"
          coords="<?=$coors[$i]?>"
          shape="circle"
          title="<?=$building->procResType($village->resarray['f'.$i.'t'])?> Level <?=$village->resarray['f'.$i]?>"
      />
  <?php endfor; ?>
	<area href="<?=\App\Routes::DORF2?>" coords="144,131,36" shape="circle" title="Village centre" alt="" />
</map>

<div id="village_map" class="f<?php echo $village->type; ?>">
    <?php
    for ($i=1; $i<=18; $i++) {
        if ($village->resarray['f'.$i.'t'] != 0) {
            $text = "";
            switch($i){
                case 1:$text = "Woodcutter";break;
                case 2:$text = "Clay Pit";break;
                case 3:$text = "Iron Mine";break;
                case 4:$text = "Cropland";break;
            }
            ?>
            <img
                src="/img/x.gif"
                class="reslevel rf<?=$i?> level<?=$village->resarray['f'.$i]?>"
                alt="<?=$text?> Level <?=$village->resarray['f'.$i]?>" />
            <?php
        }
    }
    ?>
  
    <img id="resfeld" usemap="#rx" src="/img/x.gif" alt="" />
</div>