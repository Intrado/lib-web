<?
$pagetitle = preg_replace('/\\<.+>/','',(isset($TITLE) ? $TITLE : ""));
header('Content-type: text/html; charset=UTF-8') ;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

	<title><?= getBrand();?>: <?= $pagetitle ?></title>
	
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>

	<script src='script/utils.js'></script>
	<script src='script/sorttable.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen">
	
	<script src="script/form.js.php" type="text/javascript"></script>
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet">
	
<!--[if lte IE 6]>
    <link href="css/ie6.css" type="text/css" rel="stylesheet"/>
<![endif]-->

<!--[if lte IE 7]>
    <link href="css/ie7.css" type="text/css" rel="stylesheet"/>
<![endif]-->
	
</head>


<body style="margin: 0px; background-color: white;" onBeforeUnLoad="if(typeof(unloadsession) != 'undefined') {unloadsession();}">
	<script>
		var _brandtheme = "<?=getBrandTheme();?>";
	</script>
	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>


<!-- ********************************************************************* -->

<?
	$logo = '<img src="logo.img.php?hash=' . crc32("cid".getSystemSetting("_logocontentid")) . '">';
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
		<td><div style="padding-left:10px; "><?=$logo?></div></td>
	</tr>
	</table>
</div>


<div class="pagetitle"><? if(isset($ICON)) print '<img src="img/icon_' . $ICON . '" align="absmiddle">'; ?> <?= (isset($TITLE) ? $TITLE : "") ?></div>
<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

<div class="content">
