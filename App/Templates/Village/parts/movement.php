<?if (!empty($movements)) :?>
<table id="movements">
    <thead>
        <tr>
            <th colspan="3"><?=TROOP_MOVEMENTS?></th>
        </tr>
    </thead>
    
    <tbody>
        <?php foreach ($movements as $movement):?>
        <tr>
            <td class="typ">
                <a href="/<?=\App\Routes::BUILD?>?id=39">
                    <img
                        src="/img/x.gif"
                        class="<?=$movement['action']?>"
                        alt="<?=$movement['title']?>"
                        title="<?=$movement['title']?>"
                    />
                </a>
                <span class="<?=$movement['aclass']?>">&raquo;</span>
            </td>
            
            <td>
                <div class="mov">
                    <span class="<?=$movement['aclass']?>"><?=$movement['quantity']?>&nbsp;<?=$movement['short']?></span>
                </div>
                <div class="dur_r">
                    in&nbsp;<span id="timer<?=$movement['timerId']?>"><?=$movement['leftTime']?></span>&nbsp;<?=HOURS?>
                </div>
            </td>
        </tr>
        <?php endforeach;?>
    </tbody>
</table>
<?php endif;