
</div>
<?

	if(isset($ERRORS) && is_array($ERRORS)) {
		foreach($ERRORS as $key => $value) {
			$ERRORS[$key] = addslashes($value);
		}
		print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
	}

	print "<div > Current system time is " . date("F jS, Y h:i a (e)") . "</div>";
?>
</body>
</html>