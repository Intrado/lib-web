
</div>

<div id="termsinfo">
Service & Support:&nbsp;<a href="mailto:<?=$_SESSION['_supportemail']?>"><?=$_SESSION['_supportemail']?></a>&nbsp;|&nbsp;<?=substr($_SESSION['_supportphone'],0,3) . "." . substr($_SESSION['_supportphone'],3,3) . "." . substr($_SESSION['_supportphone'],6,4);?>
<br>
<? /*CSDELETEMARKER_START*/ if (!$IS_COMMSUITE) { ?>
Use of this system is subject to the <a href="privacy.html" target="_blank">Privacy Policy</a> and <a href="terms.html" target="_blank">Terms of Service</a>
<br>
<? } /*CSDELETEMARKER_END*/ ?>
&copy; 1999-2009 Reliance Communications, Inc. All Rights Reserved.

</div>
<?
print "<div id='logininfo' class='noprint' >Logged in as ".escapehtml($USER->firstname)." ".escapehtml($USER->lastname)." (".escapehtml($USER->login).")<br>Current system time is " . date("F jS, Y h:i a (e)") . "</div>";

?>
<script language="javascript">
<?

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print 'window.alert(\'' . implode('.\n', $ERRORS) . '.\');';
}

if (isset($TIPS) && is_array($TIPS) ) {	
?>
	var tips = $A(<?= json_encode($TIPS) ?>);
	tips.each(function (t) {
		var e = $(t[0]);
		new Tip(e, t[1], {
			style: "protogrey",
			radius: 3,
			border: 3,
			hook: { tip: 'leftTop', mouse: true },
			offset: { x: 10, y: 0 }
		});
	});
<?
}
?>
</script>
<img id="state" src="img/spacer.gif" width="1" height="1">
<? if (isset($PAGETIME)) printf("<!-- %0.2f -->", microtime(true) - $PAGETIME) ?>
<!-- <?=$LOCALE ?> -->
</body>
</html>
