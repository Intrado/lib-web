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
$dowvalues[7] = ($schedule->time?date("g:i a", strtotime($schedule->time)):$USER->getCallEarly());

// Prepare Job JobType data
$userjobtypes = JobType::getUserJobTypes();
$jobtypes = array();
$jobtips = array();
foreach ($userjobtypes as $id => $jobtype) {
	$jobtypes[$id] = $jobtype->name;
	$jobtips[$id] = escapehtml($jobtype->info);
}

// get this jobs messagegroup and it's messages
$messagesbylangcode = array();
if ($job) {
	$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ? and type = 'classroomtemplate'", false, array($job->messagegroupid));
	if ($messagegroup) {
		$messages = DBFindMany("Message", "from message where messagegroupid = ?", false, array($messagegroup->id));
		if ($messages) {
			foreach ($messages as $id => $message) {
				$messagesbylangcode[$message->languagecode] = $message;
				$messagesbylangcode[$message->languagecode]->readHeaders();
			}
		}
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
	_L("Job Template"),
	"name" => array(
		"label" => _L('Template Name'),
		"fieldhelp" => _L("TODO: Help"),
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
		"fieldhelp" => _L("Select the option that best describes the type of notification you are sending."),
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
		"fieldhelp" => _L("TODO: Help"),
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
		"fieldhelp" => _L("TODO: Help"),
		"value" => ($job)?$job->userid:$USER->id,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($activeusers))
		),
		"control" => array("SelectMenu", "values" => ($activeusers?array("-- Select One --") + $activeusers:array("-- Select One --"))),
		"helpstep" => 4
	)
);

// Do message template form stuff
$formdata[] = _L("Message Template");

// set the subject, from email, from name
$formdata["fromname"] = array(
	"label" => _L('From Name'),
	"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
	"value" => $USER->firstname . " " . $USER->lastname,
	"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 50)
			),
	"control" => array("TextField","size" => 25, "maxlength" => 50),
	"helpstep" => 4
);

$formdata["fromemail"] = array(
	"label" => _L("From Email"),
	"fieldhelp" => _L('This is the address the email is coming from. Recipients will also be able to reply to this address.'),
	"value" => (isset($messagesbylangcode[$defaultcode])?$messagesbylangcode[$defaultcode]->fromemail:$USER->email),
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255),
		array("ValEmail")
		),
	"control" => array("TextField","max"=>255,"size"=>35),
	"helpstep" => 5
);


// set the default language first and make it required
// get the message parts for this message if it exists
$message = false;
if (isset($messagesbylangcode[$defaultcode])) {
	$message = $messagesbylangcode[$defaultcode];
	$parts = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($message->id));
}

$formdata[] = $defaultlanguage;
$formdata[$defaultcode . "-subject"] = array(
	"label" => _L("Subject"),
	"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
	"value" => ($message)?$message->subject:"",
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255)
	),
	"control" => array("TextField","max"=>255,"size"=>45),
	"helpstep" => 6
);
$formdata[$defaultcode . "-body"] = array(
	"label" => _L("Message Body"),
	"fieldhelp" => _L("TODO: Help"),
	"value" => ($message)?$message->format($parts):"",
	"validators" => array(
		array("ValRequired")),
	"control" => array("TextArea", "rows" => 10, "cols" => 60),
	"helpstep" => 4
);

// unset the default language so it doesn't get overwritten below
if (isset($languagemap[$defaultcode]))
	unset($languagemap[$defaultcode]);

// create form items for all the customer's languages
foreach ($languagemap as $code => $language) {
	// get the message parts for this message if it exists
	$message = false;
	if (isset($messagesbylangcode[$code])) {
		$message = $messagesbylangcode[$code];
		$parts = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($message->id));
	}
	$formdata[] = $language;
	$formdata[$code . "-subject"] = array(
		"label" => _L("Subject"),
		"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
		"value" => ($message)?$message->subject:"",
		"validators" => array(),
		"control" => array("TextField","max"=>255,"size"=>45),
		"helpstep" => 6
	);
	$formdata[$code . "-body"] = array(
		"label" => _L("Message Body"),
		"fieldhelp" => _L("TODO: Help"),
		"value" => ($message)?$message->format($parts):"",
		"validators" => array(),
		"control" => array("TextArea", "rows" => 10, "cols" => 60),
		"helpstep" => 4
	);
}

$helpsteps = array (
	_L('TODO: Help')
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

		// get the messagegroup or create a new one
		if ($job)
			$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ? and type = 'classroomtemplate' and not deleted", false, array($job->messagegroupid));
		if (!isset($messagegroup) || !$messagegroup)
			$messagegroup = new MessageGroup();
		
		// update the message group
		$messagegroup->userid = $owner;
		$messagegroup->type = 'classroomtemplate';
		$messagegroup->defaultlanguagecode = Language::getDefaultLanguageCode();
		$messagegroup->name = $postdata['name'];
		$messagegroup->description = "Classroom Messageing Template";
		$messagegroup->modified = date("Y-m-d H:i:s");
		$messagegroup->permanent = 1;
		if ($messagegroup->id)
			$messagegroup->update();
		else
			$messagegroup->create();
		
		// attempt to get all the messages for this message group and associate them by langcode in an array
		$messagesbylangcode = array();
		if ($messagegroup) {
			$messages = DBFindMany("Message", "from message where messagegroupid = ?", false, array($messagegroup->id));
			if ($messages) {
				foreach ($messages as $id => $message){
					$messagesbylangcode[$message->languagecode] = $message;
					$messagesbylangcode[$message->languagecode]->readHeaders();
				}
			}
		}

		// get the default language code
		$defaultcode = Language::getDefaultLanguageCode();
		
		// update, create or orphan all the message parts.
		foreach(Language::getLanguageMap() as $code => $language) {
			// if there is a message body specified for this language code, create/update a message
			if ($postdata[$code . "-body"]) {
				// if the message is already associated, reuse it. otherwise create a new one
				if (isset($messagesbylangcode[$code])) {
					$message = $messagesbylangcode[$code];
				} else {
					$message = new Message();
				}
				$message->messagegroupid = $messagegroup->id;
				$message->userid = $owner;
				$message->name = $messagegroup->name;
				$message->description = $messagegroup->description;
				$message->type = 'email';
				$message->subtype = 'html';
				$message->autotranslate = 'none';
				$message->modifydate = date("Y-m-d H:i:s");
				$message->languagecode = $code;
				$message->subject = ($postdata[$code . '-subject'])?$postdata[$code . '-subject']:$postdata[$defaultcode . '-subject'];
				$message->fromname = $postdata['fromname'];
				$message->fromemail = $postdata['fromemail'];
				$message->stuffHeaders();
				$message->recreateParts($postdata[$code . "-body"], null, null);
				if ($message->id)
					$message->update();
				else
					$message->create();
			// if no body, orphan any existing message for this language code
			} else {
				if (isset($messagesbylangcode[$code])) {
					$messagesbylangcode[$code]->deleted = 1;
					$messagesbylangcode[$code]->messagegroup = null;
					$messagesbylangcode[$code]->update();
				}
			}
		}
		
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
		$job->enddate = date("Y-m-d", (2 * 86400));
		$job->starttime = $time;
		$job->endtime = date("H:i", strtotime($time . " + 1 hour"));
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
		$peoplelist->description = "Classroom Messageing Template";
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
		
		Query("COMMIT");
		
		if ($ajax)
			$form->sendTo("settings.php");
		else
			redirect("settings.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Classroom Messageing Template');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript" src="script/listform.js.php"></script>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck","ValWeekRepeatItem")); ?>
</script>
<?

startWindow(_L('Classroom Message delivery settings'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>