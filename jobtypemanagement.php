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


if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'addtype') || CheckFormSubmit($f, "new") 
	|| CheckFormSubmit($f, "new_high") || CheckFormSubmit($f, "newsurvey") 
	|| CheckFormSubmit($f, "delete") !== false )
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
				// new jobtype for high priority
				if($IS_COMMSUITE){
					getJobtypeForm($f, $s, NULL, $maxphones, $maxemails, $maxsms, 2);
				}
				// new jobtype for general priority
				getJobtypeForm($f, $s, NULL, $maxphones, $maxemails, $maxsms, 3);

				foreach($surveytypes as $surveytype){
					if(CheckFormSubmit($f, 'delete') !== false &&
						CheckFormSubmit($f, 'delete') == $surveytype->id){
						$surveytype->deleted = 1;
						$surveytype->update();
					} else {
						getJobtypeForm($f, $s, $surveytype, $maxphones, $maxemails, $maxsms);
					}
				}
				
				getJobtypeForm($f, $s, NULL, $maxphones, $maxemails, $maxsms, 3, true);

			$_SESSION['jobtypemanagement']['radio'] = GetFormData($f, $s, "joborsurvey");
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
	PutFormData($f, $s, "joborsurvey", isset($_SESSION['jobtypemanagement']['radio']) ? $_SESSION['jobtypemanagement']['radio'] : "job");

	foreach($surveytypes as $surveytype){
		putJobtypeForm($f, $s, $surveytype, $maxphones, $maxemails, $maxsms, $jobtypeprefs, 3);
	}
	putJobtypeForm($f, $s, null, $maxphones, $maxemails, $maxsms, $jobtypeprefs, 3, true);
	if($IS_COMMSUITE){
		putJobtypeForm($f, $s, null, $maxphones, $maxemails, $maxsms, $jobtypeprefs, 2);
	}
	putJobtypeForm($f, $s, null, $maxphones, $maxemails, $maxsms, $jobtypeprefs, 3);
	
}


$PAGE = "admin:jobtype";
$TITLE = "Job Type Manager";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Save"));
startWindow("Jobtypes");
?>

<table cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Name</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Info For Parents</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Contact Preferences</th>
		<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">&nbsp;</th>
	</tr>
<?
foreach($systemprioritynames as $index => $name){
?>
	<tr>
		<th align="left"><?=$name?><th>
	</tr>
	
<?
		foreach($types[$index] as $type) {
			if($type->systempriority != $index)
				continue;
			displayJobtypeForm($f, $s, $type->id, $maxphones, $maxemails, $maxsms, false);
		}
		if($IS_COMMSUITE && $index == 2)
			displayJobtypeForm($f, $s, "_newhigh_", $maxphones, $maxemails, $maxsms,"new_high");
		if($index == 3){
			displayJobtypeForm($f, $s, "_new_", $maxphones, $maxemails, $maxsms, "new");
		}
?>
<?
}
?>
	<tr>
		<th align="left">Surveys<th>
	<tr>
<?
				foreach($surveytypes as $surveytype) {
					displayJobtypeForm($f, $s, $surveytype->id, $maxphones, $maxemails, $maxsms, false);
				}
				displayJobtypeForm($f, $s, "_newsurvey_", $maxphones, $maxemails, $maxsms, "newsurvey");
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

function putJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms, $jobtypeprefs, $systempriority = 3, $issurvey = false){
	if($type == null){
		$type = new Jobtype();
		if($issurvey){
			$type->id = "_newsurvey_";
		} else if($systempriority == 3){
			$type->id = "_new_";
		} else if ($systempriority == 2){
			$type->id = "_newhigh_";
		}
	}
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

function getJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms, $systempriority = 3, $issurvey = false){
	if($type == null){
		if($issurvey){
			$jobtypeid = "_newsurvey_";
		} else if($systempriority == 3){
			$jobtypeid = "_new_";
		} else if ($systempriority == 2){
			$jobtypeid = "_newhigh_";
		}
		$type = new Jobtype();
		$name = GetFormData($f, $s, "jobtypename" . $jobtypeid);
		if($name == "")
			return;
		$type->name = $name;
		$type->infoforparents = GetformData($f, $s, "jobtypedesc" . $jobtypeid);
		$type->systempriority = $systempriority;
		if($systempriority == 2){
			$type->timeslices = 0;
		} else {
			$type->timeslices = 450;
		}
		if($issurvey == true){
			$type->issurvey = 1;
		}
		$type->create();

	} else {
		if($type->name != "Emergency"){
			$type->name = GetFormData($f, $s, "jobtypename" . $type->id);
		}
		$type->infoforparents = GetFormData($f, $s, "jobtypedesc" . $type->id);
		if($issurvey == true){
			$type->issurvey = 1;
		}
		$type->update();
		$jobtypeid = $type->id;
	}
	for($i=0; $i<$maxphones; $i++){
		QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
					values ('" . $type->id . "','phone','" . $i . "','"
					. DBSafe(GetFormData($f, $s, "jobtype" . $jobtypeid . "phone" . $i)) . "')
					on duplicate key update
					jobtypeid = '" . $type->id . "',
					type = 'phone',
					sequence = '" . $i . "',
					enabled = '" . DBSafe(GetFormData($f, $s, "jobtype" . $jobtypeid . "phone" . $i)) . "'");
	}
	for($i=0; $i<$maxemails; $i++){
		QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
					values ('" . $type->id . "','email','" . $i . "','"
					. DBSafe(GetFormData($f, $s, "jobtype" . $jobtypeid . "email" . $i)) . "')
					on duplicate key update
					jobtypeid = '" . $type->id . "',
					type = 'email',
					sequence = '" . $i . "',
					enabled = '" . DBSafe(GetFormData($f, $s, "jobtype" . $jobtypeid . "email" . $i)) . "'");
	}
	if(getSystemSetting("_hassms")){
		if(!$type->issurvey){
			for($i=0; $i<$maxsms; $i++){
				QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
							values ('" . $type->id . "','sms','" . $i . "','"
							. DBSafe(GetFormData($f, $s, "jobtype" . $jobtypeid . "sms" . $i)) . "')
							on duplicate key update
							jobtypeid = '" . $type->id . "',
							type = 'sms',
							sequence = '" . $i . "',
							enabled = '" . DBSafe(GetFormData($f, $s, "jobtype" . $jobtypeid . "sms" . $i)) . "'");
			}
		}
	}

}

function displayJobtypeForm($f, $s, $jobtypeid, $maxphones, $maxemails, $maxsms, $add = false){
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
			<table  cellpadding="3" cellspacing="1" width="100%">
				<tr class="listheader">
					<th>&nbsp;</th>
<?
					for($i=0; $i < $maxcolumns; $i++){
						?><th><?=$i+1?></th><?
					}
?>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
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
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
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
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">SMS:</th>
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
		if($add){
			?><td class="bottomBorder" ><?=submit($f,$add, "Add");?></td><?
		} else if(($type->systempriority == 2 && $IS_COMMSUITE) || $type->systempriority == 3) {
			?><td class="bottomBorder" ><?=submit($f, "delete","Delete", $jobtypeid) ?></td><?
		} else {
			?><td class="bottomBorder" >&nbsp;</td><?
		}
?>
	</tr>
<?
}
?>