<?

if (get_magic_quotes_gpc()) {
	error_log("ERROR: Disable magic quotes: 'magic_quotes_gpc = Off'");
	exit("ERROR: Disable magic quotes: 'magic_quotes_gpc = Off'");
}

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
	var $rendermode = "normal";
	var $multipart = false;

	function Form ($name, $formdata, $helpsteps = null, $buttons = null, $rendermode = "normal") {
		$this->name = $name;
		$this->formdata = $formdata;
		$this->helpsteps = $helpsteps;
		$this->rendermode = $rendermode;

		if (isset($buttons))
			$this->buttons = $buttons;

		//only use value data in hash, ignore labels, options, etc, etc
		$values = array();
		foreach ($formdata as $k => $v) {
			if (isset($v['value']))
				$values[$k] = $v['value'];
		}
		
		$this->serialnum = md5(serialize($values));
	}

	function handleRequest($dontexit = false) {
		if (!isset($_REQUEST['form']) || $_REQUEST['form'] != $this->name) {
			return false; //nothing to do
		}

		$isAjax = isset($_REQUEST['ajax']);
		$isApi = isset($_REQUEST['api']);

		//single item ajax validation call
		if (isset($_REQUEST['ajaxvalidator'])) {

			// NOTE: maintaing previous behavior while removing errors from httpd log files. See bug:4606
			$requestjson = (isset($_REQUEST['json'])?$_REQUEST['json']:NULL);
			$jsondata = json_decode($requestjson,true);

			$result = array();

			list($form,$item) = explode("_",$_REQUEST['formitem']);

			$itemresult = Validator::validate_item($this->formdata,$item,$jsondata['value'],$jsondata['requiredvalues']);
			if ($itemresult === true) {
				$result['vres'] = true;
			} else {
				list($validator,$msg) = $itemresult;
				$result['vres'] = false;
				$result['vmsg'] = $msg;
				$result['v'] = $validator;
			}

			if ($dontexit) {
				return $result;
			} else {
				header("Content-Type: application/json");
				echo json_encode($result);
				exit();
			}
		}

		if ($isApi) {
			// We only support GET/POST actions on API requests...
			//
			switch ($_SERVER['REQUEST_METHOD']) {
				case "POST":
					// Form POST API call...translate input json to form post variables.
					//
					$payload = json_decode(file_get_contents("php://input"), true);

					foreach ($payload['formData'] as $name => $value) {
						if ($name == "formsnum") {
							$_POST[$this->name . "-$name"] = $value;
						} else {
							$_POST[$this->name . "_$name"] = $value;
						}
					}

					// Hack Alert:
					//
					// Forcing request to "look" like ajax submit.
					// Need to do this because downstream form scripts (that call us)
					// will only generate json response for ajax requests.
					// Yes -- we can certainly update our form scripts to handle API requests properly, but
					// that would require us to modify 100's of scripts...
					//
					$_POST['submit'] = (isset($_GET['submit']) ? $_GET['submit'] : "submit");
					$_GET['ajax'] = true;

					break;

				case "GET":
					// Form GET API call...respond with form data
					//
					$formData = array();

					foreach ($this->getFormdata() as $key => $value) {
						if (is_array($value)) {
							$formData[$key] = array();

							$formData[$key]['value'] = $value['value'];
							$formData[$key]['label'] = $value['label'];
							$formData[$key]['validators'] = $value['validators'];
						}
					}

					header("Content-Type: application/json");
					echo json_encode(array("formData" => $formData));

					exit();

				default:
					$result = array("status" => "fail", "unsupportedaction" => $_SERVER['REQUEST_METHOD']);

					header("Content-Type: application/json");
					echo json_encode($result);

					exit();
			}
		}

		//ajax post form - merge in data, check validation, etc
		if (isset($_POST['submit'])) {
			//check the form snum vs loaded formdata
			if (($isAjax || $isApi) && $this->checkForDataChange()) {
				$result = array("status" => "fail", "datachange" => true);
				if ($dontexit) {
					return $result;
				} else {
					header("Content-Type: application/json");
					echo json_encode($result);
					exit();
				}
			}

			//we need to set all checkboxes to false because if they are unchecked we won't see any
			//POST data for them if they are checked, the POST data will reset them to true
			foreach ($this->formdata as $name => $data) {
				if (is_string($data))
					continue;
				
				// get the form control
				$control = $data['control'];
				// get the class of the form control
				$formclass = $control[0];
				// create an instance of the form item
				$item = new $formclass($this, $name, $control);
				
				// some form items need to be set to an initial value on submit
				// check boxes and multicheck boxes don't send post data when they have nothing checked
				if ($item->clearonsubmit)
					$this->formdata[$name]['value'] = $item->clearvalue;
			}

			foreach ($_POST as $name => $value) {
				if ($name == "submit")
					continue;
				$itemparts = explode("_",$name);
				if (count($itemparts) != 2)
					continue;
				list($form,$item) = $itemparts;
				if (isset($this->formdata[$item])) {
					if (is_array($value)) {
						foreach ($value as $k => $v)
							$value[$k] = trim($v);
						$this->formdata[$item]['value'] = $value;
					} else {
						$this->formdata[$item]['value'] = trim($value);
					}
				}
			}

			// FILES...
			foreach ($this->formdata as $name => $formitem) {
				if ($formitem['control'][0] === 'FileUpload') {
					$fileKey = "{$this->name}_{$name}";
					if (isset($_FILES[$fileKey])) {
						$this->formdata[$name]['value'] = $_FILES[$fileKey];
					}
				}
			}

			$this->preValidation();

			$errors = $this->validate();

			//if this is an ajax request, validate now and return json results for the form
			if (($isAjax || $isApi) && ($errors !== false)) {
				$result = array("status" => "fail", "validationerrors" => $errors);
				if ($dontexit) {
					return $result;
				} else {
					header("Content-Type: application/json");
					echo json_encode($result);
					exit();
				}
			}

			// If there were no validation errors
			if ($errors === false) {

				// Go ahead with handling the form data submission
				$location = $this->handleSubmit($this->getSubmit(), $this->getData());

				// If handleSubmit returned a valid page to send us to post-submission
				if ($location !== false) {

					// For compatibility with non-AJAX form submissions
					if ($isAjax)
						$form->sendTo($location);
					else
						redirect($location);
				}
			}
		}

		return false;
	}

	// This is a no-op function stub for the new PageForms that will provide this method;
	// see _PageTemplate.php PageForm::handleSubmit() for an example of what to do
	function handleSubmit($button, $data) {
		return(false);
	}
	
	function markRequired($item) {
		array_unshift($this->formdata[$item]['validators'], array("ValRequired"));
	}
	
	function preValidation() {
		//do nothing
	}

	function renderJavascript() {
		$str = '';

		foreach ($this->formdata as $name => $itemdata) {
			if (is_string($itemdata))
				continue;
				
			if (isset($itemdata['control'])) {
				$control = $itemdata['control'];
			} else {
				//set a hidden field
				$control = array("HiddenField"); //FIXME just continue? no point in echoing ''
			}

			$formclass = $control[0];
			$item = new $formclass($this,$name, $control);
			
			if ($formclass != "FormHtml") { //FIXME what is the harm of calling renderjs for html? it returns ''
				$value = $itemdata['value'];
				$str .= $item->renderJavascript($value);
			}
		} //foreach

		return '<script type="text/javascript">' . $str . '</script>';
	}
	
	function renderJavascriptLibraries() {
		static $renderedformclasses = array(); // Used to determine if a formitem's javascript libraries have already been loaded.
		
		$str = '';

		foreach ($this->formdata as $name => $itemdata) {
			if (is_string($itemdata))
				continue;
				
			if (isset($itemdata['control'])) {
				$control = $itemdata['control'];
			} else {
				//set a hidden field
				$control = array("HiddenField");
			}

			$formclass = $control[0];
			if (in_array($formclass, $renderedformclasses))
				continue;
				
			$renderedformclasses[] = $formclass;
			
			$item = new $formclass($this,$name, $control);
			
			$js = $item->renderJavascriptLibraries();
			$str .= $js;
		} //foreach

		return $str;
	}
	
	function renderFormItems() {
		$lasthelpstep = false;
		$str = '';
		foreach ($this->formdata as $name => $itemdata) {
			//check for section titles
			if (is_string($itemdata)) {
				if ($lasthelpstep) {
					$str .= '</fieldset>';
					$lasthelpstep = false;
				}

				$str .= '
					<h2 class="formsectionheader">'.$itemdata.'</h2>';
				unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
				continue;
			}

			if (isset($itemdata['control'])) {
				$control = $itemdata['control'];
			}

			$formclass = $control[0];
			$item = new $formclass($this,$name, $control);

			//inject which function to use for getting the value from this control
			$this->formdata[$name]['jsgetvalue'] = $item->jsGetValue();

			if ($formclass == "HiddenField") {
				$str.= $item->render($itemdata['value']);
				unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
				continue;
			}

			if ($lasthelpstep && $lasthelpstep != $itemdata['helpstep']) {
				$str .= '
			</fieldset>';
			}

			if (isset($itemdata['helpstep']) && ($lasthelpstep != $itemdata['helpstep'])) {
				$lasthelpstep = $itemdata['helpstep'];
				$str .= '<fieldset id="'. $this->name . '_helpsection_'.$lasthelpstep.'">';
			}

			$n = $this->name."_".$item->name;
			$l = $itemdata['label'];

			if ($formclass == "FormHtml") {

				$str.= '
						<div class="formfieldarea cf ' . $this->rendermode . '" id="'.$n.'_fieldarea" >
							<div class="formtitle">
								<span class="formlabel">'.$l.'</span>
							</div>
							<!--div class="formicon"></div-->
							<div class="formcontrol cf">'.$item->render("").'</div>
						</div>
						';
				
				unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
			} else {
				$value = $itemdata['value'];
				$requiredfields = isset($itemdata['requires']) ? $this->getFieldValues($itemdata['requires']) : array();
				$t = $this->tindex++;
				$i = "img/pixel.gif";
				$alt = "";
				$style = "";
				$msg = false;

				//see if isrequired on any of the validators
				$isrequired = false;
				foreach ($itemdata['validators'] as $v) {
					$validator = new $v[0]();
					if ($validator->isrequired) {
						$isrequired = true;
						break;
					}
				}

				$isblank = (is_array($value) && !count($value)) || (!is_array($value) && mb_strlen($value) == 0);

				if ($this->getSubmit() || !$isblank) {
					//validate and show normally
					$valresult = Validator::validate_item($this->formdata,$name,$value,$requiredfields);
					if ($valresult === true) {
						$i = "img/icons/accept.gif";
						$alt = "Valid";
						$style = 'style="background: transparent;"' ; //rgb(225,255,225)
						$msg = false;
					} else {
						list($validator,$msg) =  $valresult;
						$i = "img/icons/exclamation.gif";
						$alt = "Validation Error";
						$style = 'style="background: transparent;"' ;
					}
				} else if (!$this->getSubmit() && $isblank && $isrequired) {
					//show required highlight
					$i = "img/icons/error.gif";
					$alt = "Required Field";
					$style = 'style="background: transparent;"' ;
				}
			
				$str.= '
					<div class="formfieldarea cf ' . $this->rendermode . '" id="'.$n.'_fieldarea" '.$style.'>
						<div class="formtitle">
							<label class="formlabel" for="'.$n.'" >'.$l.'</label>
							<img class="formicon" alt="'.$alt.'" title="'.$alt.'" id="'.$n.'_icon" src="'.$i.'" />
						</div>
						<div class="formcontrol cf"><span>
											'.$item->render($value).'
						</span></div>
						<div id="'.$n.'_msg" class="underneathmsg cf">'.($msg ? $msg : "").'</div>
					</div>
					';
				
			}
		} //foreach

		if ($lasthelpstep)
			$str .= '
			</fieldset>';

		return $str;
	}

	function render ($ignorelibraries = false) {
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$str = '';
		
		if (!$ignorelibraries)
			$str .= $this->renderJavascriptLibraries();

		$enctype = ($this->multipart) ? ' enctype="multipart/form-data"' : '';

		$str .= '
		<div class="newform_container cf">
		<form class="newform" id="'.$this->name.'" name="'.$this->name.'" method="POST" action="'.$posturl.'"' . $enctype . '>
		<!--[if IE]><input type="text" style="display: none;" disabled="disabled" size="1" /><![endif]-->
		<input name="'.$this->name.'-formsnum" type="hidden" value="' . $this->serialnum . '">
		<table summary="Form" class="form_table" ><tr><td valign="top"> <!-- FORM CONTENT -->';

		$str .= $this->renderFormItems();

		//submit buttons
		foreach ($this->buttons as $code) {
			$str .= $code;
		}

		$str .= '
				<div id="'.$this->name.'_spinner" class="formspinner" style="display: none;"><img src="img/ajax-loader.gif" alt="Loading..."></div>
				<!-- END FORM CONTENT -->
				</td>
				<td id="'.$this->name.'_helpercell" valign="top">
				<!-- HELPER -->
				<div id="'.$this->name.'_startguide" style="float: right; padding-top: 3px;">'.($this->helpsteps ? icon_button("Guide","information","return form_enable_helper(event);") : "").'</div>
				<div id="'.$this->name.'_helper" class="helper">
					<div class="title"><a style="float: right;" href="#" onclick="form_disable_helper(event); return false;"><img src="img/icons/cross.gif" alt="Close Guide" title="Close"></a>Guide</div>
					<div class="helpercontent" id="'.$this->name.'_helpercontent" ></div>
					<div class="toolbar" >
						<a href="#" style="float:left;" onclick="this.blur(); return form_step_handler(event,-1);"><img src="img/icons/fugue/arrow_090.gif" border="0" alt="Previous Step" title="Previous Step" width="16"></a>
						<a href="#" style="float:left;" onclick="this.blur(); return form_step_handler(event,1);"><img src="img/icons/fugue/arrow_270.gif" border="0" alt="Next Step" title="Next Step" width="16"></a>
						<div id="'.$this->name.'_helperinfo" class="info">Click the arrow to begin</div>
					</div>
				</div>
				<!-- END HELPER -->
				</td>
			</tr>
		</table>
		</form>
		</div>
		';
		$str .= $this->renderFormJavascript($posturl);
		
		$str .= $this->renderJavascript();
		
		return $str;
	}
	
	function renderFormJavascript($posturl) {
		$str = '
		<script type="text/javascript">
			form_load("'.$this->name.'",
				"'.$posturl.'",
				'.json_encode($this->formdata).',
				'.json_encode($this->helpsteps).',
				'.($this->ajaxsubmit ? "true" : "false").'
			);
		</script>';
		return $str;
	}

	function checkForDataChange() {
		return $this->serialnum != $_POST[$this->name.'-formsnum'];
	}

	function validate () {
		if ($this->validationresults !== null)
			return $this->validationresults;

		$this->validationresults= array();

		$anyerrors = false;
		foreach ($this->formdata as $name => $data) {
			if (is_string($data))
				continue;
			if (!isset($data['validators']))
				continue;
			$requiredfields = isset($data['requires']) ? $this->getFieldValues($data['requires']) : array();
			$itemresult = Validator::validate_item($this->formdata,$name,$data['value'],$requiredfields);
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
			if (is_string($data))
				continue;
			if (isset($data['value']))
				$res[$name] = $data['value'];
		}
		return $res;
	}

	function getFormdata() {
		$fd = $this->formdata;

		// Add this too:
		//<input name="'.$this->name.'-formsnum" type="hidden" value="' . $this->serialnum . '">
		$fd['formsnum'] = Array(
			'label' => '',
			'value' => $this->serialnum,
			'validators' => Array(),
			'control' => Array(
				'0' => 'HiddenField',
				'maxlength' => 255,
				'size' => 0
			),
			'helpstep' => 1,
			'jsgetvalue' => 'form_default_get_value'
		);

		return($fd);
	}

	//gets assoc array for just provided field names
	function getFieldValues ($names) {
		$res = array();
		foreach ($names as $name) {
			if (isset($this->formdata[$name]['value']))
				$res[$name] = $this->formdata[$name]['value'];
		}
		return $res;
	}

	function isAjaxSubmit() {
		return isset($_GET['ajax']) && isset($_POST['submit']);
	}

	function getSubmit() {
		return(isset($_POST['submit']) && isset($_REQUEST['form']) && ($_REQUEST['form'] == $this->name) ? $_POST['submit'] : false);
	}

	//sends repsonse to an ajax call that will redirect the browser to a url
	// SMK added optional 'extra' argument to give us a chance to inject
	// some extra data into the JSON for the client to have access to
	function sendTo ($url, $extra=Array()) {
		$default = array("status" => "success", "nexturl" => $url);
		$result = is_array($extra) ? array_merge($default, $extra) : $default;
		header("Content-Type: application/json");
		echo json_encode($result);
		exit();
	}
	
	// fire observable event on client side.
	function fireEvent ($memo) {
		$result = array("status" => "fireevent", "memo" => $memo);
		header("Content-Type: application/json");
		echo json_encode($result);
		exit();
	}

	//Send an element outside the form some new content
	function modifyElement ($elementname, $htmlcontent) {
		$result = array("status" => "modify", "name" => $elementname, "content" => $htmlcontent);
		header("Content-Type: application/json");
		echo json_encode($result);
		exit();
	}
}

?>
