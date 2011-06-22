<?


////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("common.inc.php");
require_once("../inc/table.inc.php");

$messageinfo = reliablePageLinkCall("postPageGetForCode");



////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

if (!$messageinfo) {
	$theme = "classroom";
	$theme1 = "3e693f";
	$theme2 = "b47727";
	$TITLE = "School Messenger";
	$urlcomponent = "m";
} else {
	$theme = $messageinfo->brandinfo["theme"];
	$theme1 = $messageinfo->brandinfo["theme1"];
	$theme2 = $messageinfo->brandinfo["theme2"];
	$TITLE = escapehtml($messageinfo->customerdisplayname) . " - " . escapehtml($messageinfo->jobname);
	$urlcomponent = $messageinfo->urlcomponent;
	apache_note("CS_CUST",urlencode($messageinfo->urlcomponent)); //for logging
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

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<style>
		.navband1 {
			height: 6px; 
			background: #<?=$theme1 ?>;
		}
		.navband2 {
			height: 2px; 
			background: #<?=$theme1 ?>;
			margin-bottom: 3px;
		}
		.swooshbg {
			background: <?=fadecolor($theme2, "FFFFFF", .1)?>
		}
		body {
			padding: 0; margin: 0px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;
		}
	</style>
	<title><?=$TITLE?></title>
	<script type="text/javascript" src="page.js.php"></script>
</head>
<body>
	<table border=0 cellspacing=0 cellpadding=0 width="100%">
		<tr>
			<td>
				<div style="padding-left:10px; padding-bottom:10px">
					<img src="img.php?code=<?= escapehtml($CODE) ?>&id=<?= $messageinfo->logocontentid ?></img>" />
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

if ($messageinfo->nummessageparts > 0) {
	startWindow("Audio", false, false, false);

?>
		<div style="margin: 10px; margin-top: 15px; padding: 5px; float: left; border: 1px solid #3e693f">
			Phone Message: <?=date('M j, Y g:i a', $messageinfo->jobstarttime)?><br>
			<div style="font-size: 20px; margin-bottom: 5px"><?=escapehtml($messageinfo->customerdisplayname)?></div>
			<div style="font-size: 16px; margin-bottom: 2px"><?=escapehtml($messageinfo->jobname)?></div>
			<div style="font-size: 12px">&nbsp;&nbsp;<?=escapehtml($messageinfo->jobdescription)?></div>
		</div>
		<div style="margin:10px; clear:both;">
			<div id="player"></div>
	
			<script language="JavaScript" type="text/javascript">
		 		embedPlayer("player","<?= escapehtml($CODE) ?>",<?= $messageinfo->nummessageparts ?>);
			</script>
			<br><a href="a.mp3.php?code=<?= escapehtml($CODE) ?>&full">Click here to download</a>
		</div>
<?

	endWindow();
}

if ($messageinfo->pagecontent) {

	startWindow(escapehtml($messageinfo->jobname) . " - Posted " . date('l jS \of F Y h:i:s A', $messageinfo->jobstarttime), false, false, false);
	
	echo $messageinfo->pagecontent;
	
	endWindow();
	
}


if (count($messageinfo->attachments) > 0) {

	startWindow("Files", false, false, false);
?>
	<ul>
<?
	foreach ($messageinfo->attachments as $attachment) {
?>
		<li>
			<a href="content.php?code=<?= escapehtml($CODE) ?>&id=<?= $attachment->contentid?>&fn=<?= urlencode($attachment->filename)?>">
				<?= escapehtml($attachment->filename)?> (<?= number_format($attachment->size) ?>)
			</a>
		</li>
<?
	}
?>
	</ul>
<?	
	
	endWindow();
	
}


?>
	</div>
</body>
</html>