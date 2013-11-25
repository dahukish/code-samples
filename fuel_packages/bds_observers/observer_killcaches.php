<?php

namespace Orm;

class Observer_Killcache extends Observer
{

    /**
     * @var  string  Slug property
     */
    protected $_properties = array();

    /**
     * Set the properties for this observer instance, based on the parent model's
     * configuration or the defined defaults.
     *
     * @param  string  Model class this observer is called on
     */
    public function __construct($class)
    {
        $props = $class::observers(get_class($this));
        $this->_properties = isset($props['properties']) ? $props['properties'] : array();
        $this->_special = isset($props['special']) ? $props['special'] : array();
    }

    public function after_save(Model $model)
    {



        $properties = (array) $this->_properties;

        if(!empty($properties))
        {
            foreach ($properties as $property)
            {
                try
                {
                    \Cache::delete($property);
                }
                catch(\InvalidPathException $e)
                {
                    \Log::warning($e->getMessage());
                }
                catch(\Exception $e)
                {

                    \Log::warning($e->getMessage());
                }
            }
        }

        $special = (array) $this->_special;

        if(!empty($special))
        {
            foreach ($special as $spec)
            {
                try
                {
                    if(isset($model->slug) && preg_match('/\w+?/',$model->slug))
                    {
                        \Cache::delete($spec.'.'.str_replace('-', '_', $model->slug));
                    }
                    else
                    {
                        \Cache::delete_all($spec);
                    }


                }
                catch(\InvalidPathException $e)
                {
                    \Log::warning($e->getMessage());
                }
                catch(\Exception $e)
                {
                    \Log::warning($e->getMessage());
                }
            }
        }
    }

    public function after_delete(Model $model)
    {

        $properties = (array) $this->_properties;

        if(!empty($properties))
        {
            foreach ($properties as $property)
            {
                try
                {
                    \Cache::delete($property);
                }
                catch(\InvalidPathException $e)
                {
                    \Log::warning($e->getMessage());
                }
                catch(\Exception $e)
                {
                    \Log::warning($e->getMessage());
                }
            }
        }

        $special = (array) $this->_special;

        if(!empty($special))
        {
            foreach ($special as $spec)
            {
                try
                {
                    if(isset($model->slug) && preg_match('/\w+?/',$model->slug))
                    {
                        \Cache::delete($spec.'.'.str_replace('-', '_', $model->slug));
                    }
                    else
                    {
                        \Cache::delete_all($spec);
                    }
                }
                catch(\InvalidPathException $e)
                {
                    \Log::warning($e->getMessage());
                }
                catch(\Exception $e)
                {
                    \Log::warning($e->getMessage());
                }
            }
        }

    }
}