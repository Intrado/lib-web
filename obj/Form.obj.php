<?


/* use the 3 column fieldset layout, each form item on a line by itself*/
class Form {
	var $name = "formname";
	var $formdata = array();
	var $tindex = 1;
	var $serialnum = "";
	
	function Form ($formdata, $name) {
		$this->formdata = $formdata;
		if (!isset($name ))
			$name = sprintf("form%u",crc32(mt_rand() + time()));
		$this->name = $name;
		
		$this->serialnum = $_SESSION["formsnum_$name"] = md5("form" . mt_rand() . microtime());
	}

	function render () {
		$lasthelpstep = false;
		$str = '
		<form class="newform" id="'.$this->name.'" name="'.$this->name.'" method="POST">
		<input name="formsnum_' . $this->name . '" type="hidden" value="' . $this->serialnum . '">';
		
		foreach ($this->formdata as $name => $itemdata) {
			if (isset($itemdata['control'])) {
				$control = $itemdata['control'];
			} else {
				//set a hidden field
				$control = array("Hidden");
			}
			
			$formclass = $control[0];
			$item = new $formclass($name, $control);

			if ($lasthelpstep && $lasthelpstep != $itemdata['helpstep']) {
				$str .= '
			</fieldset>';
			}
			
			if ($lasthelpstep != $itemdata['helpstep']) {
				$lasthelpstep = $itemdata['helpstep'];
				$str .= '<fieldset id="helpsection_'.$lasthelpstep.'">';
				$this->helpsteps[] = $lasthelpstep;
			}
			
			$n = $this->name."_".$item->name;
			$t = $this->tindex++;
			$l = $itemdata['label'];
			$i = "img/pixel.gif";
			$value = $itemdata['value'];
			$style = "";	
			$msg = "";
			
			//see if valrequired is any of the validators
			$isrequired = false;
			foreach ($itemdata['validators'] as $v) {
				if ($v[0] == "ValRequired") {
					$isrequired = true;
					$i = "img/icons/error.gif";
					$style = 'style="background: rgb(255,255,220);"' ;
					$msg = "Required";
					break;
				}
			}
			//check the value, and set style accordingly, dont count required fields with no value
			$valresult = $isrequired && mb_strlen($value) == 0 ? true : Validator::validate_item($this->formdata,$name,$value);
			
			if ($valresult !== true) {
				list($validator,$msg) =  $valresult;
				$i = "img/icons/exclamation.gif";
				$style = 'style="background: rgb(255,200,200);"' ;
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
			'.json_encode($this->formdata).'
		);
		</script>
		';
		return $str;
	}
}


?>