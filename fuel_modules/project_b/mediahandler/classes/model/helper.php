<?php
namespace Mediahandler;

class Model_Helper extends \Orm\Model
{
	private static $config = false;

	public static function _init()
	{
		\Config::load('mediahandler',true);
		static::$config = \Config::get('mediahandler');
	}

	public static function validate_system()
	{
		$errors = array();

		try
		{
			\File::read_dir(static::$config['asset_path_base']);
		}
		catch(\InvalidPathException $e)
		{
			$errors[] = $e->getMessage();
		}

		# do the sub-directories exist...
		foreach (static::$config['assest_folders'] as $folder_name)
		{
			try
			{
				\File::read_dir(static::$config['asset_path_base'].'/'.$folder_name);
			}
			catch(\InvalidPathException $e)
			{
				$errors[] = $e->getMessage();
			}
		}

		return (!empty($errors))? $errors : true;
	}
}