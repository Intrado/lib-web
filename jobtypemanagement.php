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
include_once("obj/JobType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}


$systemprioritynames = array("1" => "Emergency",
							"2" => "High Priority",
							"3" => "General");

foreach($systemprioritynames as $index => $name){
	$types[$index] = DBFindMany('JobType', "from jobtype where deleted=0 and systempriority = '" . $index . "' and not issurvey order by name");
}
$surveytypes = getSystemSetting('_hassurvey', true) ? DBFindMany('JobType', "from jobtype where deleted=0 and systempriority = '3' and issurvey order by name") : array();
$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);
$maxcolumns = max($maxphones, $maxemails, $maxsms);
$jobtypeprefs = getJobTypePrefs();

/****************** main message section ******************/

$f = "setting";
$s = "main";
$reloadform = 0;


if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "add") || CheckFormSubmit($f, "delete") !== false )
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

		foreach($systemprioritynames as $index => $name)
			foreach($types[$index] as $type) {
				TrimFormData($f, $s, "jobtypedesc" . $type->id);
				TrimFormData($f, $s, "jobtypename" . $type->id);
			}

		foreach($surveytypes as $surveytype){
			TrimFormData($f, $s, "jobtypedesc" . $surveytype->id);
			TrimFormData($f, $s, "jobtypename" . $surveytype->id);
		}

		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(QuickQuery("select count(*) from jobtype where id != '" . DBSafe(CheckFormSubmit($f, "delete")) . "' and issurvey and not deleted") < 1){
			error("You must have at least one survey job type");
		} else if(QuickQuery("select count(*) from userjobtypes where jobtypeid = '" . DBSafe(CheckFormSubmit($f, 'delete')) . "'")){
			error("A user is still restricted to that job type", "Please remove the restriction if you would like to delete that job type");
		} else if($error = checkNames($f, $s, $systemprioritynames, $types, $surveytypes)){
			error("There are duplicate job type names", $error);
		} else {

			foreach($systemprioritynames as $index => $name){
				foreach($types[$index] as $type){
					if(CheckFormSubmit($f, 'delete') !== false &&
						CheckFormSubmit($f, 'delete') == $type->id){
						if(($type->systempriority == 2 && $IS_COMMSUITE) || $type->systempriority == 3) {
							$type->deleted = 1;
							$type->update();
						}
					} else {
						getJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms);
					}
				}
			}
			foreach($surveytypes as $surveytype){
				if(CheckFormSubmit($f, 'delete') !== false &&
					CheckFormSubmit($f, 'delete') == $surveytype->id){
					$surveytype->deleted = 1;
					$surveytype->update();
				} else {
					getJobtypeForm($f, $s, $surveytype, $maxphones, $maxemails, $maxsms);
				}
			}
			if(CheckFormSubmit($f, "add"))
				redirect("jobtypeaddition.php");
			redirect("settings.php");
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	foreach($systemprioritynames as $index => $name){
		foreach($types[$index] as $type){
			putJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms, $jobtypeprefs);
		}
	}
	foreach($surveytypes as $surveytype){
		putJobtypeForm($f, $s, $surveytype, $maxphones, $maxemails, $maxsms, $jobtypeprefs, 3);
	}

}


$PAGE = "admin:settings";
$TITLE = "Job Type Manager";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Done"), submit($f, "add", "Create New Job Type"));
startWindow("Job Types" . help("JobType_Manager"));
?>

<table cellpadding="0" cellspacing="0" width="100%">
<?
foreach($systemprioritynames as $index => $name){
?>
	<tr class="listheader">
		<th align="left" colspan="4"><?=$name?><th>
	</tr>
	<tr>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Name</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Display Information</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Default Notification Setting</th>
		<th align="left" class="windowRowHeader bottomBorder" colspan=100 valign="top" style="padding-top: 6px;">&nbsp;</th>
	</tr>

<?
	foreach($types[$index] as $type) {
		if($type->systempriority != $index)
			continue;
		displayJobtypeForm($f, $s, $type->id, $maxphones, $maxemails, $maxsms, false);
	}
}
if (getSystemSetting('_hassurvey', true)) {
?>

	<tr class="listheader">
		<th align="left" colspan="4">Surveys<th>
	<tr>
	<tr>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Name</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Display Information</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Default Notification Setting</th>
		<th align="left" class="windowRowHeader bottomBorder" colspan=100 valign="top" style="padding-top: 6px;">&nbsp;</th>
	</tr>
<?
	foreach($surveytypes as $surveytype) {
		displayJobtypeForm($f, $s, $surveytype->id, $maxphones, $maxemails, $maxsms, false);
	}
}
?>
</table>
<?
endWindow();
buttons();
endForm();
include("navbottom.inc.php");



////////////////////////////////////////////////////////////////////////////////
// Funcitons
////////////////////////////////////////////////////////////////////////////////

//Check names of job types to make sure unique
function checkNames($f, $s, $systemprioritynames, $types, $surveytypes){
	$errors = array();
	$namecount = array();
	$names = array();
	foreach($systemprioritynames as $index => $name){
		foreach($types[$index] as $type){
			$name = GetFormData($f, $s, "jobtypename" . $type->id);
			$lcname = strtolower($name);
			if(!isset($namecount[$lcname]))
				$namecount[$lcname] = 0;
			$namecount[$lcname]++;
			$names[] = $name;
		}
	}
	foreach($surveytypes as $surveytype){
		$name = GetFormData($f, $s, "jobtypename" . $surveytype->id);
		$lcname = strtolower($name);
		if(!isset($namecount[$lcname]))
			$namecount[$lcname] = 0;
		$namecount[$lcname]++;
		$names[] = $name;
	}
	foreach($names as $name){
		if($namecount[strtolower($name)] > 1 && !isset($errors[strtolower($name)]))
			$errors[strtolower($name)] = $name;
	}
	return $errors;
}

//fetches all jobtypeprefs and returns 3 dimensional array
//builds by jobtypeid, type, sequence
function getJobTypePrefs(){
	$query = "Select jobtypeid, type, sequence, enabled from jobtypepref";
	$res = Query($query);
	$jobtypes = array();
	while($row = DBGetRow($res)){
		if(!isset($jobtypes[$row[0]]))
			$jobtypes[$row[0]] = array();
		if(!isset($jobtypes[$row[0]][$row[1]]))
			$jobtypes[$row[0]][$row[1]] = array();
		if(!isset($jobtypes[$row[0]][$row[1]][$row[2]]))
			$jobtypes[$row[0]][$row[1]][$row[2]] = array();
		$jobtypes[$row[0]][$row[1]][$row[2]] = $row[3];
	}
	return $jobtypes;

}

function putJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms, $jobtypeprefs){
	global $IS_COMMSUITE;
	PutFormData($f, $s, "jobtypename" . $type->id, $type->name, "text", 0, 50, true);
	PutFormData($f, $s, "jobtypedesc" . $type->id, $type->info, "text", 0, 255, true);
	for($i=0; $i<$maxphones; $i++){
		PutFormData($f, $s, "jobtype" . $type->id . "phone" . $i, isset($jobtypeprefs[$type->id]["phone"][$i]) ? $jobtypeprefs[$type->id]["phone"][$i] : 0, "bool", 0, 1);
	}
	for($i=0; $i<$maxemails; $i++){
		PutFormData($f, $s, "jobtype" . $type->id . "email" . $i, isset($jobtypeprefs[$type->id]["email"][$i]) ? $jobtypeprefs[$type->id]["email"][$i] : 0, "bool", 0, 1);
	}
	if(!$IS_COMMSUITE && getSystemSetting("_hassms")){
		if(!$type->issurvey){
			for($i=0; $i<$maxsms; $i++){
				PutFormData($f, $s, "jobtype" . $type->id . "sms" . $i, isset($jobtypeprefs[$type->id]["sms"][$i]) ? $jobtypeprefs[$type->id]["sms"][$i] :0 , "bool", 0, 1);
			}
		}
	}

}

function getJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms){
	global $IS_COMMSUITE;
	if($type->name != "Emergency"){
		$type->name = GetFormData($f, $s, "jobtypename" . $type->id);
	}
	$type->info = GetFormData($f, $s, "jobtypedesc" . $type->id);
	$type->update();
	QuickUpdate("Begin");
	QuickUpdate("delete from jobtypepref where jobtypeid = '" . $type->id . "'");
	$values = array();
	for($i=0; $i<$maxphones; $i++){
		$values[] = "('" . $type->id . "','phone','" . $i . "','"
					. DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "phone" . $i)) . "')";
	}
	for($i=0; $i<$maxemails; $i++){
		$values[] = "('" . $type->id . "','email','" . $i . "','"
					. DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "email" . $i)) . "')";
	}
	if(!$IS_COMMSUITE && getSystemSetting("_hassms")){
		if(!$type->issurvey){
			for($i=0; $i<$maxsms; $i++){
				$values[] = "('" . $type->id . "','sms','" . $i . "','"
							. DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "sms" . $i)) . "')";
			}
		}
	}
	QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
					values " . implode(",", $values));
	QuickUpdate("Commit");
}

function displayJobtypeForm($f, $s, $jobtypeid, $maxphones, $maxemails, $maxsms){
	global $IS_COMMSUITE;
	$maxcolumns = max($maxphones, $maxemails, $maxsms);
?>
	<tr>
		<td class="bottomBorder" >
			<?
				if($jobtypeid+0){
					$type = new JobType($jobtypeid);
				}
				if(isset($type) && $type->systempriority == 1)
					echo $type->name;
				else {
					if(!isset($type)){
						echo "New: ";
					}
					NewFormItem($f, $s, "jobtypename" . $jobtypeid, "text", 20, 50);
				}
			?>
		</td>
		<td class="bottomBorder" ><? NewFormItem($f, $s, "jobtypedesc" . $jobtypeid, "textarea", 20, 3);?></td>
		<td class="bottomBorder">
			<table  cellpadding="0" cellspacing="0" width="100%">
				<tr class="listheader">
					<th align="left">&nbsp;</th>
<?
					for($i=0; $i < $maxcolumns; $i++){
						?><th><?=$i+1?></th><?
					}
?>
				</tr>
				<tr>
					<th class="bottomBorder">Phone</th>
<?
						for($i=0; $i < $maxcolumns; $i++){
							?><td class="bottomBorder" align="center"><?
							if($i < $maxphones){
								destination_label_popup("phone", $i, $f, $s, "jobtype" . $jobtypeid . "phone" . $i);
							} else {
								echo "&nbsp;";
							}
							?></td><?
						}
?>
				</tr>
				<tr>
					<th class="bottomBorder">Email</th>
<?
						for($i=0; $i < $maxcolumns; $i++){
							?><td class="bottomBorder" align="center"><?
							if($i < $maxemails){
								destination_label_popup("email", $i, $f, $s, "jobtype" . $jobtypeid . "email" . $i);
							} else {
								echo "&nbsp;";
							}
							?></td><?
						}
?>
				</tr>
<?
				if(!$IS_COMMSUITE && getSystemSetting("_hassms")){
					if(!((isset($type) && $type->issurvey) || $jobtypeid == "_newsurvey_")){
?>
					<tr>
						<th class="bottomBorder">SMS</th>
<?
							for($i=0; $i < $maxcolumns; $i++){
								?><td class="bottomBorder" align="center"><?
								if($i < $maxsms){
									destination_label_popup("sms", $i, $f, $s, "jobtype" . $jobtypeid . "sms" . $i);
								} else {
									echo "&nbsp;";
								}
								?></td><?
							}
?>
					</tr>
<?
					}
				}
?>
			</table>
		</td>
<?
		if(($type->systempriority == 2 && $IS_COMMSUITE) || $type->systempriority == 3) {
			?><td colspan=100 class="bottomBorder" ><?=button("Delete", "if(confirmDelete()) submitForm('" . $f . "','delete','" . $jobtypeid. "');")?></td><?
		} else {
			?><td colspan=100 class="bottomBorder" >&nbsp;</td><?
		}
?>
	</tr>
<?
}
?>