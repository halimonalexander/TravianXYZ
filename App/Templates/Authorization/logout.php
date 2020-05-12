<?php
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?=$title?></title>
    <meta name="content-language" content="en" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script src="/mt-core.js?2389c" type="text/javascript"></script>
    <script src="/mt-more.js?2389c" type="text/javascript"></script>
    <script src="/unx.js?2389c" type="text/javascript"></script>
    <script src="/new.js?2389c" type="text/javascript"></script>
    <link href="<?=$gpLocation?>lang/en/lang.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>lang/en/compact.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href='<?=$gpLocationExtra?>travian.css?e21d2' rel='stylesheet' type='text/css' />
    <link href='<?=$gpLocationExtra?>lang/en/lang.css?e21d2' rel='stylesheet' type='text/css' />";
    
    <script type="text/javascript">
        window.addEvent('domready', start);
    </script>
</head>


<body class="v35 ie ie8">
    <div class="wrapper">
        <img style="filter:chroma();" src="/img/x.gif" id="msfilter" alt=""/>
        <div id="dynamic_header"></div>
        <div id="header"></div>
    </div>
    
    <div id="mid">
        <?php include("parts/menu.php"); ?>
        
        <div id="content" class="logout">
            <h1>Logout successful.</h1><img class="roman" src="/img/x.gif" alt="">
            <p>Thank you for your visit.</p>
            
            <p>If other people use this computer too, you should delete your cookies for your own safety:<br/><a
                    href="/login?del_cookie">&raquo; delete cookies</a></p>
        </div>
        
        <div id="side_info">
            <?php
            include(__DIR__ . "/../../../Templates/news.php");
            ?>
        </div>
    </div>
   
    <?php
    include("parts/footer.php");
    ?>
</body>
</html>