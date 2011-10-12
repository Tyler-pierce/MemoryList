<?php

require_once APPPATH . "/libraries/couch.php";
require_once APPPATH . "/libraries/couchClient.php";
require_once APPPATH . "/libraries/couchDocument.php";
require_once APPPATH . "/libraries/couchReplicator.php";

class MemoryListCi extends MemoryList {

    function __construct($name = false, $timeSlice = TimeSlice::DAY)
    {
        $ci = &get_instance();

        $ci->config->load('memcache');
        
        $this->_prefix = $ci->config->item('memcache_prefix');
        
        $this->_defaultTiming = $ci->config->item('memcache_default_time');
        
        $this->set_memcache($ci->config->item('memcache_servers'));
    }
}