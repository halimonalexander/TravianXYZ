    <div id="side_navi">
        <a id="logo" href="<?=HOMEPAGE?>" title="logo">
            <img src="/img/x.gif" <?=($session->plus ? 'class="logo_plus"' :'')?> alt="Travian">
        </a>
      
        <p>
            <a href="<?=HOMEPAGE?>"><?=HOME?></a>
            <a href="/spieler.php?uid=<?php echo $session->uid; ?>"><?=PROFILE?></a>
            <a href="#" onclick="return Popup(0,0,1);"><?=INSTRUCT?></a>
            
            <?php if ($session->access == MULTIHUNTER) { ?>
                <a href="/Admin/admin.php"><font color="Blue"><?=MULTIHUNTER_PAN?></font></a>
            <?php } elseif ($session->access == ADMIN) { ?>
                <a href="/Admin/admin.php"><font color="Red"><?=ADMIN_PANEL?></font></a>
                <a href="/massmessage.php"><?=MASS_MESSAGE?></a>
                <a href="/sysmsg.php"><?=SYSTEM_MESSAGE?></a>
                <a href="/create_account.php">Create Natars</a>
            <?php } ?>
            <a href="/logout.php"><?php echo LOGOUT?></a>
        </p>
  
        <p>
            <a href="/plus.php?id=3">
                <?=SERVER_NAME?> <b><span class="plus_g">P</span><span class="plus_o">l</span><span class="plus_g">u</span><span class="plus_o">s</span></b>
            </a>
        </p>
  
        <p>
          <a href="/rules.php"><b><?=GAME_RULES?></b></a>
          <a href="/support.php"><b><?=SUPPORT?></b></a>
        </p>
      
        <?php
        $isDeletingAccountTimestamp = $database->isDeleting($session->uid);
        if ($isDeletingAccountTimestamp) {
            $time = $generator->getTimeFormat(($isDeletingAccountTimestamp - time()));
            $canCancelDeletion = $isDeletingAccountTimestamp > time() + 48 * 3600;
            ?>
            <p>
            <?php if ($canCancelDeletion) { ?>
                <a href="/spieler.php?s=3&id=<?=$session->uid?>&a=1&e=4">
                    <img class="del" src="/img/x.gif" alt="Cancel process" title="Cancel process" />
                </a>
            <?php } ?>
            
            <a href="/spieler.php?s=3">The account will be deleted in <span id="timer1"><?=$time?></span></a>
            </p>
        <?php } ?>
    </div>