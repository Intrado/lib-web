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
				  </table>
</td>
		<td style="background: url(img/themes/<?=$theme?>/win_r.gif);"></td>
	</tr>
	<tr>
		<td><img src="img/themes/<?=$theme?>/win_bl.gif" alt=""></td>
		<td style="background: url(img/themes/<?=$theme?>/win_b.gif);"></td>
		<td><img src="img/themes/<?=$theme?>/win_br.gif" alt=""></td>
	</tr>
</table>
</div>
<div style="text-align: right; padding: 5px; background-color: white;">
	<script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=S&use_flash=NO&use_transparent=NO&lang=en></script><br />
	<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;">About SSL Certificates</a>
</div>

<div style="background-color: #<?=$primary?>; color: white">
	<div style="text-align:right; font-size: 12px; margin: 5px;">
		<p>Service & Support:</p>
		<p><a style="color: white;" href="mailto:<?=$scheme['_supportemail']?>"><?=$scheme['_supportemail']?></a>&nbsp;|&nbsp;<?=substr($scheme['_supportphone'],0,3) . "." . substr($scheme['_supportphone'],3,3) . "." . substr($scheme['_supportphone'],6,4);?></p>
	</div>

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