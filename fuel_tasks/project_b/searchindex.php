<?php

namespace Fuel\Tasks;

class Searchindex
{

	/**
	 * Usage (from command line):
	 *
	 * php oil r searchindex
	 *
	 * @return string
	 */
	public static function run($args = NULL)
	{
		echo \Cli::color("\n===========================================",'green');
		echo \Cli::color("\nRunning DEFAULT task [Searchindex:Run]",'blue');
		echo \Cli::color("\n-------------------------------------------\n\n",'green');
		echo "\nUse searchindex create to generate a new search index from given tables.\n\n";
		echo "\nUse searchindex insert_tables to insert new tables into the index.\n\n";
		echo "\nUse searchindex delete to delete the index table.\n\n";

	}



	/**
	 * Usage (from command line):
	 *
	 * php oil r searchindex:create
	 *
	 * @return string
	 */
	public static function create()
		{

		echo \Cli::color("\n===========================================",'green');
		echo \Cli::color("\nRunning task [Searchindex:Create]",'blue');
		echo \Cli::color("\n-------------------------------------------\n\n",'green');

		// Check if table named 'my_table' exists
		if(!\DBUtil::table_exists('searchindex'))
		{
		    try
		    {
				echo \Cli::color("\nCreating Table [searchindex]\n",'green');

				\DBUtil::create_table('searchindex', array(
					'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
					'content_id' => array('constraint' => 11, 'type' => 'int'),
					'tablename' => array('constraint' => 255, 'type' => 'varchar'),
					'title' => array('constraint' => 255, 'type' => 'varchar'),
					'slug' => array('constraint' => 255, 'type' => 'varchar'),
					'body' => array('type' => 'text'),
					'summary' => array('type' => 'text'),
					'meta_keys' => array('type' => 'text'),
					'meta_desc' => array('type' => 'text'),
					'created_at' => array('constraint' => 11, 'type' => 'int'),
					'updated_at' => array('constraint' => 11, 'type' => 'int'),
					'temporal_start' => array('constraint' => 11, 'type' => 'int'),
					'temporal_end' => array('constraint' => 11, 'type' => 'int'),

				), array('id'), false, 'MyISAM');

			}
			catch(\Database_Exception $e)
			{
			    echo \Cli::color(sprintf("\nError during creation, therefore I cannot create it: %s\n\n",$e->getMessage()),'red');
			}
		}
		else
		{
		    echo \Cli::color("\nThis table currently exists already... Cannot create new search index table\n\n",'red');
		}

		echo "\n\n";

	}


	/**
	 * Usage (from command line):
	 * php oil r searchindex:insert_models "arguments"
	 *
	 * @return string
	 */
	public static function insert_models($args = NULL)
		{

		echo \Cli::color("\n===========================================",'green');
		echo \Cli::color("\nRunning task [Searchindex:Create]",'blue');
		echo \Cli::color("\n-------------------------------------------\n\n",'green');

		if(is_null($args))
		{
			echo \Cli::color("\nArguments cannot be NULL\n",'red');
			return false;
		}

		// Check if table named 'my_table' exists
		if(!\DBUtil::table_exists('searchindex'))
		{
		    echo \Cli::color("\nThis table doesn't currently exist... Create a new search index table\n\n",'red');
		}
		else
		{
		    try
		    {
				\DBUtil::truncate_table('searchindex');

				echo \Cli::color("\nParsing Arguments\n",'green');

				$models = explode(',',$args);

				foreach ($models as $model)
				{

					$query = \DB::insert('searchindex', array('content_id', 'tablename', 'title', 'slug', 'body', 'summary', 'meta_keys', 'meta_desc', 'created_at', 'updated_at', 'temporal_start', 'temporal_end'));

					$results = $model::find('all');

					$value_count=0;

					foreach ($results as $r_id => $r_value)
					{
						if(isset($r_value->status) && (intval($r_value->status) < 3)) continue;

						$title="";
						if ((isset($r_value->name_first))&&(isset($r_value->name_last))&&(isset($r_value->title))) {
							$title=$r_value->name_first." ".$r_value->name_last." - ".$r_value->title;
						}
						elseif (isset($r_value->title)) $title=$r_value->title;
						elseif (isset($r_value->name)) $title=$r_value->name;
						elseif (isset($r_value->username)) $title=$r_value->username;

						$body = "";
						if (isset($r_value->body)) $body=$r_value->body;
						elseif (isset($r_value->description)) $body=$r_value->description;
						elseif (isset($r_value->email)) $body=$r_value->email;

						$summary = "";
						if (isset($r_value->summary)) $summary=$r_value->summary;
						elseif (isset($r_value->excerpt)) $summary=$r_value->excerpt;

						$query->values(array(
							(preg_match('/\d+/', $r_value->id)? $r_value->id : 0),
							($model),
							($title),
							(isset($r_value->slug)? $r_value->slug : ''),
							strip_tags($body),
							strip_tags($summary),
							(isset($r_value->meta_keys)? $r_value->meta_keys : ''),
							(isset($r_value->meta_desc)? $r_value->meta_desc : ''),
							(\Date::forge()->get_timestamp()),
							(\Date::forge()->get_timestamp()),
							isset($r_value->temporal_start)?$r_value->temporal_start:0,
							isset($r_value->temporal_end)?$r_value->temporal_end:2147483647,
						));

						$value_count++;
					}


					if($value_count>0)
					{
						echo \Cli::color(sprintf("\nInserting records for: %s\n\n",$model),'blue');
						$query->execute();
						echo \Cli::color(sprintf("\nAdded records for: %s\n\n",$model),'green');
					}
					else
					{
						echo \Cli::color(sprintf("\nNo records for: %s\n\n",$model),'red');
					}

					$query = null;

				}

			}
			catch(\Database_Exception $e)
			{
			    echo \Cli::color(sprintf("\nError during update: %s\n\n",$e->getMessage()),'red');
			}
		}

		echo "\n\n";

	}

	/**
	 * Usage (from command line):
	 *
	 * php oil r searchindex:delete
	 *
	 * @return string
	 */
	public static function delete()
		{

		echo \Cli::color("\n===========================================",'green');
		echo \Cli::color("\nRunning task [Searchindex:Delete]",'blue');
		echo \Cli::color("\n-------------------------------------------\n\n",'green');


		// print_r($args);


		// Check if table named 'my_table' exists
		if(\DBUtil::table_exists('searchindex'))
		{
			// Catch the exception
			try
			{
			   \DBUtil::drop_table('searchindex');
				echo \Cli::color("\nDeleting Table [searchindex]\n",'green');
			}
			catch(\Database_Exception $e)
			{
			    echo \Cli::color("\nError during deletion, therefore I cannot delete it...\n\n",'red');
			}
		}
		else
		{
		    echo \Cli::color("\nTable doesn't exist, therefore I cannot delete it...\n\n",'red');
		}

		echo "\n\n";

	}

}