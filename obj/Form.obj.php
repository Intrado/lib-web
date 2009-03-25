<?


/* use the 3 column fieldset layout, each form item on a line by itself*/
class Form {
	var $name;
	var $formdata;
	var $helpsteps;
	var $buttons = array("Submit");
	var $tindex = 1;
	var $serialnum = "";
	var $validationresults = null;
	var $ajaxsubmit = true;
	
	function Form ($name, $formdata, $helpsteps, $buttons = null) {
		$this->name = $name;
		$this->formdata = $formdata;
		$this->helpsteps = $helpsteps;
		
		if (isset($buttons))
			$this->buttons = $buttons;
		
		$this->serialnum = $_SESSION["formsnum_$name"] = md5("form" . mt_rand() . microtime());
	}
	
	function handleRequest() {
		if (!isset($_REQUEST['form']) || $_REQUEST['form'] != $this->name)
			return false; //nothing to do
		
		if (isset($_REQUEST['ajaxvalidator'])) {
			$result = array();
		
			list($form,$item) = explode("_",$_REQUEST['formitem']);
			
			$itemresult = Validator::validate_item($this->formdata,$item,$_REQUEST['value']);
			if ($itemresult === true) {
				$result['vres'] = true;
			} else {
				list($validator,$msg) = $itemresult;
				$result['vres'] = false;
				$result['vmsg'] = $msg;
				$result['v'] = $validator;
			}
			
			header("Content-Type: application/json");
			echo json_encode($result);
			
			exit();
		}
		
		//ajax post form - merge in data, check validation, etc
		if ($_POST['submit']) {
			foreach ($_POST as $name => $value) {
				if ($name == "submit")
					continue;
				list($form,$item) = explode("_",$name);
				if (isset($this->formdata[$item])) {
					$this->formdata[$item]['value'] = trim($value);
				}
			}
			
			$errors = $this->validate();
						
			//if this is an ajax request, validate now and return json results for the form
			if (isset($_REQUEST['ajax']) && $errors !== false) {
				$result = array("status" => "fail", "validationerrors" => $errors);
				header("Content-Type: application/json");
				echo json_encode($result);
				exit();
			}
		}
	}

	function render () {
		$theme = getBrandTheme();
		$lasthelpstep = false;
		
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$str = '
		<div class="newform_container">
		<form class="newform" id="'.$this->name.'" name="'.$this->name.'" method="POST" action="'.$posturl.'">
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
				$str .= '<fieldset id="'. $this->name . '_helpsection_'.$lasthelpstep.'">';
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
			if ($this->getSubmit()) {
				$valresult = Validator::validate_item($this->formdata,$name,$value);
				if ($valresult === true && mb_strlen($value) > 0) {
					$i = "img/icons/accept.gif";
					$style = 'style="background: rgb(225,255,225);"' ;
					$msg = "OK";
				}
			} else {
				$valresult = $isrequired && mb_strlen($value) == 0 ? true : Validator::validate_item($this->formdata,$name,$value);
			}
			
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
		
		//submit buttons
		foreach ($this->buttons as $code) {
			$str .= $code;
		}
		
		$str .= '
		</form>
		
		<div id="'.$this->name.'_helper" class="helper">
			<div class="toolbar" >
				<a href="#" style="float:left;" ><img src="img/arrowleftmonth.gif" border=0></a>
				<a href="#" style="float:right;" ><img src="img/arrowrightmonth.gif" border=0></a>
				Guide
			</div>
			<div class="content" id="'.$this->name.'_helpercontent" >'.$this->helpsteps[0].'</div>
		</div>
		
		</div>
		<div style="clear: both;"></div>

		<script type="text/javascript">
		form_load("'.$this->name.'",
			"'.$posturl.'",
			'.json_encode($this->formdata).',
			'.json_encode($this->helpsteps).',
			'.($this->ajaxsubmit ? "true" : "false").'
		);
		</script>
		';
		return $str;
	}
	

	function validate () {
		if ($this->validationresults !== null)
			return $this->validationresults;
		
		$this->validationresults= array();
		
		$anyerrors = false;
		foreach ($this->formdata as $name => $data) {
			$itemresult = Validator::validate_item($this->formdata,$name,$data['value']);
			if ($itemresult === true) {
				$this->validationresults[] = array("name" => $name,"vres" => true);
			} else {
				$anyerrors = true;
				list($validator,$msg) = $itemresult;
				$this->validationresults[] = array("name" => $name,"vres" => false,"vmsg" => $msg, "v" => $validator);
			}
		}
		
		if ($anyerrors) {
			return $this->validationresults;
		} else {
			$this->validationresults = false;
			return false;
		}
	}
	
	function getData () {
		$res = array();
		foreach ($this->formdata as $name => $data) {
			$res[$name] = $data['value'];
		}
		return $res;
	}
	
	function isAjaxSubmit() {
		return isset($_GET['ajax']) && isset($_POST['submit']);
	}
	
	function getSubmit() {
		if (!isset($_POST['submit']) || $_REQUEST['form'] != $this->name)
			return false;
		return $_POST['submit'];
	}
	
	//sends repsonse to an ajax call that will redirect the browser to a url
	function sendTo ($url) {
		$result = array("status" => "success", "nexturl" => $url);
		header("Content-Type: application/json");
		echo json_encode($result);
		exit();
	}
}


?>