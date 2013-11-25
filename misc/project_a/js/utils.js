/*
* JS Utils file
*
*
*
*/

function validate_cond_obj_temp(cond_obj){

	if(cond_obj.option_value_range){
			if((/\w/.test(cond_obj.condition_from)) && (/\w/.test(cond_obj.condition_to))) return true;
	}else{
	  		if(/\w/.test(cond_obj.condition_from)) return true;
	}

	return false;
}

function validate_cond_obj(cond_obj){

	if(cond_obj.cond_range){
			if((/\w/.test(cond_obj.min_val)) && (/\w/.test(cond_obj.max_val))) return true;
	}else{
	  		if(/\w/.test(cond_obj.min_val)) return true;
	}

	return false;
}

function validate_cond_meta_obj_temp(cond_meta_obj){ // just a pass through for now
	return true;
}

function format_ymd(ts,yr_adjust)
{
	var temp_date = new Date(ts);
	var yr_adj = (yr_adjust)? yr_adjust:0;

	// zero out the time of the object
	temp_date.setHours(0);
	temp_date.setMinutes(0);
	temp_date.setSeconds(0);
	temp_date.setMilliseconds(0);

	var single_digit = function(val)
	{
		return (parseInt(val)>9)? val : '0'+val;
	};

	return ((temp_date.getFullYear()+yr_adj)+'-'+single_digit((temp_date.getMonth()+1))+'-'+single_digit(temp_date.getDate()));
}

function crop_range_helper(range_val,start,jq_obj,offset)
{
	var range = /(\d+)-?(\d+)?$/.exec(range_val);
	var off = (offset)? offset:0;

	if(range.length && (range.length > 2) && (typeof range[2] != 'undefined'))
	{
		var low = parseInt(range[1]);
		var high = parseInt(range[2]);

		var a = (low < high)? low : high ;
		var b = (low < high)? high : low ;

		var a_date = format_ymd(((start + (((a + off)*86400)))*1000));
		var b_date = format_ymd(((start + (((b + off)*86400)))*1000));

		if(a_date === b_date) jq_obj.html(a_date);
		else jq_obj.html(a_date+" &mdash; "+b_date);

	}
	else
	{
		var single = parseInt(range[1]);
		var s_date = format_ymd(((start + ((single*86400)))*1000));
		jq_obj.html(s_date);
	}

}

function valid_crop_date(crop_date, min, max, min_only)
{
	if(min_only) return ((crop_date >= min))? true : false;
	else return ((crop_date >= min) && (crop_date <= max))? true : false;
}

function no_parents_for(obj,tagName,parSearch)
{
	return (obj.prop('tagName')===tagName)?obj:obj.parents(parSearch);
}

function outside_season(parent)
{
	var $form = no_parents_for(parent,'FORM','form');

	// parent.parents('form');
	// var $pp_cb = parent.find('input[id^="form_sel_crops_date_pp_"]');
	var out_season = false;
	if($form.length) out_season = ($form.data('use-limits') && (parseInt($form.data('use-limits'))>0))? false : true; // if the limits are on you are not outside the season and therefore false

	return out_season;
}

function time_with_offset(date_obj,offset_adj)
{
	var os_adj = (typeof offset_adj !=='undefined')? offset_adj:1;  // always default to 1
	var offset = Math.round((date_obj.getTimezoneOffset()*60*1000)); // offset is in minutes so convert to milliseconds
	var time = Math.round((date_obj.getTime()+(offset*os_adj)) / 1000); //have to add the offset back in
	return time;
}

function full_date_validation(obj, min_max_obj, warn_obj, on_success_cb, on_fail_cb)
{
	if(!obj) return false;

	var answer = true;
	var obj_parent_data = false;

	var current_time = time_with_offset((new Date()));

	$parent = obj.parents('[data-js-context="crop-date"]');

	if(obj_parent_data = $parent.data())
	{
		var temp_id = obj.attr('id');

		var m = /\d+?$/i.exec(temp_id);

		var $sib_phase_sel = $('#form_sel_phase_'+m[0]);
		var $sib_crops_date = $('#form_sel_crops_date_'+m[0]);
		var $sib_crops_date_ext = $('#form_sel_crops_date_ext_'+m[0]);

		// get all the pertinent info for the time window
		// var crop_window = crop_parse_min_max($parent,$sib_crops_date_ext,$sib_crops_date.val());
		var crop_window = min_max_obj;

		var late_offset = 0;

		if($sib_crops_date_ext.length)
		{
			switch(parseInt($sib_crops_date_ext.val()))
			{
				case 1:
					crop_window.timenow = crop_window.min;
				break;

				case 2:
					crop_window.timenow = crop_window.min_orig;
					late_offset = 14;
				break;

				case 3:
					crop_window.timenow = crop_window.min;
					late_offset = 14;
				break;
			}

			//keep us out of the past
			if(crop_window.timenow < current_time) crop_window.timenow = current_time;
		}

		if($sib_phase_sel.length)
		{
			if($sib_phase_sel.val() == 0) // validation of a sort -SH
			{
				alert(warn_obj.validation_msg);
				(warn_obj.validation_func)&&(warn_obj.validation_func(on_fail_cb()));
				return false;
			}

			// setup the warning obj
			var warn = {msg:null,cb:null};

			// if this is to be overridden then skip the whole thing -SH
			if(! outside_season($parent))
			{
				var time_range = /(\d+?)(?:-(\d+?))?$/i.exec($sib_phase_sel.val());

				var min = 0;
				var max = 0;

				if(time_range.length == 3)
				{
					min = (parseInt(time_range[1]) <= parseInt(time_range[2])) ? parseInt(time_range[1]) : parseInt(time_range[2]);
					max = (parseInt(time_range[2]) >= parseInt(time_range[1]))? parseInt(time_range[2]) : parseInt(time_range[1]);
				}
				else
				{
					min = parseInt(time_range[1]);
				}

				if(max > 0)
				{
					var diff_max = Math.round((crop_window.max + (((max + late_offset) * 86400) * -1)));
					var diff_min = Math.round((crop_window.max + (((min + late_offset) * 86400) * -1)));

					if(crop_window.timenow < crop_window.min)
					{
						warn.msg = warn_obj.min_msg;
						warn.cb = warn_obj.min_func;
					}
					else if(crop_window.timenow > diff_min)
					{
						warn.msg = warn_obj.hard_max_msg;
						warn.cb = warn_obj.hard_max_func;
					}
					else if(crop_window.timenow > diff_max)
					{
						warn.msg = warn_obj.soft_max_msg;
					}
				}
				else
				{
					var diff = Math.round((crop_window.max + (((min + late_offset) * 86400) * -1)));

					if(crop_window.timenow < crop_window.min)
					{
						warn.msg = warn_obj.min_msg;
						warn.cb = warn_obj.min_func;
					}
					else if(crop_window.timenow > diff)
					{
						warn.msg = warn_obj.hard_max_msg;
						warn.cb = warn_obj.hard_max_func;
					}

				}
			}

			if(warn.msg)
			{
				//TODO: make a homebrew lite-api to handle warnigs in a visually nicer way -SH
				answer = confirm(warn.msg);
			}
		}

	}

	if(! answer)
	{
		(on_fail_cb)&&(on_fail_cb());
		if($sib_phase_sel.length) $sib_phase_sel.val(0);
		return false;
	}
	else (warn.cb)&&(warn.cb()); // if a callback is set run it

	// run any code for the success
	on_success_cb($sib_phase_sel,late_offset);

	return true;
}

function crop_parse_min_max(parent,ext_obj,dateText)
{
	var obj = {status:0, timenow:0, min:0, max:0};

	var t_date = new Date(dateText);
	obj.timenow = time_with_offset(t_date);

	//snap the crop window takes the window
	//timestamp and the selected time to snap to
	var snap_dates = function(ts)
	{
		var new_date = new Date((parseInt(ts)*1000));

		new_date.setFullYear(t_date.getFullYear());

		return time_with_offset(new_date,0); //0 offset will turn it off but still return the timestamp
	};

	if(parent.data('extension'))
	{

		if(ext_obj.length && parseInt(ext_obj.val()))
		{
			obj.min 		= snap_dates(parent.data('pre-date-ext'));
			obj.min_orig 	= snap_dates(parent.data('pre-date'));
			obj.max 		= snap_dates(parent.data('post-date-ext'));
			obj.max_orig 	= snap_dates(parent.data('post-date'));
			obj.status=1;
		}
		else
		{
			obj.min = snap_dates(parent.data('pre-date'));
			obj.max = snap_dates(parent.data('post-date'));
			obj.status=1;
		}
	}
	else
	{
		obj.min = snap_dates(parent.data('pre-date'));
		obj.max = snap_dates(parent.data('post-date'));
		obj.status=1;
	}

	return obj;
}

function onActionCropHelper(dateText,inst,obj)
{
	$this = (obj)?obj:$(this);

	var $parent = $this.parents('[data-js-context="crop-date"]');

	var temp_id = $this.attr('id');
	var m = /\d+?$/i.exec(temp_id);

	var $date_ext_obj = $('#form_sel_crops_date_ext_'+m[0]);
	var $date_input_obj = $parent.find('input[id^="form_sel_crops_date_"]');
	var $seed_hidden = $parent.find('input[id^="form_sel_crops_seed_range_"]');
	var $comp_hidden = $parent.find('input[id^="form_sel_crops_comp_range_"]');
	var $range_html = $parent.find('[data-js-context="comp-range-value"]');
	var $seed_range = $parent.find('.seed-range');

	// if no date passed
	if(!dateText) dateText = $date_input_obj.val();

	// if no instance with the last value
	var last_val = (inst&&inst.lastVal)? inst.lastVal:dateText;

	var min_max_obj = crop_parse_min_max($parent,$date_ext_obj,dateText);

	var on_success_cb = function(phase_obj,offset)
	{
		if(phase_obj && phase_obj.length)
		{
			$date_input_obj.val(format_ymd(min_max_obj.timenow*1000));

			crop_range_helper(phase_obj.val(),min_max_obj.timenow,$range_html,offset);
			// if there is a seed value update the seed range when you update the current date -SH
			if($seed_hidden.length)
			{
				var $seed_html = $parent.find('[data-js-context="seed-range-value"]');
				crop_range_helper($seed_hidden.val(),min_max_obj.timenow,$seed_html);
			}

			if($seed_range.length)
			{
				if(phase_obj.find(':selected').text() === 'start seedlings indoors for transplant')
				{
					$seed_range.show();
				}
				else
				{
					$seed_range.hide();
				}
			}
		}
	};

	var on_fail_cb = function()
	{
		$date_input_obj.val(last_val);
		if($seed_range.length) $seed_range.hide();
	};

	// handles the warnings - DUH
	var warn_obj = {
		validation_msg: 'You must first choose a starting phase (eg: plant from seeds, etc.) before selecting a unit value.',
		validation_func: function(fail_cb){ fail_cb(); },
		min_msg: 'The selected crop cannot be grown before the next growing season and will be scheduled for the next available season. Is this ok?',
		min_func: function()
		{
			var temp_date = format_ymd((parseInt(min_max_obj.min)*1000));
			min_max_obj.timenow = min_max_obj.min;
			$date_input_obj.val(temp_date);
		},
		// this deals with the soft warning to the user, letting them know that the item may not finish in time.
		soft_max_msg: 'The selected crop may not have enough time be grown in the current growing season. Is this ok?',
		soft_max_func: function()
		{
			//console.log('will nto be called for right now');
		},
		// this is a hard warning (this item cannot be set for the time given and action need to be taken to proceed).
		hard_max_msg: 'The selected crop cannot be grown in the current growing season and will be schedule for the next available season. Is this ok?',
		hard_max_func: function()
		{
			var temp_date = format_ymd((parseInt(min_max_obj.min)*1000),1); //add a year
			var temp_next_year = new Date(temp_date);
			min_max_obj.timenow = time_with_offset(temp_next_year); //set to beginning of next season
			$date_input_obj.val(temp_date);
		},
	};

	return full_date_validation($this, min_max_obj, warn_obj, on_success_cb, on_fail_cb);
};


function AjaxObj(path,params,action_type,return_type,s_cb_func,e_cb_func,alert)
{
	this.path = path;
	this.params = params;
	this.action_type = (typeof action_type !== 'undefined')? action_type : 'GET';
	this.return_type = (typeof return_type !== 'undefined')? return_type : 'json';
	this.s_cb_func = (typeof s_cb_func !== 'undefined')? s_cb_func : null;
	this.e_cb_func = (typeof e_cb_func !== 'undefined')? e_cb_func : null;
	this.alert = (typeof alert !== 'undefined')? alert : false;
	this.success = false;
	this.return_data = null;
};

AjaxObj.prototype.execute = function(path,params,action_type,return_type,s_cb_func,e_cb_func,queue)
{
	//the setup
	var _path = (typeof path !== 'undefined' || path != null)? path : this.path;
	var _params = (typeof params !== 'undefined' || params != null)? params : this.params;
	var _action_type = (typeof action_type !== 'undefined' || action_type != null)? action_type : this.action_type;
	var _return_type = (typeof return_type !== 'undefined' || return_type != null)? return_type : this.return_type;
	var _s_cb_func = (typeof s_cb_func !== 'undefined' || s_cb_func != null)? s_cb_func : this.s_cb_func;
	var _e_cb_func = (typeof e_cb_func !== 'undefined' || e_cb_func != null)? e_cb_func : this.e_cb_func;

	//ref to use within ajax call back function
	var that = this;

	var deferreds = [];

	deferreds.push(($.ajax({
	  type: _action_type,
	  url: _path,
	  data: _params,
	  dataType: _return_type
	})));

	// TODO: add queing of promises -SH
	// if(queue)
	// {
	// 	$.each(queue, function(i, item)
	// 	{
 	//    deferreds.push(item);
 	//  });
	// }

	var successFunc = function(s_data)
  	{
		that.success = true;

		//quick fix for now until I find out why the response is not coming back as a json object -SH
		if((typeof s_data === 'string') && (_return_type === 'json')) s_data = $.parseJSON(s_data);

		if(! s_data.status)
		{
			if(_e_cb_func != null && (typeof _e_cb_func === 'function')) _e_cb_func(that,s_data);
			if(that.alert) alert(s_data.msg);
		}
		else
		{
			if(_s_cb_func != null && (typeof _s_cb_func === 'function')) _s_cb_func(that,s_data);
		}

		that.return_data = s_data;
	};

	var failFunc = function(jqXHR, textStatus, errorThrown)
	{
        alert("Status: " + textStatus); alert("Error: " + errorThrown);
    };

    $.when.apply(null,deferreds).then(successFunc,failFunc);

	return this;
};

AjaxObj.prototype.isSuccess = function()
{
	return this.success;
};

AjaxObj.prototype.parseResults = function(parseFunc)
{
	return parseFunc(this.s_data);
};