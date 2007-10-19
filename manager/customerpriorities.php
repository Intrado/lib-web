<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");
include_once("AspAdminUser.obj.php");

if (isset($_GET['id'])) {
	$_SESSION['currentid']= $_GET['id']+0;
	redirect();
}
$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);
if(isset($_SESSION['currentid'])) {
	$currentid = $_SESSION['currentid'];
	$custquery = Query("select s.dbhost, s.dbusername, s.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custinfo = mysql_fetch_row($custquery);
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if(!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
}

if(isset($_REQUEST['delete'])){
	$jobtypeid = $_REQUEST['delete']+0;
	QuickUpdate("update jobtype set deleted = 1 where id = '$jobtypeid'", $custdb);
	redirect();
}

//////////////////////////////////////////
// Data Handling
//////////////////////////////////////////

$reload = 0;
$refresh = 0;
$result = Query("select id, name, systempriority, timeslices from jobtype where deleted = 0 order by systempriority, name", $custdb);
$jobtypes = array();
while($row = DBGetRow($result, true)){
	$jobtypes[] = $row;
}

$f = "form";
$s = "priorities";

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, 'new')) {
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
		} else if(!$accountcreator->runCheck(GetFormData($f, $s, 'managerpassword'))) {
				error('Bad Manager Password');
		} else {
		
			if(CheckFormSubmit($f, 'new')){
				$name = DBSafe(GetFormData($f, $s, 'newname'));
				$timeslice = GetFormData($f, $s, 'newtimeslice') +0;
				$systempriority = GetFormData($f, $s, 'newsystempriority')+0;
				QuickUpdate("insert into jobtype(name, systempriority, timeslices) values
							('$name', '$systempriority', '$timeslice')", $custdb);
			}
				
			foreach($jobtypes as $jobtype){
				$id = $jobtype['id'];
				$name = DBSafe(GetFormData($f, $s, 'name'.$jobtype['id']));
				$timeslice = GetFormData($f, $s, 'timeslice'.$jobtype['id']) + 0;
				$systempriority = GetFormData($f, $s, 'systempriority'.$jobtype['id']) + 0;
				$query = "update jobtype set name = '$name', timeslices = '$timeslice', systempriority = '$systempriority' where id = '$id'";
				QuickUpdate($query, $custdb);	
			}
			$refresh = 1;
			$reload = 1;
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
	if($refresh){
		$result = Query("select id, name, systempriority, timeslices from jobtype where deleted = 0 order by systempriority, name", $custdb);
		$jobtypes = array();
		while($row = DBGetRow($result, true)){
			$jobtypes[] = $row;
		}
	}
	PutFormData($f, $s, 'newname', "", 'text');
	PutFormData($f, $s, 'newtimeslice', "", 'number');
	PutFormData($f, $s, 'newsystempriority', "");
	foreach($jobtypes as $jobtype){
		PutFormData($f, $s, 'name'.$jobtype['id'], $jobtype['name'], 'text');
		PutFormData($f, $s, 'timeslice'.$jobtype['id'], $jobtype['timeslices'],'number');
		PutFormData($f, $s, 'systempriority'.$jobtype['id'], $jobtype['systempriority']);
	}
	PutFormData($f, $s, 'managerpassword', "", "text");
}


//////////////////////////////////////////
// Display Functions
//////////////////////////////////////////

function getSystemPriorities () {
	return array("1" => "Emergency",
				"2" => "High Priority",
				"3" => "General");
}

function setSystemPriority($f, $s, $name) {
	$systempriorities = getSystemPriorities();
	NewFormItem($f, $s, $name, 'selectstart', NULL, NULL, 'id="addlang"');

	foreach ($systempriorities as $index => $priority) {
		NewFormItem($f, $s, $name, 'selectoption', $priority, $index);
	}
	NewFormItem($f, $s, $name, 'selectend');
}
//////////////////////////////////////////
// Display
//////////////////////////////////////////

include("nav.inc.php");

NewForm($f);
?>

<table border=1>
	<tr>
		<td>Name</td>
		<td>System Priority</td>
		<td>Throttle Level/Timeslice</td>
		<td>Add Or Delete </td>
	</tr>
<?
	$systempriorities = getSystemPriorities();
	foreach($jobtypes as $jobtype){
		?><tr>
			<td><? NewFormItem($f, $s, 'name'.$jobtype['id'], 'text', 20)?></td>
			<td><? setSystemPriority($f, $s, 'systempriority'.$jobtype['id'])?></td>
			<td><? NewFormItem($f, $s, 'timeslice'.$jobtype['id'], 'text', 20)?></td>
			<td><a href="customerpriorities.php?delete=<?=$jobtype['id']?>">Delete</a>
		</tr><?
	}
?>
	<tr>
		<td><? NewFormItem($f, $s, 'newname', 'text', 20)?></td>
		<td><? setSystemPriority($f, $s, 'newsystempriority')?></td>
		<td><? NewFormItem($f, $s, 'newtimeslice', 'text', 20)?></td>
		<td><? NewFormItem($f, 'new', 'add', 'submit')?></td>
		
	</tr>

</table>
<p> <? NewFormItem($f, $s, "Save", 'submit');?>
<br> Manager Password: <? NewFormItem($f, $s, 'managerpassword', 'password', 25); ?></p>

<?
EndForm();
include("navbottom.inc.php");
?>