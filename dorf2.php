<?php

use App\Helpers\DatetimeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\TraceHelper;
use App\Models\User\User;

include("GameEngine/Village.php");
require_once 'tempGlobalLoader.php';

$start = TraceHelper::getTimer();

if (isset($_GET['newdid'])) {
    $_SESSION['wid'] = $_GET['newdid'];
    
    (new User())
        ->setSelectedVillage((int) $_GET['newdid'], $session->uid);
    
    ResponseHelper::redirect($_SERVER['PHP_SELF']);
} else {
    $building->procBuild($_GET);
}

require_once 'Templates/head.php';
?>


<body class="v35 ie ie8">
    <div class="wrapper">
        <img style="filter:chroma();" src="img/x.gif" id="msfilter" alt=""/>
        <div id="dynamic_header"></div>
        <?php include("Templates/header.tpl"); ?>
      
        <div id="mid">
            <?php include("Templates/menu.tpl"); ?>
          
            <div id="content" class="village2">
                <h1>
                    <?=$village->vname;
                    if ($village->loyalty != '100') {
                        $color = $village->loyalty > '33' ? "gr" : "re";?>
                      <div id="loyality" class="<?=$color?>"><?=LOYALTY?><?=floor($village->loyalty)?>%</div>
                    <?php } ?>
                </h1>
              
              <?php include("Templates/dorf2.php");
              if ($building->NewBuilding) {
                  include("Templates/Building.php");
              }
              ?>
          </div>
        <div id="side_info">
            <?php
            include("Templates/multivillage.tpl");
            include("Templates/quest.tpl");
            include("Templates/news.tpl");
            include("Templates/links.tpl");
            ?>
        </div>
        <div class="clear"></div>
      </div>
  
    <div class="footer-stopper"></div>
    <div class="clear"></div>
  
    <?php
    include("Templates/footer.tpl");
    include("Templates/res.tpl");
    ?>
  
    <div id="stime">
        <div id="ltime">
            <div id="ltimeWrap">
                <?=CALCULATED_IN?> <b><?=round(TraceHelper::getDiff($start) * 1000)?></b> ms
                <br/>
                <?=SEVER_TIME?> <span id="tp1" class="b"><?=DatetimeHelper::currentTime()?></span>
            </div>
        </div>
    </div>
    <div id="ce"></div>
</body>
</html>
