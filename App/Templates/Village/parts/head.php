<?php
$title = SERVER_NAME;
$gpLocation = GP_LOCATE;
$customGpLocation = ($this->session->gpack == null || GP_ENABLE == false) ? GP_LOCATE : $this->session->gpack;
$assetsVersionHash = '0faaa';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?=$title?></title>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <meta name="content-language" content="en" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script src="/mt-full.js?<?=$assetsVersionHash?>" type="text/javascript"></script>
    <script src="/unx.js?<?=$assetsVersionHash?>" type="text/javascript"></script>
    <script src="/new.js?<?=$assetsVersionHash?>" type="text/javascript"></script>
    <link href="<?=$gpLocation?>lang/en/compact.css?<?=$assetsVersionHash?>" rel="stylesheet" type="text/css" />
    <link href="<?=$gpLocation?>lang/en/lang.css?<?=$assetsVersionHash?>" rel="stylesheet" type="text/css" />
    <link href='<?=$customGpLocation?>travian.css?<?=$assetsVersionHash?>' rel='stylesheet' type='text/css' />
	  <link href='<?=$customGpLocation?>lang/en/lang.css?<?=$assetsVersionHash?>' rel='stylesheet' type='text/css' />
    <script type="text/javascript">
        window.addEvent('domready', start);
    </script>
</head>
