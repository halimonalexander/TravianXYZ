<?php
$s = isset($_GET['s']) ? (int) $_GET['s'] : 0;
?>

<h1><?=RESIDENCE?> <span class="level"><?=LEVEL?> <?=$village->resarray['f'.$id]?></span></h1>

<div id="textmenu">
    <a
        href="<?=\App\Routes::BUILD?>?id=<?=$id?>"
        <?php if ($s === 0) { echo "class=\"selected\""; } ?>
    >Management</a>
    <a 
        href="<?=\App\Routes::BUILD?>?id=<?=$id?>&amp;s=1"
        <?php if ($s === 1) { echo "class=\"selected\""; } ?>
    ><?=TRAIN?></a>
    <a
        href="<?=\App\Routes::BUILD?>?id=<?=$id?>&amp;s=2"
        <?php if ($s === 2) { echo "class=\"selected\""; } ?>
    ><?=CULTURE_POINTS?></a>
    <a
        href="<?=\App\Routes::BUILD?>?id=<?=$id?>&amp;s=3"
       <?php if ($s === 3) { echo "class=\"selected\""; } ?>
    ><?=LOYALTY?></a>
    <a
        href="<?=\App\Routes::BUILD?>?id=<?=$id?>&amp;s=4"
        <?php if ($s === 4) { echo "class=\"selected\""; } ?>
    ><?=EXPANSION?></a>
</div>