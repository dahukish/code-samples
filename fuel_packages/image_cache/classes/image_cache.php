<?php
namespace Imagecache;
class Get
{
	/**
	 * @var array default configuration values
	 */
	// protected static $_defaults = array();

	/**
	 * @var array configuration of this instance
	 */
	protected static $config = array();

	/**
	 * @var object holds the Image Handler Instance
	 */
	protected static $_handler = NULL;



	public static function _init()
	{
		\Config::load('image_cache', true);
		static::$config = array(
			'cache_dir'=>\Config::get('image_cache.cache_dir'),
			'image_dir'=>\Config::get('image_cache.image_dir'),
			'image_presets'=>\Config::get('image_cache.image_presets'),
			'valid_exts'=>\Config::get('image_cache.valid_exts'),
			'default_width'=>200,
			'default_height'=>180,
		);
	}

	private static function is_valid_file($filename)
	{
		if (preg_match("/\.(\w+)$/",$filename,$m)) {
			$ext=$m[1];
			return (in_array(strtolower($ext),static::$config['valid_exts']));
		}
		return false;
	}

	public static function image_tag($filename,$preset=NULL,$alt="",$fallback="")
	{
		$src = self::image($filename,$preset);

		if(!$src) return $fallback;

		return '<img src="/'.$src.'" alt="'.$alt.'" />';
	}

	public static function image($filename,$preset=NULL)
	{
		if(!preg_match('/\w+/i', $filename)) return false;
		if(! static::is_valid_file($filename)) return false;

		$prepend = (!is_null($preset))? $preset : 'default';

		$new_file = $prepend.'_'.$filename;

		try
		{
			if(!preg_match('/\w+/i', $filename)) throw new \FileAccessException;
			return \File::get_url(static::$config['image_dir']. DS .$new_file,array(),'images');
		}
		catch(\FileAccessException $e)
		{
			if(is_null($preset))
			{
				$width = static::$config['default_width'];
				$height = static::$config['default_height'];
			}
			else
			{
				$size = \Arr::get(static::$config['image_presets'],$preset,NULL);
				if(!is_null($size))
				{
					$width = $size['width'];
					$height = $size['height'];
				}
				else
				{
					$width = static::$config['default_width'];
					$height = static::$config['default_height'];
				}
			}

			$path = static::$config['image_dir']. DS .$filename;

			if(!preg_match('/\w+/i', $filename))
			{
				return 'http://placehold.it/'.$width.'x'.$height.'/999/eee';
			}

			try
			{
				if(is_null($height))
				{
					\Image::load($path)->resize($width, $height)->crop('0%','0%','100%','100%')->save_pa($prepend.'_');
				}
				else
				{
					\Image::load($path)->crop_resize($width, $height)->save_pa($prepend.'_');
				}

			}
			catch(\FileAccessException $e)
			{
				return false;
			}
			catch(\OutOfBoundsException $e)
			{
				return false;
			}
			catch(\ErrorException $e)
			{
				return false;
			}

			try
			{
				$get_file_url = \File::get_url(static::$config['image_dir']. DS .$new_file,array(),'images');
			}
			catch(\FileAccessException $e)
			{
				return false;
			}
			catch(\OutOfBoundsException $e)
			{
				return false;
			}
			catch(\ErrorException $e)
			{
				return false;
			}

			return $get_file_url;
		}

		return false;
	}

}