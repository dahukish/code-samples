<?php
class Model_Page extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'title',
		'body',
		'summary',
		'slug',
		'parent_id',
		'template',
		'created_at',
		'updated_at',
		'order',
		'status',
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
		'Orm\Observer_Slug' => array(
			'events' => array('before_insert'),
		),
		'Orm\Observer_Summarize' => array(
			'events' => array('before_insert'),
			'source' => 'body',
        	'property' => 'summary',
		),
		'Orm\Observer_Editlock' => array(
			'events' => array('after_save'),
		),
		'Orm\Observer_Killcache' => array(
			'events' => array('after_save','after_delete'),
			'special' => array(
				'Model_Page',
				),
		),
		'Orm\Observer_Relatedfiles' => array(
			'events' => array('after_save','before_delete'),
			'properties' => array('rel_media','rel_docs'),
		),
		'Orm\Observer_Parent' => array(
			'events' => array('before_delete'),
			'property' => 'parent_id',
		),
	);

	/**
	 * validate: create a new instance of the validation object specfically for this model.
	 * @param  string $factory
	 * @return Validation $obj
	 */
	public static function validate($factory)
	{
		$val = Validation::forge($factory);
		$val->add_field('title', 'Title', 'required|max_length[255]');
		$val->add_field('body', 'Body', 'required');
		$val->add_field('slug', 'Slug', 'max_length[255]');
		$val->add_field('parent_id', 'Parent Id', 'valid_string[numeric]');
		$val->add_field('template', 'Template', 'max_length[255]');
		$val->add_field('order', 'Order', 'valid_string[numeric]');
		$val->add_field('status', 'Status', 'valid_string[numeric]');

		return $val;
	}

	/**
	 * get_ancestors: get the ancestors of a given page id
	 * @param  integer $id
	 * @return mixed
	 */
	public static function get_ancestors($id)
	{

		$page_ancestors = array();

		$temp_page = static::find($id);

		if(!is_object($temp_page)) return false;

		static::get_parent_recursive($temp_page, $page_ancestors);

		return $page_ancestors;

	}

	/**
	 * build_page_h_path: build a link to a page using
	 * the pages ancestors to construct the path
	 * @param  integer $id
	 * @param  string $link_path
	 * @return mixed
	 */
	public static function build_page_h_path($id, $link_path='pages/')
	{
		$page_ancestors = array();
		$temp_page = static::find($id);
		static::get_parent_id_recursive($temp_page,$page_ancestors);

		if(!empty($page_ancestors)){
			$page_ancestors = array_reverse($page_ancestors);
			foreach ($page_ancestors as $ancestor) {
				$link_path.= $ancestor->slug .'/';
			}
		}else{
			return false;
		}

		return $link_path;
	}

	/**
	 * get_descendant_tree: get a pages descendants
	 * @param  Model_Page $obj
	 * @param  boolean    $top
	 * @param  array      $array
	 * @return mixed
	 */
	public static function get_descendant_tree(Model_Page $obj, $top=false, $array=array())
	{
		$array[$obj->id]['item'] = $obj;

		$children = static::get_children($obj->id);

		if(!empty($children))
		{
			foreach ($children as $c_id => $child)
			{
				$sub_children = static::get_children($child->id);
				if(!empty($sub_children)) $array[$obj->id]['children'][] = static::get_descendant_tree($child,false);
				else $array[$obj->id]['children'][$c_id]['item'] = $child;
			}
		}
		else
		{
			return false;
		}

		return $array;
	}

	/**
	 * get_parent_recursive: an internal helper function used to get the parent of the current page
	 * @param  Model_Page $obj
	 * @param  array     $page_ancestors
	 * @return mixed
	 */
	private static function get_parent_recursive(Model_Page $obj, &$page_ancestors)
	{
		$page_ancestors[] = $page;

		$temp_page = static::find($page->parent_id);

		if(@$temp_page->parent_id !== 0 && isset($temp_page->parent_id))
		{
			if(count($page_ancestors) > 10) return false;
			static::get_parent_recursive($temp_page, $page_ancestors);
		}

		return $temp_page;

	}

	/**
	 * get_children: a shortcut to get he children of a certain page
	 * @param  integer $id
	 * @return mixed
	 */
	public static function get_children($id)
	{
		$children = static::query()->where(array('parent_id','=',$id))->order_by('order','asc')->get();

		if(empty($children)) return false;

		return $children;
	}

	/**
	 * build_page_menu_recursive: build a menu using a descendant tree array -- recursively
	 * @param  array  $descendant_tree
	 * @param  integer  $current_id
	 * @param  integer $level
	 * @param  string  $menu_holder
	 * @param  array   $menu_hack
	 * @return string
	 */
	public static function build_page_menu_recursive($descendant_tree,$current_id,$level=1,$menu_holder="",$menu_hack=array())
	{
		if(!empty($descendant_tree))
		{
			$menu_holder.='<ul class="level-'.$level.'">';
			if(isset($menu_hack['before']) && ($level === 1)) $menu_holder.=$menu_hack['before'];
			foreach ($descendant_tree as $d_id => $d)
			{

				if(isset($d['children']) && !empty($d['children']))
				{
					foreach ($d['children'] as $c_id => $ch)
					{
						$c = (isset($ch['item']))? $ch : current($ch);

						$property_a = isset($c['item']->title)? 'title':'name';
						$property_b = isset($c['item']->slug)? 'slug':'id';
						$class = get_class($c['item']);
						$base = \Inflector::pluralize(basename(strtolower(str_replace('_', '/', $class))));

						$menu_holder .= '<li '.((intval($current_id) === intval($c_id))? 'class="active"': '').' >';
						if(isset($c['item'])) $menu_holder.= \Html::anchor($base.'/'.$c['item']->{$property_b},$c['item']->{$property_a});
						if(isset($c['children']) && !empty($c['children']))
						{
							$new_level = $level+1;
							$menu_holder .= static::build_page_menu_recursive($c['children'],$current_id,$new_level);
						}
						$menu_holder .= '</li>';

					}
				}
				else
				{
					$c = (isset($d['item']))? $d : current($d);
					$property_a = isset($c['item']->title)? 'title':'name';
					$property_b = isset($c['item']->slug)? 'slug':'id';
					$class = get_class($c['item']);
					$base = \Inflector::pluralize(basename(strtolower(str_replace('_', '/', $class))),((preg_match('/a$/i', $class))? 1 : 0));
					$menu_holder .= '<li '.((intval($current_id) === intval($d_id))? 'class="active"': '').' >';
					if(isset($c['item'])) $menu_holder.= \Html::anchor($base.'/'.$c['item']->{$property_b},$c['item']->{$property_a});
					$menu_holder .= '</li>';
				}

			}
			if(isset($menu_hack['after']) && ($level === 1)) $menu_holder.=$menu_hack['after'];
			$menu_holder .= '</ul>';
		}

		return $menu_holder;

	}

}