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
require_once("obj/JobType.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/WeekRepeat.fi.php");
require_once("obj/WeekRepeat.val.php");
require_once("obj/Job.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/JobList.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging'))
	redirect("unauthorized.php");


class TemplateEdit extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		$emailheader = "<th>Email</th>";
		$emailcomponent = "<td>";
		if ($this->args["hasEmail"]) {
			$emailcomponent .= icon_button(_L("Edit Email"), "pencil","return form_submit(event,'editemail');");
			$emailcomponent .= icon_button(_L("Remove Email"), "cross","return confirmDelete()?form_submit(event,'removeemail'):false;");
		} else {
			$emailcomponent .= icon_button(_L("Add Email"), "add","return form_submit(event,'editemail');");
		}
		$emailcomponent .= "</td>";
		
		$phoneheader = "";
		$phonecomponent = "";
		if (getSystemSetting('_hasphonetargetedmessage', false)) {
			$phoneheader = "<th>Phone</th>";
			$phonecomponent .= "<td>";
			if ($this->args["hasPhone"]) {
				$phonecomponent .= icon_button(_L("Edit Phone"), "pencil","return form_submit(event,'editphone');");
				$phonecomponent .= icon_button(_L("Remove Phone"), "cross","return confirmDelete()?form_submit(event,'removephone'):false;");
			} else {
				$phonecomponent .= icon_button(_L("Add Phone"), "add","return form_submit(event,'editphone');");
			}
			$phonecomponent .= "</td>";
		}
		
		$str = 
		"<table class='list' style='width:auto;'>
			<tr>
			$emailheader
			$phoneheader
			</tr>
			<tr>
			$emailcomponent 
			$phonecomponent
			</tr>
		</table>";
		return $str;
	}
}


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// always try to find the alert job
$job = DBFind("Job", "from job where type = 'alert' and status = 'repeating'", false, array());

// if there is one, look up it's schedule
if ($job)
	$schedule = DBFind("Schedule", "from schedule where id = ?", false, array($job->scheduleid));
else
	$schedule = new Schedule();

// get scheduled days of week into a format useable by the form item
$dowvalues = array();
$data = explode(",", $schedule->daysofweek);
for ($x = 1; $x < 8; $x++)
	$dowvalues[$x-1] = in_array($x,$data);

$defaulttime = date("g:i a", strtotime("5:00 pm"));
if($USER->getCallLate() < date("g:i a", strtotime($defaulttime . " + 1 hour")))
	$defaulttime = $USER->getCallEarly();
$dowvalues[7] = ($schedule->time?date("g:i a", strtotime($schedule->time)):$defaulttime);

// Prepare Job JobType data
$userjobtypes = JobType::getUserJobTypes();
$jobtypes = array();
$jobtips = array();
foreach ($userjobtypes as $id => $jobtype) {
	// don't show systempriority 1 job types
	if ($jobtype->systempriority > 1) {
		$jobtypes[$id] = $jobtype->name;
		$jobtips[$id] = escapehtml($jobtype->info);
	}
}




// get the customer default language data
$defaultcode = Language::getDefaultLanguageCode();
$defaultlanguage = Language::getName(Language::getDefaultLanguageCode());
$languagemap = Language::getLanguageMap();

// get all active user logins
$activeusers = QuickQueryList("select id, login from user where not deleted and enabled and login != 'schoolmessenger'", true, false, array());

// Do job template form stuff
$formdata = array(
	_L("%s Template",getJobTitle()),
	"name" => array(
		"label" => _L('Template Name'),
		"fieldhelp" => _L("Enter a name for Classroom Messaging %s.",getJobsTitle()),
		"value" => ($job)?$job->name:"",
		"validators" => array(
			array("ValRequired"),
			array("ValDuplicateNameCheck","type" => "job"),
			array("ValLength","max" => 30)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 30),
		"helpstep" => 1
	),
	"jobtype" => array(
		"label" => _L("Type/Category"),
		"fieldhelp" => _L("Select the option that best describes the type of %s you are sending.",getJobTitle()),
		"value" => ($job)?$job->jobtypeid:"",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($jobtypes))
		),
		"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
		"helpstep" => 2
	),
	"schedule" => array(
		"label" => _L("Days to run"),
		"fieldhelp" => _L("Select which days Classroom Messages should be sent."),
		"value" => $dowvalues,
		"validators" => array(
			array("ValRequired"),
			array("ValWeekRepeatItem")
		),
		"control" => array("WeekRepeatItem","timevalues" => newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate())),
		"helpstep" => 3
	),
	"owner" => array(
		"label" => _L("Owner"),
		"fieldhelp" => _L("Select the user account Classroom Message jobs should run under."),
		"value" => ($job)?$job->userid:$USER->id,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($activeusers))
		),
		"control" => array("SelectMenu", "values" => ($activeusers?array("-- Select One --") + $activeusers:array("-- Select One --"))),
		"helpstep" => 4
	)
);


// get this jobs messagegroup and it's messages
if ($job) {
	$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ? and type = 'classroomtemplate'", false, array($job->messagegroupid));
}

// Do message template form stuff
$formdata[] = _L("Message Template");


$emailheader = "<th>Email</th>";
$emailcomponent = "<td>";
if ($messagegroup && $messagegroup->hasMessage("email")) {
	$emailcomponent .= icon_button(_L("Edit Email"), "pencil","return form_submit(event,'editemail');");
	$emailcomponent .= icon_button(_L("Remove Email"), "cross","return confirmDelete()?form_submit(event,'removeemail'):false;");
} else {
	$emailcomponent .= icon_button(_L("Add Email"), "add","return form_submit(event,'editemail');");
}
$emailcomponent .= "</td>";

$phoneheader = "";
$phonecomponent = "";
if (getSystemSetting('_hasphonetargetedmessage', false)) {
	$phoneheader = "<th>Phone</th>";
	$phonecomponent .= "<td>";
	if ($messagegroup && $messagegroup->hasMessage("phone")) {
		$phonecomponent .= icon_button(_L("Edit Phone"), "pencil","return form_submit(event,'editphone');");
		$phonecomponent .= icon_button(_L("Remove Phone"), "cross","return confirmDelete()?form_submit(event,'removephone'):false;");
	} else {
		$phonecomponent .= icon_button(_L("Add Phone"), "add","return form_submit(event,'editphone');");
	}
	$phonecomponent .= "</td>";
}

$formdata["template"] = array(
		"label" => _L("Template"),
		"control" => array("FormHtml", "html" => 
				"<table class='list' style='width:auto;'>
					<tr>
					$emailheader
					$phoneheader
					</tr>
					<tr>
					$emailcomponent
					$phonecomponent
					</tr>
				</table>"),
		"helpstep" => 5
);


$helpsteps = array (
	_L('The Template Name will be displayed in reports'),
	_L('The %s Type determines where the system sends the message.',getJobTitle()),
	_L('Select which days Classroom Messages should be sent.'),
	_L('Select the user account that Classroom Messaging %s should be sent from.',getJobsTitle()),
	_L('Edit Template')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"settings.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons);

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
		
		// get the owner specified by postdata
		$owner = $postdata['owner'];
		
		if (!$messagegroup) {
			$messagegroup = new MessageGroup();
			$messagegroup->userid = $owner;
			$messagegroup->type = 'classroomtemplate';
			$messagegroup->defaultlanguagecode = Language::getDefaultLanguageCode();
			$messagegroup->name = $postdata['name'];
			$messagegroup->description = "Classroom Messageing Template";
			$messagegroup->modified = date("Y-m-d H:i:s");
			$messagegroup->permanent = 1;
			$messagegroup->create();
		}
		
		// get existing job if it exists
		$job = DBFind("Job", "from job where type = 'alert' and status = 'repeating'", false, array());
		
		// get the job's schedule or create a new one if there isn't any
		if ($job)
			$schedule = DBFind("Schedule", "from schedule where id = ?", false, array($job->scheduleid));
		if (!isset($schedule) || !$schedule)
			$schedule = new Schedule();
		
		// update the schedule
		$scheduledata = json_decode($postdata['schedule'],true);
		$days = array();
		$time = $USER->getCallEarly();
		foreach ($scheduledata as $index => $data) {
			if ($index == 7) {
				$time = date("H:i", strtotime($data));
			} else {
				if ($data)
					$days[] = $index + 1;
			}
		}
		$schedule->userid = $owner;
		$schedule->daysofweek = implode(",", $days);
		$schedule->time = $time;
		$schedule->nextrun = $schedule->calcNextRun();
		
		if ($schedule->id)
			$schedule->update();
		else
			$schedule->create();

		// update or create the job
		if (!$job)
			$job = new Job();
		$job->messagegroupid = $messagegroup->id;
		$job->userid = $owner;
		$job->scheduleid = $schedule->id;
		$job->jobtypeid = $postdata['jobtype'];
		$job->name = $postdata['name'];
		$job->description = "Classroom Messaging Template";
		$job->type = 'alert';
		if (!$job->id)
			$job->createdate = date("Y-m-d H:i:s");
		$job->modifydate = date("Y-m-d H:i:s");
		$job->startdate = date("Y-m-d", 86400);
		$job->enddate = $job->startdate;
		$job->starttime = "00:00";
		$job->endtime = "23:59";
		$job->status = 'repeating';
		$job->setOption("skipemailduplicates",0);
		if ($job->id)
			$job->update();
		else
			$job->create();
		
		// update or create the joblist
		$joblist = DBFind("JobList", "from joblist where jobid = ?", false, array($job->id));
		if (!$joblist)
			$joblist = new JobList();
		
		// get the peoplelist or create a new one
		if ($joblist)
			$peoplelist = DBFind("PeopleList", "from list where id = ?", false, array($joblist->listid));
		if (!isset($peoplelist) || !$peoplelist)
			$peoplelist = new PeopleList();
		
		// get the list entry or create a new one
		if ($peoplelist)
			$listentry = DBFind("ListEntry", "from listentry where listid = ?", false, array($peoplelist->id));
		if (!isset($listentry) || !$listentry)
			$listentry = new ListEntry();
		
		// get the rule
		if ($listentry)
			$rule = DBFind("Rule", "from rule where id = ?", false, array($listentry->ruleid));
		if (!isset($rule) || !$rule)
			$rule = new Rule();
		
		// set all the rule, listentry, peoplelist, joblist values
		$rule->logical = 'and';
		$rule->fieldnum = 'alrt';
		$rule->val = "";
		
		if ($rule->id)
			$rule->update();
		else
			$rule->create();
		
		$peoplelist->userid = $owner;
		$peoplelist->type = 'alert';
		$peoplelist->name = $postdata['name'];
		$peoplelist->description = "Classroom Messaging Template";
		$peoplelist->modifydate = date("Y-m-d H:i:s");
		$peoplelist->deleted = 0;
		
		if ($peoplelist->id)
			$peoplelist->update();
		else
			$peoplelist->create();
		
		$listentry->listid = $peoplelist->id;
		$listentry->type = 'rule';
		$listentry->ruleid = $rule->id;
		
		if ($listentry->id)
			$listentry->update();
		else
			$listentry->create();
		
		$joblist->jobid = $job->id;
		$joblist->listid = $peoplelist->id;
		
		if ($joblist->id)
			$joblist->update();
		else
			$joblist->create();
		
		
		if (strstr($button, "remove")) {
			QuickUpdate("delete m.* ,mp.*
					from messagegroup mg
					inner join message m on (mg.id = m.messagegroupid)
					inner join messagepart mp on (m.id = mp.messageid)
					where
					mg.type='classroomtemplate' and
					m.type=?",
				false, array(substr($button, 6)));
		}
		
		Query("COMMIT");
		$redirect = "settings.php";
		switch ($button) {
			case "removephone":
			case "removeemail":
				$redirect = "classroommessagetemplate.php";
				break;
			case "editemail":
				$redirect = "classroommessageemailtemplate.php";
				break;
			case "editphone":
				if (getSystemSetting('_hasphonetargetedmessage', false))
					$redirect = "classroommessagephonetemplate.php";
				break;
		}
		
		if ($ajax)
			$form->sendTo($redirect);
		else
			redirect($redirect);
		

	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript" src="script/listform.js.php"></script>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck","ValWeekRepeatItem")); ?>
</script>
<?
$TITLE = _L('Classroom Messaging Template');
startWindow($TITLE);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>