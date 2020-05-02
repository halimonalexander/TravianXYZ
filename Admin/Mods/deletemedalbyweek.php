<?php

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       medals.php                                                  ##
##  Developed by:  aggenkeech                                                  ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
#################################################################################

include_once("../../Account.php"); // todo https://github.com/halimonalexander/TravianXYZ/issues/1

if (!isset($_SESSION))
    session_start();
if ($_SESSION['access'] < ADMIN)
    die("Access Denied: You are not Admin!");

$deleteweek = $_POST['medalweek'];
$database->query("DELETE FROM ".TB_PREFIX."medal WHERE week = ".$deleteweek."");

header("Location: ../../../Admin/admin.php?p=delmedal");
