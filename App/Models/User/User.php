<?php

namespace App\Models\User;

use App\Models\AbstractModel;

class User extends AbstractModel
{
    private $vocationMode = null;
    
    public function getVocationMode()
    {
        if ($this->vocationMode === null) {
             $this->vocationMode = new UserVocationMode();
        }
        
        return $this->vocationMode;
    }
    
    public function setSelectedVillage(int $userId, int $villageId)
    {
        $this->db->query(
            "UPDATE {$this->tablePrefix}users
             SET village_select = {$villageId}
             WHERE id = {$userId}"
        );
    }
    
    public function create($username, $password, $email, $tribe): ?int
    {
        $protectionTime = ($this->isServerActive() ? time() : strtotime(START_DATE . ' ' . START_TIME)) + PROTECTION;

        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO {$this->tablePrefix}users (username, password, access, email, timestamp, tribe, protect, lastupdate, regtime)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);"
        );
        $result = $stmt->execute([
            $username,
            $password,
            USER,
            $email,
            time(),
            $tribe,
            $protectionTime,
            time(),
            time(),
        ]);
        if (!$result) {
            throw new \RuntimeException('Unable to create new user');
        }

        return $this->db->getPdo()->lastInsertId();
    }
    
    public function removeBeginnerProtection(int $userId)
    {
        $this->db->query(
            "UPDATE ".TB_PREFIX."users
            SET protect = now()
            WHERE id = {$userId};"
        );
    }
}
