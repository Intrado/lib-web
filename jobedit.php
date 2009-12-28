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

$jobid = $_SESSION['jobid'];
if ($_SESSION['jobid'] == NULL) {
	$job = Job::jobWithDefaults();
} else {
	$job = new Job($_SESSION['jobid']);
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

class WeekRepeatItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '
				<input id="'.$n.'" name="'.$n.'" type="hidden" value="' . escapehtml(json_encode($value)) . '"/>
				<table border="0" cellpadding="2" cellspacing="1" class="list">
					<tr class="listHeader" align="left" valign="bottom">
						<th>Su</th>
						<th>M</th>
						<th>Tu</th>
						<th>W</th>
						<th>Th</th>
						<th>F</th>
						<th>Sa</th>
						<th>Time</th>
					</tr>
					<tr>
						<td><input id="itm0_'.$n.'" type="checkbox" '. ($value[0] ? 'checked' : '').' /></td>
						<td><input id="itm1_'.$n.'" type="checkbox" '. ($value[1] ? 'checked' : '').' /></td>
						<td><input id="itm2_'.$n.'" type="checkbox" '. ($value[2] ? 'checked' : '').' /></td>
						<td><input id="itm3_'.$n.'" type="checkbox" '. ($value[3] ? 'checked' : '').' /></td>
						<td><input id="itm4_'.$n.'" type="checkbox" '. ($value[4] ? 'checked' : '').' /></td>
						<td><input id="itm5_'.$n.'" type="checkbox" '. ($value[5] ? 'checked' : '').' /></td>
						<td><input id="itm6_'.$n.'" type="checkbox" '. ($value[6] ? 'checked' : '').' /></td>
					<td>';

		$str .= '<select id="itm7_'.$n.'">';
		foreach ($this->args['timevalues'] as $selectvalue => $selectname) {
			$checked = $value[7] == $selectvalue;
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>
				';
		}
		$str .= '</select>';

		$str .=		'</td>
					</tr>
				</table>
				<script type="text/javascript" language="javascript">
					function makerepeat(e) {
						var n = "' .$n. '";
						var values = Array();
						for(var i=0;i < 7;i++) {
							values.push($("itm" + i + "_" +  n).checked);
						}
						values.push($("itm7_" +  n).getValue());
						$(n).value = values.toJSON();
						form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
					}
					document.observe("dom:loaded", function() {
						var n = "' .$n. '";
						for(var i=0;i < 8;i++) {
							$("itm" + i + "_"+ n).observe("change",makerepeat);
						}
					});
				</script>
		';
		return $str;
	}
}

class ValWeekRepeatItem extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;

		if(!is_array($value)) {
			$value = json_decode($value);
		}

		if(count($value) != 8 )
			return _L('An error occurred, try reloading the page'); // decoding problem or anything else
		$time = strtotime($value[7]);
		if($time < strtotime($USER->getCallEarly()))
			return _L('Time is can not be before %1$s',$USER->getCallEarly());
		else if($time > strtotime($USER->getCallLate()))
			return _L('Time is can not be after %1$s',$USER->getCallLate());
		for($i = 0;$i < 7;$i++){
			if(!is_bool($value[$i]))
				return _L('Invalid Input');
		}
		return true;
	}
}

/* TODO these validators are copied from the wizard
	Make sure they are moved to avoid duplicate implementation
*/

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

$messages = array_merge(array("" => _L("-- Select a Message --")),QuickQueryList("select id, name, (name +0) as digitsfirst from messagegroup where userid=? and deleted=0 order by digitsfirst,name", true,false,array($USER->id)));



$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');

$formdata = array(
	_L('Job Settings'),
	"name" => array(
		"label" => _L('Job Name'),
		"value" => isset($job->name)?$job->name:"",
		"validators" => array(
			array("ValRequired"),
			array("ValDuplicateNameCheck","type" => "job"),
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
		"value" => isset($job->jobtypeid)?$job->jobtypeid:"",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($jobtypes))
		),
		"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
		"helpstep" => 2
	));

	$callearlyvalidators = array(
			array("ValRequired"),
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate'))
	);
	$calllatevalidators = $callearlyvalidators;
	
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
		$repeatvalues[] = date("g:i a", strtotime($schedule->time));
		$formdata["repeat"] = array(
			"label" => _L("Repeat"),
			"fieldhelp" => _L(""),
			"value" => $repeatvalues,
			"validators" => array(
				array("ValRequired"),
				array("ValWeekRepeatItem")
			),
			"control" => array("WeekRepeatItem","timevalues" => newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate())),
			"helpstep" => 2
		);
	} else {
		$callearlyvalidators[] = array("ValTimeWindowCallEarly");
		$calllatevalidators[] = array("ValTimeWindowCallLate");
		$formdata["date"] = array(
			"label" => _L("Start Date"),
			"fieldhelp" => _L("Notification will begin on the selected date."),
			"value" => "now + $dayoffset days",
			"validators" => array(
				array("ValRequired"),
				array("ValDate", "min" => date("m/d/Y", strtotime("now + $dayoffset days")))
			),
			"control" => array("TextDate", "size"=>12, "nodatesbefore" => $dayoffset),
			"helpstep" => 2
		);
	}

	// Prepare the the "Number of Days to run" data
	$numdays = array();
	$maxdays = first($ACCESS->getValue('maxjobdays'), 7);
	for ($i = 1; $i <= $maxdays; $i++) {$numdays[$i] = $i;}

	// Prepare attempt data
	$attempts = array();
	$maxattempts = first($ACCESS->getValue('callmax'), 1);
	for ($i = 1; $i <= $maxattempts; $i++) {$attempts[$i] = $i;}

	$formdata = array_merge($formdata,array(
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
		"validators" => $callearlyvalidators,
		"requires" => array("calllate"),// is only required for non repeating jobs
		"control" => array("SelectMenu", "values"=>$startvalues),
		"helpstep" => 2
	),
	"calllate" => array(
		"label" => _L("End Time"),
		"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
		"value" => $USER->getCallLate(),
		"validators" => $calllatevalidators,
		"requires" => array("callearly", "date"), // is only required for non repeating jobs
		"control" => array("SelectMenu", "values"=>$endvalues),
		"helpstep" => 2
	),
	_L('Job Lists'),
	"lists" => array(
		"label" => _L('Lists'),
		"value" => empty($selectedlists)?"":json_encode($selectedlists),
		"validators" => array(
			array("ValRequired"),
			array("ValLists")
		),
		"control" => array("JobListItem"),
		"helpstep" => 3
	),
	"skipduplicates" => array(
		"label" => _L('Skip Duplicates'),
		"value" => $job->isOption("skipduplicates") || $job->isOption("skipemailduplicates"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 3
	),
	_L('Job Message'),
	"message" => array(
		"label" => _L('Message'),
		"value" => (isset($job->id)?$job->messagegroupid:""),
		"validators" => array(array("ValRequired")),
		"control" => array("SelectMenu", "values" => $messages),
		"helpstep" => 4
	),
	_L('Advanced Options '),
	"report" => array(
		"label" => _L('Completion Report'),
		"value" => $job->isOption("sendreport"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	)));

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
			"helpstep" => 5
		);
	}
	
	$formdata = array_merge($formdata,array(
	"attempts" => array(
		"label" => _L('Max Attempts'),
		"value" => $job->getOptionValue("maxcallattempts"),
		"validators" => array(array("ValRequired")),
		"control" => array("SelectMenu", "values" => $attempts),
		"helpstep" => 5
	),
	"replyoption" => array(
		"label" => _L('Allow Reply'),
		"value" => $job->isOption("leavemessage"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"confirmoption" => array(
		"label" => _L('Allow Confirmation'),
		"value" => $job->isOption("messageconfirmation"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
));
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
		$job->name = $postdata['name'];
		$job->description = "";
		$job->jobtypeid = $postdata['jobtype'];

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
		$job->userid = $USER->id;
		$job->modifydate = date("Y-m-d H:i:s", time());



		// TODO remove type in job
		$jobtypes = array();
		if ($cansendphone) {
			$jobtypes[] = "phone";
		}
		if ($cansendemail) {
			$jobtypes[] = "email";
		}
		if ($cansendsms) {
			$jobtypes[] = "sms";
		}
		$job->type=implode(",",$jobtypes);
		//

		$job->setOption("skipduplicates",$postdata['skipduplicates']);


		$job->messagegroupid = $postdata['message'];


		// set jobsetting 'callerid' blank for jobprocessor to lookup the current default at job start
		if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
				// blank callerid is fine, save this setting and default will be looked up by job processor when job starts
				$job->setOptionValue("callerid",Phone::parse($postdata['callerid']));
		} else {
			$job->setOptionValue("callerid", getDefaultCallerID());
		}

		if ($USER->authorize("leavemessage"))
			$job->setOption("leavemessage", $postdata['replyoption']);

		if ($USER->authorize("messageconfirmation"))
			$job->setOption("messageconfirmation", $postdata['confirmoption']);


		$job->setOption("sendreport",$postdata['report']);
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
<? Validator::load_validators(array("ValDuplicateNameCheck","ValWeekRepeatItem","ValTimeWindowCallEarly","ValTimeWindowCallLate","ValDate","ValLists")); ?>
</script>
<?

startWindow(_L('Job Information'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
