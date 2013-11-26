<?php

/**
 * Scheduler Helper - To act as a go between for the interpreter and fuel
 * @package    Queue
 * @version    0.1
 * @author     Barking Dog Studios
 * @license    MIT License
 * @copyright  2010 - 2013 Barking Dog Studios
 * @link       http://www.barking.ca
 */

namespace Queue;

class QueueExecption extends \FuelException {}

interface QueueDataInterface
{
	public function get_queue_data();
}

interface QueueDataResultInterface
{
	public function get_rows();
	public function get_row_body($row);
	public function get_row_type($row);
}

interface QueueStoreInterface
{
	public function to_store($body, $type, $delay_until=NULL);
	public function has_data();
	public function save();
}

interface QueueConsumerInterface
{
	public function from_store();
}

class Queue
{
	/**
	 * @var array configuration of this instance
	 */
	protected static $config = array();

	/**
	 * _init: basic setup for the Queue service
	 * @return null
	 */
	public static function _init()
	{
		\Config::load('queue', true);

		static::$config = \Config::get('queue');
		static::validate();
	}

	/**
	 * validate: runs a series of checks (based on data from the local config) to make sure the service is running properly
	 * @return boolean							 did all the checks pass?
	 */
	private static function validate($config)
	{
		# TODO: make this work -SH
	}

	/**
	 * process_data: takes data provided by the data handler and store its via the store handler
	 * @param  QueueDataInterface  $data_handler grabs the data from a data source (the DB)
	 * @param  QueueStoreInterface $data_store   handles where the scraped data is store for the consumer (DB/APMQ)
	 * @return boolean							 did this perform the request successfully
	 */
	public static function process_data(QueueDataInterface $data_handler, QueueStoreInterface $data_store)
	{
		$queue_res = $data_handler->get_queue_data();

		if(!empty($queue_res))
		{
			foreach ($queue_res->get_rows() as $q_row)
			{
				$data_store->to_store($queue_res->get_row_body($q_row),$queue_res->get_row_type($q_row));
			}

			if($data_store->has_data()) $data_store->save();
		}
	}

	/**
	 * consume_data: take the data from the store and act on it accordingly
	 * @param  QueueConsumerInterface $consumer control the consumption of the data (DB/APMQ)
	 * @param  Closure                $cb_func  the action to perform on each item in the queue
	 * @return boolean                          did this perform the request successfully
	 */
	public static function consume_data(QueueConsumerInterface $consumer, $cb_func=null)
	{
		# TODO: make this work -SH
	}

}