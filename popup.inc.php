<?
$pagetitle = preg_replace('/\\<.+>/','',(isset($TITLE) ? $TITLE : ""));
?>

<script>
	var _brandtheme = "<?=getBrandTheme();?>";
</script>


<html>
<head>
<title><?= getBrand();?>: <?= $pagetitle ?></title>
	<script src='script/utils.js'></script>
	<script src='script/nav.js'></script>
	<script src='script/sorttable.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css.php' type='text/css' rel='stylesheet' media='screen'>
</head>


<body onload="try {UpdateTimer();} catch (e) {}" style="margin: 0px; background-color: white;">
	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>



<!-- ********************************************************************* -->

<?
	if($clickurl = getSystemSetting('_logoclickurl'))
		$logo =  '<a href="' . $clickurl . '" target="_blank"><img src="logo.img.php"></a>';
	else
		$logo = '<img src="logo.img.php">';
?>


<div>
	<table width="100%" border=0 cellpadding=0 cellspacing=0 background="img/header_bg.gif" >
		<tr><td style="font-size:8px;">&nbsp;</td></tr>
	</table>
</div>

<div>
	<table width="100%" border=0 cellpadding=0 cellspacing=0>
	<tr>
<?	// LOGO ?>
		<td><div style="padding-left:10px; padding-bottom:10px;"><?=$logo?></div></td>
	</tr>
	</table>
</div>


<div class="pagetitle"><? if(isset($ICON)) print '<img src="img/icon_' . $ICON . '" align="absmiddle">'; ?> <?= (isset($TITLE) ? $TITLE : "") ?></div>
<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

<div class="content">
