<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("dbmo/authserver/AspAdminUser.obj.php");
require_once("../obj/FormUserItems.obj.php");
require_once("dbmo/authserver/AspAdminQuery.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("superuser"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'

if (isset($_GET['id'])) {
	if ($_GET['id'] === "new") {
		$_SESSION['edituserid'] = null;
	} else {
		$_SESSION['edituserid']= $_GET['id']+0;
	}
	redirect();
}

$edituserid = isset($_SESSION['edituserid'])?$_SESSION['edituserid']:false;

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


class QueriesItem extends FormItem {

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input type="hidden" id="'.$n.'" name="'.$n.'" value="' . escapehtml(json_encode($value)) . '" />';
		
		$unrestrictchecked = $value == "unrestricted" ? "checked" : "";
		$str .= '<input type="checkbox" id="'.$n.'-restrict" '.$unrestrictchecked .' onclick="unrestrictcheck(\''.$n.'-restrict\', \'checks\')"><label for="'.$n.'-restrict">Unrestricted</label>';
		$str .= '<div id="checks" class="radiobox" style="margin-left: 1em;">';
		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = 'checks-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').'  onclick="datafieldcheck(\''.$id.'\', \''.$n.'-restrict\')"/><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />';
			$counter++;
		}
		
		$str .= '</div>';
		return $str;
		
	}
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		return '
			//if we check the restrict box, uncheck each field
			function unrestrictcheck(restrictcheckbox, checkboxdiv) {
				restrictcheckbox = $(restrictcheckbox);
				checkboxdiv = $(checkboxdiv);
				if (restrictcheckbox.checked) {
					checkboxdiv.descendants().each(function(e) {
						e.checked = false;
					});
					$("'.$n.'").value = "\"unrestricted\"";
				} else {
					$("'.$n.'").value = "[]";
				}
			}
	
			// if a data field is checked. Uncheck the unrestrict box
			function datafieldcheck(checkbox, restrictcheckbox) {
				checkbox = $(checkbox);
				restrictcheckbox = $(restrictcheckbox);
				if (checkbox.checked)
						restrictcheckbox.checked = false;
				var checks = new Array();
				$("checks").descendants().each(function(e) {
					if (e.checked)
						checks.push(e.value);
				});		
				$("'.$n.'").value = checks.toJSON();
			}';
	}
}


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$basicroles = array(
	"logincustomer" => "Log in to customer account",
	"newcustomer" => "Create customers",
	"editcustomer" => "Edit existing customers",
	"edittemplate" => "Edit customer templates",
	"editpriorities" => "Manage customer job types",
	"customercontacts" => "Search customer contacts",
	"users" => "View Customer users",
	"imports" => "View Imports",
	"editimportalerts" => "Edit import alerts",
	"lockedusers" => "Manage locked users",
	"smsblock" => "Manage SMS blocked numbers",
	"activejobs" => "View active jobs",
	"editdm" => "Edit SmartCall Appliances",
	"diskagent" => "Manage SwiftSync Agents"
);

$advancedroles = array(
	"runqueries" => "Run custom queries",
	"ffield2gfield" => "F field to G field migration",
	"billablecalls" => "Billable call information",
	"bouncedemailsearch" => "Bounced email search",
	"passwordcheck" => "Check for bad/similar passwords",
	"emergencyjobs" => "List of Recent Emergency Jobs",
	"tollfreenumbers" => "Add Toll Free Numbers",
	"aspcallgraphs" => "View ASP Graphs",
	"logcollector" => "Run Log Collector"
);

$superroles = array(
	"editqueries" => "Edit/Add Queries (arbitrary sql)",
	"systemdm" => "Manage System DMs",
	"manageserver" => "Manage Server list, restart commsuite",
	"superuser" => "Edit Users (this page)",
);

$activeroles = array();

if ($edituserid) {
	$edituser = DBFind("AspAdminUser", "from aspadminuser where id=?",false,array($edituserid));
	$activeroles = explode(",",$edituser->permissions);
	//if ($edituser->queries == "unrestricted")
} else {
	$edituser = new AspAdminUser();
}

$managerqueries = DBFindMany("AspAdminQuery", "from aspadminquery order by name");
$querymap = array();
foreach ($managerqueries as $managerquery) {
	$querymap[$managerquery->id] = $managerquery->name;
}


$helpstepnum = 1;
$helpsteps = array("TODO");

$formdata["login"] = array(
	"label" => _L('Username'),
	"value" => $edituser->login,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$formdata["firstname"] = array(
	"label" => _L('First Name'),
	"value" => $edituser->firstname,
	"validators" => array(
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);
$formdata["lastname"] = array(
	"label" => _L('Last Name'),
	"value" => $edituser->lastname,
	"validators" => array(
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$pass = ($edituserid) ? 'nopasswordchange' : '';
$passlength = 5;
$formdata["password"] = array(
	"label" => _L("Password"),
	"fieldhelp" => _L('The password is used to log into the web interface.'),
	"value" => $pass,
	"validators" => array(
		array("ValRequired"),
	),
	"requires" => array("firstname", "lastname", "login"),
	"control" => array("TextPasswordStrength","maxlength" => 20, "size" => 25, "minlength" => $passlength, "generator" => true),
	"helpstep" => 1
);

$formdata["passwordconfirm"] = array(
	"label" => _L("Confirm Password"),
	"fieldhelp" => _L('This field is used to confirm a new password.'),
	"value" => $pass,
	"validators" => array(
		array("ValRequired"),
		array("ValFieldConfirmation", "field" => "password")
	),
	"requires" => array("password"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 25),
	"helpstep" => 1
);

$formdata["email"] = array(
	"label" => _L('Email'),
	"value" => $edituser->email,
	"validators" => array(
		array("ValEmail"),
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$formdata[] = "Basic Permissions";
$formdata["enablebasic"] = array (
		"label" => "",
		"control" => array("FormHtml", "html" => icon_button(_L('Enable All Basic Permissions'),"group",'checkAllCheckboxes(\'basic\');')),
		"helpstep" => 1
);

foreach($basicroles as $role => $description) {
	$formdata[$role] = array(
		"label" => $description,
		"value" => in_array($role,$activeroles) ? 1 : 0,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
	);
}

$formdata[] = "Advanced Permissions";

$formdata["enableadvanced"] = array (
		"label" => "",
		"control" => array("FormHtml", "html" => icon_button(_L('Enable All Advanced Permissions'),"group",'checkAllCheckboxes(\'advanced\');')),
		"helpstep" => 1
);

foreach($advancedroles as $role => $description) {
	$formdata[$role] = array(
		"label" => $description,
		"value" => in_array($role,$activeroles) ? 1 : 0,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
	);
}

$formdata[] = "Super Permissions";

$formdata["enablesuper"] = array (
		"label" => "",
		"control" => array("FormHtml", "html" => icon_button(_L('Enable All Super Permissions'),"group",'checkAllCheckboxes(\'super\');')),
		"helpstep" => 1
);

foreach($superroles as $role => $description) {
	$formdata[$role] = array(
		"label" => $description,
		"value" => in_array($role,$activeroles) ? 1 : 0,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
	);
}

$formdata[] = "Query Restrictions";

$formdata["queries"] = array(
		"label" => _L('User Queries'),
		"value" => ($edituser->queries!="unrestricted"?explode(",",$edituser->queries):"unrestricted"),
		"validators" => array(),
		"control" => array("QueriesItem", "values" => $querymap),
		"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"users.php"));
$form = new Form("useredit",$formdata,false,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		
		//save data here	
		$edituser->login = $postdata["login"];
		$edituser->firstname = $postdata["firstname"];
		$edituser->lastname = $postdata["lastname"];
		$edituser->email = $postdata["email"];
		
		$permissions = array();
		foreach ($basicroles as $role => $description) {
			if ($postdata[$role])
				$permissions[] = $role;
		}
		
		foreach ($advancedroles as $role => $description) {
			if ($postdata[$role])
			$permissions[] = $role;
		}
		
		foreach ($superroles as $role => $description) {
			if ($postdata[$role])
			$permissions[] = $role;
		}
		
		$edituser->permissions = implode(",",$permissions);

		$queries = json_decode($postdata["queries"],true);
		if ($queries == "unrestricted") {
			$queriesval = "unrestricted";
		} else {
			$queriesval = implode(",",is_array($queries) ? $queries : array());
		}
		
		$edituser->queries = $queriesval;
		
		if ($edituserid)
			$edituser->update();
		else
			$edituser->create();

		Query("COMMIT");
		
		if ($postdata['password'] !== "nopasswordchange") {
			QuickUpdate("update aspadminuser set password=password(?) where id=?", false, array($postdata['password'], $edituser->id));
		}
		if ($ajax)
			$form->sendTo("users.php");
		else
			redirect("users.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Edit User');

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValPassword")); ?>
</script>
<?
startWindow(_L('Edit User'));
echo $form->render();
endWindow();

?>
<script type="text/javascript">
function checkAllCheckboxes(type) {
	
	var roles;
	if (type=="basic") {
		roles = $A(<?=json_encode(array_keys($basicroles));?>);
	} else if (type=="advanced") {
		roles = $A(<?=json_encode(array_keys($advancedroles));?>);
	} else if (type=="super") {
		roles = $A(<?=json_encode(array_keys($superroles));?>);
	}

	roles.each(function(id) {
		var itemid = "useredit_" + id;
		$("useredit_" + id).checked = true;
	});
}
</script>


<?

include_once("navbottom.inc.php");
?>
