<?php
#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Edited by:     akshay9, ZZJHONS, songeriux                                 ##
##  Filename       Database.php                                                ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################

echo "Attemp to access deprecated file<br>";
echo "<pre style='text-align: left'>";
print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

include_once("config.php");
include("Database/MysqliModel.php");
include("Protection.php");
