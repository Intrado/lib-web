<?
require_once("obj/Phone.obj.php");

if (isset($_GET['iframe'])) {
	include_once("iframebottom.inc.php");
	return;
}

?>
</div><!-- end for container starts in nav.inc.php -->
</div><!-- end for content_wrap starts in nav.inc.php -->
</div><!-- end for wrap starts in nav.inc.php -->

<div id="footer" class="cf">
<div class="contain">

<div id="termsinfo">
Service &amp; Support:&nbsp;<a href="mailto:<?=$_SESSION['_supportemail']?>"><?=$_SESSION['_supportemail']?></a>&nbsp;|&nbsp;(<?=substr($_SESSION['_supportphone'],0,3) . ")&nbsp;" . substr($_SESSION['_supportphone'],3,3) . "-" . substr($_SESSION['_supportphone'],6,4);?>
<br>
Use of this system is subject to the <a href="http://www.schoolmessenger.com/privacy-policy" target="_blank">Privacy Policy</a> and <a href="terms.html" target="_blank">Terms of Service</a>
<br>
&copy; 1999-<? echo date('Y'); ?> SchoolMessenger. All Rights Reserved.

</div>
<?
print "<div id='logininfo' class='noprint' >Logged in as ".escapehtml($USER->firstname)." ".escapehtml($USER->lastname)." (".escapehtml($USER->login).")";
$inboundnumber = getSystemSetting("inboundnumber");
if ($inboundnumber)
	echo "<br>Remote phone access #: <b>" . Phone::format($inboundnumber) . "</b>";
echo "<br>Current system time is " . date("F jS, Y h:i a (e)") . "</div>";
?>

</div>
</div><!-- #footer -->

<!-- div class="branding"><? dologo() ?></div-->

<script type="text/javascript">
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
<img id="state" src="img/spacer.gif" width="1" height="1" alt="">
<? if (isset($PAGETIME)) printf("<!-- %0.2f -->", microtime(true) - $PAGETIME) ?>
<!-- <?=$LOCALE ?> -->
</body>
</html>
