<?
// REQUIRED for 6.2 to 7.0 upgrade

// Creates new limited database user/pass for every customer in the system

////////////////////////////////
// authserver variables must be set!!!
$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";
////////////////////////////////

require_once("../inc/db.inc.php");
require_once("../manager/managerutils.inc.php");

// connect to authserver db
$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

// find all customers
$data = array();
$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)  order by s.id, c.id";
$res = mysql_query($query, $auth);
while($row = mysql_fetch_row($res)){
	$data[] = $row;
}

$shardhost = 0; // unknown shard
// for each customer, create subscriber db user/pass
foreach ($data as $customer) {
	$cid = $customer[0];
	$custdbname = 'c_'.$cid;
	$sharddb;
	if ($shardhost !== $customer[1]) {
		$sharddb = DBConnect($customer[1], $customer[2], $customer[3], "aspshard")
			or die("Could not connect to shard ".$customer[1]);
			
		$shardhost = $customer[1];
		echo "--- SHARD ".$shardhost." ---\n";
	}
	echo "Customer ".$cid."\n";

	$limitedusername = "c_".$cid."_limited";
	$limitedpassword = genpassword();

	$query = "update customer set limitedusername='".$limitedusername."', limitedpassword='".$limitedpassword."' where id=".$cid;
	mysql_query($query, $auth)
		or die("FAILURE on customer ".$cid." update : ". mysql_error($auth));
	
	createLimitedUser($limitedusername, $limitedpassword, $custdbname, $sharddb);
}

mysql_close($auth);
?>
