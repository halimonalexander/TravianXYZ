<?php

namespace App\Models\User;

use App\Models\AbstractModel;

class User extends AbstractModel
{
    public function setSelectedVillage(int $userId, int $villageId)
    {
        $this->db->query(
            "UPDATE " . $this->tablePrefix . "users
             SET village_select=" . $villageId . "
             WHERE id=" . $userId
        );
    }
}
