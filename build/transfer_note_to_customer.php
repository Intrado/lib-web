<?

$authhost="localhost";
$authuser="root";
$authpass="";
$authdb="authserver";

$auth = mysql_connect($authhost, $authuser, $authpass, true)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where nsid = '' order by c.id";
$res = mysql_query($query, $auth);
$data = array();
while($row = mysql_fetch_row($res)){
	$data[] = $row;
}

$notesfile = "notesfile.txt";
if(!$fp = fopen($notesfile, "a")){
	echo "Failed to open notes file\n";
	exit();
}

foreach($data as $customer){
	echo "Working on customer " . $customer[0] . "\n";
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3], true)
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	$result = mysql_query("select value from setting where name = '_managernote'", $custdb) or die("Failed to fetch setting " . mysql_error($custdb));
	$row = mysql_fetch_row($result);
	$managernote = "";
	if($row != false){
		$managernote = $row[0];
		fwrite($fp, "Customer: " . $customer[0] . " Notes: " . $managernote . "\n");
		mysql_query("update customer set nsid = '" . mysql_escape_string($managernote) . "' where id = " . $customer[0], $auth) or die("Failed to update customer: " . mysql_error($auth));
		mysql_query("delete from setting where name = '_managernote'", $custdb) or die("Failed to clear out manager setting on customer " . $customer[0] . ": " . mysql_error($custdb));
	}
}

fclose($fp);
?>