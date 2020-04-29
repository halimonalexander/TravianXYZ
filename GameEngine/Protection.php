<?php
#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       Protection.php                                              ##
##  Developed by:  SlimShady                                                   ##
##  Edited by:     Dzoki & Dixie                                               ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################

function protectIncomingData(array &$data): void
{
    global $database;
    $connection = $database->connection;
    
    $data = array_map(function ($item) use ($connection) {
        return $connection->real_escape_string($item);
    }, $data);
    
    $data = array_map('htmlspecialchars', $data);
}

// has npc exception because they work with special $_post
if (!isset($_POST['ft'])){
    protectIncomingData($_POST);
}

$rsargs = $_GET['rsargs'];
protectIncomingData($_GET);
$_GET['rsargs'] = $rsargs;

protectIncomingData($_COOKIE);
