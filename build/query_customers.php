<?

$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

$usage = "
Description:
This script will query all customers and output to screen or file. SQL queries come from a file or stdin
Usage:
php query_customers.php  [-o outputfile] [-c csvoutputfile] [-q queryfile] [\"sql\" ...]
-o output to file instead of stdout
-c output to a CSV file (prepends customerid to output columns)
-q read queries from queryfile, each command should be separated by $$$
";

$opts = array("outputfile" => false, "outputcsv" => false, "queryfile" => false);
$sqlqueries = array();
array_shift($argv); //ignore this script
for ($i = 0; $i < $argc-1; $i++) {
	$arg = $argv[$i];

	if ($arg[0] == "-") {
		for ($x = 1; $x < strlen($arg); $x++) {
			switch ($arg[$x]) {
				case "o":
					$opts['outputfile'] = $argv[++$i];
					break;
				case "c":
					$opts['outputcsv'] = $argv[++$i];
					break;
				case "q":
					$opts['queryfile'] = $argv[++$i];
					break;
				default:
					echo "Unknown option " . $arg[$x] . "\n";
					exit($usage);
			}
		}
	} else {
		$sqlqueries[] = $arg;
	}
}

if (count($sqlqueries) == 0 && !$opts['queryfile']) {
	echo "No query specified\n";
	exit($usage);
}


if ($opts['queryfile']) {
	$sqlqueries = array_merge($sqlqueries, explode("$$$",file_get_contents($opts['queryfile'])));
}


if ($opts['outputfile']) {
	$fpout = fopen($opts['outputfile'],"w") or die ("Can't open output file for writing");
} else if ($opts['outputcsv']) {
	$fpout = fopen($opts['outputcsv'],"w") or die ("Can't open output file for writing");
} else {
	$fpout = STDOUT;
}


$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) order by c.id";
$res = mysql_query($query, $auth);
$data = array();
while($row = mysql_fetch_row($res)){
	$data[] = $row;
}

$wroteheaders = false;
foreach($data as $customer){
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
	mysql_query($setcharset, $custdb);				
	

	if (!$opts['outputcsv'])
		fprintf($fpout,"==================== Customer % 5d ====================\n",$customer[0]);


	$querynum = 1;
	foreach ($sqlqueries as $sqlquery) {



		if (trim($sqlquery)){
			if ($wroteheaders && $querynum > 1)
				$wroteheaders = false;

			if (count($sqlqueries) > 1)
				fprintf($fpout,"---------- Query % 5d ----------\n",$querynum++);


			$sqlquery = str_replace('_$CUSTOMERID_', $customer[0], $sqlquery);
			$res = mysql_query($sqlquery,$custdb)
				or die ("Failed to execute sql: " . mysql_error($custdb));

			if ($opts['outputcsv']) {

				//write field header

				if (!$wroteheaders) {
					$wroteheaders = true;
				fwrite($fpout,'"customerid"');
					for ($i = 0; $i < mysql_num_fields($res); $i++) {
						$field = mysql_fetch_field($res, $i);
						fwrite($fpout,',"' . $field->name . '"');
					}
					fwrite($fpout,"\n");
				}

				while ($row = mysql_fetch_row($res)) {
					fwrite($fpout,'"' . $customer[0] . '","' . implode('","',$row) . '"' . "\n");
				}
			} else {


				$fields = array();
				for ($i = 0; $i < mysql_num_fields($res); $i++) {
					$fields[] = mysql_fetch_field($res, $i);
				}


				$sizes = array();
				$data = array();
				while ($row = mysql_fetch_row($res)) {
					foreach ($row as $index => $col)
						$sizes[$index] = @max($sizes[$index],strlen($col),strlen($fields[$index]->name));
					$data[] = $row;
				}

				$line = "|";
				foreach ($fields as $index => $field)
						$line .= sprintf("%" . $sizes[$index] . "." . $sizes[$index] . "s|",$field->name);
				fwrite($fpout,$line . "\n");
				fwrite($fpout,str_repeat("-",strlen($line)-1) . "\n");


				foreach ($data as $row) {
					$line = "|";
					for ($i = 0; $i < count($row); $i++) {
						$line .= sprintf("%" . $sizes[$i] . "s|",$row[$i]);
					}

					fwrite($fpout,$line . "\n");
				}
			}
		}
	}
}

?>
