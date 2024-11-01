<?php

// skale-social plugin - util function


class SkaleDSutil extends SkaleDSwp{
	public function delete_links(){

	}

	//read the options values
	function get_options($set_defaults = false){
		if(!$this->cfgObj){
			$obj = array();
			foreach($this->cfg as $sds_option){
				$obj[$sds_option["name"]] = get_option($sds_option["name"]);

				//set default value for options.
				if($set_defaults){
					if(isset($sds_option["value"]) && strlen($sds_option["value"])){
						update_option($sds_option["name"], $sds_option["value"]);
					}
				}
			}

			//get the time when this links were created
			$obj["skale_time"] = get_option($this->pre."_skale_time");
			if(!$obj["skale_time"]){
				$obj["skale_time"] = date('H:i:s', time());
				update_option($this->pre."_skale_time", $obj["skale_time"]);
			}


			$this->cfgObj = $obj;
		} else{
			$obj = $this->cfgObj;
		}


		return $obj;
	}

	// form input with group
	public function generate_input($opts)
	{
		// check if opts passed is an array
		if( ! is_array($opts))
			return 'Variable passed not an array';

		// define variable
		$input = '';

		// if we're including the form group div

		// if a tooltip has been set
		if(isset($opts['tooltip']) && $opts['tooltip'] != '')
			$tooltip = 'data-toggle="tooltip" data-placement="right" data-original-title="'.$opts['tooltip'].'"';
		// no tooltip
		else
			$tooltip = '';


		if(!isset($opts['box-class'])){
			$opts['box-class'] = "";
		}


		// input div
		$input .='<span class="sds-input-box '.$opts['box-class'].' option-'.$opts['box-class'].' option-'.$opts['name'].'-box ">';

		if(isset($opts['menu-item'])){
			$input .='<a href="#" class="sds-menu-item">'.$opts['menu-item'].' <i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i></a>';
		}

		// label with tooltip
		if($opts['type'] != "info"){
			$input .= '<label for="'.$opts['name'].'" class="sds-option-label" '.$tooltip.'>'.$opts['label'].'</label>';
		}

		// switch based on the inputn type
		switch($opts['type'])
		{
			case 'text':
			default:
				$input.='<input class="sds-input-option" name="'.$opts['name'].'" id="'.$opts['name'].'" type="text" value="'.$opts['value'].'" placeholder="'.$opts['placeholder'].'" '.(isset($opts['disabled']) ? $opts['disabled'] : null).' />';
			break;

			case 'error':
				$input.='<p class="text-danger">'.$opts['error'].'</p>';
			break;

			case 'info':
				if(!isset($opts['content'])){$opts['content'] = "";}
				$input.='<div class="wpw-options-info">
						    <h3>'.$opts['label'].'</h3>
						    <div>'.$opts['content'].'</div>
						 </div>';
			break;

			case 'number':
				$input.='<input class="sds-input-option" name="'.$opts['name'].'" id="'.$opts['name'].'" type="number" value="'.$opts['value'].'" placeholder="'.$opts['placeholder'].'" />';
			break;

			case 'colorpicker':
				$input.= '<input id="'.$opts['name'].'" name="'.$opts['name'].'" type="text" class="sds-colorpicker form-control" value="'.$opts['value'].'" style="border-color: '.($opts['value'] != '' ? $opts['value'] : '#eaeaea').'" />';
			break;

			case 'textarea':
				$input.='<textarea class="form-control '.(isset($opts['class']) ? $opts['class'] : null).'" name="'.$opts['name'].'" id="'.$opts['name'].'" rows="'.$opts['rows'].'">'.$opts['value'].'</textarea>';
			break;

			case 'checkbox':
				$input.='<input class="'.(isset($opts['class']) ? $opts['class'] : null).'" name="'.$opts['name'].'" id="'.$opts['name'].'" type="checkbox" '.$opts['checked'].' value="'.$opts['value'].'" '.(isset($opts['disabled']) ? $opts['disabled'] : null).' />';
			break;

			case 'select':
				$input.='<select class="sds-input-option" name="'.$opts['name'].'" id="'.$opts['name'].'">';

				// add all options
				foreach($opts['options'] as $key => $value)
				{
					$input.= '<option value="'.$value.'" '.($value == $opts['selected'] ? 'selected="selected"' : null).'>'.$key.'</option>';
				}

				$input.='</select>';
			break;
		}

		if(isset($opts['description'])){
			$input.= '<span class="sds-info">'.$opts['description'].'</span>';
		}



		// close input div
		$input.= '</span>';

		if(isset($opts['br'])){
			$input.= '<br class="clear-both"/>';
		}
		if(isset($opts['hr'])){
			$input.= '<hr class="clear-both"/>';
		}

		// return the input
		return $input;
	}

}
