<?


/* use the 3 column fieldset layout, each form item on a line by itself*/
class Form {
	var $name = "formname";
	var $formpres = array();
	var $formdata = array();
	var $tindex = 1;
	
	function Form ($formpres, $formdata, $name = null) {
		$this->formpres = $formpres;
		$this->formdata = $formdata;
		if (!isset($name ))
			$name = sprintf("form%u",crc32(mt_rand() + time()));
		$this->name = $name;
	}

	function render () {
		$lasthelpstep = false;
		$str = '
		<form id="'.$this->name.'" name="'.$this->name.'" method="POST">';
		
		foreach ($this->formpres as $name => $itemdata) {			
			$formclass = $itemdata[0];
			$item = new $formclass($name, $itemdata[1],$itemdata[2],$itemdata[3],$itemdata[4]);

			if ($lasthelpstep && $lasthelpstep != $item->helpstep) {
				$str .= '
			</fieldset>';
			}
			
			if ($lasthelpstep != $item->helpstep) {
				$lasthelpstep = $item->helpstep;
				$str .= '<fieldset id="helpsection_'.$lasthelpstep.'">';
				$this->helpsteps[] = $lasthelpstep;
			}
			
			$n = $this->name."_".$item->name;
			$t = $this->tindex++;
			$l = $item->label;
			$i = "img/pixel.gif";
			$value = $this->formdata[$item->name][0];
			$style = "";
			$msg = "";
			if ($item->style == "style-required") {
				$i = "img/icons/error.gif";
				$style = 'style="background: rgb(255,255,220);"' ;
				$msg = "Required";
			}
			
			$str.= '
			<div id="'.$n.'_fieldarea" '.$style.' >
				<div class="prop"></div>
				<label for="'.$n.'" tabindex="'.$t.'" >'.$l.'</label>
				'.$item->render($this,$value).'
				<div class="msgarea">
					<img alt="" id="'.$n.'_icon" src="'.$i.'" />
					<span id="'.$n.'_msg">'.$msg.'</span>
				</div>
				<div class="clear"></div>
			</div>
			';
		} //foreach
		
		if ($lasthelpstep)
			$str .= '
			</fieldset>';
			
			
		//end the form and add some script to attach event listeners and validators
		//change - verify that this isnt going to spam with every key press. from docs:
		//	The change event occurs when a control loses the input focus and its value has been modified since gaining focus. This event is valid for INPUT, SELECT, and TEXTAREA. element. 
		//blur - ex trigger required fields validation when tabing out of, or clicking out of a required field
		//	The blur event occurs when an element loses focus either via the pointing device or by tabbing navigation. This event is valid for the following elements: LABEL, INPUT, SELECT, TEXTAREA, and BUTTON. 
		//keyup - seems to not be official w3c, but supported by browsers, will need a resettable delay timer so we dont validate 100x while typing. other events would need to cancel the timer

		$str .= '
		</form>
		
		<script type="text/javascript">
		form_load("'.$this->name.'",
			"'. $_SERVER['SCRIPT_NAME'] .'",
			'.json_encode($this->formpres).',
			'.json_encode($this->formdata).'
		);
		</script>
		';
		return $str;
	}
}


?>