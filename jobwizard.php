<?
if (isset($_GET['source'])) {
	highlight_file(__FILE__);
	exit();
}

session_start();

require_once("inc/utils.inc.php");
require_once("inc/html.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");


//################ PAGE CODE ################

//includes




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


//======================================================================


include("jobwizard_steps.php");


$wizdata = array(
	"basic" => new JobWiz_basic("New Job Wizard"),
	"list" => new JobWiz_list("Fancy List Buider"),
	"complex" => array(
		"name" => new JobWiz_list("Name"),
		"stuff" => new JobWiz_list("Stuff"),
		"things" => new JobWiz_list("Things"),
	)
);

//======================================================================

if (isset($_GET['cancel']) || !isset($_SESSION['wizard']['step'])) {
	unset($_SESSION['wizard']);
	$_SESSION['wizard']['data'] = array();
	$wizard = new Wizard($wizdata,false);
	$_SESSION['wizard']['step'] = $step = $wizard->getCurrentStep();	
	redirect("jobwizard.php?step=$step");
}

if (isset($_GET['step'])) {
	$_SESSION['wizard']['step'] = $_GET['step'];
}

if (isset($_GET['done'])) {
	exit("Awesome");
}


$wizard = new Wizard($wizdata,$_SESSION['wizard']['step']);
$step = $wizard->getCurrentStep();
$stepdata = $wizard->getStepData();
$form = $wizard->getForm();
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		
		//save data here
		
		$_SESSION['wizard']['data'][$step] = $postdata;
		
		if ($ajax) {			
			if ($button == "next") {
				if ($next = $wizard->getNextStep())
					$form->sendTo("jobwizard.php?step=$next");
				else
					$form->sendTo("jobwizard.php?alldone");
			} else if ($button == "prev") {
				if ($next = $wizard->getPrevStep())
					$form->sendTo("jobwizard.php?step=$next");
				
			} else if ($button == "done") {
				$form->sendTo("jobwizard.php?done");
			}
		}
	}
}


//======================================================================


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?= $stepdata->title ?></title>
	<script>
	var _brandtheme = "3dblue";
	</script>
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>

	<script src="script/form.js.php" type="text/javascript"></script>
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	
	<script src="script/utils.js" type="text/javascript"></script>
	<style>
	<? include_once("css/css.inc.php"); ?>
	</style>
	
</head>
<body>

	<h1><?= $stepdata->title ?></h1>
	


	<div style="border: 1px solid black; padding: 0.5%; margin: 0.5%; width: 750px; float: left;">
		<?= $wizard->render() ?>
	</div>
	<div style="border: 1px solid black; padding: 0.5%; margin: 0.5%; width: 300px; float: left; overflow: auto;">
		<pre style="font-size: 7pt; font-family: monospace;"><? var_dump($_SESSION['wizard']['data']) ?></pre>
	</div>
</html>
<?




/*
//iterate over the entire wizard, showing steps along the way
$step = $wizard->getCurrentStep();
$count = 1;
do {
	$wizard->setCurrentStep($step);
	$f = $wizard->getStepData();
	
	
	echo "step $count\t\t$step";
	$t = 5 - ceil((strlen($step)+1) / 8);
	while ($t-- > 0)
		echo "\t";
	echo "\t$f->title\n";
	$count++;
} while ($step = $wizard->getNextStep());
*/



echo "\n";