<?php
require_once 'parts/head.php';
?>

<body class="v35 ie ie8">
    <div class="wrapper">
        <img style="filter:chroma();" src="/img/x.gif" id="msfilter" alt="" />
        
        <div id="dynamic_header"></div>
    
        <?php include(__DIR__ . "/../../../Templates/header.tpl"); ?>
    </div>
    
    <div id="mid">
        <?php include(__DIR__ . "/parts/menu.php"); ?>
        
        <div id="content"  class="build">
            <?php
            
            $id = $_GET['id'];
            
            if ($id == '99' && $village->resarray['f99t'] == 40) {
                include(__DIR__ . "/parts/Build/ww.php");
            } elseif ($village->resarray['f'.$_GET['id'].'t'] == 0 && $_GET['id'] >= 19) {
                include(__DIR__ . "/parts/Build/avaliable.php");
            } else {
                if (isset($_GET['t']) && !empty($_GET['t'])) {
                    if ($_GET['t'] == 1) {
                        $_SESSION['loadMarket'] = 1;
                    }
                    
                    $template = $village->resarray['f'.$_GET['id'].'t']."_".$_GET['t'];
                } elseif (isset($_GET['s']) && !empty($_GET['s'])) {
                    $template = $village->resarray['f'.$_GET['id'].'t']."_".$_GET['s'];
                } else {
                    $template = $village->resarray['f'.$_GET['id'].'t'];
                }
    
                include(__DIR__ . "/parts/Build/{$template}.tpl");
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
</body>
</html>
