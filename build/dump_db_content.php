<?
/* Dump customer content from CommSuite database into the local filesystem
 * The idea is, these content files can be used for loading into an alternate 
 * storage system such as Cassandra via another tool.
 * 
 * Content will be extrated into two files for each content record. A .dat
 * file will contain the data and a .metadata file will contain the file 
 * metadata, such as type, with the following destination format
 * $destfolder/<customerid>/<contentid>.dat
 * $destfolder/<customerid>/<contentid>.metadata
 * 
 * - Nickolas
 */

// authserver connection information (only read access required)
$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

// destination for extracted content files
$destfolder = "/tmp/content/";

$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

// get customer db connection info
$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) order by c.id";
$res = mysql_query($query, $auth);
$data = array();
while($row = mysql_fetch_row($res)){
	$data[] = $row;
}

// iterate all customers
foreach($data as $customer){
	$customerid = $customer[0];
	
	// connect to the customer db
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	// select the contentid and type
	$res = mysql_query("select id, contenttype from content", $custdb);
	$contentrecords = array();
	while ($row = mysql_fetch_row($res)) {
		$contentrecords[] = $row;
	}
	
	// for each contentid
	foreach ($contentrecords as $content) {
		$contentid = $content[0];
		$contenttype = $content[1];
		
		// construct the file paths for the dat and metadata files
		$folder = $destfolder . $customerid . "/";
		$datfile = $contentid . ".dat";
		$metadatafile = $contentid . ".metadata";
		
		if (!file_exists($folder))
			mkdir($folder, 0755, true);
		
		$f = fopen($folder . $datfile, "w");
		
		// un-base64 the data and write it to the dat file
		$res = mysql_query("select data from content where id = " . $contentid, $custdb);
		$b64data = mysql_fetch_row($res);
		$data = base64_decode($b64data[0]);
		$b64data = "";
		
		fwrite($f, $data);
		$data = "";
		
		fclose($f);
		
		// create a metadata file with the contenttype and owner=system
		$f = fopen($folder . $metadatafile, "w");
		fwrite($f, "type=" . $contenttype . "\n" . "owner=system" . "\n");
		
		fclose($f);
	} // end for each contentid
} // end for each customer

?>
