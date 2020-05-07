<?php

namespace App\Helpers;

class GlobalVariablesHelper
{
    public static function getBuilding(int $buildingId): array
    {
        $name = "bid{$buildingId}";
        
        global $$name;
        
        return $$name;
    }
    
    public static function getHeroStats(int $unitId): array
    {
        $name = "h{$unitId}";
        
        global $$name;
        
        return $$name;
    }
    
    public static function getHeroResources(int $unitId): array
    {
        $name = "h{$unitId}_full";
        
        global $$name;
        
        return $$name;
    }
    
    public static function getHeroLevels(): array
    {
        global $hero_levels;
        
        return $hero_levels;
    }
    
    public static function getUnit(int $unitId): array
    {
        $name = "u{$unitId}";
    
        global $$name;
        
        return $$name;
    }
}
