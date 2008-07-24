<?

$authhost="localhost";
$authuser="root";
$authpass="";
$authdb="authserver";

$auth = mysql_connect($authhost, $authuser, $authpass, true)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);


$shardquery = "select id, dbhost, dbusername, dbpassword from shard";
$shardresult = mysql_query($shardquery, $auth);
$shardinfo = array();
while($row = mysql_fetch_row($shardresult)){
	$shardinfo[$row[0]] = $row;
}

$customerquery = "select id, shardid from customer order by id";
$customerresult = mysql_query($customerquery, $auth);
$customerinfo = array();
while($row = mysql_fetch_row($customerresult)){
	$customerinfo[$row[0]] = $row;
}

$emailfile = "email_backups.txt";
if(!$fp = fopen($emailfile, "a")){
	echo "Failed to open notes file\n";
	exit();
}


$curr="";
foreach($customerinfo as $customer){
	echo "Working on customer " . $customer[0] . "\n";

	if($curr != $customer[1]){
		$custdb = mysql_connect($shardinfo[$customer[1]][1], $shardinfo[$customer[1]][2], $shardinfo[$customer[1]][3], true)
					or die("Could not connect to customer: " . mysql_error($custdb));
		$curr = $customer[1];
	}
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	//Fetch all users that have emails
	$emailresult = mysql_query("select id, email from user where email != ''", $custdb);
	$emails = array();

	//split email on ";" and array_shift first email off
	//update email with shifted email
	//update aremail with imploded remaining array
	while($row = mysql_fetch_row($emailresult)){
		$primaryemail = "";
		$aremail = "";
		fwrite($fp, "Customer: " . $customer[0] . " User: " . $row[0] . " has emails: " . $row[1] . "\n");
		$emaillist = explode(";", $row[1]);
		$primaryemail = array_shift($emaillist);
		$aremail = implode(";", $emaillist);
		mysql_query("update user set email = '" . $primaryemail . "', aremail = '" . $aremail . "' where id = " . $row[0], $custdb)
				or die("Failed to update user " . $row[0] . ": " . mysql_error($custdb));
	}
	//profit
}

fclose($fp);
?>