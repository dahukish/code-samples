<?php
namespace Mediahandler;

class Model_Relation extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'object_id',
		'object_table',
		'file_id',
		'created_at',
		'updated_at',
		'field_instance',
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
	);

	protected static $_belongs_to = array(
		'file' => array(
		    'key_from' => 'file_id',
		    'model_to' => 'Mediahandler\\Model_File',
		    'key_to' => 'id',
		    'cascade_save' => false,
		    'cascade_delete' => false,
		),
	);


	public static function validate($factory)
	{
		$val = \Validation::forge($factory);
		$val->add_field('object_id', 'object_id', 'required|valid_string[numeric]');
		$val->add_field('object_table', 'object_table', 'required|max_length[255]');
		$val->add_field('file_id', 'file_id', 'required|valid_string[numeric]');
		$val->add_field('field_instance', 'field_instance', 'required|max_length[128]');
		return $val;
	}
}
