<?php
/**
 * This is a helper model class to helper methods for calendar output
 */
class Model_Calendar
{
	private 	$_day			=	86400;
	private 	$_max_days		=	42;
	private 	$_dow			=	7;
	private 	$_weeks 		= 	false;
	private 	$_prev 			= 	false;
	private 	$_next 			= 	false;
	private		$_month 		= 	false;
	private		$_year 			= 	false;
	private 	$_full_month 	= 	false;
	private		$_today			=	false;
	private		$_collection	=	array();
	private		$_base_uri		=	false;
	private		$_type			=	false;


	public static function forge($month=1, $year=1980, Array $collection=NULL, $base_uri=NULL, $type=NULL)
	{
		return new static($month, $year, $collection, $base_uri, $type);
	}

	public function get_day_count()
	{
		return intval(date('t',strtotime("{$this->_year}-{$this->_month}-1")));
	}

	public function get_start_date_dow($format='w')
	{
		return intval(date($format,strtotime("{$this->_year}-{$this->_month}-1")));
	}

	public function __construct($month=NULL, $year=NULL, Array $collection=NULL, $base_uri=NULL, $type=NULL)
	{
		if(is_null($month)) throw new Exception("Error Processing Request: month cannot be null.");
		if(is_null($year)) throw new Exception("Error Processing Request: year cannot be null.");

		$this->_month = $month;
		$this->_year = $year;

		$this->_today = date('Y-n-d');

		if(!is_null($collection)) $this->_collection = $collection;
		if(!is_null($base_uri)) $this->_base_uri = $base_uri;
		if(!is_null($type)) $this->_type = $type;
	}

	public function get_last_week()
	{
		return floor((floatval($this->get_day_count())+floatval($this->get_start_date_dow()+1))/6);
	}

	public function get_full_month()
	{
		return $this->_full_month;
	}

	public function get_year()
	{
		return $this->_year;
	}

	public function get_month()
	{
		return $this->_month;
	}

	public function get_weeks()
	{
		return $this->_weeks;
	}

	public function get_prev()
	{
		$month = $this->_month;
		$month--;
		$year = ($month === 0)? $this->_year-1 : $this->_year;
		if($month === 0) $month = 12;
		return "{$year}-{$month}";
	}

	public function get_next()
	{
		$month = $this->_month;
		$month++;
		$year = ($month === 13)? $this->_year+1 : $this->_year;
		if($month === 13) $month = 1;
		return "{$year}-{$month}";
	}

	public function get_base()
	{
		return ($this->_base_uri)? $this->_base_uri : '';
	}

	public function get_type()
	{
		return ($this->_type)? $this->_type : '';
	}

	public function build()
	{

		$start_dow = $this->get_start_date_dow();
		$dc = $this->get_day_count()+$start_dow-1;

		$this->_full_month = date('F Y',strtotime("{$this->_year}-{$this->_month}-1"));

		$this->_weeks = array();
		$week_count = 1;
		$dow_count = $start_dow+1;

		for ($i=$start_dow; $i < ($this->_max_days - $start_dow); $i++)
		{
			if($i > $dc) break;

			if(($i % 7) === 0)
			{
				$week_count++;
				$dow_count = 1;
			}

			$std_obj = NULL;
			$day_number = ($i-$start_dow)+1;

			$std_obj = new stdClass();
			$std_obj->day = $day_number;
			$std_obj->has_items = (isset($this->_collection[$day_number])&&!empty($this->_collection[$day_number]))? true : false;
			$std_obj->link = ($std_obj->has_items)? $this->_base_uri."/{$this->_year}/{$this->_month}/{$day_number}" : '#';
			$std_obj->today = ($this->_today === "{$this->_year}-{$this->_month}-{$day_number}")? true : false;

			# add padding for first week
			if($week_count == 1 && $i == $start_dow)
			{
				for ($x=0; $x < $start_dow; $x++)
				{
					if(!isset($this->_weeks[$week_count][$x+1])||empty($this->_weeks[$week_count][$x+1])) $this->_weeks[$week_count][$x+1] = null;
				}
			}

			$this->_weeks[$week_count][$dow_count] = $std_obj;

			# add padding for last week
			if($week_count == $this->get_last_week() && $i == $dc+1)
			{
				for ($y=$dow_count+1; $y < $this->_dow+1; $y++)
				{
					$this->_weeks[$week_count][$y] = null;
					if(!isset($this->_weeks[$week_count][$y])||empty($this->_weeks[$week_count][$y])) $this->_weeks[$week_count][$y] = null;
				}
			}

			$dow_count++;


		}

		return $this;

	}



}