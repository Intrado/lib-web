<?
// Cannot use form.inc because no session has started
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("XML/RPC.php");
require_once("inc/db.inc.php");
require_once("inc/memcache.inc.php");
require_once("inc/auth.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/DBMappedObject.php");
require_once("obj/Job.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$code = '';
$submit = false;

if (isset($_GET['s']))
	$code = $_GET['s'];

if (isset($_GET['auto']))
	$submit = true;
	
if (isset($_POST['submit'])) {
	$code = $_POST['code'];
	$submit = true;
}

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","unsubscribe"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

$customer = getCustomerName($CUSTOMERURL);
$email = base64url_decode($code);

$badcode = false;
if (!$code || !$customer || !validEmail($email))
	$badcode = true;

if (!$badcode && $submit) {
	$badcode = !emailUnsubscribe($CUSTOMERURL, $email);
}

apache_note("CS_USER",urlencode($email)); //for logging

$customer = escapehtml($customer);
$email = escapehtml($email);

$TITLE = $customer;

?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<link href="css.php?nocommoninc" type="text/css" rel="stylesheet" media="screen, print">
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/utils.js" type="text/javascript"></script>
	<title><?=$TITLE?></title>
</head>
<body style='padding: 0; margin: 0px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;'>
	<table border=0 cellspacing=0 cellpadding=0 width="100%">
		<tr>
			<td>
				<div style="padding-left:10px; padding-bottom:10px">
					<img src="logo.img.php" />
				</div>
			</td>
			<td>
				<div class="navlogoarea">
					<img src="img/shwoosh.gif" />
				</div>
			</td>
			<td width="100%" class="navlogoarea"></td>
		</tr>
	</table>
	<div class="navband1"><img src="img/pixel.gif"></div>
	<div class="navband2"><img src="img/pixel.gif"></div>
	<div style="margin: 15px">
<?
if ($badcode) {
	startWindow("Bad Request!");
	?>
		<h1>Your request appears to be invalid.</h1>
		<p>Please check your URL and try again.</p>
	<?
	endWindow();
} else if ($submit) {
	startWindow("Unsubscribe Email");
	?>
		<h1>Your request has been processed.</h1>
		<p>Your email (<?=$email?>) will no longer receive <b>ANY</b> messages from <?=$customer?>.</p>
		<p>To re-subscribe, contact <?=$customer?>.</p>
	<?
	endWindow();
} else {
	startWindow("Unsubscribe My Email Address", false, false, false);
	?>
		<div>
			<h1>To unsubscribe from <b>ALL</b> messages, confirm your email address below.</h1>
			<p>Once unsubscribed, you will no longer receive any emails distributed through this system on behalf of <?=$customer?> to the following address.</p>
			<p><big style="color: #cc0000;">This may include emergency broadcasts and other important announcements.</big></p>
			<div><div style="float:left; margin-right: 5px">Email:</div><div style="padding-left: 3px; padding-right: 3px; width: auto; float: left"><?=$email?></div></div>
			<div style="clear: both; margin-bottom: 10px"><img src="img/pixel.gif" /></div>
			<form method="POST" action="unsubscribeemail.php">
			<input type="hidden" name="code" value="<?=escapehtml($code)?>">
			<?=submit_button("Unsubscribe my email","submit","fugue/slash")?>
			</form>
		</div>
	<?
	endWindow();
}
?>
	</div>
	<script type="text/javascript">
		function form_submit() {};
	</script>
</body>
</html>
