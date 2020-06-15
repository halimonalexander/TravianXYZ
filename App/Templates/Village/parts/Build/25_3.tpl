<div id="build" class="gid25">
	<?php include("25_menu.php"); ?>

	<?=RESIDENCE_LOYALTY_DESC; ?>
	<b><?=floor($this->database->getVillageField($village->wid,'loyalty'))?></b> <?=PERCENT?>.
</div>
