<?
/*CSDELETEMARKER_START*/

header("Content-type: text/plain");

// TODO verify valid session, and logged in as schoolmessenger user

$data = file_get_contents("txtreply.log");
$data = str_replace("\r","",$data);
$data = ereg_replace("\n+&","&",$data);
$data = ereg_replace("\n+[^2]","|",$data);
$data = explode("\n",$data);

foreach ($data as $line) {
	if(!ereg("200.-..-.. ..:..:..,.*",$line))
		continue;
	$pos = strpos($line,",");
	$date = substr($line,0,$pos);
	$line = substr($line,$pos);
	parse_str($line,$bits);
	echo $date . " - " . $bits['shortcode'] . " - " . $bits['smsnumber'] . " - \"" . $bits['message'] . "\"" . " - " . $bits['smaction'] . "\n";
}

/*CSDELETEMARKER_END*/
?>
