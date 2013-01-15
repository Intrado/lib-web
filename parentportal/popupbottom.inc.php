
</div>
</div>
<?
if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
if(isset($STATUS) && is_array($STATUS)) {
	foreach($STATUS as $key => $value) {
		$STATUS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.status = \'' . implode('. ', $STATUS) . '.\';</script>';
}
?>
</body>
</html>