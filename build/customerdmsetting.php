<?
///////////////////////////////////////////
// create/edit customer dmsetting database records
// Usage: php customerdmsetting <dbuser> <dbpass>
///////////////////////////////////////////


include_once("relianceutils.php");


if(isset($argv[1])){
	$dbuser = $argv[1];
	$dbpass = "";
	if (isset($argv[2])) $dbpass = $argv[2];
} else {
	$confirm = "n";
	while($confirm != "y"){
		echo "\nEnter DB User:\n";
		$dbuser = trim(fread(STDIN, 1024));
		echo "\nEnter DB Pass:\n";
		$dbpass = trim(fread(STDIN, 1024));
		echo "DBUSER: " . $dbuser . "\n";
		echo "DBPASS: " . $dbpass . "\n";
		$confirm = generalMenu(array("Is this information correct?", "y or n"), array("y", "n"));
	}
}

echo "Connecting to mysql...\n";
$authdb = mysql_connect("127.0.0.1", $dbuser, $dbpass)
	or die("Failed to connect to database");
mysql_select_db("commsuite")
	or die("Failed to access authserver database");

echo "connection ok\n";

////////////////////////////////////
// select dm to manage

$query = "select id, name, lastip, enablestate from dm";
$res = mysql_query($query,$authdb)
	or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

$numdms = mysql_num_rows($res);
if ($numdms == 0) {
	echo "There are currently no DMs in this database.";
	exit();
}

$i=1;
$dmrow=array();
while ($row=mysql_fetch_row($res)) {
	$dmrow[$i] = $i;
	echo "[".$i++."] ".$row[1]." ".$row[2]." ".$row[3]."\n";
}
$dmselect = generalMenu(array("Please select the DM to manage"), $dmrow);
$dmid = mysql_result($res, $dmselect-1, 0);
$dmname = mysql_result($res, $dmselect-1, 1);


/////////////////////////////////////
// print settings of dm and choose action to manage

printSettings();

$action = "";
while ($action != "q") {
	$action = generalMenu(array("What action would you like to perform?", "q=quit, e=edit, a=auth, u=unauth, p=print, r=reset"), array("q", "e", "a", "u", "p", "r"));
	// edit
	if ($action == "e") {
		editSettings();
	}
	// authorize
	if ($action == "a") {
		authorize();
	}
	// unauthorize
	if ($action == "u") {
		unauthorize();
	}
	// print
	if ($action == "p") {
		printSettings();
	}
	// reset
	if ($action == "r") {
		dmreset();
	}
}

echo "Good bye\n";
exit();


function readSettings() {
	global $dmid, $authdb;
	global $enabled, $telcotype, $testweightedresults, $callerid, $callsec, $rescount, $inbound, $hasdelay;

	// enabled
	$query = "select value from dmsetting where dmid=".$dmid." and name='dm_enabled'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$enabled = mysql_result($res, 0, 0);
	}

	// telcotype
	$query = "select value from dmsetting where dmid=".$dmid." and name='telco_type'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$telcotype = mysql_result($res, 0, 0);
	}

	// testweightedresults
	$query = "select value from dmsetting where dmid=".$dmid." and name='testweightedresults'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$testweightedresults = mysql_result($res, 0, 0);
	}

	// callerid
	$query = "select value from dmsetting where dmid=".$dmid." and name='telco_caller_id'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$callerid = mysql_result($res, 0, 0);
	}

	// calls_per_sec
	$query = "select value from dmsetting where dmid=".$dmid." and name='telco_calls_sec'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$callsec = mysql_result($res, 0, 0);
	}

	// resource count
	$query = "select value from dmsetting where dmid=".$dmid." and name='delmech_resource_count'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$rescount = mysql_result($res, 0, 0);
	}

	// inbound
	$query = "select value from dmsetting where dmid=".$dmid." and name='telco_inboundtoken'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$inbound = mysql_result($res, 0, 0);
	}

	// test hasdelay
	$query = "select value from dmsetting where dmid=".$dmid." and name='test_has_delays'";
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$hasdelay = mysql_result($res, 0, 0);
	}


}

function printSettings() {
	global $dmid, $authdb;
	global $enabled, $telcotype, $testweightedresults, $callerid, $callsec, $rescount, $inbound, $hasdelay;

	readSettings();

	////////////////////////
	// status
	$value = "";
	$query = "select authorizedip from dm where id=".$dmid;
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$value = mysql_result($res, 0, 0);
	}
	echo "\nAuthorized IP : ".$value;

	$value = "";
	$query = "select lastip from dm where id=".$dmid;
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$value = mysql_result($res, 0, 0);
	}
	echo "\nLast IP       : ".$value;

	$value = "";
	$query = "select lastseen from dm where id=".$dmid;
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$value = mysql_result($res, 0, 0);
	}
	echo "\nLast Seen     : ".date("M j, Y g:i a", $value/1000);

	$value = "";
	$query = "select enablestate from dm where id=".$dmid;
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	if (mysql_num_rows($res)==1) {
		$value = mysql_result($res, 0, 0);
	}
	echo "\nState         : ".$value;

	//////////////////////
	// settings
	echo "\nEnabled       : ".$enabled;
	echo "\nType          : ".$telcotype;
	echo "\nTest Weights  : ".$testweightedresults;
	echo "\nTest Has Delay: ".$hasdelay;
	echo "\nCaller ID     : ".$callerid;
	echo "\nCalls Per Sec : ".$callsec;
	echo "\nResource Count: ".$rescount;
	echo "\nInbound Count : ".$inbound;
	echo "\n";
}

function editSettings() {
	global $dmid, $authdb, $dmname;
	global $enabled, $telcotype, $testweightedresults, $callerid, $callsec, $rescount, $inbound, $hasdelay;

	$value = $enabled;
	if ($value == "") $value = "0";
	echo "\nDM Enabled (0|1): [".$value."] ";
	$enabled = trim(fread(STDIN, 1024)) +0;
	if ($enabled == "") $enabled = $value;

	$value = $telcotype;
	if ($value == "") $value = "Test";
	echo "\nTelco Type (Asterisk|Jtapi|Test): [".$value."] ";
	$telcotype = mysql_real_escape_string(trim(fread(STDIN, 1024)));
	if ($telcotype == "") $telcotype = $value;

	if ($telcotype == "Test") {
		$value = $testweightedresults;
		if ($value == "") $value = "A=1";
		echo "\nTest Weighted Results: [".$value."] ";
		$testweightedresults = mysql_real_escape_string(trim(fread(STDIN, 1024)));
		if ($testweightedresults == "") $testweightedresults = $value;

		$value = $hasdelay;
		if ($value == "") $value = "1";
		echo "\nTest Has Delay (0|1): [".$value."] ";
		$hasdelay = trim(fread(STDIN, 1024)) +0;
		if ($hasdelay == "") $hasdelay = $value;
	}

	$value = $callerid;
	echo "\nCaller ID: [".$value."] ";
	$callerid = preg_replace("/[^0-9]*/","",trim(fread(STDIN, 1024)));
	if ($callerid == "") $callerid = $value;

	$value = $callsec;
	if ($value == "") $value = "60";
	echo "\nCalls Per Second: [".$value."] ";
	$callsec = trim(fread(STDIN, 1024)) +0;
	if ($callsec == "") $callsec = $value;

	$value = $rescount;
	if ($value == "") $value = "23";
	echo "\nResource Count: [".$value."] ";
	$rescount = trim(fread(STDIN, 1024)) +0;
	if ($rescount == "") $rescount = $value;

	$value = $inbound;
	if ($value == "") $value = "1";
	echo "\nInbound Count: [".$value."] ";
	$inbound = trim(fread(STDIN, 1024)) +0;
	if ($inbound == "") $inbound = $value;


	/////////////////////////////////
	// confirmation
	echo "\n\n!!!! Please review your settings!!!\n";
	echo "DM Enabled       = ".$enabled."\n";
	echo "Telco Type       = ".$telcotype."\n";
	if ($telcotype == "Test") {
	echo "Test Results     = ".$testweightedresults."\n";
	echo "Test Has Delay   = ".$hasdelay."\n";
	}
	echo "Caller ID        = ".$callerid."\n";
	echo "Calls Per Second = ".$callsec."\n";
	echo "Resource Count   = ".$rescount."\n";
	echo "Inbound Count    = ".$inbound."\n";

	echo "Settings for DM : ".$dmname."\n";
	$confirm = generalMenu(array("Is this information correct?", "y or n"), array("y", "n"));
	if ($confirm == "y") {
		saveSettings();
	} else {
		echo "No changes have been made.\n";
	}
}

function saveSettings() {
	global $dmid, $authdb;
	global $enabled, $telcotype, $testweightedresults, $callerid, $callsec, $rescount, $inbound, $hasdelay;

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'dm_enabled', '".mysql_real_escape_string($enabled, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($enabled, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'delmech_resource_count', '".mysql_real_escape_string($rescount, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($rescount, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'testweightedresults', '".mysql_real_escape_string($testweightedresults, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($testweightedresults, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'test_has_delays', '".mysql_real_escape_string($hasdelay, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($hasdelay, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'telco_inboundtoken', '".mysql_real_escape_string($inbound, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($inbound, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'telco_calls_sec', '".mysql_real_escape_string($callsec, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($callsec, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'telco_caller_id', '".mysql_real_escape_string($callerid, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($callerid, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	// telco type
	$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'telco_type', '".mysql_real_escape_string($telcotype, $authdb)."') on duplicate key update `value` = '".mysql_real_escape_string($telcotype, $authdb)."'";
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "update custdm set `telco_type`='".mysql_real_escape_string($telcotype, $authdb)."' where dmid=".$dmid;
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));


	echo "The DM settings have been saved\n";
}

function authorize() {
	global $dmid, $authdb, $dmname;

	// if there are no settings, do not authorize just exit
	$query = "select count(*) from dmsetting where dmid=".$dmid;
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n".mysql_error($authdb));
	if (mysql_result($res,0,0) == 0) {
		echo "The DM has no settings.  You must first edit settings prior to authorizing/activation of this DM\n";
		return;
	}

	// if this is the first time authorized, create custdm record and a few settings once
	$query = "select count(*) from custdm where dmid=".$dmid;
	$res = mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	$custdmcount = mysql_result($res, 0, 0);
	if ($custdmcount == 0) {
		// find the telco_type
		$query = "select value from dmsetting where name='telco_type'";
		$res = mysql_query($query,$authdb)
			or die ("Failed to execute statement\n$query\n\n".mysql_error($authdb));
		$telco_type = mysql_result($res, 0, 0);

		// custdm record
		$query = "insert into custdm (`dmid`, `name`, `enablestate`, `telco_type`) values ('".$dmid."','".$dmname."','active','".$telco_type."')";
		mysql_query($query,$authdb)
			or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

		// customerid=1
		$query = "update dm set customerid=1 where id=".$dmid;
		mysql_query($query,$authdb)
			or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
		// telco_dial_timeout=45000
		$query = "INSERT INTO `dmsetting` ( `dmid` , `name` , `value` ) VALUES ('".$dmid."', 'telco_dial_timeout', '45000') on duplicate key update `value` = '45000'";
		mysql_query($query,$authdb)
			or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));
	}

	// update authorized state on dm and custdm
	$query = "update dm set authorizedip = lastip, enablestate = 'active' where id = ".$dmid;
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "update custdm set `enablestate`='active' where dmid=".$dmid;
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	echo "The DM has been authorized\n";
}

function unauthorize() {
	global $dmid, $authdb;

	$query = "update dm set enablestate = 'disabled' where id = ".$dmid;
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	$query = "update custdm set `enablestate`='disabled' where dmid=".$dmid;
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	echo "The DM has been unauthorized\n";
}

function dmreset() {
	global $dmid, $authdb;

	$query = "update dm set command='reset' where id=".$dmid;
	mysql_query($query,$authdb)
		or die ("Failed to execute statement \n$query\n\n" . mysql_error($authdb));

	echo "The DM has been reset\n";
}


?>
