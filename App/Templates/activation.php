<?php
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?=$title?></title>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <meta name="content-language" content="en" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script src="/mt-core.js?0faaa" type="text/javascript"></script>
    <script src="/mt-more.js?0faaa" type="text/javascript"></script>
    <script src="/unx.js?0faaa" type="text/javascript"></script>
    <script src="/new.js?0faaa" type="text/javascript"></script>
    <link href="<?=$gpLocation?>lang/en/compact.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>lang/en/lang.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>travian.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>lang/en/lang.css" rel="stylesheet" type="text/css" />
</head>

<body class="v35 ie ie7" onload="initCounter()">

<div class="wrapper">
    <div id="dynamic_header">
    </div>
    <div id="header"></div>
    <div id="mid">
        <?php include(__DIR__ . "/../../Templates/menu.tpl"); ?>
        <div id="content"  class="activate">
            <?php
            
            if (
                isset($_GET['e']) &&
                (
                    START_DATE < date('m/d/Y') ||
                    START_DATE == date('m/d/Y') &&
                    START_TIME <= date('H:i')
                )
            ) {
                switch($_GET['e'])
                {
                    case 1:
                        include(__DIR__ . "/activate/delete.php");
                        break;
                    case 2:
                        include(__DIR__ . "/activate/activated.php");
                        break;
                    case 3:
                        include(__DIR__ . "/activate/cantfind.php");
                        break;
                }
            } else if(isset($_GET['id']) && isset($_GET['c'])) {
                $c=$database->getActivateField($_GET['id'],"email",0);
                if($_GET['c'] == $generator->encodeStr($c,5)){
                    include(__DIR__ . "/activate/delete.php");
                } else {
                    include(__DIR__ . "/activate/activate.php");
                }
            } else {
                include(__DIR__ . "/activate/activate.php");
            }
            
            ?>
        </div>
        <div id="side_info" class="outgame">
        </div>
        
        <div class="clear"></div>
    </div>
    
    <div class="footer-stopper outgame"></div>
    <div class="clear"></div>
    
    <?php include(__DIR__ . "/../../Templates/footer.tpl"); ?>
    <div id="ce"></div>
</body>
</html>