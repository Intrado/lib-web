<?
include_once('common.inc.php');

$PAGE = "start:start";
$TITLE = _L("Unauthorized");

include_once('nav.inc.php');

?>
<table border="0" cellpadding="0" cellspacing="0" height="400" width="100%">
	<tr>
		<td valign="middle" align="center">
			<p id="navtitle"><? if(isset($_SESSION['portaluser'])) { echo _L("Sorry, you are not authorized to use this feature"); } else { echo _L("Sorry, your session has expired"); } ?></p>
			<p id="navdesc"><? if(isset($_SESSION['portaluser'])) { echo _L("Please navigate to another page"); } else { ?><a href="index.php?logout=1"><?=_L("Click here to log in")?></a><? } ?></p>
			<p><?=_L("You will be automatically redirected in 10 seconds")?></p>
			<p>&nbsp;</p>
		</td>
	</tr>
</table>
<?
include_once('navbottom.inc.php');
?>
<script language="javascript">setTimeout("window.location='start.php'", 10000);</script>
