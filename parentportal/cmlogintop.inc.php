
<html>
<head>
	<title>Contact Manager<?=isset($TITLE) ? ": " . $TITLE : ""?></title>
	<script src='script/utils.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css/style.css' type='text/css' rel='stylesheet' media='screen'>
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
						<? /*CSDELETEMARKER_START*/ if (!$IS_COMMSUITE) { ?>
									<div style="float:right">
										<table width="135" border="0" cellpadding="2" cellspacing="0" title="Click to Verify - This site chose VeriSign SSL for secure e-commerce and confidential communications.">
											<tr>
											<td width="135" align="center" valign="top"><script src=https://seal.verisign.com/getseal?host_name=contactme.schoolmessengerr.com&size=M&use_flash=YES&use_transparent=YES&lang=en></script><br />
											<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;">ABOUT SSL CERTIFICATES</a></td>
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
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px; background-color: #365F8D;'>
	<table border=0 cellpadding=0 cellspacing=0 width="100%">
	<tr style="background-color: #365F8D;">
		<td colspan="2"><img src="img/school_messenger.gif" /></td>
	</tr>
	<tr style="background-color: #666666;">
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td width="320" valign="top" style="background-color: #D4DDE2; color: #365F8D;"><img src="img/contactmanager_splash.jpg"></td>
		<td style="background-color: #D4DDE2; color: #365F8D;">
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