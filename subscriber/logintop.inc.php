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
$primary = $scheme['colors']['_brandprimary'];

$CustomBrand = isset($scheme['productname']) ? $scheme['productname'] : "";
$custname = isset($scheme['customerName']) ? $scheme['customerName'] : "";
?>

<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title>SUBSCRIBER<?=isset($TITLE) ? ": " . $TITLE : ""?></title>
	
	<script src='script/utils.js'></script>
	<script src='script/sorttable.js'></script>
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
    
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet">
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css.php?skipcommon' type='text/css' rel='stylesheet' media='screen'>
</head>

<?
if((isset($_COOKIE['embeddedpage']) && $_COOKIE['embeddedpage']) || isset($_GET['embedded'])){
?>
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px;'>
	<table width="100%">
		<tr>
			<td colspan="3" align="right">
				<table>
					<tr>
						<td><img src="img/sm_white.gif" /></td>
						<td>
						<? /*CSDELETEMARKER_START*/ if (!$IS_COMMSUITE && isset($_SERVER["HTTPS"])) { ?>
									<div style="float:right">
										<table width="135" border="0" cellpadding="2" cellspacing="0" title="<?=_L("Click to Verify - This site chose VeriSign SSL for secure e-commerce and confidential communications.")?>">
											<tr>
											<td width="135" align="center" valign="top"><script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=M&use_flash=YES&use_transparent=YES&lang=en></script><br />
											<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;"><?=_L("ABOUT SSL CERTIFICATES")?></a></td>
											</tr>
										</table>
									</div>
						<? } /*CSDELETEMARKER_END*/ ?>
						</td>
						</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td width="20%">&nbsp;</td>
			<td width="60%">&nbsp;</td>
			<td width="20%">&nbsp;</td>
		<tr>
		<tr>
			<td>&nbsp;</td>
			<td>

<?
} else {
?>
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px; background-color: #<?=$primary?>;'>
	<table border=0 cellpadding=0 cellspacing=0 width="100%">
	<tr style="background-color: #FFFFFF;">
		<td><div style="padding-left:5px; padding-bottom:5px;"><img src="logo.img.php" /></div></td>
		<td>
			<br>
		</td>
	</tr>
	<tr style="background-color: #666666;">
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td width="320" valign="top" style="background-color: #D4DDE2; color: #365F8D;"><img src="loginpicture.img.php"></td>
		<td style="background-color: #D4DDE2; color: #<?=$primary?>;">
			<table>
				<tr>
					<td width="15px">&nbsp;</td>
					<td width="80%">&nbsp;</td>
					<td width="20%">&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
<?
}
?>