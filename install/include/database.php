<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////
//                                             TRAVIANX                                             //
//            Only for advanced users, do not edit if you dont know what are you doing!             //
//                                Made by: Dzoki & Dixie (TravianX)                                 //
//                              - TravianX = Travian Clone Project -                                //
//                                 DO NOT REMOVE COPYRIGHT NOTICE!                                  //
//////////////////////////////////////////////////////////////////////////////////////////////////////

include("constant.php");

class MYSQLi_DB
{
    /** @var false|\mysqli  */
    public $connection;
    
    public function __construct()
    {
        $this->connection = mysqli_connect(SQL_SERVER, SQL_USER, SQL_PASS, SQL_DB);
    }
    
    public function query($query)
    {
        return $this->connection->query($query);
    }
}

$database = new MYSQLi_DB();
