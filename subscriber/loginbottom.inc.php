
<?
if(isset($_COOKIE['embeddedpage']) && $_COOKIE['embeddedpage']=="1"){
?>
		</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>
<div style="background-color: #365F8D; color: #365F8D; height: 3px; margin-left:30px; margin-right:30px;">&nbsp;</div>
<?
} else {
?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr style="background-color: white;">
		<td>&nbsp;</td>
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
	<tr style="background-color: white;">
		<td>&nbsp;</td>
		<td><div align="right">&copy; <?=_L("1999-2010 Reliance Communications, Inc. All Rights Reserved.")?></div></td>
	</tr>
</table>
<?
}
if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
</body>
</html>
