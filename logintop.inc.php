<?

$scheme = getCustomerData($CUSTOMERURL);

if ($scheme == false) {
	$scheme = array("_brandtheme" => "classroom",
					"colors" => array(
						"_brandprimary" => "3e693f",
						"_brandtheme1" => "3e693f",
						"_brandtheme2" => "b47727",
						"_brandratio" => ".2"));
}
$theme = $scheme['_brandtheme'];
$primary = $scheme['colors']['_brandprimary'];
$theme1 = $scheme['colors']['_brandtheme1'];
$theme2 = $scheme['colors']['_brandtheme2'];
$globalratio = $scheme['colors']['_brandratio'];

$topbg = fadecolor($theme2, "FFFFFF", $globalratio/2);

$CustomBrand = isset($scheme['productname']) ? $scheme['productname'] : "";
$custname = isset($scheme['customerName']) ? $scheme['customerName'] : "";

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

if (!isset($scheme['_supportemail']))
	$scheme['_supportemail'] = "support@schoolmessenger.com";
	
if (!isset($scheme['_supportphone']))
	$scheme['_supportphone'] = "8009203897";
	

header('Content-type: text/html; charset=UTF-8') ;

if ($IS_COMMSUITE) {
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title>SchoolMessenger <?=$TITLE?></title>
	<script src='script/utils.js'></script>
	<script src='script/nav.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>	
</head>
<body style='padding: 0; margin: 0px; font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; background-color: #f0f0f0; color: #595959;'>
<?
$logofilename = "img/customlogo.gif";
if (file_exists($logofilename) ) {
?>
<img style="margin: 15px; border: solid 15px  white;" src="<?= $logofilename ?>">
<? } else { ?>
<br><br><br><br>
<? } ?>
<table align="center" cellpadding="8" cellspacing="0" style="border: 7px solid #9B9B9B; background-color: white;">
	<tr>
		<td bgcolor="#365F8D"><img style="margin-left: 10px; margin-top: 5px; margin-bottom: 5px; display: inline;" src='img/school_messenger.gif' /></td>
		<td bgcolor="#365F8D" align="center"><div style='margin-top: 3px; margin-right: 10px; font-size: large; display: inline; float: right; color: white;'><?= escapehtml($custname) ?></div></td>
	</tr>
	<tr>
		<td colspan="2">
<?
} /*CSDELETEMARKER_START*/ else {
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title><?=$CustomBrand?> <?=$TITLE?></title>
	
	<style>	
.navband1 {
	height: 6px; 
	background: #<?=$primary?>;
}

.navband2 {
	height: 2px; 
	background: #<?=$theme2?>;
}

.navlogoarea {
	background-color: <?=$topbg?>;
}

.indexform {
	font-size: 110%;
	color: #<?=$primary?>;
}

.indexform input {
	font-size: 12pt;
	color: #<?=$primary?>;
}

	</style>
	
<script langauge="javascript">

function capslockCheck(e){
	var keypressed;
	var shiftkey;

	if(e.keyCode)
		keypressed = e.keyCode;
	else
		keypressed = e.which;

	if(e.shiftKey) {
		shiftkey = true;
	} else {
		if(keypressed == 16) {
			shiftkey = true;
		} else {
			shiftkey = false;
		}
	}
	if(((keypressed >= 65 && keypressed <= 90) && !shiftkey) || ((keypressed >= 97 && keypressed <= 122) && shiftkey)){
		new getObj('capslockwarning').style.display = 'block';
	} else {
		new getObj('capslockwarning').style.display = 'none';
	}
}

function getObj(name)
{
  if (document.getElementById)
  {
	this.obj = document.getElementById(name);
  }
  else if (document.all)
  {
	this.obj = document.all[name];
  }
  else if (document.layers)
  {
	this.obj = document.layers[name];
  }
  if(this.obj)
	this.style = this.obj.style;
}

</script>
</head>
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px; background-color:<?=$primary?>'>

<table class="navlogoarea" border=0 cellspacing=0 cellpadding=0 width="100%">
	<tr>
		<td bgcolor="white"><div style="padding-left:10px; padding-bottom:10px;"><img src="logo.img.php" /></div></td>
		<td><img src="img/shwoosh.gif" alt=""></td>
		<td width="100%" align="right" style="padding-right: 10px;"></td>
	</tr>
</table>
<div class="navband1"><img src="img/pixel.gif"></div>
<div class="navband2"><img src="img/pixel.gif"></div>
<div style="background: url(img/header_bg.gif); height:10px;"><img src="img/pixel.gif"></div>


<div style="background-color: white;">

<table border="0" cellpadding="0" cellspacing="0" style="width: 79%; margin-left: 10%; margin-right: 10%; background-color: white;">
	<tr>
		<td><img src="img/themes/<?=$theme?>/win_tl.gif" alt=""></td>
		<td width="100%" style="background: url(img/themes/<?=$theme?>/win_t.gif);"></td>
		<td><img src="img/themes/<?=$theme?>/win_tr.gif" alt=""></td>
	</tr>
	<tr>
		<td style="background: url(img/themes/<?=$theme?>/win_l.gif);"></td>
		<td><table order="0" cellpadding="0" cellspacing="0">
			  <tr>
				  <td valign="top"><img style="float: left; margin-top: 10px;" src="loginpicture.img.php" alt=""></td>
				  <td width="100%" valign="top">


<?
} /*CSDELETEMARKER_END*/
?>