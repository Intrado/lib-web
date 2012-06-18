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
require_once("obj/RestrictedValues.fi.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageprofile')) {
	redirect('unauthorized.php');
}

if(isset($_GET['id'])){
	if(QuickQuery("select count(*) from access where name = 'SchoolMessenger Admin' and id = ?",false,array($_GET['id']))){
		redirect('unauthorized.php');
	}
}


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

// Remove name and language from restricted fields
unset($FIELDMAP["f01"]);
unset($FIELDMAP["f02"]);
unset($FIELDMAP["f03"]);

$datafields = $obj->getValue('datafields') ? explode('|',$obj->getValue('datafields')) : array();

// remove restrictions on first name,lastname and language if they exist
$datafields = array_diff($datafields, array("f01","f02","f03")); 

$published = $obj->getValue('publish') ? explode('|',$obj->getValue('publish')) : array();
$subscribed = $obj->getValue('subscribe') ? explode('|',$obj->getValue('subscribe')) : array();

$blockednumberoptions = array (
	"none" => _L("No Access"),
	"viewonly" => _L("View Only"),
	"addonly" => _L("Add/Delete Own"),
	"editall" => _L("Add/Delete All")
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
		"fieldhelp" => _L('Allows users to log in to the system via phone using their access code and PIN.'),
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
		"label" => _L('View %s Statistics', getJobTitle()),
		"fieldhelp" => _L('Shows current %s statistics on the start page.', getJobTitle()),
		"value" => $obj->getValue("startstats"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 3
	),
	"startshort" => array(
		"label" => _L('View Shortcuts'),
		"fieldhelp" => _L('Displays the shortcut menu for commonly used features.'),
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
		"fieldhelp" => _L('Allows users to send %s with phone messages and manage related messages.', getJobsTitle()),
		"value" => $obj->getValue("sendphone"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"callearly" => array(
		"label" => _L('Can\'t Schedule Before'),
		"fieldhelp" => _L('Restricts the earliest time that a user may schedule a %s.', getJobTitle()),
		"value" => $obj->getValue("callearly"),
		"requires" => array("calllate"),
		"validators" => array(
			array("ValInArray","values" => array_keys($calltimes)),
			array("ValJobWindowTime","field" => "calllate", "fieldlabel" => _L('Can\'t Schedule After'), "op" => "earlier")
		),
		"control" => array("SelectMenu", "values" => $calltimes),
		"helpstep" => 4
	),
	"calllate" => array(
		"label" => _L('Can\'t Schedule After'),
		"fieldhelp" => _L('Restricts the latest time that a user may schedule a %s.', getJobTitle()),
		"value" => $obj->getValue("calllate"),
		"requires" => array("callearly"),
		"validators" => array(
			array("ValInArray","values" => array_keys($calltimes)),
			array("ValJobWindowTime","field" => "callearly", "fieldlabel" => _L('Can\'t Schedule Before'), "op" => "later")
		),
		"control" => array("SelectMenu", "values" => $calltimes),
		"helpstep" => 4
	),
	"callmax" => array(
		"label" => _L('Max Call Attempts'),
		"fieldhelp" => _L('Restricts the maximum number of call attempts a user may set for a %.', getJobTitle()),
		"value" => $obj->getValue("callmax") ? $obj->getValue("callmax") : 5,
		"validators" => array(
			array("ValNumber","min" => 1, "max" => 14),
			array("ValNumeric")
		),
		"control" => array("SelectMenu", "values" => array_combine(range(1,14),range(1,14))),
		"helpstep" => 4
	),
	"leavemessage" => array(
		"label" => _L('Voice Responses'),
		"fieldhelp" => _L('Allows users to accept voice responses from contacts in reply to their %s.', getJobTitle()),
		"value" => $obj->getValue("leavemessage"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"messageconfirmation" => array(
		"label" => _L('Message Confirmations'),
		"fieldhelp" => _L('Allows users to request confirmation from contacts over the phone.'),
		"value" => $obj->getValue("messageconfirmation"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"sendemail" => array(
		"label" => _L('Send Emails'),
		"fieldhelp" => _L('Allows users to send emails and manage related messages.'),
		"value" => $obj->getValue("sendemail"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"sendsms" => array(
		"label" => _L('Send SMS txt messages'),
		"fieldhelp" => _L('Allows users to send SMS messages and manage related messages.'),
		"value" => $obj->getValue("sendsms"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"sendmulti" => array(
		"label" => _L('Multi-language Messages'),
		"fieldhelp" => _L('Allows users to create %s with more than one language.', getJobsTitle()),
		"value" => $obj->getValue("sendmulti"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
//_L('Classroom Messaging Options'),
	"targetedmessage" => array(
		"label" => _L('Send Classroom Messages'),
		"fieldhelp" => _L('Allows users to select and send from a library of classroom messages'),
		"value" => $obj->getValue("targetedmessage"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"targetedcomment" => array(
		"label" => _L('Add Remark Classroom Message'),
		"fieldhelp" => _L('Allows users to add a remark to the predefined classroom message'),
		"value" => $obj->getValue("targetedcomment"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"facebookpost" => array(
		"label" => _L('Post to Facebook'),
		"fieldhelp" => _L('Allow users to post messages to Facebook pages that they have admin rights for'),
		"value" => $obj->getValue("facebookpost"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"twitterpost" => array(
		"label" => _L('Post to Twitter'),
		"fieldhelp" => _L('Allow users to post messages to their Twitter status'),
		"value" => $obj->getValue("twitterpost"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
	"feedpost" => array(
		"label" => _L('Post to Feed'),
		"fieldhelp" => _L('Allow users to post messages to feed categories they have rights for'),
		"value" => $obj->getValue("feedpost"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 4
	),
_L('Advanced %s Options', getJobTitle()),
	"createrepeat" => array(
		"label" => _L('Create Repeating %s', getJobsTitle()),
		"fieldhelp" => _L('Allows users to schedule regularly occurring %s.', getJobsTitle()),
		"value" => $obj->getValue("createrepeat"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"survey" => array(
		"label" => _L('Create Surveys'),
		"fieldhelp" => _L('Allows users to build surveys and collect responses via phone and email.'),
		"value" => $obj->getValue("survey"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"setcallerid" => array(
		"label" => _L('Override Caller ID'),
		"fieldhelp" => _L('Allows users to override Caller ID on a %s to be any phone number.', getJobTitle()),
		"value" => $obj->getValue("setcallerid"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 5
	),
	"maxjobdays" => array(
		"label" => _L('Max %s Run Days', getJobTitle()),
		"fieldhelp" => _L('Restricts the maximum number of days a user can schedule a %s to run.', getJobTitle()),
		"value" => $obj->getValue("maxjobdays") ? $obj->getValue("maxjobdays") : 2,
		"validators" => array(
			array("ValNumber","min" => 1, "max" => 7),
			array("ValNumeric")
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
		"fieldhelp" => _L('Allows users to create and edit lists used in %s.', getJobsTitle()),
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

_L('Publish/Subscribe Options'),

	"publish" => array(
		"label" => _L('Publish'),
		"fieldhelp" => _L('Allows users to publish objects. These objects can be used by users with the "Subscribe" privilege for that object type.'),
		"value" => $published,
		"validators" => array(),
		"control" => array("RestrictedValues", "values" => array("messagegroup"=>"Messages","list"=>"Lists"), "label" => _L("Allow publishing these types:")),
		"helpstep" => 7
	),
	"subscribe" => array(
		"label" => _L('Subscribe'),
		"fieldhelp" => _L('Allows users to view and subscribe to published objects.'),
		"value" => $subscribed,
		"validators" => array(),
		"control" => array("RestrictedValues", "values" => array("messagegroup"=>"Messages","list"=>"Lists"), "label" => _L("Allow subscribing to these types:")),
		"helpstep" => 7
	),

_L('Contact & Field Options'),
	"datafields" => array(
		"label" => _L('Field Restriction'),
		"fieldhelp" => _L('Restricts the data fields that are visible to the user to create lists or personalized messages. Leave all fields unchecked for unlimited access.'),
		"value" => $datafields,
		"validators" => array(
			array("ValInArray","values" => array_keys($FIELDMAP))
		),
		"control" => array("RestrictedValues", "values" => $FIELDMAP), //TODO write a control similar to what was used on old form
		"helpstep" => 8
	),
	"viewcontacts" => array(
		"label" => _L('View Contacts'),
		"fieldhelp" => _L('Enables the Contacts Tab, where users can browse all of the contacts they are permitted to see.'),
		"value" => $obj->getValue("viewcontacts"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 8
	),
	"managecontactdetailsettings" => array(
		"label" => _L('Edit Contact Details'),
		"fieldhelp" => _L('Allows users to modify contact details such as phone numbers, email addresses, and contact preferences.'),
		"value" => $obj->getValue("managecontactdetailsettings"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 8
	),
	"portalaccess" => array(
		"label" => _L('Contact Manager Administration'),
		"fieldhelp" => _L('Allows users to to change settings and options related to the Contact Manager.'),
		"value" => $obj->getValue("portalaccess"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 8
	),
	"generatebulktokens" => array(
		"label" => _L('Generate Bulk Activation Codes'),
		"fieldhelp" => _L('Allows users to generate Contact Manager activation codes for groups of contacts.'),
		"value" => $obj->getValue("generatebulktokens"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 8
	),


_L('Report Options'),
	"createreport" => array(
		"label" => _L('Create Reports'),
		"fieldhelp" => _L('Allows users to create, save, and schedule reports.'),
		"value" => $obj->getValue("createreport"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 9
	),
	
_L('Monitoring'),
	"monitorevent" => array(
		"label" => _L('Event Monitoring'),
		"fieldhelp" => _L('Allows users to monitor activity'),
		"value" => $obj->getValue("monitorevent"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
	"monitorsystemwideevent" => array(
		"label" => _L('Systemwide Event Monitoring'),
		"fieldhelp" => _L('Allows users to monitor activity by other users in the system'),
		"value" => $obj->getValue("monitorsystemwideevent"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 10
	),
_L('Systemwide View Options'),
	"viewsystemreports" => array(
		"label" => _L('Systemwide Reports'),
		"fieldhelp" => _L('Allows users to view reports for all other user\'s %s.', getJobsTitle()),
		"value" => $obj->getValue("viewsystemreports"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 11
	),
	"viewusagestats" => array(
		"label" => _L('Usage Stats'),
		"fieldhelp" => _L('Shows systemwide usage statistics.'),
		"value" => $obj->getValue("viewusagestats"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 11
	),
	"viewcalldistribution" => array(
		"label" => _L('Call Distribution'),
		"fieldhelp" => _L('Shows systemwide call distribution over time.'),
		"value" => $obj->getValue("viewcalldistribution"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 11
	),
	"viewsystemactive" => array(
		"label" => _L('Active %s', getJobsTitle()),
		"fieldhelp" => _L('Allows users to view active %s for all users.', getJobsTitle()),
		"value" => $obj->getValue("viewsystemactive"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 11
	),
	"viewsystemcompleted" => array(
		"label" => _L('Completed %s', getJobsTitle()),
		"fieldhelp" => _L('Allows users to view completed %s for all users.', getJobsTitle()),
		"value" => $obj->getValue("viewsystemcompleted"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 11
	),
	"viewsystemrepeating" => array(
		"label" => _L('Repeating %s', getJobsTitle()),
		"fieldhelp" => _L('Allows users to view repeating %s for all users.', getJobsTitle()),
		"value" => $obj->getValue("viewsystemrepeating"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 11
	),
	"callblockingperms" => array(
		"label" => _L('Blocked Destination Access'),
		"fieldhelp" => _L('Controls access to the systemwide blocked phone/email list.'),
		"value" => $obj->id ? ($obj->getValue("blocknumbers") ? $obj->getValue("callblockingperms") : "none") : "viewonly",
		"validators" => array(
			array("ValInArray","values" => array_keys($blockednumberoptions))
		),
		"control" => array("RadioButton", "values" => $blockednumberoptions),
		"helpstep" => 11
	),
_L('Security & Administrator Controls'),
	"securitywarning" => array (
		"label" => _L('Security Notice'),
		"control" => array("FormHtml", "html" => '<p style="border: 3px double red; font-weight: bold; width: 50%; padding: 5px;"><img src="img/icons/error.gif" alt="" style="vertical-align: top;">'._L('The following settings control top-level administration functions. Only top-level administrators should have these enabled.').'</p>'),
		"helpstep" => 12
	),
	"enableadminoptions" => array (
		"label" => _L('Enable All'),
		"control" => array("FormHtml", "html" => icon_button(_L('Enable Administrator Options'),"key",'checkAllCheckboxes(true);')),
		"helpstep" => 12
	),
	"manageaccount" => array(
		"label" => _L('Manage Users'),
		"fieldhelp" => _L('Allows users to create and edit other users.').'<p style="color:red;">'._L('Only top-level administrators should have this enabled.').'</p>',
		"value" => $obj->getValue("manageaccount"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	),
	"manageprofile" => array(
		"label" => _L('Manage Profiles'),
		"fieldhelp" => _L('Allows users to create and edit access profiles.').'<p style="color:red;">'._L('Only top-level administrators should have this enabled.').'</p>',
		"value" => $obj->getValue("manageprofile"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	),
	"managesystem" => array(
		"label" => _L('Manage System Settings'),
		"fieldhelp" => _L('Allows users to modify systemwide settings.').'<p style="color:red;">'._L('Only top-level administrators should have this enabled.').'</p>',
		"value" => $obj->getValue("managesystem"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	),
	"managesystemjobs" => array(
		"label" => _L('Manage All %s', getJobsTitle()),
		"fieldhelp" => _L('Allows users to cancel, archive, or delete any %s sent by any user, or to run any repeating %s.', getJobTitle(),getJobTitle()).'<p style="color:red;">'._L('Only top-level administrators should have this enabled.').'</p>',
		"value" => $obj->getValue("managesystemjobs"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	),
	"managetasks" => array(
		"label" => _L('Manage Data Imports'),
		"fieldhelp" => _L('Allows users to change the way data is imported into the system.').'<p style="color:red;">'._L('Only top-level administrators should have this enabled.').'</p>',
		"value" => $obj->getValue("managetasks"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	),
	"metadata" => array(
		"label" => _L('Manage Field Definitions'),
		"fieldhelp" => _L('Allows users to add or delete the data fields to which import data is mapped.').' <p style="color:red;">'._L('Only top-level administrators should have this enabled.').'</p>',
		"value" => $obj->getValue("metadata"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	),
	"manageclassroommessaging" => array(
		"label" => _L('Manage Classroom Data'),
		"fieldhelp" => _L('Allows users to view and edit the classroom messaging template and classroom categories'),
		"value" => $obj->getValue("manageclassroommessaging"),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 12
	)
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

if (!getSystemSetting("_hastargetedmessage", false)) {
	unset($formdata['targetedmessage']);
	unset($formdata['targetedcomment']);
	unset($formdata['manageclassroommessaging']);
}

if (getSystemSetting('_hascallback', false)) {
	unset($formdata['setcallerid']);
}

if (!getSystemSetting('_hasfacebook', false))
	unset($formdata['facebookpost']);

if (!getSystemSetting('_hastwitter', false))
	unset($formdata['twitterpost']);

if (!getSystemSetting('_hasfeed', false))
	unset($formdata['feedpost']);

$helpsteps = array (
	_L('Enter a name and optional description for this Access Profile.'),
	_L('Choose how you want users with this profile to be able to access the system. Then select whether they should be able to edit their own account information or not.'),
	_L('Select whether or not this profile will display current job statistics on the start page.').'<br><br>'._L('You may also choose to display the shortcuts menu, allowing users to quickly access the most common tasks.'),
	_L('Select the combination of messaging options most appropriate for the users of this profile. Click on the individual options for more information about their functions.'),
	_L('Users can send one time jobs by default. This section allows you to enable repeating job and survey creation. You can also limit the number of days a job can run and allow users to change their Caller ID.'),
	_L('This section determines whether the user can create and edit lists as well as the types of lists they can create. Enabling Create & Edit Lists does not allow users to contact people outside of their restrictions.').'<br><br>'._L('Uploading lists by ID number allows users to create lists of ID numbers, referencing contacts that exist in the database.').'<br><br>'._L('Uploading lists by contact data will let users create lists from CSV files containing any contact information, regardless of whether the contact exists in the database. This method allows users to contact anyone they upload to their address book.'),
	_L('Select the object types the user can subscribe to or publish.'),
	_L('Select the fields users should be able to see. They can use these fields for lists, messages, and reports. Leave everything blank to allow unlimited access.').'<br><br>'._L('You may also use this section to allow access to the Contacts Tab and allow users to edit contact details.'),
	_L('Choose whether the user can create reports or not.'),
	_L('Choose whether the user can monitor events like job sent and/or completed'),
	_L('The options in this section control views of all of the activity in the system.'),
	_L('This section contains options for top-level administrative functions such as creating users and managing system settings. You should only enable these features for top-level administrators.')
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

		Query("BEGIN");
			$obj->moduserid = $USER->id;
			$obj->modified = date("Y-m-d g:i:s");
			if(!$obj->id)
				$obj->created = date("Y-m-d g:i:s");

			$obj->name = $postdata['name'];
			$obj->description = $postdata['description'];
			$obj->update();


			$allowedfields = $postdata['datafields'];
			$allowedfields = (isset($allowedfields) ? implode('|',$allowedfields) : "");

			// get published object types
			$published = (isset($postdata['publish']) && $postdata['publish'])? implode('|',$postdata['publish']): false;
			// get subscribed object types
			$subscribed = (isset($postdata['subscribe']) && $postdata['subscribe'])? implode('|',$postdata['subscribe']): false;
			
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
			$obj->setPermission("publish", $published);
			$obj->setPermission("subscribe", $subscribed);
			$obj->setPermission("datafields", $allowedfields);
			$obj->setPermission("createrepeat", (bool)$postdata['createrepeat']);
			$obj->setPermission("maxjobdays", $postdata['maxjobdays']);
			$obj->setPermission("blocknumbers", $postdata['callblockingperms'] != "none");
			$obj->setPermission("callblockingperms", $postdata['callblockingperms']);
			$obj->setPermission("createreport", (bool)$postdata['createreport']);
			$obj->setPermission("monitorevent", (bool)$postdata['monitorevent']);
			$obj->setPermission("monitorsystemwideevent", (bool)$postdata['monitorsystemwideevent']);
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
			$obj->setPermission("facebookpost", (bool)(isset($postdata['facebookpost'])?$postdata['facebookpost']:false));
			$obj->setPermission("twitterpost", (bool)(isset($postdata['twitterpost'])?$postdata['twitterpost']:false));
			$obj->setPermission("feedpost", (bool)(isset($postdata['feedpost'])?$postdata['feedpost']:false));
				
			if (getSystemSetting("_hasportal", false)) {
				$obj->setPermission("portalaccess", (bool)$postdata['portalaccess']);
				$obj->setPermission("generatebulktokens", (bool)$postdata['generatebulktokens']);
			}

			if (getSystemSetting("_hassurvey", true)) {
				$obj->setPermission("survey", (bool)$postdata['survey']);
			}

			if (getSystemSetting("_hassms", false)) {
				$obj->setPermission("sendsms", (bool)$postdata['sendsms']);
			}
			
			if (getSystemSetting("_hastargetedmessage", false)) {
				$obj->setPermission("targetedmessage", (bool)$postdata['targetedmessage']);
				$obj->setPermission("targetedcomment", (bool)$postdata['targetedcomment']);
				$obj->setPermission("manageclassroommessaging", (bool)$postdata['manageclassroommessaging']);
			}
			
			if (!getSystemSetting('_hascallback', false)) {
				$obj->setPermission("setcallerid", (bool)$postdata['setcallerid']);
			}
			
		Query("COMMIT");

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
<script type="text/javascript">
function checkAllCheckboxes(domanagement){

	var managementoptions = "manageaccount,manageprofile,managesystem,managesystemjobs,managetasks,metadata,manageclassroommessaging".split(",");

	var form = document.forms[0].elements;
	for(var i = 0; i < form.length; i++){
		if(form[i].type == "checkbox"){

			//skip datafields
			if (form[i].name.indexOf("datafields") != -1)
				continue;

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
