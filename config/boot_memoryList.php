<?php

/// INTERFACE

require_once 'interface/MemcacheDataInterface.php';

require_once 'interface/MemoryListInterface.php';


/// LIBS

/* Configuration driver */
require_once 'lib/Config.php';

/* Enum style structure for memory list to handle time periods */
require_once 'lib/TimeSlice.php';

/* Basic memcache library interfaces with php insert/increment/delete methods */
require_once 'lib/MemcacheData.php';

/* MemoryList memory based data structure */
require_once 'lib/MemoryList.php';

