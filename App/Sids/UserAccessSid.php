<?php

namespace App\Sids;

class UserAccessSid
{
    /**
     *
     */
    public const BANNED = 0;
    
    /**
     *
     */
    public const AUTH = 1;
    
    /**
     * The very common access for regular users
     */
    public const USER = 2;
    
    /**
     * Generated user, controlled by script
     */
    public const AI_USER = 3;
    
    /**
     *
     */
    public const MODERATOR = 4;
    
    /**
     *
     */
    public const MULTIHUNTER = 8;
    
    /**
     *
     */
    public const ADMIN = 9;
}
