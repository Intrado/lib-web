
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
			<div style="text-align: right; margin: 5px;">
				<script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=S&use_flash=NO&use_transparent=NO&lang=en></script><br />
				<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;">About SSL Certificates</a>
			</div>
		</td>
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
