<?php
return array(

	/*
	Directory to use for caching
	*/

	'cache_dir'=>APPPATH.'cache'.DS.'image_cache',
	'image_dir'=>DOCROOT.'assets'.DS.'files'.DS.'mediahandler'.DS.'images',
	'image_presets'=>array(

			'topItems'=>array(
					'width'=>1210,
					'height'=>292,
					'crop'=>1
					),
			'slideItems'=>array(
					'width'=>1210,
					'height'=>390,
					'crop'=>1,
					),
			'homeFeaturedNews'=>array(
					'width'=>72,
					'height'=>72,
					'crop'=>1
					),
			'homeFeaturedItems'=>array(
					'width'=>293,
					'height'=>136,
					'crop'=>1
					),
			"sidebarWidgets"=>array(
					'width'=>293,
					'height'=>null
					),
			'departmentFeaturedItems'=>array(
					'width'=>282,
					'height'=>206,
					'crop'=>1
					),
			'generalFeaturedImage'=>array(
					'width'=>586,
					'height'=>null,
					),
			'departmentLogo'=>array(
					'width'=>110,
					'height'=>null
					),
			'adminImgSelect' => array('width'=>200, 'height'=>null),
			'browseView' => array('width'=>100, 'height'=>75),
	),
	'valid_exts'=>array('jpg','png','bmp','jpeg','gif'),
);