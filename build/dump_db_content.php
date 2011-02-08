<?

$authhost="localhost:3306";
$authuser="root";
$authpass="asp123";
$authdb="authserver";

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

foreach($data as $customer){
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	$res = mysql_query("select id, contenttype from content", $custdb);
	$contentrecords = array();
	while ($row = mysql_fetch_row($res)) {
		$contentrecords[] = $row;
	}
	
	foreach ($contentrecords as $content) {
		
		$folder = $destfolder . str_replace("/", "_", $content[1]) . "/" . $customer[0] . "/";
		$file = $content[0];
//		switch ($content[1]) {
//			case "image/gif":
//				$file .= ".gif";
//				break;
//			case "audio/wav":
//				$file .= ".wav";
//				break;
//			case "text/plain":
//				$file .= ".txt";
//				break;
//			case "application/zip":
//				$file .= ".zip";
//				break;
//			default:
//				$type = explode("/", $content[1]);
//				$file .= "." . $type[1];
//		}
		if (!file_exists($folder))
			mkdir($folder, 0755, true);
		
		$f = fopen($folder . $file, "w");
		
		$res = mysql_query("select data from content where id = " . $content[0], $custdb);
		$b64data = mysql_fetch_row($res);
		$data = base64_decode($b64data[0]);
		$b64data = "";
		
		fwrite($f, $data);
		$data = "";
		
		fclose($f);
	}
}

?>
