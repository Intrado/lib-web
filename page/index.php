<?


////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("common.inc.php");
require_once("../inc/table.inc.php");

$messageinfo = reliablePageLinkCall("postPageGetForCode");

$hasFiles = count($messageinfo->attachments) > 0;
$hasMedia = $messageinfo->nummessageparts > 0;


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

if (!$messageinfo) {
	$theme = "classroom";
	$primary = "3e693f";
	$theme1 = "3e693f";
	$theme2 = "b47727";
	$globalratio = ".2";
	$TITLE = "School Messenger";
	$urlcomponent = "m";
} else {
	$theme = $messageinfo->brandinfo["theme"];
	$primary = $messageinfo->brandinfo["primary"];
	$theme1 = $messageinfo->brandinfo["theme1"];
	$theme2 = $messageinfo->brandinfo["theme2"];
	$globalratio = $messageinfo->brandinfo['globalratio'];
	//title is used by facebook to the link name
	$TITLE = escapehtml($messageinfo->jobname) . " - " . escapehtml($messageinfo->customerdisplayname);
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
header('Content-type: text/html; charset=UTF-8') ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<style type="text/css">
		.navband1 {
			height: 6px; 
			background: #<?=$primary ?>;
		}
		.navband2 {
			height: 2px; 
			background: #<?=$theme2 ?>;
			margin-bottom: 3px;
		}
		.navlogoarea {
			background: <?=fadecolor($theme2, "FFFFFF", $globalratio/2)?>;
		}
		body {
			padding: 0; margin: 0px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif;
		}
		img {
			border: 0px;
		}
		.attachmentfile {
			font-size: 12pt;
		}
		.hoverlinks a {
			text-decoration: none;
		}
		.hoverlinks a:hover {
			text-decoration: underline;
		}
		.pageextras {
			float: left; width: 45%; margin-bottom: 15px;
		}
		.pageextras h3 {
			color: gray;
		}
		.extrasborder {
			border-right: 1px solid #BFBFBF;
			margin-right: 15px; 
		}
		.extrafiles li {
			list-style-type: none;
			margin-left: -35px;
		}
		
	</style>
	<title><?=$TITLE?></title>
	<script type="text/javascript" src="page.js"></script>
</head>
<body>

	<table class="navlogoarea" border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td bgcolor="white"><div style="padding-left:10px;"><img src="content.php?code=<?= escapehtml($CODE) ?>&id=<?= $messageinfo->logocontentid ?>" alt=""/></div></td>
			<td><img src="img/shwoosh.gif" alt=""/></td>
			<td width="100%"></td>
		</tr>
	</table>
	<div class="navband1"><img src="img/pixel.gif" alt=""/></div>
	<div class="navband2"><img src="img/pixel.gif" alt=""/></div>
	<div style="margin: 15px;">

	<h1><?=escapehtml($messageinfo->customerdisplayname)?></h1>
	<div style="color: gray; float: right;">Sent: <?=date('M j, Y', $messageinfo->jobstarttime)?></div>
	<h2><?=escapehtml($messageinfo->jobname)?></h2>

	<div style="clear: both; border-top: 1px solid #BFBFBF; margin-bottom: 15px;" ><img src="img/pixel.gif" alt=""/></div>

<?
if ($hasMedia) {
	$shouldDrawBorder = $hasFiles; //add border if showing both the player and the files list
	
?>
	<div class="pageextras <?= $shouldDrawBorder ? "extrasborder" : "" ?>"><h3>Media</h3>
	

		<div style="width: 165px;">
			<div id="player"></div>
			<a style="float: right;" href="a.mp3.php?code=<?= escapehtml($CODE) ?>&full&dl">Download</a>		
		</div>
		
		<script language="JavaScript" type="text/javascript">
	 		embedPlayer("player","<?= escapehtml($CODE) ?>",<?= $messageinfo->nummessageparts ?>);
		</script>
		
	</div>
<?
}

if ($hasFiles) {
?>

<div class="pageextras"><h3>Files</h3>

	<ul class="hoverlinks extrafiles">
<?
	foreach ($messageinfo->attachments as $attachment) {
?>
		<li>
			<a href="content.php?code=<?= escapehtml($CODE) ?>&id=<?= $attachment->contentid?>&fn=<?= urlencode($attachment->filename)?>">
				<img src="img/icons/page_white_put.png" style="margin-right: 10px;" alt="Download" /><?= escapehtml($attachment->filename)?>
			</a>
		</li>
<?
	}
?>
	</ul>

</div>
<?
}

//if we had either extras, need to clear floats and add another line
if ($hasMedia || $hasFiles) {
?>
<div style="clear: both; border-top: 1px solid #BFBFBF; margin-bottom: 15px;" ><img src="img/pixel.gif" alt=""/></div>
<?
}

if ($messageinfo->pagecontent) {
	echo $messageinfo->pagecontent;
}

?>

	</div>
</body>
</html>