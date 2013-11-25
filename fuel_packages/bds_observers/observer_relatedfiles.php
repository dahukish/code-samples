<?php

namespace Orm;

class Observer_Relatedfiles extends Observer
{

	/**
     * @var  string  Default file property
     */
    public static $properties = array('rel_media');

    /**
     * @var  string  Slug property
     */
    protected $_properties;

    /**
     * Set the properties for this observer instance, based on the parent model's
     * configuration or the defined defaults.
     *
     * @param  string  Model class this observer is called on
     */
    public function __construct($class)
    {
        $props = $class::observers(get_class($this));
        $this->_properties = isset($props['properties']) ? $props['properties'] : static::$properties;
    }

    public function before_delete(Model $obj)
    {
        $relations = \Mediahandler\Model_Relation::query()->where(array('object_id','=',$obj->id))->get();
        foreach ($relations as $relation) $relation->delete();
    }

    public function after_save(Model $obj)
    {
        $properties = (array) $this->_properties;
        foreach ($properties as $property)
        {
            if(!is_null(($new_vals = \Input::post($property,NULL))))
            {
				if ($new_vals!="") {
					$new_vals = explode(',',$new_vals);
					$relations = \Mediahandler\Model_Relation::query()->where(array(array('object_id','=',$obj->id),array('field_instance','=',$property)))->get();
					foreach($relations as $r) $r->delete();  # going to reset the associated items below, so just clear old ones...

					foreach ($new_vals as $new_val)
					{
						if ($new_val>0) {

                            $file_rel = \Mediahandler\Model_Relation::forge(array(
									'object_id'=>$obj->id,
									'object_table'=>get_class($obj),
									'file_id'=>$new_val,
									'field_instance'=>$property,
								));

							$file_rel->save();
							$file_rel = NULL;
						}
					}
				}
            }
        }

    }
}