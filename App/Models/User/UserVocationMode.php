<?php

namespace App\Models\User;

use App\Models\AbstractModel;

class UserVocationMode extends AbstractModel
{
    public function remove($userId)
    {
        $this->db->query(
            "UPDATE {$this->tablePrefix}users
            SET vac_mode = '0',
                vac_time = '0'
            WHERE id = {$userId};"
        );
    }
}
