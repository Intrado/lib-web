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
	if ($shardhost !== $customer[1]) {
		if (isset($shard))
			mysql_close($shard);
		$shardhost = $customer[1];
		$shard = mysql_connect($customer[1], $customer[2], $customer[3])
			or die("Could not connect to shard: " . mysql_error($shard));
		echo "--- SHARD ".$shardhost." ---\n";
	}
	echo "Customer ".$cid."\n";

	$limitedusername = "c_".$cid."_limited";
	$limitedpassword = genpassword();

	$query = "update customer set limitedusername='".$limitedusername."', limitedpassword='".$limitedpassword."' where id=".$cid;
	mysql_query($query, $auth)
		or die("FAILURE on customer ".$cid." update : ". mysql_error($auth));
	
	$query = "drop user '$limitedusername'";
	mysql_query($query, $shard);

	$query = "drop user '$limitedusername'@'localhost'";
	mysql_query($query, $shard);
	
	$query = "create user '$limitedusername' identified by '$limitedpassword'";
	mysql_query($query, $shard)
		or die("FAILURE on customer ".$cid." create : ". mysql_error($shard));

	$query = "create user '$limitedusername'@'localhost' identified by '$limitedpassword'";
	mysql_query($query, $shard)
		or die("FAILURE on customer ".$cid." create local : ". mysql_error($shard));

	$query = "grant select, insert, update, delete, create temporary tables, execute on $custdbname . * to '$limitedusername'";
	mysql_query($query, $shard)
		or die("FAILURE on customer ".$cid." grant : ". mysql_error($shard));

	$query = "grant select, insert, update, delete, create temporary tables, execute on $custdbname . * to '$limitedusername'@'localhost'";
	mysql_query($query, $shard)
		or die("FAILURE on customer ".$cid." grant local : ". mysql_error($shard));
	
}

mysql_close($shard);
mysql_close($auth);
?>
