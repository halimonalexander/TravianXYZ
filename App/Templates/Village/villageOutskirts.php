<?php
use App\Helpers\TraceHelper;
use App\Helpers\DatetimeHelper;
?>
<?php require_once 'parts/head.php';?>

<body class="v35 ie ie8">
    <div class="wrapper">
        <img style="filter:chroma();" src="/img/x.gif" id="msfilter" alt=""/>
        
        <div id="dynamic_header"></div>
        
        <?php include(__DIR__ . "/../../../Templates/header.tpl"); ?>
    </div>
    
    <div id="mid">
        <?php
        include("parts/menu.php");
        if ($_SESSION['ok'] == '1'){
            include_once "../Templates/announcement.php";
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
            
            <?php include("parts/field.php");?>
            
            <div id="map_details">
                <?php
                include("parts/movement.php");
                include("parts/production.php");
                include("parts/troops.php");
                
                if ($building->NewBuilding) {
                    include("../Templates/Building.php");
                }
                ?>
            </div>
            
            <div id="side_info">
                <?php
                include(__DIR__ . "/../../../Templates/multivillage.tpl");
                include(__DIR__ . "/../../../Templates/quest.tpl");
                include(__DIR__ . "/../../../Templates/news.php");
                include(__DIR__ . "/../../../Templates/links.tpl");
                ?>
            </div>
            <div class="clear"></div>
        </div>
        
      
        <?php
        include(__DIR__ . "/../../../Templates/footer.tpl");
        include(__DIR__ . "/../../../Templates/res.tpl");
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