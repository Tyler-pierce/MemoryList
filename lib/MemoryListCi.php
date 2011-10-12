<?php

/// INTERFACE
require_once APPPATH . '/libraries/memcache/interface/MemcacheDataInterface.php';
require_once APPPATH . '/libraries/memcache/interface/MemoryListInterface.php';


/// LIBS
require_once APPPATH . '/libraries/memcache/includes/TimeSlice.php';
require_once APPPATH . '/libraries/memcache/includes/MemcacheData.php';
require_once APPPATH . '/libraries/memcache/includes/MemcacheDataCi.php';
require_once APPPATH . '/libraries/memcache/includes/MemoryList.php';

/**
 *  Code Igniter driver for Memory List.  File structure should match above includes
 *  and config put in CI as per their direction for configs.  MemoryListCi itself could go
 *  anywhere (eg. libraries/memcache/MemoryListCi.php)
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */
class MemoryListCi extends MemoryList {

    function __construct($name = false, $timeSlice = TimeSlice::DAY)
    {
        $this->_memcache = new MemcacheDataCi();

        $this->_time = new TimeSlice($timeSlice);

        $this->setName($name);
    }
}