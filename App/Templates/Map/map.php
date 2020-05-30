<?php
use App\Helpers\ResponseHelper;
use App\Helpers\TraceHelper;
use App\Helpers\DatetimeHelper;
?>
<?php require_once __DIR__ . '/../Village/parts/head.php';?>

<body class="v35 ie ie8">
<div class="wrapper">
    <img style="filter:chroma();" src="/img/x.gif" id="msfilter" alt="" />
    <div id="dynamic_header">
    </div>
    <?php include(__DIR__ . "/../../../Templates/header.tpl"); ?>
    <div id="mid">
        <?php
        require_once __DIR__ . '/../Village/parts/menu.php';

        if (isset($_GET['d']) && isset($_GET['c'])) {
            if ($generator->getMapCheck($_GET['d']) == $_GET['c']) {
                include("parts/vilview.php");
            } else {
                ResponseHelper::redirect(\App\Routes::DORF1);
            }
        } else {
            include("parts/mapview.php");
        }
        ?>
        <div id="side_info">
            <?php
            include(__DIR__ . "/../../../Templates/multivillage.tpl");
            include(__DIR__ . "/../../../Templates/quest.tpl");
            include(__DIR__ . "/../../../Templates/news.php");
            include(__DIR__ . "/../../../Templates/links.tpl");
            ?>
        </div>
    </div>

    <?php
    include(__DIR__ . "/../../../Templates/footer.tpl");
    include(__DIR__ . "/../../../Templates/res.tpl");
    ?>
    <div id="stime">
        <div id="ltime">
            <div id="ltimeWrap">
                Calculated in <b><?php
                    echo \App\Helpers\TraceHelper::getDiffInSeconds($start);
                    ?></b> ms

                <br />Server time: <span id="tp1" class="b"><?php echo date('H:i:s'); ?></span>
            </div>
        </div>
    </div>
    <div id="ce"></div>
</body>
</html>