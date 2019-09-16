<?php

namespace PHPGuus\FlysystemRaid\Exceptions;

class IncorrectNumberOfFileSystems extends \Exception
{
    public function __construct(int $count, int $requiredCount)
    {
        parent::__construct('Incorrect number of file systems provided for '.
            'the given RAID level: '.$count.' file systems provided, '.
            'however at least '.$requiredCount.' file systems are '.
            'expected.');
    }
}
