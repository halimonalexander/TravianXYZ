<table id="production">
    <thead>
        <tr>
            <th colspan="4"><?=PROD_HEADER?>:</th>
        </tr>
    </thead>
  
    <tbody>
        <tr>
            <td class="ico"><img class="r1" src="/img/x.gif" alt="<?=LUMBER?>" title="<?=LUMBER?>" /></td>
            <td class="res"><?=LUMBER?>:</td>
            <td class="num"><?=$village->getProd("wood")?></td>
            <td class="per"><?=PER_HR?></td>
        </tr>
		
        <tr>
            <td class="ico"><img class="r2" src="/img/x.gif" alt="<?=CLAY?>" title="<?=CLAY?>" /></td>
            <td class="res"><?=CLAY?>:</td>
            <td class="num"><?=$village->getProd("clay")?></td>
            <td class="per"><?=PER_HR?></td>
        </tr>
        
        <tr>
            <td class="ico"><img class="r3" src="/img/x.gif" alt="<?=IRON?>" title="<?=IRON?>" /></td>
            <td class="res"><?=IRON?>:</td>
            <td class="num"><?=$village->getProd("iron")?></td>
            <td class="per"><?=PER_HR?></td>
        </tr>
        
        <tr>
            <td class="ico"><img class="r4" src="/img/x.gif" alt="<?=CROP?>" title="<?=CROP?>" /></td>
            <td class="res"><?=CROP?>:</td>
            <td class="num"><?=$village->getProd("crop")?></td>
            <td class="per"><?=PER_HR?></td>
        </tr>
		</tbody>	
</table>