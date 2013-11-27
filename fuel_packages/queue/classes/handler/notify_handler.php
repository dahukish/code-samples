<?php

namespace Queue\Handler;

/**
 * An attempt at trying to control the format of the return data
 */
class Notify_Handler_Result implements \Queue\QueueDataResultInterface
{
	/**
	 * $rows: the local variable that will hold the results of the datahandler query
	 * @var array
	 */
	private $rows = null;

	/**
	 * __construct
	 * @param array $rows result data from the datahandler
	 */
	public function __construct(array $rows)
	{
		if(empty($rows)) throw new \Exception("Error Processing Request: rows must not be empty");

		$this->rows = $rows;
	}

	/**
	 * get_rows: return the rows
	 * @return array
	 */
	public function get_rows()
	{
		return $this->rows;
	}

	/**
	 * get_row_body: helper function to retrieve the body column for a given row
	 * @param  array  $row the row to extract the body from
	 * @return mixed
	 */
	public function get_row_body(array $row)
	{
		return $this->get_col($row,'body');
	}

	/**
	 * get_row_type: helper function for retrieve the type column for a given row
	 * @param  array  $row the row to extract the type from
	 * @return mixed
	 */
	public function get_row_type(array $row)
	{
		return $this->get_col($row,'type');
	}

	/**
	 * get_col: getter function to retrieve specific columns from a given row
	 * @param  array  $row      the row to extract the column from
	 * @param  string $col_name the column to search for
	 * @return mixed
	 */
	private function get_col(array $row, $col_name)
	{
		if(!is_string($col_name)
			||(is_string($col_name) && !preg_match('/\w+/i', $col_name)))
				throw new \Exception("Error Processing Request: col_name must contain a valid string");

		if(!isset($row[$col_name]))
			throw new \Exception(sprintf("Error Processing Request: column %s not found in row",$col_name));

		if(isset($row[$col_name])&&empty($row[$col_name]))
			throw new \Exception(sprintf("Error Processing Request: %s cannot be empty or does not contain valid data",$col_name));

		return $row[$col_name];
	}
}

/**
 * This class handles grabbing on \Orm\Query result and return it to the queue service
 */
class Notify_Handler implements \Queue\QueueDataInterface
{
	/**
	 * $orm_query the orm dependency to handle model queries
	 * @var Orm\Query
	 */
	private $orm_query = null;

	/**
	 * $pretty_filter a closure object that contains filtering code (provided by \Orm\Bds_Query_Helper::make_pretty_cols)
	 * @var Closure
	 */
	private $pretty_filter = null;

	/**
	 * $relations an optional store for any orm based relations that will be added to the query before execution
	 * @var array
	 */
	private $relations = null;

	/**
	 * $conditions an optional store for any orm based conditions that will be added to the query before execution
	 * @var array
	 */
	private $conditions = null;

	/**
	 * $queue_type a class-wide reference to the type of queue data it will become
	 * @var string
	 */
	private $queue_type = 'crop_notify';

	/**
	 * __construct
	 * @param OrmQuery $orm_query
	 * @param Closure  $pretty_filter
	 * @param array   $relations
	 * @param array   $conditions
	 */
	public function __construct(\Orm\Query $orm_query, \Closure $pretty_filter, $relations=null, $conditions=null)
	{
		$this->orm_query = $orm_query;

		if(!is_null($relations)&&!is_array($relations))
			throw new Exception("Error Processing Request: relations must be an array");

		if(!is_null($conditions)&&!is_array($conditions))
			throw new Exception("Error Processing Request: conditions must be an array");


		if(!is_null($relations)&&is_array($relations)&&!empty($relations))
			$this->relations = $relations;

		if(!is_null($conditions)&&is_array($conditions)&&!empty($conditions))
			$this->conditions = $conditions;

		$this->pretty_filter = $pretty_filter;
	}

	/**
	 * add_query_optional: add any optional arguments to the injected query
	 * @param string $type         the type of optional to add
	 * @param string $query_method the method that handles the adding
	 */
	private function add_query_optional($type, $query_method)
	{
		if(!is_string($type)
			||(is_string($type) && !preg_match('/\w+/i', $type)))
				throw new \Exception("Error Processing Request: type must contain a valid string");

		if(!property_exists($this, $type))
			throw new \Exception(sprintf("Error Processing Request: %s property does not exist", $type));

		if(!is_string($query_method)
			||(is_string($query_method) && !preg_match('/\w+/i', $query_method)))
				throw new \Exception("Error Processing Request: type must contain a valid string");

		if(!method_exists($this->orm_query, $query_method))
			throw new \Exception(sprintf("Error Processing Request: %s method does not exist", $query_method));

		if(empty($this->{$type})) return;

		foreach ($this->{$type} as $item)
		{
			call_user_func(array($this->orm_query,$query_method),$item);
		}
	}

	/**
	 * get_queue_data: handle passing the data from the query results and pass it to the main queue service
	 * @return mixed
	 */
	public function get_queue_data()
	{

		# add optionals if needed
		$this->add_query_optional('relations','related');

		$this->add_query_optional('conditions','where');

		# set the filter closure
		$pretty_filter = $this->pretty_filter;

		if(!is_callable($pretty_filter)) throw new \Exception("Error Processing Request: pretty_filter is not callable");

		# get the data we need
		$data   =  $this->orm_query
						->get_query()
						->execute()
						->as_array();

		# use some custom filtering to make the array data returned more readable
		$filtered_data = $pretty_filter($this->orm_query,$data);

		$json_data = array();

		if(empty($filtered_data)) throw new \Exception("Error Processing Request: filtered_data cannot be empty");

		# package the data for delivery to the queue system
		foreach ($filtered_data as $f_data)
		{
			$json_data[] = array('body'=>json_encode($f_data),'type'=>$this->queue_type);
		}

		if(empty($json_data)) throw new \Exception("Error Processing Request: json_data cannot be empty");

		return new Notify_Handler_Result($json_data);
	}

}