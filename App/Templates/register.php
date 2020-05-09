<?php
use App\Sids\TribeSid;
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
        <div id="dynamic_header"></div>
        
        <div id="header"></div>
        
        <div id="mid">
            <?php include(__DIR__ . "/../../Templates/menu.tpl");
        
            if(REG_OPEN == true){ ?>
            <div id="content"  class="signup">
                
                <h1><img src="/img/x.gif" class="anmelden" alt="register for the game"></h1>
                <h5><img src="/img/x.gif" class="img_u05" alt="registration"/></h5>
                
                <p><?=BEFORE_REGISTER?></p>
                
                <form name="snd" method="post" action="<?=\App\Routes::REGISTER?>">
                    <input type="hidden" name="invited" value="<?=$invited?>" />
                    <input type="hidden" name="ft" value="a1" />
                    
                    <table cellpadding="1" cellspacing="1" id="sign_input">
                        <tbody>
                        <tr class="top">
                            <th><?=NICKNAME?></td>
                            <td>
                                <input class="text" type="text" name="name" value="<?=$form->getValue('name')?>" maxlength="30" />
                                <span class="error"><?=$form->getError('name')?></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?=EMAIL?></th>
                            <td>
                                <input class="text" type="text" name="email" value="<?=stripslashes($form->getValue('email'))?>" />
                                <span class="error"><?=$form->getError('email')?></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?=PASSWORD?></th>
                            <td>
                                <input class="text" type="password" name="pw" value="<?=stripslashes($form->getValue('pw'))?>" maxlength="100" />
                                <span class="error"><?=$form->getError('pw')?></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    
                    <table cellpadding="1" cellspacing="1" id="sign_select">
                        <tbody>
                        <tr class="top">
                            <th><img src="/img/x.gif" class="img_u06" alt="choose tribe"></th>
                            <th colspan="2"><img src="/img/x.gif" class="img_u07" alt="starting position"></th>
                        </tr>
                        
                        <tr>
                            <td class="nat">
                                <label>
                                    <input
                                        class="radio"
                                        type="radio"
                                        name="vid"
                                        value="<?=TribeSid::ROMANS?>"
                                        <?=$form->getRadio('vid', TribeSid::ROMANS)?>
                                    >&nbsp;<?=ROMANS?>
                                </label>
                            </td>
                            
                            <td class="pos1">
                                <label><input class="radio" type="radio" name="kid" value="0" checked>&nbsp;<?=RANDOM?></label>
                            </td>
                            
                            <td class="pos2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td>
                                <label>
                                    <input
                                        class="radio"
                                        type="radio"
                                        name="vid"
                                        value="<?=TribeSid::TEUTONS?>"
                                        <?=$form->getRadio('vid',TribeSid::TEUTONS)?>
                                    >&nbsp;<?=TEUTONS?>
                                </label>
                            </td>
                            <td>
                                <label><input class="radio" type="radio" name="kid" value="1" <?=$form->getRadio('kid',1)?>>&nbsp;<?=NW?> <b>(-|+)</b>&nbsp;</label>
                            </td>
                            <td>
                                <label><input class="radio" type="radio" name="kid" value="2" <?=$form->getRadio('kid',2)?>>&nbsp;<?=NE?> <b>(+|+)</b></label>
                            </td>
                        </tr>
                        
                        <tr class="btm">
                            <td>
                                <label>
                                    <input
                                        class="radio"
                                        type="radio"
                                        name="vid"
                                        value="<?=TribeSid::GAULS?>"
                                        <?=$form->getRadio('vid',TribeSid::GAULS)?>
                                    >&nbsp;<?=GAULS?>
                                </label>
                            </td>
                            <td>
                                <label>
                                    <input
                                        class="radio"
                                        type="radio"
                                        name="kid"
                                        value="3"
                                        <?=$form->getRadio('kid',3)?>
                                    >&nbsp;<?=SW?> <b>(-|-)</b>
                                </label>
                            </td>
                            <td><label><input class="radio" type="radio" name="kid" value="4" <?=$form->getRadio('kid',4)?>>&nbsp;<?=SE?> <b>(+|-)</b></label>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    
                    <ul class="important">
                        <?php
                        echo $form->getError('tribe');
                        echo $form->getError('agree');
                        ?>
                    </ul>
                    
                    <p>
                        <label>
                            <input class="check" type="checkbox" name="agb" value="1" <?=$form->getRadio('agb',1)?>/>
                            <?=ACCEPT_RULES?>
                        </label>
                    </p>
                    
                    <p class="btn">
                        <input type="image" value="anmelden" name="s1" id="btn_signup" class="dynamic_img" src="/img/x.gif" alt="register" />
                    </p>
                </form>
                
                <p class="info"><?= ONE_PER_SERVER?></p>
            </div>
            <?php } else { ?>
            <div id="content"  class="signup">
                
                <h1><img src="/img/x.gif" class="anmelden" alt="register for the game"></h1>
                <h5><img src="/img/x.gif" class="img_u05" alt="registration"/></h5>
                
                <p><?=REGISTER_CLOSED?></p>
            </div>
        <?php } ?>
        
        <div id="side_info" class="outgame">
            <?php include __DIR__ . '/../../Templates/news.php';?>
        </div>
        
        <div class="clear"></div>
    </div>
    
    <div class="footer-stopper outgame"></div>
    <div class="clear"></div>
    
    <?php include(__DIR__ . "/../../Templates/footer.tpl");?>
    
    <div id="ce"></div>

</body>
</html>