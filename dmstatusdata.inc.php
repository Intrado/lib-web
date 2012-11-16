<?
?>
<div id='systemstats'>

	<b>General</b>
	<table width="100%">
		<tr><td width="30%">
			Active State:
		</td><td><div id='dmenabled'></div></td></tr>
		<tr><td width="30%">
			System Startup Time:
		</td><td><div id='startuptime'></div></td></tr>
		<tr><td width="30%">
			Current System Time:
		</td><td><div id='currenttime'></div></td></tr>
		<tr><td width="30%">
			System has been Running:
		</td><td><div id='sysrunning'></div></td></tr>
		<tr><td width="30%">
			Total Clock Resets:
		</td><td><div id='clockresetcount'></div></td></tr>
	</table>
	<div id="dispatchers"></div>
	<b>Resource Allocation</b>
	<table width="100%">
		<tr><td width="30%">
			Active Outbound:
		</td><td><div id='resactout'></div></td></tr>
		<tr><td width="30%">
			Idle Outbound:
		</td><td><div id='residleout'></div></td></tr>
		<tr><td width="30%">
			Active Inbound:
		</td><td><div id='resactin'></div></td></tr>
		<tr><td width="30%">
			Idle Inbound:
		</td><td><div id='residlein'></div></td></tr>
		<tr><td width="30%">
			Throttled by Schedule:
		</td><td><div id='resthrotsched'></div></td></tr>
		<tr><td width="30%">
			Throttled due to Congestion:
		</td><td><div id='resthroterr'></div></td></tr>
		<tr><td width="30%">
			Total Resources:
		</td><td><div id='restotal'></div></td></tr>
	</table>
	<b>Call Results</b>
	<table width="100%">
		<tr><td width="30%">
			Answered:
		</td><td><div id='A'></div></td></tr>
		<tr><td width="30%">
			Machine:
		</td><td><div id='M'></div></td></tr>
		<tr><td width="30%">
			Busy:
		</td><td><div id='B'></div></td></tr>
		<tr><td width="30%">
			No Answer:
		</td><td><div id='N'></div></td></tr>
		<tr><td width="30%">
			Disconnect:
		</td><td><div id='X'></div></td></tr>
		<tr><td width="30%">
			Unknown:
		</td><td><div id='F'></div></td></tr>
		<tr><td width="30%">
			Trunk Busy:
		</td><td><div id='TB'></div></td></tr>
		<tr><td width="30%">
			Other:
		</td><td><div id='failures'></div></td></tr>
		<tr><td width="30%">
			Inbound:
		</td><td><div id='inboundcompletedcount'></div></td></tr>
		<tr><td width="30%">
			Total Ring Time:
		</td><td><div id='dialtime'></div></td></tr>
		<tr><td width="30%">
			Total Call Time:
		</td><td><div id='billtime'></div></td></tr>
	</table>
	<b>Cache Information</b>
	<table width="100%">
		<tr><td width="30%">
			Location:
		</td><td><div id='cachelocation'></div></td></tr>
		<tr><td width="30%">
			Max Size:
		</td><td><div id='cachemax'></div></td></tr>
		<tr><td width="30%">
			Current Size:
		</td><td><div id='cachesize'></div></td></tr>
		<tr><td width="30%">
			Current File Count:
		</td><td><div id='cachefilecount'></div></td></tr>
		<tr><td width="30%">
			Deleted File Count:
		</td><td><div id='cachedelcount'></div></td></tr>
		<tr><td width="30%">
			Total Hits:
		</td><td><div id='cachehit'></div></td></tr>
		<tr><td width="30%">
			Total Misses:
		</td><td><div id='cachemiss'></div></td></tr>
	</table>
	<b>Download Audio Content</b>
	<table width="100%">
		<tr><td width="30%">
			Total Downloads:
		</td><td><div id='getcontentapicount'></div></td></tr>
		<tr><td width="30%">
			Total Time:
		</td><td><div id='getcontentapitime'></div></td></tr>
		<tr><td width="30%">
			Average Time:
		</td><td><div id='getcontentapiavg'></div></td></tr>
	</table>
	<b>Upload Audio Content</b>
	<table width="100%">
		<tr><td width="30%">
			Total Uploads:
		</td><td><div id='putcontentapicount'></div></td></tr>
		<tr><td width="30%">
			Total Time:
		</td><td><div id='putcontentapitime'></div></td></tr>
		<tr><td width="30%">
			Average Time:
		</td><td><div id='putcontentapiavg'></div></td></tr>
	</table>
	<b>Text-to-Speech Content</b>
	<table width="100%">
		<tr><td width="30%">
			Total Downloads:
		</td><td><div id='ttsapicount'></div></td></tr>
		<tr><td width="30%">
			Total Time:
		</td><td><div id='ttsapitime'></div></td></tr>
		<tr><td width="30%">
			Average Time:
		</td><td><div id='ttsapiavg'></div></td></tr>
	</table>
	<b>Continue Task Request</b>
	<table width="100%">
		<tr><td width="30%">
			Total Requests:
		</td><td><div id='continueapicount'></div></td></tr>
		<tr><td width="30%">
			Total Time:
		</td><td><div id='continueapitime'></div></td></tr>
		<tr><td width="30%">
			Average Time:
		</td><td><div id='continueapiavg'></div></td></tr>
	</table>
	<b>System Details</b>
	<table width="100%">
		<tr><td width="30%">
			Uptime:
		</td><td><div id='uptime'></div></td></tr>
		<tr><td width="30%">
			Disk Usage:
		</td><td><div id='diskspace'></div></td></tr>
		<tr><td width="30%">
			Memory Usage:
		</td><td><div id='memory'></div></td></tr>
	</table>

</div>
<?
?>
