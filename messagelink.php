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
require_once("inc/DBMappedObject.php");
require_once("obj/Job.obj.php");

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

// find the message from authserver for this code
$messageinfo = loginMessageLink($code);
$customer = "Not Found";

$badcode = true;
$urlcomponent = 'm';
if ($messageinfo !== false) {
	$badcode = false;
	$customer = ($messageinfo)?getSystemSetting("displayname"):"Not Found";
	$urlcomponent = getSystemSetting('urlcomponent');
	$job = new Job($messageinfo['jobid']+0);
	$jobname = escapehtml($job->name);
	$jobdescription = escapehtml($job->description);
	$jobstarttime = strtotime($job->startdate . $job->starttime);
}

$TITLE = $customer;
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<style>
		.navband1 {
			height: 6px; 
			background: #3e693f;
		}
		.navband2 {
			height: 2px; 
			background: #b47727;
			margin-bottom: 3px;
		}
		.swooshbg {
			background: <?=fadecolor("b47727", "FFFFFF", .1)?>
		}
	</style>
	<title><?=$TITLE?></title>
</head>
<body style='padding: 0; margin: 0px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;'>
	<table border=0 cellspacing=0 cellpadding=0 width="100%">
		<tr>
			<td>
				<div style="padding-left:10px; padding-bottom:10px">
					<img src="logo.img.php?urlcomponent=<?=$urlcomponent?>" />
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
startWindow("Message Preview", false, false, false);
if ($badcode) {
?>
	<div>
		<h1>The requested information was not found.</h1>
		<p>The message you are looking for does not exist or has expired.</p>
	</div>
<?
} else {
?>
	<div style="margin: 10px; margin-top: 15px; padding: 5px; float: left; border: 1px solid #3e693f">
		Phone Message: <?=date('m/d/Y g:i a', $jobstarttime)?><br>
		<div style="font-size: 20px; margin-bottom: 5px"><?=$customer?></div>
		<div style="font-size: 16px; margin-bottom: 2px"><?=$jobname?></div>
		<div style="font-size: 12px">&nbsp;&nbsp;<?=$jobdescription?></div>
	</div>
	<div style="margin:10px; clear:both;">
		<div id="player"></div>		
		<script type="text/javascript" language="javascript" src="script/prototype.js"></script>	
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
		<script language="JavaScript" type="text/javascript">
	 		embedPlayer("messagelink_preview.wav.php/embed_preview.wav?jobcode=<?=$code?>","player");
		</script>
		<br><a href="messagelink_preview.wav.php/message.wav?jobcode=<?=$code?>">Click Here</a>
	</div>
<?
}
endWindow();
?>
	</div>
</body>
</html>
