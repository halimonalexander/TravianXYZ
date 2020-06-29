<?php
include("next.php");

$level = $village->resarray['f'.$id];
$bid  = \App\Helpers\GlobalVariablesHelper::getBuilding(1); 
?>
<div id="build" class="gid1">
	<a href="#" onClick="return Popup(0,4);" class="build_logo">
		<img class="building g1" src="/img/x.gif" alt="<?=B1?>" title="<?=B1?>" />
	</a>

	<h1><?=B1?> <span class="level"><?=LEVEL?> <?=$level?></span></h1>

	<p class="build_desc"><?=B1_DESC?></p>

	<table cellpadding="1" cellspacing="1" id="build_value">
		<tr>
			<th><?=CUR_PROD?>:</th>
			<td><b><?=$bid[$level]['prod']* SPEED?></b> <?=PER_HR?></td>
		</tr>
		<?php
		if (!$building->isMax($village->resarray['f'.$id.'t'], $id)) {
			$next = $level + 1 + $loopsame + $doublebuild + $master;
			if ($village->capital == 1) {
				if ($next<=20) { ?>
					<tr>
						<th><?=NEXT_PROD; echo $next?>:</th>
						<td><b><?=$bid[$next]['prod']* SPEED?></b> <?=PER_HR?></td>
					</tr>
				<?php } else { ?>
					<tr>
						<th><?=NEXT_PROD; echo 20 ?>:</th>
						<td><b><?=$bid[20]['prod']* SPEED?></b> <?=PER_HR?></td>
					</tr>
				<?php }
			}else{
				if ($next<=10){ ?>
					<tr>
						<th><?=NEXT_PROD; echo $next?>:</th>
						<td><b><?=$bid[$next]['prod']* SPEED?></b> <?=PER_HR?></td>
					</tr>
				<?php } else { ?>
					<tr>
						<th><?=NEXT_PROD; echo 10?>:</th>
						<td><b><?=$bid[10]['prod']* SPEED?></b> <?=PER_HR?></td>
					</tr>
				<?php }
			}
		}
		?>
	</table>

	<?php include("upgrade.php");?>
</p>
</div>
