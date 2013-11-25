<?php
namespace Orm;

/**
 * BDS ORM query object helper. Extends the ORM query class to allow for lightweight/meaningful
 * queries when dealing with large queries with lots of related data.
 */
class Bds_Query_Helper extends Query
{

	public static function make_pretty_cols(Query $query, $results, $filter_handler=NULL)
	{
		$cols = array();

		$cols['t0']['model'] = \Inflector::tableize($query->model);
		$cols['t0']['cols'] = static::get_model_properties($query->model);

		static::get_relations($query->relations,$cols);

		$res = static::readable_columns($cols,$results);

		if(!is_null($filter_handler)) return $filter_handler($res);
		else return $res;
	}

	public static function hydrate_light($data, $fields)
	{
		$collection = array();
		$col_names = static::convert_flat_string_array($fields,'.');

		if(!empty($data)&&!empty($col_names))
		{

			foreach ($data as $row=>$cols)
			{
				$temp_vals = array();

				foreach ($cols as $model_name => $m_cols)
				{
					$model_set = \Arr::get($col_names,$model_name,NULL);

					if(is_null($model_set)) continue;

					foreach ($m_cols as $m_field => $m_val)
					{
							if(!\Arr::get($model_set,$m_field,FALSE)) continue;

							$temp_vals[$m_field] = $m_val;
					}
				}

				if(!empty($temp_vals)) $collection[] = (object)$temp_vals;
			}

			return (!empty($collection))? $collection : false;
		}
		else return false;
	}

	private static function convert_flat_string_array($array,$sep)
	{
		$array_split = array();
		if(!empty($array))
		{
			foreach ($array as $element)
			{
				list($parent,$child) = explode($sep,$element);
				$array_split[$parent][$child] = true;
			}

			if(!empty($array_split)) return $array_split;
			else return false;
		}
		else return false;
	}

	private static function get_relations($relations, &$cols, $cols_start=1)
	{
		foreach ($relations as $relation)
		{
			$cols["t{$cols_start}"]['model'] = \Inflector::tableize($relation[0]->model_to);
			$cols["t{$cols_start}"]['cols'] = static::get_model_properties($relation[0]->model_to);
			$cols_start++;
		}

	}

	private static function get_model_properties($model_name)
	{
		$cols_count = 0;
		$array = array();
		foreach ($model_name::properties() as $name => $attr)
		{
			$array["c{$cols_count}"] = $name;
			$cols_count++;
		}

		return $array;
	}


	private static function readable_columns($columns, $results)
	{
		$temp = array();

		foreach ($results as $data)
		{

			$tr = array();

			foreach ($data as $table_col => $col_val)
			{
				preg_match('/(t\d{1,11})_(c\d{1,11})/i',$table_col,$m);
				$table = $columns[$m[count($m)-2]]['model'];
				$col = $columns[$m[count($m)-2]]['cols'][$m[count($m)-1]];
				$tr["{$table}"]["{$col}"] = $col_val;
			}

			$temp[] = $tr;
		}

		return $temp;
	}

}