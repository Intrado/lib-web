<b>General</b>
<table width="100%">
	<tr><td width="20%">Active State: </td><td><? if ("true" == $systemstats['dmenabled']) echo "Enabled"; else echo "<font color=\"red\">Disabled</font>"  ?></td></tr>
	<tr><td>System Startup Time: </td><td><?= date("F jS, Y h:i a", $systemstats['startuptime'] / 1000) ?></td></tr>
	<tr><td>Current System Time: </td><td><?
		$currenttime = time();
		$syscurrenttime = $systemstats['currenttime'] / 1000;
		
		// if more than five minutes, then we probably lost connection
		if (abs($currenttime - $syscurrenttime) > (5*60)) {
			echo "<font color=\"red\">" . date("F jS, Y h:i a", $syscurrenttime) . "  (stale data, lost connection)</font>";
		} else {
			echo date("F jS, Y h:i a", $syscurrenttime);
		}
	?></td></tr>
	<tr><td>System Has Been Running: </td><td><?
		$diff = floor(($systemstats['currenttime'] - $systemstats['startuptime']) / 60000); // minutes running
		$days = floor($diff / (60*24));
		$remain = $diff % (60*24);
		$hours = floor($remain / 60);
		$mins = $remain % 60;
		
		echo $days . " days " . $hours . " hours " . $mins . " minutes";
		
		?></td></tr>
	<tr><td>Total Clock Resets: </td><td><?= $systemstats['clockresetcount'] ?></td></tr>
</table>

<?
foreach ($dispatchers as $dname => $dispatcher) {
?>
<b>Communication Activity <?= $dname ?></b>
<table width="100%">
	<tr><td width="20%">Connection Failures: </td><td><?= $dispatcher['comerr'] ?></td></tr>
	<tr><td>Connection Timeouts: </td><td><?= $dispatcher['comtimeout'] ?></td></tr>
	<tr><td>Read Timeouts: </td><td><?= $dispatcher['comreadtimeout'] ?></td></tr>
</table>
<?
}
?>

<b>Resource Allocation</b>
<table width="100%">
	<tr><td width="20%">Active Outbound: </td><td><?= $systemstats['resactout']  ?></td></tr>
	<tr><td>Idle Outbound: </td><td><?= $systemstats['residleout'] ?></td></tr>
	<tr><td>Active Inbound: </td><td><?= $systemstats['resactin'] ?></td></tr>
	<tr><td>Idle Inbound: </td><td><?= $systemstats['residlein'] ?></td></tr>
	<tr><td>Throttled by schedule: </td><td><?= $systemstats['resthrotsched']  ?></td></tr>
	<tr><td>Throttled by error: </td><td><?= $systemstats['resthroterr'] ?></td></tr>
	<tr><td>Total Resources: </td><td><?= $systemstats['restotal'] ?></td></tr>
</table>

<b>Call Results</b>
<table width="100%">
	<tr><td width="20%">Answered: </td><td><?= $systemstats['A'] ?></td></tr>
	<tr><td>Machine: </td><td><?= $systemstats['M'] ?></td></tr>
	<tr><td>Busy: </td><td><?= $systemstats['B'] ?></td></tr>
	<tr><td>No Answer: </td><td><?= $systemstats['N'] ?></td></tr>
	<tr><td>Disconnect: </td><td><?= $systemstats['X'] /* bad number */?></td></tr>
	<tr><td>Unknown: </td><td><?= $systemstats['F'] /* failures include trunk busy */ ?></td></tr>
	<tr><td>Trunk Busy: </td><td><?= $systemstats['TB'] ?></td></tr>
	<tr><td>Other: </td><td><?= $systemstats['failures'] /* not attempted resource failed before dial */ ?></td></tr>
	<tr><td>Total Ring Time: </td><td><?= $systemstats['dialtime'] ?></td></tr>
	<tr><td>Total Call Time: </td><td><?= $systemstats['billtime'] ?></td></tr>
</table>

<b>Cache Information</b>
<table width="100%">
	<tr><td width="20%">Location: </td><td><?= $systemstats['cachelocation'] ?></td></tr>
	<tr><td>Max Size: </td><td><?= $systemstats['cachemax'] ?></td></tr>
	<tr><td>Current Size: </td><td><?= $systemstats['cachesize'] ?></td></tr>
	<tr><td>Current File Count: </td><td><?= $systemstats['cachefilecount'] ?></td></tr>
	<tr><td>Deleted File Count: </td><td><?= $systemstats['cachedelcount'] ?></td></tr>
	<tr><td>Total Hits: </td><td><?= $systemstats['cachehit'] ?></td></tr>
	<tr><td>Total Misses: </td><td><?= $systemstats['cachemiss'] ?></td></tr>
</table>

<b>Download Audio Content</b>
<table width="100%">
	<tr><td width="20%">Total Downloads: </td><td><?= $systemstats['getcontentapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['getcontentapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['getcontentapicount'] == 0) echo "0"; else echo sprintf("%.2f", ($systemstats['getcontentapitime'] / $systemstats['contentapicount'])) ?></td></tr>
</table>

<b>Upload Audio Content</b>
<table width="100%">
	<tr><td width="20%">Total Uploads: </td><td><?= $systemstats['putcontentapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['putcontentapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['putcontentapicount'] == 0) echo "0"; else echo sprintf("%.2f", ($systemstats['putcontentapitime'] / $systemstats['contentapicount'])) ?></td></tr>
</table>

<b>Text-to-Speech Content</b>
<table width="100%">
	<tr><td width="20%">Total Downloads: </td><td><?= $systemstats['ttsapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['ttsapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['ttsapicount'] == 0) echo "0"; else echo sprintf("%.2f", ($systemstats['ttsapitime'] / $systemstats['ttsapicount'])) ?></td></tr>
</table>

<b>Continue Task Request</b>
<table width="100%">
	<tr><td width="20%">Total Requests: </td><td><?= $systemstats['continueapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['continueapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['continueapicount'] == 0) echo "0"; else echo sprintf("%.2f", ($systemstats['continueapitime'] / $systemstats['ttsapicount'])) ?></td></tr>
</table>

<b>System Details</b>
<table width="100%">
	<tr><td width="20%" valign="top">Uptime: </td><td><?= "<pre>" . nl2br($systemstats['uptime']) . "</pre>" ?></td></tr>
	<tr><td valign="top">Disk Usage: </td><td><?= "<pre>" . nl2br($systemstats['diskspace']) . "</pre>" ?></td></tr>
	<tr><td valign="top">Memory Usage: </td><td><?= "<pre>" . nl2br($systemstats['memory']) . "</pre>" ?></td></tr>
</table>
