
<table>
	<tr>
		<td>&nbsp;</td>
		<td colspan="3"><div style="background-color: #365F8D; color: #365F8D; height: 3px;">&nbsp;</div></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td width="100%">&nbsp;</td>
		<td align="right"><img src="img/school_messenger.gif" /></td>
		<td align="right">
			<script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=S&use_flash=NO&use_transparent=NO&lang=en></script><br />
			<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;">About SSL Certificates</a>
		</td>
		<td>&nbsp;</td>
	</tr>
</table>
<?
if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
</body>
</html>
