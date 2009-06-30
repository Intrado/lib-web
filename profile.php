<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");
require_once("obj/FieldMap.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageprofile')) {
	redirect('unauthorized.php');
}

/*CSDELETEMARKER_START*/
if(isset($_GET['id'])){
	if(QuickQuery("select count(*) from access where name = 'SchoolMessenger Admin' and id = ?",false,array($_GET['id']))){
		redirect('unauthorized.php');
	}
}
/*CSDELETEMARKER_END*/


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$_SESSION['editaccessid'] = $_GET['id'] == "new" ? null : ($_GET['id'] + 0);
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Controls And Validators
////////////////////////////////////////////////////////////////////////////////

		//TODO add callearly calllate logic check

class ValJobWindowTime extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args, $requiredvalues) {
		$value = strtotime($value);
		$value2 = strtotime($requiredvalues[$args['field']]);
		
		//only check if both times parse
		if ($value != -1 && $value !== false && $value2 != -1 && $value2 !== false) {
			if ($args['op'] == "later" && $value <= $value2)
				return _L('%1$s must be later than %2$s',$this->label, $args['fieldlabel']);
			if ($args['op'] == "earlier" && $value >= $value2)
				return _L('%1$s must be earlier than %2$s',$this->label, $args['fieldlabel']);
		}
		
		return true;
	}
}

class ValDupeProfileName extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		$query = "select count(*) from access where name = ? and id != ?";
		$res = QuickQuery($query,false,array($value,$args['accessid']+0));
		if ($res)
			return _L('An access profile with that name already exists. Please choose another');
		
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$obj = new Access($_SESSION['editaccessid']);

$calltimes = newform_time_select(); //gets an array of times
$calltimes = array_combine($calltimes,$calltimes); //makes assoc array of times to labels (needed by SelectMenu)
$calltimes = array_merge(array("" => _L("No Restriction")),$calltimes); //prepend an item for no restrictions

$FIELDMAP = array_merge(FieldMap::getMapNamesLike('f'), FieldMap::getMapNamesLike('g'), FieldMap::getMapNamesLike('c'));

$datafields = $obj->getValue('datafields') ? explode('|',$obj->getValue('datafields')) : array();

$blockednumberoptions = array (
	"none" => _L("No Access"),
	"viewonly" => _L("View Numbers Only"),
	"addonly" => _L("Add/Delete Own Numbers"),
	"editall" => _L("Add/Delete All Numbners")
);

$formdata = array(
	"name" => array(
		"label" => _L('Name'),
		"fieldhelp" => _L('The name for this access profile.'),
		"value" => $obj->name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 50),
			array("ValDupeProfileName","accessid" => $_SESSION['editaccessid'])
		),
		"control" => array("TextField", "size" => "20", "maxsize" => 50),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"fieldhelp" => _L('The description of this access profile.'),
		"value" => $obj->description,
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField", "size" => "30", "maxsize" => 50),
		"helpstep" => 1
	),
	"enableuseroptions" => array (
		"label" => _L('Enable All'),
		"control" => array("FormHtml", "html" => icon_button(_L('Enable All User-level Options'),"group",'checkAllCheckboxes(false);')),
		"helpstep" => 1
	),
_L('Login Options'),
	"loginweb" => array(
		"label" => _L('Log in via web'),
		"fieldhelp" => _L('Allows basic access to this website.'),
		"value" => $obj->getValue("loginweb"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 2
	),
	"loginphone" => array(
		"label" => _L('Log in via phone'),
		"fieldhelp" => _L('Allows users to call in to the inbound number using their access code and PIN.'),
		"value" => $obj->getValue("loginphone"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 2
	),
	"managemyaccount" => array(
		"label" => _L('Edit Personal Account'),
		"fieldhelp" => _L('Allows a user to change their personal information, including username and email.'),
		"value" => $obj->getValue("managemyaccount"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 2
	),
	
_L('Start Page & Nav Options'),
	"startstats" => array(
		"label" => _L('View Job Statistics'),
		"fieldhelp" => _L('Shows statistics of current jobs on the start page.'),
		"value" => $obj->getValue("startstats"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 3
	),
	"startshort" => array(
		"label" => _L('View Shortcuts'),
		"fieldhelp" => _L('Shows the shortcuts navigation element'),
		"value" => $obj->getValue("startshort"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 3
	),	
	"starteasy" => array(
		"label" => _L('Outbound Recording'),
		"fieldhelp" => _L('Allows users to call any phone number and record audio messages.'),
		"value" => $obj->getValue("starteasy"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 3
	),

_L('Messaging Options'),
	"sendphone" => array(
		"label" => _L('Send Phone Calls'),
		"fieldhelp" => _L('Allows users to send jobs via phone calls and manage related messages.'),
		"value" => $obj->getValue("sendphone"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"callearly" => array(
		"label" => _L('Don\'t Call Before'),
		"fieldhelp" => _L('Restricts the earliest time that a user may schedule a job.'),
		"value" => $obj->getValue("callearly"),
		"requires" => array("calllate"),
		"validators" => array(
			array("ValInArray","values" => array_keys($calltimes)),
			array("ValJobWindowTime","field" => "calllate", "fieldlabel" => _L('Don\'t Call After'), "op" => "earlier")
		),
		"control" => array("SelectMenu", "values" => $calltimes),
		"helpstep" => 4
	),
	"calllate" => array(
		"label" => _L('Don\'t Call After'),
		"fieldhelp" => _L('Restricts the latest time that a user may schedule a job.'),
		"value" => $obj->getValue("calllate"),
		"requires" => array("callearly"),
		"validators" => array(
			array("ValInArray","values" => array_keys($calltimes)),
			array("ValJobWindowTime","field" => "callearly", "fieldlabel" => _L('Don\'t Call Before'), "op" => "later")
		),
		"control" => array("SelectMenu", "values" => $calltimes),
		"helpstep" => 4
	),
	"callmax" => array(
		"label" => _L('Max Call Attempts'),
		"fieldhelp" => _L('Restricts the maximum number of call attempts a user may set for a job.'),
		"value" => $obj->getValue("callmax") ? $obj->getValue("callmax") : 5,
		"validators" => array(
			array("ValInArray","values" => range(1,14))
		),
		"control" => array("SelectMenu", "values" => array_combine(range(1,14),range(1,14))),
		"helpstep" => 4
	),
	"leavemessage" => array(
		"label" => _L('Voice Responses'),
		"fieldhelp" => _L('Allows users to accept voice Responses from contacts in reply to a job.'),
		"value" => $obj->getValue("leavemessage"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"messageconfirmation" => array(
		"label" => _L('Message Confirmations'),
		"fieldhelp" => _L('Allows users to request confirmation over the phone.'),
		"value" => $obj->getValue("messageconfirmation"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"sendemail" => array(
		"label" => _L('Send Emails'),
		"fieldhelp" => _L('Allows users to send jobs via email and manage related messages.'),
		"value" => $obj->getValue("sendemail"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"sendsms" => array(
		"label" => _L('Send SMS txt messages'),
		"fieldhelp" => _L('Allows users to send jobs via cell phone SMS txt and manage related messages.'),
		"value" => $obj->getValue("sendsms"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"sendmulti" => array(
		"label" => _L('Multi-language Messages'),
		"fieldhelp" => _L('Allows users to specify additional alternate languages for a message.'),
		"value" => $obj->getValue("sendmulti"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	
_L('Advanced Job Options'),
	"createrepeat" => array(
		"label" => _L('Create Repeating Jobs'),
		"fieldhelp" => _L('Allows users to schedule regularly occuring jobs.'),
		"value" => $obj->getValue("createrepeat"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"survey" => array(
		"label" => _L('Create Surveys'),
		"fieldhelp" => _L('Allows users to send multi-question surveys and collect responses via phone and email/web.'),
		"value" => $obj->getValue("survey"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"setcallerid" => array(
		"label" => _L('Override CallerID'),
		"fieldhelp" => _L('Allows users to override CallerID on a job to be any phone number.'),
		"value" => $obj->getValue("setcallerid"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"maxjobdays" => array(
		"label" => _L('Max Job Run Days'),
		"fieldhelp" => _L('Restricts the maximum number of days a user can schedule a job to run.'),
		"value" => $obj->getValue("maxjobdays") ? $obj->getValue("maxjobdays") : 2,
		"validators" => array(
			array("ValInArray","values" => range(1,7))
		),
		"control" => array("SelectMenu", "values" => array_combine(range(1,7),range(1,7))),
		"helpstep" => 5
	),

	//repeating
	//survey
	//callerid
	//days to run	

_L('List Options'),
	"createlist" => array(
		"label" => _L('Create & Edit Lists'),
		"fieldhelp" => _L('Allows users to create and edit lists used in jobs.'),
		"value" => $obj->getValue("createlist"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 6
	),
	"listuploadids" => array(
		"label" => _L('Upload Lists by ID#'),
		"fieldhelp" => _L('Allows users to upload a file containing ID numbers to a list. Only people they would otherwise normally have access to see are added.'),
		"value" => $obj->getValue("listuploadids"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 6
	),
	"listuploadcontacts" => array(
		"label" => _L('Upload List by Contact Data'),
		"fieldhelp" => _L('Allows users to upload a file containing arbitrary contact data.'),
		"value" => $obj->getValue("listuploadcontacts"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 6
	),
	
_L('Contact & Field Options'),
	"datafields" => array(
		"label" => _L('Allowed Fields'),
		"fieldhelp" => _L('Restricts the fields that are visible to the user, and which fields may be used to create lists. Leave all fields unchecked for unlimited access.'),
		"value" => $datafields,
		"validators" => array(
			array("ValInArray","values" => array_keys($FIELDMAP))
		),
		"control" => array("MultiCheckBox", "values" => $FIELDMAP), //TODO write a control similar to what was used on old form
		"helpstep" => 7
	),
	"viewcontacts" => array(
		"label" => _L('View Contacts'),
		"fieldhelp" => _L('Shows visible contacts in the system on the Contacts Tab'),
		"value" => $obj->getValue("viewcontacts"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 7
	),	
	"managecontactdetailsettings" => array(
		"label" => _L('Edit Contact Details'),
		"fieldhelp" => _L('Allows users to modify contact details such as phone numbers and email addresses.'),
		"value" => $obj->getValue("managecontactdetailsettings"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 7
	),
	"portalaccess" => array(
		"label" => _L('Contact Manager Administration'),
		"fieldhelp" => _L('Allows users to to change settings and options related to the Contact Manager.'),
		"value" => $obj->getValue("portalaccess"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 7
	),
	"generatebulktokens" => array(
		"label" => _L('Generate Bulk Activation Codes'),
		"fieldhelp" => _L('Allows users to general Contact Manager access codes in bulk.'),
		"value" => $obj->getValue("generatebulktokens"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 7
	),
	
	
_L('Report Options'),
	"createreport" => array(
		"label" => _L('Create Reports'),
		"fieldhelp" => _L('Allows users to create, save, and schedule reports.'),
		"value" => $obj->getValue("createreport"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 8
	),

_L('Systemwide View Options'),
	"viewsystemreports" => array(
		"label" => _L('Systemwide Reports'),
		"fieldhelp" => _L('Shows reports for other user\'s jobs.'),
		"value" => $obj->getValue("viewsystemreports"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	"viewusagestats" => array(
		"label" => _L('Usage Stats'),
		"fieldhelp" => _L('Shows systemwide usage stats.'),
		"value" => $obj->getValue("viewusagestats"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	"viewcalldistribution" => array(
		"label" => _L('Call Distribution'),
		"fieldhelp" => _L('Shows systemwide call distribution over time.'),
		"value" => $obj->getValue("viewcalldistribution"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	"viewsystemactive" => array(
		"label" => _L('Active Jobs'),
		"fieldhelp" => _L('Shows active jobs across all users.'),
		"value" => $obj->getValue("viewsystemactive"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	"viewsystemcompleted" => array(
		"label" => _L('Completed Jobs'),
		"fieldhelp" => _L('Shows completed jobs across all users.'),
		"value" => $obj->getValue("viewsystemcompleted"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	"viewsystemrepeating" => array(
		"label" => _L('Repeating Jobs'),
		"fieldhelp" => _L('Shows repeating jobs across all users.'),
		"value" => $obj->getValue("viewsystemrepeating"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	"callblockingperms" => array(
		"label" => _L('Blocked Numbers Access'),
		"fieldhelp" => _L('Controls access to the systemwide blocked numbers list.'),
		"value" => $obj->id ? ($obj->getValue("blocknumbers") ? $obj->getValue("callblockingperms") : "none") : "viewonly",
		"validators" => array(
			array("ValInArray","values" => array_keys($blockednumberoptions))
		),
		"control" => array("RadioButton", "values" => $blockednumberoptions),
		"helpstep" => 9
	),
_L('Security & Administrator Controls'),
	"securitywarning" => array (
		"label" => _L('Security Notice'),
		"control" => array("FormHtml", "html" => '<p style="border: 3px double red; font-weight: bold; width: 50%; padding: 5px;"><img src="img/icons/error.gif" alt="" style="vertical-align: top;">The following settings control top-level administration functions. Only top-level administrators should have these enabled.</p>'),
		"helpstep" => 10
	),
	"enableadminoptions" => array (
		"label" => _L('Enable All'),
		"control" => array("FormHtml", "html" => icon_button(_L('Enable Administrator Options'),"key",'checkAllCheckboxes(true);')),
		"helpstep" => 1
	),
	"manageaccount" => array(
		"label" => _L('Manage Users'),
		"fieldhelp" => _L('Allows users to create and edit other users.<p style="color:red;">Only top-level administrators should have this enabled.</p>'),
		"value" => $obj->getValue("manageaccount"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
	"manageprofile" => array(
		"label" => _L('Manage Profiles'),
		"fieldhelp" => _L('Allows users to create and edit access profiles.<p style="color:red;">Only top-level administrators should have this enabled.</p>'),
		"value" => $obj->getValue("manageprofile"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
	"managesystem" => array(
		"label" => _L('Manage System Settings'),
		"fieldhelp" => _L('Allows users to modify systemwide settings.<p style="color:red;">Only top-level administrators should have this enabled.</p>'),
		"value" => $obj->getValue("managesystem"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
	"managesystemjobs" => array(
		"label" => _L('Manage All Jobs'),
		"fieldhelp" => _L('Allows users to cancel, archive, or delete any job sent by any user, or to run any repeating job.<p style="color:red;">Only top-level administrators should have this enabled.</p>'),
		"value" => $obj->getValue("managesystemjobs"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
	"managetasks" => array(
		"label" => _L('Manage Data Imports'),
		"fieldhelp" => _L('Allows users to change the way data is imported into the system.<p style="color:red;">Only top-level administrators should have this enabled.</p>'),
		"value" => $obj->getValue("managetasks"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
	"metadata" => array(
		"label" => _L('Manage Field Definitions'),
		"fieldhelp" => _L('Allows users to add or delete fields used by imports and list rules.<p style="color:red;">Only top-level administrators should have this enabled.</p>'),
		"value" => $obj->getValue("metadata"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
);

//remove any formdata for features that are not enabled

if(!getSystemSetting("_hasportal", false)) {
	unset($formdata['portalaccess']);
	unset($formdata['generatebulktokens']);
}

if (!getSystemSetting("_hassurvey", true)) {
	unset($formdata['survey']);
}

if (!getSystemSetting("_hassms", false)) {
	unset($formdata['sendsms']);
}


$helpsteps = array (
	_L('Name & Desc'),
	_L('Login'),
	_L('Start Page & Nav'),
	_L('Messages'),
	_L('Advanced Job Options'),
	_L('Lists'),
	_L('Contacts & Fields'),
	_L('Reports'),
	_L('Systemwide View'),
	_L('Security & Top-level Access'),
);

$buttons = array(submit_button(_L('Save'),"submit","tick"));
$form = new Form("accessprofile",$formdata,$helpsteps,$buttons);

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
		
		$obj = new Access($_SESSION['editaccessid']);
		$obj->moduserid = $USER->id;
		$obj->modified = date("Y-m-d g:i:s");
		if(!$obj->id)
			$obj->created = date("Y-m-d g:i:s");
		
		$obj->name = $postdata['name'];
		$obj->description = $postdata['description'];
		$obj->update();
		
		
		$allowedfields = $postdata['datafields'];
		$allowedfields = (isset($allowedfields) ? implode('|',$allowedfields) : "");

		$obj->setPermission("loginweb", (bool)$postdata['loginweb']);
		$obj->setPermission("loginphone", (bool)$postdata['loginphone']);
		$obj->setPermission("startstats", (bool)$postdata['startstats']);
		$obj->setPermission("startshort", (bool)$postdata['startshort']);
		$obj->setPermission("starteasy", (bool)$postdata['starteasy']);
		$obj->setPermission("sendphone",(bool)$postdata['sendphone']);
		$obj->setPermission("callearly", $postdata['callearly']);
		$obj->setPermission("calllate", $postdata['calllate']);
		$obj->setPermission("callmax", $postdata['callmax']);
		$obj->setPermission("sendemail", (bool)$postdata['sendemail']);
		$obj->setPermission("sendmulti", (bool)$postdata['sendmulti']);
		$obj->setPermission("createlist", (bool)$postdata['createlist']);
		$obj->setPermission("listuploadids", (bool)$postdata['listuploadids']);
		$obj->setPermission("listuploadcontacts", (bool)$postdata['listuploadcontacts']);
		$obj->setPermission("datafields", $allowedfields);
		$obj->setPermission("createrepeat", (bool)$postdata['createrepeat']);
		$obj->setPermission("setcallerid", (bool)$postdata['setcallerid']);
		$obj->setPermission("maxjobdays", $postdata['maxjobdays']);
		$obj->setPermission("blocknumbers", $postdata['callblockingperms'] != "none");
		$obj->setPermission("callblockingperms", $postdata['callblockingperms']);
		$obj->setPermission("createreport", (bool)$postdata['createreport']);
		$obj->setPermission("viewsystemreports", (bool)$postdata['viewsystemreports']);
		$obj->setPermission("viewusagestats", (bool)$postdata['viewusagestats']);
		$obj->setPermission("viewcalldistribution", (bool)$postdata['viewcalldistribution']);
		$obj->setPermission("managesystemjobs", (bool)$postdata['managesystemjobs']);
		$obj->setPermission("managemyaccount", (bool)$postdata['managemyaccount']);
		$obj->setPermission("manageaccount", (bool)$postdata['manageaccount']);
		$obj->setPermission("manageprofile", (bool)$postdata['manageprofile']);
		$obj->setPermission("managesystem", (bool)$postdata['managesystem']);
		$obj->setPermission("viewcontacts", (bool)$postdata['viewcontacts']);
		$obj->setPermission("managecontactdetailsettings", (bool)$postdata['managecontactdetailsettings']);
		$obj->setPermission("metadata", (bool)$postdata['metadata']);
		$obj->setPermission("managetasks", (bool)$postdata['managetasks']);
		$obj->setPermission("viewsystemactive", (bool)$postdata['viewsystemactive']);
		$obj->setPermission("viewsystemrepeating", (bool)$postdata['viewsystemrepeating']);
		$obj->setPermission("viewsystemcompleted", (bool)$postdata['viewsystemcompleted']);
		$obj->setPermission("leavemessage", (bool)$postdata['leavemessage']);
		$obj->setPermission("messageconfirmation", (bool)$postdata['messageconfirmation']);
		
		if(getSystemSetting("_hasportal", false)) {
			$obj->setPermission("portalaccess", (bool)$postdata['portalaccess']);
			$obj->setPermission("generatebulktokens", (bool)$postdata['generatebulktokens']);
		}

		if (getSystemSetting("_hassurvey", true)) {
			$obj->setPermission("survey", (bool)$postdata['survey']);
		}

		if (getSystemSetting("_hassms", false)) {
			$obj->setPermission("sendsms", (bool)$postdata['sendsms']);
		}

		$_SESSION['editaccessid'] = $obj->id;
				
        //save data here    
        if ($ajax)
            $form->sendTo("profiles.php");
        else
            redirect("profiles.php");
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:profiles";
$TITLE = _L('Edit Access Profile: %1$s', escapehtml($obj->id ? $obj->name : "New Access Profile") );
$DESCRIPTION = escapehtml($obj->description);

include_once("nav.inc.php");

?>

<style>
/* tweak normal form css so we have more room for all these checkbox labels */
.newform .formcontenttable .formtableheader {
	width: 200px;
}
</style>

<script type="text/javascript">
<? Validator::load_validators(array("ValDupeProfileName","ValJobWindowTime")); ?>
</script>

<?

startWindow(_L('Profile Access Controls'));
echo $form->render();
endWindow();

?>
<script>
function checkAllCheckboxes(domanagement){
	
	var managementoptions = "manageaccount,manageprofile,managesystem,managesystemjobs,managetasks,metadata".split(",");
	
	var form = document.forms[0].elements;
	for(var i = 0; i < form.length; i++){
		if(form[i].type == "checkbox"){
			
			//see if it's a management checkbox
			if (managementoptions.some(function(v) {return form[i].name.indexOf(v) != -1})) {
				if (domanagement)
					if (!form[i].checked)
						form[i].click();
			} else {
				if (!domanagement)
					if (!form[i].checked)
						form[i].click();
			}
		}
	}
}
</script>


<?

include_once("navbottom.inc.php");
?>