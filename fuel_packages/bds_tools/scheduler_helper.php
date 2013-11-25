<?php

namespace Bds\Scheduler;

#defines for condition types that have specific calculations
define('CYCLE_START_A', 74); //A for Veg/PNE/Herb/Etc
define('CYCLE_START_B', 310); //B for Fruit Nut
define('GREENHOUSE_DAYS', 14); //change for DTM_SEED
define('GERMINATION', 98);
define('THINNING_SEEDLINGS_SEED', 112);
define('THINNING_SEEDLINGS_DS', 154);
define('POTTING_UP', 167);
define('FEEDING_SEED', 113);
define('FEEDING', 19);
define('HARVEST', 120);
define('HARVEST_FREQ', 290);
define('PRUNE_SUCKER', 116);
define('MULCHING', 117);
define('WATER_WEEKLY', 151);
define('SEASON_EXTENDER', 141);
define('COMMON_PROBLEMS', 88);
define('PEST_DISEASE', 104);
define('ANIMAL_INVOLVEMENT', 119);
define('DTM_SEED_GD', 14);
define('DTM_SEED', 300);
define('DTM_DS', 12);
define('DTM_TP', 94);
define('DTM_SEED_SAVE',122);
define('TP_HARDEN_OFF', 155);
define('FLOWERING',179);
define('ROGUING',160);
define('OVERWINTERING_A',193);
define('OVERWINTERING_B',192);
define('STRATIFICATION_PRE_TREAT',52);
define('STRATIFICATION_PERIOD',194);
define('GERM_TEMP',195);
define('NEW_GROWTH',311);
define('TRANSPLANT_READY',312);
define('TRANSPLANT_NOTIFY',305);
define('PRODUCTIVE_LIFESPAN',35);
define('FIRST_FRUITNG_SEED',30);
define('FIRST_FRUITNG_GRAFT',176);
define('FRUIT_THINNING',206);
define('DTM_FN',307);
define('PEST_MANAGMENT',204);
define('DISEASE_MANAGMENT',205);
define('WEEDING',203);
define('MOWING',168);
define('LIMB_TRAINING',169);
define('MULCHING_FN',171);
define('WINTER_CARE',172);

#defines for times
define('DAY', 86400);
define('WEEK', 604800);

#defines to phases
define('SEED', 1);
define('TRANSPLANT', 2);
define('DIRECT_SEED', 3);
define('DIRECT_START', 4);
define('TRANSPLANT_BUY', 5);
define('TRANSPLANT_NURSE', 6);
define('TRANSPLANT_OTHER', 7);
// define('RUNNER_STOLON', 8);
define('SEED_SAVING_TP', 9);
define('SEED_SAVING_DS', 10);

#defines for space types
define('SQUARE_FOOT_GARDEN',2);
define('GARDEN_BEDS',4);
define('WATER_GARDEN',6);
define('HYDROPONIC_GARDENS',8);
define('AQUAPONIC_GARDENS',9);
define('VERTICAL_SPACES',11);
define('GLASS_GARDENS',12);
define('FOREST_AND_SHADE_GARDENS',14);
define('INDOOR_GARDENS',15);
define('SPECIALTY_SPACES',16);
define('MICRO_LIVESTOCK_SPACES',17);
define('FEED_PRODUCTION_SPACES',18);
define('SOIL_NUTRIENT_PRODUCTION_SPACES',19);
define('PROCESSING_AND_STORAGE_SPACES',20);
define('CONTAINER_GARDENS',21);

#defines for Taskgroups
define('TGROUP_A',3); //Veg/Herb/PNE/etc
define('TGROUP_B',4); //Fruit/Nut

#general defines for usefulness
define('TASK_TYPE',1);
define('COND_TYPE',2);

define('REL_NA',0); 		// script has full control of the logic no user input
define('REL_TASK',1); 		// script will use related task to help generate a time-stamp
define('REL_CAL',2); 		// script will use the current calendar week numbers for time-stamp
define('REL_LFD',3); 		// script will use last frost date and relative week numbers for time-stamp
define('REL_CROPSTART',4);	// script will user crop start (strtotime('now')) and relative week numbers for time-stamp
define('NON_REL_CAL',5);	// straight calendar date, will translate to times-stamp

define('TS_AVG',1);
define('TS_FIRST',2);
define('TS_LAST',3);

class HelperException extends \FuelException {}

/**
 * Helper class for the scheduler
 *
 * @package     Bds
 *
 */
class Helper
{
	protected $_task_groups_query = NULL;
	protected $_task_query = NULL;
	protected $_task_groups = array();
	protected $_tasks = array();
	protected $_tasks_dependancies = array();
	protected $_ff_obj = NULL;
	protected $_conditions = array();
	protected $_category = false;
	// protected $_categories = array();
	protected $_ugz = NULL;
	protected $_ugz_props = array();
	protected $_date_baseline = false;
	protected $_harvest_avg = false;
	protected $_start_date = array();
	protected $hz = NULL;
	protected $_timestamps = array();
	protected $_current_phase = false;
	protected $_taskgroup_cat = 0;
	protected $_logger = NULL;
	protected $_dtm_days = 0;
	protected $_man_date = false;

	#for handling nursery time
	protected $_nursery = false; //will this scheduler need to add nursery tasks?
	protected $_nusery_tasks = array(); // a place for the nursery tasks
	protected $_nursery_alt_phase = false; //the alternate phase to use
	protected $_nursery_alt_dtm = false; //the condition that houses the time value for the out life-span of the crop
	protected $_nursery_offset = NULL; //must be null to start - SH

	#for zee math
	protected $_scaler = 1;


	#arg assign properties
	protected $_args_list = array();

	/**
	 * @param \Model_Organic_FloraFauna $ff_obj
	 * @param \Orm\Query                $task_groups_query
	 * @param \Orm\Query                $task_query
	 * @param \Model_User_Growzone      $ugz
	 * @param ErrorHandler              $logger
	 * @param mixed                    	$hardiness_zone
	 * @param mixed                    	$args_list
	 */
	public function __construct(\Model_Organic_FloraFauna $ff_obj, \Orm\Query $task_groups_query, \Orm\Query $task_query, \Model_User_Growzone $ugz, ErrorHandler $logger, $scaler, $hardiness_zone, $args_list=NULL)
	{
		$this->_ff_obj = $ff_obj;
		$this->_task_groups_query = $task_groups_query;
		$this->_task_query = $task_query;
		$this->_ugz = $ugz;
		$this->_logger = $logger;
		$this->_hz = $hardiness_zone;
		$this->_args_list = $args_list;

		# using a scaler now to increase the lifetime of tasks
		if(intval($scaler)>1) $this->_scaler = $scaler;

		if(!isset($args_list['u_crop_id']) && empty($args_list['u_crop_id'])) $this->_logger->handle_error('args_list:u_crop_id is required, and currently missing.');
		if(!isset($args_list['user_id']) && empty($args_list['user_id'])) $this->_logger->handle_error('args_list:user_id is required, and currently missing.');

	}

	/**
	 * [setup used to do heavy lifting instead of the constructor]
	 * @return [void] [nothing]
	 */
	private function setup()
	{

		$this->_conditions = $this->_ff_obj->crop_conditions(NULL,NULL,NULL,$this->_ff_obj);
		$this->_category = $this->_ff_obj->template_id;
		$this->_current_phase = $this->get_phase();
		$this->_taskgroup_cat = $this->get_taskgroup_cat();
		$this->_ugz_props = json_decode($this->_ugz->zone_meta)->properties;
		$this->_date_baseline = $this->get_start_baseline();
		$this->_man_date = $this->get_valid_man_date();
		if($this->_nursery) $this->_nursery_offset = $this->ndate_offset($this->_man_date,$this->_nursery_alt_dtm);



		$this->_task_groups = $this->_task_groups_query->where('id','=',$this->_taskgroup_cat)->get();

		$all_tasks = $this->_task_query->get();

		if(empty($all_tasks)) $this->_logger->handle_error('Cannot find any tasks associated with this group.');

		foreach ($this->_task_groups as $tg_id => $t_group)
		{
			$t_goup_ids = explode(',',$t_group->grouped_task_ids);

			if(empty($t_goup_ids)) $this->_logger->handle_error('This group has no associated tasks.');

			$this->_tasks[$tg_id] = array();

			if($this->_nursery) $this->task_filter($all_tasks,$this->_nursery_alt_phase,$tg_id,$t_goup_ids,'_nusery_tasks');
			$this->task_filter($all_tasks,$this->_current_phase,$tg_id,$t_goup_ids);

			unset($all_tasks); //dump the excess
		}

		$deps = \Arr::get($this->_tasks,$this->_taskgroup_cat,NULL);

		if(!is_null($deps)) $this->_tasks_dependancies = $this->check_dependencies($deps);
		else  $this->_logger->handle_error('Warning: Template Type not currently supported.');

	}

	private function ndate_offset($date_ts=NULL,$cond_id=NULL)
	{
		if(is_null($date_ts)) $this->_logger->handle_error("Warning: date_ts cannot be null.");
		if(is_null($cond_id)) $this->_logger->handle_error("Warning: cond_id cannot be null.");

		list($dtm_min,$dtm_max) = $this->get_safe_range($this->get_condition($cond_id));
		$dtm_adjust = 0;

		if(intval($dtm_min) === intval($dtm_max)) $date_ts = $date_ts + intval($dtm_min*DAY);
		else
		{
			$date_ts = $this->get_task_timestamp($this->week_range_to_array(($date_ts + intval(($dtm_min*DAY) + ($dtm_adjust * -1))),($date_ts + intval(($dtm_max*DAY) + ($dtm_adjust * -1)))),TS_AVG);
		}

		return $date_ts;
	}

	private function task_filter($all_tasks=NULL,$filter_key=NULL,$tg_id=NULL,$t_goup_ids=NULL,$filtered_store="_tasks")
	{
		if(is_null($all_tasks)) $this->_logger->handle_error("Warning: all_tasks cannot be null.");
		if(is_null($filter_key)) $this->_logger->handle_error("Warning: filter_key cannot be null.");
		if(is_null($tg_id)) $this->_logger->handle_error("Warning: $tg_id cannot be null.");
		if(is_null($t_goup_ids)) $this->_logger->handle_error("Warning: t_goup_ids cannot be null.");
		if(!isset($this->{$filtered_store})) $this->_logger->handle_error("Warning: storage propert does not exist on current object.");

		$seed_save_phases = array(SEED_SAVING_DS,SEED_SAVING_TP);

		foreach ($all_tasks as $a_id => $a_task)
		{
			#if in current taskgroup
			if(!in_array($a_id,$t_goup_ids)) continue;

			#if not valid for current phase
			if(preg_match('/\d{1,11}/',$a_task->phases))
			{
				$phases = json_decode($a_task->phases);
				if(!in_array($filter_key,$phases)) continue;
			}

			#check to see if the task is in-compatable with seed saving
			if(!in_array($filter_key,$seed_save_phases)&&(intval($a_task->seed_save)>0))
			{
				$alt_task = \Arr::get($all_tasks,$a_task->seed_alt_task,NULL);

				if(!is_null($alt_task)) $a_task = $alt_task;
			}

			if(preg_match('/\d{1,11}/',$a_task->sub_tasks)) $this->assign_subtasks($a_task,$all_tasks);

			$this->{$filtered_store}[$tg_id][$a_task->id] = $a_task;
		}

	}

	/**
	 * [assign_subtasks glues parent tasks and subtasks together - recursive]
	 * @param  [Model_Task] $task 	[take a reference ]
	 * @param  [array] $all_tasks	[take a reference ]
	 * @return [void]       		[nothing]
	 */
	private function assign_subtasks(&$task=NULL,&$all_tasks=NULL)
	{

		if(is_null($task)) $this->_logger->handle_error('reference to task cannot be null.');
		if(is_null($all_tasks)) $this->_logger->handle_error('reference to task cannot be null.');


		$subtasks = (array)json_decode($task->sub_tasks);

		if(!empty($subtasks))
		{
			$task->sub_task_objs = array();

			foreach ($subtasks as $subtask)
			{
				$sub_obj = \Arr::get($all_tasks,$subtask,NULL);
				if(is_null($sub_obj)) continue; //nothing to see here -SH

				#if the sub-task has phases set we will then compare it to the current phase
				#to see if this is a valid task, otherwise ignore it phases and add it.

				if(preg_match('/\d{1,11}/i',$sub_obj->phases))
				{
					$phases = json_decode($sub_obj->phases);

					if(!in_array($this->_current_phase,$phases)) continue;
				}

				if(preg_match('/\d{1,11}/',$sub_obj->sub_tasks)) $this->assign_subtasks($sub_obj,$all_tasks);

				$task->sub_task_objs[$sub_obj->id] = $sub_obj;

			}
		}

		return false;
	}

	/**
	 * [check_dependencies looks through tasks and subtasks for any dependencies ]
	 * @param  [type] $tasks [an array of tasks and subtasks to sift through an check for dependencies when dealing with time relation]
	 * @return [mixed]        [returns an array of tasks that are needed as dependencies, or false when none found]
	 */
	private function check_dependencies(array $tasks=NULL)
	{

		foreach ($tasks as $t_id => $task)
		{
			if(isset($task->sub_task_objs) && !empty($task->sub_task_objs)) $sub_array = $this->check_dependencies($task->sub_task_objs);

			if(intval($task->rel_task_id)>0)
			{
				$temp_task = \Arr::get($this->_tasks[$this->_taskgroup_cat],$task->rel_task_id,NULL);

				if(!is_null($temp_task)) $array[(((intval($temp_task->rel_cond_type_id)>0) && (intval($temp_task->logic_status) !== 2))? 'cond_'.$temp_task->rel_cond_type_id : 'task_'.$temp_task->id)] = false;
			}
		}

		if(!empty($sub_array)) $array = $array+$sub_array;

		if(!empty($array)) return $array;

		return false;
	}

	/**
	 * [validate_dependant - keeps the validation of prerequisite DRY and allows for further expansion on the validation]
	 * @param  string    $key [the key for the needed condition type]
	 * @return [boolean]      [returns true if found]
	 */
	private function validate_dependant($key=NULL)
	{
		if(!isset($this->_tasks_dependancies[$key]) || is_bool($this->_tasks_dependancies[$key]))
		{
			list($prefix,$id) = explode('_',$key);

			$this->_logger->handle_error('Warning: Missing required (Dependant) condition type ID#'.$id.'. This could mean a task is being processed out of sequence, or the needed condition has no valid data.',1);
			return false;
		}

		return true;
	}

	/**
	 * [validate_condition - check to see if the condition is set in $this->_conditions on a per request basis]
	 * @param  int    $cond_type_id [the key for the needed condition type]
	 * @return [boolean]      [returns true if found]
	 */
	private function validate_condition($cond_type_id=0)
	{
		if(is_null(\Arr::get($this->_conditions,$cond_type_id,NULL)))
		{
			$this->_logger->handle_error('Warning: Missing required (Non-Dependant) condition type ID#'.$cond_type_id.'. This could mean a task is being processed out of sequence, or the needed condition has no valid data.',1);
			return false;
		}
		return true;
	}

	/**
	 * [is_depended_on check for task in _task_dependencies array propetry]
	 * @param  Model_Scheduler_Task  $task [the task to check for dependencies]
	 * @return mixed       [returns 'cond' for condition, 'task' for a task or false for not depended on]
	 */
	private function is_depended_on($task=NULL)
	{


		if(is_null($task)) $this->_logger->handle_error('is_depended_on: $task cannot be null');


		if(intval($task->rel_cond_type_id)>0)
		{
			if(!is_null(\Arr::get($this->_tasks_dependancies,'cond_'.$task->rel_cond_type_id,NULL))) return 'cond';
		}

		if(intval($task->rel_task_id)>0)
		{
			if(!is_null(\Arr::get($this->_tasks_dependancies,'task_'.$task->id,NULL))) return 'task';
		}

		return false;
	}


	/**
	 * [set_dependant set the dependent if it is a valid task that has dependencies]
	 * @param [Model_Scheduler_Task] $task [the task object to set]
	 * @param [mixed] $t_stamp [an integer timestamp or an array of integer timestamps]
	 * @return boolean [return true if set and false if not sel (also if not need to be set ie: not a dependency)]
	 */
	private function set_dependant($task=NULL,$t_stamp=NULL)
	{
		if(is_null($task)) $this->_logger->handle_error('set_dependant: $task cannot be null');
		if(is_null($t_stamp)) $this->_logger->handle_error("set_dependant: $t_stamp(task#{$task->id}) cannot be null");

		if((is_array($t_stamp) && !empty($t_stamp)) || (is_numeric($t_stamp) && (intval($t_stamp)>0)))
		{
			#is the current task depended on?
			if(($task_dep = $this->is_depended_on($task)) !== false)
			{
				$dep_type = ($task_dep === 'cond')? 'cond_': 'task_';
				$dep_method = ($task_dep === 'cond')? 'rel_cond_type_id': 'id';

				$this->_tasks_dependancies[$dep_type.$task->{$dep_method}] = $t_stamp;
				return true;
			}
		}


		return false;
	}

	/**
	 * [get_dependant - retrieve dependent from object]
	 * @param  str    	$key [the key needed for the condition type]
	 * @param  mixed    $fall_back [if this is set the get_dependant will return that value in stead of false]
	 * @return mixed      [returns either an integer timestamp for a single date or an array of timestamps for a range]
	 */
	private function get_dependant($key=NULL,$fall_back=NULL)
	{
		$this->validate_dependant($key);

		$dep = \Arr::get($this->_tasks_dependancies,$key,false);
		if(empty($dep)) return ((!is_null($fall_back))? $fall_back : false);

		return $dep;
	}

	/**
	 * get_condition an interface for getting conditions store in the object if they are valid
	 * @param  integer $cond_type_id  the condition type id you want to retrieve
	 * @return mixed                  the condition array or false if not valid
	 */
	private function get_condition($cond_type_id=0, $fall_back=NULL)
	{
		$this->validate_condition($cond_type_id);

		$cond = \Arr::get($this->_conditions,$cond_type_id,false);
		if(empty($cond)) return ((!is_null($fall_back))? $fall_back : false);

		return $cond;
	}

	/**
	 * get_critical_conds looks through the array of conditions and tries to identify critical ones, it then returns the array of ids for any found
	 * @return array returns a list of critical ids as assigned by the admin
	 */
	private function get_critical_conds()
	{
		if(empty($this->_conditions)) return false;

		$array = array();

		foreach ($this->_conditions as $cond_id => $cond)
		{
			if(intval($cond['is_critical'])>0) $array[] = intval($cond_id);
		}

		return $array;
	}

	/**
	 * [flatten_tasks - helper task to flatten the hierarchical object list for the tasks. To be used with validate_conditions() method ]
	 * @return [array] [returns a flatten task list for validation]
	 */
	private function flatten_tasks($task=NULL)
	{
		$flattend_tasks = array();

		#loop through all the tasks
		if(is_null($task))
		{
			$tasks = \Arr::get($this->_tasks,$this->_taskgroup_cat,NULL);

			if(is_null($tasks)) $tasks = array();

			foreach ($tasks as $t_id => $task)
			{
				if(isset($task->sub_task_objs) && !empty($task->sub_task_objs))
				{
					$this->fetch_child_tasks($task,$flattend_tasks);
					$flattend_tasks[$task->id] = $task;
				}
				else
				{
					$flattend_tasks[$task->id] = $task;
				}
			}
		}
		else
		{

			if(isset($task->sub_task_objs) && !empty($task->sub_task_objs))
			{
				$this->fetch_child_tasks($task,$flattend_tasks);
				$flattend_tasks[$task->id] = $task;
			}
		}

		return $flattend_tasks;

	}

	/**
	 * [fetch_child_tasks - gets all child task recursively]
	 * @param  [Model_Task] $task 	[reference to the task we want to retrieve the child tasks from]
	 * @return [array]       		[an array of child tasks]
	 */
	private function fetch_child_tasks(&$task=NULL,&$array=array())
	{
		$children = array();

		if(isset($task->sub_task_objs) && !empty($task->sub_task_objs))
		{
			foreach ($task->sub_task_objs as $t_id => $sub_task_obj)
			{
				if(isset($sub_task_obj->sub_task_objs) && !empty($sub_task_obj->sub_task_objs))
				{
					$this->fetch_child_tasks($sub_task_obj,$array);
					$children[$sub_task_obj->id] = $sub_task_obj;
				}
				else
				{
					$children[$sub_task_obj->id] = $sub_task_obj;
				}
			}
		}

		$array = $array+$children;

	}

	/**
	 * [run_validations checks to make sure all conditions needed by the tasks throws error]
	 * @return [Helper] [returns object for your chaining pleasure]
	 */
	public function run_validations()

	{
		$this->setup();

		try
		{
			if(empty($this->_task_groups)) $this->_logger->handle_error('No viable task groups found: aborting schedule creation');
			if(empty($this->_tasks)) $this->_logger->handle_error('No viable tasks found: aborting schedule creation');
			static::validate_conditions();
		}
		catch(\ErrorException $e)
		{
			// die($e->getMessage());
			// echo $e->getMessage();
			throw new \ErrorException($e->getMessage());
		}

		return $this;
	}

	/**
	 * [validate_conditions checks to make sure all conditions needed by the tasks throws error]
	 * @return [bool] [return true if passed and throws an exception if not]
	 */
	private function validate_conditions()
	{

		$flat_tasks = $this->flatten_tasks(); //flatten out the used tasks for ease of use with the validation algorithm -SH

		#make sure all task conditions are set and valid

		foreach ($flat_tasks as $t_id => $task)
		{
			if(intval($task->rel_cond_type_id)>0)
			{

				if(is_null(($temp = \Arr::get($this->_conditions,$task->rel_cond_type_id,NULL))))
				{
					if(!(intval($task->logic_status) === 2)) $this->_logger->handle_error('Warning: (Task related) Condition Type ID#'.$task->rel_cond_type_id.' not found in flora/fauna condtions.',1);
					else $this->_logger->handle_error('Caution: (Task related) Non-critical Condition Type ID#'.$task->rel_cond_type_id.' not found in flora/fauna condtions.',2);
				}

				switch ($task->logic_status)
				{
					case 1:
					case 3:
						if(! static::validate_cond_value(\Arr::get($this->_conditions,$task->rel_cond_type_id,array())))
						{
							$this->_logger->handle_error('Warning: (Task related) Condition Type ID#'.$task->rel_cond_type_id.' exists on flora/fauna, but contains no useful data.',1);
						}
					break;

					case 2:
						if(! static::validate_cond_value(\Arr::get($this->_conditions,$task->rel_cond_type_id,array())))
						{
							$this->_logger->handle_error('Caution: (Task related) Non-critical Condition Type ID#'.$task->rel_cond_type_id.' exists on flora/fauna, but contains no useful data.',2);
						}
					break;
				}

			}
		}

		unset($flat_tasks);

		// }

		#check for non task conditions on flora fauna but still very relevant (ie: the conditions that make up the start date)
		static::validate_non_task_conds();

		return true;
	}

	/**
	 * [validate_non_task_conds used to validate condition types need by the scheduler but not actually created as a link to a task in the taskgroup]
	 * @return [void] [she returns NOTHING!]
	 */
	private function validate_non_task_conds()
	{
		switch($this->_taskgroup_cat)
		{
			case TGROUP_A:



				$conds_check = array(43,53,155,17); // condition type ids

				switch($this->_current_phase)
				{
					case SEED:
						$conds_check[] = GREENHOUSE_DAYS;
						$conds_check[] = GERMINATION;
						$conds_check[] = THINNING_SEEDLINGS_SEED;
						// $conds_check[] = HARVEST;
					break;

					case SEED_SAVING_TP:
						$conds_check[] = GREENHOUSE_DAYS;
						$conds_check[] = GERMINATION;
						$conds_check[] = THINNING_SEEDLINGS_SEED;
						$conds_check[] = DTM_SEED_SAVE;
					break;

					case DIRECT_SEED:
						$conds_check[] = GERMINATION;
						$conds_check[] = THINNING_SEEDLINGS_DS;
						$conds_check[] = HARVEST;
						$conds_check[] = HARVEST_FREQ;
					break;

					case SEED_SAVING_DS:
						$conds_check[] = GERMINATION;
						$conds_check[] = THINNING_SEEDLINGS_DS;
						$conds_check[] = DTM_SEED_SAVE;
					break;

					case TRANSPLANT:
						$conds_check[] = HARVEST;
						$conds_check[] = HARVEST_FREQ;
					break;

				}

			break;

			case TGROUP_B:
				$conds_check = array();
			break;
		}

		#if any space conditions add them in here
		$space_conditions = $this->get_space_conditions();
		if(!empty($space_conditions)) $conds_check = $conds_check+$space_conditions;

		foreach ($conds_check as $c_chk)
		{
				if(is_null(\Arr::get($this->_conditions,$c_chk,NULL))) $this->_logger->handle_error('Warning: (Space/Non-task related) Condition of type ID#'.$c_chk.' is required for calculations, but not found on current crop.',1);


				if(! static::validate_cond_value(\Arr::get($this->_conditions,$c_chk,array())))
				{
					$this->_logger->handle_error('Warning: (Space/Non-task related) Condition Type ID#'.$c_chk.' exists on flora/fauna, but contains no useful data.',1);
				}

		}

	}

	/**
	 * [validate_cond_value - validate a single condition element to check if it is empty or null]
	 * @param  array  $cond [the condition element, which is in itself an associative array]
	 * @return [boolean]       [true if pass, false if not]
	 */
	private function validate_cond_value(array $cond)
	{
		if(empty($cond)) return false;

		if(intval($cond['cc_status']) > 0)
		{
			$elements = array('min_val');

			if(intval($cond['has_options']) === 1) $elements[] = 'widget_val';

			foreach ($elements as $element)
			{
				if(is_null(\Arr::get($cond,$element,NULL))) return false;
			}

			if(intval($cond['widget_only'])===0)
			{
				foreach (array('min_val') as $el)
				{
					if(intval(\Arr::get($cond,$el,0)) === 0) return false;
				}
			}
			else
			{
				if(!preg_match('/\w+?/i',$cond['widget_val'])) return false;
			}

		}

		if((intval($cond['cc_status']) === 0)&&(intval($cond['is_critical']) > 0)) $this->_logger->handle_error('Caution: (Validation related) Critical Condition Type ID#'.$cond['cond_type_id'].' has status set to n/a.',2);



		return true;
	}

	/**
	 * [get_safe_range - handles the check for a condition type that is using a range. if the max value range is invalid it uses the low part of the range as the high value as well]
	 * @param  array  $condition [a condition array containing the values needed to do the work]
	 * @return array            [returns the valid low and high part of a range]
	 */
	private function get_safe_range(array $condition)
	{

		$low = intval($condition['min_val']);
		$high = intval($condition['max_val']);

		if($low > $high) $high = $low;

		return array($low,$high);
	}

	/**
	 * [get_tasks - get all tasks contained in the given property]
	 * @param  mixed $task_group 	the task group holding the relevant tasks
	 * @param  string $task_prop  	the task property holding said tasks. By default this is '_tasks', but can also be used to retrieve nursery tasks.
	 * @return array             	an array of tasks
	 */
	private function get_tasks($task_group_cat=NULL, $task_prop='_tasks')
	{
		if(is_null($task_group_cat)) $this->_logger->handle_error("Warning: task_group_cat cannot be null.");
		if(empty($this->{$task_prop})) $this->_logger->handle_error("No viable tasks found: aborting schedule creation");

		if(!isset($this->{$task_prop}[$task_group_cat]) || (isset($this->{$task_prop}[$task_group_cat]) && empty($this->{$task_prop}[$task_group_cat])))
				$this->_logger->handle_error('No viable tasks found: aborting schedule creation');

		return $this->{$task_prop}[$task_group_cat];
	}

	/**
	 * [generate - starts the schedule creation process]
	 * @param integer $start_offset - an integer timestamp that can be passed to offset the start of
	 * @param [type] [varname] [description]
	 * @return [void] [return nothing]
	 */
	private function generate_schedule($start_offset=NULL,$main_tasks=NULL)
	{


		$aux_args = (!is_null($start_offset))? array('n_trans_date'=>$start_offset) : NULL;

		#default action grab all the current phases tasks
		if(is_null($main_tasks)) $main_tasks = $this->get_tasks($this->_taskgroup_cat);

		foreach ($main_tasks as $t_id => $task)
		{
			$allowed_date_types = array(REL_NA,REL_TASK);

			if((intval($task->rel_cond_type_id)>0) && in_array($task->date_type,$allowed_date_types))
			{
				$temp_cond = \Arr::get($this->_conditions,$task->rel_cond_type_id,NULL);

				if(is_null($temp_cond)) continue;

				if(intval($temp_cond['cc_status'])>0)
				{
					if(($t_stamps = $this->fetch($task,$aux_args)) !== false)
					{
						$this->set_dependant($task,$t_stamps); //check and set dependency
						$this->process_timestamps($t_stamps,$task,TASK_TYPE);
					}
					else
					{
						$this->_logger->handle_error('Warning: (Task Related/Semi-Hardcoded Logic) ID#'.$task->rel_cond_type_id.' did not return a value from the lookup list.',1);
					}
				}

			}
			else //generic tasks
			{
				if(($t_stamps = $this->make_timestamps($task)) !== false)
				{
					$this->set_dependant($task,$t_stamps); //check and set dependency
					$this->process_timestamps($t_stamps,$task,TASK_TYPE);
				}
				else
				{
					$this->_logger->handle_error('Warning: (Task Related/Semi-Hardcoded Logic) ID(Task)#'.$task->id.' did not return a value from the lookup list.',1);
				}
			}

		}



				#any non-task linked conditions need adding to the timestamps go below -SH
				$non_task_conds = array();
				$non_task_conds = $non_task_conds + $this->get_space_conditions();

				foreach ($non_task_conds as $nt_id)
				{
					$temp_non_task_cond = \Arr::get($this->_conditions,$nt_id,NULL);

					if(is_null($temp_non_task_cond)) continue;

					if(intval($temp_non_task_cond['cc_status'])>0)
					{
						$task = new \Model_Scheduler_Task();
						$task->id = $task->rel_cond_type_id = $nt_id;

						if(($t_stamps = $this->fetch($task)) !== false)
						{
							$this->process_timestamps($t_stamps,$task,COND_TYPE);
						}
						else
						{
							$this->_logger->handle_error('Warning: (Non Task/ Purely Hardcoded) ID#'.$task->rel_cond_type_id.' did not return a useable value.',1);
						}
					}
				}


	}

	/**
	 * [generate - an alias for running both the nursery_handler and the schedule generation - #TODO: refactor the crap out of this -SH]
	 * @return Helper - returns the object itself for chaining
	 */
	public function generate()
	{
		#currently handles all logic for ading nursery time -SH
		if($this->_nursery)
		{
			$this->generate_schedule(NULL,$this->get_tasks($this->_taskgroup_cat,'_nusery_tasks'));
		}

		#generates all the normal timestamp/tasks, will use the offset if a nursery space has calculated the new offset -SH
		$this->generate_schedule($this->_nursery_offset);

		return $this;
	}

	/**
	 * [save_to_table 	grabs the saved timestamps and converts them into an sql statement then inserts them into the scheduler table]
	 * @param Model_Object - #TODO: write this out
	 * @return \Bds\Scheduler\Helper returns - #TODO: write this out
	 */
	public function save_to_table($model_class=NULL)
	{

		if(empty($model_class)) $this->_logger->handle_error('save_to_table:$model_class cannot be null.');

		$tablename = $model_class::table();
		$properties = array(); foreach($model_class::properties() as $prop=>$meta)
		{
			if(in_array($prop,array('id'))) continue;

			$properties[] = $prop;
		}


		if(empty($this->_timestamps)) $this->_logger->handle_error('Warning: Unable to find timestamps. Cannot save to database table.');

		$query = \DB::insert($tablename, $properties);

		foreach ($this->_timestamps as $timestamp=>$tasks)
		{

			foreach ($tasks as $task_label => $task)
			{
				list($t_type,$t_id) = explode('_',$task_label);

				$query->values(array(
					$this->_args_list['u_crop_id'],
					$this->_args_list['user_id'],
					$t_id,
					$task['cond_type_id'],
					$timestamp,
					strtotime('now'),
					strtotime('now'),
				));
			}

		}

		$query->execute();


		return true;
	}

	/**
	 * [get_start_date provides functionality to retrieve the 'start' date, or a point in relative time to produce calculations from]
	 * @return [int] [integer time-stamp]
	 */
	private function get_start_baseline()
	{
		$year = date('Y');
		$baseline = 0;

		if(isset($this->_args_list['man_year']) && !empty($this->_args_list['man_year']) && is_int($this->_args_list['man_year']))
		{
			$year = $this->_args_list['man_year'];
		}

		$baseline = mktime(0,0,0,1,intval($this->_hz->frost_end_50),intval($year));

		#is this date past our current date
		#if no manual date provided default to next year
		if(intval($baseline) < intval(strtotime('now')))
		{
			$year = (intval($year)+1);
			$baseline = mktime(0,0,0,1,intval($this->_hz->frost_end_50),intval($year));
		}

		return ($this->_ugz_props->indoor_outdoor === 'outdoor')? $baseline : strtotime('now');
	}

	/**
	 * [get_start_avg return the average of the start date]
	 * @return [mixed] [returns an integer time-stamp average of the start date or false if not found]
	 */
	private function get_start_avg()
	{

			$start_date = $this->_start_date;
			$first = (is_array($start_date))? array_shift($start_date) : $this->_date_baseline;
			$last = (is_array($start_date))? array_pop($start_date) : $this->_date_baseline;

			if(intval($first) === intval($last)) return $first;

			if(intval($last) > intval($first))
			{
				$diff = intval($last) - intval($first);
				$diff = round(intval($diff)/2);
				$avg = intval($last) - intval($diff);

				return $avg;
			}

			return false;
	}

	/**
	 * [get_phase grabs the phase from the user crop and returns it as a nice nerd-friendly number (DOWN WITH STRINGS)]
	 * @return [int] [returns an int representing the state/phase of the crop/space]
	 */
	private function get_phase()
	{
		if(empty($this->_args_list)) $this->_logger->handle_error("get_phase: _args_list cannot be empty.");

		if(!isset($this->_args_list['phase'])) $this->_logger->handle_error("get_phase: _args_list['phase'] must be set.");

		$dtm_phase = 0;

		#TODO: make select DRY using an anon func -SH

		switch($this->_category)
		{
			case 1:
			case 3:
			case 7:
			case 8:
				switch($this->_args_list['phase'])
				{
					case 'plant seeds in the garden':
						if(isset($this->_args_list['save_seeds']) && ($this->_args_list['save_seeds'] === true))
						{
							$dtm_days = DTM_SEED_SAVE;
							$dtm_phase = SEED_SAVING_DS;
						}
						else
						{
							$dtm_days = DTM_DS;
							$dtm_phase = DIRECT_SEED;
						}
					break;

					case 'start plants indoor from seed':
					case 'start seedlings indoors for transplant':
						if(isset($this->_args_list['save_seeds']) && ($this->_args_list['save_seeds'] === true))
						{
							$dtm_days = DTM_SEED_SAVE;
							$dtm_phase = SEED_SAVING_TP;
						}
						else
						{
							$dtm_days = DTM_TP;
							$dtm_phase = TRANSPLANT;
						}

						// tell the system that we will be using a nursery space -SH
						$this->_nursery = true;
						$this->_nursery_alt_phase = SEED;
						$this->_nursery_alt_dtm = DTM_SEED_GD;
					break;

					case 'buy seedlings':
						if(isset($this->_args_list['save_seeds']) && ($this->_args_list['save_seeds'] === true))
						{
							$dtm_days = DTM_SEED_SAVE;
							$dtm_phase = SEED_SAVING_TP;
						}
						else
						{
							$dtm_days = DTM_SEED;
							$dtm_phase = TRANSPLANT;
						}
					break;
				}
			break;
		}

		#setup the define alias for the DTM_DAYS
		if(isset($dtm_days)) $this->_dtm_days = $dtm_days;

		return $dtm_phase;
	}

	/**
	 * [get_phase_maturity 	- 	check the current object phase and return the appropriate condition value if found. Throws an exception if needed condition not found on crop.
	 * 							also sets the DTM_DAYS constant
	 * @return [array] [returns the condition type array from the crop condition if found. Else an exception is thrown.]
	 */
	private function get_phase_maturity()
	{

		if(! $this->_current_phase) $this->_logger->handle_error('Current Phase not set. Unable to continue.');

		$dtm = array();

		switch($this->_current_phase)
		{
			case SEED:
				$dtm = $this->get_condition(DTM_SEED);
			break;

			case TRANSPLANT:
				$dtm = $this->get_condition(DTM_TP);
			break;

			case DIRECT_SEED:
				$dtm = $this->get_condition(DTM_DS);
			break;

			case SEED_SAVING_DS:
			case SEED_SAVING_TP:
				$dtm = $this->get_condition(DTM_SEED_SAVE);
			break;

		}

		return $dtm;
	}

	/**
	 * [get_taskgroup_cat takes the current task groups into consideration and looks for anything that will define it as either Group A(Veg/Herb/Etc) or Group B(Fruit/Nut)]
	 * @return [int] [the category the taskgroup belongs to]
	 */
	private function get_taskgroup_cat()
	{

		$cat = false;

		switch($this->_category)
		{
			case 1:
			case 3:
			case 7:
			case 8:
				$cat =  TGROUP_A;
			break;

			case 2:
				$cat = TGROUP_B;
			break;

			default:
				$cat = TGROUP_A;
			break;
		}

		return $cat;

	}

	/**
	 * [fetch - is basically a lookup table for each condition type and hopefully returns a timestamp]
	 * @param  Model_Scheduler_Task $task - the task that needs to be handled in a certain way
	 * @param  mixed $aux_args - pass along any condition specific overrides to change calculation behaviour
	 * @return mixed - returns false when no lookup is found, otherwise returns a timestamp for a single date or an array containing a range of values (one per week)
	 */
	private function fetch(\Model_Scheduler_Task $task=NULL, $aux_args=NULL)
	{
		if(is_null($task)) $this->_logger->handle_error('Task object is missing.');

		$low  = 0;
		$high = 0;
		$rel_range_low = (intval($task->from_rel_number)!==0)?$task->from_rel_number:0;
		$rel_range_high = ((intval($task->is_range)>0)&&(intval($task->to_rel_number)!==0))?$task->to_rel_number:0;

		$cond_type_id = $task->rel_cond_type_id;

		switch($cond_type_id)
		{
			case CYCLE_START_A:

				$base_time = $this->_date_baseline;
				$gd_days_low = 0;
				$gd_days_high = 0;

				$start_cond = $this->get_condition(CYCLE_START_A);

				if(empty($start_cond)) return array();

				#determine widget value
				if($this->_ugz_props->indoor_outdoor === 'outdoor')
				{
					if(preg_match('/hardiest_plant/i',$start_cond['widget_val']))
					{
						$low  = $base_time + (($gd_days_high*DAY)* -1) + (-6*WEEK);
						$high = $base_time + (($gd_days_low*DAY)* -1) + (-4*WEEK);
					}
					else if(preg_match('/hardy_plant/i',$start_cond['widget_val']))
					{
						$low  = $base_time + (($gd_days_high*DAY)* -1) + (-3*WEEK);
						$high = $base_time + (($gd_days_low*DAY)* -1) + (0*WEEK);
					}
					else //frost_sensitive
					{
						$low  = $base_time + (($gd_days_low*DAY)* -1) + (1*WEEK);
						$high = $base_time + (($gd_days_high*DAY)* -1) + (1*WEEK);
					}
				}
				else
				{
					$low  = $base_time + (($gd_days_low*DAY)* -1);
					$high = $base_time + (($gd_days_high*DAY)* -1);
				}

				$this->_start_date = $this->week_range_to_array($low,$high);

				#if we have a manual date override an use that instead
				if($this->_man_date) $this->_start_date = $this->_man_date;

				#check to see if there is an override date for when using a nursery -SH
				if(!is_null($aux_args))
				{
					if(isset($aux_args['n_trans_date']) && !empty($aux_args['n_trans_date']))
					{
						if($this->validate_manual_date($aux_args['n_trans_date'])) $this->_start_date = $aux_args['n_trans_date'];
					}
				}

				#setup DTM timing
				$start_date = $this->get_task_timestamp($this->_start_date,TS_FIRST);
				list($dtm_min,$dtm_max) = $this->get_safe_range($this->get_phase_maturity());

				$dtm_adjust = 0;

				if(intval($dtm_min) === intval($dtm_max)) $dtm_avg = $start_date + intval($dtm_min*DAY);
				else
				{
					$weeks = $this->week_range_to_array(($start_date + intval(($dtm_min*DAY) + ($dtm_adjust * -1))),($start_date + intval(($dtm_max*DAY) + ($dtm_adjust * -1))));
				}

				if(isset($weeks) && !empty($weeks)) $dtm_val = $weeks;
				else $dtm_val = $dtm_avg;

				$this->_tasks_dependancies['cond_'.$this->_dtm_days] = $dtm_val;

				return $this->_start_date;

			break;

			case GERMINATION:

				$avg = $this->get_task_timestamp($this->_start_date);

				$germ_cond = $this->get_condition(GERMINATION);
				if(empty($germ_cond)) return array();

				$low = intval($avg) + (intval($germ_cond['min_val']) * DAY);
				$high = intval($avg) + (intval($germ_cond['max_val']) * DAY);



				$temp_range = $this->week_range_to_array($low,$high);

				if(!isset($this->_tasks_dependancies['cond_'.GERMINATION])||is_bool($this->_tasks_dependancies['cond_'.GERMINATION]))
				{
					$this->_tasks_dependancies['cond_'.GERMINATION] = $temp_range;
				}

				return $temp_range;

			break;

			case THINNING_SEEDLINGS_SEED:
			case THINNING_SEEDLINGS_DS:

				$germ_date = $this->get_task_timestamp($this->get_dependant('cond_'.GERMINATION,$this->_start_date));

				if(empty($germ_date)) return array();

				$week_num = (intval($rel_range_low)>0)?$rel_range_low:2;

				$new_date = round(intval($germ_date) + (intval($week_num)*WEEK));

				$this->_tasks_dependancies['cond_'.$cond_type_id] = $new_date;

				return $new_date;

			break;

			case POTTING_UP:

				$potting_val = $this->get_task_timestamp($this->get_dependant('cond_'.THINNING_SEEDLINGS_SEED));

				if(empty($potting_val)) return array();

				$week_num = (intval($rel_range_low)>0)?$rel_range_low:2;

				$potting_date = round(intval($potting_val) + (intval($week_num)*WEEK));

				$this->_tasks_dependancies['cond_'.POTTING_UP] = $potting_date;

				return $potting_date;

			break;

			case FEEDING_SEED:
				$feed_seed = $this->get_dependant('cond_'.POTTING_UP);
				if(!empty($feed_seed)) return $feed_seed;
				return array();
			break;

			case FEEDING:

				$time_germ = $this->get_dependant('cond_'.GERMINATION);

				if(empty($time_germ) && (!in_array($this->_current_phase,array(TRANSPLANT)))) return array();

				$start_avg = $this->get_task_timestamp(((in_array($this->_current_phase,array(TRANSPLANT)))? $this->_start_date : $time_germ),TS_FIRST);
				$feed_cond = $this->get_condition(FEEDING);
				$dtm_avg = 0;
				$dtm_set = false;


					list($dtm_min,$dtm_max) = $this->get_safe_range($this->get_phase_maturity());

					if(in_array($this->_current_phase,array(DIRECT_SEED)))
					{
						$temp_germ = $this->get_task_timestamp($time_germ);

						$start_avg = $temp_germ; //start from the time of germination -SH

						$temp_start = $this->get_task_timestamp($this->_start_date,TS_FIRST);

						if((intval($temp_germ)>0) && (intval($temp_start)>0))
						{
							$dtm_adjust = intval($temp_germ) - intval($temp_start);
						}
						else
						{
							$this->_logger->handle_error($cond_type_id.': illegal values present on condition, numeric values needed.');
							return array();
						}

					}
					else $dtm_adjust = 0;

					if(intval($dtm_min) === intval($dtm_max)) $dtm_avg = $start_avg + intval($dtm_min*DAY);
					else
					{
						$weeks = $this->week_range_to_array(($start_avg + intval(($dtm_min*DAY) + ($dtm_adjust * -1))),($start_avg + intval(($dtm_max*DAY) + ($dtm_adjust * -1))));
						$dtm_avg = $this->get_task_timestamp($weeks,TS_AVG);
					}

				if(preg_match('/heavy_feeder/i',$feed_cond['widget_val']))
				{
					$scalar = 2;
				}
				else if(preg_match('/medium_feeder/i',$feed_cond['widget_val']))
				{
					$scalar = 4;
				}
				else //no feeding
				{
					$scalar = 0;
				}

				$start_avg = $start_avg + intval((2*WEEK));

				return $this->week_range_to_array($start_avg,$dtm_avg,$scalar);

			break;

			case PRUNE_SUCKER:

				$pr_sck_cond = $this->get_condition(PRUNE_SUCKER);
				if(empty($pr_sck_cond)) return array();
				$range = array();

				if(\Str::is_json($pr_sck_cond['widget_val']))
				{
					$options = json_decode($pr_sck_cond['widget_val']);

					$lfd = $this->_date_baseline;
					$ffd = intval($lfd) + intval($this->_hz->growing_days)*DAY;
					$mid = round((intval($lfd)+intval($ffd))/2);

					if(is_array($options)&&!empty($options))
					{
						foreach ($options as $option)
						{

							if(preg_match('/This crop may need pruning or suckering/i',$option))
							{
								$range[] = $mid;
							}
							else if(preg_match('/This crop needs spring pruning/i',$option))
							{
								$range[] = round(intval($lfd)+((4*WEEK) * -1));
							}
							else if(preg_match('/This crop needs fall pruning/i',$option))
							{
								$range[] = round(intval($ffd)+(4*WEEK));
							}

						}
					}


				}

				return $range;
			break;

			case COMMON_PROBLEMS:
			case ANIMAL_INVOLVEMENT:
			case MULCHING:
				$mulching = false;
				if(in_array($this->_current_phase,array(DIRECT_SEED,SEED_SAVING_DS))) $mulching = $this->get_dependant('cond_'.GERMINATION);
				else $mulching = $this->_start_date;

				if(empty($mulching)) return array();

				return $mulching;
			break;

			case WATER_WEEKLY:

				$start_avg = $this->get_task_timestamp($this->_start_date,TS_FIRST);

				$dtm = $this->get_dependant('cond_'.$this->_dtm_days);

				if(empty($dtm)) return array();

				$last = (is_array($dtm))? array_pop($dtm) : $dtm;
				return $this->week_range_to_array($start_avg,$last);
			break;

			case SEASON_EXTENDER:
				$start_avg = $this->get_task_timestamp($this->_start_date,TS_FIRST);
				$ffd = round(intval($this->_date_baseline) + intval($this->_hz->growing_days)*DAY);
				$extender_dates = array();

				// 1 month before TP/DS date
				$extender_dates[] = round(intval($start_avg) + ((4*WEEK)* -1));

				// 1 month before FFD date
				$extender_dates[] = round(intval($ffd) + (WEEK* -1));

				return $extender_dates;
			break;

			case PEST_DISEASE:

				$dtm = $this->get_dependant('cond_'.$this->_dtm_days);

				if(empty($dtm)) return array();

				$last = (is_array($dtm))? array_pop($dtm) : $dtm;

				if(in_array($this->_current_phase,array(DIRECT_SEED,SEED_SAVING_DS))) $pest_dis_date = $this->get_task_timestamp($this->get_dependant('cond_'.GERMINATION));
				else $pest_dis_date = $this->get_task_timestamp($this->_start_date,TS_FIRST);
				// $pest_dis_date = intval(\Arr::average($this->_start_date));

				return $this->week_range_to_array((intval($pest_dis_date)+intval(2*WEEK)),$last,2);

			break;

			case HARVEST:

				$dtm = $this->get_dependant('cond_'.$this->_dtm_days);

				if(empty($dtm)) return array();

				$first = (is_array($dtm))? array_shift($dtm) : $dtm;
				$last = (is_array($dtm))? array_pop($dtm) : $dtm;

				list($h_min,$h_max) = $this->get_safe_range($this->get_condition(HARVEST));

				$hf_cond = $this->get_condition(HARVEST_FREQ);

				if(\Str::is_json($hf_cond['widget_val'])) $options = json_decode($hf_cond['widget_val']);
				else $options = array();


				$hf_cond = $this->get_condition(HARVEST_FREQ);

				if(\Str::is_json($hf_cond['widget_val'])) $options = json_decode($hf_cond['widget_val']);
				else $options = array();

				$h_last = round(intval($last)+intval($h_max*DAY));

				$temp_min = (intval($first)+intval((WEEK)* -1));
				$temp_max = (empty($options))? $h_last : $temp_min; //only itterates once if the range values equal. if the options array is empty it will default to weekly notices

				//check to see if we will be sending weekly notices depeding on saved option values on FF.
				if(is_array($options) && !empty($options))
				{
					foreach ($options as $option)
					{
						if(preg_match('/This crop can be harvested every 7-14 days/i',$option)
							||preg_match('/This crop can be harvested every 1-7 days/i',$option))
						{
							$temp_max = $h_last;
							break;
						}
					}
				}
				else return array();


				return $this->week_range_to_array($temp_min,$temp_max);

			break;

			case FLOWERING:

				if(in_array($this->_current_phase,array(SEED_SAVING_DS))) $start = $this->get_dependant('cond_'.GERMINATION);
				else $start = $this->_start_date;

				if(empty($start)) return array();

				$start = $this->get_task_timestamp($start,TS_FIRST);

				$flower_start = intval($start + (WEEK*3));

				$flower_end = intval($flower_start + (WEEK*4));

				return $this->week_range_to_array($flower_start,$flower_end);

			break;

			case ROGUING:
				$time_germ = $this->get_dependant('cond_'.GERMINATION, $this->_start_date);

				if(empty($start) && (!in_array($this->_current_phase,array(TRANSPLANT)))) return array();

				$start_avg = $this->get_task_timestamp(((in_array($this->_current_phase,array(TRANSPLANT)))? $this->_start_date : $time_germ),TS_FIRST);
				$dtm_set = false;

				if(!isset($this->_tasks_dependancies['cond_'.$this->_dtm_days]) || is_bool($this->_tasks_dependancies['cond_'.$this->_dtm_days]))
				{
					list($dtm_min,$dtm_max) = $this->get_safe_range($this->get_phase_maturity());

					//if is direct seed will need to adjust for germination shifting the harvest days a little -SH
					if(in_array($this->_current_phase,array(DIRECT_SEED)))
					{
						$temp_germ = $this->get_task_timestamp($time_germ);
						$temp_start = $this->get_task_timestamp($this->_start_date);

						$dtm_adjust = $temp_germ - $temp_start;
					}
					else $dtm_adjust = 0;

					if(intval($dtm_min) === intval($dtm_max)) $dtm_avg = $start_avg + intval($dtm_min*DAY);
					else
					{
						$weeks = $this->week_range_to_array(($start_avg + intval(($dtm_min*DAY) + ($dtm_adjust * -1))),($start_avg + intval(($dtm_max*DAY) + ($dtm_adjust * -1))));
						$dtm_avg = $this->get_task_timestamp($weeks,TS_AVG);
					}
				}
				else
				{
					$dtm_avg = $this->get_task_timestamp($this->get_dependant('cond_'.$this->_dtm_days));
					$dtm_set = true;
				}

				if($dtm_set === false)
				{
					if(isset($weeks) && !empty($weeks)) $dtm_val = $weeks;
					else $dtm_val = $dtm_avg;

					$this->_tasks_dependancies['cond_'.$this->_dtm_days] = $dtm_val;
				}

				return $this->week_range_to_array($start_avg,$dtm_avg,2);

			break;

			case OVERWINTERING_A:
			case OVERWINTERING_B:
				preg_match('/(\d{1,2})\w/',$this->_hz->hardiness,$hz_m);

				$zone = $hz_m[count($hz_m)-1];

				if(intval($zone)>0)
				{
					switch($cond_type_id)
					{
						case OVERWINTERING_A:
							if((intval($zone)>=7)&&(intval($zone)<=10)) return $this->get_task_timestamp($this->get_dependant('cond_'.$this->_dtm_days));
							else return array();
						break;

						case OVERWINTERING_B:
							if((intval($zone)>=2)&&(intval($zone)<=6)) return $this->get_task_timestamp($this->get_dependant('cond_'.$this->_dtm_days));
							else return array();
						break;
					}
				}


			break;

			case DTM_SEED_SAVE:

				$start_avg = $this->get_task_timestamp($this->_start_date,TS_AVG);

				if(empty($start_avg)) return array();

				list($h_min,$h_max) = $this->get_safe_range($this->get_condition(DTM_SEED_SAVE));

				return $this->week_range_to_array(intval($start_avg + ($h_min*DAY)),intval($start_avg + ($h_max*DAY)));

			break;

			default:

				if((intval($task->rel_task_id)>0))
				{
					$temp_task = \Arr::get($this->_tasks[$this->_taskgroup_cat],$task->rel_task_id,NULL);

					if(!is_null($temp_task))
					{
						if(($is_dep = $this->is_depended_on($temp_task)) !== false)
						{
							$dep = $this->get_task_timestamp($this->get_dependant($is_dep.'_'.$temp_task->{($is_dep === 'cond' ? 'rel_cond_type_id' : 'id')}));

							if(intval($dep)>0)
							{
								if(intval($rel_range_high)!==0)
								{
									#the range function always takes the lower value first so we adjust for this if needed
									list($temp_low,$temp_high) = ((intval($rel_range_high)>intval($rel_range_low))? array($rel_range_low,$rel_range_high) : array($rel_range_high,$rel_range_low));

									if(intval($rel_range_low)!==0) return  $this->week_range_to_array((intval($dep + (intval($temp_low)*WEEK))),(intval($dep + (intval($temp_high)*WEEK))));
								}
								else
								{
									if(intval($rel_range_low)!==0) return (intval($dep + (intval($rel_range_low)*WEEK)));
								}
							}
						}

					}

				}

				return $this->_start_date;
			break;

		}

		return false;
	}

	/**
	 * [get_date_type_start returns the proper start date for the given type]
	 * @param  integer $date_type 	[an interger representing the date type to use]
	 * @return mixed    			[the integer timestamp if required or false if non needed (ie: date type 5 does not return a start date as it does not need one)]
	 */
	private function get_date_type_start($date_type=NULL)
	{
		if(is_null($date_type)) $this->_logger->handle_error('get_date_type_start: $date_type cannot be null.');

		$start = false;
		switch(intval($date_type))
		{
			case REL_CAL:
				$start = mktime(0,0,0,1,1,intval(date('Y')));
			break;

			case REL_LFD:
				$start = $this->_date_baseline;
			break;

			case REL_CROPSTART:
				$start = $this->_start_date;
			break;

		}

		return $start;
	}


	/**
	 * [get_task_date_range retrieves the valid range from a given task]
	 * @param  [Model_Structure_Task] $task [the task object, used to determine the return value]
	 * @return [mixed]            			[returns an array with the range(of integer timestamps) if one is found otherwise it returns false]
	 */
	private function get_task_date_range($task=NULL)
	{
		if(is_null($task)) $this->_logger->handle_error('get_task_date_range: $task cannot be null.');

		$range = false;
		switch(intval($task->date_type))
		{
			case REL_CAL:
			case REL_LFD:
			case REL_CROPSTART:
				$min_val = $task->from_rel_number;
				$max_val = ((intval($task->is_range)>0)&&(intval($task->to_rel_number)!==0))? $task->to_rel_number : $min_val;
			break;

			case NON_REL_CAL:
				$min_val = $task->from_rel;
				$max_val = (intval($task->is_range)>0)? $task->to_rel : $min_val;
			break;

		}

		if(isset($min_val)&&isset($max_val)) $range = array($min_val,$max_val);

		return $range;
	}

	/**
	 * [make_timestamps - an alternative to the fetch method, but will only work with generic tasks and not tasks attached to condition types (those require more code-lovin)]
	 * @param  [Model_Task] $task [the task object to generate timestamps from]
	 * @return [mixed] [returns false when no lookup is found, otherwise returns a time-stamp for a single date or an array containing a range of values (one per week)]
	 */
	private function make_timestamps($task=NULL)
	{
		if(is_null($task)) $this->_logger->handle_error('make_timestamps: $task cannot be null.');

		$weeks = array();

		$start = $this->get_task_timestamp($this->get_date_type_start($task->date_type));
		list($min,$max) = $this->get_task_date_range($task);

		#check to see if low to high is in the right order
		list($temp_min,$temp_max) = (intval($max)>intval($min))? array($min,$max) : array($max,$min);

		$scalar = ((intval($task->is_repeatable)>0)&&(intval($task->rep_units)>0))? intval($task->rep_units) : 1;

		if($start === false) $weeks = $this->week_range_to_array($temp_min,$temp_max,$scalar);
		else
		{
			$weeks = $this->week_range_to_array(intval(($start + ($temp_min*WEEK))),intval(($start + ($temp_max*WEEK))),$scalar);
		}

		return $weeks;
	}

	/**
	 * [process_timestamps takes mixed variable of a single/range of time-stamp(s) and adds them timestamps property]
	 * @param  [mixed] $t_stamps [either a single integer time-stamp or an array of integer timestamps]
	 * @param  [int] $id       [should be the id to save with the time-stamp(s)]
	 * @param  [type] $type     [whether this is a generic task or condition generated time]
	 * @return [type]           [description]
	 */
	private function process_timestamps($t_stamps=NULL,$task=NULL,$type=NULL)
	{
		if(is_null($t_stamps)) $this->_logger->handle_error('variable $t_stamps cannot be null.');
		if(is_null($task)) $this->_logger->handle_error('variable $task cannot be null.');
		if(is_null($type)) $this->_logger->handle_error('variable $type cannot be null.');

		$prefix = ($type === COND_TYPE)? 'cond_':'task_';

		$tasks = $this->flatten_tasks($task);

		if(is_array($t_stamps))
		{
			foreach ($t_stamps as $t_stamp)
			{
				if(!empty($tasks))
				{
					foreach ($tasks as $t)
					{
						$this->_timestamps[$t_stamp][$prefix.$t->id] = $this->format_task_for_timestamp($t);

					}
				}
				else
				{
					$this->_timestamps[$t_stamp][$prefix.$task->id] = $this->format_task_for_timestamp($task);
				}
			}

		}
		else
		{
			if(intval($t_stamps) !== 0)
			{

				if(!empty($tasks))
				{
					foreach ($tasks as $t)
					{
						$this->_timestamps[$t_stamps][$prefix.$t->id] = $this->format_task_for_timestamp($t);
					}
				}
				else
				{
					$this->_timestamps[$t_stamps][$prefix.$task->id] = $this->format_task_for_timestamp($task);
				}

			}
		}
	}


	/**
	 * [format_task_for_timestamp description]
	 * @param  [Model_Task] $task [task object to format for for time-stamp inclusion]
	 * @return [array]       [array with formatted data for time-stamp inclusion]
	 */
	private function format_task_for_timestamp($task)
	{
		$array = array();

		$name = "";

		if(intval($task->rel_cond_type_id)>0)
		{

			if(!is_null(($cond = \Arr::get($this->_conditions,$task->rel_cond_type_id,NULL))))
			{
				$name = (isset($task->name)&&preg_match('/\w+?/i',$task->name))? $task->name : $cond['name'];
				$cond_name = $cond['name'];
				$id = $cond['id'];
			}
			else
			{
				$name = $task->name;
			}

		}
		else
		{
			$name = $task->name;
		}

		$array['name'] = $name;
		if(isset($id)) $array['cond_type_id'] = $id;
		if(isset($cond_name)) $array['cond_name'] = $cond_name;

		return $array;
	}

	/**
	 * [get_task_timestamp handles formatting the timestamps and how we process them (average,first in range or last in range)]
	 * @param  [mixed] $value 	[the single integer timestamp or array of timestamps]
	 * @param  [integer] $opt   [integer constant of the option to use]
	 * @return [mixed]        	[returns the timestamps needed]
	 */
	private function get_task_timestamp($value=NULL,$opt=TS_AVG)
	{
		if(is_null($value)) $this->_logger->handle_error('get_task_timestamp: $value cannot be null.');
		if(is_null($opt)) $this->_logger->handle_error('get_task_timestamp: $opt cannot be null.');

		if(is_array($value)&&!empty($value))
		{
			$temp = $value;

			switch($opt)
			{
				case TS_AVG:
					try
					{
						$value = intval(\Arr::average($temp));
					}
					catch(\ErrorException $e)
					{
						throw new \ErrorException($e->getMessage());
						// die($e->getMessage());
					}
				break;

				case TS_FIRST:
					$value = (is_array($temp))? array_shift($temp) : $temp;
				break;

				case TS_LAST:
					$value = (is_array($temp))? array_pop($temp) : $temp;
				break;
			}
		}

		return $value;
	}

	/**
	 * [week_range_to_array takes two time-stamps as a range and returns an array ]
	 * @param  [int] $low  [an integer time-stamp for the low part of the range]
	 * @param  [type] $high [an integer time-stamp for the high part of the range]
	 * @return [array]       [returns an array of the timestamps in weekly intervals]
	 */
	private function week_range_to_array($low,$high,$scalar=1)
	{
		$weeks = array();

		if(intval($low) !== 0 && intval($high) !== 0)
		{

			if(intval($low) === intval($high))
			{
				$weeks[] = $this->apply_global_scaling($low);
			}
			else
			{

				if(intval($scalar)>0) // this is for jumping the week to week range not the global scale of the schedule
				{
					for ($i=$low; $i < $high; $i=$i+(WEEK*intval($scalar)))
					{
						$weeks[] = $this->apply_global_scaling($i);
					}
				}

			}

		}
		return $weeks;
	}

	/**
	 * apply_global_scaling  	this applies any global scaling present in the object
	 * @param integer 			timestamp
	 * @return integer 			scaled timestamp
	 */
	private function apply_global_scaling($val)
	{
		return ceil(floatval($val)*floatval($this->_scaler));
	}

	/**
	 * [get_space_conditions - grab the conditions needed for the current space (if any). To be used with validation and time-stamp generation.]
	 * @return [array] [an array of integers representing the condition types to use to the current space-type.]
	 */
	private function get_space_conditions()
	{
		$space_conditions = array();

		#add conditions per space type to check
		switch ($this->_ugz->space_type)
		{
			case SQUARE_FOOT_GARDEN:
				if(in_array($this->_current_phase,array(TRANSPLANT,DIRECT_SEED)))
				{
					$space_conditions[] = 26;
					$space_conditions[] = 80;
				}
			break;

			case GARDEN_BEDS:
				if(in_array($this->_current_phase,array(TRANSPLANT,DIRECT_SEED,SEED_SAVING_DS)))
				{
					$space_conditions[] = 27;
					$space_conditions[] = 82;
					$space_conditions[] = ($this->_current_phase === SEED_SAVING_DS)? 159 : 138;
					$space_conditions[] = ($this->_current_phase === SEED_SAVING_DS)? 159 : 83;
					$space_conditions[] = ($this->_current_phase === SEED_SAVING_DS)? 181 : 142;
				}
			break;

			// case WATER_GARDEN:
			// 	$space_conditions[] = GREENHOUSE_DAYS;
			// break;

			case HYDROPONIC_GARDENS:
				if(in_array($this->_current_phase,array(TRANSPLANT)))
				{
					$space_conditions[] = 33;
				}
			break;

			case AQUAPONIC_GARDENS:
				if(in_array($this->_current_phase,array(TRANSPLANT)))
				{
					$space_conditions[] = 31;
				}
			break;

			case CONTAINER_GARDENS:
				if(in_array($this->_current_phase,array(TRANSPLANT)))
				{
					$space_conditions[] = 25;
					$space_conditions[] = 36;
				}

				if(in_array($this->_current_phase,array(TRANSPLANT,DIRECT_SEED)))
				{
					$space_conditions[] = 80;
				}
			break;
		}

		return $space_conditions;
	}

	public function get_all_timestamps()
	{
		return $this->_timestamps;
	}

	public function get_logger()
	{
		return $this->_logger;
	}

	/**
	 * get_valid_man_date 	get and returns a manual date from the args_list if present
	 * @return mixed 		return false if not valid date present (ie: go about your merry scheduling ways)
	 */
	private function get_valid_man_date()
	{
		if(isset($this->_args_list['man_date']) && !empty($this->_args_list['man_date']) && $this->validate_manual_date($this->_args_list['man_date']))
		{
			return $this->_args_list['man_date'];
		}
		else return false;
	}


	/**
	 * [validate_manual_date 		takes in what we hope is an integer unix timestamp and validates it
	 * @param  integer $man_date 	the integer to be validated
	 * @return boolen           	returns true if valid, false if not
	 */
	private function validate_manual_date($man_date=NULL)
	{
		if(is_null($man_date)) $this->_logger->handle_error('validate_manual_date:$man_date cannot be null');

		try
		{
			list($month,$day,$year) = explode('-',date('m-d-Y',$man_date));
		}
		catch(\HelperException $e)
		{
			return false;
		}

		return checkdate(intval($month),intval($day),intval($year));

	}

}