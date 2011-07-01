<?php

/**
 *  A settings singleton class to keep safe track of various settings.
 *  
 *  @author T Pierce <tyler@metroleap.com>
 */

class Config 
{
    /**
     *  Sets to an instance of this class
     */
    private static $_instance; 

    /**
     *  More general settings
     */
    private $_settings = array();


    /**
     *  Construct
     *  
     *  @param bbuser
     *  @param vboptions
     */
    private function __construct ()
    {
        $this->set_settings();
    }

    /**
     *  Retrieve the static instance of this class.
     *  
     *  @return VBSettings object
     */
    public static function get_instance ($_REQUEST = array())
    {
        if (!self::$_instance)
        {
            self::$_instance = new Config($_REQUEST);
        }

        return self::$_instance;
    }


    /// PUBLIC METHODS

    /// GETS / IS
    
    public function get ($setting) { return $this->_settings[$setting]; }

   

    /// PRIVATE METHODS

    /**
     *  Set the options up so they can be retrieved easily with our Gets.
     *  
     *  @param bbuser
     *  @param vboptions
     */
    private function set_settings ()
    {
        include 'config/memcache.php';

        $this->_settings = compact(
            'memcache_servers', 
            'memcache_prefix', 
            'memcache_default_time'
        );
    }
}
