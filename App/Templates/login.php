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
    <script src="/unx.js?0faaa" type="text/javascript"></script>
    <script src="/new.js?0faaa" type="text/javascript"></script>
    <link href="<?=$gpLocation?>lang/en/compact.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>lang/en/lang.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>travian.css?f4b7c" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>lang/en/lang.css" rel="stylesheet" type="text/css" />
</head>

<body class="v35 ie ie7" onload="initCounter()">
<div class="wrapper">
    <div id="dynamic_header"></div>
    
    <div id="header"></div>
    
    <div id="mid">
        <?php include(__DIR__ . "/../../Templates/loggedOutMenu.php")?>
        
        <div id="content" class="login">
            <h1><img class="img_login" src="/img/x.gif" alt="log in the game" /></h1>
            
            <?php
            if (COMMENCE > time()) { ?>
                <p><font color="red" size="6"><?=NOT_OPENED_YET?></font></p>
                
                <center><big>Server will start in: </big></center>
                
                <script type="text/javascript">
                    TargetDate = "<?=START_DATE?> <?=START_TIME?>";
                    CountActive = true;
                    CountStepper = -1;
                    LeadingZero = true;
                    DisplayFormat = "%%H%%:%%M%%:%%S%%";
                    FinishMessage = "START NOW";

                    function calcage(secs, num1, num2) {
                        s = ((Math.floor(secs/num1))%num2).toString();
                        if (LeadingZero && s.length < 2) {
                            s = "0" + s;
                        }

                        return "" + s + "";
                    }

                    function CountBack(secs) {
                        if (secs < 0) {
                            document.getElementById("cntdwn").innerHTML = FinishMessage;
                            return;
                        }
                        DisplayStr = DisplayFormat.replace(/%%D%%/g, calcage(secs,86400,100000));
                        DisplayStr = DisplayStr.replace(/%%H%%/g, calcage(secs,3600,100000));
                        DisplayStr = DisplayStr.replace(/%%M%%/g, calcage(secs,60,60));
                        DisplayStr = DisplayStr.replace(/%%S%%/g, calcage(secs,1,60));

                        document.getElementById("cntdwn").innerHTML = DisplayStr;
                        if (CountActive)
                            setTimeout("CountBack(" + (secs+CountStepper) + ")", SetTimeOutPeriod);
                    }

                    function putspan(backcolor, forecolor) {
                        document.write("<div class='activation_time' id='cntdwn'></div>");
                    }

                    if (typeof(BackColor)=="undefined")
                        BackColor = "white";
                    if (typeof(ForeColor)=="undefined")
                        ForeColor= "black";
                    if (typeof(TargetDate)=="undefined")
                        TargetDate = "12/31/2020 5:00 AM";
                    if (typeof(DisplayFormat)=="undefined")
                        DisplayFormat = "%%H%%:%%M%%:%%S%%";
                    if (typeof(CountActive)=="undefined")
                        CountActive = true;
                    if (typeof(FinishMessage)=="undefined")
                        FinishMessage = "";
                    if (typeof(CountStepper)!="number")
                        CountStepper = -1;
                    if (typeof(LeadingZero)=="undefined")
                        LeadingZero = true;

                    CountStepper = Math.ceil(CountStepper);

                    if (CountStepper == 0)
                        CountActive = false;

                    var SetTimeOutPeriod = (Math.abs(CountStepper)-1)*1000 + 990;
                    putspan(BackColor, ForeColor);
                    var dthen = new Date(TargetDate);
                    var dnow = new Date();
                    if(CountStepper>0)
                        ddiff = new Date(dnow-dthen);
                    else
                        ddiff = new Date(dthen-dnow);
                    gsecs = Math.floor(ddiff.valueOf()/1000);

                    CountBack(gsecs);
                </script>
            <?php } else {
            ?>
                <h5><img class="img_u04" src="/img/x.gif" alt="login" /></h5>
                
                <p><?=COOKIES?></p>
                
                <form method="post" name="snd" action="<?=\App\Routes::LOGIN?>">
                    <input type="hidden" name="ft" value="a4" />
                    
                    <script type="text/javascript">
                        Element.implement({
                            //imgid: falls zu dem link ein pfeil geh?rt kann dieser "auf/zugeklappt" werden
                            showOrHide: function(imgid) {
                                //einblenden
                                if (this.getStyle('display') == 'none')
                                {
                                    if (imgid != '')
                                    {
                                        $(imgid).className = 'open';
                                    }
                                }
                                //ausblenden
                                else
                                {
                                    if (imgid != '')
                                    {
                                        $(imgid).className = 'close';
                                    }
                                }
                                this.toggleClass('hide');
                            }
                        });
                    </script>
                    
                    <table cellpadding="1" cellspacing="1" id="login_form">
                        <tbody>
                        <tr class="top">
                            <th><?=NAME?></th>
                            <td>
                                <input
                                    class="text"
                                    type="text"
                                    name="user"
                                    value="<?=stripslashes(stripslashes(stripslashes($form->getDiff("user",$_COOKIE['COOKUSR']))))?>"
                                    maxlength="30"
                                    autocomplete='off'
                                />
                                <span class="error"><?=$form->getError("user")?></span>
                            </td>
                        </tr>
                        
                        <tr class="btm">
                            <th><?=PASSWORD?></th>
                            <td>
                                <input
                                    class="text"
                                    type="password"
                                    name="pw"
                                    value="<?=$form->getValue("pw");?>"
                                    maxlength="100"
                                    autocomplete='off'
                                />
                                <span class="error"><?=$form->getError("pw")?></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    
                    <p class="btn">
                        <!--<input type="hidden" name="e1d9d0c" value="" />-->
                        <input
                            type="image"
                            value="login"
                            name="s1"
                            onclick="xy();"
                            id="btn_login"
                            class="dynamic_img"
                            src="/img/x.gif"
                            alt="login button"
                        />
                    </p>
                </form>
            <?php }
            
            if ($form->getError("pw") == LOGIN_PW_ERROR) { ?>
                <p class="error_box">
                    <span class="error"><?=PW_FORGOTTEN?></span><br>
                    <?=PW_REQUEST?><br>
                    <a href="/password.php?npw=<?=$database->getUserField($form->getValue('user'), 'id', 1)?>">
                        <?=PW_GENERATE?>
                    </a>
                </p>
            <?php }
            
            if ($form->getError("activate") != "") { ?>
                <p class="error_box">
                    <span class="error"><?=EMAIL_NOT_VERIFIED?></span><br>
                    <?=EMAIL_FOLLOW?><br>
                    <a href="/activate?usr=<?=$form->getError("activate")?>"><?=VERIFY_EMAIL?></a>
                </p>
            <?php }
            
            if ($form->getError("vacation") != "") { ?>
                <p class="error_box">
                    <span class="error"><?=$form->getError("vacation")?></span>
                </p>
            <?php } ?>
        </div>
        
        <div id="side_info" class="outgame">
            <?php
            include(__DIR__ . "/../../Templates/News.php");
            ?>
        </div>
        
        <div class="clear"></div>
    </div>
    
    <div class="footer-stopper outgame"></div>
    <div class="clear"></div>
    
    <?php include(__DIR__ . "/../../Templates/footer.tpl"); ?>
    
    <div id="ce"></div>
</body>
</html>
