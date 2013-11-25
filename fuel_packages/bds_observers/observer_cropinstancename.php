<?php

namespace Orm;

class Observer_Cropinstancename extends Observer
{

    /**
     *
     * @param  Model  Model object subject of this observer method
     */
    public function before_save(Model $obj)
    {
        if(!isset($obj->instance_name) || !preg_match('/\w+?/i',$obj->instance_name))
        {
            $ff_obj = \Model_Organic_FloraFauna::query()->select('name')->where('id','=',$obj->crop_id)->get_one();
            $ucrop_objs = \Model_User_Crop::query()->where(array(
                                                        array('crop_id','=',$obj->crop_id),
                                                        array('space_id','=',$obj->space_id),
                                                    ))->order_by('id','asc')->get();

            $temp_count = intval(count($ucrop_objs));

            if(intval($temp_count) <= 0) $temp_count = 0;
            else
            {
                $temp_count = 0;

                foreach ($ucrop_objs as $c_id => $ucrop)
                {
                    if(preg_match('/\w+?/i',$ucrop->instance_name)) $temp_count++;
                }
            }

            $count = 1 + $temp_count;

            if(!empty($ff_obj)) $obj->instance_name = $ff_obj->name." ({$count})";
            else $obj->instance_name = "Noname ({$count})";

        }
    }
}