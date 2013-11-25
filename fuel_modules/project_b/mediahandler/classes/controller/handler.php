<?php
namespace Mediahandler;

class Controller_Handler extends \Controller_Admin
{

	private $errors = array();

	public function before()
	{
		parent::before();

		$this->config = \Config::load('mediahandler',true);

		# perform a check on the system to make sure all of the folder and permissions are correct -SH
		if(($pu_val_system_errs = \Plupload\Helper::validate_system()) !== true) foreach ($pu_val_system_errs as $p_err) $this->errors[] = $p_err;
		if(($mh_val_system_errs = Model_Helper::validate_system()) !== true) foreach ($mh_val_system_errs as $m_err) $this->errors[] = $m_err;


		if(!empty($this->errors)) throw new \ErrorException(sprintf("The following errors were found: (%s)",implode(')(',$this->errors)));

	}

	public function get_upload_iframe()
	{
		$data = array();

		$array = array();
		$array[] = \Input::get('image',NULL);
		$array[] = \Input::get('fld',NULL);
		$array[] = str_replace('.', '-', \Input::get('old_file',NULL));
		$data['iframe_src'] = "/mediahandler/handler/upload_view?mode=".\Input::get("mode",NULL)."&max=".\Input::get("max",1)."&min_width=".\Input::get("min_width",0)."&min_height=".\Input::get("min_height",0);
		$data['width'] = 800;
		$data['height'] = 400;
		$data["prefix"] = \Input::get("prefix");

		foreach ($array as $value)
		{
			if(is_null($value)) continue;
			$data['iframe_src'] .= '/'.$value;
		}

		return $this->response(\View::forge('form_iframe',$data));
	}

	public function get_upload_view($image,$fld,$old_file=NULL)
	{
		$data = array();

		$data['image'] = $image;
		$data['fld'] = $fld;
		if(!is_null($old_file)) $data['old_file'] = str_replace('-','.',$old_file);

		return $this->response(\View::forge('form_upload',$data));
	}

	public function post_ajax_upload()
	{
		$validations = \Config::get('mediahandler.image_validations');

		$image = \Input::post('image',NULL);
		$fld = \Input::post('fld',NULL);
		$img_val = \Arr::get($validations,$fld,NULL);

		$val = \Validation::forge('mediahandler_upload');

		//custom validation
		$val->add_callable(new \BdsRules());
		$val->add_field($fld, $fld, 'max_length[255]')->add_rule('image_validation',$img_val);

		if ($val->run())
		{
			\Session::set_flash('success', e('Image Uploaded.'));
		}
		else
		{

			\Session::set_flash('error', $val->error());
		}

		$data = array();
		$data['image'] = $image;
		$data['fld'] = $fld;

		return $this->response(\View::forge('form_upload',$data));
	}

	public function post_delete_image()
	{

		$path = DOCROOT.'assets'.DS.'files'.DS;

		$file = \Input::post('file',NULL);

		if(!is_null($file) && preg_match('/\w+/i', $file))
		{
			if(! \File::delete($path.$file))
			{
				return $this->response(json_encode(array('error'=>'not deleted')));
			}

		}
		else
		{
			$this->response(json_encode(array('error'=>'not deleted')));
		}


		return $this->response(json_encode(array('deleted'=>'deleted')));
	}

	public function get_plupload_iframe($width=800,$height=600)
	{
		$data = array();

		$mode = (!is_null(\Input::get('mode',NULL)))? '?mode='.\Input::get('mode') : '' ;
		$prefix = (!is_null(\Input::get('prefix',NULL)))? '&prefix='.\Input::get('prefix') : '' ;
		$max = "&max=".\Input::get("max",1);
		$max .= "&min_width=".\Input::get("min_width",0);
		$max .= "&min_height=".\Input::get("min_height",0);


		$data['iframe_src'] = "/mediahandler/handler/pluploadform{$mode}{$prefix}{$max}";
		$data['width'] = $width;
		$data['height'] = $height;

		return $this->response(\View::forge('form_iframe',$data));
	}

	function action_pluploadform()
	{

		$data = array();
		$data['mode'] = \Input::get('mode','all');
		$data['max'] = \Input::get('max',1);
		$data['min_width'] = \Input::get('min_width',0);
		$data['min_height'] = \Input::get('min_height',0);
		$data['prefix'] = \Input::get('prefix','rel_media');
		$data['view'] = (\Request::is_hmvc())? 'standalone' : 'ajax';
		$data['browse_refresh'] = (\Request::is_hmvc())? ' data-refresh="/mediahandler/file/browse_full" data-context="browse" ' : '';

		$browse_path = (\Request::is_hmvc())? 'mediahandler/file/browse_full' : 'mediahandler/file/browse';
		$data["browse_path"] = "/".$browse_path."?mode=".$data["mode"]."&max=".$data["max"]."&min_width=".$data["min_width"]."&min_height=".$data["min_height"];
		$data['browse'] = \Request::forge($browse_path)->execute();

		if(in_array($data['mode'], array('all','document'))) $data['external'] = \Request::forge('mediahandler/file/external')->execute();
		$data['tab'] = '#browse';

		switch ($data['mode'])
		{
			case 'image':
				$data['filters'] = '{title : "Image files", extensions : "jpg,bmp,jpeg,gif,png"}';
				break;

			case 'document':
				$data['filters'] = '{title : "Document files", extensions : "pdf,rtf,doc,xls,docx,xlsx"}';
				break;

			default:
				$data['filters'] = '{title : "Image files", extensions : "jpg,bmp,jpeg,gif,png"},{title : "Document files", extensions : "pdf,rtf,doc,xls,docx,xlsx"}';
				break;
		}

		if(\Request::is_hmvc()) return $this->response(\View::forge('plupload',$data)->render());

		return \Response::forge(\View::forge('plupload',$data));

	}

	static function plupload_callback($filename)
	{
		// Not being used
	}

	function action_plupload()
	{
		\Plupload::upload('\Mediahandler\Controller_Handler::plupload_callback');
	}


}
