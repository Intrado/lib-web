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

	function Form ($name, $formdata, $helpsteps = null, $buttons = null) {
		$this->name = $name;
		$this->formdata = $formdata;
		$this->helpsteps = $helpsteps;

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
		if (!isset($_REQUEST['form']) || $_REQUEST['form'] != $this->name)
			return false; //nothing to do
		
		if (isset($_REQUEST['ajaxvalidator'])) {

			$jsondata = json_decode($_REQUEST['json'],true);

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

		//ajax post form - merge in data, check validation, etc
		if (isset($_POST['submit'])) {
			//check the form snum vs loaded formdata
			if (isset($_REQUEST['ajax']) && $this->checkForDataChange()) {
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

				if (isset($data['control'])) {
					$controltype = strtolower($data['control'][0]);
					if ($controltype == "checkbox")
						$this->formdata[$name]['value'] = false;
					else if ($controltype == "multicheckbox")
						$this->formdata[$name]['value'] = array();
					else if ($controltype == "restrictedfields")
						$this->formdata[$name]['value'] = array();
				}
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

			$errors = $this->validate();

			//if this is an ajax request, validate now and return json results for the form
			if (isset($_REQUEST['ajax']) && $errors !== false) {
				$result = array("status" => "fail", "validationerrors" => $errors);
				if ($dontexit) {
					return $result;
				} else {
					header("Content-Type: application/json");
					echo json_encode($result);
					exit();
				}
			}
		}

		return false;
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
				$control = array("HiddenField");
			}

			$formclass = $control[0];
			$item = new $formclass($this,$name, $control);
			
			if ($formclass != "FormHtml") {
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
			$showicon = (isset($itemdata['renderoptions']) && isset($itemdata['renderoptions']['icon'])) ? $itemdata['renderoptions']['icon'] : true;
			$showlabel = (isset($itemdata['renderoptions']) && isset($itemdata['renderoptions']['label'])) ? $itemdata['renderoptions']['label'] : true;
			$showerrormessage = (isset($itemdata['renderoptions']) && isset($itemdata['renderoptions']['errormessage'])) ? $itemdata['renderoptions']['errormessage'] : true;

			//check for section titles
			if (is_string($itemdata)) {
				if ($lasthelpstep) {
					$str .= '</table></fieldset>';
					$lasthelpstep = false;
				}

				$str .= '
					<h2>'.$itemdata.'</h2>';
				unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
				continue;
			}

			if (isset($itemdata['control'])) {
				$control = $itemdata['control'];
			} else {
				//set a hidden field
				$control = array("HiddenField");
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
			</table></fieldset>';
			}

			if ($lasthelpstep != $itemdata['helpstep']) {
				$lasthelpstep = $itemdata['helpstep'];
				$str .= '<fieldset id="'. $this->name . '_helpsection_'.$lasthelpstep.'"><table summary="" width="100%" cellspacing="0" table-layout="fixed" class="formcontenttable">';
			}

			$n = $this->name."_".$item->name;
			$l = $itemdata['label'];

			if ($formclass == "FormHtml") {
				$str.= '
				<tr>
					' . ($showlabel ? '<th class="formtableheader"><label class="formlabel" for="'.$n.'" >'.$l.'</label></th>' : '') . '
					' . ($showicon ? '<td class="formtableicon"></td>' : '') . '
					<td class="formtablecontrol">'.$item->render('').'</td>
				</tr>
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

				//see if valrequired is any of the validators
				$isrequired = false;
				foreach ($itemdata['validators'] as $v) {
					if ($v[0] == "ValRequired") {
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
						$style = 'style="background: rgb(255,255,255);"' ; //rgb(225,255,225)
						$msg = false;
					} else {
						list($validator,$msg) =  $valresult;
						$i = "img/icons/exclamation.gif";
						$alt = "Validation Error";
						$style = 'style="background: rgb(255,200,200);"' ;
					}
				} else if (!$this->getSubmit() && $isblank && $isrequired) {
					//show required highlight
					$i = "img/icons/error.gif";
					$alt = "Required Field";
					$style = 'style="background: rgb(255,255,220);"' ;
				}

				$str.= '
				<tr id="'.$n.'_fieldarea" '.$style.'>
					'.($showlabel ? '<th class="formtableheader"><label class="formlabel" for="'.$n.'" tabindex="'.$t.'" >'.$l.'</label></th>' : '').'
					'.($showicon ? '<td class="formtableicon"><img alt="'.$alt.'" title="'.$alt.'" id="'.$n.'_icon" src="'.$i.'" /></td>' : '').'
					<td class="formtablecontrol">
						'.$item->render($value).'
						'.($showerrormessage ? '<div id="'.$n.'_msg" class="underneathmsg">'.($msg ? $msg : "").'</div>' : '').'
					</td>
				</tr>
				';
			}
		} //foreach

		if ($lasthelpstep)
			$str .= '
			</table></fieldset>';

		return $str;
	}

	function render ($ignorelibraries = false) {
		$theme = getBrandTheme();

		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$str = '';
		
		if (!$ignorelibraries)
			$str .= $this->renderJavascriptLibraries();
		
		$str .= '
		<div class="newform_container">
		' . (empty($this->parentform) ? '<form class="newform" id="'.$this->name.'" name="'.$this->name.'" method="POST" action="'.$posturl.'">
		<input name="'.$this->name.'-formsnum" type="hidden" value="' . $this->serialnum . '">' : '') . '
		<table summary="Form" width="100%" cellspacing="0" cellpadding="0" table-layout="fixed" ><tr><td valign="top"> <!-- FORM CONTENT -->';

		$str .= $this->renderFormItems();

		//submit buttons
		foreach ($this->buttons as $code) {
			$str .= $code;
		}

		$str .= '
				<!-- END FORM CONTENT -->
				</td>
				<td id="'.$this->name.'_helpercell" valign="top" width="'.($this->helpsteps ? '100px' : '0px').'">
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
		
		' . (empty($this->parentform) ? '</form>' : '') . '



		</div>
		<div style="clear: both;"></div>

		' . (empty($this->parentform) ? '
		<script type="text/javascript">

		form_load("'.$this->name.'",
			"'.$posturl.'",
			'.json_encode($this->formdata).',
			'.json_encode($this->helpsteps).',
			'.($this->ajaxsubmit ? "true" : "false").'
		);
		</script>
		' : '');
		
		if (empty($this->parentform)) {
			$str .= $this->renderJavascript();
		}
		
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

	//Send an element outside the form some new content
	function modifyElement ($elementname, $htmlcontent) {
		$result = array("status" => "modify", "name" => $elementname, "content" => $htmlcontent);
		header("Content-Type: application/json");
		echo json_encode($result);
		exit();
	}
}

?>