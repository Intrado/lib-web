<?
$inputfilename = "query.csv";
$outputfilename = "Duplicate_students.csv";
$logfilename = "duplicate_id_log.log";
$idposition = 0;

include("duplicate_id_finder.inc.php");

if(!$inputfp = fopen($inputfilename, "r"))
	wlog_die("Could not open input file: ".$inputfilename);

if(!$outputfp = fopen($outputfilename, "w"))
	wlog_die("Could not open output file: ".$outputfilename);

$students = array();
$count=0;
while($row = fgetcsv($inputfp)){
	$students[$row[$idposition]] = $students[$row[$idposition]]+1;
	$count++;
}

while($key = key($students)){
	if($students[$key] > 1){
		$line = array($key, $students[$key]);
		writeline($outputfp, $line);
	}
	next($students);
}

wlog("Read ".$count." students.");
fclose($outputfp);
fclose($inputfp);
fclose($logfp);

?>