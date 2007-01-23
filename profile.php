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
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($form, $section);

		//do check
		if( CheckFormSection($form, $section) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes

			$accss = new Access($_SESSION['accessid']);
			$accss->customerid = $USER->customerid;
			$accss->moduserid = $USER->id;
			$accss->modified = date("Y-m-d H:i:s");
			if(!$accss->id)
				$accss->created = date("Y-m-d H:i:s");

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
			$accss->setPermission("managesystemjobs", (bool)GetFormData($form, $section, 'managesystemjobs'));
			$accss->setPermission("managemyaccount", (bool)GetFormData($form, $section, 'managemyaccount'));
			$accss->setPermission("manageaccount", (bool)GetFormData($form, $section, 'manageaccount'));
			$accss->setPermission("manageprofile", (bool)GetFormData($form, $section, 'manageprofile'));
			$accss->setPermission("managesystem", (bool)GetFormData($form, $section, 'managesystem'));
			$accss->setPermission("viewcontacts", (bool)GetFormData($form, $section, 'viewcontacts'));
			if ($IS_COMMSUITE) {
				$accss->setPermission("sendprint", (bool)GetFormData($form, $section, 'sendprint'));
			}
			$accss->setPermission("metadata", (bool)GetFormData($form, $section, 'metadata'));
			$accss->setPermission("managetasks", (bool)GetFormData($form, $section, 'managetasks'));
			$accss->setPermission("viewsystemactive", (bool)GetFormData($form, $section, 'viewsystemactive'));
			$accss->setPermission("viewsystemrepeating", (bool)GetFormData($form, $section, 'viewsystemrepeating'));
			$accss->setPermission("viewsystemcompleted", (bool)GetFormData($form, $section, 'viewsystemcompleted'));
			$accss->setPermission("survey", (bool)GetFormData($form, $section, 'survey'));

			$_SESSION['accessid'] = $accss->id;

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
				array("callmax","text",1,50),
				array("sendemail","bool",0,1),
				array("sendprint","bool",0,1),
				array("sendmulti","bool",0,1),
				array("createlist","bool",0,1),
				array("listuploadids","bool",0,1),
				array("listuploadcontacts","bool",0,1),
				array("createrepeat","bool",0,1),
				array("setcallerid","bool",0,1),
				array("maxjobdays","number",1,7),
				array("blocknumbers","bool",0,1),
				array("createreport","bool",0,1),
				array("viewsystemreports","bool",0,1),
				array("managesystemjobs","bool",0,1),
				array("managemyaccount","bool",0,1),
				array("manageaccount","bool",0,1),
				array("manageprofile","bool",0,1),
				array("managesystem","bool",0,1),
				array("viewcontacts","bool",0,1),
				array("metadata","bool",0,1),
				array("managetasks","bool",0,1),
				array("viewsystemactive","bool",0,1),
				array("viewsystemrepeating","bool",0,1),
				array("viewsystemcompleted","bool",0,1),
				array("survey","bool",0,1));

	foreach($permissions as $field) {
		PutFormData($form, $section, $field[0], $accss->getValue($field[0]),$field[1],$field[2],$field[3]);
	}

	// Only set the radio button values mapped to 'callblockingperms' if the master permission is enabled.
	if ($accss->getValue('blocknumbers')) {
		PutFormData($form, $section, 'callblockingperms', $accss->getValue('callblockingperms'));
	} else {
		PutFormData($form, $section, 'callblockingperms', 'none');
	}

	if ($accss->getValue('maxjobdays') == null) {
		PutFormData($form, $section, 'maxjobdays', 7);
	}
}


$FIELDMAP = FieldMap::getMapNames();
////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:security";
$TITLE = "Edit Access Profile: " . (GetFormData($form, $section, 'name') == NULL ? "New Access Profile" : GetFormData($form, $section, 'name'));
$DESCRIPTION = GetFormData($form, $section, 'description');

include_once("nav.inc.php");
NewForm($form);

buttons(submit($form, $section, 'submit', 'save'));

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
		<th align="right" class="windowRowHeader bottomBorder">Login:<br><? print help('Profile_Login',NULL,'grey'); ?></th>
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
		<th align="right" class="windowRowHeader bottomBorder">Start Page:<br><? print help('Profile_StartPage',NULL,'grey'); ?></th>
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
		<th align="right" class="windowRowHeader bottomBorder">Messages:<br><? print help('Profile_Messages',NULL,'grey'); ?></th>
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
					<td><? NewFormItem($form,$section,"sendemail","checkbox"); ?></td>
					<td>Send emails</td>
				</tr>
<? if ($IS_COMMSUITE) { ?>
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
		<th align="right" class="windowRowHeader bottomBorder">Lists:<br><? print help('Profile_Lists',NULL,'grey'); ?></th>
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
		<th align="right" class="windowRowHeader bottomBorder">Data:<br><? print help('Profile_Data',NULL,'grey'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr valign="top">
					<td><? NewFormItem($form,$section,"datafield","checkbox",NULL,NULL,'id="datafield" onclick="clearAllIfNotChecked(this,\'datafieldselect\');"'); ?></td>
					<td>Restrict user access to only those fields that are selected:</td>
					<td>
						<?
						NewFormItem($form,$section,"datafields","selectmultiple",count($FIELDMAP),array_flip($FIELDMAP), 'id="datafieldselect" onmousedown="setChecked(\'datafield\');"');
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Jobs:<br><? print help('Profile_Jobs',NULL,'grey'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"createrepeat","checkbox"); ?></td>
					<td>Create Repeating Jobs</td>
				</tr>
				<tr>
					<td><? NewFormItem($form,$section,"survey","checkbox"); ?></td>
					<td>Create Survey Jobs</td>
				</tr>
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
		<th align="right" class="windowRowHeader bottomBorder">Reports:<br><? print help('Profile_Reports',NULL,'grey'); ?></th>
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
		<th align="right" class="windowRowHeader bottomBorder">Security:<br><? print help('Profile_Security',NULL,'grey'); ?></th>
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
		<th align="right" class="windowRowHeader bottomBorder">Contacts:<br><? print help('Profile_Contacts',NULL,'grey'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form,$section,"viewcontacts","checkbox"); ?></td>
					<td>View Contacts</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Systemwide View:<br><? print help('Profile_SystemwideView',NULL,'grey'); ?></th>
		<td class="bottomBorder" width="100%">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td><? NewFormItem($form, $section, "viewsystemreports", "checkbox", 40, 'nopotion', 'id="viewsystemreports"' ); ?></td>
					<td>View systemwide reports</td>
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
					<td>Manage Metadata</td>
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