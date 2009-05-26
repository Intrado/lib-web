<?
class Wizard {
	var $name;
	var $wizdata;
	var $filteredwizdata;
	var $steplist;
	var $curstep;
	var $datachange;
	var $done;
	
	function Wizard ($name, $wizdata, $curstep = false) {
		$this->name = $name;
		$this->wizdata = $wizdata;
		$this->filteredwizdata = $this->filter();
		$this->steplist = $this->getStepList();
		if (!$curstep) {
			if (isset($_SESSION[$name]['step'])) {
				$curstep = $_SESSION[$name]['step'];
			} else {
				$curstep = $this->steplist[0];
			}
		}
		$this->curstep = $curstep;
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
			return $this->_getStepData($wizdata[$first],$tail);
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
			if (is_array($wizstepdata)) {
				$this->_getStepList($wizstepdata,$curstep . "/" . $wizstep,false);
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
			if (is_array($wizstepdata)) {
				$substeps = $this->_filter($wizstepdata,$curstep . "/" . $wizstep);
				if (count($substeps) > 0)
					$newwizdata[$wizstep] = $substeps;
			} else {
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
		$form->buttons[] = icon_button("Cancel","cross","if(confirm('Are you sure you want to cancel?')) window.location='?cancel';");
		if ($this->getNextStep())
			$form->buttons[] = submit_button("Next","next","arrow_right");
		else
			$form->buttons[] = submit_button("Done","done","accept");
		
		//merge in any existing wizard post data
		if (isset($_SESSION[$this->name]['data'][$this->curstep])) {
			foreach ($_SESSION[$this->name]['data'][$this->curstep] as $name => $value) {
				if (isset($form->formdata[$name]))
					$form->formdata[$name]['value'] = $value;
			}
		}
		
		return $form;
	}
	
	function render() {
		return $this->getForm()->render();
	}
	
	function handleRequest () {
		if (isset($_GET['cancel']) || !isset($_SESSION[$this->name]['step'])) {
			unset($_SESSION[$this->name]);
			$_SESSION[$this->name]['data'] = array();
			$_SESSION[$this->name]['step'] = $step = $this->steplist[0];
			redirect($_SERVER['SCRIPT_NAME']."?step=$step");
		}
		
		if (isset($_GET['step'])) {
			$_SESSION[$this->name]['step'] = $_GET['step'];
			$this->setCurrentStep($_GET['step']);
		}
		
		if (isset($_GET['done'])) {
			$this->done = true;
		}
		
		$stepdata = $this->getStepData();
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
						if ($next = $this->getNextStep())
							$form->sendTo($_SERVER['SCRIPT_NAME']."?step=$next");
						else
							$form->sendTo($_SERVER['SCRIPT_NAME']."?done");
					} else if ($button == "prev") {
						if ($next = $this->getPrevStep())
							$form->sendTo($_SERVER['SCRIPT_NAME']."?step=$next");
						
					} else if ($button == "done") {
						$form->sendTo($_SERVER['SCRIPT_NAME']."?done");
					}
				}
			}
		}
	}
	
	function isDone() {
		return $this->done;
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
?>