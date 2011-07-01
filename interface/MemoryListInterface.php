<?php

/**
 *  Public interface of Memory List
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */

interface MemoryListInterface
{
    /// Public Interface
    public function setName($name);

    public function insert($key, $value = 1);

    public function query($upperBound = false);

    public function aggregateData($data, $topIndex = false, $timeSlice = false, $maxLevel = false);
}

