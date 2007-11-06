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


if(isset($_GET['clear'])){
	unset($_SESSION['jobtypemanagement']['radio']);
	redirect();
}

$systemprioritynames = array("1" => "Emergency",
							"2" => "High Priority",
							"3" => "General");

foreach($systemprioritynames as $index => $name){
	$types[$index] = DBFindMany('JobType', "from jobtype where deleted=0 and systempriority = '" . $index . "' and not issurvey order by name");
}
$surveytypes = DBFindMany('JobType', "from jobtype where deleted=0 and systempriority = '3' and issurvey order by name");
$maxphones = getSystemSetting("maxphones", 4);
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

		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(QuickQuery("select count(*) from jobtype where id != '" . DBSafe(CheckFormSubmit($f, "delete")) . "' and issurvey and not deleted") < 1){
			error("You must have at least one survey job type");
		} else if(QuickQuery("select count(*) from userjobtypes where jobtypeid = '" . DBSafe(CheckFormSubmit($f, 'delete')) . "'")){
			error("A user is still restricted to that job type", "Please remove the restriction if you would like to delete that job type");
		} else {

			foreach($systemprioritynames as $index => $name){
				foreach($types[$index] as $type){
					if(CheckFormSubmit($f, 'delete') !== false &&
						CheckFormSubmit($f, 'delete') == $type->id){
						$type->deleted = 1;
						$type->update();
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
			redirect();
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


$PAGE = "admin:jobtype";
$TITLE = "Job Type Manager";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Save"), submit($f, "add", "Add a Job Type"));
startWindow("Job Types");
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
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Info For Parents</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Contact Preferences</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">&nbsp;</th>
	</tr>

<?
	foreach($types[$index] as $type) {
		if($type->systempriority != $index)
			continue;
		displayJobtypeForm($f, $s, $type->id, $maxphones, $maxemails, $maxsms, false);
	}
?>
<?
}
?>
	<tr class="listheader">
		<th align="left" colspan="4">Surveys<th>
	<tr>
	<tr>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Name</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Info For Parents</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Contact Preferences</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">&nbsp;</th>
	</tr>
<?
	foreach($surveytypes as $surveytype) {
		displayJobtypeForm($f, $s, $surveytype->id, $maxphones, $maxemails, $maxsms, false);
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
	PutFormData($f, $s, "jobtypename" . $type->id, $type->name, "text", 0, 50);
	PutFormData($f, $s, "jobtypedesc" . $type->id, $type->infoforparents, "text", 0, 255);
	for($i=0; $i<$maxphones; $i++){
		PutFormData($f, $s, "jobtype" . $type->id . "phone" . $i, isset($jobtypeprefs[$type->id]["phone"][$i]) ? $jobtypeprefs[$type->id]["phone"][$i] : 0, "bool", 0, 1);
	}
	for($i=0; $i<$maxemails; $i++){
		PutFormData($f, $s, "jobtype" . $type->id . "email" . $i, isset($jobtypeprefs[$type->id]["email"][$i]) ? $jobtypeprefs[$type->id]["email"][$i] : 0, "bool", 0, 1);
	}
	if(getSystemSetting("_hassms")){
		if(!$type->issurvey){
			for($i=0; $i<$maxsms; $i++){
				PutFormData($f, $s, "jobtype" . $type->id . "sms" . $i, isset($jobtypeprefs[$type->id]["sms"][$i]) ? $jobtypeprefs[$type->id]["sms"][$i] :0 , "bool", 0, 1);
			}
		}
	}

}

function getJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms){
	if($type->name != "Emergency"){
		$type->name = GetFormData($f, $s, "jobtypename" . $type->id);
	}
	$type->infoforparents = GetFormData($f, $s, "jobtypedesc" . $type->id);
	$type->update();

	for($i=0; $i<$maxphones; $i++){
		QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
					values ('" . $type->id . "','phone','" . $i . "','"
					. DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "phone" . $i)) . "')
					on duplicate key update
					jobtypeid = '" . $type->id . "',
					type = 'phone',
					sequence = '" . $i . "',
					enabled = '" . DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "phone" . $i)) . "'");
	}
	for($i=0; $i<$maxemails; $i++){
		QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
					values ('" . $type->id . "','email','" . $i . "','"
					. DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "email" . $i)) . "')
					on duplicate key update
					jobtypeid = '" . $type->id . "',
					type = 'email',
					sequence = '" . $i . "',
					enabled = '" . DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "email" . $i)) . "'");
	}
	if(getSystemSetting("_hassms")){
		if(!$type->issurvey){
			for($i=0; $i<$maxsms; $i++){
				QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
							values ('" . $type->id . "','sms','" . $i . "','"
							. DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "sms" . $i)) . "')
							on duplicate key update
							jobtypeid = '" . $type->id . "',
							type = 'sms',
							sequence = '" . $i . "',
							enabled = '" . DBSafe(GetFormData($f, $s, "jobtype" . $type->id . "sms" . $i)) . "'");
			}
		}
	}

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
		<td class="bottomBorder" >
			<table  cellpadding="0" cellspacing="0" width="100%">
				<tr class="listheader">
					<th align="left">Contact Type</th>
<?
					for($i=0; $i < $maxcolumns; $i++){
						?><th><?=$i+1?></th><?
					}
?>
				</tr>
				<tr>
					<td class="bottomBorder">Phone</td>
<?
						for($i=0; $i < $maxcolumns; $i++){
							?><td class="bottomBorder" align="center"><?
							if($i < $maxphones){
								echo NewFormItem($f, $s, "jobtype" . $jobtypeid . "phone" . $i, "checkbox", 0, 1);
							} else {
								echo "&nbsp;";
							}
							?></td><?
						}
?>
				</tr>
				<tr>
					<td class="bottomBorder">Email</td>
<?
						for($i=0; $i < $maxcolumns; $i++){
							?><td class="bottomBorder" align="center"><?
							if($i < $maxemails){
								echo NewFormItem($f, $s, "jobtype" . $jobtypeid . "email" . $i, "checkbox", 0, 1);
							} else {
								echo "&nbsp;";
							}
							?></td><?
						}
?>
				</tr>
<?
				if(getSystemSetting("_hassms")){
					if(!((isset($type) && $type->issurvey) || $jobtypeid == "_newsurvey_")){
?>
					<tr>
						<td class="bottomBorder">SMS</td>
<?
							for($i=0; $i < $maxcolumns; $i++){
								?><td class="bottomBorder" align="center"><?
								if($i < $maxsms){
									echo NewFormItem($f, $s, "jobtype" . $jobtypeid . "sms" . $i, "checkbox", 0, 1);
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
			?><td class="bottomBorder" ><?=submit($f, "delete","Delete", $jobtypeid) ?></td><?
		} else {
			?><td class="bottomBorder" >&nbsp;</td><?
		}
?>
	</tr>
<?
}
?>