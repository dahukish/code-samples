<?php

namespace Orm;

class Observer_Summarize extends Observer
{

    /**
     * @var  mixed  Default source property or array of properties, which is/are used to create the slug
     */
    public static $source = 'body';

    /**
     * @var  string  Default slug property
     */
    public static $property = 'summary';

    /**
     * @var  mixed  Source property or array of properties, which is/are used to create the slug
     */
    protected $_source;

    /**
     * @var  string  Slug property
     */
    protected $_property;

    /**
     * Set the properties for this observer instance, based on the parent model's
     * configuration or the defined defaults.
     *
     * @param  string  Model class this observer is called on
     */
    public function __construct($class)
    {
        $props = $class::observers(get_class($this));
        $this->_source    = isset($props['source']) ? $props['source'] : static::$source;
        $this->_property  = isset($props['property']) ? $props['property'] : static::$property;
    }

    /**
     * Creates a summary from the body text and adds it to the object
     *
     * @param  Model  Model object subject of this observer method
     */
    public function before_insert(Model $obj)
    {
        // determine the summary
        $properties = (array) $this->_source;
        $source = '';
        foreach ($properties as $property)
        {
            $source = $obj->{$property};
        }

        if(!preg_match('/\w/i', $obj->{$this->_property}))
        {
            $obj->{$this->_property} = \Str::truncate(strip_tags($source),200,'...');
        }
        else
        {
            $obj->{$this->_property} = \Str::truncate(strip_tags($obj->{$this->_property}),200,'...');
        }

    }
}