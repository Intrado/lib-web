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
require_once("obj/Person.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/MessageGroupSelectMenu.fi.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
require_once("obj/FormListSelect.fi.php");
require_once("inc/date.inc.php");
require_once("obj/ValListSelection.val.php");
require_once("obj/SurveyQuestionnaire.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting('_hassurvey', true) || !$USER->authorize('survey')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id'])) {
	setCurrentSurvey($_GET['id']);
	if ($jobid = getCurrentSurvey()) {
		$job = new Job($jobid);
		$_SESSION['scheduletemplate'] = $job->questionnaireid;
	} else {
		$_SESSION['scheduletemplate'] = null;
	}
	redirect();
}

if (isset($_GET['scheduletemplate'])) {
	$questionnaireid = $_GET['scheduletemplate'] + 0;

	if (userOwns("surveyquestionnaire",$questionnaireid)) {
		$_SESSION['scheduletemplate'] = $questionnaireid;
		setCurrentSurvey("new");
	}
	redirect();
}

$completedmode = false;
$submittedmode = false;

if (getCurrentSurvey() != NULL) {
	$job = new Job(getCurrentSurvey());

	if ('complete' == $job->status || 'cancelled' == $job->status || 'cancelling' == $job->status) {
		$completedmode = true;
	}

	if ($job->status == 'active' || $job->status == 'procactive' || $job->status == 'processing' || $job->status == 'scheduled' || $completedmode) {
		$submittedmode = true;
	}
}

$userjobtypes = JobType::getUserJobTypes(true);
// Prepare Job Type data
$jobtypes = array();
$jobtips = array();
foreach ($userjobtypes as $id => $jobtype) {
	$jobtypes[$id] = $jobtype->name;
	$jobtips[$id] = escapehtml($jobtype->info);
}

$QUESTIONNAIRES = array();
// if submitted or completed, gather only the selected questionnaireid
// because the schedulemanager copies the questionnaire setting deleted=1 when job is due to start
if ($submittedmode || $completedmode) {
	$QUESTIONNAIRES = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where id=$job->questionnaireid");
} else {
	$QUESTIONNAIRES = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where userid=$USER->id and deleted = 0 order by name");
}

$templates = array("" =>_L("-- Select a template --"));
foreach ($QUESTIONNAIRES as $questionnaire) {
	$templates[$questionnaire->id] = $questionnaire->name;
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// Prepare List data
$selectedlists = array();
if (isset($job->id)) {
	$selectedlists = QuickQueryList("select listid from joblist where jobid=?", false,false,array($job->id));
}

// Prepare Scheduling data
$dayoffset = (strtotime("now") > (strtotime(($ACCESS->getValue("calllate")?$ACCESS->getValue("calllate"):"11:59 pm"))))?1:0;

$customstarttime = isset($job->id)? date("g:i a", strtotime($job->starttime)) : $USER->getCallEarly();
$costomendtime = isset($job->id)? date("g:i a", strtotime($job->endtime)) : $USER->getCallLate();
$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $customstarttime);
$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $costomendtime);


$helpsteps = array();
$formdata = array();

$formdata[] = _L('Survey');

$helpsteps[] = _L("Enter a name for your survey. " .
					"Using a descriptive name that indicates the message content will make it easier to find the survey later. " .
					"You may also optionally enter a description of the survey.");
	$formdata["name"] = array(
		"label" => _L('Name'),
		"fieldhelp" => _L('Enter a name for your survey.'),
		"value" => isset($job->name)?$job->name:"",
		"validators" => array(
			array("ValRequired"),
			array("ValDuplicateNameCheck","type" => "survey"),
			array("ValLength","max" => 30)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 50),
		"helpstep" => 1
	);
	$formdata["description"] = array(
		"label" => _L('Description'),
		"fieldhelp" => _L('Enter a description of the survey. This is optional, but can help identify the survey later.'),
		"value" => isset($job->description)?$job->description:"",
		"validators" => array(
			array("ValLength","min" => 0,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 50),
		"helpstep" => 1
	);
	
	if ($submittedmode || $completedmode) {
		$helpsteps[] = _L("Select the option that best describes the type of notification you are sending. 
							The category you select will determine which introduction your recipients will hear.");
		$formdata["jobtype"] = array(
			"label" => _L("Type/Category"),
			"fieldhelp" => _L("The option that best describes the type of notification you are sending."),
			"control" => array("FormHtml","html" => escapehtml($jobtypes[$job->jobtypeid])),
			"helpstep" => 2
		);
	} else {
		$helpsteps[] = _L("Select the option that best describes the type of notification you are sending.
							 The category you select will determine which introduction your recipients will hear.");
		$formdata["jobtype"] = array(
			"label" => _L("Type/Category"),
			"fieldhelp" => _L("Select the option that best describes the type of notification you are sending. 
								The category you select will determine which introduction your recipients will hear."),
			"value" => isset($job->jobtypeid)?$job->jobtypeid:"",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($jobtypes))
			),
			"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
			"helpstep" => 2
		);
	}
	
	$helpsteps[] = _L("The Delivery Window designates the earliest call time and the latest call time allowed for notification delivery.<br><br>
	<b>Note:</b> You may send a survey up until one minute before the cutoff time specified in your Access Profile. You should set the survey to run for two days to ensure all calls are made.");
	if ($completedmode) {
		$formdata["date"] = array(
			"label" => _L("Start Date"),
			"fieldhelp" => _L("Notification will begin on the selected date."),
			"control" => array("FormHtml","html" => date("m/d/Y", strtotime($job->startdate))),
			"helpstep" => 3
		);
	} else {
		$formdata["date"] = array(
			"label" => _L("Start Date"),
			"fieldhelp" => _L("Notification will begin on the selected date."),
			"value" => isset($job->startdate)?$job->startdate:"now + $dayoffset days",
			"validators" => array(
				array("ValRequired"),
				array("ValDate", "min" => date("m/d/Y", strtotime("now + $dayoffset days")))
			),
			"control" => array("TextDate", "size"=>12, "nodatesbefore" => $dayoffset),
			"helpstep" => 3
		);
	}
	if ($completedmode) {
		$formdata["days"] = array(
			"label" => _L("Days to Run"),
			"fieldhelp" => _L("Select the number of days this survey should run."),
			"control" => array("FormHtml","html" => (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400),
			"helpstep" => 3
		);
		$formdata["callearly"] = array(
			"label" => _L("Start Time"),
			"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
			"control" => array("FormHtml","html" => date("g:i a", strtotime($job->starttime))),
			"helpstep" => 3
		);
		$formdata["calllate"] = array(
			"label" => _L("End Time"),
			"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
			"control" => array("FormHtml","html" => date("g:i a", strtotime($job->endtime))),
			"helpstep" => 3
		);
	} else {
		// Prepare the the "Number of Days to run" data
		$maxdays = first($ACCESS->getValue('maxjobdays'), 7);
		$numdays = array_combine(range(1,$maxdays),range(1,$maxdays));
		$formdata["days"] = array(
			"label" => _L("Days to Run"),
			"fieldhelp" => _L("Select the number of days this survey should run."),
			"value" => (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400,
			"validators" => array(
				array("ValRequired"),
				array("ValDate", "min" => 1, "max" => ($ACCESS->getValue('maxjobdays') != null ? $ACCESS->getValue('maxjobdays') : "7"))
			),
			"control" => array("SelectMenu", "values" => $numdays),
			"helpstep" => 3
		);

		$formdata["callearly"] = array(
			"label" => _L("Start Time"),
			"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
			"value" => date("g:i a", strtotime($job->starttime)),
			"validators" => array(
						array("ValRequired"),
						array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
						array("ValTimeWindowCallEarly")
			),
			"requires" => array("calllate"),// is only required for non repeating jobs
			"control" => array("SelectMenu", "values"=>$startvalues),
			"helpstep" => 3
		);

		$formdata["calllate"] = array(
			"label" => _L("End Time"),
			"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
			"value" => date("g:i a", strtotime($job->endtime)),
			"validators" => array(
						array("ValRequired"),
						array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
						array("ValTimeWindowCallLate")
			),
			"requires" => array("callearly"), // is only required for non repeating jobs
			"control" => array("SelectMenu", "values"=>$endvalues),
			"helpstep" => 3
		);
		$formdata["calllate"]["requires"][] = "date";
	}

	$helpsteps[] = _L("Select an existing list to use. If you do not see the list you need,
						 you can make one by clicking the Lists subtab above. <br><br>
						 You may also opt to skip duplicates. Skip Duplicates is for calling 
						 each number once, so if, for example, two recipients have the same 
						 number, they will only be called once.");
	$helpsteps[] = _L("Select an existing message to use. If you do not see the message
						 you need, you can make a new message by clicking the Messages subtab above.");
	$helpsteps[] = _L("<ul><li>Auto Report - Selecting this option causes the system to email
						 a report to the email address associated with your account when the job 
						 is finished.<li>Max Attempts - This option lets you select the maximum
						 number of times the system should try to contact a recipient.
						 <li>Allow Reply - Check this if you want recipients to be able to
						 record responses.<br><br><b>Note:</b>You will need to include instructions
						 to press '0' to record a response in your message.<br><br>
						 <li>Allow Confirmation - Select this option if you would like recipients
						 to give a 'yes' or 'no' response to your message.<br><br>
						 <b>Note:</b>You will need to include instructions 
						 to press '1' for 'yes' and '2' for 'no' in your message.</ul>");

	if ($submittedmode || $completedmode) {
		$formdata[] = _L('List(s)');
		$query = "select name from list where id in (" . repeatWithSeparator("?", ",", count($selectedlists)) . ")";
		$listhtml = implode("<br/>",QuickQueryList($query,false,false,$selectedlists));
		$formdata["lists"] = array(
			"label" => _L('Lists'),
			"fieldhelp" => _L('Select a list from your existing lists.'),
			"control" => array("FormHtml","html" => $listhtml),
			"helpstep" => 4
		);
		
		$formdata[] = _L('Template');
		$formdata["template"] = array(
			"label" => _L('Template'),
			"fieldhelp" => _L('Select an existing survey template to use from the menu.'),
			"value" => (((isset($job->questionnaireid) && $job->questionnaireid))?$job->questionnaireid:""),
			"validators" => array(),
			"control" => array("MessageGroupSelectMenu", "values" => $templates, "static" => true),
			"helpstep" => 5
		);
		
		$formdata[] = _L('Advanced Options ');
		$formdata["report"] = array(
			"label" => _L('Auto Report'),
			"fieldhelp" => _L("Select this option if you would like the system to email you when the survey has finished running."),
			"control" => array(
				"FormHtml",
				"html" => "<input type='checkbox' " . ($job->isOption("sendreport")?"checked":"") . " disabled />"),
			"helpstep" => 6
		);
		
		// Prepare attempt data
		$maxattempts = first($ACCESS->getValue('callmax'), 1);
		$attempts = array_combine(range(1,$maxattempts),range(1,$maxattempts));

		$formdata["attempts"] = array(
			"label" => _L('Max Attempts'),
			"fieldhelp" => _L("Select the maximum number of times the system should try to contact an individual."),
			"control" => array("FormHtml","html" => $job->getOptionValue("maxcallattempts")),
			"helpstep" => 6
		);
		
	} else {
		$formdata[] = _L('List(s)');
		$formdata["lists"] = array(
			"label" => _L('Lists'),
			"fieldhelp" => _L('Select a list from your existing lists.'),
			"value" => ($selectedlists)?$selectedlists:array(),
			"validators" => array(
				array("ValRequired"),
				array("ValFormListSelect")
			),
			"control" => array("FormListSelect","jobid" => $job->id),
			"helpstep" => 4
		);
		
		$formdata[] = _L('Template');
		$formdata["template"] = array(
			"label" => _L('Template'),
			"fieldhelp" => _L('Select a survey template from your existing survey templates.'),
			"value" => (((isset($job->questionnaireid) && $job->questionnaireid))?$job->questionnaireid:""),
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values"=>array_keys($templates))
			),
			"control" => array("MessageGroupSelectMenu", "values" => $templates),
			"helpstep" => 5
		);
		
		$formdata[] = _L('Advanced Options ');
		$formdata["report"] = array(
			"label" => _L('Auto Report'),
			"fieldhelp" => _L("Select this option if you would like the system to email you when the survey has finished running."),
			"value" => $job->isOption("sendreport"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 6
		);
		
				// Prepare attempt data
		$maxattempts = first($ACCESS->getValue('callmax'), 1);
		$attempts = array_combine(range(1,$maxattempts),range(1,$maxattempts));

		$formdata["attempts"] = array(
			"label" => _L('Max Attempts'),
			"fieldhelp" => ("Select the maximum number of times the system should try to contact an individual."),
			"value" => $job->getOptionValue("maxcallattempts"),
			"validators" => array(
				array("ValRequired"),
				array("ValNumeric"),
				array("ValNumber", "min" => 1, "max" => $maxattempts)
			),
			"control" => array("SelectMenu", "values" => $attempts),
			"helpstep" => 6
		);
	}



$buttons = array(submit_button(_L('Save'),"submit","tick"));
if (!$submittedmode) {
	$buttons[] = submit_button(_L('Proceed To Confirmation'),"send","arrow_right");
} 
$buttons[] = icon_button(_L('Cancel'),"cross",null,(isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')?"start.php":"surveys.php"));


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
		$job->name = $postdata['name'];
		$job->description = $postdata['description'];
		$job->modifydate = date("Y-m-d H:i:s", time());
		$job->type = 'survey';
		
		$job->sendphone = false;
		$job->messagegroupid = NULL;
		$job->sendemail = false;
		
		if ($completedmode) {
			$job->update();
		} else {
			$numdays = $postdata['days'];
			$job->startdate = date("Y-m-d", strtotime($postdata['date']));
			$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));

			$job->starttime = date("H:i", strtotime($postdata['callearly']));
			$job->endtime = date("H:i", strtotime($postdata['calllate']));

			if ($submittedmode) {
				$job->update();
			} else {
				$job->jobtypeid = $postdata['jobtype'];
				$job->userid = $USER->id;
				
				$job->setOption("skipduplicates",true);
				$job->setOption("skipemailduplicates",true);
				
				$job->questionnaireid = $postdata['template'];
				
				// set jobsetting 'callerid' blank for jobprocessor to lookup the current default at job start
				if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
						// blank callerid is fine, save this setting and default will be looked up by job processor when job starts
						$job->setOptionValue("callerid",Phone::parse($postdata['callerid']));
				} else {
					$job->setOptionValue("callerid", getDefaultCallerID());
				}
				
				$job->setOption("sendreport",$postdata['report']?1:0);
				$job->setOptionValue("maxcallattempts", $postdata['attempts']);

				if ($job->id) {
					$job->update();
				} else {
					$job->status = "new";
					$job->createdate = date("Y-m-d H:i:s", time());
					$job->create();
				}
				if ($job->id) {
					/* Store lists*/
					QuickUpdate("DELETE FROM joblist WHERE jobid=?",false,array($job->id));
					$listids = $postdata['lists'];
					$batchargs = array();
					$batchsql = "";
					foreach ($listids as $id) {
						$batchsql .= "(?,?),";
						$batchargs[] = $job->id;
						$batchargs[] = $id;
					}
					if ($batchsql) {
						$sql = "INSERT INTO joblist (jobid,listid) VALUES " . trim($batchsql,",");
						QuickUpdate($sql,false,$batchargs);
					}
				}
			}
		}
		Query("COMMIT");

		if ($button=="send") {
			$_SESSION['jobid'] = $job->id;
			$sendto = "surveyconfirm.php";
		} else {
			if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
				unset($_SESSION['origin']);
				$sendto = 'start.php';
			} else {
				$sendto = 'surveys.php';
			}
		}
		if ($ajax)
			$form->sendTo($sendto);
		else
			redirect($sendto);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";

$TITLE = _L('Survey Editor: ');
$TITLE .= ($job->id == NULL ? _L("New Survey") : escapehtml($job->name));

include_once("nav.inc.php");

// Load Custom Form Validators
?>
<script type="text/javascript">
<? 
Validator::load_validators(array("ValDuplicateNameCheck",
								"ValTimeWindowCallEarly",
								"ValTimeWindowCallLate",
								"ValFormListSelect"));
?>
</script>
<?

startWindow(_L('Survey Information'));

echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
