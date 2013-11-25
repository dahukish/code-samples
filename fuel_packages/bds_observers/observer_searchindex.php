<?php

namespace Orm;

class Observer_Searchindex extends Observer
{

    public function after_save(Model $model)
    {
        if(!\DBUtil::table_exists('searchindex')) throw new \ErrorException('Search Index Table Does Not Exist. Please Create One.');

        $id = $model->id;
        $model_name = get_class($model);

        // prepare a select statement
        $query = \DB::select('id')->from('searchindex');
        $query->where(array(
            array('content_id','=',$id),
            array('tablename','=',$model_name),
            ));

        $res = $query->execute()->as_array();

        if(empty($res))
        {
            $save = \DB::insert('searchindex', array('content_id', 'tablename', 'title', 'slug', 'body', 'summary', 'meta_keys', 'meta_desc', 'created_at', 'updated_at'));

            $save->values(array(
                    (preg_match('/\d+/', $model->id)? $model->id : 0),
                    ($model_name),
                    (isset($model->title)? $model->title : $model->name),
                    ($model->slug),
                    (isset($model->body)? $model->body : $model->description),
                    (isset($model->summary)? $model->summary : $model->excerpt),
                    (isset($model->meta_keys)? $model->meta_keys : ''),
                    (isset($model->meta_desc)? $model->meta_desc : ''),
                    (\Date::forge()->get_timestamp()),
                    (\Date::forge()->get_timestamp()),
            ));
        }
        else
        {
            $save = \DB::update('searchindex');

            $save->set(array(

                    'content_id'=>(preg_match('/\d+/', $model->id)? $model->id : 0),
                    'tablename'=>($model_name),
                    'title'=>(isset($model->title)? $model->title : $model->name),
                    'slug'=>($model->slug),
                    'body'=>(isset($model->body)? $model->body : $model->description),
                    'summary'=>(isset($model->summary)? $model->summary : $model->excerpt),
                    'meta_keys'=>(isset($model->meta_keys)? $model->meta_keys : ''),
                    'meta_desc'=>(isset($model->meta_desc)? $model->meta_desc : ''),
                    'updated_at'=>\Date::forge()->get_timestamp(),

                    ))->where(array(
                        array('content_id','=',$id),
                        array('tablename','=',$model_name),
                    ));
        }

        $save->execute();

    }

    public function before_delete(Model $model)
    {
        if(\DBUtil::table_exists('searchindex'))
        {
            $id = $model->id;
            $model_name = get_class($model);

            // prepare a select statement
            $query = \DB::select('id')->from('searchindex');
            $query->where(array(
                array('content_id','=',$id),
                array('tablename','=',$model_name),
                ));

            $res = $query->execute()->as_array();

            if(!empty($res))
            {
                $delete = \DB::delete('searchindex');
                $delete->where(array(
                array('content_id','=',$id),
                array('tablename','=',$model_name),
                ))->execute();
            }
        }
    }
}