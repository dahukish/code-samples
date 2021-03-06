<?php

 return array(
 	'image_validations'=>array( // these image validation sizes may be removed in future

 		'bg_image'=>array(
			'dimensions'=>array(
							'min'=>array(1680,350),
							'max'=>array(1720,400),
						),
			'allowed_types'=>array(
				'image/gif',
				'image/bmp',
				'image/png',
				'image/jpeg',
				),
			),

 		'featured_img'=>array(
			'dimensions'=>array(
							'min'=>array(584,268),
							'max'=>array(620,300),
						),
			'allowed_types'=>array(
				'image/gif',
				'image/bmp',
				'image/png',
				'image/jpeg',
				),
			),
 		'bw_logo'=>array(
			'dimensions'=>array(
							'min'=>array(213,120),
							'max'=>array(290,130),
						),
			'allowed_types'=>array(
				'image/gif',
				'image/bmp',
				'image/png',
				'image/jpeg',
				),
			),
 		'c_logo'=>array(
			'dimensions'=>array(
							'min'=>array(155,135),
							'max'=>array(165,145),
						),
			'allowed_types'=>array(
				'image/gif',
				'image/bmp',
				'image/png',
				'image/jpeg',
				),
			),
 	),
 	'asset_path_base'=>'assets/files/mediahandler/',
 	'assest_folders'=>array(
 		'images',
 		'documents',
 	),
 );