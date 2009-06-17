<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/table.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Access.obj.php");
include_once("obj/Permission.obj.php");
include_once("obj/FieldMap.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageprofile')) {
	redirect('unauthorized.php');
}

/*CSDELETEMARKER_START*/
if(isset($_GET['id'])){
	$id = $_GET['id']+0;
	if(QuickQuery("select count(*) from access where name = 'SchoolMessenger Admin' and id = '$id'")){
		redirect('unauthorized.php');
	}
}
/*CSDELETEMARKER_END*/
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentAccess($_GET['id']);
	redirect();
}

/****************** main message section ******************/

$form = "security";
$section = "main";
$reloadform = 0;

if(CheckFormSubmit($form,$section))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($form))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($form, $section);

		//do check
		if( CheckFormSection($form, $section) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} elseif(GetFormData($form, $section, 'calllate') && GetFormData($form, $section, 'callearly') && (strtotime(GetFormData($form, $section, 'callearly')) >= strtotime(GetFormData($form, $section, 'calllate')))) {
			error("The earliest call time must be before the latest call time");
		} elseif(TrimFormData($form, $section, "name") == ""){
			error("Profile names cannot be blank");
		} elseif(QuickQuery("select count(*) from access where name = '"  . DBSafe(TrimFormData($form,$section,"name")) . "' and id != '" . $_SESSION['accessid'] . "'")){
			error("This profile name already exists, please choose another");
		} else {
			//submit changes

			$accss = new Access($_SESSION['accessid']);
			$accss->moduserid = $USER->id;
			$accss->modified = date("Y-m-d g:i:s");
			if(!$accss->id)
				$accss->created = date("Y-m-d g:i:s");

			//TODO set options

			//only allow editing some fields
			PopulateObject($form,$section,$accss,array("name", "description"));
			$accss->update();

			$allowedfields = GetFormData($form, $section, 'datafields');
			$allowedfields = (isset($allowedfields) ? implode('|',$allowedfields) : "");

			$accss->setPermission("loginweb", (bool)GetFormData($form, $section, 'loginweb'));
			$accss->setPermission("loginphone", (bool)GetFormData($form, $section, 'loginphone'));
			$accss->setPermission("startstats", (bool)GetFormData($form, $section, 'startstats'));
			$accss->setPermission("startshort", (bool)GetFormData($form, $section, 'startshort'));
			$accss->setPermission("starteasy", (bool)GetFormData($form, $section, 'starteasy'));
			$accss->setPermission("sendphone",(bool)GetFormData($form, $section, 'sendphone'));
			$accss->setPermission("callearly", GetFormData($form, $section, 'callearly'));
			$accss->setPermission("calllate", GetFormData($form, $section, 'calllate'));
			$accss->setPermission("callmax", GetFormData($form, $section, 'callmax'));
			$accss->setPermission("sendemail", (bool)GetFormData($form, $section, 'sendemail'));
			$accss->setPermission("sendsms", (bool)GetFormData($form, $section, 'sendsms'));
			$accss->setPermission("sendmulti", (bool)GetFormData($form, $section, 'sendmulti'));
			$accss->setPermission("createlist", (bool)GetFormData($form, $section, 'createlist'));
			$accss->setPermission("listuploadids", (bool)GetFormData($form, $section, 'listuploadids'));
			$accss->setPermission("listuploadcontacts", (bool)GetFormData($form, $section, 'listuploadcontacts'));
			$accss->setPermission("datafields", $allowedfields);
			$accss->setPermission("createrepeat", (bool)GetFormData($form, $section, 'createrepeat'));
			$accss->setPermission("setcallerid", (bool)GetFormData($form, $section, 'setcallerid'));
			$accss->setPermission("maxjobdays", GetFormData($form, $section, 'maxjobdays'));
			$accss->setPermission("blocknumbers", GetFormData($form, $section, 'blocknumbers'));
			$accss->setPermission("callblockingperms", GetFormData($form, $section, 'callblockingperms'));
			$accss->setPermission("createreport", (bool)GetFormData($form, $section, 'createreport'));
			$accss->setPermission("viewsystemreports", (bool)GetFormData($form, $section, 'viewsystemreports'));
			$accss->setPermission("viewusagestats", (bool)GetFormData($form, $section, 'viewusagestats'));
			$accss->setPermission("viewcalldistribution", (bool)GetFormData($form, $section, 'viewcalldistribution'));
			$accss->setPermission("managesystemjobs", (bool)GetFormData($form, $section, 'managesystemjobs'));
			$accss->setPermission("managemyaccount", (bool)GetFormData($form, $section, 'managemyaccount'));
			$accss->setPermission("manageaccount", (bool)GetFormData($form, $section, 'manageaccount'));
			$accss->setPermission("manageprofile", (bool)GetFormData($form, $section, 'manageprofile'));
			$accss->setPermission("managesystem", (bool)GetFormData($form, $section, 'managesystem'));
			$accss->setPermission("viewcontacts", (bool)GetFormData($form, $section, 'viewcontacts'));
			$accss->setPermission("managecontactdetailsettings", (bool)GetFormData($form, $section, 'managecontactdetailsettings'));
			if ($SETTINGS['feature']['has_print']) {
				$accss->setPermission("sendprint", (bool)GetFormData($form, $section, 'sendprint'));
			}
			$accss->setPermission("metadata", (bool)GetFormData($form, $section, 'metadata'));
			$accss->setPermission("managetasks", (bool)GetFormData($form, $section, 'managetasks'));
			$accss->setPermission("viewsystemactive", (bool)GetFormData($form, $section, 'viewsystemactive'));
			$accss->setPermission("viewsystemrepeating", (bool)GetFormData($form, $section, 'viewsystemrepeating'));
			$accss->setPermission("viewsystemcompleted", (bool)GetFormData($form, $section, 'viewsystemcompleted'));
			$accss->setPermission("survey", (bool)GetFormData($form, $section, 'survey'));
			$accss->setPermission("leavemessage", (bool)GetFormData($form, $section, 'leavemessage'));
			$accss->setPermission("messageconfirmation", (bool)GetFormData($form, $section, 'messageconfirmation'));
			if(getSystemSetting("_hasportal", false)){
				$accss->setPermission("portalaccess", (bool)GetFormData($form, $section, 'portalaccess'));
				$accss->setPermission("generatebulktokens", (bool)GetFormData($form, $section, 'generatebulktokens'));
			}

			$_SESSION['accessid'] = $accss->id;
			ClearFormData($form);
			redirect('profiles.php');

			$reloadform = 1;
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($form);

	//check to see if the name & desc is prepopulated from another form

	$accss = new Access($_SESSION['accessid']);

	//TODO break out options

	$fields = array(
				array("name","text",1,50),
				array("description","text",1,50));
	PopulateForm($form,$section,$accss,$fields);

	$datafields = explode('|',$accss->getValue('datafields'));
	PutFormData($form, $section, 'datafield', $accss->getValue('datafields')? 1 : 0, "bool", 0, 1);
	PutFormData($form, $section, 'datafields', $datafields, "text", "nomin", "nomax");

	//FIXME just use PutFormData
	$permissions = array(
				array("loginweb","bool",0,1),
				array("loginphone","bool",0,1),
				array("startstats","bool",0,1),
				array("startshort","bool",0,1),
				array("starteasy","bool",0,1),
				array("sendphone","bool",0,1),
				array("callearly","text",1,50),
				array("calllate","text",1,50),
				array("sendemail","bool",0,1),
				array("sendsms","bool",0,1),
				array("sendprint","bool",0,1),
				array("sendmulti","bool",0,1),
				array("createlist","bool",0,1),
				array("listuploadids","bool",0,1),
				array("listuploadcontacts","bool",0,1),
				array("createrepeat","bool",0,1),
				array("setcallerid","bool",0,1),
				array("blocknumbers","bool",0,1),
				array("createreport","bool",0,1),
				array("viewsystemreports","bool",0,1),
				array("viewusagestats","bool",0,1),
				array("viewcalldistribution","bool",0,1),
				array("managesystemjobs","bool",0,1),
				array("managemyaccount","bool",0,1),
				array("manageaccount","bool",0,1),
				array("manageprofile","bool",0,1),
				array("managesystem","bool",0,1),
				array("viewcontacts","bool",0,1),
				array("managecontactdetailsettings","bool",0,1),
				array("metadata","bool",0,1),
				array("managetasks","bool",0,1),
				array("viewsystemactive","bool",0,1),
				array("viewsystemrepeating","bool",0,1),
				array("viewsystemcompleted","bool",0,1),
				array("survey","bool",0,1),
				array("leavemessage","bool",0,1),
				array("messageconfirmation", "bool", 0, 1),
				array("portalaccess","bool",0,1),
				array("generatebulktokens","bool",0,1));

	foreach($permissions as $field) {
		PutFormData($form, $section, $field[0], $accss->getValue($field[0]),$field[1],$field[2],$field[3]);
	}

	$callmax = $accss->getValue("callmax");
	$callmax = $callmax === false ? 5 : $callmax ;
	PutFormData($form, $section, "callmax", $callmax,"text",1,50);

	$maxjobdays = $accss->getValue("maxjobdays");
	$maxjobdays = $maxjobdays === false ? 2 : $maxjobdays ;

	PutFormData($form, $section, "maxjobdays", $maxjobdays,"number",1,7);



	// Only set the radio button values mapped to 'callblockingperms' if the master permission is enabled.
	if ($accss->getValue('blocknumbers')) {
		PutFormData($form, $section, 'callblockingperms', $accss->getValue('callblockingperms'));
	} else {
		PutFormData($form, $section, 'callblockingperms', 'viewonly');
	}

}


$ffields = FieldMap::getMapNamesLike('f');
$gfields = FieldMap::getMapNamesLike('g');
$cfields = FieldMap::getMapNamesLike('c');

$FIELDMAP = $ffields + $gfields + $cfields; // GUI preferred ordering


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:profiles";
$TITLE = "Edit Access Profile: " . (GetFormData($form, $section, 'name') == NULL ? "New Access Profile" : escapehtml(GetFormData($form, $section, 'name')));
$DESCRIPTION = GetFormData($form, $section, 'description');

include_once("nav.inc.php");
NewForm($form);

buttons(submit($form, $section, 'Save'), button("Check All", "checkAllCheckboxes()"));

startWindow('Profile Information', 'padding: 3px;');
print 'Name: ';
NewFormItem($form,$section,"name","text", 30);
print '&nbsp;&nbsp;Description: ';
NewFormItem($form,$section,"description","text", 50);
print '&nbsp;';
endWindow();

startWindow('Allowed Functions');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Login:<br><? print help('Profile_Login'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"loginweb","checkbox"); ?></td>
					<td>Log into the system via the web</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"loginphone","checkbox"); ?></td>
					<td>Log into the system via the phone</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"managemyaccount","checkbox"); ?></td>
					<td>Edit personal account information</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Start Page:<br><? print help('Profile_StartPage'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"startstats","checkbox"); ?></td>
					<td>View Start Page Job Statistics</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"startshort","checkbox"); ?></td>
					<td>View Start Page Shortcuts</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"starteasy","checkbox"); ?></td>
					<td>Enable outbound recording</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Messages:<br><? print help('Profile_Messages'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"sendphone","checkbox"); ?></td>
					<td>Send phone calls</td>
					<td>Earliest Time to Call:</td>
					<td><? time_select($form,$section,"callearly","No restriction"); ?></td>
					<td>Latest Time to Call:</td>
					<td><? time_select($form,$section,"calllate","No restriction"); ?></td>
					<td>Maximum Attempts:</td>
					<td>
						<?
						NewFormItem($form,$section,"callmax","selectstart",NULL,NULL,'id="callmax"');
						//federal law requires that automatic dialing machines make no more than 14 calls to the same number
						for($i = 1; $i <= 14; $i++)
							NewFormItem($form,$section,"callmax","selectoption",$i,$i);
						NewFormItem($form,$section,"callmax","selectend");
						?>
					</td>
				</tr>
				<tr>
					<td><? NewFormItem($form, $section, "leavemessage", "checkbox"); ?></td>
					<td>Allow voice responses</td>
				</tr>
				<tr>
					<td><? NewFormItem($form, $section, "messageconfirmation", "checkbox"); ?></td>
					<td>Allow message confirmations</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"sendemail","checkbox"); ?></td>
					<td>Send emails</td>
				</tr>

<? if (getSystemSetting('_hassms', false)) { ?>
				<tr>
					<td><? NewFormItem($form,$section,"sendsms","checkbox"); ?></td>
					<td>Send SMS messages</td>
				</tr>
<? } ?>


<? if ($SETTINGS['feature']['has_print']) { ?>
				<tr>
					<td><? NewFormItem($form,$section,"sendprint","checkbox"); ?></td>
					<td>Send printed letters</td>
				</tr>
<? } ?>
				<tr>
					<td><? NewFormItem($form,$section,"sendmulti","checkbox"); ?></td>
					<td>Send multi-language messages</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Lists:<br><? print help('Profile_Lists'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"createlist","checkbox"); ?></td>
					<td>Create/Edit Lists</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"listuploadids","checkbox"); ?></td>
					<td>Upload Lists by ID# lookup</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"listuploadcontacts","checkbox"); ?></td>
					<td>Upload Lists by contact data</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Data:<br><? print help('Profile_Data'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr valign="top">
					<td><? NewFormItem($form,$section,"datafield","checkbox",NULL,NULL,'id="datafield" onclick="clearAllIfNotChecked(this,\'datafieldselect\');"'); ?></td>
					<td>Restrict user access to only those fields that are selected:</td>
					<td>
						<?
						// removed array_flip on $FIELDMAP; jjl
						NewFormItem($form,$section,"datafields","selectmultiple",count($FIELDMAP),$FIELDMAP, 'id="datafieldselect" onmousedown="setChecked(\'datafield\');"');
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Jobs:<br><? print help('Profile_Jobs'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"createrepeat","checkbox"); ?></td>
					<td>Create Repeating Jobs</td>
				</tr>
<?
	if (getSystemSetting("_hassurvey", true)) {
?>
				<tr>
					<td><? NewFormItem($form,$section,"survey","checkbox"); ?></td>
					<td>Create Survey Jobs</td>
				</tr>
<?
	}
?>
				<tr>
					<td><? NewFormItem($form,$section,"setcallerid","checkbox"); ?></td>
					<td>Set the job caller ID</td>
				</tr>
				<tr>
					<td>
					<?
						NewFormItem($form, $section, "maxjobdays", "selectstart");
						for ($i = 1; $i <= 7; $i++) {
							NewFormItem($form, $section, 'maxjobdays', "selectoption", $i, $i);
						}
						NewFormItem($form, $section, "maxjobdays", "selectend");
					?>
					</td>
					<td>Maximum number of days for which users can schedule a job to run</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Reports:<br><? print help('Profile_Reports'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"createreport","checkbox"); ?></td>
					<td>Create Reports</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Security:<br><? print help('Profile_Security'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"manageaccount","checkbox"); ?></td>
					<td>Manage User Accounts</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"manageprofile","checkbox"); ?></td>
					<td>Manage Access Profiles</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"managesystem","checkbox"); ?></td>
					<td>Manage Systemwide Settings</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Contacts:<br><? print help('Profile_Contacts'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"viewcontacts","checkbox", null, null, "id='viewcontacts'"); ?></td>
					<td>View Contacts</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"managecontactdetailsettings","checkbox"); ?></td>
					<td>Manage Contact Detail Settings</td>
				</tr>
			</table>
		</td>
	</tr>
<?
	if(getSystemSetting("_hasportal", false)){
?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Contact Manager:<br><?=help('Profile_ContactManager')?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"portalaccess","checkbox", null, null, "onclick='if(this.checked){new getObj(\"viewcontacts\").obj.checked=true}'"); ?></td>
					<td>Access Contact Manager Administration Options</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"generatebulktokens","checkbox"); ?></td>
					<td>Generate Bulk Activation Codes</td>
				</tr>
			</table>
		</td>
	</tr>
<?
	}
?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Systemwide View:<br><? print help('Profile_SystemwideView'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form, $section, "viewsystemreports", "checkbox", 40, 'nopotion', 'id="viewsystemreports"' ); ?></td>
					<td>View systemwide report data (controls access to report details on individual reports)</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"viewusagestats","checkbox", 40, 'nooption', "id='viewusagestats'"); ?></td>
					<td>View systemwide usage stats</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"viewcalldistribution","checkbox", 40, 'nooption', "id='viewcalldistribution'"); ?></td>
					<td>View systemwide call distribution</td>
				</tr>
				<tr>
					<td><? NewFormItem($form, $section, "viewsystemactive", "checkbox", 40, 'nooption', "id='viewsystemactive'"); ?></td>
					<td>View all active jobs</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"viewsystemcompleted","checkbox", 40, 'nooption', "id='viewsystemcompleted'"); ?></td>
					<td>View all completed jobs</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"viewsystemrepeating","checkbox", 40, 'nooption', "id='viewsystemrepeating'"); ?></td>
					<td>View all repeating jobs</td>
				</tr>
				<tr>
					<td><? NewFormItem($form, $section, "managesystemjobs", "checkbox", 40, 'nopotion', 'id="managesystemjobs"'); ?></td>
					<td>Manage systemwide jobs</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"metadata","checkbox"); ?></td>
					<td>Manage Field Definitions</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"managetasks","checkbox"); ?></td>
					<td>Manage Imports</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"blocknumbers","checkbox", null, null, "id='blocknumbers'"); ?></td>
					<td>Access systemwide blocked numbers list
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<table border="0" cellpadding="2" cellspacing="0">
						<tr>
							<td valign="top">&nbsp;<? NewFormItem($form,$section,"callblockingperms","radio", null, 'viewonly', "id='callblockingperms1'" ); ?></td><td>View Numbers Only</td>
							<td valign="top">&nbsp;<? NewFormItem($form,$section,"callblockingperms","radio", null, 'addonly', "id='callblockingperms2'"); ?></td><td>Add/Delete Own Numbers</td>
							<td valign="top">&nbsp;<? NewFormItem($form,$section,"callblockingperms","radio", null, 'editall', "id='callblockingperms3'"); ?></td><td>Add/Delete All Numbers</td>
						</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
<script>
	function checkAllCheckboxes(){
		var form = document.forms[0].elements;
		for(var i = 0; i < form.length; i++){
			if(form[i].type == "checkbox"){
				form[i].checked = true;
			}
		}
	}
</script>