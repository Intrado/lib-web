
</div>

<div id="termsinfo">
<? /*CSDELETEMARKER_START*/ if (!$IS_COMMSUITE) { ?>
Technical Support:&nbsp;<a href="mailto:<?=$_SESSION['_supportemail']?>"><?=$_SESSION['_supportemail']?></a>&nbsp;|&nbsp;<?=substr($_SESSION['_supportphone'],0,3) . "." . substr($_SESSION['_supportphone'],3,3) . "." . substr($_SESSION['_supportphone'],6,4);?>
<br>
Use of this system is subject to the <a href="privacy.html" target="_blank">Privacy Policy</a> and <a href="terms.html" target="_blank">Terms of Service</a>
<br>
<? } /*CSDELETEMARKER_END*/ ?>
&copy; 1999-2008 Reliance Communications, Inc. All Rights Reserved.

</div>
<?
print "<div id='logininfo' class='noprint' >Logged in as $USER->firstname $USER->lastname ($USER->login) <br> Current system time is " . date("F jS, Y h:i a (e)") . "</div>";

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
<img id="state" src="img/spacer.gif" width="1" height="1">
<? if (isset($_GET['timer'])) printf("<!-- %0.2f -->", microtime(true) - $PAGETIME) ?>
</body>
</html>
