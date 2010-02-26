<?
$TITLE = "Classroom Time Information";
$PAGE = "unauthorized:unauthorized";
require_once('inc/common.inc.php');
require_once("obj/Schedule.obj.php");
require_once("inc/html.inc.php");



$schedule = DBFind("Schedule","from job j inner join schedule s on (j.scheduleid = s.id) where j.type = 'alert' and j.status = 'repeating'","s");

if(!$schedule) {
	redirect('unauthorized.php');
}

$cutoff = strtotime($schedule->time);
$errortext = "";
if(strpos($schedule->daysofweek, Date('w',time() + 1)) === false) {

	$errortext .= _L('The Classroom Comment Feature is Disabled for Today');
} else {
	if($cutoff < time()) {
		$errortext .= _L('The Classroom Comment Feature is Disabled After: %s', Date('g:i a',$cutoff));
	} else {
		redirect('classroommessageoverview.php');
	}
}

$dows = explode(',',$schedule->daysofweek);
if(!empty($dows)) {
	$today = Date('w',time()) + 1;
	$next = $today % 7 + 1;
	while(!in_array($next,$dows) && $next != $today) {
		$next = $next % 7 + 1;
	}
	$weekdays = array(_L('Sunday'),_L('Monday'),_L('Tuesday'),_L('Wednesday'),_L('Thursday'),_L('Friday'),_L('Saturday'));
	$errortext .= "<br />" . _L('Classroom Comments will be available on %s after 1:00 am', $weekdays[$next - 1]);
}


require_once('nav.inc.php');

?>

<table border="0" cellpadding="0" cellspacing="0" height="400" width="100%">
	<tr>
		<td valign="middle" align="center">
			<p id="navtitle"><? if(is_object($USER)) { echo $errortext; } else { ?>Sorry, your session has expired<? } ?></p>
			<p id="navdesc"><? if(is_object($USER)) { ?>Please navigate to another page <? } else { ?><a href="index.php?logout=1">Click here</a> to log in<? } ?></p>
			<p>You will be automatically redirected in 20 seconds</p>
			<p>&nbsp;</p>
		</td>
	</tr>
</table>
<?
require_once('navbottom.inc.php');
?>
<script language="javascript">setTimeout("window.location='<?= is_object($USER) ? 'classroommessageoverview.php' : './'; ?>';", 20000);</script>