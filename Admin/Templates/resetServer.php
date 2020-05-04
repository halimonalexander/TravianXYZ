<?php
#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       resetServer.php                                             ##
##  Developed by:  Ronix                                                       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2012-2014. All rights reserved.                ##
##                                                                             ##
#################################################################################

include_once("../../GameEngine/config.php");
include("../../GameEngine/Database/db_MYSQLi.php");
include("../../GameEngine/Protection.php");

if (!isset($_SESSION))
    session_start();

if($_SESSION['access'] != ADMIN)
    die("<h1><font color=\"red\">Access Denied: You are not Admin!</font></h1>");

set_time_limit(0);
$database->query("TRUNCATE TABLE ".TB_PREFIX."a2b");
$database->query("TRUNCATE TABLE ".TB_PREFIX."abdata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."activate");
$database->query("TRUNCATE TABLE ".TB_PREFIX."active");
$database->query("TRUNCATE TABLE ".TB_PREFIX."admin_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."alidata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."ali_invite");
$database->query("TRUNCATE TABLE ".TB_PREFIX."ali_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."ali_permission");
$database->query("TRUNCATE TABLE ".TB_PREFIX."allimedal");
$database->query("TRUNCATE TABLE ".TB_PREFIX."artefacts");
$database->query("TRUNCATE TABLE ".TB_PREFIX."attacks");
$database->query("TRUNCATE TABLE ".TB_PREFIX."banlist");
$database->query("TRUNCATE TABLE ".TB_PREFIX."bdata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."build_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."chat");
$database->query("TRUNCATE TABLE ".TB_PREFIX."config");
$database->query("TRUNCATE TABLE ".TB_PREFIX."deleting");
$database->query("TRUNCATE TABLE ".TB_PREFIX."demolition");
$database->query("TRUNCATE TABLE ".TB_PREFIX."diplomacy");
$database->query("TRUNCATE TABLE ".TB_PREFIX."enforcement");
$database->query("TRUNCATE TABLE ".TB_PREFIX."farmlist");
$database->query("TRUNCATE TABLE ".TB_PREFIX."fdata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."forum_cat");
$database->query("TRUNCATE TABLE ".TB_PREFIX."forum_edit");
$database->query("TRUNCATE TABLE ".TB_PREFIX."forum_post");
$database->query("TRUNCATE TABLE ".TB_PREFIX."forum_survey");
$database->query("TRUNCATE TABLE ".TB_PREFIX."forum_topic");
$database->query("TRUNCATE TABLE ".TB_PREFIX."general");
$database->query("TRUNCATE TABLE ".TB_PREFIX."gold_fin_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."hero");
$database->query("TRUNCATE TABLE ".TB_PREFIX."illegal_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."links");
$database->query("TRUNCATE TABLE ".TB_PREFIX."login_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."market");
$database->query("TRUNCATE TABLE ".TB_PREFIX."market_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."mdata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."medal");
$database->query("TRUNCATE TABLE ".TB_PREFIX."movement");
$database->query("TRUNCATE TABLE ".TB_PREFIX."ndata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."online");
$database->query("TRUNCATE TABLE ".TB_PREFIX."password");
$database->query("TRUNCATE TABLE ".TB_PREFIX."prisoners");
$database->query("TRUNCATE TABLE ".TB_PREFIX."raidlist");
$database->query("TRUNCATE TABLE ".TB_PREFIX."research");
$database->query("TRUNCATE TABLE ".TB_PREFIX."route");
$database->query("TRUNCATE TABLE ".TB_PREFIX."send");
$database->query("TRUNCATE TABLE ".TB_PREFIX."tdata");
$database->query("TRUNCATE TABLE ".TB_PREFIX."tech_log");
$database->query("TRUNCATE TABLE ".TB_PREFIX."training");
$database->query("TRUNCATE TABLE ".TB_PREFIX."units");
$time=time();
$database->query("TRUNCATE TABLE ".TB_PREFIX."odata");

$database->populateOasisdata();
$database->populateOasis();
$database->populateOasisUnits2();
$uid=$database->getVillageID(5);

$passw=md5('123456');
$database->query("TRUNCATE TABLE ".TB_PREFIX."users");
$database->query("INSERT INTO ".TB_PREFIX."users (id, username, password, email, tribe, access, gold, gender, birthday, location, desc1, desc2, plus, b1, b2, b3, b4, sit1, sit2, alliance, sessid, act, timestamp, ap, apall, dp, dpall, protect, quest, gpack, cp, lastupdate, RR, Rc, ok) VALUES
(5, 'Multihunter', '".$passw."', 'multihunter@travianx.mail', 0, 9, 0, 0, '0000-00-00', '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 0, 'gpack/travian_default/', 1, 0, 0, 0, 0),
(1, 'Support', '', 'support@travianx.mail', 0, 8, 0, 0, '0000-00-00', '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 0, 'gpack/travian_default/', 1, 0, 0, 0, 0),
(2, 'Nature', '', 'support@travianx.mail', 4, 8, 0, 0, '0000-00-00', '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 0, 'gpack/travian_default/', 1, 0, 0, 0, 0),
(4, 'Taskmaster', '', 'support@travianx.mail', 0, 8, 0, 0, '0000-00-00', '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 0, 'gpack/travian_default/', 1, 0, 0, 0, 0)");

$database->query("INSERT INTO ".TB_PREFIX."units (vref) VALUES ($uid)");
$database->query("INSERT INTO ".TB_PREFIX."tdata (vref) VALUES ($uid)");

$database->query("INSERT INTO ".TB_PREFIX."fdata (vref, f1t, f2t, f3t, f4t, f5t, f6t, f7t, f8t, f9t, f10t, f11t, f12t, f13t, f14t, f15t, f16t, f17t, f18t, f26, f26t, wwname) VALUES ($uid, '1', '4', '1', '3', '2',  '2', '3', '4', '4', '3', '3', '4', '4', '1', '4', '2', '1', '2', '1', '15', 'World Wonder')");

$database->query("DELETE FROM ".TB_PREFIX."vdata WHERE owner<>5");
$database->query("UPDATE ".TB_PREFIX."wdata SET occupied=0 WHERE id<>$uid");
$database->query("TRUNCATE TABLE ".TB_PREFIX."ww_attacks");

header("Location: ../admin.php?p=resetdone");
?> 
