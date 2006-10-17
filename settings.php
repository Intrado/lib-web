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

	QuickUpdate("update jobtype set deleted=1 where customerid = $USER->customerid and priority = '$priority' and deleted=0");
	redirect();
}


if($priority = $_GET['moveup'] + $_GET['movedn']) {
	movePriority($priority, $_GET['movedn']);
	redirect();
}

function movePriority($priority, $down = true) {
	global $USER;
	$op = $down ? array('>','') : array('<','desc');
	$swap = QuickQueryRow("select id, priority from jobtype where customerid = $USER->customerid and priority $op[0] '$priority' and deleted =0 order by priority $op[1] limit 1");
	if ($swap) {
		QuickUpdate("update jobtype set priority = $swap[1] where customerid = $USER->customerid and priority = '$priority'");
		QuickUpdate("update jobtype set priority = '$priority' where id = $swap[0]");
		return $swap[1];
	}
	return false;
}

function getSetting($name) {
	global $USER;
	$name = DBSafe($name);
	return QuickQuery("select value from setting where customerid = $USER->customerid and name = '$name'");
}

function setSetting($name, $value) {
	global $USER;
	$old = getSetting($name);
	$name = DBSafe($name);
	$value = DBSafe($value);
	if($old === false) {
		QuickUpdate("insert into setting (name, value, customerid) values ('$name', '$value', $USER->customerid)");
	} else {
		if($value === '' || $value === NULL)
			QuickUpdate("delete from setting where customerid = $USER->customerid and name = '$name'");
		elseif($value != $old)
			QuickUpdate("update setting set value = '$value' where customerid = $USER->customerid and name = '$name'");
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
		} else {
			//check the parsing

			if (count($errors) > 0) {
				error('There was an error parsing the setting', implode("",$errors));
			} else {
				//submit changes
				//TODO check that the setting->userid == user->id so that there is no chance of hijacking
				if ($setting->id && !customerOwns("setting",$setting->id)) {
					die("Unauthorized");
				}

				if($types = $_POST['jobtype']) {
					foreach($types as $id => $name) {
						$name = DBSafe($name);
						$systempriority = DBSafe((isset($_POST['systempriority'][$id]) ? $_POST['systempriority'][$id] : "3"));
						if($id == 'new' && $name)
							QuickUpdate("insert into jobtype (name, priority, systempriority, customerid) values ('$name', " . (QuickQuery("select max(priority) from jobtype where customerid = $USER->customerid and deleted=0") + 10000) . ", '$systempriority', $USER->customerid)");
						else
							QuickUpdate("update jobtype set name = '$name' , systempriority='$systempriority' where id = $id");
					}
				}

				setSetting('retry', GetFormData($f, $s, 'retry'));
				setSetting('callerid', Phone::parse(GetFormData($f, $s, 'callerid')));

				setSetting('defaultareacode', GetFormData($f, $s, 'defaultareacode'));


				setSetting('disablerepeat', GetFormData($f, $s, 'disablerepeat'));
				setSetting('alertmessage', GetFormData($f, $s, 'alertmessage'));

				setSetting('autoreport_replyemail', GetFormData($f, $s, 'autoreport_replyemail'));
				setSetting('autoreport_replyname', GetFormData($f, $s, 'autoreport_replyname'));

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

	PutFormData($f,$s,"retry",getSetting('retry'),"number","5","240");
	PutFormData($f, $s, "callerid", Phone::format(getSetting('callerid')), 'phone', 0, 20);

	PutFormData($f, $s, "defaultareacode", getSetting('defaultareacode'), 'number',200,999);


	PutFormData($f, $s, "disablerepeat", getSetting('disablerepeat'), 'bool');
	PutFormData($f, $s, "alertmessage", getSetting('alertmessage'), 'text',0,255);


	PutFormData($f, $s, "autoreport_replyemail", getSetting('autoreport_replyemail'), 'email',0,100);
	PutFormData($f, $s, "autoreport_replyname", getSetting('autoreport_replyname'), 'text',0,100);


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
	if($_GET['edittype'] == $obj->id || $obj->id == 'new') {
		return '<input type="text" name="jobtype[' . $obj->id . ']" width="100%" value="' . htmlentities($obj->name) . '">';
	} else {
		return $obj->name;
	}
}

function fmt_systempriority($obj, $name) {
	global $f, $s, $USER;
	if($_GET['edittype'] == $obj->id || $obj->id == 'new') {
		$result =  '<select name="systempriority[' . $obj->id . ']">';
		foreach ($USER->getCustomer()->getSystemPriorities() as $index => $name)
			$result .= '<option ' . ($obj->systempriority == $index ? "selected" : "") . ' value="' . $index . '">' . htmlentities($name) . '</option>';
		return $result;
	} else {
		$priorities = $USER->getCustomer()->getSystemPriorities();
		return htmlentities($priorities[$obj->systempriority]);
	}
}
function fmt_edit($obj, $name) {
	global $f, $s;
	if($_GET['edittype'] == $obj->id)
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
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Job Type/<br>Priorities:<br><? print help('Settings_JobTypes', NULL, 'grey'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="0" cellspacing="0" width="60%">
							<tr>
								<td>
						<?
							$types = DBFindMany('JobType', "from jobtype where customerid = $USER->customerid and deleted=0 order by priority");
								$types[] = $type = new JobType();
								$type->id = 'new';
								$type->priority = QuickQuery("select max(priority) from jobtype where customerid = $USER->customerid and deleted=0") + 10000;
							showObjects($types,array('priority' => 'Priority', 'name' => 'Type', 'systempriority' => "Service Level", 'edit' => '', 'move' => ''),
								array('priority' => 'fmt_priority', 'edit' => 'fmt_edit', 'move' => 'fmt_move', 'name' => 'fmt_name', 'systempriority' => "fmt_systempriority"));
						?>

								</td>
							</tr>
						</table>
					</td>
				</tr>
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
						Disable Repeating Jobs<br><? print help('Settings_DisableRepeat', NULL, 'grey'); ?>
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
						Default Caller ID Number<br><? print help('Settings_CallerID', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'callerid', 'text', 20);  ?>
					</td>
				</tr>

				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Default Local Area Code<br><? print help('Settings_DefaultLocalAreaCode', NULL, 'grey'); ?>
					</th>
					<td>
					<? NewFormItem($f, $s, 'defaultareacode', 'text', 3,3);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Autoreport Email address
					</th>
					<td>
					<? NewFormItem($f, $s, 'autoreport_replyemail', 'text', 30,100);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Autoreport Email Name
					</th>
					<td>
					<? NewFormItem($f, $s, 'autoreport_replyname', 'text', 30,100);  ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">
						Systemwide Alert Message
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