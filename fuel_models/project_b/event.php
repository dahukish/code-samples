<?php

class Model_Event extends \Orm\Model_Temporal_BDS
{
	protected static $_properties = array(
		'id',
		'title',
		'slug',
		'department',
		'body',
		'summary',
		'status',
		'has_location',
		'facility',
		'street',
		'street2',
		'city',
		'postal',
		'geocoded',
		'longitude',
		'latitude',
		'categories',
		'author_id',
		'meta_desc',
		'meta_keys',
		'robots',
		'contacts',
		'edates',
		'created_at',
		'updated_at',
		'temporal_start',
		'temporal_end',
		'to_delete',
		'approved',
	);

	protected static $_observers = array(
		'Orm\Observer_CreatedAt' => array(
			'events' => array('before_insert'),
			'mysql_timestamp' => false,
		),
		'Orm\Observer_UpdatedAt' => array(
			'events' => array('before_save'),
			'mysql_timestamp' => false,
		),
		'Orm\Observer_Slug' => array(
			'events' => array('before_save'),
			'source' => 'title',
        	'property' => 'slug',
		),
		'Orm\Observer_Summarize' => array(
			'events' => array('before_insert'),
			'source' => 'body',
        	'property' => 'summary',
		),
		'Orm\Observer_Geocode' => array(
			'events' => array('before_save'),
		),
		'Orm\Observer_Editlock' => array(
			'events' => array('after_save'),
		),
		'Orm\Observer_Multidate' => array(
			'events' => array('after_save','before_delete'),
			'properties' => array('edates'),
		),
		'Orm\Observer_Relatedfiles' => array(
			'events' => array('after_save','before_delete'),
			'properties' => array('featured_img'), //name of the field that stores the image(s) tied to this content
		),
		'Orm\Observer_Searchindex' => array(
			'events' => array('after_save','before_delete'),
		),
	);

	protected static $_belongs_to = array(
		'department' => array(
		    'key_from' => 'department',
		    'model_to' => 'Model_Department',
		    'key_to' => 'id',
		    'cascade_save' => true,
		    'cascade_delete' => false,
		),
	);

	protected static $_has_many = array('dates' => array(
	    'key_from' => 'id',
	    'model_to' => 'Model_Multidate',
	    'key_to' => 'id',
	    'cascade_save' => true,
	    'cascade_delete' => true,
	));

	protected static $_many_many = array(
	    'files' => array(
	        'key_from' => 'id',
	        'key_through_from' => 'object_id',
	        'table_through' => 'relations',
	        'key_through_to' => 'file_id',
	        'model_to' => 'Mediahandler\\Model_File',
	        'key_to' => 'id',
	        'cascade_save' => true,
	        'cascade_delete' => false,
	        'where'=>array(array('object_table','=','Model_Event')),
	    ),
	);

	/**
	 * validate: create a new instance of the validation object specfically for this model.
	 * @param  string $factory
	 * @return an instance of the Validation class
	 */
	public static function validate($factory)
	{
		$val = Validation::forge($factory);

		# add in custom validation rules
		$val->add_callable(new BdsRules());

		$val->add_field('title', 'Title', 'required|max_length[255]');
		$val->add_field('slug', 'Slug', 'max_length[255]');
		$val->add_field('status', 'Status', 'required|valid_string[numeric]');
		$val->add_field('author_id', 'Author Id', 'required|valid_string[numeric]');
		$val->add_field('template_id', 'Template Id', 'valid_string[numeric]');
		$val->add_field('edates', 'Date(s)','required')->add_rule('multidate_valid','');
		$val->add_field('department', 'Department', 'required|valid_string[numeric]');
		$val->add_field('featured_img', 'Featured Image', 'max_length[255]');
		$val->add_field('featured_img_alt', 'Featured Image Alt Text', 'max_length[255]');
		$val->add_field('has_location', 'Has Location', 'valid_string[numeric]');
		$val->add_field('facility', 'Facility', 'valid_string[numeric]');
		$val->add_field('street', 'Street', 'max_length[255]');
		$val->add_field('street2', 'Street(2)', 'max_length[255]');
		$val->add_field('city', 'Town/City', 'max_length[32]');
		$val->add_field('postal', 'Postal Code', 'max_length[7]');
		$val->add_field('categories', 'Categories', 'required');

		return $val;
	}

	/**
	 * get_collection: a helper function to grab events for a certain month to be used with the calendar object
	 * @param  integer $month
	 * @param  integer $year
	 * @return array
	 */
	public static function get_collection($month=NULL, $year=NULL)
	{
		$last_day = date('t',strtotime("{$year}-{$month}-1"));

		$min = mktime(0,0,1,$month,1,$year);
		$max = mktime(23,59,59,$month,$last_day,$year);
		$now = mktime(0,0,1,date('n'),date('j'),date('Y'));

		$timestamp_end_name = static::temporal_property('end_column');
		$max_timestamp = static::temporal_property('max_timestamp');

		$res = static::query()->select('id')->related('dates')->where(array(
														array('status','>=',3),
														array('dates.start','>=',date('Y-m-d H:i:s',$min)),
														array('dates.start','>=',date('Y-m-d H:i:s',$now)),
														array('dates.end','<=',date('Y-m-d H:i:s',$max)),
														array($timestamp_end_name,$max_timestamp),
													))->get_query()->execute();
		$days = array();

		$dates = $res->as_array(); foreach ($dates as $date)
		{
			$days[date('j',strtotime($date['t1_c3']))] = true;
		}

		return (!empty($days))? $days : array();
	}

	/**
	 * zero_time: a simple format helper that overrides the time part of the timestamp with a given time
	 * @param  integer $edate
	 * @param  string $time
	 * @return integer
	 */
	private function zero_time($edate=NULL, $time=NULL)
	{
		return strtotime(date('Y-m-d',$edate).' '.$time);
	}

	/**
	 * get_now: get the current time via external source or internal system time zeroed to midnight of the selected date
	 * @param  string $date
	 * @return integer
	 */
	private function get_now($date=NULL)
	{
		$temp_now = (!is_null($date))? $date : strtotime('now');

		# zero time to midnight on the same date
		return $this->zero_time($temp_now,'00:00:01');
	}

	/**
	 * get_edates: a quick way to grab the edates in array from an object
	 * @return mixed
	 */
	private function get_edates()
	{
		return \Str::is_json($this->edates)? json_decode($this->edates) : NULL;
	}

	/**
	 * set_next_valid_date: set the date of the object to the next valid date
	 * @param integer $date
	 */
	public function set_next_valid_date($date=NULL)
	{
		$this->created_at = $this->get_next_valid_date($date);
	}

	/**
	 * set_last_valid_date: set object date to the last valid date of the current object
	 * @param integer $date
	 */
	public function set_last_valid_date($date=NULL)
	{
		if($this->created_at===false) return false;

		$temp_date = $this->get_last_valid_date($date);

		# zero out the times so that we can truly compare
		$a = $this->zero_time($temp_date,'00:00:01');
		$b = $this->zero_time($this->created_at,'00:00:01');

		$this->last_valid_date = ($a === $b)? false : $temp_date;
	}

	/**
	 * get_last_valid_date: easy way to grab the last valid date on an event object
	 * @param  string $date
	 * @return mixed
	 */
	public function get_last_valid_date($date=NULL)
	{
		$edates = $this->get_edates();

		if(empty($edates)) return false;

		$end = end($edates);

		$now = $this->get_now();

		# clourse to help with date validation
		$is_now = function($temp_date) use ($now)
		{
			return (intval(strtotime($temp_date)) >= intval($now));
		};

		return (isset($end->to)&&!empty($end->to)&&$is_now($end->to))? strtotime($end->to) : false;
	}

	/**
	 * get_next_valid_date: helper function to get the next valid date saved to an event given the input date (either provided from an outside source or "now")
	 * @param  mixed $date
	 * @return mixed
	 */
	public function get_next_valid_date($date=NULL)
	{
		$now = $this->get_now();

		$edates = $this->get_edates();

		if(empty($edates)) return false;

		$day = 86400-2;

		foreach ($edates as $ed)
		{
			$from 	= $this->zero_time($ed->from,'00:00:01');
			$to 	= $this->zero_time($ed->to,'23:59:59');

			for($i=$from; $i < ($to + 1); $i = $i + $day)
			{

				if($i >= $now)
				{
					if(intval($ed->multi)===0) return $i;

					switch($ed->recurUnit)
					{
						case "all":
							return $i;
						break;

						case "days":
							if(in_array(date('D',$i),$ed->recur_days)) return $i;
						break;

						case "monthly":

							# some quick closures to help with code duplication
							$recur_helper = function($str_day) use($i,$ed)
							{
								$dow = strtolower($ed->recur_every_day);
								$temp = strtotime("{$str_day} {$dow} of this month",$i);
								$temp_min = $this->zero_time(date('Y-m-d',$temp),'00:00:01');
								$temp_max = $this->zero_time(date('Y-m-d',$temp),'23:59:59');
								return array($temp_min,$temp_max);
							};

							$recur_switch = function($day_name) use ($recur_helper,$i)
							{
								list($min,$max) = $recur_helper($day_name,$i);
								if($min <= $i && $max >= $i) return $i;
							};

							switch($ed->recur_days)
							{
								case "1":
									return $recur_switch("First");
								break;

								case "2":
									return $recur_switch("Second");
								break;

								case "3":
									return $recur_switch("Third");
								break;

								case "4":
									return $recur_switch("Fourth");
								break;

								case "5":
									return $recur_switch("Last");
								break;

								case "9":
									if(date('j',$i) === date('j',$from)) return $i;
								break;
							}

						break;
					}
				}
			}

		}

	}

}