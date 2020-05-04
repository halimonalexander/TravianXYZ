<?php

namespace App\Models\User;

use App\Models\AbstractModel;

class UserActivity extends AbstractModel
{
    public function setActive(string $username)
    {
        $time = time();
        try {
            $this->db->getPdo()->beginTransaction();
            
            $this->db->query("REPLACE INTO {$this->tablePrefix}active VALUES ('$username', $time)");
            $this->db->query(
                "UPDATE {$this->tablePrefix}users
                SET timestamp = {$time}
                WHERE username = '{$username}';"
            );
    
            $this->db->getPdo()->commit();
        } catch (\PDOException $exception) {
            $this->db->getPdo()->rollBack();
            
            return false;
        }

        return true;
    }
}
