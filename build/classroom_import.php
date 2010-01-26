<?

$usage = "
Description:
This script will import classroom messages data

Usage:
-l languagecode ie 'en', 'es' etc.
-i input csv file
-o output root path

classroom_import.php will create a subfoalder named as the languagecode if it does not already exist.
In the language folder the import will create data.php

php classroom_import.php -l en -i messages.csv  -o /usr/commsuite/www/messagedata
";

$flag = false;
$values = array();
array_shift($argv); //ignore this script
foreach ($argv as $arg) {
	if ($arg[0] == "-") {
		for ($x = 1; $x < strlen($arg); $x++) {
			switch ($arg[$x]) {
				case "l":
					$flag = "l";
					break;
				case "i":
					$flag = "i";
					break;
				case "o":
					$flag = "o";
					break;
				default:
					echo "Unknown option " . $arg[$x] . "\n";
					exit($usage);
			}
		}
	} else {
		if($flag) {
			$values[$flag] = $arg;
			$flag = false;
		} else {
			echo "No flag for value " . $arg . "\n";
			exit($usage);
		}
	}
}

if (!isset($values["l"]))
	exit("No language specified\n$usage");
if (!isset($values["i"]))
	exit("No input file specified\n$usage");
if (!isset($values["o"]))
	exit("No output file specified\n$usage");

$input = fopen($values["i"], 'r') or die("Can't open input file \"" . $values["i"] . "\"\n");

if(!file_exists($values["o"])) {
	exit("Output path does not exist\n$usage");
}
$path = $values["o"] . "/" . $values["l"];
if(!file_exists($path)) {
	mkdir($path, 0755);
}
$output = fopen($path . "/data.php", 'w') or die("Can't open output file \"" . $values["o"] . "\"\n");

echo "Processing";
$count = 0;

fwrite($output, "<? \n\$messagedatacache[\"" . $values["l"] . "\"] = array(\n");

while($line = fgets($input)) {
	$linevalues = explode(",",$line);
	if(!$linevalues)
		exit("Unable to read line: \n$line\n");
	if(!isset($linevalues[0]) || !isset($linevalues[1]))
		exit("Unable to read the first two values in line: \n$line\n");
	echo ".";
	$count++;
	fwrite($output, '"' . (addslashes(trim(trim($linevalues[0]),'"')) . '"=>"' . addslashes(trim(trim($linevalues[1]),'"')) . "\",\n"));
}
fwrite($output,");\n ?>");

echo "\nImported $count records\n";
fclose($input);
fclose($output);

?>
