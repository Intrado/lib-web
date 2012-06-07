
</div><!-- .content_wrap started in nav.inc.php -->


<div id="footer" class="cf">

<div id="termsinfo">
<?=_L('Use of this system is subject to the %1$s and %2$s', '<a href="locale/' . $LOCALE . '/privacy.html" target="_blank">' . _L("Privacy Policy") . "</a>", '<a href="locale/' . $LOCALE . '/terms.html" target="_blank">' . _L("Terms of Service") . "</a>")?>
<br>
&copy; 1999-2012 Reliance Communications, Inc. <?=_L("All Rights Reserved.")?>

</div>
<?

print "<div id='logininfo' class='noprint' >" . _L('Logged in as %1$s %2$s (%3$s) ', escapehtml($_SESSION['portaluser']['portaluser.firstname']), escapehtml($_SESSION['portaluser']['portaluser.lastname']), $_SESSION['portaluser']['portaluser.username']) . " <br> " . _L('Current system time is %s', date("F jS, Y h:i a (e)")) . "</div>";

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
<img id="state" src="img/spacer.gif" width="1" height="1">
<? if (isset($_GET['timer'])) printf("<!-- %0.2f -->", microtime(true) - $PAGETIME) ?>

</div><!-- #footer -->

</body>
</html>
