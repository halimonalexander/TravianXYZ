<div id="build" class="gid25">
	<?php include("25_menu.php"); ?>

	<table cellpadding="1" cellspacing="1" id="expansion">
		<thead>
			<tr>
				<th colspan="6"><a name="h2"></a><?=CONQUERED_BY_VILLAGE?></th>
			</tr>
			<tr>
				<td colspan="2"><?=VILLAGE?></td>
				<td><?=PLAYER?></td>
				<td><?=INHABITANTS?></td>
				<td><?=COORDINATES?></td>
				<td><?=DATE?></td>
			</tr>
		</thead>
		<tbody>
			<?php
			$slot1 = $this->database->getVillageField($village->wid, 'exp1');
			$slot2 = $this->database->getVillageField($village->wid, 'exp2');
			$slot3 = $this->database->getVillageField($village->wid, 'exp3');

			if ($slot1 != 0 || $slot2 != 0 || $slot3 != 0){
				for($i=1; $i <= 3; $i++){
					if (${'slot'.$i}<>0) {
						$coor = $this->database->getCoor(${'slot'.$i});
						$vname = $this->database->getVillageField(${'slot'.$i},'name');
						$owner = $this->database->getVillageField(${'slot'.$i},'owner');
						$pop = $this->database->getVillageField(${'slot'.$i},'pop');
						$vcreated = $this->database->getVillageField(${'slot'.$i},'created');
						$ownername = $this->database->getUserField($owner,'username',0);
						?>
						<tr>
							<td class="ra">'.$i.'.</td>
							<td class="vil">
								<a href="<?=\App\Routes::MAP?>?d=<?=${'slot'.$i}?>&c=<?=$generator->getMapCheck(${'slot'.$i})?>"><?=$vname?></a>
							</td>
							<td class="pla"><a href="/spieler.php?uid=<?=$owner?>"><?=$ownername?></a></td>
							<td class="ha"><?=$pop?></td>
							<td class="aligned_coords">
								<div class="cox">(<?=$coor['x']?></div>
								<div class="pi">|</div>
								<div class="coy"><?=$coor['y']?>)</div>
							</td>
							<td class="dat"><?=date('d-m-Y',$vcreated)?></td>
						</tr>
						<?php
					}
				}
			} else{ ?>
			<tr>
				<td colspan="6" class="none"><?=NONE_CONQUERED_BY_VILLAGE?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
