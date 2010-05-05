<?

require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/html.inc.php");


if (!$MANAGERUSER->authorized("superuser"))
	exit("Not Authorized");

$roles = array(
	"logincustomer" => "Log in to customer account",
	"newcustomer" => "Create customers",
	"editcustomer" => "Edit existing customers",
	"editpriorities" => "Manage customer job types",
	"customercontacts" => "Search customer contacts",
	"users" => "View Customer users",
	"imports" => "View Imports",
	"editimportalerts" => "Edit import alerts",
	"lockedusers" => "Manage locked users",
	"smsblock" => "Manage SMS blocked numbers",
	"activejobs" => "View active jobs",
	"editdm" => "Edit SmartCall Appliances",
	"diskagent" => "Manage SwiftSync Agents",
	
	"ffield2gfield" => "ADVANCED - F field to G field migration",
	"billablecalls" => "ADVANCED - Billable call information",
	"bouncedemailsearch" => "ADVANCED - Bounced email search",
	"passwordcheck" => "ADVANCED - Check for bad/similar passwords",
	"emergencyjobs" => "ADVANCED - List of Recent Emergency Jobs",

	"systemdm" => "SUPER - Manage System DMs",
	"superuser" => "SUPER - Edit Manager Roles (this page)"
);


$f = "editroles";
$s = "main";
$reloadform = 0;


if (CheckFormSubmit($f,"loadusers")) {
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$uids = GetFormData($f,$s,"edituserids");
			$_SESSION['edituserids'] = array();
			foreach ($uids as $uid) {
				$_SESSION['edituserids'][] = $uid + 0;
			}
			
			redirect();
		}
	}
}
$message = "";
if(CheckFormSubmit($f,$s)) {
	//check to see if formdata is valid
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (!isset($_SESSION['edituserids']) || !count($_SESSION['edituserids'])) {
			error('You should load some user settings first before making changes');
		} else {
			$permissions = array();
			foreach ($roles as $role => $desc) {
				if (GetFormData($f,$s,$role))
					$permissions[] = $role;
			}			
			$query = "update aspadminuser set permissions='" . DBSafe(implode(",",$permissions)) . "' where id in (" . implode(",",$_SESSION['edituserids']) . ")";
			QuickUpdate($query);
			$message = "<em>Permissions updated</em>";
			$reloadform = 1;
		}
	}
} else {
	$reloadform = 1;
}

//load relavent data

$users = DBFindMany("AspAdminUser", "from aspadminuser");

$logins = array();
foreach ($users as $id => $u)
	$logins[$id] = $u->login;

if (isset($_SESSION['edituserids'])) {
	$activeroles = array();
	foreach ($users as $u) {
		if (in_array($u->id, $_SESSION['edituserids']))
			$activeroles = array_merge($activeroles,explode(",",$u->permissions));
	}
	$activeroles = array_unique($activeroles);
} else {
	$activeroles = array();
}

if( $reloadform ) {
	ClearFormData($f);
	
	PutFormData($f,$s,"edituserids", isset($_SESSION['edituserids']) ? $_SESSION['edituserids'] : array(), "array",array_keys($users));
	
	foreach ($roles as $role => $desc) {
		PutFormData($f,$s,$role,in_array($role,$activeroles) ? 1 : 0,"bool",0,1);
	}
}

//---------------------------------

require_once("nav.inc.php");


NewForm($f);
?>
<h3>Edit user roles</h3>
<?= $message ?>
<table>
<tr>
<th>Users:</th>
<td>
<? 
	NewFormItem($f,$s,"edituserids","selectmultiple",10,$logins);
?>
</td>
<td><? NewFormItem($f,"loadusers","Load User Permissions","submit"); ?></td>

</tr>

<? if (isset($_SESSION['edituserids'])) { ?>
<tr>
<th valign="top">Permissions:</th>


<td>
<table>
	<tr>
		<th>Enable</th><th>Role</th>
	</tr>
<?
	foreach ($roles as $roleval => $roledesc) {
?>
	<tr>
		<td><? NewFormItem($f,$s,$roleval,"checkbox"); ?></td>
		<td><?= $roledesc ?></td>
	</tr>
<?
	}
?>
</table>

<? NewFormItem($f,$s,"Save","submit"); ?>

</td>
</tr>
<? } ?>

</table>
<?
EndForm();
include_once("navbottom.inc.php");
?>