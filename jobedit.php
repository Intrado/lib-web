<?
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


$completedmode = false; // Flag indicating that a job is complete or cancelled so only allow editing of name and description.
$submittedmode = false; // Flag indicating that a job has been submitted, allowing editing of date/time, name/desc, and a few selected options.

$jobid = $_SESSION['jobid'];
if ($_SESSION['jobid'] == NULL) {
	$job = Job::jobWithDefaults();
} else {
	$job = new Job($_SESSION['jobid']);
	$completedmode = in_array($job->status, array('complete','cancelled','cancelling'));
	$submittedmode = ($completedmode || in_array($job->status,array('active','procactive','processing','scheduled')));
}


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class JobListItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		return "<input id='" .$n."' name='".$n."' type='hidden' value='". escapehtml($value) ."'/>
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

class ValTranslationExpirationDate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args,$requiredvalues) {
		global $USER;	
		global $submittedmode;
		if($submittedmode) {
			global $job;
			$modifydate = QuickQuery("select min(modifydate) from message where messagegroupid = ? and autotranslate = 'translated'", false, array($job->messagegroupid));
		} else {
			if($requiredvalues['message'] == "")
				return true;
			$modifydate = QuickQuery("select min(modifydate) from message where messagegroupid = ? and autotranslate = 'translated'", false, array($requiredvalues['message']));
		}
		if($modifydate != false) {
			if(strtotime("today") - strtotime($modifydate) > (7*86400))
				return $this->label. " ". _L('The selected message below contains auto-translated content older than 7 days. Regenerate translations to schedule a start date');
			if(strtotime($value) - strtotime($modifydate) > (7*86400))
				return _L("Can not schedule the job with a message containing auto-translated content older than 7 days from the Start Date");
		}
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

// get the user's owned and subscribed messages
$messages = QuickQueryList(
	"(select mg.id,
		mg.name as name,
		(mg.name +0) as digitsfirst
	from messagegroup mg
	where mg.userid=?
		and not mg.deleted)
	UNION
	(select mg.id,
		mg.name as name,
		(mg.name +0) as digitsfirst
	from publish p
	inner join messagegroup mg on
		(p.messagegroupid = mg.id)
	where p.userid=?
		and p.action = 'subscribe'
		and p.type = 'messagegroup'
		and not mg.deleted)
	order by digitsfirst, name",
	true,false,array($USER->id, $USER->id));

if($messages === false) {
	$messages = array("" =>_L("-- Select a Message --"));
} else {
	$messages = array("" =>_L("-- Select a Message --")) + $messages;
}

if($job->messagegroupid != null) {
	$deletedmessage = QuickQueryRow("select id, name from messagegroup where id = ? and deleted = 1", false, false,array($job->messagegroupid));
	if($deletedmessage != false)
		$messages += array($deletedmessage[0] => $deletedmessage[1]);
}


$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
$cansendmultilingual = $USER->authorize('sendmulti');

$helpsteps = array();
$formdata = array();

$formdata[] = _L('Job Settings');

$helpsteps[] = _L("The name of your job. The best names are brief and discriptive of the message content.");

	$formdata["name"] = array(
		"label" => _L('Job Name'),
		"value" => isset($job->name)?$job->name:"",
		"validators" => array(
			array("ValRequired"),
			array("ValDuplicateNameCheck","type" => "job"),
			array("ValLength","max" => ($JOBTYPE == "repeating")?30:50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	);
	$formdata["description"] = array(
		"label" => _L('Description'),
		"value" => isset($job->description)?$job->description:"",
		"validators" => array(
			array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	);

	if($submittedmode || $completedmode) {
		$helpsteps[] = _L("The option that best describes the type of notification you are sending.");
		$formdata["jobtype"] = array(
			"label" => _L("Type/Category"),
			"fieldhelp" => _L("The option that best describes the type of notification you are sending."),
			"control" => array("FormHtml","html" => $jobtypes[$job->jobtypeid]),
			"helpstep" => 2
		);
	} else {
		$helpsteps[] = _L("Select the option that best describes the type of notification you are sending.");
		$formdata["jobtype"] = array(
			"label" => _L("Type/Category"),
			"fieldhelp" => _L("Select the option that best describes the type of notification you are sending."),
			"value" => isset($job->jobtypeid)?$job->jobtypeid:"",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($jobtypes))
			),
			"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
			"helpstep" => 2
		);
	}
	
	if ($JOBTYPE == "repeating") {
		$schedule = new Schedule($job->scheduleid);

		$scheduledows = array();
		if ($schedule->id == NULL) {
			$schedule->time = $USER->getCallEarly();
		} else {
			$data = explode(",", $schedule->daysofweek);
			for ($x = 1; $x < 8; $x++){
				if(in_array($x,$data))
					$scheduledows[$x-1] = true;
			}
		}
		$repeatvalues = array();
		for ($x = 0; $x < 7; $x++) {
			$repeatvalues[] = isset($scheduledows[$x]);
		}
		$repeatvalues[7] = date("g:i a", strtotime($schedule->time));

		$helpsteps[] = _L("");  // Guide for the whole scheduling section
		$formdata["repeat"] = array(
			"label" => _L("Repeat"),
			"fieldhelp" => _L(""),
			"value" => $repeatvalues,
			"validators" => array(
				array("ValRequired"),
				array("ValWeekRepeatItem")
			),
			"control" => array("WeekRepeatItem","timevalues" => newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate())),
			"helpstep" => 3
		);
	} else {
		if($completedmode) {

			$helpsteps[] = _L("The Delivery Window designates the earliest call time and the latest call time allowed for notification delivery.");  // Guide for the whole scheduling section
			$formdata["date"] = array(
				"label" => _L("Start Date"),
				"fieldhelp" => _L("Notification will begin on the selected date."),
				"control" => array("FormHtml","html" => date("m/d/Y", strtotime($job->startdate))),
				"helpstep" => 3
			);
		} else {
			$helpsteps[] = _L("The Delivery Window designates the earliest call time and the latest call time allowed for notification delivery.");  // Guide for the whole scheduling section
			$formdata["date"] = array(
				"label" => _L("Start Date"),
				"fieldhelp" => _L("Notification will begin on the selected date."),
				"value" => isset($job->startdate)?$job->startdate:"now + $dayoffset days",
				"validators" => array(
					array("ValRequired"),
					array("ValDate", "min" => date("m/d/Y", strtotime("now + $dayoffset days")))
					,array("ValTranslationExpirationDate")
				),
				"control" => array("TextDate", "size"=>12, "nodatesbefore" => $dayoffset),
				"helpstep" => 3
			);
			if(!$submittedmode)
				$formdata["date"]["requires"] = array("message");
		}
	}
	if($completedmode) {
		$formdata["days"] = array(
			"label" => _L("Days to Run"),
			"fieldhelp" => _L(""),
			"control" => array("FormHtml","html" => (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400),
			"helpstep" => 3
		);
		$formdata["callearly"] = array(
			"label" => _L("Start Time"),
			"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
			"control" => array("FormHtml","html" => $USER->getCallEarly()),
			"helpstep" => 3
		);
		$formdata["calllate"] = array(
			"label" => _L("End Time"),
			"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
			"control" => array("FormHtml","html" => $USER->getCallLate()),
			"helpstep" => 3
		);
	} else {
		// Prepare the the "Number of Days to run" data
		$maxdays = first($ACCESS->getValue('maxjobdays'), 7);
		$numdays = array_combine(range(1,$maxdays),range(1,$maxdays));
		$formdata["days"] = array(
			"label" => _L("Days to Run"),
			"fieldhelp" => _L(""),
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

		if($JOBTYPE != "repeating") {// is only required for non repeating jobs
			$formdata["calllate"]["requires"][] = "date";
		}
	}

	$helpsteps[] = _L("List");
	$helpsteps[] = _L("Message");
	$helpsteps[] = _L("Advanced");

	if($submittedmode || $completedmode) {
		$formdata[] = _L('Job Lists');

		$formdata["lists"] = array(
			"label" => _L('Lists'),
			"control" => array("FormHtml","html" => implode("<br/>",QuickQueryList("select name from list where id in (" . repeatWithSeparator("?", ",", count($selectedlists)) . ")", false,false,$selectedlists))),
			"helpstep" => 4
		);
		$formdata["skipduplicates"] = array(
			"label" => _L('Skip Duplicates'),
			"control" => array("FormHtml","html" => ($job->isOption("skipduplicates") || $job->isOption("skipemailduplicates"))?"<input type='checkbox' checked disabled/>":"<input type='checkbox' disabled/>"),
			"helpstep" => 4
		);
		$formdata[] = _L('Job Message');
		$formdata["message"] = array(
			"label" => _L('Message'),
			"value" => (((isset($job->messagegroupid) && $job->messagegroupid))?$job->messagegroupid:""),
			"validators" => array(),
			"control" => array("MessageGroupSelectMenu", "values" => $messages, "static" => true),
			"helpstep" => 5
		);

		$formdata[] = _L('Advanced Options ');
		$formdata["report"] = array(
			"label" => _L('Completion Report'),
			"control" => array("FormHtml","html" => $job->isOption("sendreport")?"<input type='checkbox' checked disabled/>":"<input type='checkbox' disabled/>"),
			"helpstep" => 6
		);

		if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
			$formdata["callerid"] = array(
				"label" => _L("Personal Caller ID"),
				"control" => array("FormHtml","html" => Phone::format($job->getSetting("callerid",getDefaultCallerID()))),
				"helpstep" => 6
			);
		}

		// Prepare attempt data
		$maxattempts = first($ACCESS->getValue('callmax'), 1);
		$attempts = array_combine(range(1,$maxattempts),range(1,$maxattempts));

		$formdata["attempts"] = array(
			"label" => _L('Max Attempts'),
			"control" => array("FormHtml","html" => $job->getOptionValue("maxcallattempts")),
			"helpstep" => 6
		);
		$formdata["replyoption"] = array(
			"label" => _L('Allow Reply'),
			"control" => array("FormHtml","html" => $job->isOption("leavemessage")?"<input type='checkbox' checked disabled/>":"<input type='checkbox' disabled/>"),
			"helpstep" => 6
		);
		$formdata["confirmoption"] = array(
			"label" => _L('Allow Confirmation'),
			"control" => array("FormHtml","html" => $job->isOption("messageconfirmation")?"<input type='checkbox' checked disabled/>":"<input type='checkbox' disabled/>"),
			"helpstep" => 6
		);
	} else {
		$formdata[] = _L('Job Lists');
		$formdata["lists"] = array(
			"label" => _L('Lists'),
			"value" => empty($selectedlists)?"":json_encode($selectedlists),
			"validators" => array(
				array("ValRequired"),
				array("ValLists", 'jobtype' => $JOBTYPE)
			),
			"control" => array("JobListItem"),
			"helpstep" => 4
		);
		$formdata["skipduplicates"] = array(
			"label" => _L('Skip Duplicates'),
			"value" => $job->isOption("skipduplicates") || $job->isOption("skipemailduplicates"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 4
		);
		$formdata[] = _L('Job Message');
		$formdata["message"] = array(
			"label" => _L('Message'),
			"value" => (((isset($job->messagegroupid) && $job->messagegroupid))?$job->messagegroupid:""),
			"validators" => array(
				array("ValRequired"),
				array("ValMessageTranslationExpiration"),
				array("ValInArray","values"=>array_keys($messages))
				),

			"control" => array("MessageGroupSelectMenu", "values" => $messages),
			"helpstep" => 5
		);

		if ($JOBTYPE != "repeating") {
			$formdata["message"]["requires"] = array("date");
		}

		$formdata[] = _L('Advanced Options ');
		$formdata["report"] = array(
			"label" => _L('Completion Report'),
			"value" => $job->isOption("sendreport"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 6
		);

		if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
			$formdata["callerid"] = array(
				"label" => _L("Personal Caller ID"),
				"fieldhelp" => (""),
				"value" => Phone::format($job->getSetting("callerid",getDefaultCallerID())),
				"validators" => array(
					array("ValLength","min" => 3,"max" => 20),
					array("ValPhone")
				),
				"control" => array("TextField","maxlength" => 20, "size" => 15),
				"helpstep" => 6
			);
		}

		// Prepare attempt data
		$maxattempts = first($ACCESS->getValue('callmax'), 1);
		$attempts = array_combine(range(1,$maxattempts),range(1,$maxattempts));

		$formdata["attempts"] = array(
			"label" => _L('Max Attempts'),
			"value" => $job->getOptionValue("maxcallattempts"),
			"validators" => array(array("ValRequired")),
			"control" => array("SelectMenu", "values" => $attempts),
			"helpstep" => 6
		);
		$formdata["replyoption"] = array(
			"label" => _L('Allow Reply'),
			"value" => $job->isOption("leavemessage"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 6
		);
		$formdata["confirmoption"] = array(
			"label" => _L('Allow Confirmation'),
			"value" => $job->isOption("messageconfirmation"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 6
		);
	}



$buttons = array(submit_button(_L('Save'),"submit","tick"));
if ($JOBTYPE == "normal" && !$submittedmode) {
	$buttons[] = submit_button(_L('Proceed To Confirmation'),"send","arrow_right");
} 
$buttons[] = icon_button(_L('Cancel'),"cross",null,"start.php");


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
		$job->type = 'notification';
		
		if($completedmode) {
			$job->update();
		} else {
			if ($JOBTYPE == "repeating") {
				$repeatdata = json_decode($postdata['repeat'],true);

				$schedule = new Schedule($job->scheduleid);
				$schedule->time = date("H:i", strtotime($repeatdata[7]));
				$schedule->triggertype = "job";
				$schedule->type = "R";
				$schedule->userid = $USER->id;

				$dow = array();
				for ($x = 0; $x < 7; $x++) {
					if($repeatdata[$x] === true) {
						$dow[$x] = $x+1;
					}
				}
				$schedule->daysofweek = implode(",",$dow);
				$schedule->nextrun = $schedule->calcNextRun();

				$schedule->update();
				$job->scheduleid = $schedule->id;
				$numdays = $postdata['days'];
				// 86,400 seconds in a day - precaution b/c windows doesn't
				//	like dates before 1970, and using 0 makes windows think it's 12/31/69
				$job->startdate = date("Y-m-d", 86400);
				$job->enddate = date("Y-m-d", ($numdays * 86400));
			} else if ($JOBTYPE == 'normal') {
				$numdays = $postdata['days'];
				$job->startdate = date("Y-m-d", strtotime($postdata['date']));
				
				$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
			}
			
			$job->starttime = date("H:i", strtotime($postdata['callearly']));
			$job->endtime = date("H:i", strtotime($postdata['calllate']));

			if($submittedmode) {
				$job->update();
			} else {
				$job->jobtypeid = $postdata['jobtype'];
				$job->userid = $USER->id;

				$job->setOption("skipduplicates",$postdata['skipduplicates']?1:0);
				$job->setOption("skipemailduplicates",$postdata['skipduplicates']?1:0);


				$job->messagegroupid = $postdata['message'];


				// set jobsetting 'callerid' blank for jobprocessor to lookup the current default at job start
				if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
						// blank callerid is fine, save this setting and default will be looked up by job processor when job starts
						$job->setOptionValue("callerid",Phone::parse($postdata['callerid']));
				} else {
					$job->setOptionValue("callerid", getDefaultCallerID());
				}

				if ($USER->authorize("leavemessage"))
					$job->setOption("leavemessage", $postdata['replyoption']?1:0);

				if ($USER->authorize("messageconfirmation"))
					$job->setOption("messageconfirmation", $postdata['confirmoption']?1:0);


				$job->setOption("sendreport",$postdata['report']?1:0);
				$job->setOptionValue("maxcallattempts", $postdata['attempts']);

				if ($job->id) {
					$job->update();
				} else {
					$job->status = ($JOBTYPE == "normal")?"new":"repeating";
					$job->createdate = date("Y-m-d H:i:s", time());
					$job->create();
				}
				if($job->id) {
					/* Store lists*/
					QuickUpdate("DELETE FROM joblist WHERE jobid=$job->id");
					$listids = json_decode($postdata['lists']);
					$batchvalues = array();
					foreach ($listids as $id) {
						$values = "($job->id,". ($id+0) . ")"; // TODO prepstmt args
						$batchvalues[] = $values;
					}
					if (!empty($batchvalues)) {
						$sql = "INSERT INTO joblist (jobid,listid) VALUES ";
						$sql .= implode(",",$batchvalues);
						QuickUpdate($sql);
					}
				}
			}
		}
		Query("COMMIT");

		if($button=="send") {
			$_SESSION['jobid'] = $job->id;
			$sendto = "jobconfirm.php";
		} else {
			$sendto = "jobs.php";
		}
		if ($ajax)
			$form->sendTo($sendto);
		else
			redirect($sendto);
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
<? Validator::load_validators(array("ValDuplicateNameCheck","ValTranslationExpirationDate","ValMessageTranslationExpiration","ValWeekRepeatItem","ValTimeWindowCallEarly","ValTimeWindowCallLate","ValLists")); ?>
</script>
<?

startWindow(_L('Job Information'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
