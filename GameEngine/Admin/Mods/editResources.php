<?php
#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       editResources.php                                           ##
##  Developed by:  aggenkeech                                                  ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2012. All rights reserved.                ##
##                                                                             ##
#################################################################################
if (!isset($_SESSION)) session_start();
if($_SESSION['access'] < 9) die("Access Denied: You are not Admin!");
include_once("../../config.php");

$session = $_POST['admid'];
$id = $_POST['did'];

$sql = $database->query("SELECT * FROM ".TB_PREFIX."users WHERE id = ".$session."");
$access = $database->fetchArray($sql);
$sessionaccess = $access['access'];

if($sessionaccess != 9) die("<h1><font color=\"red\">Access Denied: You are not Admin!</font></h1>");

$database->query("UPDATE ".TB_PREFIX."vdata SET
	wood  = '".$_POST['wood']."', 
	clay  = '".$_POST['clay']."', 
	iron  = '".$_POST['iron']."', 
	crop  = '".$_POST['crop']."', 
	maxstore  = '".$_POST['maxstore']."', 
	maxcrop   = '".$_POST['maxcrop']."' 
	WHERE wref = '".$id."'");

header("Location: ../../../Admin/admin.php?p=village&did=".$id."");
