<?php

/**
 * An ancestor class to hold methods that deal with spacial calculations
 * common to several models within the project
 */
class Model_User_Basespace extends \Orm\Model
{

	/**
	 * get_area: calculate the area for the given object
	 * @param  Model_User_Basespace $obj
	 * @return float: the calculated area for the given object
	 */
	public static function get_area(Model_User_Basespace $obj)
	{
		if(!method_exists($obj, 'meta_prop')) return false;

		foreach (array('dim_width','dim_width_p','dim_length','dim_length_p') as $prop)
		{
			if(($meta_val = $obj->meta_prop($prop)) === false) return false;
			else $$prop = $meta_val;
		}

		$width = floatval($dim_width.'.'.$dim_width_p);
    	$length = floatval($dim_length.'.'.$dim_length_p);

    	return ($width*$length);
	}

	/**
	 * get_current_area_values: get all calculated area values for the given object
	 * @param  Model_User_Basespace $obj
	 * @return array $area_value,$area_used,$max_area
	 */
	public static function get_current_area_values(Model_User_Basespace $obj)
	{
		$area_value = 0;
		$area_used = 0;
		$max_area = 0;
		$current_crops = array();

		if(!empty($obj))
		{
			$area_value = static::get_area($obj);
			$area_used = 0;
			$max_area = $area_value;

			$current_crops = \Model_User_Crop::get_crop_minmax($obj->id,NULL,'est_comp_max');

			#calculate the current space available
			foreach ($current_crops as $cc_id => $cc)
			{
				$current_area = \Model_User_Crop::current_area($cc);
				$area_used = $area_used + $current_area;
				$area_value = $area_value - $current_area;
			}
		}

		$array = array($area_value,$area_used,$max_area);

		return $array;
	}

	/**
	 * meta_prop: a cleaner way to access metadata attached to the given object
	 * @param  string $key: the key to search for within the metadata
	 * @param  string $default: the value to fallback to if no key is found
	 * @return mixed: the value contained withing the metadata collection
	 */
	public function meta_prop($key,$default=NULL)
	{
		$prop = false;
		$meta_prop = $this->meta_prop;

		if(\Str::is_json($this->{$meta_prop}))
		{
			$meta = json_decode($this->{$meta_prop},true);

			if(!isset($meta['properties'])) return (!is_null($default))? $default:false;

			if(empty($meta['properties'])) return (!is_null($default))? $default:false;

			$prop = \Arr::get($meta['properties'],$key,false);
		}

		return $prop;
	}
}