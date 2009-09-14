<?
// Cannot use form.inc because no session has started
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

require_once("XML/RPC.php");
require_once("inc/db.inc.php");
require_once("inc/auth.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/DBMappedObject.php");
require_once("obj/Job.obj.php");

$code = '';
if (isset($_GET['e'])) {
	$code = $_GET['e'];
}

$customer = "TEST VALUE";
$badcode = true;
$email = "test@test.com";
$TITLE = $customer;
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<link href="css.php?nocommoninc" type="text/css" rel="stylesheet" media="screen, print">
	<title><?=$TITLE?></title>
</head>
<body style='padding: 0; margin: 0px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;'>
	<table border=0 cellspacing=0 cellpadding=0 width="100%">
		<tr>
			<td>
				<div style="padding-left:10px; padding-bottom:10px">
					<img src="logo.img.php?hash=<?= $badcode ? "12345" : crc32("cid".getSystemSetting("_logocontentid"))?>" />
				</div>
			</td>
			<td>
				<div class="swooshbg">
					<img src="img/shwoosh.gif" />
				</div>
			</td>
			<td width="100%" class="swooshbg"></td>
		</tr>
	</table>
	<div class="navband1"><img src="img/pixel.gif"></div>
	<div class="navband2"><img src="img/pixel.gif"></div>
	<div style="margin: 15px">
<?
startWindow("Block My Email Address", false, false, false);
?>
	<div>
		<p>Enter (or confirm) the email address you would like blocked below.</p>
		<p>Once blocked, you will no longer receive messages from this system via email. To re-subscribe, contact <?=$customer?>.</p>
		<div style="margin-bottom: 10px">Email:&nbsp;<input type='text' maxlength="200" size="50" value="<?=$email?>"></div>
		<?=submit_button("Add","submit","tick")?>
	</div>
<?
endWindow();
?>
	</div>
</body>
</html>
