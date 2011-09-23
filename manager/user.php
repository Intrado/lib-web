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
require_once("dbmo/auth/AspAdminUser.obj.php");
require_once("../obj/FormUserItems.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("edituser"))
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


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

if ($edituserid) {
	$edituser = DBFind("AspAdminUser", "from aspadminuser where id=?",false,array($edituserid));
} else {
	$edituser = new AspAdminUser();
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
		array("ValRequired"),
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);
$formdata["lastname"] = array(
	"label" => _L('Last Name'),
	"value" => $edituser->lastname,
	"validators" => array(
		array("ValRequired"),
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
		array("ValLength","min" => $passlength,"max" => 20),
		array("ValPassword", "login" => $edituser->login, "firstname" => $edituser->firstname, "lastname" => $edituser->lastname)
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
		array("ValLength","min" => $passlength,"max" => 20),
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
include_once("navbottom.inc.php");
?>