<?php

/**
 *  Builds, maintains and queries a datastructure that perhaps closest resembles a singly linked list.
 *  
 *  - supports aggregation of similar data entries
 *  - the 'head' of the structure is the latest entry, stack style
 *  - supports lengthier time slice based queries.  Set time slice by a time function and that slice will be queryable.
 *  - aggregation will occur within the timeslice given only (ie each timeslice can contain aggregations of data but aggregations will not
 *    cross into over other timeslices).
 *  
 *  Keys refer to the key of the element, the memcache key is taken care of by the class.
 *  - The memcache key is constructed thus environmentPrefix[from MemcacheData]_$name_$timeslice_$eventNumber
 *  - dataname and timeslice are both provided at construction and are requirements.  Eventnumber is handled by MemoryList automatically. 
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */

class MemoryList implements MemoryListInterface
{
    /**
     *  Sets to an instance of our memcache data handler during object construction.
     */
    private $_memcache = null;

    /**
     *  Set to true if limited functionality, ie user did not provide enough data to
     *  run some queries or automated functionality.
     */
    private $_limitedFunctionality = false;

    /**
     *  Sets to the elements of the structure as queried.
     */
    private $_elements = array();

    /**
     *  Sets to the basic name of this instance of the data structure, and the full memory
     *  entry name of the structure which is a function of the name and the time slice.
     *  Temp name can be used to store the name while a temporary switch occurs during multi-queries.
     */
    private $_name     = '';
    private $_memName  = '';
    private $_tempName = '';

    /**
     *  The integer representation of the slice in time we are concerned with and the lib
     *  for dealing with it.
     */
    private $_timeSlice = '';
    private $_time      = null;

    /**
     *  Should not change this array via code in this class except for 'value'.  It contains a list of the allowable
     *  query modifier chaining methods and their default values.
     *  All chained methods are applicable to added queries using multi.
     *  'value' will be set upon data sanitization accordingly to 'filter'
     *  @see __call
     */
    private $_queryMod = array(
        /* limit query to x results */
        'limit'       => array('filter' => 'int',      'default' => false),
        /* offset of query to pull (note that query still has to run from beginning) */
        'offset'      => array('filter' => 'int',      'default' => false),
        /* reverse result of each separate query */
        'reverse'     => array('filter' => 'boolean',  'default' => false),
        /* aggregate entire structure before querying  */
        'aggregate'   => array('filter' => 'mixed',    'default' => false),
        /* takes array of MemoryList names, will run same query type on all accumulated queries  */
        'multi'       => array('filter' => 'array',    'default' => array()),
        /* 'remember' where query began */
        'setWaypoint' => array('filter' => 'boolean',  'default' => false),
        /* sort the mixed queries by date (does nothing if only one query run) */
        'sort'        => array('filter' => 'boolean',  'default' => true),
        /* use a waypoint if one exists and (1 query) query only up to that point (2 aggregation) aggregate from that point onward */
        'useWaypoint' => array('filter' => 'boolean',  'default' => false),
        /* get extra data pertaining to query result NOTE needs to be implemented still */
        'debug'       => array('filter' => 'boolean',  'default' => false),
    );


    /**
     *  Constructor
     *  
     *  Sets an instance of memcache and timeslice.
     *  
     *  @param int timeSlice
     */
    function __construct($name = false, $timeSlice = TimeSlice::DAY)
    {
        $this->_memcache = new MemcacheData();

        $this->_time = new TimeSlice($timeSlice);

        $this->setName($name);
    }

    /**
     *  Set the name of the memorylist being queried/manipulated.
     *  
     *  @param string name
     *  @return instance of MemoryList
     */
    public function setName ($name)
    {
        if (is_string($name))
        {
            $this->_name      = $name;
            $this->_timeSlice = $this->_time->getInt('CURRENT');
            
            $this->_memName = $this->_name . '_' . $this->_timeSlice;

            // expirey for entries is either the memcache max (1 month) or double the slice type,
            // so if slice is 1 hour it stores for 2 hours, giving time for entire slice to be processed
            // later if need be.
            $this->_expirey  =  min(
                                    $this->_time->getSliceInSeconds(TimeSlice::MONTH),
                                    ($this->_time->getSliceInSeconds() * 2)
                                );
        }
        else
        {
            $this->_limitedFunctionality = true;
        }

        return $this;
    }

    /**
     *  Overloaded method, any method called on MemoryList that isn't defined runs this. For allowed calls,
     *  @see private $_queryMod
     *  @param string name
     *  @param array params
     */
    public function __call ($name, $params)
    {
        if (isset($this->_queryMod[$name]))
        {
            // only accepting a single param ever, if wanting multiple will implemented as 1 mixed/array param
            $param = reset($params);

            switch ($this->_queryMod[$name]['filter'])
            {
                case 'int' :
                    $param = (int) $param;
                    $this->_queryMod[$name]['value'] = $param;
                    break;

                case 'boolean' :
                    if (is_bool($param))
                    {
                        $this->_queryMod[$name]['value'] = $param;
                    }
                    break;

                case 'string' :
                    if (is_string($param))
                    {
                        $this->_queryMod[$name]['value'] = $param;
                    }
                    break;

                case 'array' :
                    if (is_array($param))
                    {
                        $this->_queryMod[$name]['value'] = $param;
                    }
                    break;
                
                case 'mixed' :
                    $this->_queryMod[$name]['value'] = $param;
                    break;
            }
        }

        return $this;
    }

    /**
     *  Attempts to insert a new entry into the structures stack.
     *
     *  @param string key
     *  @param mixed data
     *  @param int amt
     *  @return new item count on success or false on failure
     */
    public function insert($key, $value = 1)
    {
        // the structure is specific, the data is organized by a type (KEY) and it's value (DATA)
        $data        = array();
        $data['KEY'] = $key;
        $data['VAL'] = $value;
        $data['TIM'] = time();

        // Increment 'head' counter and retrieve index of next insertion entry
        $newTopIndex = $this->_memcache->increment($this->_memName, $data['VAL']);

        if (!$newTopIndex)
        {
            $updateResult = $this->_memcache->onlyInsert($this->_memName, $data['VAL'], $this->_expirey);

            if ($updateResult)
            {
                $newTopIndex = $data['VAL'];
            }
            else
            {
                return false;
            }
        }

        if ($this->_memcache->onlyInsert($this->_memName . '_' . $newTopIndex, $data))
        {
            // Success!
            return $newTopIndex;
        }

        return false;
    }

    /**
     *  Query results of this time slice.  Chain methods as per _queryMod array in variable defs.
     *  
     *  Upper Bound possible to pass such that a waypoint can be used for aggregation
     *  
     *  @param int upperBound
     *  @return array of query results keyed sequentially
     */
    public function query($upperBound = false)
    {
        $resultPre = $this->_preQueryWork();

        list($returnArray, $upperBound) = $this->_queryWork($upperBound);

        // if resultPre is an array it returned query data and must be merged
        if (is_array($resultPre))
        {
            $returnArray = array_merge($returnArray, $resultPre);
        }

        $returnArray = $this->_postQueryWork($returnArray, $upperBound);
        
        return $returnArray;
    }

    /**
     *  Aggregate the current timeslice, or the given timeslice.
     *  
     *  Use waypoint for max safety (on off chance someone manages to increment, then this work is done,
     *  and then the data is pulled, missing the first entry (or more possibly)).
     *  
     *  @param array data
     *  @param int topIndex
     *  @param int timeSlice
     *  @param int maxLevel
     *  @return the amount of entries that were aggregated.
     */
    public function aggregateData($data, $topIndex = false, $timeSlice = false, $maxLevel = false)
    {
        $timeSlice = ($timeSlice ? $timeSlice : $this->_timeSlice);
    
        $memName = $this->_name . '_' . $this->_timeSlice;
    
        $i = $j = $topIndex = ($topIndex ? $topIndex : $this->_memcache->retrieve($memName));

        $maxLevel = ($maxLevel ? (int) $maxLevel : count($data));

        $totalAggregated = 0;

        $newData       = array();
        $completedData = array();


        foreach ($data as $element)
        {
            // if we reached the maximum level we want to aggregate to on an item, am processing
            // current queue before continuing.
            if (isset($newData[$element['KEY']]) && $newData[$element['KEY']]['level'] > $maxLevel)
            {
                $j = $this->storeAggregates($newData, $completedData, $j);
            }

            if (!isset($newData[$element['KEY']]))
            {
                $newData[$element['KEY']] = $element;
                $newData[$element['KEY']]['level'] = 1;
            }
            else
            {
                $newData[$element['KEY']]['VAL'] += $element['VAL'];

                $newData[$element['KEY']]['level'] += 1;

                // we didnt change time for now, using the first entry (latest time)

                // clean up the compacted entry
                $this->_memcache->remove($memName . '_' . $i);

                ++$totalAggregated;
            }

            $i -= $element['VAL'];
        }

        // process remaining data
        $this->storeAggregates($newData, $completedData, $j);

        // return as the data array would be if queried
        return $completedData;
    }

    /**
     *  Do the memory inserts for aggragated entries from memory.
     *  
     *  @param Array &data
     *  @param int currentIndex
     *  @return index of remaining entries to process if any
     */
    private function storeAggregates (Array &$data, &$completedData, $currentIndex)
    {
        foreach ($data as $dataKey => $element)
        {
            unset($data[$dataKey]);

            unset($element['level']); // this was meant to be temporary data

            $this->_memcache->update($this->_memName . '_' . $currentIndex, $element, $this->_expirey);

            $completedData[] = $element;

            $currentIndex -= $element['VAL'];
        }

        return $currentIndex;
    }

    /**
     *  Here is found the meat and potatoes of running a query on a single data set accounting
     *  for most query modifiers.  The separation is built such that preQuery and postQuery are
     *  run once on entire query and this can be run multiple times if a multiquery is called.
     *  
     *  @param boolean upperBound
     *  @return array of queried data
     */
    private function _queryWork ($upperBound = false)
    {
        $returnArray = array();

        $decrement = 1;

        $lowerBound = 0;

        if (!$upperBound)
        {
            $upperBound = $this->_memcache->retrieve($this->_memName);
        }
        
        if (isset($this->_queryMod['useWaypoint']['value']) && $this->_queryMod['useWaypoint']['value'])
        {
            $waypoint = $this->_memcache->retrieve($this->_memName . '_wp');

            if ($waypoint)
            {
                $lowerBound = $waypoint;
            }
        }

        $limit = (isset($this->_queryMod['limit']['value']) ? $this->_queryMod['limit']['value'] : $upperBound);

        $skipTo = (isset($this->_queryMod['offset']['value']) && $this->_queryMod['offset']['value'] ? $this->_queryMod['offset']['value'] : 0);

        if ($upperBound)
        {
            for ($i = $upperBound, $j = ($upperBound - $limit) ; $i > $lowerBound && $j < $upperBound ; $i -= $decrement)
            {
                $element = $this->_memcache->retrieve($this->_memName . '_' . $i);

                if ($element)
                {
                    // decrement by the amount, so that we skip down aggregated lists correctly
                    $decrement = $element['VAL'];

                    if ($skipTo <= 0)
                    {
                        $returnArray[] = $element;
                        ++$j;
                    }
                    else
                    {
                        --$skipTo;
                    }
                }
                // should not be reached, if so there is corrupted data somehow in memcache, such as data overwritten by
                // another program inserting over a key.
                else
                {
                    $decrement = 1;         // keep trying to decrement by one until reaching a good value
                    $returnArray[] = false; // false for corrupted entry
                }
            }
        }

        return array($returnArray, $upperBound);
    }

    /**
     *  All operations that need to run before a query begin here.
     *  
     *  @return false if nothing need happen as a result of this and query array if multi query was run
     */
    private function _preQueryWork ()
    {
        if (isset($this->_queryMod['multi']['value']))
        {
            // remember name of original query so that it can be reset afterward
            $this->_tempName = $this->_name;

            $queryResults = array();

            foreach ($this->_queryMod['multi']['value'] as $memoryListName)
            {
                $this->setName($memoryListName);

                $queryResults = array_merge($queryResults, $this->_queryWork());
            }

            $this->setName($this->_tempName);

            return $queryResults;
        }

        return false;
    }
    
    /**
     *  All operations that need to run after a query is run start here.
     *  
     *  Query result is made available to this.
     *  
     *  @param Array returnArray
     *  @return true if completed successfully or false in case of a problem
     */
    private function _postQueryWork (Array $returnArray, $topIndex)
    {
        // Sets to true in a special case of a waypoint and aggregate request being used, which makes the aggregate function
        // pull data after the waypoint with a fresh query and only compact that data.
        $postAggregate = false;
        $sort          = false;


        if (isset($this->_queryMod['multi']))
        {
            $sort = (isset($this->_queryMod['sort']['value']) ? $this->_queryMod['sort']['value'] : $this->_queryMod['sort']['default']);
        }

        // AGGREGATE
        if (isset($this->_queryMod['aggregate']['value']) && $this->_queryMod['aggregate']['value'])
        {
            // check if a maxlevel was given, else pass false to aggregateData indicating the default of full
            $maxLevel = ($this->_queryMod['aggregate']['value'] === true ? false : (int) $this->_queryMod['aggregate']['value']);

            // move top index to the set waypoint if requested
            if (isset($this->_queryMod['useWaypoint']['value']) && $this->_queryMod['useWaypoint']['value'])
            {
                $topIndex      = $this->_memcache->retrieve($this->_memName . '_wp');
                $postAggregate = true;
            }
            else
            {
                $returnArray = $this->aggregateData($returnArray, $topIndex, false, $maxLevel);
            }
        }

        // WAYPOINT (save where last query ended)
        if (isset($this->_queryMod['setWaypoint']['value']) && $this->_queryMod['setWaypoint']['value'])
        {
            $this->_memcache->update($this->_memName . '_wp', $topIndex, $this->_expirey);
        }

        // REVERSE RESULT
        if (isset($this->_queryMod['reverse']['value']) && $this->_queryMod['reverse']['value'])
        {
            $returnArray = array_reverse($returnArray);
        }

        // Reset query values in case more queries are run this script
        foreach ($this->_queryMod as $queryModKey => $queryMod)
        {
            if ($queryModKey != 'multi')
            {
                unset($this->_queryMod[$queryModKey]['value']);
            }
        }

        if ($postAggregate)
        {
            $data = $this->query($topIndex);

            $this->aggregateData($data, $topIndex, false, $maxLevel);
        }

        if (isset($this->_queryMod['multi']['value']))
        {
            unset($this->_queryMod['multi']['value']);
        }

        if ($sort)
        {
            $keyedArray = array();

            foreach ($returnArray as $i => $array)
            {
                // append i as value of least significance to avoid duplicate and resolve ties
                $keyedArray[$array['TIM'] . '_' . $i] = $array;
            }

            krsort($keyedArray); echo '<br /><br /><br />';

            $returnArray = array_values($keyedArray);
        }

        return $returnArray;
    }
}
