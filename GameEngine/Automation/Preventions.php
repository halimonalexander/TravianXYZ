<?php

namespace GameEngine\Automation;

class Preventions
{
    private const PREVENTION_TIMEOUT = 50;
    
    public function can(string $prevention)
    {
        $filename = $this->getFilename($prevention);
        
        return !file_exists($filename) || time() - filemtime($filename) > self::PREVENTION_TIMEOUT;
    }
    
    public function delete(string $prevention)
    {
        $filename = $this->getFilename($prevention);
    
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    
    public function add(string $prevention)
    {
        $filename = $this->getFilename($prevention);
    
        $ourFileHandle = fopen($filename, 'w');
        fclose($ourFileHandle);
    }
    
    private function getFilename(string $prevention)
    {
        return __DIR__ . "/Prevention/{$prevention}.txt";
    }
}
