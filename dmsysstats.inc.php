<b>General</b>
<table width="100%">
	<tr><td width="20%">Active State: </td><td><? if ("true" == $systemstats['dmenabled']) echo "Enabled"; else echo "<font color=\"red\">Disabled</font>"  ?></td></tr>
	<tr><td>Current System Time: </td><td><?= $systemstats['currenttime'] ?></td></tr>
	<tr><td>Total Clock Resets: </td><td><?= $systemstats['clockresetcount'] ?></td></tr>
</table>

<?
foreach ($dispatchers as $dname => $dispatcher) {
?>
<b>Communication Activity <?= $dname ?></b>
<table width="100%">
	<tr><td width="20%">Failures: </td><td><?= $dispatcher['comerr'] ?></td></tr>
	<tr><td>Timeouts: </td><td><?= $dispatcher['comtimeout'] ?></td></tr>
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
	<tr><td>Bad Number: </td><td><?= $systemstats['X'] ?></td></tr>
	<tr><td>Failed: </td><td><?= $systemstats['F'] ?></td></tr>
	<tr><td>Not Attempted: </td><td><?= $systemstats['failures'] ?></td></tr>
	<tr><td>Total Dial Time: </td><td><?= $systemstats['dialtime'] ?></td></tr>
	<tr><td>Total Play Time: </td><td><?= $systemstats['playtime'] ?></td></tr>
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
	<tr><td width="20%">Total Fetches: </td><td><?= $systemstats['getcontentapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['getcontentapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['getcontentapicount'] == 0) echo "0"; else echo ($systemstats['getcontentapitime'] / $systemstats['contentapicount']) ?></td></tr>
</table>

<b>Upload Audio Content</b>
<table width="100%">
	<tr><td width="20%">Total Fetches: </td><td><?= $systemstats['putcontentapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['putcontentapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['putcontentapicount'] == 0) echo "0"; else echo ($systemstats['putcontentapitime'] / $systemstats['contentapicount']) ?></td></tr>
</table>

<b>Text-to-Speech Content</b>
<table width="100%">
	<tr><td width="20%">Total Fetches: </td><td><?= $systemstats['ttsapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['ttsapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['ttsapicount'] == 0) echo "0"; else echo ($systemstats['ttsapitime'] / $systemstats['ttsapicount']) ?></td></tr>
</table>

<b>Continue Task Request</b>
<table width="100%">
	<tr><td width="20%">Total Fetches: </td><td><?= $systemstats['continueapicount'] ?></td></tr>
	<tr><td>Total Time: </td><td><?= $systemstats['continueapitime'] ?></td></tr>
	<tr><td>Average Time: </td><td><? if ($systemstats['continueapicount'] == 0) echo "0"; else echo ($systemstats['continueapitime'] / $systemstats['ttsapicount']) ?></td></tr>
</table>

<b>System Details</b>
<table width="100%">
	<tr><td width="20%" valign="top">Uptime: </td><td><?= nl2br($systemstats['uptime']) ?></td></tr>
	<tr><td valign="top">Disk Usage: </td><td><?= nl2br($systemstats['diskspace']) ?></td></tr>
	<tr><td valign="top">Memory Usage: </td><td><?= nl2br($systemstats['memory']) ?></td></tr>
</table>
