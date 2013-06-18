
</div><!-- End container -->
</div><!-- End content_wrap -->
</div><!-- End wrap -->
<?

	if(isset($ERRORS) && is_array($ERRORS)) {
		foreach($ERRORS as $key => $value) {
			$ERRORS[$key] = addslashes($value);
		}
		print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
	}
?>
	<div class='footer' id='footer'>
		<span class="timestamp">Current system time is: <?=date("F jS, Y h:i a (e)")?></span>
	</div>
</body>
</html>