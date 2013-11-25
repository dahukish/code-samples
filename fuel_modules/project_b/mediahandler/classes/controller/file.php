<?php
namespace Mediahandler;

class Controller_File extends \Controller_Admin
{

	public function action_index()
	{
		$this->template->title = "Media";
		$this->template->content = \Request::forge('mediahandler/handler/pluploadform', false)->execute()->response();
	}

	public function action_view($id = null)
	{
		$data['file_file'] = Model_File::find($id);

		$this->template->title = "View Media";
		$this->template->content = \View::forge('file/view', $data);

	}

	public function action_browse()
	{
		$min_width=\Input::get("min_width",0);
		$min_height=\Input::get("min_height",0);

		switch(\Input::get('mode','all'))
		{
			case 'image':

				$where=array(
							array('mime','IN',array(
								'image/jpeg',
								'image/bmp',
								'image/gif',
								'image/png',
							)),
							array("width",">=",$min_width),
							array("height",">=",$min_height),
						);

				$data['files'] = Model_File::query()->where($where)->order_by(array('name'=>"ASC"))->get();
			break;

			case 'document':

				$in = array(
					'application/pdf',
					'application/msword',
					'application/excel',
					'application/vnd.ms-excel',
					'application/ms-excel',
					'external/data',
				);

				$data['files'] = Model_File::query()->where(array('mime','IN',$in))->order_by(array('name'=>"ASC"))->get();
			break;

			default:

				$where=array(
							array('mime','IN',array(
								'image/jpeg',
								'image/bmp',
								'image/gif',
								'image/png',
							)),
							array("width",">=",$min_width),
							array("height",">=",$min_height),
						);
				$images = Model_File::query()->where($where)->order_by(array('name'=>"ASC"))->get();

				$in = array(
					'application/pdf',
					'application/msword',
					'application/excel',
					'application/vnd.ms-excel',
					'application/ms-excel',
					'external/data',
				);

				$docs = Model_File::query()->where(array('mime','IN',$in))->order_by(array('name'=>"ASC"))->get();

				$data['files'] = $docs+$images;

			break;
		}

		$data['max'] = \Input::get('max',1);
		$data['min_width'] = \Input::get('min_width',1);
		$data['min_height'] = \Input::get('min_height',1);

		$data["title"]="";
		return $this->response(\View::forge('file/browse', $data));
	}

	public function action_browse_full() //works with the hmvc call to the hmvc call -SH the one above wont get any args...
	{
		$in = array(
			'image/jpeg',
			'image/bmp',
			'image/gif',
			'image/png',
		);
		$images = Model_File::query()->where(array('mime','IN',$in))->order_by(array('name'=>"ASC"))->get();

		$in = array(
			'application/pdf',
			'application/msword',
			'application/excel',
			'application/vnd.ms-excel',
			'application/ms-excel',
			'external/data',
		);

		$docs = Model_File::query()->where(array('mime','IN',$in))->order_by(array('name'=>"ASC"))->get();

		$data['files'] = $docs+$images;

		if(\Input::is_ajax()) return $this->response(\View::forge('file/browse_full', $data)->render());
		return $this->response(\View::forge('file/browse_full', $data));
	}

	public function action_external()
	{
		return $this->response(\View::forge('file/_form_external'));
	}

	public function action_create()
	{

		if (\Input::method() == 'POST')
		{
			$val = Model_File::validate('create');

			//lets hijack this post request
			if(\Input::is_ajax() && \Input::post('is_plupload',false))
			{
				$temp_dir = APPPATH . 'tmp' . DS . 'plupload';
				$info = \File::file_info($temp_dir. DS .\Input::post('filename'));
				$width = 0;
				$height = 0;
				$alt_text = "";
				$new_path = "";

				list($mime_arch,$sub_type) = explode('/', $info['mimetype']);

				\Config::load('mediahandler', true);
				$asset_path_base = \Config::get('mediahandler.asset_path_base');

				switch($mime_arch)
				{
					case 'application':
						$new_path = $asset_path_base.'documents'.DS.\Str::lower(\Input::post('filename'));

					break;

					case 'image':
						$new_path = $asset_path_base.'images'.DS.\Str::lower(\Input::post('filename'));
						list($width,$height) = getimagesize($info['realpath']);
						$alt_text = \Input::post('name');
					break;
				}

				//move file to proper directory
				try
				{
					\File::rename($info['realpath'],$new_path);
				}
				catch(\ErrorException $e)
				{
					try
					{
						\File::delete($info['realpath']);
					}
					catch(\ErrorException $e)
					{
						return $this->response(array('status'=>0,'body'=>$e->getMessage()));
					}

					return $this->response(array('status'=>0,'body'=>$e->getMessage()));
				}

				# add some values to the $_POST super global so that validation works
				$_POST['path'] = $new_path;
				$_POST['mime'] = $info['mimetype'];
				$_POST['width'] = $width;
				$_POST['height'] = $height;
				$user = \Auth::check()? \Model_User::find_by_username(\Auth::get_screen_name()) : 0;
				$_POST['author_id'] = is_object($user)? $user->id : 0;
				$_POST['saved_as'] = \Input::post('filename');
				$_POST['alt_text'] = $alt_text;

			}

			if ($val->run())
			{
				$file_file = Model_File::forge(array(
					'name' => \Input::post('name'),
					'slug' => \Input::post('slug'),
					'mime' => \Input::post('mime'),
					'path' => \Input::post('path'),
					'filename' => \Str::lower(\Input::post('filename')),
					'extension' => \Input::post('extension',''),
					'saved_as' => \Str::lower(\Input::post('saved_as','')),
					'size' => \Input::post('size',0),
					'height' => \Input::post('height',0),
					'width' => \Input::post('width',0),
					'author_id' => \Input::post('author_id',0),
					'alt_text' => \Input::post('alt_text',''),
					'ext_url' => \Input::post('ext_url',''),
					'embed_code' => \Input::post('embed_code',''),
				));

				if ($file_file and $file_file->save())
				{
					if(\Input::is_ajax())
					{
						$file_file->image_tag = \Imagecache\Get::image_tag($file_file->filename);
						return $this->response(array('status'=>1,'body'=>$file_file));
					}

					\Session::set_flash('success', e('Added file_file #'.$file_file->id.'.'));

					\Response::redirect('mediahandler/file');
				}

				else
				{
					if(\Input::is_ajax()) return $this->response(array('status'=>0,'body'=>'Could Not Save file.'));

					\Session::set_flash('error', e('Could not save file_file.'));
				}
			}
			else
			{
				if(\Input::is_ajax()) return $this->response(array('status'=>0,'body'=>$val->error()));

				\Session::set_flash('error', $val->error());
			}
		}

		$this->template->title = "Files";
		$this->template->content = \View::forge('file/create');

	}

	public function action_edit($id = null)
	{
		$file_file = Model_File::find($id);
		$val = Model_File::validate('edit');

		if ($val->run())
		{
			$file_file->name = \Input::post('name');
			$file_file->slug = \Input::post('slug');
			$file_file->mime = \Input::post('mime');
			$file_file->path = \Input::post('path');
			$file_file->filename = \Str::lower(\Input::post('filename'));
			$file_file->extension = \Input::post('extension');
			$file_file->saved_as = \Str::lower(\Input::post('saved_as'));
			$file_file->size = \Input::post('size');
			$file_file->height = \Input::post('height');
			$file_file->width = \Input::post('width');
			$file_file->file_collection_id = \Input::post('file_collection_id');
			$file_file->author_id = \Input::post('author_id');
			$file_file->link_id = \Input::post('link_id');
			$file_file->link_table = \Input::post('link_table');
			$file_file->alt_text = \Input::post('alt_text');
			$file_file->ext_url = \Input::post('ext_url');
			$file_file->embed_code = \Input::post('embed_code');


			if ($file_file->save())
			{
				\Session::set_flash('success', e('Updated file_file #' . $id));

				\Response::redirect('mediahandler/file');
			}

			else
			{
				\Session::set_flash('error', e('Could not update file_file #' . $id));
			}
		}

		else
		{
			if (\Input::method() == 'POST')
			{
				$file_file->name = $val->validated('name');
				$file_file->slug = $val->validated('slug');
				$file_file->mime = $val->validated('mime');
				$file_file->path = $val->validated('path');
				$file_file->filename = $val->validated('filename');
				$file_file->extension = $val->validated('extension');
				$file_file->saved_as = $val->validated('saved_as');
				$file_file->size = $val->validated('size');
				$file_file->height = $val->validated('height');
				$file_file->width = $val->validated('width');
				$file_file->file_collection_id = $val->validated('file_collection_id');
				$file_file->author_id = $val->validated('author_id');
				$file_file->link_id = $val->validated('link_id');
				$file_file->link_table = $val->validated('link_table');
				$file_file->alt_text = $val->validated('alt_text');
				$file_file->ext_url = $val->validated('ext_url');
				$file_file->embed_code = $val->validated('embed_code');

				\Session::set_flash('error', $val->error());
			}

			$this->template->set_global('file_file', $file_file, false);
		}

		$this->template->title = "File_files";
		$this->template->content = \View::forge('file/edit');

	}

	public function action_delete($id = null)
	{
		if ($file_file = Model_File::find($id))
		{
			$file_file->delete();

			\Session::set_flash('success', e('Deleted file_file #'.$id));
		}

		else
		{
			\Session::set_flash('error', e('Could not delete file_file #'.$id));
		}

		\Response::redirect('mediahandler/file');

	}


}