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
require_once("obj/WeekRepeat.fi.php");
require_once("obj/WeekRepeat.val.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
require_once("obj/ValMessageGroup.val.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Voice.obj.php");
require_once("inc/translate.inc.php");
require_once("obj/FormListSelect.fi.php");
require_once("inc/date.inc.php");
require_once("obj/ValListSelection.val.php");
require_once("obj/FacebookPage.fi.php");
require_once("obj/TwitterAuth.fi.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");


// Includes that are required for preview to work
require_once("obj/Language.obj.php");
require_once("inc/previewfields.inc.php");
require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php';
require_once("obj/PreviewModal.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
$cansendjob = isset($JOBTYPE) && isset($USER) && (($USER->authorize('sendphone') || $USER->authorize('sendemail') || $USER->authorize('sendsms')));
if (!$cansendjob)
	redirect('unauthorized.php');

$cansendrepeatingjob = ($JOBTYPE == "repeating" && $USER->authorize('createrepeat'));
if ($JOBTYPE != "normal" && !$cansendrepeatingjob)
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
PreviewModal::HandleRequestWithId();


$job = null;
if (isset($_GET['id'])) {
	if ($_GET['id'] !== "new" && !userOwns("job",$_GET['id']))
		redirect('unauthorized.php');
	setCurrentJob($_GET['id']);
	redirect();
}

if (isset($_GET['origin'])) {
	$_SESSION['origin'] = trim($_GET['origin']);
}



// Flag indicating that a job is complete or cancelled so only allow editing of name and description.
$completedmode = false; 

// Flag indicating that a job has been submitted, allowing editing of date/time, name/desc, and a few selected options.
$submittedmode = false; 

$jobid = $_SESSION['jobid'];
if ($_SESSION['jobid'] == NULL) {
	$job = Job::jobWithDefaults();
} else {
	$job = new Job($_SESSION['jobid']);
	if ($job->type != "notification")
		redirect('unauthorized.php');
	// check if editing a repeating job or normal job, verify job obj is of correct type
	if ($job->status == "repeating" && $JOBTYPE != "repeating")
		redirect('unauthorized.php');
	if ($JOBTYPE == "repeating" && $job->status != "repeating")
		redirect('unauthorized.php');
		
	$completedmode = in_array($job->status, array('complete','cancelled','cancelling'));
	$submittedmode = ($completedmode || in_array($job->status,array('active','procactive','processing','scheduled')));
}


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////
class ReadOnlyFacebookPage extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		// [ <pageid>, <pageid>, ... ]
		if (!$value)
			$value = json_encode(array());
		
		// main details div
		$str = '
			<style>
				.fbpagelist {
					width: 98%;
					padding: 3px;
					max-height: 250px;
					overflow: auto;
				}
				.fbname {
					font-weight: bold;
				}
				.fbimg {
					padding: 3px;
					float: left;
				}
			</style>
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
			<div id="fb-root"></div>
			<div id="'. $n. 'fbpages" class="fbpagelist">
				<img src="img/ajax-loader.gif" alt="'. escapehtml(_L("Loading")). '"/>
			</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		global $SETTINGS;
		$n = $this->form->name."_".$this->name;
		
		$str = '// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
					
					// load the initial list of pages if possible
					updateFbPagesRo("'.$n.'", "'.$n.'fbpages");
				};
				(function() {
					var e = document.createElement("script");
					e.type = "text/javascript";
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("fb-root").appendChild(e);
				}());
				';
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '<script type="text/javascript">
			
			function updateFbPagesRo(formitem, container) {
				
				var pages = $(formitem).value.evalJSON();
				container = $(container);
				
				container.update();
				
				// add a loading indicator
				$(container).insert(
					new Element("div", { id: formitem + "-pageloading" }).insert(
						new Element("img", { "src": "img/ajax-loader.gif", "alt": "Loading" })
					)
				);
				
				$A(pages).each(function (pageid) {
					FB.api("/" + pageid, function(res) {
						if (res && !res.error) {
							addFbPageElementRo(formitem, container, res);
						} else {
							// no data returned
							container.update(
								new Element("div").setStyle({padding: "5px"}).update(
									"'. escapehtml(_L('Error encountered trying to get pages')). '"));
						}
					});
				});
				
				// remove the loading icon
				$(formitem + "-pageloading").remove();
			}
			
			// get an account element with all the facebook page info, returns the checkbox
			function addFbPageElementRo(e, container, account) {
				if (account.category == undefined)
					var category = "Wall Posting";
				else
					var category = account.category.escapeHTML();
					
				var name = account.name.escapeHTML();
				var id = account.id;
				
				var pageimage = new Element("img", { "class": "fbimg", "src": "https://graph.facebook.com/"+ account.id +"/picture?type=square" });
				var accountitem = new Element("div").insert(
						pageimage
					).insert(
						new Element("div").insert(
							new Element("div", { "class": "fbname" }).update(name)
						).insert(
							new Element("div", { "class": "fbcategory" }).update(category))
					);
				$(container).insert(accountitem);
				$(container).insert(new Element("div").setStyle({ "clear": "both"}));
			}
			</script>';
		return $str;
	}
}

class TwitterAccountPopup extends FormItem {
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		$validtoken = ($this->args['hasvalidtoken']);
		
		$str = '
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.($validtoken?"authed":"noauth").'" />
			<div id="'. $n. 'connect" style="display:'. ($validtoken?"none":"block"). '">
				'. icon_button(_L("Add Twitter Account"), "custom/twitter", "popup('popuptwitterauth.php', 600, 300)").'
			</div>
			<div id="'. $n. 'authed" style="display:'. ($validtoken?"block":"none"). '">
				'. _L("Your twitter account is connected."). '
			</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		
		$str = '// Observe an authentication update on the document (the auth popup fires this event)
				document.observe("TwAuth:update", function (res) {
					var formitem = $("'. $n. '");
					var connectdiv = $("'. $n. 'connect");
					var autheddiv = $("'. $n. 'authed");
					if (res.memo.access_token) {
						formitem.value = "authed";
						connectdiv.hide();
						autheddiv.show();
					} else {
						formitem.value = "noauth";
						connectdiv.show();
						autheddiv.hide();
					}
					form_do_validation($("'.$n.'").up("form"), $("'.$n.'"));
				});
				';
		return $str;
	}
}

class ValTwitterAccountWithMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		global $USER;
		$mg = new MessageGroup($requiredvalues['message']);
		// if the message group doesn't have twitter, we don't care if they auth an account or not
		if (!$mg->hasMessage("post", "twitter"))
			return true;
		
		if (!$USER->authorize('twitterpost'))
			return $this->label. " ". _L("current user is not authorized to post messages.");
		
		// access token not stored?
		if ($value == "noauth")
			return $this->label. " ". _L("current user is not authorized to post messages.");
			
		return true;
	}
}

class ValTranslationExpirationDate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args,$requiredvalues) {
		global $USER;	
		global $submittedmode;
		if ($submittedmode) {
			global $job;
			$query = "select 1 from message where messagegroupid = ? and autotranslate = 'translated' limit 1";
			$hastranslated = QuickQuery($query, false, array($job->messagegroupid));
		} else {
			if ($requiredvalues['message'] == "")
				return true;
			$query = "select 1 from message where messagegroupid = ? and autotranslate = 'translated' limit 1";
			$hastranslated = QuickQuery($query, false, array($requiredvalues['message']));
		}

		if ($hastranslated != false) {
			if (strtotime($value) > strtotime("today") + (7*86400))
				return _L("Can not schedule the job with a message containing
							 auto-translated content older than 7 days from the Start Date");
		}
		return true;
	}
}

// ValIsTranslated is used to prevent a repeating job to contain a translated message
class ValIsTranslated extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		$query = "select 1 from message where messagegroupid = ? and autotranslate = 'translated' limit 1";
		$istranslated = QuickQuery($query, false, array($value));
		if ($istranslated > 0) {
			return _L("Can not select a message that is auto-translated with a repeating job");
		}
		return true;
	}
}

// requires the message form item and validates that there are valid pages selected... but only if the message has facebook
class ValFacebookPageWithMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		global $USER;
		$mg = new MessageGroup($requiredvalues['message']);
		// if the message group doesn't have facebook, we don't care if they choose a page or not
		if (!$mg->hasMessage("post", "facebook"))
			return true;
		
		if (!$USER->authorize('facebookpost'))
			return $this->label. " ". _L("current user is not authorized to post messages.");
		
		$fbdata = json_decode($value);
		
		// get the authorized pages
		// don't trust args, look up the authorized pages
		$authpages = getFbAuthorizedPages();
		$authwall = getSystemSetting("fbauthorizewall");
		
		// check to see if any pages are selected
		$haspage = false;
		foreach ($fbdata as $pageid) {
			$haspage = true;
			// check authorized pages to see if the ones selected are allowed
			if ($pageid == "me") {
				if (!$authwall)
					return $this->label. " ". _L("has an invalid selection. Personal wall posting is disabled.");
			} else if ($authpages && !in_array($pageid, $authpages)) {
				return $this->label. " ". _L("has an invalid posting location selected. Page is not authorized.");
			}
		}
		if (!$haspage)
			return $this->label. " ". _L("must have one or more pages to post to.");
		
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

$dayoffset = (strtotime("now") > (strtotime(($ACCESS->getValue("calllate")?$ACCESS->getValue("calllate"):"11:59 pm"))))?1:0;

$customstarttime = isset($job->id)? date("g:i a", strtotime($job->starttime)) : $USER->getCallEarly();
$costomendtime = isset($job->id)? date("g:i a", strtotime($job->endtime)) : $USER->getCallLate();
$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $customstarttime);
$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $costomendtime);

// get the user's owned and subscribed messages
$messages = array("" =>_L("-- Select a Message --"));
$query = "(select mg.id,mg.name as name,(mg.name +0) as digitsfirst	from messagegroup mg 
			where mg.userid=? and mg.type = 'notification' and not mg.deleted)
		UNION
			(select mg.id,mg.name as name,(mg.name +0) as digitsfirst from publish p
			inner join messagegroup mg on (p.messagegroupid = mg.id)
			where p.userid=? and p.action = 'subscribe'	and p.type = 'messagegroup'	and not mg.deleted)
			order by digitsfirst, name";
if ($selectmessages = QuickQueryList($query,true,false,array($USER->id, $USER->id))) {
	foreach ($selectmessages as $id => $name) {
		$messages[$id] = $name;
	} 
}

// Add the selected message to the list if it happens to be deleted 
if ($job->messagegroupid != null) {
	$query = "select id, name from messagegroup where id = ? and deleted = 1";
	if ($deletedmessage = QuickQueryRow($query, false, false,array($job->messagegroupid))) {
		$messages[$deletedmessage[0]] = $deletedmessage[1];
	}
}

// get the facebook pages this job is using (if any)
$fbpages = array();
if ($job)
	$fbpages = QuickQueryList("select destination from jobpost where type = 'facebook' and jobid = ?", false, false, array($job->id));

$helpsteps = array();
$helpstepnum = 1;
$formdata = array();

$formdata[] = _L('Job');

$helpsteps[] = _L("Enter a name for your job. " .
					"Using a descriptive name that indicates the message content will make it easier to find the job later. " .
					"You may also optionally enter a description of the the job.");
$formdata["name"] = array(
	"label" => _L('Name'),
	"fieldhelp" => _L('Enter a name for your job.'),
	"value" => isset($job->name)?$job->name:"",
	"validators" => array(
		array("ValRequired"),
		array("ValDuplicateNameCheck","type" => "job"),
		array("ValLength","max" => ($JOBTYPE == "repeating")?30:50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => $helpstepnum
);
$formdata["description"] = array(
	"label" => _L('Description'),
	"fieldhelp" => _L('Enter a description of the job. This is optional, but can help identify the job later.'),
	"value" => isset($job->description)?$job->description:"",
	"validators" => array(
		array("ValLength","min" => 0,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => $helpstepnum
);


$helpsteps[] = _L("Select the option that best describes the type of notification you are sending. ".
					"The category you select will determine which introduction your recipients will hear.");
if ($submittedmode || $completedmode) {
	$formdata["jobtype"] = array(
		"label" => _L("Type/Category"),
		"fieldhelp" => _L("The option that best describes the type of notification you are sending."),
		"control" => array("FormHtml","html" => escapehtml($jobtypes[$job->jobtypeid])),
		"helpstep" => ++$helpstepnum
	);
} else {
	$formdata["jobtype"] = array(
		"label" => _L("Type/Category"),
		"fieldhelp" => _L("Select the option that best describes the type of notification you are sending. ".
							"The category you select will determine which introduction your recipients will hear."),
		"value" => isset($job->jobtypeid)?$job->jobtypeid:"",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($jobtypes))
		),
		"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
		"helpstep" => ++$helpstepnum
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
			if (in_array($x,$data))
				$scheduledows[$x-1] = true;
		}
	}
	$repeatvalues = array();
	for ($x = 0; $x < 7; $x++) {
		$repeatvalues[] = isset($scheduledows[$x]);
	}
	$repeatvalues[7] = date("g:i a", strtotime($schedule->time));

	$timevalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());

	$helpsteps[] = _L("The options in this section create a delivery window for your job. ".
						"It's important that you leave enough time for the system to contact everyone in your list. ".
						"The options are:".
							"<ul>".
								"<li>Repeat - This is the day(s) of the week and the time of day the job will start running.".
								"<li>Days to Run - The number of days the job should run within the times you select.".
								"<li>Start Time and End Time - These represent the time the job should start and stop.".
							"</ul>");
	$formdata["repeat"] = array(
		"label" => _L("Repeat"),
		"fieldhelp" => _L("Select which days this job should run."),
		"value" => $repeatvalues,
		"validators" => array(
			array("ValRequired"),
			array("ValWeekRepeatItem")
		),
		"control" => array(
			"WeekRepeatItem",
			"timevalues" => $timevalues
			),
		"helpstep" => ++$helpstepnum
	);
} else {
	$helpsteps[] = _L("The options in this section create a delivery window for your job. ".
						"It's important that you leave enough time for the system to contact everyone in your list. ".
						"The options are:".
							"<ul>".
								"<li>Start Date - This is the day the job will start running.".
								"<li>Days to Run - The number of days the job should run within the times you select.".
								"<li>Start Time and End Time - These represent the time the job should start and stop.".
							"</ul>");
	if ($completedmode) {
		$formdata["date"] = array(
			"label" => _L("Start Date"),
			"fieldhelp" => _L("Notification will begin on the selected date."),
			"control" => array("FormHtml","html" => date("m/d/Y", strtotime($job->startdate))),
			"helpstep" => ++$helpstepnum
		);
	} else {
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
			"helpstep" => ++$helpstepnum
		);
		if (!$submittedmode)
			$formdata["date"]["requires"] = array("message");
	}
}

if ($completedmode) {
	$formdata["days"] = array(
		"label" => _L("Days to Run"),
		"fieldhelp" => _L("Select the number of days this job should run."),
		"control" => array("FormHtml","html" => (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400),
		"helpstep" => $helpstepnum
	);
	$formdata["callearly"] = array(
		"label" => _L("Start Time"),
		"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
		"control" => array("FormHtml","html" => date("g:i a", strtotime($job->starttime))),
		"helpstep" => $helpstepnum
	);
	$formdata["calllate"] = array(
		"label" => _L("End Time"),
		"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
		"control" => array("FormHtml","html" => date("g:i a", strtotime($job->endtime))),
		"helpstep" => $helpstepnum
	);
} else {
	// Prepare the the "Number of Days to run" data
	$maxdays = first($ACCESS->getValue('maxjobdays'), 7);
	$numdays = array_combine(range(1,$maxdays),range(1,$maxdays));
	$formdata["days"] = array(
		"label" => _L("Days to Run"),
		"fieldhelp" => _L("Select the number of days this job should run."),
		"value" => (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400,
		"validators" => array(
			array("ValRequired"),
			array("ValDate", "min" => 1, "max" => ($ACCESS->getValue('maxjobdays') != null ? $ACCESS->getValue('maxjobdays') : "7"))
		),
		"control" => array("SelectMenu", "values" => $numdays),
		"helpstep" => $helpstepnum
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
		"helpstep" => $helpstepnum
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
		"helpstep" => $helpstepnum
	);

	if ($JOBTYPE != "repeating") {// is only required for non repeating jobs
		$formdata["calllate"]["requires"][] = "date";
	}
}

$helpsteps[] = _L("Select an existing list to use. If you do not see the list you need, ".
					"you can make one by clicking the Lists subtab above. <br><br> ".
					"You may also opt to skip duplicates. Skip Duplicates is for calling ".
					"each number once, so if, for example, two recipients have the same ".
					"number, they will only be called once.");
$helpsteps[] = _L("Select an existing message to use. If you do not see the message ".
					"you need, you can make a new message by clicking the Messages subtab above.");

if ($submittedmode || $completedmode) {
	$formdata[] = _L('List(s)');
	$query = "select name from list where id in (" . repeatWithSeparator("?", ",", count($selectedlists)) . ")";
	$listhtml = implode("<br/>",QuickQueryList($query,false,false,$selectedlists));
	$formdata["lists"] = array(
		"label" => _L('Lists'),
		"fieldhelp" => _L('Select a list from your existing lists.'),
		"control" => array("FormHtml","html" => $listhtml),
		"helpstep" => ++$helpstepnum
	);
	$formdata["skipduplicates"] = array(
		"label" => _L('Skip Duplicates'),
		"fieldhelp" => _L('Skip Duplicates if you would like to only contact recipients who share contact information once.'),
		"control" => array(
			"FormHtml",
			"html" => "<input type='checkbox' " . ($job->isOption("skipduplicates")?"checked":"") . " disabled />"),
		"helpstep" => $helpstepnum
	);
	$formdata[] = _L('Message');
	$formdata["message"] = array(
		"label" => _L('Message'),
		"fieldhelp" => _L('Select an existing message to use from the menu.'),
		"value" => (((isset($job->messagegroupid) && $job->messagegroupid))?$job->messagegroupid:""),
		"validators" => array(),
		"control" => array("MessageGroupSelectMenu", "values" => $messages, "static" => true),
		"helpstep" => ++$helpstepnum
	);

	// readonly facebook page list
	if ($fbpages) {
		$formdata[] = _L('Social Media Options');
		$helpsteps[] = _L("TODO: readonly facbook page list");
		$formdata["fbpages"] = array(
			"label" => _L('Facbook Page(s)'),
			"fieldhelp" => _L(''),
			"value" => json_encode($fbpages),
			"validators" => array(),
			"control" => array("ReadOnlyFacebookPage"),
			"helpstep" => ++$helpstepnum
		);
	}
	$helpsteps[] = _L("<ul><li>Auto Report - Selecting this option causes the system to email ".
					"a report to the email address associated with your account when the job ".
					"is finished.<li>Max Attempts - This option lets you select the maximum ".
					"number of times the system should try to contact a recipient. ".
					"<li>Allow Reply - Check this if you want recipients to be able to ".
					"record responses.<br><br><b>Note:</b>You will need to include instructions ".
					"to press '0' to record a response in your message.<br><br> ".
					"<li>Allow Confirmation - Select this option if you would like recipients ".
					"to give a 'yes' or 'no' response to your message.<br><br> ".
					"<b>Note:</b>You will need to include instructions ".
					"to press '1' for 'yes' and '2' for 'no' in your message.</ul>");
	
	$formdata[] = _L('Advanced Options ');
	$formdata["report"] = array(
		"label" => _L('Auto Report'),
		"fieldhelp" => _L("Select this option if you would like the system to email you when the job has finished running."),
		"control" => array(
			"FormHtml",
			"html" => "<input type='checkbox' " . ($job->isOption("sendreport")?"checked":"") . " disabled />"),
		"helpstep" => ++$helpstepnum
	);

	if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
		$formdata["callerid"] = array(
			"label" => _L("Personal Caller ID"),
			"fieldhelp" => ("This features allows you to override the number that will display on recipient's Caller IDs."),
			"control" => array("FormHtml","html" => Phone::format($job->getSetting("callerid",getDefaultCallerID()))),
			"helpstep" => $helpstepnum
		);
	}

	// Prepare attempt data
	$maxattempts = first($ACCESS->getValue('callmax'), 1);
	$attempts = array_combine(range(1,$maxattempts),range(1,$maxattempts));

	$formdata["attempts"] = array(
		"label" => _L('Max Attempts'),
		"fieldhelp" => _L("Select the maximum number of times the system should try to contact an individual."),
		"control" => array("FormHtml","html" => $job->getOptionValue("maxcallattempts")),
		"helpstep" => $helpstepnum
	);
	if ($USER->authorize('leavemessage')) {
		$formdata["replyoption"] = array(
			"label" => _L('Allow Reply'),
			"fieldhelp" => _L("Select this option if recipients should be able to record replies. ".
								"Make sure that the message instructs recipients to press '0' to record a response."),
			"control" => array(
				"FormHtml",
				"html" => "<input type='checkbox' " . ($job->isOption("leavemessage")?"checked":"") . " disabled />"),
			"helpstep" => $helpstepnum
		);
	}
	if ($USER->authorize('messageconfirmation')) { 
		$formdata["confirmoption"] = array(
			"label" => _L('Allow Confirmation'),
			"fieldhelp" => _L("Select this option if you would like recipients to be able to respond to your message ".
								"by pressing 1' for 'yes' or '2' for 'no'. ".
								"You will need to instruct recipients to do this in your message."),
			"control" => array(
				"FormHtml",
				"html" => "<input type='checkbox' " . ($job->isOption("messageconfirmation")?"checked":"") . " disabled />"),
			"helpstep" => $helpstepnum
		);
	}
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
		"helpstep" => ++$helpstepnum
	);
	$formdata["skipduplicates"] = array(
		"label" => _L('Skip Duplicates'),
		"fieldhelp" => ("Skip Duplicates if you would like to only contact recipients who share contact information once."),
		"value" => $job->isOption("skipduplicates"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
	);
	$formdata[] = _L('Message');
	$messagevalidators = array(
			array("ValRequired"),
			array("ValInArray","values"=>array_keys($messages)),
			array("ValMessageGroup")
			);
	if ($JOBTYPE == "repeating") {
		$messagevalidators[] = array("ValIsTranslated");
	}
	$formdata["message"] = array(
		"label" => _L('Message'),
		"fieldhelp" => _L('Select a message from your existing messages.'),
		"value" => (((isset($job->messagegroupid) && $job->messagegroupid))?$job->messagegroupid:""),
		"validators" => $messagevalidators,
		"control" => array("MessageGroupSelectMenu", "values" => $messages),
		"helpstep" => ++$helpstepnum
	);

	if ($JOBTYPE != "repeating") {
		$formdata["message"]["requires"] = array("date");
	}

	// Social Media options
	if ((getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) || 
			(getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost'))) {
		$formdata[] = _L('Social Media Options');
	}
	
	// if the account may post to facebook. show facebook page selection formitem
	if (getSystemSetting("_hasfacebook") && $USER->authorize("facebookpost")) {
		$helpsteps[] = _L("TODO: facebook page selection");
		$formdata["fbpage"] = array(
			"label" => _L('Facebook Page(s)'),
			"fieldhelp" => _L("Select which pages to post to."),
			"value" => ($fbpages?json_encode($fbpages):""),
			"validators" => array(
				array("ValFacebookPageWithMessage", "authpages" => getFbAuthorizedPages(), "authwall" => getSystemSetting("fbauthorizewall"))),
			"control" => array("FacebookPage", "access_token" => $USER->getSetting("fb_access_token", false)),
			"requires" => array("message"),
			"helpstep" => ++$helpstepnum);
		if (!$job->messagegroupid)
			$formdata["fbpage"]["validators"][] = array("ValRequired");
			
	}
	
	// if the user account may post to twitter, but has no valid twitter access token
	if (getSystemSetting("_hastwitter") && $USER->authorize("twitterpost")) {
		// get this here so twitter failures only effect users with twitter access
		$tw = new Twitter($USER->getSetting("tw_access_token"));
		$helpsteps[] = _L("TODO: twitter connection required if message group has twitter message");
		$formdata["twitter"] = array(
			"label" => _L('Twitter Authorization'),
			"fieldhelp" => _L("TODO: twitter connection required if message group has twitter message"),
			"value" => "",
			"validators" => array(
				array("ValTwitterAccountWithMessage")),
			"control" => array("TwitterAccountPopup", "hasvalidtoken" => $tw->hasValidAccessToken()),
			"requires" => array("message"),
			"helpstep" => ++$helpstepnum);
		if (!$tw->hasValidAccessToken() && !$job->messagegroupid)
			$formdata["twitter"]['validators'][] = array("ValRequired");
	}
	
	$helpsteps[] = _L("<ul><li>Auto Report - Selecting this option causes the system to email ".
					"a report to the email address associated with your account when the job ".
					"is finished.<li>Max Attempts - This option lets you select the maximum ".
					"number of times the system should try to contact a recipient. ".
					"<li>Allow Reply - Check this if you want recipients to be able to ".
					"record responses.<br><br><b>Note:</b>You will need to include instructions ".
					"to press '0' to record a response in your message.<br><br> ".
					"<li>Allow Confirmation - Select this option if you would like recipients ".
					"to give a 'yes' or 'no' response to your message.<br><br> ".
					"<b>Note:</b>You will need to include instructions ".
					"to press '1' for 'yes' and '2' for 'no' in your message.</ul>");
	$formdata[] = _L('Advanced Options ');
	$formdata["report"] = array(
		"label" => _L('Auto Report'),
		"fieldhelp" => _L("Select this option if you would like the system to email you when the job has finished running."),
		"value" => $job->isOption("sendreport"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => ++$helpstepnum
	);

	if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
		$formdata["callerid"] = array(
			"label" => _L("Personal Caller ID"),
			"fieldhelp" => _L("This features allows you to override the number that will display on recipient's Caller IDs."),
			"value" => Phone::format($job->getSetting("callerid",getDefaultCallerID())),
			"validators" => array(
				array("ValLength","min" => 3,"max" => 20),
				array("ValPhone")
			),
			"control" => array("TextField","maxlength" => 20, "size" => 15),
			"helpstep" => $helpstepnum
		);
	}
	
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
		"helpstep" => $helpstepnum
	);
	if ($USER->authorize('leavemessage')) { 
		$formdata["replyoption"] = array(
			"label" => _L('Allow Reply'),
			"fieldhelp" => _L("Select this option if recipients should be able to record replies. ".
								"Make sure that the message instructs recipients to press '0' to record a response."),
			"value" => $job->isOption("leavemessage"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
	}
	if ($USER->authorize('messageconfirmation')) { 
		$formdata["confirmoption"] = array(
			"label" => _L('Allow Confirmation'),
			"fieldhelp" => _L("Select this option if you would like recipients to be able to respond to your message ".
								"by pressing 1' for 'yes' or '2' for 'no'. You will need to instruct recipients to do ".
								"this in your message."),
			"value" => $job->isOption("messageconfirmation"),
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
	}
}



$buttons = array(submit_button(_L('Save'),"submit","tick"));
if ($JOBTYPE == "normal" && !$submittedmode) {
	$buttons[] = submit_button(_L('Proceed To Confirmation'),"send","arrow_right");
} 
$buttons[] = icon_button(_L('Cancel'),"cross",null,(isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')?"start.php":"jobs.php"));


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
		
		if ($completedmode) {
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
					if ($repeatdata[$x] === true) {
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

			if ($submittedmode) {
				$job->update();
			} else {
				$job->jobtypeid = $postdata['jobtype'];
				$job->userid = $USER->id;

				$job->setOption("skipduplicates",$postdata['skipduplicates']?1:0);
				$job->setOption("skipemailduplicates",$postdata['skipduplicates']?1:0);

				$messagegroup = new MessageGroup($postdata['message']);
				if ($JOBTYPE != "repeating")
					$messagegroup->reTranslate();
				
				$job->messagegroupid = $messagegroup->id;

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
					// clear existing jobpost entries
					QuickUpdate("DELETE FROM jobpost WHERE jobid=?",false,array($job->id));
					// only create a page post if facebook or twitter and there is a post page or voice
					$createpagepost = false;
					if (((getSystemSetting("_hasfacebook") && $USER->authorize("facebookpost")) || (getSystemSetting("_hastwitter") && $USER->authorize("twitterpost"))) && 
								($messagegroup->hasMessage("post", "page") || $messagegroup->hasMessage("post", "voice"))) {
						QuickUpdate("insert into jobpost (jobid, type, destination) values (?,'page','')", false, array($job->id));
					}
					// insert facebook pages, (if the user can and the message group has a facebook message)
					if (getSystemSetting("_hasfacebook") && $USER->authorize("facebookpost") && $messagegroup->hasMessage("post", "facebook")) {
						$batchsql = "";
						$batchargs = array();
						foreach (json_decode($postdata['fbpage']) as $fbpageid) {
							$batchsql .= "(?,'facebook',?),";
							if ($fbpageid == "me")
								$fbpageid = $USER->getSetting("fb_user_id");
							$batchargs[] = $job->id;
							$batchargs[] = $fbpageid;
						}
						QuickUpdate("insert into jobpost (jobid, type, destination) values ". trim($batchsql,","), false, $batchargs);
					}
					// insert facebook pages, (if the user can and the message group has a facebook message)
					if (getSystemSetting("_hastwitter") && $USER->authorize("twitterpost") && $messagegroup->hasMessage("post", "twitter")) {
						// get the twitter user's id
						$twdata = json_decode($USER->getSetting("tw_access_token"));
						QuickUpdate("insert into jobpost (jobid, type, destination) values (?,'twitter',?)", false, array($job->id, $twdata->user_id));
					}
				}
			}
		}
		Query("COMMIT");

		if ($button=="send") {
			$_SESSION['jobid'] = $job->id;
			$sendto = "jobconfirm.php";
		} else {
			if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
				unset($_SESSION['origin']);
				$sendto = 'start.php';
			} else {
				$sendto = 'jobs.php';
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
$PAGE = "notifications:jobs";

$TITLE = ($JOBTYPE == 'repeating' ? _L('Repeating Job Editor: ') : _L('Job Editor: '));
$TITLE .= ($jobid == NULL ? _L("New Job") : escapehtml($job->name));

include_once("nav.inc.php");

// Load Custom Form Validators
?>
<script type="text/javascript">
<? 
Validator::load_validators(array("ValDuplicateNameCheck",
								"ValTranslationExpirationDate",
								"ValWeekRepeatItem",
								"ValTimeWindowCallEarly",
								"ValTimeWindowCallLate",
								"ValFormListSelect",
								"ValIsTranslated",
								"ValMessageGroup",
								"ValFacebookPageWithMessage",
								"ValTwitterAccountWithMessage"));
?>
</script>
<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
<script src="script/livepipe/window.js" type="text/javascript"></script>
<script src="script/modalwrapper.js" type="text/javascript"></script>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<?
PreviewModal::includePreviewScript();

startWindow(_L('Job Information'));
if ($JOBTYPE == "repeating" && getSystemSetting("disablerepeat") ) {
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align="center">
			<div class='alertmessage noprint'>
				The System Administrator has disabled all Repeating Jobs. <br>
				No Repeating Jobs can be run while this setting remains in effect.
			</div></td>
	</tr>
</table>
<?
}

echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
