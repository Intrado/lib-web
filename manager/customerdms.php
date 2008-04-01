<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("AspAdminUser.obj.php");


$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

$dms = array();
$query = "select customerid, dmuuid, name, authorizedip, lastip, enablestate, lastseen from dm where type = 'customer' order by customerid, name";
$result = Query($query);
while($row = DBGetRow($result)){
	$dm = array();
	$dm['customerid'] = $row[0];
	$dm['dmuuid'] = $row[1];
	$dm['name'] = $row[2];
	$dm['authorizedip'] = $row[3];
	$dm['lastip'] = $row[4];
	$dm['enablestate'] = $row[5];
	$dm['lastseen'] = $row[6];
	$dms[$row[1]] = $dm;
}

$f = "dm";
$s = "edit";
$reloadform = 0;
if (CheckFormSubmit($f,$s)){
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f,$s);

		// Checks to see if user left out any of the required fields
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		}else{

			if(!$accountcreator->runCheck(GetFormData($f, $s, "managerpassword"))) {
				error('Bad Manager Password');
			} else {
				foreach($dms as $dm){
					QuickUpdate("update dm set enablestate = '" . DBSafe(GetFormData($f, $s, "dm" . $dm['dmuuid'])) . "' where dmuuid = '" . $dm['dmuuid'] . "'");
				}
				redirect();
			}
		}
	}
} else {
	$reloadform = 1;
}


if($reloadform){
	ClearFormData($f);
	foreach($dms as $dm){
		PutFormData($f, $s, "dm" . $dm['dmuuid'], $dm['enablestate'], "text");
	}
	PutFormData($f, $s, "Save", "");
	PutFormData($f, $s, "managerpassword", "", "text");

}


include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<table border="1">
	<th>Customer ID</th>
	<th>DM ID</th>
	<th>Name</th>
	<th>Authorized IP</th>
	<th>Last IP</th>
	<th>Last Seen</th>
	<th>State</th>
	<th>Actions</th>

<?
	foreach($dms as $dm){
?>
		<tr>
			<td><?=$dm['customerid']?></td>
			<td><?=$dm['dmuuid']?></td>
			<td><?=$dm['name']?></td>
			<td><?=$dm['authorizedip']?></td>
			<td><?=$dm['lastip']?></td>
			<td><?=date('M d, Y h:i:s', $dm['lastseen'])?></td>
			<td>
			<?
				NewFormItem($f, $s, "dm" . $dm['dmuuid'], "selectstart");
				NewFormItem($f, $s, "dm" . $dm['dmuuid'], "selectoption", "New", "new");
				NewFormItem($f, $s, "dm" . $dm['dmuuid'], "selectoption", "Active", "active");
				NewFormItem($f, $s, "dm" . $dm['dmuuid'], "selectoption", "Disabled", "disabled");
				NewFormItem($f, $s, "dm" . $dm['dmuuid'], "selectend");
			?>
			</td>
			<td><a href="editdm.php?dmid=<?=$dm['dmuuid']?>"/>Edit</a></td>
		</tr>
<?
	}
?>
</table>
<table>
	<tr><td><? NewFormItem($f, $s, "Save", "submit"); ?></td></tr>
</table>
<?
managerPassword($f, $s);

EndForm($f);
include_once("navbottom.inc.php");
?>