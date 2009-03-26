<?

$isNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");


// pass along the customerurl (used by phone activation feature to find a customer without any existing associations)
$appendcustomerurl = "";
if (isset($_GET['u'])) {
	$appendcustomerurl = "?u=".urlencode($_GET['u']);
}

$changeuser = false;
$forgot = false;
if(isset($_GET['c'])){
	$changeuser = true;
	include("activate.php");
	exit();
} else if(isset($_GET['f'])){
	$forgot = true;
	include("activate.php");
	exit();
} else if(isset($_GET['n'])){
	include("activate.php");
	exit();
}

if(isset($_GET['embedded'])){
	setcookie('embeddedpage', "1");
	redirect();
}
if(isset($_GET['deleteembedded'])){
	setcookie('embeddedpage');
	redirect();
}

if (isset($_GET['logout'])) {
	doStartSession(); // start the session to get the id
	subscriberPutSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}

if ($SETTINGS['feature']['has_ssl']) {
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		redirect("https://" . $_SERVER["SERVER_NAME"] . "/index.php".$appendcustomerurl);
	}
}


$login="";
$badlogin=false;
$id = false;
$sessionstarted = false;

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];

	$result = subscriberLogin($CUSTOMERURL, $login, $password);
	if ($result['result'] == "")
		$id = $result['userID'];
	else
		$badlogin = true;

} else if (!isset($_GET['logout'])){
	doStartSession(); // we must start the session to obtain the user information before trying to perform the following IF conditions
	$sessionstarted = true;
	if (isset($_SESSION['subscriberid'])) {
		$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'start.php'.$appendcustomerurl;
		unset($_SESSION['lasturi']);
		redirect($redirpage);
    }
}

if ($id) {
	if (!$sessionstarted)
		doStartSession($id);

	redirect("start.php");
}


$TITLE= "Sign In";

include_once("cmlogintop.inc.php");

?>
<form method="POST" action="index.php<?echo $appendcustomerurl;?>" name="login">
	<table style="color: #365F8D;" >
		<tr>
			<td colspan="3">
				<div style="font-size: 20px; font-weight: bold;">SchoolMessenger Self Signup</div>
				<br>
				<br>
			</td>
		</tr>
		<tr>
			<td colspan="3">
<?
				if ($badlogin) {
				?>
					<div style="color: red;">Incorrect username/password. Please try again.</div><br>
				<?
				}
?>
			</td>
		<tr>
			<td>Email:</td>
			<td><input type="text" id="logintext" name="login" size="50" maxlength="255" value="<?=escapehtml($login)?>"/></td>
			<td>&nbsp;</td>

		</tr>
		<tr>
			<td>Password&nbsp;(case&nbsp;sensitive):</td>
			<td><input type="password" name="password" size = "50" maxlength="50" onkeypress="capslockCheck(event)"/></td>
			<td align="left"><a href="forgotpassword.php<?echo $appendcustomerurl;?>">Forgot your password? Click Here</a></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><br><div id="capslockwarning"  style="padding-left:3px; float:left; display:none; color:red;">Warning! Your Caps Lock key is on.</div></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right"><div style="text-align: right;"><input type="image" src="img/signin.gif" onmouseover="this.src='img/signin_over.gif';" onmouseout="this.src='img/signin.gif';"></div></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="3">First time accessing the SchoolMessenger Self Signup?</td>
		</tr>
		<tr>
			<td colspan="3"><a href="newsubscriber.php<?echo $appendcustomerurl;?>"><b>Sign up now</b></a></td>
		</tr>
	</table>
</form>
<br>
<br>
<br>
<br>
<br>
<?

/*
$formdata = array(
	"firstname" => array(
		"label" => "First Name",
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3,"max" => 50)
		),
		"requires" => "someotherfield",
		"control" => array("TextField","maxlength" => 50),
		"helpstep" => 1
	));
	
$form = new Form($formdata, "gjbtest");
$form->render();
*/


include("cmloginbottom.inc.php");
?>
<script langauge="javascript">
document.getElementById('logintext').focus();

function capslockCheck(e){
		var keypressed;
		var shiftkey;

		if(e.keyCode)
			keypressed = e.keyCode;
		else
			keypressed = e.which;

		if(e.shiftKey) {
			shiftkey = e.shiftkey;
		} else {
			if(keypressed == 16) {
				shiftkey = true;
			} else {
				shiftkey = false;
			}
		}
		if(((keypressed >= 65 && keypressed <= 90) && !shiftkey) || ((keypressed >= 97 && keypressed <= 122) && shiftkey)){
			new getObj('capslockwarning').style.display = 'block';
		} else
			new getObj('capslockwarning').style.display = 'none';
	}

</script>