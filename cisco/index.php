<?

$isindexpage = true;
require_once("common.inc.php");

if (isset($_GET['dn'])) {
	$_SESSION['dn'] = $_GET['dn'];
}

if (isset($_GET['loginalpha'])) {
	if ($_GET['loginalpha'] == "true") {
		$_SESSION['loginalpha'] = true;
	} else {
		$_SESSION['loginalpha'] = false;
	}
}


$badlogin = false;
$scheme = getCustomerData($CUSTOMERURL);

//try logging in once user/pass or code/pin is checked
function tryLogin ($userid) {
	if (!$userid) {
		error_log("User trying to log in but has bad user/pass/url");
		return false;
	}
	if ($userid == -1) {
		return false; // -1 for login lockout period
	}
	doStartSession();
	$newuser = new User($userid);
	$newaccess = new Access($newuser->accessid);
	if($newuser->enabled && $newaccess->getValue('loginphone')) {
		$USER = $_SESSION['user'] = $newuser;
		$ACCESS = $_SESSION['access'] = $newaccess;
		$_SESSION['custname'] = getSystemSetting('displayname');
		$_SESSION['timezone'] = getSystemSetting('timezone');
		$USER->lastlogin = QuickQuery("select now()");
		$USER->update(array("lastlogin"));

		return true;
	} else {
		@session_destroy();
		return false;
	}
}

if (isset($_GET['logout'])) {
	$dn = isset($_SESSION['dn']) ? $_SESSION['dn'] : "";
	@session_destroy();
	$_SESSION['dn'] = $dn;
} else if (isset($_SESSION['user'])) {
	$_SESSION['productname'] = isset($scheme['productname']) ? $scheme['productname'] : "" ;
	sleep(1);
	header("Location: $URL/main.php");
	exit();
} else if (isset($_GET['code']) && isset($_GET['pin'])) {
	if (tryLogin(doLoginPhone($_GET['code'], $_GET['pin'],null, $CUSTOMERURL))) {
		$_SESSION['productname'] = isset($scheme['productname']) ? $scheme['productname'] : "" ;
		sleep(1);
		header("Location: $URL/main.php");
		exit();
	} else {
		$badlogin = true;
	}
} else if (isset($_GET['login']) && isset($_GET['password'])) {
	if (tryLogin(doLogin($_GET['login'], $_GET['password'], $CUSTOMERURL,$_SERVER['REMOTE_ADDR']))) {
		$_SESSION['productname'] = isset($scheme['productname']) ? $scheme['productname'] : "" ;
		sleep(1);
		header("Location: $URL/main.php");
		exit();
	} else {
		$badlogin = true;
		$_SESSION['loginalpha'] = true;
	}
}


header("Content-type: text/xml");

?>

<CiscoIPPhoneInput>
<Title><?=$scheme['productname']?> - Welcome</Title>
<Prompt><?= ($badlogin) ? ((isset($_SESSION['loginalpha']) && $_SESSION['loginalpha']) ? "Invalid username or password":"Invalid code or PIN") : "Please log in" ?></Prompt>
<URL><?= $URL . "/index.php" ?></URL>


<? if (isset($_SESSION['loginalpha']) && $_SESSION['loginalpha']) { ?>

<InputItem>
<DisplayName>Username</DisplayName>
<QueryStringParam>login</QueryStringParam>
<DefaultValue></DefaultValue>
<InputFlags>A</InputFlags>
</InputItem>

<InputItem>
<DisplayName>Password</DisplayName>
<QueryStringParam>password</QueryStringParam>
<DefaultValue></DefaultValue>
<InputFlags>AP</InputFlags>
</InputItem>

<? } else { ?>

<InputItem>
<DisplayName>Access code</DisplayName>
<QueryStringParam>code</QueryStringParam>
<DefaultValue></DefaultValue>
<InputFlags>N</InputFlags>
</InputItem>

<InputItem>
<DisplayName>PIN</DisplayName>
<QueryStringParam>pin</QueryStringParam>
<DefaultValue></DefaultValue>
<InputFlags>NP</InputFlags>
</InputItem>

<? } ?>

<SoftKeyItem>
<Name>Submit</Name>
<URL>SoftKey:Submit</URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>&lt;&lt;</Name>
<URL>SoftKey:&lt;&lt;</URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL>SoftKey:Cancel</URL>
<Position>3</Position>
</SoftKeyItem>

<? if (isset($_SESSION['loginalpha']) && $_SESSION['loginalpha']) { ?>

<SoftKeyItem>
<Name>Numeric</Name>
<URL><?= htmlentities($URL . "/index.php?loginalpha=false") ?></URL>
<Position>4</Position>
</SoftKeyItem>

<? } else { ?>

<SoftKeyItem>
<Name>Alpha</Name>
<URL><?= htmlentities($URL . "/index.php?loginalpha=true") ?></URL>
<Position>4</Position>
</SoftKeyItem>

<? } ?>

</CiscoIPPhoneInput>
