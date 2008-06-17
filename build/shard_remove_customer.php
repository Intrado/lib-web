<?
//////////////////////////////////////
// cleanup all references to a customer from this shard
// Usage: php shard_remove_customer.php <customer ID> <shard user> <shard pass>
//////////////////////////////////////

include_once("relianceutils.php");

$customerid = "";

if (isset($argv[1])) {
	$customerid = $argv[1];
} else {
	echo "please provide the customer ID";
	exit();
}

if(isset($argv[2])){
	$dbuser = $argv[2];
	$dbpass = "";
	if (isset($argv[3])) $dbpass = $argv[3];
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
$sharddb = mysql_connect("127.0.0.1", $dbuser, $dbpass)
	or die("Failed to connect to database");
echo "connection ok\n";
mysql_select_db("aspshard");


$confirm = "n";
while($confirm != "y"){
	$confirm = generalMenu(array("Are you sure you want to remove customer ".$customerid." from this shard database?", "y or n"), array("y", "n"));
	if ($confirm == "n") exit();
}

// remove records
echo("removing customer... id=".$customerid."\n");

$tablearray = array("importqueue", "jobstatdata", "qjobperson", "qjobtask", "specialtaskqueue", "qreportsubscription", "qjobsetting", "qschedule", "qjob");
foreach ($tablearray as $t) {
	echo ($t."\n");
	$query = "delete from ".$t." where customerid=".mysql_real_escape_string($customerid, $sharddb);
	if (!mysql_query($query,$sharddb)) {
		echo("Failed to execute statement \n$query\n\n : " . mysql_error($sharddb));
	}
}

echo "!!!DONE!!!\n";
?>

