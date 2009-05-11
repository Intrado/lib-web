<?
class Wizard {
	var $wizdata;
	var $filteredwizdata;
	var $steplist;
	var $curstep;
	
	function Wizard ($wizdata, $curstep = false) {
		$this->wizdata = $wizdata;
		$this->filteredwizdata = $this->filter();
		$this->steplist = $this->getStepList();
		if (!$curstep)
			$curstep = $this->steplist[0];
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
				if ($wizstepdata->isEnabled($_SESSION['wizard']['data'],$curstep . "/" . $wizstep))
					$newwizdata[$wizstep] = $wizstepdata;
			}
		}
		return $newwizdata;
	}
	
	function getForm() {
		$stepdata = $this->getStepData();		
		$form = $stepdata->getForm($_SESSION['wizard']['data'], $this->curstep);
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
		if (isset($_SESSION['wizard']['data'][$this->curstep])) {
			foreach ($_SESSION['wizard']['data'][$this->curstep] as $name => $value) {
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
		$stepdata = $this->getStepData();
		$form = $stepdata->getForm($_SESSION['wizard']['data'], $this->curstep);
		
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