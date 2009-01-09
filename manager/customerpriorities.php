<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");

if (!$MANAGERUSER->authorized("editpriorities"))
	exit("Not Authorized");
	
	
if (isset($_GET['id'])) {
	$_SESSION['currentid']= $_GET['id']+0;
	redirect();
}
if(isset($_SESSION['currentid'])) {
	$currentid = $_SESSION['currentid'];
	$custquery = Query("select s.dbhost, s.dbusername, s.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custinfo = mysql_fetch_row($custquery);
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if(!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
}


//////////////////////////////////////////
// Data Handling
//////////////////////////////////////////

$reload = 0;
$refresh = 0;
$result = Query("select id, name, systempriority, issurvey from jobtype where deleted = 0 order by systempriority, issurvey, name", $custdb);
$jobtypes = array();
while($row = DBGetRow($result, true)){
	$jobtypes[] = $row;
}

$error = 0;
$f = "form";
$s = "priorities";

if(CheckFormSubmit($f, 'new')) {
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			if(GetFormData($f, $s, 'newname') != ""){
				$name = DBSafe(GetFormData($f, $s, 'newname'));
				QuickUpdate("insert into jobtype(name, systempriority, issurvey) values
							('$name', '2', '0')", $custdb);
				redirect();
			} else {
				error("You cannot add a job type that has a blank name");
			}
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
	PutFormData($f, $s, 'newname', "", 'text');
	PutFormData($f, 'new', 'add', '');
}

//////////////////////////////////////////
// Display functions
//////////////////////////////////////////

function getPriorityName($systempriority){
	$systemprioritynames = array(1 => "Emergency",
								2 => "High Priority",
								3 => "General");

	return $systemprioritynames[$systempriority];
}

//////////////////////////////////////////
// Display
//////////////////////////////////////////

include("nav.inc.php");

NewForm($f);
?>

<table class=list>
	<tr>
		<td>Name</td>
		<td>System Priority</td>
		<td>Is Survey?</td>
		<td>Actions</td>
	</tr>
<?
	foreach($jobtypes as $jobtype){
		?><tr>
			<td><?=$jobtype["name"]?></td>
			<td><?=getPriorityName($jobtype["systempriority"])?></td>
			<td><?= $jobtype["issurvey"] ? "Yes" : "No" ?></td>
			<td>&nbsp</td>
		</tr><?
	}
?>
	<tr>
		<td><? NewFormItem($f, $s, 'newname', 'text', 20)?></td>
		<td>High Priority</td>
		<td>No</td>
		<td><? NewFormItem($f, 'new', 'add', 'submit')?></td>

	</tr>

</table>
<div style="color:green">
<p>If you want to add more general or survey types, please log into the customer and use the Job Type Management page.
<p>If you are adding a High Priority Job Type, make sure it qualifies as a High Priority.  This would include things like "Attendance" or "Food Services".
<p>If it does not fall into that category, please make the Job Type a General.
<p>Do not forget to configure the job type call preferences for any added job types on this page.
</div>
<?
EndForm();
?>