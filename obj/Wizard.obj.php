<?
class Wizard {
	var $name;
	var $wizdata;
	var $filteredwizdata;
	var $steplist;
	var $curstep;
	var $datachange;
	var $finish;
	var $doneurl = false;
	
	function Wizard ($name, $wizdata, $finish) {
		$this->name = $name;
		$this->wizdata = $wizdata;

		if (!isset($_SESSION[$name])) {
			$_SESSION[$name] = array("data" => array());
		}

		$this->filteredwizdata = $this->filter();
		//add finisher
		$this->wizdata['finish'] = $finish;
		$this->filteredwizdata['finish'] = $finish;
		
		$this->steplist = $this->getStepList();
		if (isset($_SESSION[$name]['step'])) {
			$this->curstep = $_SESSION[$name]['step'];
		} else {
			$_SESSION[$name]['step'] = $curstep = $this->steplist[0];
			redirect($_SERVER['SCRIPT_NAME']."?step=$curstep"); //when initializing, redirect to first step
		}
	}
	
	function getStepData ($curstep = null) {
		if ($curstep == null)
			$curstep = $this->curstep;
		return $this->_getStepData($this->wizdata, $curstep);
	}

	function _getStepData ($wizdata, $curstep) {
		//remove any leading slash
		if (strpos($curstep,"/") === 0)
			$curstep = substr($curstep,1);
		//see if we need to descend a level
		if (($pos = strpos($curstep,"/")) !== false) {
			$first = substr($curstep,0,$pos);
			$tail = substr($curstep,$pos+1);
			return $this->_getStepData($wizdata[$first]->children,$tail);
		} else {
			return $wizdata[$curstep];
		}
	}
	
	function getStepList () {
		return $this->_getStepList($this->filteredwizdata,"",true);
	}

	function _getStepList ($wizdata, $curstep = "", $reset = true) {
		static $list;
		if ($reset)
			$list = array();
		
		foreach ($wizdata as $wizstep => $wizstepdata) {
			if ($wizstepdata instanceof WizSection) {
				$this->_getStepList($wizstepdata->children,$curstep . "/" . $wizstep,false);
			} else {
				$list[] = $curstep . "/" . $wizstep;
			}
		}
		
		return $list;
	}
	
	function getCurrentStep () {
		return $this->curstep;
	}
	
	function setCurrentStep ($curstep) {
		//TODO validate this is a valid step to go to
		if (in_array($curstep,$this->steplist))
			$this->curstep = $curstep;
	}
	
	function getNextStep () {
		return $this->_getNextStep($this->steplist, $this->curstep);
	}

	function _getNextStep ($list,$curstep) {
		//prepend leading slash
		if (strpos($curstep,"/") !== 0)
			$curstep = "/" . $curstep;
		
		$pos = array_search($curstep,$list);
		if ($pos !== false && isset($list[$pos+1]))
			return $list[$pos+1];
		else
			return false;
	}
	
	function getPrevStep () {
		return $this->_getPrevStep($this->steplist, $this->curstep);
	}

	function _getPrevStep ($list,$curstep) {
		//prepend leading slash
		if (strpos($curstep,"/") !== 0)
			$curstep = "/" . $curstep;
		
		$pos = array_search($curstep,$list);
		if ($pos !== false && isset($list[$pos-1]))
			return $list[$pos-1];
		else
			return false;
	}

	function filter () {
		return $this->_filter($this->wizdata,"");
	}

	function _filter ($wizdata, $curstep = "") {
		$newwizdata = array();
		foreach ($wizdata as $wizstep => $wizstepdata) {
			if ($wizstepdata instanceof WizSection) {
				$substeps = $this->_filter($wizstepdata->children,$curstep . "/" . $wizstep);
				if (count($substeps) > 0)
					$newwizdata[$wizstep] = new WizSection($wizstepdata->title,$substeps);
			} else if ($wizstepdata instanceof WizStep) {
				if (isset($_SESSION[$this->name]['data']) && $wizstepdata->isEnabled($_SESSION[$this->name]['data'],$curstep . "/" . $wizstep))
					$newwizdata[$wizstep] = $wizstepdata;
			}
		}
		return $newwizdata;
	}
	
	function getForm() {
		$stepdata = $this->getStepData();		
		$form = $stepdata->getForm($_SESSION[$this->name]['data'], $this->curstep);
		$form->ajaxsubmit = true;
		
		$form->buttons = array();
		
		//insert a hidden button next or done that will be used if someone presses enter key in a text field
		//do this so that the first visible button can be 'previous'
		$form->buttons[] = hidden_submit_button($this->getNextStep() ? "next" : "done");
		
		//real buttons		
		if ($this->getPrevStep())
			$form->buttons[] = submit_button("Previous","prev","arrow_left");
		$form->buttons[] = icon_button("Cancel","cross","if(confirm('".addslashes(_L("Are you sure you want to cancel?"))."')) window.location='?cancel';");
		$form->buttons[] = submit_button("Next","next","arrow_right");
		
		//merge in any existing wizard post data, except transient fields
		if (isset($_SESSION[$this->name]['data'][$this->curstep])) {
			foreach ($_SESSION[$this->name]['data'][$this->curstep] as $name => $value) {
				if (isset($form->formdata[$name])) {
					//only set the value if the field is not marked as transient, transient values reset to the provided values between steps.
					if (!isset($form->formdata[$name]['transient']) || $form->formdata[$name]['transient'] == false)
						$form->formdata[$name]['value'] = $value;
				}
			}
		}
		
		return $form;
	}
	
	
	function _renderNav ($wizdata,$curstep = "",$depth = 0) {
		$res = '';
		$itemcount = 0;
		
		$res .= str_repeat("\t",$depth) . '<ol class="wiznav_'.$depth.'">' . "\n";
		foreach ($wizdata as $wizstep => $wizstepdata) {
			$thisstep = $curstep . "/" . $wizstep;
			if ($wizstepdata instanceof WizSection) {
				$subres = $this->_renderNav($wizstepdata->children,$thisstep,$depth + 1,false);
				if ($subres) {
					$itemcount++;
					$res .= str_repeat("\t",$depth+1) . '<li class="wiznav_'.$depth.'"><span class="wiznav_header">' . $wizstepdata->title . ":</span>\n";
					$res .= $subres;
					$res .= str_repeat("\t",$depth+1) . '</li>' . "\n";
				}
			} else {
				$stepnum = array_search($thisstep,$this->steplist);
				$curstepnum = array_search($this->curstep,$this->steplist);
				
				if ($stepnum === $curstepnum) {
					$itemcount++;
					$res .= str_repeat("\t",$depth+1) . '<li class="wiznav_'.$depth.' wiznav_active" ><img src="img/icons/diagona/10/131.gif" alt="">'.$this->getStepData($thisstep)->title.'</li>' . "\n";
				} else if($stepnum !== false && $stepnum < $curstepnum) {
					$itemcount++;
					$res .= str_repeat("\t",$depth+1) . '<li class="wiznav_'.$depth.' wiznav_enabled"><a href="?step='.$thisstep.'"><img src="img/icons/diagona/10/102.gif" alt="">'.$this->getStepData($thisstep)->title.'</a></li>' . "\n";
				} else if ($stepnum !== false) {
					$itemcount++;
					$res .= str_repeat("\t",$depth+1) . '<li class="wiznav_'.$depth.' wiznav_disabled"><img src="img/icons/diagona/10/159.gif" alt="">'.$this->getStepData($thisstep)->title.'</li>' . "\n";
				}
			}
		}
		
		$res .= str_repeat("\t",$depth) . '</ol><div style="clear: both;"></div>' . "\n";
				
		if ($itemcount)
			return $res;
		else
			return "";
	}
	function render() {
		$stepdata = $this->getStepData();
		$navhtml = '<div class="wiznavcontainer"><h4>Progress</h4>' . $this->_renderNav($this->wizdata) . '</div>';
		
		
		if ($stepdata instanceof WizStep)
			$mainhtml = $this->getForm()->render();
		else if ($stepdata instanceof WizFinish) {
			$mainhtml = $stepdata->getFinishPage($_SESSION[$this->name]['data']);
			if ($this->doneurl)
				$mainhtml .= icon_button(_L("Done"),"fugue/arrow_180",null, $this->doneurl);
		}
		
		$res = '<table border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr>
							<td valign="top" width="150px" style="border-right: 1px solid black;">'.$navhtml.'</td>
							<td valign="top" width="auto" style="padding: 3px;">'.$mainhtml.'</td>
					</tr>
				</table>';
		
		return $res;
	}
	
	function handleRequest () {
		if (isset($_GET['cancel']) || isset($_GET['new'])) {
			unset($_SESSION[$this->name]);
			if (isset($_GET['cancel']) && $this->doneurl) {
				redirect($this->doneurl);
			} else {
				$_SESSION[$this->name] = array("data" => array());
				$_SESSION[$this->name]['step'] = $step = $this->steplist[0];
				redirect($_SERVER['SCRIPT_NAME']."?step=$step");
			}
		}
		
		if (isset($_GET['step'])) {
			//TODO check that the step is less than current step
			if (!isset($_SESSION[$this->name]['data']['finish'])) {
				$_SESSION[$this->name]['step'] = $_GET['step'];
				$this->setCurrentStep($_GET['step']);
			} else {
				$_SESSION[$this->name]['step'] = "/finish";
				$this->setCurrentStep("/finish");
			}
		}
		
		$stepdata = $this->getStepData();
		
		if ($stepdata instanceof WizStep) {
		
			$step = $this->getCurrentStep();
			$form = $stepdata->getForm($_SESSION[$this->name]['data'], $step);
			//if they pressed the previous button, handle form submits slightly different
			if ($form->getSubmit() == "prev") {	
				$results = $form->handleRequest(true); //dont exit, return results so we can tweak them
				if ($results !== false) {
					if (isset($results['validationerrors'])) {
						//add option to override default behavior
						$results['dontsaveurl'] = "?step=" . $this->getPrevStep();
					}
					
					header("Content-Type: application/json");
					echo json_encode($results);
					exit();
				}
			} else {
				$form->handleRequest(false); //allow this to handle like normal
			}
			
			$this->datachange = false;
			$errors = false;
			//check for form submission
			if ($button = $form->getSubmit()) { //checks for submit and merges in post data
				$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
				
				if ($form->checkForDataChange()) {
					$this->datachange = true;
				} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
					$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
					
					$_SESSION[$this->name]['data'][$step] = $postdata;
					$this->filteredwizdata = $this->filter();
					$this->steplist = $this->getStepList();
					
					if ($ajax) {
						if ($button == "next") {
							$next = $this->getNextStep();
							
							if ($next) {
								$form->sendTo($_SERVER['SCRIPT_NAME']."?step=$next");
							} else {
								//wizard is all done, save the data, lock out the session data, and go to the finish page
								if (!isset($_SESSION[$this->name]['data']['finish'])) {
									$_SESSION[$this->name]['data']['finish'] = true;
									$this->getStepData("finish")->finish($_SESSION[$this->name]['data']);
								}
								$form->sendTo($_SERVER['SCRIPT_NAME']."?step=/finish");
							}
						} else if ($button == "prev") {
							if ($next = $this->getPrevStep())
								$form->sendTo($_SERVER['SCRIPT_NAME']."?step=$next");
						}
					}
				}
			}
		}
	}
		
	function dataChange() {
		return $this->datachange;
	}
}

abstract class WizStep {
	var $title;
	
	function WizStep($title) {
		$this->title = $title;
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		return true;
	}

	//returns a populated Form object. use wizdata to prepopulate appropriate fields
	abstract function getForm($postdata, $curstep);
}

class WizSection {
	var $title;
	var $children;
	function WizSection($title,$children) {
		$this->title = $title;
		$this->children = $children;
	}
}

abstract class WizFinish {
	var $title;
	
	function WizFinish ($title) {
		$this->title = $title;
	}
	
	//called once
	abstract function finish ($postdata);
	
	//may be called more than once
	abstract function getFinishPage ($postdata);
}

?>
