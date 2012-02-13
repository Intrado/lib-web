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
require_once("obj/Phone.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ValRules.val.php");
require_once("obj/ValSections.val.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/FormUserItems.obj.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/InpageSubmitButton.fi.php");
require_once("obj/RestrictedValues.fi.php");
require_once("obj/CallerID.fi.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageaccount')) {
	redirect('unauthorized.php');
}

$id = isset($_GET['id']) ? ($_GET['id']+0) : 0;
$makeNewUser = !$id;

if (!$makeNewUser)
	if (QuickQuery("select count(*) from user where login = 'schoolmessenger' and id =?", false, array($id)))
		redirect('unauthorized.php');

if ($makeNewUser) {
	$usercount = QuickQuery("select count(*) from user where enabled = 1 and login != 'schoolmessenger'");
	$maxusers = getSystemSetting("_maxusers", "unlimited");
	if (($maxusers !== "unlimited") && $maxusers <= $usercount) {
		redirect("users.php?maxusers");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$edituser = new User($id);

$readonly = $edituser->importid != null;
$ldapuser = $edituser->ldap;
$profilename = QuickQuery("select name from access where id=?", false, array($edituser->accessid));

$hasenrollment = getSystemSetting('_hasenrollment', false);

$hasstaffid = ($edituser->staffpkey) ? true : false;

$accessprofiles = QuickQueryList("select id, name from access where name != 'SchoolMessenger Admin' order by name", true);

if (!count($accessprofiles)) {
	redirect("users.php?noprofiles");
}

$userjobtypeids = QuickQueryList("select id from jobtype where id in (select jobtypeid from userjobtypes where userid=?) and not deleted and not issurvey order by systempriority, name asc", false, false, array($edituser->id));
$jobtypes = QuickQueryList("select id, name from jobtype where not deleted and not issurvey order by systempriority, name asc", true);

$usersurveytypes = QuickQueryList("select id from jobtype where id in (select jobtypeid from userjobtypes where userid=?) and not deleted and issurvey order by systempriority, name asc", false, false, array($edituser->id));
$surveytypes = QuickQueryList("select id, name from jobtype where not deleted and issurvey order by systempriority, name asc", true);

$userfeedcategories = QuickQueryList("select id from feedcategory where id in (select feedcategoryid from userfeedcategory where userid=?) and not deleted", false, false, array($edituser->id));
$feedcategories = QuickQueryList("select id, name from feedcategory where not deleted", true);

$IS_LDAP = getSystemSetting('_hasldap', '0');

$orgs = Organization::getAuthorizedOrgKeys();

////////////////////////////////////////////////////////////////////////////////
// Custom form items
////////////////////////////////////////////////////////////////////////////////

// requires org to section map as an argument
class UserSectionFormItem extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		// get all orgid to orgkey values
		$orgs = Organization::getAuthorizedOrgKeys();
		// get sectionkeys for current value
		if ($value)
			$sections = QuickQueryList("select id, skey from section where id in (". DBParamListString(count($value)) .")", true, false, $value);
		
		$str = '<style type="text/css">
		.usersectionchoose {
			margin-left: 6px;
			border: 1px dotted gray;
			padding: 4px;
			overflow: auto;
			max-height: 150px;
			width: 30%;
			float: left;
		}
		</style>
		<div id='.$n.' class="radiobox" style="overflow: auto; max-height: 150px; display: '. ($value?'block':'none') .'">';
		if ($value) {
			foreach ($value as $sectionid) {
				$id = $n . $sectionid;
				$str .= '<input  id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.$sectionid.'" checked /><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($sections[$sectionid]).'</label><br />';
			}
		}
		$str .= '</div>';
		if ($orgs) {
			$str .= '<select id="'. $n .'-select" style="float: left" onchange="getSections(this, \''. $n .'-sectionchoosediv\', \''. $n .'\', \''. $n .'-addbutton\')">
				<option value="0" >--- '.escapehtml(_L("Select a %s",getSystemSetting("organizationfieldname","Organization"))).' ---</option>';
			foreach ($orgs as $orgid => $okey) {
				$str .= '<option value="'. $orgid .'" >'.escapehtml($okey).'</option>';
			}
			$str .= '</select>
				<div id="'. $n .'-sectionchoosediv" class="usersectionchoose" style="display: none"></div>';
		}
		// create an add button for use later
		$str .= icon_button("Add", "add", "moveSections(this, '$n-sectionchoosediv', '$n', '$n-select')", null, "id=\"$n-addbutton\" style=\"display: none;\"");
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		// javascript to populate the sections and move the checkbox up into selected sections
		$str = '<script type="text/javascript">
			// get sections from an ajax call and populate a div with checkboxes for each section key
			function getSections(selectelement, targetelement, formitemid, addbutton) {
				selectelement = $(selectelement);
				targetelement = $(targetelement);
				addbutton = $(addbutton);
				
				// if they selected the first element then hide the sections and add button
				if (selectelement.value == 0) {
					addbutton.hide();
					targetelement.hide();
					return;
				}
				
				// make the add button hidden
				addbutton.hide();
				
				// empty out the choose div and put a loading gif in there
				targetelement.show();
				targetelement.update("<img src=\"img/ajax-loader.gif\" /><br>Please wait.<br>Loading content...");
				
				// populate the choose div with data from an ajax call
				cachedAjaxGet("ajax.php?type=getsections&organizationid=" + selectelement.value, function(result, itemid) {
					var sections = result.responseJSON;
					var insertedelements = false;
					targetelement.update();
					if (sections !== false) {
						for (id in sections) {
							chkid = itemid + id.toString();
							chkname = itemid + "[]";
							lblid = chkid + "-label";
							
							// if this checkbox already exists it is in user sections and we cant re-create it
							if ($(chkid) == null) {
								targetelement.insert(
									new Element("input", {"id": chkid, "name": chkname, "type": "checkbox", "value": id})
								).insert(
									new Element("label", {"id": lblid, "for": chkid}).update(sections[id])
								).insert(
									new Element("br")
								);
								insertedelements = true;
							}
						}
					}
					// if we didnt insert anything then put some text in
					if (!insertedelements)
						targetelement.update("'. addslashes(_L("No sections available")). '");
					else // make the add button visible
						addbutton.show();
				}, formitemid, true);
			}
			
			// move checked section key checkbox elements from the source div into the target div
			function moveSections(buttonelement, sourceelement, targetelement, menuelement) {
				
				sourceelement = $(sourceelement);
				targetelement = $(targetelement);
				menuelement = $(menuelement);
				
				// show the targetelement
				targetelement.show();
				
				// hide the add button
				$(buttonelement).hide();
				
				// move all checked items into the targetelement
				sourceelement.descendants().each(function(e) {
					if (e.checked) {
						targetelement.insert(
							e.remove()
						).insert(
							$(e.id + "-label").remove()
						).insert(
							new Element("br")
						);
					}
				});
				// empty and hide the sourceelement
				sourceelement.update();
				sourceelement.hide();
				
				// reset the select menu to default
				menuelement.value = 0;
			}
			</script>';
		
		return $str;
	}
}

class ValUserOrganization extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if ($value) {
			$validorgs = Organization::getAuthorizedOrgKeys();
			foreach ($value as $id)
				if (!isset($validorgs[$id]))
					return _L('%s has invalid data selected.', $this->label);
		}
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpsteps = array();

$formdata[] = _L("Account Information");

$formdata["firstname"] = array(
	"label" => _L("First Name"),
	"fieldhelp" => _L("The user's first name."),
	"value" => $edituser->firstname,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50, "size" => 30),
	"helpstep" => 1
);
$formdata["lastname"] = array(
	"label" => _L("Last Name"),
	"fieldhelp" => _L("The user's last name."),
	"value" => $edituser->lastname,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50, "size" => 30),
	"helpstep" => 1
);

$formdata["description"] = array(
	"label" => _L("Description"),
	"fieldhelp" => _L('An optional description of the user.'),
	"value" => $edituser->description,
	"validators" => array(
		array("ValLength", "max" => 50)
	),
	"control" => array("TextField","maxlength" => 50, "size" => 40),
	"helpstep" => 1
);

$formdata["login"] = array(
	"label" => _L("Username"),
	"fieldhelp" => _L('The username that the user will use to log into the system.'),
	"value" => $edituser->login,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => getSystemSetting("usernamelength", 5),"max" => 20),
		array("ValLogin", "userid" => $edituser->id)
	),
	"control" => array("TextField","maxlength" => 20, "size" => 20),
	"helpstep" => 1
);

if($IS_LDAP){
	$formdata["ldap"] = array(
		"label" => _L("Use LDAP Auth"),
		"fieldhelp" => _L('Authenticate with an onsite LDAP server.  You do not need to set a password for your username here.'),
		"value" => $ldapuser,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 1
	);
	// TODO how to determine if password is required or not, based on ldap checkbox
}

$pass = (!$makeNewUser) ? 'nopasswordchange' : '';
$passlength = getSystemSetting("passwordlength", 5);
$formdata["password"] = array(
	"label" => _L("Password"),
	"fieldhelp" => _L('The password is used to log into the web interface.'),
	"value" => $pass,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => $passlength,"max" => 20),
		array("ValPassword", "login" => $edituser->login, "firstname" => $edituser->firstname, "lastname" => $edituser->lastname)
	),
	"requires" => array("firstname", "lastname", "login"),
	"control" => array("TextPasswordStrength","maxlength" => 20, "size" => 25, "minlength" => $passlength),
	"helpstep" => 1
);

$formdata["passwordconfirm"] = array(
	"label" => _L("Confirm Password"),
	"fieldhelp" => _L('This field is used to confirm a new password.'),
	"value" => $pass,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => $passlength,"max" => 20),
		array("ValFieldConfirmation", "field" => "password")
	),
	"requires" => array("password"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 25),
	"helpstep" => 1
);

$formdata["accesscode"] = array(
	"label" => _L("Phone User ID"),
	"fieldhelp" => _L('The number in this field is like a username for logging into the system by phone.'),
	"value" => $edituser->accesscode,
	"validators" => array(
		array("ValNumeric", "min" => 4),
		array("ValAccesscode", "userid" => $edituser->id)
	),
	"control" => array("TextField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

$pin = $edituser->accesscode ? '00000' : '';
$formdata["pin"] = array(
	"label" => _L("Phone PIN Code"),
	"fieldhelp" => _L('The number in this field is like a password for logging into the system by phone.'),
	"value" => $pin,
	"validators" => array(
		array("ValConditionallyRequired", "field" => "accesscode"),
		array("ValNumeric", "min" => 4),
		array("ValPin", "accesscode" => $edituser->accesscode)
	),
	"requires" => array("accesscode"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

$formdata["pinconfirm"] = array(
	"label" => _L("Confirm PIN"),
	"fieldhelp" => _L('This field is used to confirm a new PIN number.'),
	"value" => $pin,
	"validators" => array(
		array("ValConditionallyRequired", "field" => "pin"),
		array("ValNumeric"),
		array("ValFieldConfirmation", "field" => "pin")
	),
	"requires" => array("pin"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

$formdata["email"] = array(
	"label" => _L("Account Email"),
	"fieldhelp" => ("This is used for forgot passwords, reporting, and as the return address in email messages."),
	"value" => $edituser->email,
	"validators" => array(
		array("ValLength","min" => 0,"max" => 255),
		array("ValEmail")
	),
	"control" => array("TextField","maxlength" => 255, "size" => 35),
	"helpstep" => 1
);

$formdata["aremail"] = array(
	"label" => _L("Auto Report Emails"),
	"fieldhelp" => _L("Email addresses entered here will receive copies of any autoreports associated with this user. The user's email address will automatically receive reports and should not be entered here."),
	"value" => $edituser->aremail,
	"validators" => array(
		array("ValLength","min" => 0,"max" => 1024),
		array("ValEmailList")
	),
	"control" => array("TextField","maxlength" => 1024, "size" => 50),
	"helpstep" => 1
);

$formdata["phone"] = array(
	"label" => _L("Phone"),
	"fieldhelp" => _L('This is the direct access phone number for the user.'),
	"value" => Phone::format($edituser->phone),
	"validators" => array(
		array("ValLength","min" => 2,"max" => 20),
		array("ValPhone")
	),
	"control" => array("TextField","maxlength" => 20, "size" => 15),
	"helpstep" => 1
);

if (!getSystemSetting('_hascallback', false)) {
	$authorizedcallerids = QuickQueryList("select callerid,callerid from authorizedcallerid",true);
	$formdata["callerid"] = array(
				"label" => _L("Caller ID"),
				"fieldhelp" => _L('This is the default Caller ID phone number for jobs sent by the user.'),
				"value" => ($edituser->id +0 > 0)?$edituser->getSetting("callerid", ""):"",
				"validators" => array(
					array("ValLength","min" => 0,"max" => 20),
					array("ValPhone")
				),
				"control" => array("CallerID","maxlength" => 20, "size" => 15,"selectvalues"=>$authorizedcallerids, "allowedit" => $USER->authorize('setcallerid')),
				"helpstep" => 1
	);
	
	
	if(getSystemSetting("requireapprovedcallerid",false)) {
		$authorizedusercallerids = QuickQueryList("select callerid from authorizedusercallerid where userid=?",false,false,array($edituser->id));
		foreach($authorizedcallerids as $calleridkey => $calleridvalue) {
			$authorizedcallerids[$calleridkey] = Phone::format($calleridvalue);
		}
		$formdata["restrictcallerid"] = array(
			"label" => _L("Restrict Caller ID"),
			"fieldhelp" => _L(''),
			"value" => $authorizedusercallerids,
			"validators" => array(),
			"control" => array("RestrictedValues", "values" => $authorizedcallerids, "label" => _L("Allow the following Caller IDs:")),
			"helpstep" => 1
		);
	}
}

$formdata[] = _L("Account Restrictions");

$formdata["accessid"] = array(
	"label" => _L("Access Profile"),
	"fieldhelp" => _L('Access Profiles define which sets of features a group of users may access.'),
	"value" => ($edituser->accessid)?$edituser->accessid:"",
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($accessprofiles))
	),
	"control" => array("SelectMenu", "values"=>array(0=>"--- ". escapehtml(_L("Select a profile")). " ---") + $accessprofiles),
	"helpstep" => 2
);

$formdata["jobtypes"] = array(
	"label" => _L("Job Type Restriction"),
	"fieldhelp" => _L('If the user should only be able to send certain types of jobs, check the job types here. Checking nothing will allow the user to send any job type.'),
	"value" => $userjobtypeids,
	"validators" => array(
		array("ValInArray", "values" => array_keys($jobtypes))
	),
	"control" => array("MultiCheckBox", "values"=>$jobtypes),
	"helpstep" => 2
);
if (getSystemSetting("_hassurvey", true)) {
	$formdata["surveytypes"] = array(
		"label" => _L("Survey Type Restriction"),
		"fieldhelp" => _L('If the user should only be able to send certain types of surveys, check the survey types here. Checking nothing will allow the user to send any survey type.'),
		"value" => $usersurveytypes,
		"validators" => array(
			array("ValInArray", "values" => array_keys($surveytypes))
		),
		"control" => array("MultiCheckBox", "values"=>$surveytypes),
		"helpstep" => 2
	);
}
if (getSystemSetting("_hasfeed", false)) {
	
	$formdata["feedcategories"] = array(
		"label" => _L("Feed Category Restriction"),
		"fieldhelp" => _L('If the user should only be able to send to specific feed categorie, check them here. Checking nothing will allow the user to send to any feed category.'),
		"value" => $userfeedcategories,
		"validators" => array(
			array("ValInArray", "values" => array_keys($feedcategories))
		),
		"control" => array("MultiCheckBox", "values"=>$feedcategories),
		"helpstep" => 2
	);
}

$formdata[] = _L("Data View");

if ($hasenrollment) {
	// if the user already has a staff id then display a read only version.
	if ($hasstaffid) {
		$formdata["staffpkey"] = array(
			"label" => _L('Staff ID'),
			"fieldhelp" => _L("Set if the user is directly related to a staff ID and data access should be controlled based on it's value."),
			"control" => array("FormHtml", "html" => '<div style="border: 1px dotted gray; padding 3px">'. $edituser->staffpkey ."</div>"),
			"helpstep" => 2
		);
	} else {
		$formdata["staffpkey"] = array(
			"label" => _L("Staff ID"),
			"fieldhelp" => _L("Set if the user is directly related to a staff ID and data access should be controlled based on it's value."),
			"value" => $edituser->staffpkey,
			"validators" => array(
				array("ValLength", "max" => 20)
			),
			"control" => array("TextField","maxlength" => 20, "size" => 12),
			"helpstep" => 1
		);
	}
	
	$formdata["submit"] = array(
		"label" => "",
		"value" => "",
		"validators" => array(),
		"control" => array("InpageSubmitButton", "name" => (($hasstaffid)?_L('Remove Staff ID'):_L('Set Staff ID')), "icon" => (($hasstaffid)?"cross":"disk")),
		"helpstep" => 1
	);
	
	// display read only section associations, if there are any
	$userimportsections = QuickQueryList("
		select s.skey
		from userassociation ua
			inner join section s on
				(ua.sectionid = s.id)
		where ua.userid = ? and ua.importid is not NULL order by skey", false, false, array($edituser->id));
	if ($userimportsections) {
		$html = '<div style="border: 1px dotted gray; padding: 3px;">';
		foreach ($userimportsections as $skey)
			$html .= "<div>$skey</div>";
		$html .= "</div>";
		$formdata["readonlysectoins"] = array(
			"label" => _L('Imported Sections'),
			"fieldhelp" => _L('Sections for this user associated during data import'),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 2
		);
	}
	
	// get user section associations that arn't created by an import or are zero
	$usersections = QuickQueryList("select sectionid from userassociation where userid = ? and type = 'section' and importid is null and sectionid != 0", false, false, array($edituser->id));
	// if no authorized orgs. don't allow setting sections
	if ($orgs) {
		$formdata["sectionids"] = array(
			"label" => _L('Additional Sections'),
			"fieldhelp" => _L('Add or remove sections to associate with this user'),
			"value" => $usersections,
			"validators" => array(
				array("ValSections")
			),
			"control" => array("UserSectionFormItem"),
			"helpstep" => 2
		);
	}
}

// display read only organization associations, if there are any
$userimportorgs = QuickQueryList("
	select o.orgkey
	from userassociation ua
		inner join organization o on
			(ua.organizationid = o.id)
	where ua.userid = ? and ua.importid is not NULL order by orgkey", false, false, array($edituser->id));
if ($userimportorgs) {
	$html = '<div style="border: 1px dotted gray; padding: 3px;">';
	foreach ($userimportorgs as $okey)
		$html .= "<div>$okey</div>";
	$html .= "</div>";
	$formdata["readonlyorgs"] = array(
		"label" => _L('Imported %s',getSystemSetting("organizationfieldname","Organization")),
		"control" => array("FormHtml", "html" => $html),
		"helpstep" => 2
	);
}

// get user organization associations that arn't created by an import
$userorgs = QuickQueryList("select organizationid from userassociation where userid = ? and type = 'organization' and importid is null", false, false, array($edituser->id));
// if there are no orgs. don't show the form item
if ($orgs) {
	$formdata["organizationids"] = array(
		"label" => getSystemSetting("organizationfieldname","Organization"),
		"fieldhelp" => _L('Add or remove user organization associations'),
		"value" => $userorgs,
		"validators" => array(
			array("ValUserOrganization")
		),
		"control" => array("MultiCheckBox", "height" => "150px", "values" => $orgs),
		"helpstep" => 2
	);
}

$rules = cleanObjects($edituser->getRules());
$fields = QuickQueryMultiRow("select fieldnum from fieldmap where options not like '%multisearch%'");
$ignoredFields = array();
foreach ($fields as $fieldnum)
	$ignoredFields[] = $fieldnum[0];

$allowedfieldtypes = array('f', 'g', 'c');

$formdata["datarules"] = array(
	"label" => _L("Data Restriction"),
	"fieldhelp" => _L('If the user should only be able to access certain data, you may create restriction rules here.'),
	"value" => json_encode(array_values($rules)),
	"validators" => array(
		array("ValRules", "allowedFieldTypes" => $allowedfieldtypes)
	),
	"control" => array("FormRuleWidget", "allowedFieldTypes" => $allowedfieldtypes, "ignoredFields" => $ignoredFields, "showRemoveAllButton" => true),
	"helpstep" => 1
);

// if user has a staff ID then only f and g field restrictions can be used.
if ($hasstaffid) {
	$formdata["datarules"]["validators"][0]["allowedFieldTypes"] = array('f', 'g');
	$formdata["datarules"]["control"]["allowedFieldTypes"] = array('f', 'g');
}

// Read only users have some control items disabled and some validators removed
if ($readonly) {
	// First Name
	$formdata["firstname"]["control"] = array("FormHtml","html" => $edituser->firstname);
	unset($formdata["firstname"]["validators"]);
	// Last Name
	$formdata["lastname"]["control"] = array("FormHtml","html" => $edituser->lastname);
	unset($formdata["lastname"]["validators"]);
	// Description
	unset($formdata["description"]);
	// User Login
	$formdata["login"]["control"] = array("FormHtml","html" => $edituser->login);
	unset($formdata["login"]["validators"]);
	// Use LDAP login auth
	if($IS_LDAP) $formdata["ldap"]["control"] = array("FormHtml","html" => $ldapuser?"True":"False");
	// Password
	unset($formdata["password"]["requires"]);
	// Access code
	$formdata["accesscode"]["control"] = array("FormHtml","html" => $edituser->accesscode);
	unset($formdata["accesscode"]["validators"]);
	// Phone login PIN
	unset($formdata["pin"]["requires"]);
	// Account Email
	$formdata["email"]["control"] = array("FormHtml","html" => $edituser->email);
	unset($formdata["email"]["validators"]);
	// Autoreport Email
	$formdata["aremail"]["control"] = array("FormHtml","html" => $edituser->aremail);
	unset($formdata["aremail"]["validators"]);
	// Phone
	$formdata["phone"]["control"] = array("FormHtml","html" => Phone::format($edituser->phone));
	unset($formdata["phone"]["validators"]);
	// Caller ID
	if (!getSystemSetting('_hascallback', false)) {
		$formdata["callerid"]["control"] = array("FormHtml","html" => ($edituser->id +0 > 0)?Phone::format($edituser->getSetting("callerid", "")):"");
		unset($formdata["callerid"]["validators"]);
	}
	// Access Profile
	$formdata["accessid"]["control"] = array("FormHtml","html" => $profilename);
	unset($formdata["accessid"]["validators"]);
	// Job Types
	$displayjobtypes = "";
	foreach ($jobtypes as $jobtypeid => $jobtypename) {
		if (count($userjobtypeids)) {
			if (in_array($jobtypeid, $userjobtypeids))
				$displayjobtypes .= escapehtml($jobtypename) . "<br>";
		} else
			$displayjobtypes .= escapehtml($jobtypename) . "<br>";
	}
	$formdata["jobtypes"]["control"] = array("FormHtml", "html" => "<div style='border: 1px dotted;'>$displayjobtypes</div>");
	unset($formdata["jobtypes"]["validators"]);
	// Survey Types
	if (getSystemSetting("_hassurvey", true)) {
		$displaysurveytypes = "";
		foreach ($surveytypes as $jobtypeid => $jobtypename) {
			if (count($usersurveytypes)) {
				if (in_array($jobtypeid, $usersurveytypes))
					$displaysurveytypes .= $jobtypename. "<br>";
			} else
				$displaysurveytypes .= $jobtypename. "<br>";
		}
		$formdata["surveytypes"]["control"] = array("FormHtml", "html" => "<div style='border: 1px dotted;'>$displaysurveytypes</div>");
		unset($formdata["surveytypes"]["validators"]);
	}
	// Staff Pkey value
	if ($hasenrollment) {
		$formdata["staffpkey"] = array(
			"label" => _L('Staff ID'),
			"control" => array("FormHtml", "html" => '<div style="border: 1px dotted gray; padding 3px">'. $edituser->staffpkey ."</div>"),
			"helpstep" => 2
		);
		unset($formdata["submit"]);
	}
	// Data restrictions
	if (count($rules)) {
		$formdata["datarules"]["control"] = array("FormRuleWidget", "readonly" => true);
		$formdata["datarules"]["validators"] = array();
	} else {
		unset($formdata["datarules"]);
	}
}

$buttons = array(submit_button(_L("Done"),"submit","tick"), icon_button(_L("Cancel"),"cross",null,"users.php"));

$form = new Form("account", $formdata, null, $buttons);
$form->ajaxsubmit = true;

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

		if ($makeNewUser) {
			$edituser->enabled = 1;
		}

		Query("BEGIN");

		if (!$readonly) {
			$edituser->firstname = $postdata['firstname'];
			$edituser->lastname = $postdata['lastname'];
			$edituser->description = $postdata['description'];
			$edituser->login = $postdata['login'];
			$edituser->ldap = isset($postdata['ldap'])?$postdata['ldap']:false;
			$edituser->accesscode = $postdata['accesscode'];

			$edituser->email = $postdata['email'];
			$edituser->aremail = $postdata['aremail'];

			$userphone = Phone::parse($postdata['phone']);
			$edituser->phone = $userphone;

			if (strlen($userphone) == 0 )
				$userphone = false;

			if($IS_LDAP){
				if(isset($postdata['ldap']) && $postdata['ldap'])
					$edituser->ldap=1;
				else
					$edituser->ldap=0;
			}

			$edituser->accessid = $postdata['accessid'];

			if ($makeNewUser) // create or update the user
				$edituser->create();
			else
				$edituser->update();

			if (isset($postdata['callerid']))
				$edituser->setSetting("callerid",Phone::parse($postdata['callerid']));
			
			if (isset($postdata['restrictcallerid'])) {
				error_log("change callerid restriction 1");
				if (count($postdata['restrictcallerid'])) {
					error_log("change callerid restriction 2");
						
					if(count(array_diff($postdata['restrictcallerid'],$authorizedusercallerids)) > 0 || 
						count(array_diff($authorizedusercallerids,$postdata['restrictcallerid'])) > 0) {
						error_log("change callerid restriction 3");
						
						QuickUpdate("delete from authorizedusercallerid where userid=?",false,array($edituser->id));
						QuickUpdate("insert into authorizedusercallerid (userid,callerid) values " . repeatWithSeparator("({$edituser->id},?)", ",", count($postdata['restrictcallerid'])),false,$postdata['restrictcallerid']);
					}
				} else {
					error_log("delete callerid restriction");
						
					QuickUpdate("delete from authorizedusercallerid where userid=?",false,array($edituser->id));
				}
			}

			if (!$edituser->getSetting("maxjobdays", false))
				$edituser->setSetting("maxjobdays", 1);

			// Remove all existing user rules
			$rules = $edituser->getRules();
			if (count($rules)) {
				foreach ($rules as $rule) {
					// don't remove c field rules if they are using a staff id
					if ($hasstaffid) {
						if (substr($rule->fieldnum, 0, 1) !== "c") {
							Query("delete from rule where id=?", false, array($rule->id));
							Query("delete from userassociation where userid=? and ruleid=?", false, array($edituser->id, $rule->id));
						}
					 } else {
						Query("delete from rule where id=?", false, array($rule->id));
						Query("delete from userassociation where userid=? and ruleid=?", false, array($edituser->id, $rule->id));
					}
				}
			}

			if (isset($postdata['datarules']))
				$datarules = json_decode($postdata['datarules']);

			if ($button == 'inpagesubmit' && $hasstaffid) {
				// remove current staff id from user
				$edituser->staffpkey = "";
				Query("delete from userassociation where userid=? and sectionid=0", false, array($edituser->id));
			}

			if (!$hasstaffid && isset($postdata['staffpkey']) && strlen($postdata['staffpkey'])) {
				// set current staffid
				$edituser->staffpkey = $postdata['staffpkey'];
				// give them association to no sections (section assoc created via section import)
				Query("insert into userassociation (userid, type, sectionid) values (?, 'section', 0)", false, array($edituser->id));
				
				// remove any c fields from data rules
				if (count($datarules))
					foreach ($datarules as $index => $datarule)
						if (substr($datarule->fieldnum, 0, 1) == "c")
							unset($datarules[$index]);
			}

			if (count($datarules)) {
				foreach ($datarules as $datarule) {
					$rule = new Rule();
					$rule->fieldnum = $datarule->fieldnum;
					$rule->type = $datarule->type;
					$rule->logical = $datarule->logical;
					$rule->op = $datarule->op;
					$rule->val = is_array($datarule->val)?implode("|", $datarule->val):$datarule->val;
					$rule->create();

					Query("insert into userassociation (userid, type, ruleid) values (?, 'rule', ?)", false, array($edituser->id, $rule->id));
				}
			}

			// update again for staffid
			$edituser->update();

			QuickUpdate("delete from userjobtypes where userid =?", false, array($edituser->id));

			if(count($postdata['jobtypes']))
				foreach($postdata['jobtypes'] as $type)
					QuickUpdate("insert into userjobtypes values (?, ?)", false, array($edituser->id, $type));
			if (getSystemSetting("_hassurvey", true) && isset($postdata['surveytypes']) && count($postdata['surveytypes']))
				foreach($postdata['surveytypes'] as $type)
					QuickUpdate("insert into userjobtypes values (?, ?)", false, array($edituser->id, $type));
			
			// Feed Category settings
			if (getSystemSetting("_hasfeed", false)) {
				QuickUpdate("delete from userfeedcategory where userid =?", false, array($edituser->id));
				if (isset($postdata['feedcategories']) && count($postdata['feedcategories'])) {
					$fcargs = array();
					$fcquery = "insert into userfeedcategory (userid, feedcategoryid) values ";
					$fccount = 0;
					foreach ($postdata['feedcategories'] as $fcid) {
						if ($fccount++ > 0)
							$fcquery .= ",";
						$fcquery .= "(?,?)";
						$fcargs[] = $edituser->id;
						$fcargs[] = $fcid;
					}
					QuickUpdate($fcquery, false, $fcargs);
				}
			}
		}

		// If the pincode is all 0 characters then it was a default form value, so ignore it
		if (!preg_match("/^0*$/", $postdata['pin']))
			$edituser->setPincode($postdata['pin']);
			
		// add user associations for sections
		if ($hasenrollment) {
			// remove all custom user associations
			QuickUpdate("delete from userassociation where userid = ? and importid is null and type = 'section' and sectionid != 0", false, array($edituser->id));
			// re add new user associations
			$sectionids = (isset($postdata['sectionids'])?$postdata['sectionids']:array());
			foreach ($sectionids as $sectionid)
				QuickUpdate("insert into userassociation (userid, type, sectionid) values (?, 'section', ?)", false, array($edituser->id, $sectionid));
			
		}
		
		// add user associations for organizations
		QuickUpdate("delete from userassociation where userid = ? and importid is null and type = 'organization' and organizationid != 0", false, array($edituser->id));
		if (isset($postdata['organizationids']))
			foreach ($postdata['organizationids'] as $orgid)
				QuickUpdate("insert into userassociation (userid, type, organizationid) values (?, 'organization', ?)", false, array($edituser->id, $orgid));

		Query("COMMIT");
		
		// MUST set password outside of the transaction or the authserver will get a lock timeout on the user object
		// If the password is "nopasswordchange" then it was a default form value, so ignore it
		if ($postdata['password'] !== "nopasswordchange")
			$edituser->setPassword($postdata['password']);
		
		if ($button == 'inpagesubmit') {
			if ($hasstaffid)
				notice(_L("Staff ID is now removed from Data View."));
			else
				notice(_L("Staff ID is now added to Data View."));

			if ($ajax)
				$form->sendTo("user.php?id=".$edituser->id);
			else
				redirect("user.php?id=".$edituser->id);
		} else {
			notice(_L("Changes to account %s are now saved", $edituser->login));

			if ($ajax)
				$form->sendTo("users.php");
			else
				redirect("users.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:users";
$TITLE = _L('User Editor: ') . ($makeNewUser ? _L("New User") : escapehtml($edituser->firstname) . ' ' . escapehtml($edituser->lastname));

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValLogin", "ValPassword", "ValAccesscode", "ValPin", "ValRules", "ValSections", "ValUserOrganization")); ?>
</script>
<?

startWindow(_L("User Information"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
