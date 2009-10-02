<?
require_once("inc/utils.inc.php");
require_once("inc/table.inc.php");

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

$TITLE = "Scheduled Maintenance";

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
					<img src="img/logo_small.gif" />
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
	<div style="margin-top: 15px; margin-left: 30px; margin-right: 30px">
<?
startWindow("Scheduled Maintenance", false, false, false);
?>
	<div style="margin: 10px; margin-top: 15px; padding: 5px; float: left;">
		This site is currently undergoing scheduled maintenance. Please check back later.
	</div>
<?
endWindow();
?>
	</div>
</body>
</html>
