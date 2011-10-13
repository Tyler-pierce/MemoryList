<?php

/**
 *  A memcache handler for all the basic operations of memcached
 *  (add/remove/inc/dec/etc..)
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */
class MemoryListCi extends MemoryList
{
    function __construct($name = false, $timeSlice = TimeSlice::DAY)
    {
        $ci = &get_instance();

        $ci->config->load('memcache');
        
        $this->_prefix = $ci->config->item('memcache_prefix');
        
        $this->_defaultTiming = $ci->config->item('memcache_default_time');
        
        $this->set_memcache($ci->config->item('memcache_servers'));
    }
}