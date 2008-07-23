<?
if ($IS_COMMSUITE) {
?>
			</td>
		</tr>
	</table>
<?

} /*CSDELETEMARKER_START*/ else {
?>
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

<tr style="background-color: #<?=$primary?>; color: white;">
	<td colspan="2"><div style="text-align:right; font-size: 12px; margin: 5px;">
		<p>Service & Support:</p>
		<p><a style="color: white;" href="mailto:<?=$scheme['_supportemail']?>"><?=$scheme['_supportemail']?></a>&nbsp;|&nbsp;<?=substr($scheme['_supportphone'],0,3) . "." . substr($scheme['_supportphone'],3,3) . "." . substr($scheme['_supportphone'],6,4);?></p>
	</div></td>
</tr>
</table>

<?


}/*CSDELETEMARKER_END*/

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
</body>
</html>