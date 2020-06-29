<div id="textmenu">
    <a href="<?=\App\Routes::BUILD?>?id=<?=$id; ?>"><?=OVERVIEW;?></a>
    <a href="/a2b.php"><?=SEND_TROOPS;?></a>
    <a href="/warsim.php"><?=Q20_RESP1;?></a>
    <?php if ($this->session->goldclub==1): ?>
        <a href="<?=\App\Routes::BUILD?>?id=<?=$id; ?>&amp;t=99">Gold Club</a>
    <?php endif; ?>
</div>
