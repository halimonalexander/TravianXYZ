<?php
	/* Hero's mansion oases page
	Copyright: Travianx Project */

	$oasisarray = $this->database->getOasis($village->wid);
if($_GET['gid']==37 && isset($_GET['del'])){
if($session->access != BANNED){
	$this->database->removeOases($_GET['del']);
	header("Location: ".\App\Routes::BUILD."?id=".$id."&land");
}else{
	header("Location: banned.php");
}
}
?>
<table id="oases" cellpadding="1" cellspacing="1">
<thead><tr>
<th colspan="4"><?php echo OASES; ?></th>
</tr>
<tr>
<td><?php echo NAME; ?></td>
<td><?php echo COORDINATES; ?></td>
<td><?php echo LOYALTY; ?></td>
<td><?php echo RESOURCES; ?></td>
</tr></thead>
<tbody>

<?php

	for ($i=0; $i<count($oasisarray); $i++) {
		$oasiscoor = $this->database->getCoor($oasisarray[$i]['wref']); 
?>
<tr>
<td class="nam">
<a href="<?=\App\Routes::BUILD?>?gid=37&c=<?php echo $generator->getMapCheck($oasisarray[$i]['wref']); ?>&del=<?php echo $oasisarray[$i]['wref']; ?>&land"><img class="del" src="img/x.gif" alt="delete" title="<?php echo DELETE; ?>"></a>
<a href="<?=\App\Routes::MAP?>?d=<?php echo $oasisarray[$i]['wref']; ?>&c=<?php echo $generator->getMapCheck($oasisarray[$i]['wref']) ?>"><?php echo $oasisarray[$i]['name']; ?></a>
</td>
<td class="aligned_coords">
<div class="cox">(<?php echo $oasiscoor['x']; ?></div>
<div class="pi">|</div>
<div class="coy"><?php echo $oasiscoor['y']; ?>)</div>
</td>
<td class="zp"><?php echo floor($oasisarray[$i]['loyalty']); ?>%</td>
<td class="res"><?php
	switch($oasisarray[$i]['type']) {
		case 1:
		case 2:
			?><img class="r1" src="img/x.gif" alt="Wood" title="<?php echo LUMBER; ?>" />+25%<?php
			break;
		case 3:
			?><img class="r1" src="img/x.gif" alt="Wood" title="<?php echo LUMBER; ?>" />+25%
			<img class="r4" src="img/x.gif" alt="Crop" title="<?php echo CROP; ?>" />+25%<?php
			break;
		case 4:
		case 5:
			?><img class="r2" src="img/x.gif" alt="Clay" title="<?php echo CLAY; ?>" />+25%<?php
			break;
		case 6:
			?><img class="r2" src="img/x.gif" alt="Clay" title="<?php echo CLAY; ?>" />+25%
			<img class="r4" src="img/x.gif" alt="Crop" title="<?php echo CROP; ?>" />+25%<?php
			break;
		case 7:
		case 8:
			?><img class="r3" src="img/x.gif" alt="Iron" title="<?php echo IRON; ?>" />+25%<?php
			break;
		case 9:
			?><img class="r3" src="img/x.gif" alt="Iron" title="<?php echo IRON; ?>" />+25%
			<img class="r4" src="img/x.gif" alt="Crop" title="<?php echo CROP; ?>" />+25%<?php
			break;
		case 10:
		case 11:
			?><img class="r4" src="img/x.gif" alt="Crop" title="<?php echo CROP; ?>" />+25%<?php
			break;
		case 12:
			?><img class="r4" src="img/x.gif" alt="Crop" title="<?php echo CROP; ?>" />+50%<?php
			break;
	}
?></td>
</tr>
<?php } ?>
</tbody>
</table>