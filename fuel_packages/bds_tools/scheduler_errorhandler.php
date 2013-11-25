<?php
namespace Bds\Scheduler;

/**
 * Error class for the scheduler
 *
 * @package     Bds
 *
 */
class ErrorHandler
{
	protected $_log = array();
	protected $_error_class_name = false;
	protected $_save_to_log=false;
	protected $_filters = array();
	protected $_gen_msg = false;
	protected $_error_level = 0;

	public function __construct($exception_class, $save_to_log=NULL, $error_level=NULL)
	{
		$this->_error_class_name = is_null($exception_class)? 'ErrorException' : $exception_class;
		if(!is_null($error_level)) $this->_error_level = $error_level;

		if(!is_null($save_to_log)) $this->_save_to_log = $save_to_log;

		#setup filters
		$this->_filters['/Warning:/i'] = '<li style="color:#f00 !important;">##</li>';
		$this->_filters['/Caution:/i'] = '<li style="color:#f90 !important;">##</li>';
	}

	/**
	 * [get_log_count description]
	 * @return [integer] [the count of the logs array]
	 */
	public function get_log_count()
	{
		return count($this->_log);
	}

	/**
	 * get_log returns the contents of the log array
	 * @return array 		the entries in the log variable
	 */
	public function get_log()
	{
		return $this->_log;
	}

	/**
	 * [handle_error public facing interface method to either set an error in the log for just throw the exception]
	 * @param  srting  $error_text  Error text for the log/exception
	 * @param  boolean $save_to_log wether to save this to the log array, defaults to false
	 */
	public function handle_error($error_text,$level=NULL)
	{

		if(is_null($level)) $level = 0;

		if(intval($level) <= intval($this->_error_level))
		{
			if($this->_save_to_log === true)
			{
				$this->save_to_log($error_text);
			}
			else
			{

				throw new \ErrorException($error_text);
			}
		}
	}

	/**
	 * [filter_text takes takes input and uses keywords in that text to determine style markup]
	 * @param  string $error_text the text to filter
	 * @return string             the filtered text
	 */
	private function filter_text($error_text)
	{

		foreach ($this->_filters as $reg_exp => $filter)
		{
			if(preg_match($reg_exp,$error_text)) $error_text = str_replace('##', $error_text, $filter);
		}

		return $error_text;
	}

	/**
	 * save_to_log add a new error to the log array
	 * @param  string $error_text Error text fir the log/exeception
	 */
	private function save_to_log($error_text)
	{
		$this->_log[] = $this->filter_text($error_text);
	}

}