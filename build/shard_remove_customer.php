<?
//////////////////////////////////////
// STEP 2 in moving customer from one shard to another

// cleanup all references to a customer from this shard
// drop the c_x database, remove c_x dbuser, and set authserver customer record to blank password
//
// EDIT VARIABLES AT TOP OF SCRIPT, avoid entering on command line or prompts
//////////////////////////////////////


$customerid = "";
$authhost = "";
$authuser = "";
$authpass = "";

/////////////////////////////////////
// Functions

function echoarray($somearray){
	foreach($somearray as $line){
		echo $line . "\n";
	}
}

function generalmenu($questions = array(), $validresponses = array()){
	echoarray($questions);
	$response = fread(STDIN, 1024);
	$response = trim($response);
	while(!in_array($response, $validresponses)){
		echo "\nThat was not an option\n";
		$response = fread(STDIN, 1024);
		$response = trim($response);
	}
	return $response;
}

///////////////////////////////////
// Main Program

// connect to authdb
echo "Connecting to authdb...\n";
$authdb = mysql_connect($authhost, $authuser, $authpass)
	or die("Failed to connect to auth database");
echo "auth connection ok\n";
mysql_select_db("authserver", $authdb);

// find the customer shard
$query = "select s.dbhost, s.dbusername, s.dbpassword from shard s " .
		"left join customer c on (c.shardid = s.id) " .
		"where c.id=".mysql_real_escape_string($customerid, $authdb);
$res = mysql_query($query, $authdb)
	or die("Failed to execute statement \n$query\n\n : " . mysql_error($authdb));
$row = mysql_fetch_row($res);
$shardhost = $row[0];
$sharduser = $row[1];
$shardpass = $row[2];

// connect shard
echo "Connecting to sharddb...\n";
$sharddb = mysql_connect($shardhost, $sharduser, $shardpass)
	or die("Failed to connect to shard database");
echo "shard connection ok\n";
mysql_select_db("aspshard", $sharddb);

// confirm to continue
$confirm = "n";
while($confirm != "y"){
	$confirm = generalMenu(array("Are you sure you want to remove customer ".$customerid." from this shard database?", "y or n"), array("y", "n"));
	if ($confirm == "n") exit();
}

// remove records from aspshard tables
echo("removing customer... id=".$customerid."\n");

$tablearray = array("importqueue", "jobstatdata", "qjobperson", "qjobtask", "specialtaskqueue", "qreportsubscription", "qjobsetting", "qschedule", "qjob");
foreach ($tablearray as $t) {
	echo ($t."\n");
	$query = "delete from ".$t." where customerid=".mysql_real_escape_string($customerid, $sharddb);
	if (!mysql_query($query, $sharddb)) {
		echo("Failed to execute statement \n$query\n\n : " . mysql_error($sharddb));
	}
}

// get customer dbuser and delete
echo "get customer database username from authserver\n";
$query = "select dbusername from authserver.customer where id=".mysql_real_escape_string($customerid, $authdb);
$res = mysql_query($query, $authdb)
	or die("Failed to execute statement \n$query\n\n : " . mysql_error($authdb));
$row = mysql_fetch_row($res);
$cdbusername = $row[0];
echo "delete dbuser ".$cdbusername."\n";
$query = "drop user ".mysql_real_escape_string($cdbusername, $sharddb);
mysql_query($query, $sharddb)
	or die("Failed to execute statement \n$query\n\n : " . mysql_error($sharddb));

// clear username/password and disable customer in authserver
echo "blank username and password for customer record in authserver\n";
$query = "update authserver.customer set dbusername='' and dbpassword='' and enabled=0 where id=".mysql_real_escape_string($customerid, $authdb);
mysql_query($query, $authdb)
	or die("Failed to execute statement \n$query\n\n : " . mysql_error($authdb));

// drop c_X database
echo "drop customer database\n";
$query = "drop database c_".$customerid;
mysql_query($query, $sharddb)
	or die("Failed to execute statement \n$query\n\n : " . mysql_error($sharddb));


echo "!!!DONE!!!\n";
?>

