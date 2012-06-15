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
require_once("obj/Phone.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/MessageGroupSelectMenu.fi.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValMessageGroup.val.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FormListSelect.fi.php");
require_once("inc/date.inc.php");
require_once("obj/ValListSelection.val.php");

// Includes that are required for preview to work
require_once("obj/Language.obj.php");
require_once("inc/previewfields.inc.php");
require_once("inc/appserver.inc.php");
require_once("obj/PreviewModal.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
$cansendjob = isset($USER) && (($USER->authorize('sendphone') || $USER->authorize('sendemail') || $USER->authorize('sendsms')));
if (!$cansendjob)
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

$jobid = isset($_SESSION['jobid'])?$_SESSION['jobid']:null;
if ($jobid == NULL) {
	$job = Job::jobWithDefaults();
} else {
	$job = new Job($jobid);
	
	if ($job->type != "notification" && $job->status != 'template') 
		redirect('unauthorized.php');
		
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


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

$helpsteps = array();
$helpstepnum = 1;
$formdata = array();

$helpsteps[] = _L("Enter a name for your template. " .
					"Using a descriptive name that indicates the message content will make it easier to find the template later. " .
					"You may also optionally enter a description of the template.");
$formdata["name"] = array(
	"label" => _L('Name'),
	"fieldhelp" => _L('Enter a name for your % template.', getJobTitle()),
	"value" => isset($job->name)?$job->name:"",
	"validators" => array(
		array("ValRequired"),
		array("ValDuplicateNameCheck","type" => "jobtemplate"),
		array("ValLength","max" => 30)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => $helpstepnum
);
$formdata["description"] = array(
	"label" => _L('Description'),
	"fieldhelp" => _L('Enter a description of the % template. This is optional, but can help identify the %s later.', getJobTitle(), getJobTitle()),
	"value" => isset($job->description)?$job->description:"",
	"validators" => array(
		array("ValLength","min" => 0,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => $helpstepnum
);


$helpsteps[] = _L("Select the option that best describes the type of notification you are sending. ".
					"The category you select will determine which introduction your recipients will hear.");
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



$helpsteps[] = _L("Select an existing list to use. If you do not see the list you need, ".
					"you can make one by clicking the Lists subtab above.");
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

$helpsteps[] = _L("Select an existing message to use. If you do not see the message ".
					"you need, you can make a new message by clicking the Messages subtab above.");
$formdata[] = _L('Message');
$formdata["message"] = array(
	"label" => _L('Message'),
	"fieldhelp" => _L('Select a message from your existing messages.'),
	"value" => (((isset($job->messagegroupid) && $job->messagegroupid))?$job->messagegroupid:""),
	"validators" => array(
		array("ValRequired"),
		array("ValInArray","values"=>array_keys($messages)),
		array("ValMessageGroup")
	),
	"control" => array("MessageGroupSelectMenu", "values" => $messages,"jobtypeidtarget" => "jobtype"),
	"helpstep" => ++$helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"));
$buttons[] = icon_button(_L('Cancel'),"cross",null,(isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')?"start.php":"jobtemplates.php"));


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
		
		$job->jobtypeid = $postdata['jobtype'];
		$job->userid = $USER->id;
		
		$messagegroup = new MessageGroup($postdata['message']);
		$job->messagegroupid = $messagegroup->id;
		
		if ($job->id) {
			$job->update();
		} else {
			$job->status = "template";
			$job->setSetting("displayondashboard", 1);
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
		Query("COMMIT");
		
		if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
			unset($_SESSION['origin']);
			$sendto = 'start.php';
		} else {
			$sendto = 'jobtemplates.php';
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
$PAGE = "notifications:templates";

$TITLE = _L('Template Editor: ');
$TITLE .= ($jobid == NULL ? _L("New Template") : escapehtml($job->name));

include_once("nav.inc.php");

// Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck", "ValFormListSelect","ValMessageGroup"));?>
</script>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<?
PreviewModal::includePreviewScript();

startWindow(_L('Template Editor'));

echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
