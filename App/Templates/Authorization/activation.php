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
        <div id="dynamic_header"> </div>
        <div id="header"></div>
    </div>
    
    <div id="mid">
        <?php include("parts/menu.php"); ?>
        <div id="content"  class="activate">
            <?php require_once "parts/activate/{$template}.php";?>
        </div>
        <div id="side_info" class="outgame">
        </div>
    </div>
    
    <?php include("parts/footer.php"); ?>
</body>
</html>