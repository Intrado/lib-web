<?
// Cannot use form.inc because no session has started
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

require_once("XML/RPC.php");
require_once("inc/db.inc.php");
require_once("inc/auth.inc.php");
require_once("inc/utils.inc.php");

$code = '';
if (isset($_GET['s'])) {
	$code = $_GET['s'];
}

// find the message from authserver for this code
$messageinfo = loginMessageLink($code);

$TITLE = ($messageinfo)?getSystemSetting("displayname"):"Not Found";
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title>SchoolMessenger - <?=$TITLE?></title>
</head>
<body style='padding: 0; margin: 0px; margin: 15px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;'>
<?
if (!$messageinfo) {
?>
	<div>
		<h1>The requested information was not found.</h1>
		<p>The message your looking for doesn't exist anymore or has expired.</p>
	</div>
<?
} else {
?>
	<div>
		<img src="logo.img.php?hash=<?=crc32("cid".getSystemSetting("_logocontentid"))?>" />
	</div>
	<div style="margin-left: 15px">
		<h3><?=$TITLE?></h3>
	</div>
	<div style="margin:5px">
		<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
		CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
		STANDBY="Loading Windows Media Player components..."
		TYPE="application/x-oleobject">

		<PARAM NAME="FileName" VALUE="messagelink_preview.wav.php?jobcode=<?=$code?>">
		<param name="controller" value="true">
		<EMBED SRC="messagelink_preview.wav.php?jobcode=<?=$code?>" AUTOSTART="TRUE"></EMBED>
		</OBJECT>
	</div>
<?
}
?>
</body>
</html>
