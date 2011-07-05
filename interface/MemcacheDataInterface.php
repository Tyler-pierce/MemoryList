<?php

/**
 *	Public interface of a standard Module.
 *	
 *	@author T Pierce <tyler.pierce@gmail.com>
 */

interface MemcacheDataInterface
{
    /// Public Interface
    public function update ($key, $value, $memcacheTimeout = -1);

    public function onlyInsert($key, $value, $memcacheTimeout = false);

    public function increment ($key, $amount = 1);

    public function decrement ($key, $amount = 1);

    public function retrieve ($key);

    public function remove ($key);

    public function flush_all ();
}

