<?php

namespace Orm;

class Observer_Editlock extends Observer
{

    public function after_save(Model $model)
    {

    	$user = \Auth::check() ? \Model_User::find_by_username(\Auth::get_screen_name()) : null;
    	if(!is_null($user))
    	{
    		$current_uri = \Bds\General::full_uri_to_key_string(\Uri::current());

			$locks = \Model_User_Editlock::query()->where(array('edit_path','=',$current_uri),array('author_id','=',$user->id))->get();

			if(!empty($locks))
			{
				foreach ($locks as $lock_id => $lock)
				{
					$lock->delete();
				}

				\Log::info('Succesfully cleared lock for user:'.$user->id.' on model:'.$model->id);
			}

    	}
    	else
    	{
    		\Log::info('User object is null');
    	}

    }
}