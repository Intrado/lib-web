<?
header('Content-type: text/html; charset=UTF-8') ;
?>

<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title>Contact Manager<?=isset($TITLE) ? ": " . $TITLE : ""?></title>
    
	<link href='../css/css.inc.php' type='text/css' rel='stylesheet' media='screen'>
	<link href='../css/login.css' type='text/css' rel='stylesheet' media='screen'>
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
						<? if (isset($_SERVER["HTTPS"])) { ?>
									<div style="float:right">
										<table width="135" border="0" cellpadding="2" cellspacing="0" title="<?=_L("Click to Verify - This site chose VeriSign SSL for secure e-commerce and confidential communications.")?>">
											<tr>
											<td width="135" align="center" valign="top"><script src=https://seal.verisign.com/getseal?host_name=contactme.schoolmessenger.com&size=M&use_flash=YES&use_transparent=YES&lang=en></script><br />
											<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;"><?=_L("ABOUT SSL CERTIFICATES")?></a></td>
											</tr>
										</table>
									</div>
						<? } ?>
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

<body>
	
	
<div id="top_banner" class="banner cf">
	<div class="banner_logo"><img src="img/logo_small.gif" /></div>
</div><!-- end top_banner .banner -->
	
<div class="window cf">

	<div class="window_body_wrap cf">
	
		<div id="window" class="window_body cf">
			<h3 class="indexdisplayname">SchoolMessenger Contact Manager</h3>
		
			<div><img src="img/contactmanager_splash.jpg"></div>

<?
}
?>