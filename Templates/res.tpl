<?php
$wood = round($village->getProd("wood"));
$clay = round($village->getProd("clay"));
$iron = round($village->getProd("iron"));
$crop = round($village->getProd("crop"));
$totalproduction = $village->allcrop; // all crops + bakery + grain mill
?> 

<div id="res">
	<div id="resWrap">
		<table>
          <tbody>
            <tr>
              <th>
                <div>
                  <img src="/img/res/warehouse_medium.png" title="" alt="">
                  <span id="store_res"><?=$village->maxstore?></span>
                </div>
              </th>

              <td title="<?=LUMBER?>\n Production: <?=$wood?> per hour\n Full in: ">
                <img src="/img/res/lumber_small.png" class="r1" alt="<?=LUMBER?>" /><span id="l4"><?=round($village->awood)?></span>
              </td>

              <td title="<?=CLAY?>\n Production: <?=$clay?> per hour\n Full in: ">
                <img src="/img/res/clay_small.png" class="r2" alt="<?=CLAY?>" /><span id="l3"><?=round($village->aclay)?></span>
              </td>

              <td title="<?=IRON?>\n Production: <?=$iron?> per hour\n Full in: ">
                <img src="/img/res/iron_small.png" class="r3" alt="<?=IRON?>" /><span id="l2"><?=round($village->airon)?></span>
              </td>
            </tr>

            <tr>
              <th>
                <div>
                  <img src="/img/res/granary_medium.png" title="" alt="">
                  <span id="store_crop"><?=$village->maxcrop?></span>
                </div>
              </th>

              <td title="<?=CROP?>\n Production: <?=$crop?> per hour\n Full in: ">
                <img src="/img/res/crop_small.png" class="r4" alt="<?=CROP?>" /><span <?php if($village->acrop > 0) { ?>id="l1"<?php }?>><?=$village->acrop ? round($village->acrop) : 0?></span>
              </td>

              <td><img src="/img/res/freeCrop_medium.png" class="r5" alt="<?=CROP_COM?>" title="<?=CROP_COM?>" /><?=($totalproduction - $village->pop + $technology->getUpkeep($village->unitall,0))?></td>
            </tr>

            <tr>
              <td>
                <img
                  src="<?=GP_LOCATE?>img/a/<?php if ($this->session->gold <= 1): ?>gold_g<?php else: ?>gold<?php endif; ?>.gif"
                  alt="Remaining gold"
                  title="You currently have: <?=$this->session->gold?> gold"
                />
                <?php if ($this->session->gold <= 1): ?><font color="#B3B3B3"><?php endif;?>
                  <?=$this->session->gold?>
                <?php if ($this->session->gold <= 1): ?></font><?php endif;?>
              </td>
            </tr>
          </tbody>
	    </table>
    </div>
</div>