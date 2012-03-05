
			  </div><!-- end window_body -->

	</div><!-- end window_body_wrap -->
	
</div><!-- end window -->


<div id="footer" class="cf">

	<div style="float: left;">
		<script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=S&use_flash=NO&use_transparent=NO&lang=en></script>
		<p><a class="ssl" href="http://www.verisign.com/ssl-certificate/" target="_blank" title="Click to Verify - This site chose VeriSign SSL for secure e-commerce and confidential communications.">About SSL Certificates</a></p>
	</div>


	<div style="float: right">
		<p>Service & Support:
		<a href="mailto:<?=$scheme['_supportemail']?>"><?=$scheme['_supportemail']?></a>&nbsp;|&nbsp;<?=substr($scheme['_supportphone'],0,3) . "." . substr($scheme['_supportphone'],3,3) . "." . substr($scheme['_supportphone'],6,4);?></p>
	</div>

</div>


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