<?
// Cannot use form.inc because no session has started
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

apache_note("CS_APP","ml"); //for logging

require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once("inc/thrift.inc.php");
require_once($GLOBALS['THRIFT_ROOT'].'/packages/messagelink/MessageLink.php');
require_once("inc/utils.inc.php");
require_once("inc/table.inc.php");

$code = '';
if (isset($_GET['s'])) {
	$code = $_GET['s'];
}

//Takes 2 hex color strings and 1 ratio to apply to to the primary:original
function fadecolor($primary, $fade, $ratio){
	$primaryarray = array(substr($primary, 0, 2), substr($primary, 2, 2), substr($primary, 4, 2));
	$fadearray = array(substr($fade, 0, 2), substr($fade, 2, 2), substr($fade, 4, 2));
	$newcolorarray = array();
	for($i = 0; $i<3; $i++){
		$newcolorarray[$i] = dechex(round(hexdec($primaryarray[$i]) * $ratio + hexdec($fadearray[$i])*(1-$ratio)));
	}
	$newcolor = "#" . implode("", $newcolorarray);
	return $newcolor;
}

$appservererror = false;
$badcode = false;

list($appserverprotocol, $appservertransport) = initMessageLinkApp();

if($appserverprotocol == null || $appservertransport == null) {
	error_log("Can not use AppServer");
	$appservererror = true;
} else {
	$attempts = 0;
	while(true) {
		try {
			$client = new MessageLinkClient($appserverprotocol);
			// Open up the connection
			$appservertransport->open();
			try {
				$messageinfo = $client->getInfo($code);
			} catch (messagelink_MessageLinkCodeNotFoundException $e) {
				$badcode = true;
				error_log("Unable to find the messagelinkcode: " . urlencode($code));
			}
			$appservertransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("getInfo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appservertransport->close();
			if($attempts > 2) {
				error_log("getInfo: Failed 3 times to get content from appserver");
				$appservererror = true;
				break;
			}
		}
	}
}

if ($appservererror || $badcode) {
	$theme = "classroom";
	$theme1 = "3e693f";
	$theme2 = "b47727";
	$TITLE = "School Messenger";
	$urlcomponent = "m";
} else {
	$theme = $messageinfo->brandinfo["theme"];
	$theme1 = $messageinfo->brandinfo["theme1"];
	$theme2 = $messageinfo->brandinfo["theme2"];
	$TITLE = escapehtml($messageinfo->customerdisplayname);
	$urlcomponent = $messageinfo->urlcomponent;
	apache_note("CS_CUST",urlencode($messageinfo->urlcomponent)); //for logging
	
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<style type="text/css">
		.navband1 {
			height: 6px; 
			background: #<?=$theme1 ?>;
		}
		.navband2 {
			height: 2px; 
			background: #<?=$theme2 ?>;
			margin-bottom: 3px;
		}
		.swooshbg {
			background: <?=fadecolor($theme2, "FFFFFF", .1)?>
		}
	</style>
	<title><?=$TITLE?></title>
</head>
<body style='padding: 0; margin: 0px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;'>
	<table border=0 cellspacing=0 cellpadding=0 width="100%">
		<tr>
			<td>
				<div style="padding-left:10px;">
					<img src="messagelinklogo.img.php?code=<?=escapehtml($code)?>" alt="" />
				</div>
			</td>
			<td>
				<div class="swooshbg">
					<img src="img/shwoosh.gif" alt="" />
				</div>
			</td>
			<td width="100%" class="swooshbg"></td>
		</tr>
	</table>
	<div class="navband1"><img src="img/pixel.gif" alt="" /></div>
	<div class="navband2"><img src="img/pixel.gif" alt="" /></div>
	<div style="margin: 15px">
<?
startWindow("Message Preview", false, false, false);
if ($appservererror) {
?>
	<div>
		<h1></h1>
		<p>An error occurred while trying to retrieve your message. Please try again.</p>
	</div>
<?
} else if($badcode){
?>
	<div>
		<h1>The requested information was not found.</h1>
		<p>The message you are looking for does not exist or has expired.</p>
	</div>
<?	
} else {
?>
	<div style="margin: 10px; margin-top: 15px; padding: 5px; float: left; border: 1px solid #3e693f">
		Phone Message: <?=date('M j, Y g:i a', $messageinfo->jobstarttime)?><br />
		<div style="font-size: 20px; margin-bottom: 5px"><?=escapehtml($messageinfo->customerdisplayname)?></div>
		<div style="font-size: 16px; margin-bottom: 2px"><?=escapehtml($messageinfo->jobname)?></div>
		<div style="font-size: 12px">&nbsp;&nbsp;<?=escapehtml($messageinfo->jobdescription)?></div>
	</div>
	<div style="margin:10px; clear:both;">
		<div id="player"></div>		
		<script type="text/javascript" language="javascript" src="script/prototype.js"></script>	
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
		<script language="JavaScript" type="text/javascript">
	 		embedPlayer("messagelinkaudio.mp3.php?code=<?=escapehtml($code)?>","player",<?= $messageinfo->nummessageparts ?>);
		</script>
		<br /><a href="messagelinkaudio.mp3.php?code=<?=escapehtml($code)?>">Click here to download</a>
	</div>
<?
}
endWindow();
?>
	</div>
</body>
</html>
