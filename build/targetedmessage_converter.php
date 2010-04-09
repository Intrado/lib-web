<?

$usage = "
Description:
This script will create PHP include files or properties files
used for targeded message translations. The PHP files contains
an array with messagekeys to translationstring and the propsfiles
contain the same information but in a format readable by java.
For php the targeted message include files are storde in kona/messagedata
and the propsfiles are stored in redialer/messagedata.

The input csv file should have the following format
\"Messagekey\",\"Comment\",\"Comment\"
\"late-today\",\"Arrived late today\",\"Այսօր ուշ ժամանեց\"

the script will create a subfoalder named as the languagecode
if it does not already exist and place the result file in the folder.
The result file will overwrite existing files and all existing data for
that language is lost if it is not included in the input csv

The translations will be used for all customers

The script can also take an optional s flag to echo out sql that can be
used to insert messagekeys into the DB.

Usage:
-t type must be php or prop
-l languagecode ie 'en', 'es' etc.
-i input csv file
-o output root path
-s optional echo out sql to insert message keys into db

example:
php classroom_import.php -t php -l en -i messages.csv  -o /usr/commsuite/www/messagedata
";

$flag = false;
$values = array();
array_shift($argv); //ignore this script
foreach ($argv as $arg) {
	if ($arg[0] == "-") {
		for ($x = 1; $x < strlen($arg); $x++) {
			switch ($arg[$x]) {
				case "t":
					$flag = "t";
					break;
				case "l":
					$flag = "l";
					break;
				case "i":
					$flag = "i";
					break;
				case "o":
					$flag = "o";
					break;
				case "s":
					$values["s"] = true;
					$flag = false;
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
if (!isset($values["t"]) || !($values["t"] == "php" || $values["t"] == "prop"))
	exit("No type specified\n$usage");
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
if($values["t"] == "php") {
	$output = fopen($path . "/targetedmessage.php", 'w') or die("Can't open output file \"" . $values["o"] . "\"\n");
	fwrite($output, "<? \n\$messagedatacache[\"" . $values["l"] . "\"] = array(\n");
	// no need to specify utf-8
} else {
	$output = fopen($path . "/targetedmessage.properties", 'w') or die("Can't open output file \"" . $values["o"] . "\"\n");
	fwrite($output,"\xEF\xBB\xBF");//bit order mark for utf-8, this Is the only whay that works
}
echo "Processing";
$count = 0;

$checkarray = array();

//setlocale(LC_ALL, 'en_US.UTF8');

fgetcsv($input); // Skip the header line
while (($data = fgetcsv($input)) !== FALSE) {
	if(!isset($data[0]))
		exit("Unable to read the first column on line " . ($count + 1) ."\n");
	if(!isset($data[1]))
		exit("Unable to read the second column on line " . ($count + 1) ."\n");
	if(!isset($data[2]))
		exit("Unable to read the third column on line " . ($count + 1) ."\n");

	echo ".";

	// First value => message key
	// Second value => english
	// third value => translation

	$key = addslashes(strtolower(trim($data[0])));
	$value = addcslashes($data[2],"'");
	if(!isset($checkarray[$key])) {
		if($values["t"] == "php")
			fwrite($output, "'$key'=>'$value',\n");
		else
			fwrite($output,"$key=$value\n");
		$checkarray[$key] = $value;
		$count++;
	} else {
		echo "\nWarning " . ($checkarray[$key]==$value?"Two identical key/value pairs ($key => $value)":"Two messages with the same key ($key)") . " - skipping\n";
	}
}
if($values["t"] == "php") {
	fwrite($output,");\n ?>");
}
echo "\nImported $count records\n";

if(isset($values["s"])) {
	$values = array();
	foreach($checkarray as $key => $value) {
		$values[] = "('$key',1)";
	}
	if (!empty($values)) {
		echo "INSERT INTO targetedmessage (messagekey,targetedmessagecategoryid) VALUES \n		";
		echo implode(",\n		",$values);
		echo "\n";
	}
}
fclose($input);
fclose($output);
?>
