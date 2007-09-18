<?
$settingsiniphp = "../inc/settings.ini.php";
$outfile = "test.ini.php";

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

$fileLines = replaceSection($fileLines, "db", $linesToAdd);
$ldapline = findSection($fileLines, "feature");
$fileLines = deleteSection($fileLines, "ldap");
$fileLines = appendToSection($fileLines, "feature", array("", "is_ldap=false"));

writearray($fileLines, $outfilefp);
fclose($outfilefp);
fclose($settingsfp);


//function to delete section
function deleteSection($fileLines, $sectionName){
	echo "Deleting this section: " . $sectionName . "\n\n";
	$commentmarker = array();
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
	$linenumber;
	foreach($fileLines as $index => $line){
		if(ereg("^\[$sectionName\]", $line)){
			$linenumber = $index;
		}
	}
	return $linenumber;
}

//function to replace section using delete and add
function replaceSection($fileLines, $sectionName, $linesToAdd){
	echo "Replacing Section: " . $sectionName . " with these lines:\n";
	echoarray($linesToAdd);
	echo "\n";
	
	$lineToReplace = findSection($fileLines, $sectionName);
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