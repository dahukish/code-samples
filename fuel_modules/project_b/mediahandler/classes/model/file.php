<?php
namespace Mediahandler;

class Model_File extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'name',
		'slug',
		'mime',
		'path',
		'filename',
		'extension',
		'saved_as',
		'size',
		'height',
		'width',
		'created_at',
		'updated_at',
		'author_id',
		'alt_text',
		'ext_url',
		'embed_code',
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
		'Orm\Observer_SlugNoTemp' => array(
			'events' => array('before_insert'),
			'source' => 'name',   // property used to base the slug on, may also be array of properties
        	'property' => 'slug',  // property to set the slug on when empty
		),
		'Orm\Observer_Deletefile' => array(
			'events' => array('after_delete'),
			'properties' => array('path'),
		),
		'Orm\Observer_Relatedfiles' => array(
			'events' => array('before_delete'),
		),
	);

	protected static $_has_many = array(
	'relations' => array(
	    'key_from' => 'id',
	    'model_to' => 'Mediahandler\\Model_Relation',
	    'key_to' => 'file_id',
	    'cascade_save' => false,
	    'cascade_delete' => true,
	),);

	public static function validate($factory)
	{
		$val = \Validation::forge($factory);
		$val->add_field('name', 'Name', 'required|max_length[255]');
		$val->add_field('slug', 'Slug', 'max_length[255]');
		$val->add_field('mime', 'Mime', 'max_length[255]');
		$val->add_field('path', 'Path', 'max_length[255]');
		$val->add_field('filename', 'Filename', 'max_length[255]');
		$val->add_field('extension', 'Extension', 'max_length[32]');
		$val->add_field('saved_as', 'Saved as', 'max_length[255]');
		$val->add_field('size', 'Size', 'valid_string[numeric]');
		$val->add_field('height', 'Height', 'valid_string[numeric]');
		$val->add_field('width', 'Width', 'valid_string[numeric]');
		$val->add_field('author_id', 'Author', 'valid_string[numeric]');
		$val->add_field('link_id', 'Link ID', 'valid_string[numeric]');
		$val->add_field('link_table', 'Link Table', 'valid_string[numeric]');
		$val->add_field('alt_text', 'Alt Text', 'max_length[255]');

		return $val;
	}

	/*
	* Builds the markup for the edit form on load
	* Params
	* Array of file_relation results (with related files)
	* returns the HTML markup that mimics the javascript implementation
	*/

	public static function build_form_markup($rel_results,$prefix)
	{
		$html = "";
		$divs = array();

		foreach ($rel_results as $rel_id => $rel)
		{
			if(!isset($rel->file->mime)) continue;
			list($mime_arch,$type) = explode('/', $rel->file->mime);
			$divs[$mime_arch][] = '<div class="preview-media-item" data-id="'.$rel->file->id.'">'.\Imagecache\Get::image_tag($rel->file->filename,"adminImgSelect").'<div class="media-item">'.$rel->file->name.'<a href="#" class="submit" data-submit-type="remove-media-item" data-prefix="'.$prefix.'">&times;</a></div></div>';
		}

		foreach ($divs as $k_mime_arch => $v_divs)
		{
			$html .= '<div id="'.$prefix.'_'.$k_mime_arch.'">';
			foreach ($v_divs as $v_div) $html .= $v_div;
			$html .= '</div>';
		}

		return $html;
	}

}
