<?php
#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       cp.php                                                      ##
##  Developed by:  aggenkeech                                                  ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2012. All rights reserved.                ##
##                                                                             ##
#################################################################################
include_once("../../config.php");
if (!isset($_SESSION))
    session_start();
if ($_SESSION['access'] < 9)
    die("<h1><font color=\"red\">Access Denied: You are not Admin!</font></h1>");

$id = $_POST['id'];
$admid = $_POST['admid'];
$access = $_POST['access'];
$dur = $_POST['protect'] * 86400;
$protection = (time() + $dur);

$database->query("UPDATE ".TB_PREFIX."users SET
	access = ".$access.",
	gold = ".$_POST['gold'].",	
	sit1 = '".$_POST['sitter1']."',
	sit2 = '".$_POST['sitter2']."',
	protect = '".$protection."',
	cp = ".$_POST['cp'].",
	ap = '".$_POST['off']."', 
	dp = '".$_POST['def']."', 
	RR = '".$_POST['res']."', 
	apall = '".$_POST['ooff']."', 
	dpall = '".$_POST['odef']."' 
	WHERE id = ".$id."");

header("Location: ../../../Admin/admin.php?p=player&uid=".$id."");
