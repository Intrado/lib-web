
<?
if(isset($_COOKIE['embeddedpage']) && $_COOKIE['embeddedpage']=="1"){
?>


<div style="background-color: #365F8D; color: #365F8D; height: 3px; margin-left:30px; margin-right:30px;">&nbsp;</div>
<?
} else {
?>

		</div><!-- end window_body -->

	</div><!-- end window_body_wrap -->
	
</div><!-- end window -->

<div id="footer" class="cf">	

<? if (isset($_SERVER["HTTPS"])) { ?>
			
		<div style="float:left">
			<script src=https://seal.verisign.com/getseal?host_name=contactme.schoolmessenger.com&size=M&use_flash=YES&use_transparent=YES&lang=<?=substr($LOCALE, 0, 2)?>></script>
			<p><a class="ssl" href="http://www.verisign.com/ssl-certificate/" target="_blank" title="Click to Verify - This site chose VeriSign SSL for secure e-commerce and confidential communications."><?=_L("About SSL Certificates")?></a></p>
		</div>
			
<? } ?>
		
		<div style="float:right">
			<p>&copy; <?=_L("1999-2013 Reliance Communications, Inc. All Rights Reserved.")?></p>
		</div>

</div>

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
