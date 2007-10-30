<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/JobType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


//check params
if (isset($_GET['deletetype'])) {
	// TODO jobtype priority is gone, use id instead
	$priority = DBSafe($_GET['deletetype']);

	QuickUpdate("update jobtype set deleted=1 where priority = '$priority' and deleted=0");
	redirect();
}

function getSetting($name) {
	global $USER;
	$name = DBSafe($name);
	return QuickQuery("select value from setting where name = '$name'");
}

function setSetting($name, $value) {
	global $USER;
	$old = getSetting($name);
	$name = DBSafe($name);
	$value = DBSafe($value);
	if($old === false && $value !== '' && $value !==NULL) {
		QuickUpdate("insert into setting (name, value) values ('$name', '$value')");
	} else {
		if($value === '' || $value === NULL)
			QuickUpdate("delete from setting where name = '$name'");
		elseif($value != $old)
			QuickUpdate("update setting set value = '$value' where name = '$name'");
	}
}

$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);

/****************** main message section ******************/

$f = "setting";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'addtype'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if($IS_COMMSUITE && GetFormData($f, $s, "easycallmin") > GetFormData($f, $s, "easycallmax") && (GetFormData($f, $s, "easycallmax") != "")){
			error('The minimum extensions length has to be less than or equal to the maximum');
		} else {
			//check the parsing

			if (isset($errors) && count($errors) > 0) {
				error('There was an error parsing the setting', implode("",$errors));
			} else {
				//submit changes
				if(isset($_POST['jobtype']) && $types = $_POST['jobtype']) {
					foreach($types as $id => $name) {
						$name = DBSafe($name);
						$id = DBSafe($id);
						$systempriority = isset($_POST['systempriority'][$id]) ? 0 + $_POST['systempriority'][$id] : "3";
						$timeslices = isset($_POST['timeslices'][$id]) ? 0 + $_POST['timeslices'][$id] : "100";
						$timeslices = min(600,abs($timeslices));

						if($id == 'new' && $name) {
							$query = "insert into jobtype (name, systempriority, timeslices) values ('$name','$systempriority','$timeslices')";
							QuickUpdate($query);
						} else {
							if (customerOwns("jobtype",$id)) {
								$query = "update jobtype set name = '$name' , systempriority='$systempriority', timeslices='$timeslices' where id = '$id'";
								QuickUpdate($query);
							}
						}
					}
				}
				$custname= GetFormData($f, $s, 'custdisplayname');
				if($custname != "" || $custname != $_SESSION['custname']){
					setSetting('displayname', $custname);
					$_SESSION['custname']=$custname;
				}
				if($IS_COMMSUITE){
					setSetting('surveyurl', GetFormData($f, $s, 'surveyurl'));
				}
				setSetting('retry', GetFormData($f, $s, 'retry'));
				setSetting('callerid', Phone::parse(GetFormData($f, $s, 'callerid')));

				setSetting('defaultareacode', GetFormData($f, $s, 'defaultareacode'));


				setSetting('disablerepeat', GetFormData($f, $s, 'disablerepeat'));
				setSetting('alertmessage', trim(GetFormData($f, $s, 'alertmessage')));

				setSetting('autoreport_replyemail', GetFormData($f, $s, 'autoreport_replyemail'));
				setSetting('autoreport_replyname', GetFormData($f, $s, 'autoreport_replyname'));

				$checkpassword = GetFormData($f, $s, 'checkpassword') ? 1 : 0;
				setSetting('checkpassword', GetFormData($f, $s, 'checkpassword'));

				setSetting('usernamelength', GetFormData($f, $s, 'usernamelength'));
				setSetting('passwordlength', GetFormData($f, $s, 'passwordlength'));
				if(getSystemSetting("_hasportal") && $USER->authorize('portalaccess')){
					for($i = 0; $i < $maxphones; $i++){
						setSetting('lockedphone' . $i, GetFormData($f, $s, 'lockedphone' . $i));
					}
					for($i = 0; $i < $maxemails; $i++){
						setSetting('lockedemail' . $i, GetFormData($f, $s, 'lockedemail' . $i));
					}
					for($i = 0; $i < $maxsms; $i++){
						setSetting('lockedsms' . $i, GetFormData($f, $s, 'lockedsms' . $i));
					}
					setSetting('tokenlife', GetFormData($f, $s, 'tokenlife'));
				}

				if($IS_COMMSUITE){
					setSetting('easycallmin', GetFormData($f, $s, 'easycallmin'));
					setSetting('easycallmax', GetFormData($f, $s, 'easycallmax'));
				}
				redirect();

				$reloadform = 1;
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	//check for new setting name/desc from settings.php

	$custname = getSetting('displayname');
	PutFormData($f, $s,"custdisplayname", $custname, 'text', 0, 50);
	if($IS_COMMSUITE)
		PutFormData($f, $s, "surveyurl", getSetting('surveyurl'), 'text', 0, 100);
	PutFormData($f,$s,"retry",getSetting('retry'),"number",5,240);
	PutFormData($f, $s, "callerid", Phone::format(getSetting('callerid')), 'phone', 10, 10);

	PutFormData($f, $s, "defaultareacode", getSetting('defaultareacode'), 'number',200,999);


	PutFormData($f, $s, "disablerepeat", getSetting('disablerepeat'), 'bool');
	PutFormData($f, $s, "alertmessage", getSetting('alertmessage'), 'text',0,255);


	PutFormData($f, $s, "autoreport_replyemail", getSetting('autoreport_replyemail'), 'email',0,100);
	PutFormData($f, $s, "autoreport_replyname", getSetting('autoreport_replyname'), 'text',0,100);

	PutFormData($f, $s,"usernamelength", getSetting('usernamelength'), "number", 0, 10);
	PutFormData($f, $s,"passwordlength", getSetting('passwordlength'), "number", 0, 10);
	PutFormData($f,$s,"checkpassword",(bool)getSetting('checkpassword'), "bool", 0, 1);
	if($IS_COMMSUITE){
		PutFormData($f, $s, "easycallmin", getSetting('easycallmin'), "number", 0, 10);
		PutFormData($f, $s, "easycallmax", getSetting('easycallmax'), "number", 0, 10);
	}

	for($i=0; $i < $maxphones; $i++){
		PutFormData($f, $s, "lockedphone" . $i, getSystemSetting('lockedphone' . $i, 0), "bool", 0, 1);
	}
	for($i=0; $i < $maxemails; $i++){
		PutFormData($f, $s, "lockedemail" . $i, getSystemSetting('lockedemail' . $i, 0), "bool", 0, 1);
	}
	for($i=0; $i < $maxsms; $i++){
		PutFormData($f, $s, "lockedsms" . $i, getSystemSetting('lockedsms' . $i, 0), "bool", 0, 1);
	}
	PutFormData($f, $s, "tokenlife", getSetting('tokenlife', 30), 'number', 0, 365);
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


function getSystemPriorities () {
	return array("1" => "Emergency",
				"2" => "Attendance",
				"3" => "General");
}

function fmt_name($obj, $name) {
	global $f, $s;
	if((isset($_GET['edittype']) && $_GET['edittype'] == $obj->id) || $obj->id == 'new') {
		return '<input type="text" name="jobtype[' . $obj->id . ']" width="100%" value="' . htmlentities($obj->name) . '">';
	} else {
		return $obj->name;
	}
}

function fmt_systempriority($obj, $name) {
	global $f, $s, $USER;
	if((isset($_GET['edittype']) && $_GET['edittype'] == $obj->id) || $obj->id == 'new') {
		$result =  '<select name="systempriority[' . $obj->id . ']">';
		foreach (getSystemPriorities() as $index => $name)
			$result .= '<option ' . ($obj->systempriority == $index ? "selected" : "") . ' value="' . $index . '">' . htmlentities($name) . '</option>';
		return $result;
	} else {
		$priorities = getSystemPriorities();
		return htmlentities($priorities[$obj->systempriority]);
	}
}

function fmt_timeslices($obj, $name) {
	global $f, $s;
	if((isset($_GET['edittype']) && $_GET['edittype'] == $obj->id) || $obj->id == 'new') {
		return '<input type="text" name="timeslices[' . $obj->id . ']" width="100%" value="' . $obj->timeslices . '">';
	} else {
		return $obj->timeslices;
	}
}

function fmt_edit($obj, $name) {
	global $f, $s;
	if(isset($_GET['edittype']) && $_GET['edittype'] == $obj->id)
		return '<div align="center">' . submit($f, $s, 'Save') . '</div>';
	if($obj->id == 'new')
		return '<div align="center">' . submit($f, $s, 'Add') . '</div>';
	else
		return '<div align="center"><a href="' . $_SERVER['SCRIPT_NAME'] . '?edittype=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="' . $_SERVER['SCRIPT_NAME'] . '?deletetype=' . $obj->priority . '" onclick="return confirmDelete();">Delete</a></div>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Systemwide Settings';

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow('Global System Settings');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">

<?
				if($IS_COMMSUITE) {
?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Job Type/Priorities:<br><? print help('Settings_JobTypes'); ?></th>
						<td class="bottomBorder">
							<table border="0" cellpadding="0" cellspacing="0" width="60%">
								<tr>
									<td>
							<?
								$types = DBFindMany('JobType', "from jobtype where deleted=0 order by systempriority, name");
								$types[] = $type = new JobType();
								$type->id = 'new';
								$type->timeslices = 100;
								$titles = array('name' => 'Type', 'systempriority' => "Service Level", 'timeslices' => "Throttle Level", 'edit' => '');
								$formatters = array('edit' => 'fmt_edit', 'name' => 'fmt_name', 'systempriority' => "fmt_systempriority",'timeslices' => "fmt_timeslices");
								showObjects($types,$titles,$formatters);
							?>

									</td>
								</tr>
							</table>
						</td>
					</tr>
<?
				}
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Customer Info:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width=100%>

						<tr>
							<td width="30%">Customer Display Name<? print help('Settings_CustDisplayName'); ?></td>
							<td><? NewFormItem($f, $s, 'custdisplayname', 'text', 50, 50);  ?></td>
						<tr>
<?
						if($IS_COMMSUITE){
?>
							<tr>
								<td>
									Survey URL<? print help('Settings_SurveyURL'); ?>
								</td>
								<td><? NewFormItem($f, $s, 'surveyurl', 'text', 60, 100);  ?></td>
							<tr>
<?
						}
?>
							<tr>
								<td width="30%">Default Local Area Code<? print help('Settings_DefaultLocalAreaCode'); ?></td>
								<td><? NewFormItem($f, $s, 'defaultareacode', 'text', 3,3);  ?></td>
							</tr>
							<tr>
								<td>Systemwide Alert Message<? print help('Settings_SystemwideAlert'); ?></td>
								<td><? NewFormItem($f, $s, 'alertmessage', 'textarea',44,4);  ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Job:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width=100%>
							<tr>
								<td>Retry Setting<? print help('Settings_RetrySetting'); ?></td>
								<td>
									<table border="0" cellpadding="2" cellspacing="0">
										<tr>
											<td>
								<?
									NewFormItem($f,$s,'retry','selectstart');
									NewFormItem($f,$s,'retry','selectoption',5,5);
									NewFormItem($f,$s,'retry','selectoption',10,10);
									NewFormItem($f,$s,'retry','selectoption',15,15);
									NewFormItem($f,$s,'retry','selectoption',30,30);
									NewFormItem($f,$s,'retry','selectoption',60,60);
									NewFormItem($f,$s,'retry','selectoption',90,90);
									NewFormItem($f,$s,'retry','selectoption',120,120);
									NewFormItem($f,$s,'retry','selectend');
								?>
											</td>
											<td>
												minutes to retry busy and unanswered phone numbers
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td>
									Disable Repeating Jobs<? print help('Settings_DisableRepeat'); ?>
								</td>
								<td>
									<table border="0" cellpadding="2" cellspacing="0">
										<tr>
											<td><? NewFormItem($f, $s, 'disablerepeat', 'checkbox'); ?></td>
											<td>This setting will prevent all scheduled repeating jobs from running.</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td>
									Default Caller ID Number<? print help('Settings_CallerID'); ?>
								</td>
								<td>
								<? NewFormItem($f, $s, 'callerid', 'text', 20);  ?>
								</td>
							</tr>
							<tr>
								<td  width="30%">Autoreport Email address<? print help('Settings_AutoreportEmailAddress'); ?></td>
								<td><? NewFormItem($f, $s, 'autoreport_replyemail', 'text', 60,100);  ?></td>
							</tr>
							<tr>
								<td>
									Autoreport Email Name<? print help('Settings_AutoreportEmailName'); ?>
								</td>
								<td>
								<? NewFormItem($f, $s, 'autoreport_replyname', 'text', 60,100);  ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				if($IS_COMMSUITE){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">EasyCall/<br>Call Me:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width=100%>
							<tr>
								<td width="30%">Minimum Extensions Length<? print help('Settings_MinimumExtensions'); ?></td>
								<td><? NewFormItem($f, $s, 'easycallmin', 'text', 3,3);  ?></td>
							</tr>
							<tr>
								<td width="30%">Maximum Extensions Length<? print help('Settings_MaximumExtensions'); ?></td>
								<td><? NewFormItem($f, $s, 'easycallmax', 'text', 3,3);  ?></td>
							</tr>
						</table>
					</td>
				</tr>
<?
				}
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Security:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width=100%>
							<tr>
								<td width="30%">Minimum Username Length<? print help('Settings_MinimumUsername'); ?></td>
								<td><? NewFormItem($f, $s, 'usernamelength', 'text', 3,3);  ?></td>
							</tr>
							<tr>
								<td width="30%">Minimum Password Length<? print help('Settings_MinimumPassword'); ?></td>
								<td><? NewFormItem($f, $s, 'passwordlength', 'text', 3,3);  ?></td>
							</tr>
							<tr>
								<td width="30%">Very Secure Passwords<? print help('Settings_VerySecurePasswords'); ?></td>
								<td><? NewFormItem($f,$s,'checkpassword','checkbox') ?></td>
							</tr>
						</table>
					</td>
				</tr>
<?
				if(getSystemSetting("_hasportal") && $USER->authorize('portalaccess')){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Portal:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width="100%">
								<tr>
									<td width="30%">Activation Code lifetime in days</td>
									<td><? NewFormItem($f, $s, "tokenlife", "text", 3); ?></td>
								</tr>
								<tr>
									<td colspan="2">
										<table border="0" cellpadding="3" cellspacing="1" width="30%">
											<tr>
												<th>&nbsp;</th>
												<?
												for($i=1; $i<= max($maxphones, $maxemails, $maxsms);$i++){
													?><th><?=$i?></th><?
												}
												?>
											</tr>
											
											<tr>
												<td align="left">Locked&nbsp;Phones</td>
<?
												for($i=0; $i<$maxphones; $i++){
													?>
														<td align="center"><? NewFormItem($f, $s, "lockedphone" . $i, "checkbox");?></td>
													<?
												}
?>
											</tr>
											<tr>
												<td align="left">Locked&nbsp;Emails</td>
<?
												for($i=0; $i<$maxemails; $i++){
													?>
														<td align="center"><? NewFormItem($f, $s, "lockedemail" . $i, "checkbox");?></td>
													<?
												}
?>
											</tr>
											<tr>
												<td align="left">Locked&nbsp;SMS</td>
<?
												for($i=0; $i<$maxsms; $i++){
													?>
														<td align="center"><? NewFormItem($f, $s, "lockedsms" . $i, "checkbox");?></td>
													<?
												}
?>
											</tr>
										</table>
									</td>
								</tr>
						</table>
					</td>
				</tr>
<?
				}
?>
			</table>
			<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>