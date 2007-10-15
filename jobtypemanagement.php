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


//check params
if (isset($_GET['deletetype'])) {
	$priority = DBSafe($_GET['deletetype']);
	$type = new Jobtype($priority);
	$count = QuickQuery("select count(*) from jobtype where systempriority = '" . $type->systempriority . "'");
	if($count > 1){
		QuickUpdate("update jobtype set deleted=1 where id = '$priority' and deleted=0");
		redirect();
	} else {
		error("You cannot delete that jobtype", "You must have at least one jobtype for each system priority");
	}
}


$systemprioritynames = array("1" => "Emergency",
							"2" => "High Priority",
							"3" => "General");
foreach($systemprioritynames as $index => $name){
	$types[$index] = DBFindMany('JobType', "from jobtype where deleted=0 and systempriority = '" . $index . "' order by name");
}
$maxhighpriorities = getSystemSetting("maxhighpriorities", 1);
$maxphones = getSystemSetting("maxphones", 4);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", $maxphones);
$maxcolumns = max($maxphones, $maxemails, $maxsms);
$jobtypeprefs = getJobTypePrefs();

/****************** main message section ******************/

$f = "setting";
$s = "main";
$reloadform = 0;


if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'addtype') || CheckFormSubmit($f, "new") || CheckFormSubmit($f, "new_high"))
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

				//submit changes
				foreach($systemprioritynames as $index => $name){
					foreach($types[$index] as $type){
						getJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms);
					}
				}
				if(CheckFormSubmit($f, "new_high")){
					getJobtypeForm($f, $s, NULL, $maxphones, $maxemails, $maxsms, $systempriority = 2);
				}
				if(CheckFormSubmit($f, "new")){
					getJobtypeForm($f, $s, NULL, $maxphones, $maxemails, $maxsms, $systempriority = 3);
				}
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
	if(count($types[2]) < $maxhighpriorities){
		putJobtypeForm($f, $s, null, $maxphones, $maxemails, $maxsms, $jobtypeprefs, $systempriority = 2);
	}
	putJobtypeForm($f, $s, null, $maxphones, $maxemails, $maxsms, $jobtypeprefs, $systempriority = 3);
}


$PAGE = "admin:jobtype";
$TITLE = "Job Type Manager";
include_once("nav.inc.php");
NewForm($f);
startWindow("Jobtypes");
buttons(submit($f, $s, "Save"));
foreach($systemprioritynames as $index => $name){
?>
	<br>
	<div><b><?=$name?><b></div>
		<table cellpadding="3" cellspacing="0" width="100%">
			<tr>
				<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Name</th>
				<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Info For Parents</th>
				<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Contact Preferences: </th>
				<th align="left" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">&nbsp;</th>
			</tr>
			<tr>
<?
				foreach($types[$index] as $type) {
					if($type->systempriority != $index)
						continue;
					displayJobtypeForm($f, $s, $type->id, $maxphones, $maxemails, $maxsms, $add = false);
				}
				if($index == 2 && count($types[2]) < $maxhighpriorities)
					displayJobtypeForm($f, $s, "_newhigh_", $maxphones, $maxemails, $maxsms, $add = "new_high");
				if($index == 3){
					displayJobtypeForm($f, $s, "_new_", $maxphones, $maxemails, $maxsms, $add = "new");
				}
?>
			</tr>
		</table>
<?
}
buttons();
endWindow();
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

function putJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms, $jobtypeprefs, $systempriority = 3){
	if($type == null){
		$type = new Jobtype();
		if($systempriority == 3){
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
	for($i=0; $i<$maxsms; $i++){
		PutFormData($f, $s, "jobtype" . $type->id . "sms" . $i, isset($jobtypeprefs[$type->id]["sms"][$i]) ? $jobtypeprefs[$type->id]["sms"][$i] :0 , "bool", 0, 1);
	}

}

function getJobtypeForm($f, $s, $type, $maxphones, $maxemails, $maxsms, $systempriority = 3){
	if($type == null){
		if($systempriority == 3){
			$jobtypeid = "_new_";
		} else if ($systempriority == 2){
			$jobtypeid = "_newhigh_";
		}
		$type = new Jobtype();
		$type->name = GetFormData($f, $s, "jobtypename" . $jobtypeid);
		$type->infoforparents = GetformData($f, $s, "jobtypedesc" . $jobtypeid);
		$type->systempriority = $systempriority;
		$type->priority = 10000 + QuickQuery("select max(priority) from jobtype where deleted=0");
		$type->timeslices = 450;
		$type->create();
		
	} else {
		if($type->name != "Emergency"){
			$type->name = GetFormData($f, $s, "jobtypename" . $type->id);
		}
		$type->infoforparents = GetFormData($f, $s, "jobtypedesc" . $type->id);
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

function displayJobtypeForm($f, $s, $jobtypeid, $maxphones, $maxemails, $maxsms, $add = false){
	$maxcolumns = max($maxphones, $maxemails, $maxsms);
?>
	<tr>
		<td width="10%" class="bottomBorder" >
			<? 
				if($jobtypeid+0){
					$type = new JobType($jobtypeid);
				}
				if(isset($type) && $type->systempriority == 1)
					echo $type->name;
				else
					NewFormItem($f, $s, "jobtypename" . $jobtypeid, "text", 20, 50);
			?>
		</td>
		<td class="bottomBorder" ><? NewFormItem($f, $s, "jobtypedesc" . $jobtypeid, "textarea", 40, 3);?></td>
		<td class="bottomBorder" >
			<table border="1">
				<tr>
					<th>&nbsp;</th>
<?
					for($i=0; $i < $maxcolumns; $i++){
						?><th><?=$i+1?></th><?
					}
?>
				</tr>
				<tr>
					<td width="10%" >Phone:</td>
<?
						for($i=0; $i < $maxcolumns; $i++){
							if($i < $maxphones){
								?><td><? NewFormItem($f, $s, "jobtype" . $jobtypeid . "phone" . $i, "checkbox", 0, 1);?> </td><?
							} else {
								?><td>&nbsp;</td><?
							}
						}
?>
				</tr>
				<tr>
					<td width="10%" >Email:</td>
<?
						for($i=0; $i < $maxcolumns; $i++){
							if($i < $maxemails){
								?><td><? NewFormItem($f, $s, "jobtype" . $jobtypeid . "email" . $i, "checkbox", 0, 1);?> </td><?
							} else {
								?><td>&nbsp;</td><?
							}
						}
?>
				</tr>
				<tr>
					<td width="10%" >SMS:</td>
<?
						for($i=0; $i < $maxcolumns; $i++){
							if($i < $maxsms){
								?><td><? NewFormItem($f, $s, "jobtype" . $jobtypeid . "sms" . $i, "checkbox", 0, 1);?> </td><?
							} else {
								?><td>&nbsp;</td><?
							}
						}
?>
				</tr>
			</table>
		</td>
<?
		if($add){
			?><td class="bottomBorder" ><?=submit($f,$add, "Add");?></td><?
		} else if($type->systempriority != 1) {
			?><td class="bottomBorder" ><?=button("Delete","if(confirmDelete()) window.location='?deletetype=$jobtypeid'");?></td><?
		} else {
			?><td class="bottomBorder" >&nbsp;</td><?
		}
?>
	</tr>
<?
}
?>