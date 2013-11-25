<?php

namespace Fuel\Migrations;

class Create_files
{
	public function up()
	{
		\DBUtil::create_table('files', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'name' => array('constraint' => 255, 'type' => 'varchar'),
			'slug' => array('constraint' => 255, 'type' => 'varchar'),
			'mime' => array('constraint' => 255, 'type' => 'varchar'),
			'path' => array('constraint' => 255, 'type' => 'varchar'),
			'filename' => array('constraint' => 255, 'type' => 'varchar'),
			'extension' => array('constraint' => 32, 'type' => 'varchar'),
			'saved_as' => array('constraint' => 255, 'type' => 'varchar'),
			'size' => array('constraint' => 11, 'type' => 'int'),
			'height' => array('constraint' => 6, 'type' => 'int'),
			'width' => array('constraint' => 6, 'type' => 'int'),
			'author_id' => array('constraint' => 11, 'type' => 'int'),
			'alt_text' => array('constraint' => 255, 'type' => 'varchar'),
			'ext_url' => array('constraint' => 255, 'type' => 'varchar'),
			'embed_code' => array('type' => 'text'),
			'created_at' => array('constraint' => 11, 'type' => 'int'),
			'updated_at' => array('constraint' => 11, 'type' => 'int'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('files');
	}
}