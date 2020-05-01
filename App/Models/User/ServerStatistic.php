<?php

namespace App\Models\User;

use App\Models\AbstractModel;

class ServerStatistic extends AbstractModel
{
    public function getOnlinePlayers(): int
    {
        return $this->db->query(
            "SELECT count(*)
            FROM {$this->tablePrefix}users
            WHERE now() - timestamp < 600
              AND tribe BETWEEN 1 AND 3;"
        )->fetchOne() ?? 0;
    }
    
    public function getTopPlayerUsername(): ?string
    {
        return $this->db->query(
            "SELECT username
            FROM {$this->tablePrefix}users
            WHERE access < 8
              AND id > 5
              AND tribe BETWEEN 1 AND 3
            ORDER BY oldrank ASC
            Limit 1;"
        )->fetchOne();
    }
}
