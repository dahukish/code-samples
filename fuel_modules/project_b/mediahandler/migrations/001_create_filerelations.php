<?php

namespace Fuel\Migrations;

class Create_filerelations
{
	public function up()
	{
		\DBUtil::create_table('relations', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'object_id' => array('constraint' => 11, 'type' => 'int'),
			'object_table' => array('constraint' => 255, 'type' => 'varchar'),
			'file_id' => array('constraint' => 11, 'type' => 'int'),
			'field_instance' => array('constraint' => 32, 'type' => 'varchar'),
			'created_at' => array('constraint' => 11, 'type' => 'int', 'null' => true),
			'updated_at' => array('constraint' => 11, 'type' => 'int', 'null' => true),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('relations');
	}
}