<table id="troops">
    <thead>
        <tr>
	          <th colspan="3"><?php echo TROOPS_DORF; ?></th>
        </tr>
    </thead>
  
    <tbody>
        <?php
        $troops = $technology->getAllUnits($village->wid, true, 1);
        $TroopsPresent = false;
        for ($i=1; $i<=50; $i++) {
            if ($troops['u'.$i] > 0) { ?>
                <tr>
                    <td class="ico">
                        <a href="<?=\App\Routes::BUILD?>?id=39">
                            <img
                                class="unit u<?=$i?>"
                                src="/img/x.gif"
                                alt="<?=$technology->getUnitName($i)?>"
                                title="<?=$technology->getUnitName($i)?>"
                            />
                        </a>
                    </td>
                    
                    <td class="num"><?=$troops['u'.$i]?></td>
                    <td class="un"><?=$technology->getUnitName($i)?></td>
                </tr>
                <?php
                $TroopsPresent = True;
            }
        }
        
        if($troops['hero'] > 0) { ?>
            <tr>
                <td class="ico">
                    <a href="<?=\App\Routes::BUILD?>?id=39">
                        <img class="unit uhero" src="/img/x.gif" alt="Hero" title="Hero" />
                    </a>
                </td>
                
                <td class="num"><?=$troops['hero']?></td>
                <td class="un">Hero</td></tr>
            <?php
            $TroopsPresent = True;
        }
        
        $units = $technology->getUnitList($village->wid);
        
        if(!$TroopsPresent) {
          echo "<tr><td>none</td></tr>";
        }
        ?>
	  </tbody>
</table>
</div>
