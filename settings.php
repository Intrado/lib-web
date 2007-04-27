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
include_once("obj/Customer.obj.php");


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
	$priority = DBSafe($_GET['deletetype']);
	while($next = movePriority($priority))
		$priority = $next;

	QuickUpdate("update jobtype set deleted=1 where priority = '$priority' and deleted=0");
	redirect();
}


if (isset($_GET['moveup'])) {
	movePriority($_GET['moveup'], false);
	redirect();
}
if (isset($_GET['movedn'])) {
	movePriority($_GET['movedn'], true);
	redirect();
}

function movePriority($priority, $down = true) {
	global $USER;
	$priority = 0 + $priority;
	$op = $down ? array('>','') : array('<','desc');
	$swap = QuickQueryRow("select id, priority from jobtype where priority $op[0] '$priority' and deleted =0 order by priority $op[1] limit 1");
	if ($swap) {
		QuickUpdate("update jobtype set priority = $swap[1] where priority = '$priority'");
		QuickUpdate("update jobtype set priority = '$priority' where id = $swap[0]");
		return $swap[1];
	}
	return false;
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
			QuickUpdate("update setting set value = '$value' where and name = '$name'");
	}
}


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
				if($types = $_POST['jobtype']) {
					foreach($types as $id => $name) {
						$name = DBSafe($name);
						$id = DBSafe($id);
						$systempriority = isset($_POST['systempriority'][$id]) ? 0 + $_POST['systempriority'][$id] : "3";
						$timeslices = isset($_POST['timeslices'][$id]) ? 0 + $_POST['timeslices'][$id] : "100";
						$timeslices = min(600,abs($timeslices));

						if($id == 'new' && $name) {
							$nextpri = 10000 + QuickQuery("select max(priority) from jobtype where deleted=0");
							$query = "insert into jobtype (name, priority, systempriority, timeslices) values ('$name','$nextpri', '$systempriority','$timeslices')";
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
					setSetting("hostname", $custname);
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

	$custname = getSystemSetting("customername");
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
	//do some custom stuff for the options
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_priority($obj, $name) {
	return '<div align="center">' . round($obj->priority / 10000) . '</div>';
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
		foreach ($USER->getCustomer()->getSystemPriorities() as $index => $name)
			$result .= '<option ' . ($obj->systempriority == $index ? "selected" : "") . ' value="' . $index . '">' . htmlentities($name) . '</option>';
		return $result;
	} else {
		$priorities = $USER->getCustomer()->getSystemPriorities(); //jjl
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
		return '<div align="center">' . submit($f, $s, 'save', 'save') . '</div>';
	if($obj->id == 'new')
		return '<div align="center">' . submit($f, $s, 'add', 'add') . '</div>';
	else
		return '<div align="center"><a href="' . $_SERVER['SCRIPT_NAME'] . '?edittype=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="' . $_SERVER['SCRIPT_NAME'] . '?deletetype=' . $obj->priority . '" onclick="return confirmDelete();">Delete</a></div>';
}

function fmt_move($obj, $name) {
	static $alt;
	$alt = !$alt;
	if($obj->id != 'new')
	return '<div align="center">' .
	'<a href="' . $_SERVER['SCRIPT_NAME'] . '?moveup=' . $obj->priority . '">' .
			'<img src="img/arrow_up_' . ($alt ? 'w' : 'g') . '.gif" border="0"></a>' .
	'<a href="' . $_SERVER['SCRIPT_NAME'] . '?movedn=' . $obj->priority . '">' .
			'<img src="img/arrow_down_' . ($alt ? 'w' : 'g') . '.gif" border="0"></a></div>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Systemwide Settings';

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'save', 'save'));
startWindow('Global System Settings');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
<?				
				if($IS_COMMSUITE) {
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Job Type/<br>Priorities:<br><? print help('Settings_JobTypes', NULL, 'grey'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="0" cellspacing="0" width="60%">
							<tr>
								<td>
						<?
							$types = DBFindMany('JobType', "from jobtype where deleted=0 order by priority");
							$types[] = $type = new JobType();
							$type->id = 'new';
							$type->priority = QuickQuery("select max(priority) from jobtype where deleted=0") + 10000;
							$type->timeslices = 100;
							$titles = array('priority' => 'Priority', 'name' => 'Type', 'systempriority' => "Service Level", 'timeslices' => "Throttle Level", 'edit' => '', 'move' => '');
							$formatters = array('priority' => 'fmt_priority', 'edit' => 'fmt_edit', 'move' => 'fmt_move', 'name' => 'fmt_name', 'systempriority' => "fmt_systempriority",'timeslices' => "fmt_timeslices");
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
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Customer Display Name:<br><? print help('Settings_CustDisplayName', NULL, 'grey'); ?>
					</th>
					<td><? NewFormItem($f, $s, 'custdisplayname', 'text', 20, 50);  ?></td>
				<tr>
<?
				if($IS_COMMSUITE){
?>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Survey URL:<br><? print help('Settings_SurveyURL', NULL, 'grey'); ?>
					</th>
					<td><? NewFormItem($f, $s, 'surveyurl', 'text', 30, 100);  ?></td>
				<tr>
<?
				}
?>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Retry Setting:<br><? print help('Settings_RetrySetting', NULL, 'grey'); ?></th>
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
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Disable Repeating Jobs:<br><? print help('Settings_DisableRepeat', NULL, 'grey'); ?>
					</th>
					<td>
						<table border="0" cellpadding="2" cellspacing="0">
							<tr>
								<td><? NewFormItem($f, $s, 'disablerepeat', 'checkbox'); ?></td>
								<td>This setting will prevent all scheduled repeating jobs from running.</td>
							</tr>
						</table>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Default Caller ID Number:<br><? print help('Settings_CallerID', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'callerid', 'text', 20);  ?>
					</td>
				</tr>

				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Default Local Area Code:<br><? print help('Settings_DefaultLocalAreaCode', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'defaultareacode', 'text', 3,3);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Autoreport Email address:<br><? print help('Settings_AutoreportEmailAddress', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'autoreport_replyemail', 'text', 30,100);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Autoreport Email Name:<br><? print help('Settings_AutoreportEmailName', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'autoreport_replyname', 'text', 30,100);  ?>
					</td>
				</tr>
<?
				if($IS_COMMSUITE){
?>
					<tr>
						<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
							Minimum Extensions Length:<br><? print help('Settings_MinimumExtensions', NULL, 'grey'); ?>
						</th>
						<td>
						<? NewFormItem($f, $s, 'easycallmin', 'text', 3,3);  ?>
						</td>
					</tr>
					<tr>
						<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
							Maximum Extensions Length:<br><? print help('Settings_MaximumExtensions', NULL, 'grey'); ?>
						</th>
						<td>
						<? NewFormItem($f, $s, 'easycallmax', 'text', 3,3);  ?>
						</td>
					</tr>
<?
				}
?>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Minimum Username Length:<br><? print help('Settings_MinimumUsername', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'usernamelength', 'text', 3,3);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Minimum Password Length:<br><? print help('Settings_MinimumPassword', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'passwordlength', 'text', 3,3);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Very Secure Passwords:<br><? print help('Settings_VerySecurePasswords', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f,$s,'checkpassword','checkbox') ?>
					</td>
				</tr>



				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Systemwide Alert Message:<br><? print help('Settings_SystemwideAlert', NULL, 'grey'); ?>
					</th>
					<td>
						<? NewFormItem($f, $s, 'alertmessage', 'textarea',50);  ?>
					</td>
				</tr>
			</table>
			<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>