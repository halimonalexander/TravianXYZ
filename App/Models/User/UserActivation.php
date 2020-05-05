<?php

namespace App\Models\User;

use App\Models\AbstractModel;

class UserActivation extends AbstractModel
{
    public function getActivation(string $code)
    {
        return $this->db->query(
            "SELECT *
            FROM {$this->tablePrefix}activate
            WHERE act = '{$code}';"
        )->fetchRow();
    }
    
    public function getById(int $id)
    {
        return $this->db->query(
            "SELECT *
            FROM {$this->tablePrefix}activate
            WHERE id = {$id};"
        )->fetchRow();
    }
    
    public function emailExists(string $email): bool
    {
        return (bool) $this->db->query(
            "SELECT count(*)
            FROM {$this->tablePrefix}activate
            WHERE email = '{$email}';"
        )->fetchOne();
    }
    
    public function usernameExists(string $username): bool
    {
        return (bool) $this->db->query(
            "SELECT count(*)
            FROM {$this->tablePrefix}activate
            WHERE username = '{$username}';"
        )->fetchOne();
    }
    
    public function insert(
        string $username,
        string $password,
        string $email,
        int $tribe,
        int $locate,
        string $activationCode,
        string $verificationCode
    ) {
        $this->db->query(
            "INSERT INTO " . TB_PREFIX . "activate (username, password, access, email, tribe, timestamp, location, act, act2)
             VALUES ('$username', '$password', " . USER . ", '$email', $tribe, NOW(), $locate, '$activationCode', '$verificationCode');"
        );
        
        return $this->db->getPdo()->lastInsertId();
    }
    
    public function delete(string $username)
    {
        $this->db->query(
            "DELETE FROM {$this->tablePrefix}activate
            WHERE username = '{$username}';"
        );
    }
}
