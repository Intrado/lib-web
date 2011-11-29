<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Monitor.obj.php");
require_once("obj/MonitorFilter.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemactive')) {
	redirect('unauthorized.php');
}

$monitor = null;
if (isset($_GET['id'])) {
	if ($_GET['id'] !== "new" && !userOwns("monitor",$_GET['id']))
		redirect('unauthorized.php');
	
	setIfOwnsOrNew($_GET['id'],"monitorid","monitor");
	redirect();
}

if (isset($_SESSION["monitorid"])) {
	$monitor = new Monitor($_SESSION["monitorid"]);
} else {	
	$monitor = new Monitor();
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


class RestrictedValues extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (count($this->args['values']) == 0) {
			return '<img src="img/icons/information.png" alt="Information" /> ' . _L("No Restrictable Fields");
		}

		$label = (isset($this->args['label']) && $this->args['label'])? $this->args['label']: _L('Restrict to these fields:');
		$restrictchecked = count($value) > 0 ? "checked" : "";
		$str = '<input type="checkbox" id="'.$n.'-restrict" '.$restrictchecked .' onclick="restrictcheck(\''.$n.'-restrict\', \''.$n.'\')"><label for="'.$n.'-restrict">'.$label.'</label>';

		
		$style = count($this->args['values'])>10 ? ('height: 100px; overflow: auto;') : '';
		$str .= '<div id='.$n.' class="radiobox" style="' . $style . 'margin-left: 1em;">';

		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').'  onclick="datafieldcheck(\''.$id.'\', \''.$n.'-restrict\')"/><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>
		';
		return $str;
	}

	function renderJavascript($value) {
		return '
		//if we uncheck the restrict box, uncheck each field
		function restrictcheck(restrictcheckbox, checkboxdiv) {
			restrictcheckbox = $(restrictcheckbox);
			checkboxdiv = $(checkboxdiv);
			if (!restrictcheckbox.checked) {
				checkboxdiv.descendants().each(function(e) {
					e.checked = false;
				});
			}
		}

		// if a data field is checked. Check the restrict box
		function datafieldcheck(checkbox, restrictcheckbox) {
			checkbox = $(checkbox);
			restrictcheckbox = $(restrictcheckbox);
			if (checkbox.checked)
					restrictcheckbox.checked = true;
		}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array();

$helpsteps = array ();
$helpstepnum = 1;
$jobevents = array("job-active" => "Submitted","job-firstpass" => "First Attempt Completed", "job-complete" => "Completed");
$formdata["jobevent"] = array(
		"label" => _L('Job Event'),
		"value" => $monitor->id?$monitor->type:'',
		"validators" => array(
			array("ValInArray", "values" => array_keys($jobevents))
		),
		"control" => array("SelectMenu", "values"=>$jobevents),
		"helpstep" => $helpstepnum
);

// TODO add validators
$jobtypes = QuickQueryList("select id,name from jobtype where deleted=0 and not issurvey order by name",true);
$selectedjobtypes = $monitor->id?QuickQuery("select val from monitorfilter where type='jobtypeid' and monitorid=?",false,array($monitor->id)):'';
$formdata["jobtypes"] = array(
		"label" => _L('Job Types'),
		"value" => $selectedjobtypes != ""?explode(",",$selectedjobtypes):array(),
		"validators" => array(),
		"control" => array("RestrictedValues", "values" => $jobtypes, "label" => _L("Limit to these job types:")),
		"helpstep" => $helpstepnum
);

// TODO add validators
$users = QuickQueryList("select id, concat(firstname,' ', lastname,' (',login,')') as name from user where enabled = 1 and login != 'schoolmessenger'",true);
$selectedusers = $monitor->id?QuickQuery("select val from monitorfilter where type='userid' and monitorid=?",false,array($monitor->id)):'';
$formdata["users"] = array(
		"label" => _L('Users'),
		"value" => $selectedusers != ""?explode(",",$selectedusers):array(),
		"validators" => array(),
		"control" => array("RestrictedValues", "values" => $users, "label" => _L("Limit to these users:")),
		"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"monitors.php"));
$form = new Form("monitor",$formdata,null,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
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
		Query("BEGIN");
		
		$jobtypefilter = false;
		$userfilter = false;
		if ($monitor->id) {
			$jobtypefilter = DBFind("MonitorFilter", "from monitorfilter where type='jobtypeid' and monitorid=?",false,array($monitor->id));
			$userfilter = DBFind("MonitorFilter", "from monitorfilter where type='userid' and monitorid=?",false,array($monitor->id));
		}
		
		$monitor->type = $postdata['jobevent'];
		if (!$monitor->id) {
			$monitor->userid = $USER->id;
			$monitor->action =  'email';
			$monitor->create();
		} else {
			$monitor->update();
		}
		
		if (count($postdata['jobtypes']) == 0 && $jobtypefilter != false) {
			QuickUpdate("delete from monitorfilter where type='jobtypeid' and monitorid=?",false,array($monitor->id));
		} else if (count($postdata['jobtypes'])){
			if (!$jobtypefilter) {
				$jobtypefilter = new MonitorFilter();
				$jobtypefilter->type = 'jobtypeid';
				$jobtypefilter->monitorid = $monitor->id;
			}
			$jobtypefilter->val = implode(',',$postdata['jobtypes']);
			$jobtypefilter->update();
		}
		
		if (count($postdata['users']) == 0 && $userfilter != false) {
			QuickUpdate("delete from monitorfilter where type='userid' and monitorid=?",false,array($monitor->id));
		} else if (count($postdata['users'])){
			if (!$userfilter) {
				$userfilter = new MonitorFilter();
				$userfilter->type = 'userid';
				$userfilter->monitorid = $monitor->id;
			}
			$userfilter->val = implode(',',$postdata['users']);
			$userfilter->update();
		}
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("monitors.php");
		else
			redirect("monitors.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "system:monitors";
$TITLE = _L('Edit Monitor');

include_once("nav.inc.php");
startWindow(_L('Job Monitor Settings'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>