<?
// USAGE: php apply_customer_sql.php customerchanges.sql <all> | <cid1> ... <cidN>

////////////////////////////////
// authserver variables must be set!!!
$authhost="localhost:3306";
$authuser="root";
$authpass="reliance202";
$authdb="authserver";
////////////////////////////////

// connect to authserver db
$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

// customer db connection data
$data = array();

$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword, c.limitedusername, c.limitedpassword from shard s inner join customer c on (c.shardid = s.id)  order by c.id";
$res = mysql_query($query, $auth);
while($row = mysql_fetch_row($res)){
	$data[] = $row;
}


// now apply sql to each customer
foreach($data as $customer){
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	printf("Doing % 5d: ",$customer[0]);
	
	createLimitedUser($customer[4],$customer[5],"c_$customer[0]", $custdb);
	
	echo "\n";
}

/* taken from managerutils.inc.php version 7.1.5 */
// create the subscriber application database user
function createLimitedUser($limitedusername, $limitedpassword, $custdbname, $sharddb) {
	mysql_query("drop user '$limitedusername'", $sharddb);
	if (mysql_query("create user '$limitedusername' identified by '$limitedpassword'", $sharddb) === false)
		die("Failed to create user $limitedusername on ".$custdbname." error:".mysql_error($sharddb));

	$tables = array();
	$tables['audiofile'] 	= "select";
	$tables['content'] 		= "select";
	$tables['contactpref'] 	= "select, insert, update, delete";
	$tables['email'] 		= "select, update";
	$tables['fieldmap'] 	= "select";
	$tables['groupdata'] 	= "select, insert, update, delete";
	$tables['job'] 			= "select";
	$tables['jobsetting'] 	= "select";
	$tables['jobtype'] 		= "select";
	$tables['message'] 		= "select";
	$tables['messageattachment'] = "select";
	$tables['messagepart'] 	= "select";
	$tables['persondatavalues'] = "select";
	$tables['person'] 		= "select, update";
	$tables['phone'] 		= "select, update";
	$tables['reportperson'] = "select";
	$tables['setting'] 		= "select";
	$tables['sms'] 			= "select, update";
	$tables['subscriber'] 	= "select, update";
	$tables['subscriberpending'] = "select, delete";
	$tables['ttsvoice'] 	= "select";
	$tables['user'] 		= "select";
			
	foreach ($tables as $tablename => $privs) {
		if (mysql_query("grant ".$privs." on $custdbname . ".$tablename." to '$limitedusername'", $sharddb) === false)
			die("Failed to grant ".$tablename." on ".$custdbname." error:".mysql_error($sharddb));
		
	}
}

?>
