<?
$PAGE = "start:start";
$TITLE = "Unauthorized";

include_once('common.inc.php');
include_once('nav.inc.php');
?>
<table border="0" cellpadding="0" cellspacing="0" height="400" width="100%">
	<tr>
		<td valign="middle" align="center">
			<p id="navtitle"><? if(isset($_SESSION['portaluser'])) { ?>Sorry, you are not authorized to use this feature<? } else { ?>Sorry, your session has expired<? } ?></p>
			<p id="navdesc"><? if(isset($_SESSION['portaluser'])) { ?>Please navigate to another page<? } else { ?><a href="index.php?logout=1">Click here</a> to log in<? } ?></p>
			<p>You will be automatically redirected in 10 seconds</p>
			<p>&nbsp;</p>
		</td>
	</tr>
</table>
<?
include_once('navbottom.inc.php');
?>
<script language="javascript">setTimeout("window.location='<? print is_object($USER) ? 'start.php' : './'; ?>';", 10000);</script>