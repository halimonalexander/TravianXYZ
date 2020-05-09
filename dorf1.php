<?php

use App\Helpers\DatetimeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\TraceHelper;
use App\Models\User\User;

$loadVillage = true;
require_once 'appLoader.php';

$start  = TraceHelper::getTimer();

if (isset($_GET['ok'])) {
    $database->updateUserField($session->uid, 'ok', '0', '1');
    $_SESSION['ok'] = '0';
}

if (isset($_GET['newdid'])) {
    $_SESSION['wid'] = $_GET['newdid'];
    
    (new User())
        ->setSelectedVillage((int) $_GET['newdid'], $session->uid);
    
    ResponseHelper::redirect($_SERVER['PHP_SELF']);
} else {
    /** @var Building $building */
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
      <?php
      include("Templates/loggedInMenu.php");
      if ($_SESSION['ok'] == '1'){
          include_once "Templates/announcement.php";
          // todo это и есть контент ( надо будет не отрисовывать div id="content"
      }
      ?>
    
      <div id="content" class="village1">
      
          <h1>
              <?=$village->vname;
              if ($village->loyalty != '100') {
                  $color = $village->loyalty > '33' ? "gr" : "re";?>
                  <div id="loyality" class="<?=$color?>"><?=LOYALTY?><?=floor($village->loyalty)?>%</div>
              <?php } ?>
          </h1>
      
          <div id="cap" align="left">
              <?=($village->capital != '0') ? "<font color=gray>(Capital)</font>" : ""?>
          </div>
        
          <?php include("Templates/field.tpl");
          $timer = 1;
          ?>
        
          <div id="map_details">
            
              <?php
              include("Templates/movement.tpl");
              include("Templates/production.tpl");
              include("Templates/troops.tpl");

              if ($building->NewBuilding) {
                  include("Templates/Building.php");
              }
              ?>
          </div>

      <div id="side_info">
          <?php
          include("Templates/multivillage.tpl");
          include("Templates/quest.tpl");
          include("Templates/news.php");
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
