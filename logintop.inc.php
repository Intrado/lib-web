<?

$scheme = getCustomerData($CUSTOMERURL);
if($scheme == false){
	$scheme = array("_brandtheme" => "3dblue",
					"_supportemail" => "support@schoolmessenger.com",
					"_supportphone" => "8009203897",
					"colors" => array("_brandprimary" => "26477D"));
}
$CustomBrand = isset($scheme['productname']) ? $scheme['productname'] : "" ;
$primary = $scheme['colors']['_brandprimary'];
$custname = getCustomerName($CUSTOMERURL); // also found by getSystemSetting("displayname") but we may not be logged in yet

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
<body style='padding: 0; margin: 0px; margin-left: 15px; margin-right: 15px;font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; background-color: #f0f0f0; color: #595959;'>
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
</head>
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px; background-color: #<?=$primary?>;'>

<table border=0 cellpadding=0 cellspacing=0 width="100%">
<tr style="background-color: #FFFFFF;">
	<td width="389"><div style="padding-left:5px; padding-bottom:5px;"><img src="logo.img.php" /></div></td>
	<td width="100%">&nbsp;</td>
</tr>
<tr style="background-color: #666666;">
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<? // img/classroom_girl.jpg ?>
	<td style="background-color: #D4DDE2;"><img src="loginpicture.img.php"></td>
	<td style="background-color: #D4DDE2; color: #<?=$primary?>;">

		<table width="100%" style="color: #<?=$primary?>; text-align: right;">
			<tr>
				<td width="100%" style="font-size: 18px; font-weight: bold; text-align: right;"><?= escapehtml($custname) ?></div></td>
				<td><img src="img/spacer.gif" width="25"></td>
			</tr>
		</table>
<?
} /*CSDELETEMARKER_END*/
?>