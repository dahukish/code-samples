<?php
namespace Queue\Util;

class Queue_Container extends \Pimple
{
	public function __construct(array $params)
	{

		if(empty($params)) throw new \Exception("Error Processing Request: params cannot be empty");

		# setup our custom container for what we need.
		$this['make_pretty_cols'] = function($c){

			if(!isset($c['make_pretty_query'])
				||(isset($c['make_pretty_query'])&&empty($c['make_pretty_query']))) return false;

			if(!isset($c['make_pretty_res'])
				||(isset($c['make_pretty_res'])&&empty($c['make_pretty_res']))) return false;

			if(($c['make_pretty_query'] instanceof \Orm\Query) === false) return false;

			if(!is_array($c['make_pretty_res'])) return false;

			return \Orm\Bds_Query_Helper::make_pretty_cols($c['make_pretty_query'], $c['make_pretty_res']);
		};

		# add in some additional params at runtime
		foreach ($params as $key => $value)
		{
			$this[$key] = $value;
		}

	}
}