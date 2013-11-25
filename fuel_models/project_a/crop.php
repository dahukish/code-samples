<?php

class Model_User_Crop extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'space_id',
		'crop_id',
		'crop_notes',
		'units',
		'current_unit_range',
		'crop_area',
		'phase',
		'dtm',
		'status',
		'created_at',
		'updated_at',
		'save_seeds',
		'instance_name',
		'est_comp_min',
		'est_comp_max',
		'seed_time_min',
		'seed_time_max',
		'grow_start',
		'scaler',
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
		'Orm\Observer_Cropinstancename' => array(
			'events' => array('before_save'),
		),
	);

	protected static $_has_many = array(
	    'task_instances' => array(
		    'key_from' => 'id',
		    'model_to' => 'Model_Scheduler_Taskinstance',
		    'key_to' => 'u_crop_id',
		    'cascade_save' => false,
		    'cascade_delete' => false,
		),
	);

	protected static $_belongs_to = array(
	'space' => array(
	    'key_from' => 'space_id',
	    'model_to' => 'Model_User_Space',
	    'key_to' => 'id',
	    'cascade_save' => false,
	    'cascade_delete' => false,
	),
	'florafauna' => array(
	    'key_from' => 'crop_id',
	    'model_to' => 'Model_Organic_FloraFauna',
	    'key_to' => 'id',
	    'cascade_save' => false,
	    'cascade_delete' => false,
	));


	/**
	 * needs_sched_reset: simple check to determine if crop needs a reset or not
	 * @param  Model_User_Crop $crop_obj
	 * @return mixed
	 */
	public static function needs_sched_reset(Model_User_Crop $crop_obj)
	{

		if(intval($crop_obj->old_start) !== intval($crop_obj->grow_start))
		{
		 	return static::reset_schedule($crop_obj->id,$crop_obj->grow_start);
		}

		return true;
	}

	/**
	 * reset_schedule: reset crop status to inactive and wipe all tasks or just reset the start date
	 * @param  integer $crop_id
	 * @param  integer $man_date
	 * @return mixed
	 */
	public static function reset_schedule($crop_id, $man_date=NULL)
	{
		$status = false;

		if($crop_id)
		{
			try
			{
				$crop = static::query()
				->related('space')
				->where(array('id','=',$crop_id))->get_one();

				$tasks = \Model_Scheduler_Taskinstance::find('all',array('where'=>array(
						array('u_crop_id','=',$crop_id),
					)));

				if(!empty($tasks))
				{
					foreach ($tasks as $task_id => $task)
					{
						$task->delete();
					}
				}

				# is their a start date?
				if(!empty($man_date))
				{

					$args = array(
							'ugz_id'=>$crop->space->zone_id,
							'u_crop_id'=>$crop->id,
							'ff_id'=>$crop->crop_id,
							'aux_args'=>array(
									'man_date'=>$man_date,
								),
						);

					# HMVC call to the crop controller
					$res = \Request::forge('member/crops/generate_schedule')->execute($args)->response();
					$result = $res->body;
					return $res->status;
				}
				else
				{
					# reset the status
					$crop->status = 0;
					$crop->save();
				}

				$status = true;
			}
			catch(\ErrorException $e)
			{
				$status = false;
			}

		}

		return $status;
	}

	/**
	 * get_crop_minmax: helper function that determines what
	 * crops will be present in a space given the crops
	 * extreme date values (start and maximum growth time)
	 * @param  mixed $space_ids
	 * @param  integer $date
	 * @param  string $prop
	 * @param  string $order
	 * @param  integer $opt_date
	 * @param  string $opt_prop
	 * @return mixed
	 */
	public static function get_crop_minmax($space_ids, $date=NULL, $prop='est_comp_min', $order='asc', $opt_date=NULL, $opt_prop=NULL)
	{

		$query_date = (!empty($date))? $date : strtotime('now');

		#keeps the date query in the present -SH
		$anchor_prop = !empty($opt_prop)?$opt_prop:'grow_start';
		$anchord_date = !empty($opt_date)?$opt_date:$query_date;

		$where = array(
						array('space_id',((is_array($space_ids))? 'IN':'='),$space_ids), //works for a single id are an array of ids
						array('status','=',1), //must be of status 1 ie: has been scheduled, otherwise this crop is in limbo
						array($anchor_prop,'<=',$anchord_date), // the start date of the crop defaults to now
						array($prop,'>=',$query_date), //the max date of the crop defaults to now
					);

		return static::query()->where($where)->order_by($prop,$order)->get();
	}

	/**
	 * get_sched_crops: get all scheduled crops within that live
	 * within a certain space in a given range of time
	 * @param  integer $space_id
	 * @param  integer $ts_start
	 * @param  integer $ts_end
	 * @return array
	 */
	public static function get_sched_crops($space_id,$ts_start=NULL,$ts_end=NULL)
	{
		$start = (!is_null($ts_start))?$ts_start:strtotime('now');
		$end = (!is_null($ts_end))?$ts_end:$start;

		$user_crops_qry = static::query();
		$user_crops_qry->related('space');
		$user_crops_qry->related('task_instances');
		$user_crops_qry->where('space_id',((is_array($space_id))?'IN':'='),$space_id);
		$user_crops_qry->where('status','>',0);
		$user_crops_qry->where('task_instances.task_ts','>=',$start);
		$user_crops_qry->where('task_instances.task_ts','<=',$end);

		$task_result = $user_crops_qry->get_query()->execute()->as_array();

		$pretty_task_result = \Orm\Bds_Query_Helper::make_pretty_cols($user_crops_qry,$task_result);
		unset($task_result);

		return $pretty_task_result;
	}

	/**
	 * get_crop_ts_extremes: get the extreme ranges of a crop object
	 * @param  Model_User_Crop $crop_obj
	 * @param  string          $start_prop
	 * @param  string          $end_prop
	 * @return array
	 */
	public static function get_crop_ts_extremes(Model_User_Crop $crop_obj, $start_prop, $end_prop)
	{
		$min = (isset($crop_obj->{$start_prop})&&!empty($crop_obj->{$start_prop}))?$crop_obj->{$start_prop}:0;
		$max = (isset($crop_obj->{$end_prop})&&!empty($crop_obj->{$end_prop}))?$crop_obj->{$end_prop}:0;

		return array($min,$max);
	}

	/**
	 * calc_crop_area: calculate the current crop area
	 * @param  Model_User_Crop $crop_obj
	 * @return integer
	 */
	public static function calc_crop_area(Model_User_Crop $crop_obj)
	{
		$valid_fields = array('units','current_unit_range','crop_area');

		$validate = function($prop,$fallback) use ($crop_obj)
		{
			return (isset($crop_obj->{$prop})&&!empty($crop_obj->{$prop}))? $crop_obj->{$prop} : $fallback;
		};

		foreach ($valid_fields as $v_fld) $$v_fld = $validate($v_fld,1);

		return ceil(static::calc_area($units, $current_unit_range, $crop_area));
	}

	/**
	 * calc_area: wrapper for the area formula
	 * @param  integer $units
	 * @param  integer $range
	 * @param  integer $area
	 * @return float
	 */
	private static function calc_area($units=1, $range=1, $area=1)
	{
		return ((floatval($units)/floatval($range))*floatval($area));
	}

	/**
	 * calc_area_used: helper method that takes already retrieved results and calculates
	 * the area used by all the crops present in the result
	 * @param  array $pretty_crop_results
	 * @return integer
	 */
	private static function calc_area_used($pretty_crop_results)
	{
		$areas = array();
		$area_total = 0;

		foreach($pretty_crop_results as $p_result)
		{
			$areas[$p_result['user_crops']['id']] = static::calc_area($p_result['user_crops']['units'],$p_result['user_crops']['current_unit_range'],$p_result['user_crops']['crop_area']);
		}

		foreach ($areas as $area)
		{
			$area_total = $area_total+$area;
		}

		return $area_total;
	}

	/**
	 * area_taken: determines the area taken from form input
	 * or uses the current object as a fallback
	 * @param  Model_User_Crop $crop_obj
	 * @param  [type]          $input
	 * @param  [type]          $prefix
	 * @param  [type]          $fields
	 * @return [type]
	 */
	public static function area_taken(Model_User_Crop $crop_obj, $input, $prefix, $fields)
	{
		if(!empty($input))
		{

			# clean the array input
			$crop_data = \Arr::filter_prefixed(\Arr::filter_suffixed($input,"_{$crop_obj->id}"),$prefix);

			$is_field_set = function($field_key) use ($crop_obj, $crop_data, $fields)
			{
				return (isset($crop_data[$fields[$field_key]])&&!empty($crop_data[$fields[$field_key]]))? $crop_data[$fields[$field_key]] : $crop_obj->{$field_key};
			};

			$units = $is_field_set('units');
			$range = $is_field_set('current_unit_range');
		}
		else
		{
			$units = $crop_obj->units;
			$range = $crop_obj->current_unit_range;
		}

		$area  = $crop_obj->crop_area;

		return static::calc_area($units, $range, $area);
	}

	/**
	 * has_space: determine if a space has enough room for a given crop
	 * @param  Model_User_Crop  $crop_obj
	 * @param  Model_User_Basespace  $space_obj
	 * @param  integer  $area_taken
	 * @return mixed
	 */
	public static function has_space(Model_User_Crop $crop_obj, Model_User_Basespace $space_obj, $area_taken)
	{
		list($min,$max) = static::get_crop_ts_extremes($crop_obj,'grow_start','est_comp_max');

		$res = static::get_sched_crops($space_obj->id,$min,$max);

		if(!empty($res))
		{
			$max_area = \Model_User_Space::get_area($space_obj);
			$area_used = static::calc_area_used($res);

			return ((intval($area_used)+intval($area_taken)) <= intval($max_area));
		}

		return true;
	}

	/**
	 * is_valid_start: check to see if the date present on
	 * the given object is indeed valid
	 * @return boolean
	 */
	public function is_valid_start()
	{
		$this->grow_start;

		$epoch = strtotime("January 1, 1970");

		if(isset($this->grow_start) && !empty($this->grow_start) && (intval($this->grow_start) > $epoch)) return true;

		return false;
	}

	/**
	 * s_get_dtm_range: static variation of the get_dtm method
	 * @param  Model_User_Crop $crop_obj
	 * @return mixed
	 */
	public static function s_get_dtm_range(Model_User_Crop $crop_obj)
	{
		if(isset($crop_obj->dtm)&&!empty($crop_obj->dtm))
		{
			try
			{
				$dtm = explode('-',$crop_obj->dtm);
				if(is_array($dtm) && !empty($dtm) &&(count($dtm)>1))
				{
					list($a,$b) = $dtm;
					if($a===$b) return $a;
					else return $crop_obj->dtm;
				}
				else return $crop_obj->dtm;
			}
			catch(\ErrorException $e)
			{
				return $crop_obj->dtm;
			}
		}
		else return false;
	}

	/**
	 * get_dtm: get a presentable value for the days to maturity
	 * range present on the given crop object
	 * @return string
	 */
	public function get_dtm_range()
	{
		try
		{
			$dtm = explode('-',$this->dtm);
			if(is_array($dtm) && !empty($dtm) &&(count($dtm)>1))
			{
				list($a,$b) = $dtm;
				if($a===$b) return $a;
				else return $this->dtm;
			}
			else return $this->dtm;
		}
		catch(\ErrorException $e)
		{
			return $this->dtm;
		}
	}

	/**
	 * get_crop_dtm: helper function that retrieves all dtm conditions for the given crop
	 * @param  Model_User_Crop $crop_obj
	 * @param  array          $filters
	 * @return array
	 */
	public static function get_crop_dtm(Model_User_Crop $crop_obj, $filters)
	{

		$data = static::condition_data($crop_obj);

		$dtm_data = array(); foreach ($filters as $f_id => $f_prefix)
		{
			$dtm_data[$f_id] = \Arr::filter_prefixed($data,$f_prefix);
		}

		return $dtm_data;
	}

	/**
	 * condition_data: cleaner way to get related condition data for a given crop
	 * @param  Model_User_Crop $user_crop
	 * @return mixed
	 */
	public static function condition_data(Model_User_Crop $crop_obj)
	{
		\Config::load('crops',true);
		$cc = \Config::get('crops.crop_conditions');
		$data = array();
		$suffixes = array('min_val','max_val','widget_val');
		foreach ($cc as $cond_type_key => $cond_type_id)
		{
			foreach ($suffixes as $sfx)
			{
				$data[$cond_type_key.'_'.$sfx] = $crop_obj->get_condition_prop($cond_type_id,$sfx);
			}
		}

		return $data;
	}

	/**
	 * has_conditions: does the crop have any associated conditions
	 * @return boolean
	 */
	public function has_conditions()
	{
		return (isset($this->florafauna->condition)&&!empty($this->florafauna->condition));
	}

	/**
	 * has_conditions_stc: static implementation o the above method
	 * @param  Model_User_Crop $crop_obj
	 * @return boolean
	 */
	public static function has_conditions_stc(Model_User_Crop $crop_obj)
	{
		return $crop_obj->has_conditions();
	}

	/**
	 * get_condition: shortcut for getting a specific condition from a crop
	 * @param  integer $cond_type_id
	 * @return mixed
	 */
	public function get_condition($cond_type_id)
	{
		if(! $this->has_conditions()) return false;

		$cond = \Model_Structure_Condition::get_condition_by_type($this->florafauna->condition,$cond_type_id);

		if($cond) return $cond;

		return false;
	}

	/**
	 * get_condition_prop: get a specific property from a given
	 * condition associated with a given crop
	 * @param  integer $cond_type_id
	 * @param  string $cond_prop
	 * @return mixed
	 */
	public function get_condition_prop($cond_type_id,$cond_prop)
	{
		$cond = $this->get_condition($cond_type_id);

		if($cond)
		{
			if(isset($cond->{$cond_prop}) && !empty($cond->{$cond_prop}))
			{
				return $cond->{$cond_prop};
			}
			else return false;
		}

		return false;
	}

	/**
	 * get_user: get a user associated with a certain crop
	 * @param  Model_User_Crop $crop_obj
	 * @return mixed
	 */
	public static function get_user(Model_User_Crop $crop_obj)
	{
		$user_id = $crop_obj->space->zone->area->user_id;

		return \Model_User::find($user_id);
	}

	/**
	 * move: move the crop to a given space, if it is compatible and has enough room
	 * @param  Model_User_Crop $user_crop
	 * @param  Model_User      $current_user
	 * @param  integer          $area_id
	 * @param  integer          $zone_id
	 * @return mixed
	 */
	public static function move(Model_User_Crop $user_crop, Model_User $current_user, $area_id, $zone_id)
	{
		if(!empty($user_crop))
		{

			$ff_ids = array();


			$ff_ids[] = $user_crop->crop_id;

			$alt_zone = intval(\Input::post('alt_zone_space',\Input::get('alt_zone_space',0)));
			$temp_zone = (isset($current_user->property_areas[$area_id]->growzones[$alt_zone]))? $current_user->property_areas[$area_id]->growzones[$alt_zone] : NULL;

			if(!is_null($temp_zone))
			{
				$zone_meta = (array)json_decode($temp_zone->zone_meta)->properties;

				if(!empty($current_user->property_areas[$area_id]->growzones[$alt_zone]->spaces))
				{
					$temp_space_obj = current($current_user->property_areas[$area_id]->growzones[$alt_zone]->spaces);

					$field_args = array(
							'units'=>'crop_units',
							'current_unit_range'=>'crops_unit_range',
						);

					$area_taken = \Model_User_Crop::area_taken($user_crop,\Input::post(),'sel_',$field_args);

					if(\Model_User_Crop::has_space($user_crop,$temp_space_obj,$area_taken))
					{
						$space_meta = (array)json_decode($temp_space_obj->meta)->properties;
						$crop_area = floatval($space_meta['dim_width'].'.'.$space_meta['dim_width_p'])*floatval($space_meta['dim_length'].'.'.$space_meta['dim_length_p']);

						# get the calculations for the moving space so we can check if the moveing crop will fit
						$temp_space_type_obj = \Model_Spaces_Spacetype::find($temp_zone->space_type);

						#check to see if there are any condtiontypes to use for this space instead of the defaults
						if(intval($temp_space_type_obj->area_cond)>0)
						{
							$cont_type_id_area = $temp_space_type_obj->area_cond;
						}
						else
						{
							$cont_type_id_area = 26;
						}

						if(intval($temp_space_type_obj->unit_per)>0)
						{
							$cont_type_id_per = $temp_space_type_obj->unit_per;
						}
						else
						{
							$cont_type_id_per = 80;
						}

						#grab the ff ids
						$alt_crops_ids = \Arr::pluck($temp_space_obj->crops,'crop_id');

						if(!empty($alt_crops_ids))
						{
							$cond_query = \Model_Structure_Condition::query();
							$cond_query->where('link_ref_id','IN',$alt_crops_ids);
							$cond_query->and_where_open();
							$cond_query->where('cond_type_id','=',$cont_type_id_area);
							$cond_query->or_where('cond_type_id','=',$cont_type_id_per);
							$cond_query->and_where_close();
							$alt_conds = $cond_query->get();

							#sort out the data for the crops in the prospective space
							foreach ($temp_space_obj->crops as $tc)
							{
								$area = 1;
								$per = 1;
								foreach ($alt_conds as $alt_cond)
								{
									if( $tc->crop_id === $alt_cond->link_ref_id)
									{
										if($alt_cond->cond_type_id === $cont_type_id_area) $area = $alt_cond->min_val;
										if($alt_cond->cond_type_id === $cont_type_id_per) $per = $alt_cond->min_val;
									}
								}
								$crop_area = $crop_area - round(((floatval($tc->units)/floatval($per))*floatval($area)));
							}
						}

						#setup the data for this space
						foreach ($space_meta as $sm_key => $sm_value) $zone_meta[$sm_key] = $sm_value;

						$hardiness_zone = \Model_Location_Datum::query()
							->where(array(
								array('postal','LIKE','%'.$current_user->profile->postal_zip.'%'),
								array('province','=',$current_user->profile->state_province),
								array('country','=',((preg_match('/^can/iU',$current_user->profile->country))? 'ca' : 'us' )),
							))
							->get_one();

						$aux_args = array(
							'space_type'=>$temp_zone->space_type,
							'ff_ids'=>$ff_ids,
							'skill'=>((intval(\Input::post('filter_diff',0)) > 0)? intval(\Input::post('filter_diff')) : $current_user->profile->skill),
							'crop_type'=>0,
						);

						if(!is_null($hardiness_zone))
						{
							preg_match('/(\d{1,2})[ab]/i',$hardiness_zone->hardiness,$phz);
							$aux_args['hardiness_zone'] = $phz[count($phz)-1];
						}

						#this is a list of crops that can be moved
						$moveable = \Arr::pluck(\Model_Organic_FloraFauna::parse_conds_for_query($zone_meta,'structure_conditions',$aux_args),'id');
						$moved = array();
						$not_moved = array();

						#check for compatible crops to move to the new zone
						if(in_array($user_crop->crop_id,$moveable))
						{

								$user_crop->space_id = $temp_space_obj->id;
								$moved[] = $user_crop->instance_name;
								$user_crop->save();
						}
						else
						{
							$not_moved[] = $user_crop->instance_name.' (incompatible)';
						}
					}

				}
				else
				{
					$not_moved[] = $user_crop->instance_name.' (not enough space)';
				}

				if(!empty($not_moved))
				{
					if(\Input::is_ajax()) return false;
					\Session::set_flash('move_error',$not_moved);
				}

				if(!empty($moved))
				{
					if(\Input::is_ajax()) return true;
					\Session::set_flash('move_success',$moved);
					\Response::redirect('member/my_growing_plans/'.$area_id.'/'.$zone_id);
				}
			}
			else
			{
				if(\Input::is_ajax()) return false;
				\Session::set_flash('form_error','Selected zone is not valid, please try another option');
			}

		}
	}

	/**
	 * crop_data_helper: helper function the preps per crop data for the view
	 * @param  Model_User_Crop $user_crop
	 * @param  array          $view_data
	 * @return void
	 */
	public static function crop_data_helper(Model_User_Crop &$user_crop, &$view_data)
	{
		$valid_cts = array();
		$crop_id = $user_crop->id;
		$view_data['seed_date'][$crop_id] = NULL;
		$view_data['seed_show_date'][$crop_id] = NULL;
		$view_data['show_date'][$crop_id] = NULL;
		$view_data['crop_comp_range'][$crop_id] = NULL;
		$view_data['indoor'] = true;
		$view_data['crop_data_attr'][$crop_id] = NULL;
		$view_data['crop_def_start'][$crop_id] = NULL;
		$view_data['crop_data_attr_obj'][$crop_id] = NULL;
		$view_data['crop_data_attr_res_obj'][$crop_id] = NULL;
		$view_data['extension'][$crop_id] = NULL;

		# a snapshot of the crop start before changes
		$user_crop->old_start = intval($user_crop->grow_start);

		if(isset($user_crop->space)) list($area_value,$area_used,$max_area) = \Model_User_Growzone::get_current_area_values($user_crop->space);

		$view_data['area_value'] = $area_value;
		$view_data['area_used'] = $area_used;
		$view_data['max_area'] = $max_area;

		# outdoors
		if(! $user_crop->space->zone->is_indoor()) # TODO: way to intimate right now but gotta get stuff done - FIX THIS -SH
		{
			$hardiness_zone = static::get_user($user_crop)->get_hd_zone();
			$view_data['crop_window_min'] = \Model_Location_Datum::ts_from_day_num($hardiness_zone->frost_end_50);
			$view_data['crop_window_max'] = \Model_Location_Datum::ts_from_day_num($hardiness_zone->frost_start_50);

			# setup data attr for javascript on a per crop basis
			$crop_data_attr_obj = new \Bds\Scheduler\Crop_Helper($user_crop,NULL,$view_data['crop_window_min'],$view_data['crop_window_max']);

			$crop_data_attr_res_obj = $crop_data_attr_obj->get_tags();
			if(!is_null($crop_data_attr_res_obj))
			{
				$view_data['crop_data_attr'][$crop_id] = (($t_tags = \Bds\General::array_to_tags($crop_data_attr_res_obj->get_aux_data(),'data-',true)) !== false )? $t_tags : false;
				# setup season extension
				if($crop_data_attr_res_obj->get_by_key('extension')) $view_data['extension'][$crop_id] = true;
			}

			$view_data['indoor'] = false;
		}

		# is there a seed range?
		if($seed_range = \Model_Structure_Condition::get_range_from_obj($user_crop->florafauna->condition,14)) $view_data['crop_seed_range'][$crop_id] = $seed_range;
		else $view_data['crop_seed_range'][$crop_id] = null;

		#setup units per
		$view_data['per_sqft_min'][$crop_id] = (floatval($user_crop->get_condition_prop(80,'min_val'))>1)? floatval($user_crop->get_condition_prop(80,'min_val')) : 1;
		$view_data['per_sqft_max'][$crop_id] = (floatval($user_crop->get_condition_prop(80,'max_val'))>1)? floatval($user_crop->get_condition_prop(80,'max_val')) : 1;

		if(floatval($view_data['per_sqft_min'][$crop_id]) < floatval($view_data['per_sqft_max'][$crop_id]))
		{
			for ($i=floatval($view_data['per_sqft_min'][$crop_id]); $i < floatval($view_data['per_sqft_max'][$crop_id])+1; $i++)
			{
				$view_data['per_sqft_units'][$crop_id][$i] = $i;
			}
		}
		else $view_data['per_sqft_units'][$crop_id] = NULL;

		$view_data['sqft'][$crop_id] = (floatval($user_crop->get_condition_prop(26,'min_val')) > 1)? floatval($user_crop->get_condition_prop(26,'min_val')) : 1;

		$olds_units = (isset($user_crop->units)&&!empty($user_crop->units))?$user_crop->units:1;
		$old_units_per = (isset($user_crop->current_unit_range)&&!empty($user_crop->current_unit_range))?$user_crop->current_unit_range:$view_data['per_sqft_min'][$crop_id];
		$user_crop->unit_per_current_val = $old_units_per;
		$view_data['old_value'][$crop_id] = (floatval($olds_units)/floatval($old_units_per))*floatval($view_data['sqft'][$crop_id]);

		for ($i=1; $i < 101; $i++)
		{
			$view_data['units'][$crop_id][$i*floatval($view_data['per_sqft_min'][$crop_id])] = $i*floatval($view_data['per_sqft_min'][$crop_id]);
		}
		unset($i);

		$dtm_choice = (!is_null($user_crop->get_condition_prop(43,'widget_val')) && preg_match('/\w+?/i',$user_crop->get_condition_prop(43,'widget_val')))? $user_crop->get_condition_prop(43,'widget_val') : NULL;

		if(!is_null($dtm_choice))
		{
			#setup the dtm selections -SH Will need to add a check based on the other choices for FNPE next week
			$dtm_arr = static::get_crop_dtm($user_crop,array(12=>'dtm_ds_hv_',94=>'dtm_tp_hv_',14=>'dtm_tp_ind_gd_',300=>'dtm_tp_ind_hv_'));

			if(!empty($dtm_arr))
			{
				$layman_terms = \Bds\General::get_layman_terms($dtm_choice);

				foreach ($dtm_arr as $dtm_k => $dtm_v)
				{

					$dtm = (intval($dtm_v['max_val'])>0)? $dtm_v['min_val'].'-'.$dtm_v['max_val'] : $dtm_v['min_val'];
					if(!preg_match('/\d+?/',$dtm)) continue;

					if(!is_null($lay_term = (\Arr::get($layman_terms,$dtm_k,NULL))))
					{
						$valid_cts[$crop_id][$dtm_k.'##'.$dtm] = $lay_term;
						if(intval($dtm_k) === 300)
						{
							$view_data['seed_date'][$crop_id] = true;
							$view_data['seed_show_date'][$crop_id] = static::hlpr_vw_date_range($seed_range,$user_crop->grow_start,1,true); // only for the seed date range
						}

					}
				}



				if((isset($view_data['seed_date']) && !empty($view_data['seed_date'])) && (count($valid_cts[$crop_id]) === 1))
				{
					$view_data['seed_show_onload'][$crop_id] = true;
				}

				//get the default completion time for the crop
				list($c_dtm_k,$c_dtm_r) = explode('##',key($valid_cts[$crop_id]));
				$view_data['show_date'][$crop_id] = static::hlpr_vw_date_range($c_dtm_r,$user_crop->grow_start,1,true);
				$view_data['crop_comp_range'][$crop_id] = $c_dtm_r;
			}
		}

		if(isset($user_crop->space->space_type_id))
		{
			if(in_array(intval($user_crop->space->space_type_id),array(4))) $view_data['seed_saving'] = true;
		}
		else
		{
			$view_data['seed_saving'] = false;
		}


		$view_data['valid_cts'] = $valid_cts;

		for ($i=1; $i < 101; $i++)
		{
			$view_data['units_def'][$crop_id] = $i;
		}

		$view_data['crop'] = $user_crop;
	}

	/**
	 * is_curren: is this crop in the (k)now?
	 * @return boolean
	 */
	public function is_current()
	{
		if(intval($this->status) === 0) return false;

		$now = strtotime('now');
		return ((intval($this->grow_start) <= intval($now))&&(intval($this->est_comp_max) >= intval($now)));
	}

	/**
	 * dtm_date_helper: generates timestamps given a start date and the completion range
	 * @param  Model_User_Crop $crop_obj
	 * @param  integer $start_date
	 * @param  integer $dtm_range
	 * @param  integer $date_offset
	 * @param  string  $prop
	 * @return array
	 */
	public static function dtm_date_helper(Model_User_Crop &$crop_obj, $start_date, $dtm_range, $date_offset=0, $prop="est_comp")
	{
		$dtm_min = 0;
		$dtm_max = 0;

 		if(preg_match('/-/',$dtm_range)) list($dtm_min,$dtm_max) = explode('-',$dtm_range);
		else
		{
			$dtm_max = $dtm_min = $dtm_range;
		}

		$date_min = (!empty($crop_obj->{"{$prop}_min"}))? $crop_obj->{"{$prop}_min"} : ($start_date + $date_offset + (intval($dtm_min)*86400));
		$date_max = (!empty($crop_obj->{"{$prop}_max"}))? $crop_obj->{"{$prop}_max"} : ($start_date + $date_offset + (intval($dtm_max)*86400));

		if(intval($date_min) > intval($date_max)) $date_max = $date_min;

		return array($date_min,$date_max);
	}

	/**
	 * temporal_helper: generate all timely data for a give crop.
	 * @param  Model_User_Crop $crop_obj
	 * @param  array           $post_data
	 * @param  integer         $filter_id
	 * @return void
	 */
	public static function temporal_helper(Model_User_Crop &$crop_obj, $post_data, $filter_id)
	{
		$post = \Arr::filter_suffixed($post_data,"_{$filter_id}");

		$date = strtotime(\Arr::get($post,'sel_crops_date',date('Y-m-d')));
		$seed_range = \Arr::get($post,'sel_crops_seed_range',NULL);
		$ext_use = \Arr::get($post,'sel_crops_date_ext',0);

		if(!empty($crop_obj->dtm))
		{
			$offset = 0;

			if($seed_range)
			{
				if(preg_match('/-/',$seed_range))
				{
					list($seed_min,$offset) = explode('-',$seed_range);
				}
				else $offset = $seed_range;
			}

			list($crop_date_min, $crop_date_max) = static::dtm_date_helper($crop_obj, $date, $crop_obj->dtm, $offset);
			if(intval($offset)>0) list($seed_date_min, $seed_date_max) = static::dtm_date_helper($crop_obj, $date, $crop_obj->dtm, 0, 'seed_time');

			$crop_obj->est_comp_min = $crop_date_min;
			$crop_obj->est_comp_max = $crop_date_max;
			if(isset($seed_date_min)) $crop_obj->seed_time_min = $seed_date_min;
			if(isset($seed_date_max)) $crop_obj->seed_time_max = $seed_date_max;
			$crop_obj->grow_start = $date;
			$crop_obj->scaler = static::scaler($date,$crop_date_max,(($ext_use>1)?($crop_date_max+(14*86400)):$crop_date_max));
		}

	}

	/**
	 * scaler: calculate the scale of the given time-frame
	 * @param  integer $start
	 * @param  integer $orig_end
	 * @param  integer $new_end
	 * @return float
	 */
	private static function scaler($start, $orig_end, $new_end)
	{
		$scale = ($new_end-$start)/($orig_end-$start);
		if($scale <= 0) $scale = 1;
		return $scale;
	}

	/**
	 * range_to_secs: take a range and return two timestamps
	 * @param  string $range
	 * @return mixed
	 */
	public static function range_to_secs($range=NULL)
	{
		if(is_null($range)) throw new \ErrorException("Error Processing Request: range cannot be null.");

		$day = 86400;
		if(preg_match('/\d+?-\d+?/',$range))
		{
			list($min,$max) = explode('-',$range);

			return array(($min*$day),($max*$day));
		}
		else
		{
			if(intval($range)===0) return 0;

			return ($range*$day);
		}
	}
}