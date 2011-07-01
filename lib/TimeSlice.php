<?php

/**
 *	TimeSlice is meant to deal with 'slots' of time.  So a static
 *	24 hour period or 1 hour period for example.  Setup to be reminiscient of enum
 *	
 *	@author T Pierce <tyler.pierce@gmail.com>
 */

class TimeSlice
{
	const HOUR  = 1;

	const DAY   = 2;

	const WEEK  = 3;
	
	const MONTH = 4;
	
	const YEAR  = 5;

	/**
	 *	Sets to the time type const
	 */
	private $_timeType = 1; // defaulted to 1


	/**
	 *	Constructor
	 *	
	 *	Expects int representing time type const.  Send statically for clarity TimeSlice::HOUR
	 *	
	 *	@param TimeType timeTypes
	 */
	function __construct ($timeType)
	{
		// Set the divisor.  This is the value that is used to
		// cut PHP time() down to the value wanted
		$this->_divisor = $this->getDivisor($timeType);

		$this->_timeType = $timeType;
	}

	/**
	 *	Retrieve an integer representation of the seconds since 1970 representing a slice
	 *	of time.  So for an hour this will retrieve an integer representing hours since 1970.
	 *	
	 *	@param string for
	 *	@return integer
	 */
	public function getInt($for)
	{
		switch ($for)
		{
			case 'CURRENT' :
				return floor(time() / $this->_divisor);
		
			default :
				return floor(time() / $this->_divisor);
		}
	}

	/**
	 *	Retrieve the divisor for a specific time type
	 *	
	 *	@param int timeType
	 *	@return integer
	 */
	public function getDivisor ($timeType)
	{
		$divisor = 1;

		switch ((int) $timeType)
		{
			case TimeSlice::YEAR :
				$divisor = 12;

			case TimeSlice::MONTH :
				// rolling 28 day count.  No care for differing amounts of time.
				$divisor = 4 * $divisor;

			case TimeSlice::WEEK :
				$divisor = 7 * $divisor;

			case TimeSlice::DAY :
				$divisor = 24 * $divisor;

			case TimeSlice::HOUR :
				$divisor = 3600 * $divisor;
				break;
			
			default :
				throw new mlException(array(
					'code'  => 0,
					'level' => 0,
					'desc'  => 'Code should not be reached.  Unimplemented Enum type in TimeSlice calculation.',
				));
		}

		return $divisor;
	}

	/**
	 *	Retrieve the amount of seconds in the subject time slice.
	 *	
	 *	@return integer
	 */
	public function getSliceInSeconds($timeType = false)
	{
		return ($timeType ? $this->getDivisor($timeType) : $this->_divisor);
	}
}