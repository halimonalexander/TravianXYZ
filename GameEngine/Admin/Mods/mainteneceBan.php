<?php
#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       mainteneceBan.php                                           ##
##  Developed by:  aggenkeech                                                  ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2012. All rights reserved.                ##
##                                                                             ##
#################################################################################
if (!isset($_SESSION)) session_start();
if($_SESSION['access'] < 9) die("Access Denied: You are not Admin!");
include_once("../../config.php");

$session = $_POST['admid'];

$sql = $database->query("SELECT * FROM ".TB_PREFIX."users WHERE id = ".$session."");
$access = $database->fetchArray($sql);
$sessionaccess = $access['access'];

if($sessionaccess != 9) die("<h1><font color=\"red\">Access Denied: You are not Admin!</font></h1>");

$users = $database->numRows($database->query("SELECT * FROM ".TB_PREFIX."users"));

$duration = $_POST['duration'] * 3600;
$start = $_POST['start'];
$startts = strtotime($start);
$endts = $startts + $duration;
$reason = $_POST['reason'];
$admin = $session;
$active = '1';
$access = '2';

$sql = "SELECT id FROM ".TB_PREFIX."users ORDER BY ID DESC LIMIT 1";
$loops = $database->fetchOne($database->query($sql));

for($i = 0; $i < $loops + 1; $i++)
{
	$query = "SELECT * FROM ".TB_PREFIX."users WHERE id = ".$i." AND access = ".$access."";
	$result = $database->query($query);
	while($row = $database->fetchAssoc($result))
	{
		$database->query("INSERT INTO ".TB_PREFIX."banlist ".$row['id'].", ".$row['username'].", ".$reason.", ".$startts.", ".$endts.", ".$admin.", ".$active."");
		##$database->query("INSERT INTO ".TB_PREFIX."banlist (`uid`, `name`, `reason`, `time`, `end`, `admin`, `active`) VALUES (".$row['id'].", '".$row['username']."' , '$reason', '$startts', '$endts', '$admin', '1')");
	}
}

header("Location: ../../../Admin/admin.php?p=ban");
