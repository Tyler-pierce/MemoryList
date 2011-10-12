<?php

/**
 *
 * MEMCACHE HANDLER
 * 
 * This class handles our basic memcaching operations to encapsulate standard
 * failover and tests to ensure memcache was started/found successfully
 * 
 *
 * @author T Pierce <tyler.pierce@gmail.com>
 */

class MemcacheData implements MemcacheDataInterface
{

    /**
     * Sets to the current memcache connection object.
     */
    private $_memConn = false;

    /**
     * Default timing to use for storage, sets to 5 minutes by default
     * but should usually be determined by the process
     */
    private $_defaultTiming = 300;

    /**
     * Sets to an instance of config class with environment settings
     */
    private $_config = null;

    /**
     * Sets to a prefix to use when making application transactions.  So you could use
     * the game name or subsection of the game, to prefix each set and retrieval
     */
    private $_prefix = '';

    /**
     * Constructor
     * 
     * Must have settings class available.
     */
    function __construct()
    {
        $this->_config = Config::get_instance();
        
        $this->_prefix = $this->_config->get('memcache_prefix');
        
        $this->_defaultTiming = $this->_config->get('memcache_default_time');
        
        $this->set_memcache($this->_config->get('memcache_servers'));
    }

    /** 
     * Return the memcache object (which contains all memcache server connections)
     * 
     * @return memcache object or false if not set
     */
    function get_conn()
    {
        return $this->_memConn;
    }

    /**
     * Instantiate memcache object using all available configured servers passed to method.
     * Returns whether successfully connected or not, and also ensures that if we did
     * not connect, _memConn is set to false.
     * 
     * @param memcache_servers
     * @return true on connected or false on not connected
     */
    private function set_memcache($memcache_servers)
    {
        try
        {
            // check to see if static method is already set
            if ($this->_memConn)
            {
                return true;
            }
            
            $this->_memConn = new Memcache();
            
            $connected = false;
            
            // cycle through set servers and add them as found
            foreach ($memcache_servers as $memcache_server)
            {
                // persistant connection using [0] server and [1] port
                if ($this->_memConn->pconnect($memcache_server[0], $memcache_server[1]))
                {
                    // connected to at least one server!
                    $connected = true;
                }
            }
            
            // no connection found, set var for failovers on any attempt to memcache for the future
            if (! $connected)
            {
                $this->_memConn = false;
            }
            else
            {
                return true;
            }
        }
        catch (exception $e)
        {
            $this->_memConn = false;
        }
        
        return false;
    }

    /**
     * Update memcache with a key/value pair, using class default timing if none was passed.
     * Acts as an insert/replace
     * 
     * @param key
     * @param value
     * @param memcacheTimeout
     * @return true on success false on failure to add
     */
    public function update($key, $value, $memcacheTimeout = false)
    {
        $key = $this->_prefix . "_{$key}";
        
        if (! $memcacheTimeout)
        {
            $memcacheTimeout = $this->_defaultTiming;
        }
        
        $result = false;
        
        // Do we have a server successfuly set up
        if ($this->_memConn)
        {
            // Safe replace OR set
            $result = $this->_memConn->replace($key, $value, false, $memcacheTimeout);
            
            if (false == $result)
            {
                $result = $this->_memConn->set($key, $value, false, $memcacheTimeout);
            }
        }
        
        return $value;
    }

    /**
     * Insert memcache value, not doing an update on data that is known to be new is quite
     * a lot faster.
     * 
     * @param key
     * @param value
     * @param memcacheTimeout
     * @return true on success false on failure to add
     */
    public function onlyInsert($key, $value, $memcacheTimeout = false)
    {
        $key = $this->_prefix . "_{$key}";
        
        if (! $memcacheTimeout)
        {
            $memcacheTimeout = $this->_defaultTiming;
        }
        
        $result = false;
        
        // Do we have a server successfuly set up
        if ($this->_memConn)
        {
            $result = $this->_memConn->set($key, $value, false, $memcacheTimeout);
        }
        
        return $value;
    }

    /**
     * Increment a memcache value by amount. Recommended to use strong equality when testing
     * return value (===) as it could be incremented to 0.
     * 
     * @param string key
     * @param int amount
     * @return new items value on success or false on failure
     */
    public function increment($key, $amount = 1)
    {
        $key = $this->_prefix . "_{$key}";

        if ($this->_memConn)
        {
            return $this->_memConn->increment($key, $amount);
        }
        
        return false;
    }

    /**
     * Increment a memcache value by amount. Recommended to use strong equality when testing
     * return value (===) as it could be incremented to 0.
     * 
     * @param string key
     * @param int amount
     * @return new items value on success or false on failure
     */
    public function decrement($key, $amount = 1)
    {
        $key = $this->_prefix . "_{$key}";

        if ($this->_memConn)
        {
            
            return $this->_memConn->decrement($key, $amount);
        }
        
        return false;
    }

    /**
     *  Gather data from memory based on the known and passed name it holds in cache
     *  
     *  @param name
     *  @return mixed data from memcache
     */
    public function retrieve($key)
    {
        $key = $this->_prefix . "_{$key}";

        if ($this->_memConn)
        {    
            $result = $this->_memConn->get($key);

            return $result;
        }

        return false;
    }

    /**
     * Remove an entry completly from memcache.
     * 
     * @param key
     * @return result of operation
     */
    public function remove($key)
    {
        
        $key = $this->_prefix . "_{$key}";
        
        if ($this->_memConn)
        {
            
            $result = $this->_memConn->delete($key);
            
            return $result;
        }
        
        return false;
    }

    /**
     * Flush all memcache values
     * 
     * DANGER removes all memcache values from set servers.
     * @return result of flush
     */
    public function flush_all()
    {
        return $this->_memConn->flush();
    }
}

