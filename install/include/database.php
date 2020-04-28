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
    public $connection;
    
    public function __construct()
    {
        $this->connection = mysqli_connect(SQL_SERVER, SQL_USER, SQL_PASS, SQL_DB);
    }
    
    public function query($query)
    {
        return mysqli_query($this->connection, $query);
    }
}

class MYSQL_DB
{
    public $connection;
    public function __construct()
    {
        $this->connection = mysql_connect(SQL_SERVER, SQL_USER, SQL_PASS) or die(mysql_error());
        mysql_select_db(SQL_DB, $this->connection) or die(mysql_error());
    }
    
    public function mysql_exec_batch ($p_query, $p_transaction_safe = true)
    {
        if ($p_transaction_safe) {
            $p_query = 'START TRANSACTION;' . $p_query . '; COMMIT;';
        };
        
        $query_split = preg_split("/[;]+/", $p_query);
        foreach ($query_split as $command_line) {
            $command_line = trim($command_line);
            if ($command_line != '') {
                $query_result = mysql_query($command_line);
                if ($query_result == 0) {
                    break;
                };
            };
        };
    
        return $query_result;
    }
    
    public function query($query)
    {
        return mysql_query($query, $this->connection);
    }
}

$database = DB_TYPE ? new MYSQLi_DB() : new MYSQL_DB();
