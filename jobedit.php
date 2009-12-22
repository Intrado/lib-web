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
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Phone.obj.php"); // Required by job
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/FieldMap.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
$job = null;
if (isset($_GET['id'])) {
	$job = new Job($_GET['id'] + 0);
	//if ($job->type == "survey")
	//	redirect("survey.php?id=" . ($_GET['id'] + 0));
	setCurrentJob($_GET['id']);
	redirect();
}

if (isset($_GET['origin'])) {
	$_SESSION['origin'] = trim($_GET['origin']);
}

$jobid = $_SESSION['jobid'];
if ($_SESSION['jobid'] == NULL) {
	$job = Job::jobWithDefaults();
} else {
	$job = new Job($_SESSION['jobid']);
}

$JOBTYPE = "normal";

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class JobListItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		return "<input id='" .$n."' name='".$n."' type='hidden' value='". json_encode($value) ."'/>
				<div>
				<div id='listSelectboxContainer' style='float:left;width:40%;'></div>
				<div id='listchooseTotalsContainer' style='display:none'>
					<table>
						<tr><th valign=top style='text-align:left'>"._L('List Total')."</th><td valign=top id='listchooseTotal'>0</td></tr>
						<tr><td valign=top style='text-align:left'>"._L('Matched by Rules')."</td><td valign=top id='listchooseTotalRule'>0</td></tr>
						<tr><td valign=top style='text-align:left'>"._L('Additions')."</td><td valign=top id='listchooseTotalAdded'>0</td></tr>
						<tr><td valign=top style='text-align:left'>"._L('Skips')."</td><td valign=top id='listchooseTotalRemoved'>0</td></tr>
					</table>
				</div>
				<div id='allListsWindow' style='float:right;width:50%;overflow:hidden;' >
					<table width='100%' cellspacing='1' cellpadding='3' class='list' style='table-layout:fixed; font-size:90%;'>
						<thead>
							<tr class='listHeader'>
								<th width='70%' style='overflow: hidden; overflow: hidden; white-space: nowrap; text-align:left'>"._L('List Name')."</th>
								<th width='20%' style='overflow: hidden; text-align:left'>Count</th>
								<th width='16'></th>
							</tr>
						</thead>
						<tbody id='listsTableBody'>
						</tbody>
						<tfoot>
							<tr id='listsTableMyself' style='display:none'>
								<td>"._L('Myself')."</td>
								<td>1</td>
								<td></td>
							</tr>
							<tr>
								<td class='border'>
									<b>"._L('Total')."</b>
								</td>
								<td class='border' colspan=2>
									<b><span id='listGrandTotal'>0</span></b><span style='vertical-align:middle' id='listsTableStatus'></span>
								</td>
							</tr>
						</tfoot>
					</table>
				 </div>
				 </div>
				<script type=\"text/javascript\" language=\"javascript\">
					document.observe('dom:loaded', function() {
						listformVars = {listidsElement: $('" .$n."')};
						listform_load_cached_list();
						forcevalidator = function() {
							form_do_validation($('" . $this->form->name . "'), $('" . $n . "'));
						};
					});
				</script>

				";
	}
}


/* TODO these validators are copied from the wizard
	Make sure they are moved to avoid duplicate implementation
*/

class ValJobName extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		$jobcount = QuickQuery("select count(id) from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')", false, array($USER->id, $value));
		if ($jobcount)
			return "$this->label: ". _L('There is already an active notification with this name. Please choose another.');
		return true;
	}
}

class ValTimeWindowCallEarly extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if ((strtotime($value) + 3600) > strtotime($requiredvalues['calllate']))
			return $this->label. " ". _L('There must be a minimum of one hour between start and end time');
		return true;
	}
}

class ValTimeWindowCallLate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if ((strtotime($value) - 3600) < strtotime($requiredvalues['callearly']))
			return $this->label. " ". _L('There must be a minimum of one hour between start and end time');
		$now = strtotime("now");
		if ((date('m/d/Y', $now) == $requiredvalues['date']) && (strtotime($value) -1800 < $now))
			return $this->label. " ". _L("There must be a minimum of one-half hour between now and end time to submit with today's date");
		return true;
	}
}

class ValDate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if (strtotime($value) < strtotime($args['min']))
			return $this->label. " ". _L('cannot be a date earlier than %s', $args['min']);
		if (isset($args['max']))
			if (strtotime($value) > strtotime($args['max']))
				return $this->label. " ". _L('cannot be a date later than %s', $args['max']);
		return true;
	}
}

class ValLists extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;


		if (strpos($value, 'pending') !== false)
			return _L('Please finish adding this rule, or unselect the field');

		$listids = json_decode($value);
		if (empty($listids))
			return _L("Please add a list");

		$allempty = true;
		foreach ($listids as $listid) {
			if ($listid === 'addme') {
				$allempty = false;
				continue;
			}
			if (!userOwns('list', $listid))
				return _L('You have specified an invalid list');
			$list = new PeopleList($listid + 0);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			if ($renderedlist->total >= 1)
				$allempty = false;
		}
		if ($allempty)
			return _L('All of these lists are empty');
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$userjobtypes = JobType::getUserJobTypes();

// Prepare Job Type data
$jobtypes = array();
$jobtips = array();
foreach ($userjobtypes as $id => $jobtype) {
	$jobtypes[$id] = $jobtype->name;
	$jobtips[$id] = escapehtml($jobtype->info);
}

// Prepare List data

$selectedlists = array();
if (isset($job->id)) {
	$selectedlists = QuickQueryList("select listid from joblist where jobid=?", false,false,array($job->id));
}

// Prepare Scheduling data
$dayoffset = (strtotime("now") > (strtotime(($ACCESS->getValue("calllate")?$ACCESS->getValue("calllate"):"11:59 pm")) - 1800))?1:0;
$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());

// Prepare the the "Number of Days to run" data
$numdays = array();
$maxdays = $ACCESS->getValue('maxjobdays');
if ($maxdays == null) {$maxdays = 7;}
for ($i = 1; $i <= 7; $i++) {$numdays[$i] = $i;}

$messages = array_merge(array("" => "-- Select a Message --"),QuickQueryList("select id, name, (name +0) as digitsfirst from messagegroup where userid=? and deleted=0 order by digitsfirst,name", true,false,array($USER->id)));

$formdata = array(
	_L('Job Settings'),
	"name" => array(
		"label" => _L('Job Name'),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValJobName"),
			array("ValLength","max" => 30)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	/*"description" => array(
		"label" => _L('Description'),
		"value" => "",
		"validators" => array(
			array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),*/
	"jobtype" => array(
		"label" => _L("Type/Category"),
		"fieldhelp" => _L("Select the option that best describes the type of notification you are sending."),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($jobtypes))
		),
		"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
		"helpstep" => 2
	),
	"date" => array(
		"label" => _L("Start Date"),
		"fieldhelp" => _L("Notification will begin on the selected date."),
		"value" => "now + $dayoffset days",
		"validators" => array(
			array("ValRequired"),
			array("ValDate", "min" => date("m/d/Y", strtotime("now + $dayoffset days")))
		),
		"control" => array("TextDate", "size"=>12, "nodatesbefore" => $dayoffset),
		"helpstep" => 2
	),
	"days" => array(
		"label" => _L("Days to Run"),
		"fieldhelp" => _L(""),
		"value" => (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400,
		"validators" => array(
			array("ValRequired"),
			array("ValDate", "min" => 1, "max" => ($ACCESS->getValue('maxjobdays') != null ? $ACCESS->getValue('maxjobdays') : "7"))
		),
		"control" => array("SelectMenu", "values" => $numdays),
		"helpstep" => 2
	),

	"callearly" => array(
		"label" => _L("Start Time"),
		"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
		"value" => $USER->getCallEarly(),
		"validators" => array(
			array("ValRequired"),
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
			array("ValTimeWindowCallEarly")
		),
		"requires" => array("calllate"),
		"control" => array("SelectMenu", "values"=>$startvalues),
		"helpstep" => 2
	),
	"calllate" => array(
		"label" => _L("End Time"),
		"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
		"value" => $USER->getCallLate(),
		"validators" => array(
			array("ValRequired"),
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
			array("ValTimeWindowCallLate")
		),
		"requires" => array("callearly", "date"),
		"control" => array("SelectMenu", "values"=>$endvalues),
		"helpstep" => 2
	),
	_L('Job Lists'),
	"lists" => array(
		"label" => _L('Lists'),
		"value" => $selectedlists,
		"validators" => array(
			array("ValRequired"),
			array("ValLists")
		),
		"control" => array("JobListItem"),
		"helpstep" => 3
	),
	"skipduplicats" => array(
		"label" => _L('Skip Duplicates'),
		"value" => false,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 3
	),
	_L('Job Message'),
	"message" => array(
		"label" => _L('Message'),
		"value" => isset($job->id)?$job->messagegroupid:"",
		"validators" => array(array("ValRequired")),
		"control" => array("SelectMenu", "values" => $messages),
		"helpstep" => 4
	),
	_L('Advanced Options '),
	"report" => array(
		"label" => _L('Completion Report'),
		"value" => false,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"replyoption" => array(
		"label" => _L('Allow Reply'),
		"value" => false,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"confirmoption" => array(
		"label" => _L('Allow Confirmation'),
		"value" => false,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
);
//				$helpsteps[] = _L("The Delivery Window designates the earliest call time and the latest call time allowed for notification delivery.");


$helpsteps = array (
	_L("The name of your job. The best names are brief and discriptive of the message content."),
	_L("Select the option that best describes the type of notification you are sending."),
	_L("Select a list"),
	_L("Select a message"),
	_L("Optionally choose advanced options")
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("jobedit",$formdata,$helpsteps,$buttons);

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

		//save data here


		Query("COMMIT");
		if ($ajax)
			$form->sendTo("start.php");
		else
			redirect("start.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_template ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:jobs";
$TITLE = ($JOBTYPE == 'repeating' ? _L('Repeating Job Editor: ') : _L('Job Editor: ')) . ($jobid == NULL ? _L("New Job") : escapehtml($job->name));

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript" src="script/listform.js.php"></script>
<script type="text/javascript">
<? Validator::load_validators(array("ValJobName","ValTimeWindowCallEarly","ValTimeWindowCallLate","ValDate","ValLists")); ?>
</script>
<?

startWindow(_L('Job Information'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
