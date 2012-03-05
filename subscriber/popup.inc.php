<?
header('Content-type: text/html; charset=UTF-8') ;

$pagetitle = preg_replace('/\\<.+>/','',(isset($TITLE) ? $TITLE : ""));


function doLogo () {
	$logohash = crc32("cid".getSystemSetting("_logocontentid"));
	echo '<img src="logo.img.php?hash=' . $logohash .'" alt="">';
}
?>
<script>
	var _brandtheme = "<?=getBrandTheme();?>";
</script>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title>SchoolMessenger: <?= $pagetitle ?></title>
	<script src='script/utils.js'></script>
	<script src='script/sorttable.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css.php' type='text/css' rel='stylesheet' media='screen'>
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
</head>
<body onload="try {UpdateTimer();} catch (e) {}" style="margin: 0px; background-color: white;">

<div style="padding-left:10px; "><? doLogo() ?></div>
<div class="pagetitle"><? if(isset($ICON)) print '<img src="img/icon_' . $ICON . '" align="absmiddle">'; ?> <?= (isset($TITLE) ? $TITLE : "") ?></div>
<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

<div class="content">
