<?php

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       gold.php                                                    ##
##  Developed by:  Dzoki                                                       ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################
// include_once("../../Account.php");
// todo https://github.com/halimonalexander/TravianXYZ/issues/1

if (!isset($_SESSION))
    session_start();
if ($_SESSION['access'] < ADMIN)
    die("Access Denied: You are not Admin!");

$id = $_POST['id'];
$gold = $_POST['gold'];

$database->query("UPDATE ".TB_PREFIX."users SET gold = gold + ".$_POST['gold']." WHERE id != '0'");
$database->query("Insert into ".TB_PREFIX."admin_log values (0,$id,'Added <b>$gold</b> gold to all users',".time().")");

header("Location: ../../../Admin/admin.php?p=gold&g");
