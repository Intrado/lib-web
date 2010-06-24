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
if (isset($_SESSION['currentid'])) {
	$currentid = $_SESSION['currentid'];
	$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if (!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
}

if (isset($_GET['delete'])) {
	QuickUpdate("update jobtype set deleted=1 where id=?",$custdb,array($_GET['delete']));
	redirect();
}

if (isset($_GET['undelete'])) {
	QuickUpdate("update jobtype set deleted=0 where id=?",$custdb,array($_GET['undelete']));
	redirect();
}


//////////////////////////////////////////
// Data Handling
//////////////////////////////////////////

$reload = 0;
$refresh = 0;
$result = Query("select id, name, systempriority, issurvey, deleted from jobtype order by systempriority, issurvey, name", $custdb);
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
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		$priority = GetFormData($f, $s, 'priority') + 0;

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($priority !== 1 && $priority !== 2) {
			error('Priority must be between 0 and 2, 0 for Emergency and 2 for High Priority');
		} else {
			if(GetFormData($f, $s, 'newname') != ""){
				$name = GetFormData($f, $s, 'newname');
				QuickUpdate("insert into jobtype(name, systempriority, issurvey) values
							(?, ?, '0')", $custdb, array($name, $priority));
				redirect();
			} else {
				error("You cannot add a job type that has a blank name");
			}

			foreach ($jobtypes as $jobtype) {
				QuickUpdate("update jobtype set name=? where id=?", $custdb, array(trim(GetFormData($f,$s,"name_". $jobtype["id"])),$jobtype["id"]));
			}

			redirect();
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
	PutFormData($f, $s, 'newname', "", 'text', 0, 50);
	PutFormData($f, 'new', 'add', '');
	PutFormData($f,$s,'priority', 2, "number");

	foreach ($jobtypes as $jobtype) {
		PutFormData($f,$s,"name_". $jobtype["id"],$jobtype["name"],"text",1,50,1);
	}


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
		<td>Deleted?</td>
		<td>Actions</td>
	</tr>
<?
	foreach($jobtypes as $jobtype){
		?><tr>
			<td><? NewFormItem($f, $s, "name_". $jobtype["id"], 'text', "20","50",$jobtype['deleted'] ? 'style="text-decoration: line-through;"' : ''); ?></td>
			<td><?=getPriorityName($jobtype["systempriority"])?></td>
			<td><?= $jobtype["issurvey"] ? "Yes" : "No" ?></td>
			<td><?= $jobtype['deleted'] ? "Deleted" : "" ?></td>
			<td>
<?
			if ($jobtype["deleted"]) {
				echo '<a href="customerpriorities.php?undelete=' . $jobtype["id"] . '">Undelete</a>';
			} else {
				echo '<a href="customerpriorities.php?delete=' . $jobtype["id"] . '">Delete</a>';
			}

?>
			</td>
		</tr><?
	}
?>
	<tr>
		<td><? NewFormItem($f, $s, 'newname', 'text',"20","50")?></td>
		<td>
		<?
			NewFormItem($f, $s, 'priority', 'selectstart');
			NewFormItem($f, $s, 'priority', 'selectoption', 'Emergency', 1);
			NewFormItem($f, $s, 'priority', 'selectoption', 'High Priority', 2);
			NewFormItem($f, $s, 'priority', 'selectend');
		?>
		</td>
		<td>No</td>
		<td>&nbsp;</td>
		<td><? NewFormItem($f, 'new', 'add', 'submit')?></td>

	</tr>

</table>
<? NewFormItem($f, 'new', 'Save', 'submit')?>
<div style="color:green">
<p>If you want to add more general or survey types, please log into the customer and use the Job Type Management page. <b>You must have a minimum of one survey jobtype and one non-survey jobtype regardless of whether or not the customer has survey enabled.</b>
<p>If you are adding a High Priority Job Type, make sure it qualifies as a High Priority.  This would include things like "Attendance" or "Food Services".
<p>If it does not fall into that category, please make the Job Type a General.
<p>Do not forget to configure the job type call preferences for any added job types on this page.
</div>
<?
EndForm();
?>
