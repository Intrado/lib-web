<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$settingsiniphp = "../inc/settings.ini.php";
$oldsettings = "../inc/settings.ini.php.backup";
$outfile = "test.ini.php";
if(rename($settingsiniphp, $oldsettings)){
	$outfile = $settingsiniphp;
	$settingsiniphp = $oldsettings;
}

$outfilefp = fopen($outfile, "w");

$settingsfp = fopen($settingsiniphp, "r");
$fileLines = array();
while($line = fgets($settingsfp)){
	$fileLines[] = trim($line);
}

$linesToAdd = array();
$linesToAdd[] = "[authserver]";
$linesToAdd[] = "host=\"localhost:8088\"";
$linesToAdd[] = "path=\"/xmlrpc\"";

$dmapiLinesToAdd = array();
$dmapiLinesToAdd[] = "[dmapidb]";
$dmapiLinesToAdd[] = "persistent=true";
$dmapiLinesToAdd[] = "host=\"localhost\"";
$dmapiLinesToAdd[] = "user=\"" . $SETTINGS['db']['user'] . "\"";
$dmapiLinesToAdd[] = "pass=\"" . $SETTINGS['db']['pass'] . "\"";
$dmapiLinesToAdd[] = "db=\"commsuite\"";

$fileLines = replaceSection($fileLines, "db", $linesToAdd);
$fileLines = appendToSection($fileLines, "authserver", $dmapiLinesToAdd);
$ldapline = findSection($fileLines, "feature");
$fileLines = deleteSection($fileLines, "ldap");
$fileLines = appendToSection($fileLines, "feature", array("", "is_ldap=false"));
$fileLines = deleteSection("fileLines, "import");


writearray($fileLines, $outfilefp);
fclose($outfilefp);
fclose($settingsfp);


//function to delete section
function deleteSection($fileLines, $sectionName){
	echo "Deleting this section: " . $sectionName . "\n\n";
	$commentmarker = array();
	$start = null;
	$end = null;
	foreach($fileLines as $index => $line){
		$line = trim($line);
		if(ereg("^\[$sectionName\]", $line)){
			//find the section we want and mark it
			$start = $index;
		}else if(!ereg("^\[?[A-Za-z]+", $line)){
			//keep track of any comments so we dont delete them
			$commentmarker[] = $index;
		}else if($start !== null && ereg("\[.*\]", $line)){
			// find the next section and set limit one line before it
			$end = $index-1;
			break;
		}
		if($index == (count($fileLines)-1) && $end == null && $start != null){
			$end = $index;
		}
	}

	//go through the file and unset the lines we want to delete
	foreach($fileLines as $index => $line){
		if($index >= $start && $index <= $end && !in_array($index, $commentmarker)){
			unset($fileLines[$index]);
		}
	}
	return $fileLines;
}

//function to add section beginning at a line
function addLines($fileLines, $linesToAdd, $start){
	echo "Adding lines\n\n";
	$newfile = array();
	$count = 0;
	foreach($fileLines as $line){
		if($count == $start){
			$newfile = array_merge($newfile, $linesToAdd);
			//add extra blank line to space
			$newfile[] = "";
			$newfile[] = $line;
		} else {
			$newfile[] = $line;
		}
		$count++;
	}
	return $newfile;
}

//return line number of section start
function findSection($fileLines, $sectionName){
	echo "Finding Section: " . $sectionName . "\n\n";
	$linenumber = null;
	foreach($fileLines as $index => $line){
		if(ereg("^\[$sectionName\]", $line)){
			$linenumber = $index;
		}
	}
	if($linenumber != null)
		echo "success\n";
	return $linenumber;
}

//function to replace section using delete and add
function replaceSection($fileLines, $sectionName, $linesToAdd){
	echo "Replacing Section: " . $sectionName . " with these lines:\n";
	echoarray($linesToAdd);
	echo "\n";
	
	$lineToReplace = findSection($fileLines, $sectionName);
	if($lineToReplace == null)
		return $fileLines;
	$fileLines = deleteSection($fileLines, $sectionName);
	$fileLines = addLines($fileLines, $linesToAdd, $lineToReplace);
	return $fileLines;
}

function echoarray($someArray){
	foreach($someArray as $line){
		echo $line . "\n";
	}
}

//takes an array of strings and a file pointer and 
//writes each string on a new line
function writearray($someArray, $fp){
	foreach($someArray as $line){
		fwrite($fp, $line . "\n");
	}
}


//add lines to the end of a section
function appendToSection($fileLines, $sectionName, $linesToAdd){
	echo "Appending to this section: " . $sectionName . "\n\n";
	$start = null;
	$end = 0;
	foreach($fileLines as $index => $line){
		$line = trim($line);
		if(ereg("^\[$sectionName\]", $line)){
			//find the section we want and mark it
			$start = $index;
		}else if(!ereg("^\[?[A-Za-z]+", $line)){
			//keep track of any comments so we dont delete them
			$commentmarker[] = $index;
		}else if($start !== null && ereg("\[.*\]", $line)){
			// find the next section and set limit one line before it
			$end = $index-1;
			break;
		}
	}
	return addLines($fileLines, $linesToAdd, $end);
}

?>