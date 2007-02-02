<?

$usage = "
Description:
This script will remove all text between /*CSDELETEMARKER_START*/ and /*CSDELETEMARKER_END*/ tokens
Usage:
php cleanforcommsuite.php [-r] [-f] [-b] file [files...]
-r : recurse directories
-f : force, don't ask confirmation
-b : move original to <file>.bak
";

$opts = array("force" => false, "recurse" => false, "backup" => false);
$files = array();
array_shift($argv); //ignore this script
foreach ($argv as $arg) {
	if ($arg[0] == "-") {
		for ($x = 1; $x < strlen($arg); $x++) {
			switch ($arg[$x]) {
				case "f":
					$opts['force'] = true;
					break;
				case "r":
					$opts['recurse'] = true;
					break;
				case "b":
					$opts['backup'] = true;
					break;
				default:
					echo "Unknown option " . $arg[$x] . "\n";
					exit($usage);
			}
		}
	} else {
		if (!file_exists($arg)) {
			echo "Skipping $arg, file does not exist\n";
		} else if (!$opts['recurse'] && is_dir($arg)) {
			echo "Skipping directory $arg\n";
		} else {
			$files[] = $arg;
		}
	}
}

if (count($files) == 0) {
	echo "No files to work on.\n";
	exit($usage);
}


$fileschanged = 0;
$bytesremoved = 0;
$fileschecked = 0;

function processFile ($file) {
	global $opts, $fileschanged, $bytesremoved, $fileschecked;
	if (is_dir($file)) {
		if ($opts['recurse']) {
			echo "Checking directory $file\n";
			$scandir = dir($file);
			while ($subfile = $scandir->read()) {
				//ignore files starting with "."
				if (strpos($subfile,".") === 0)
					continue;
				processFile ($file . "/" . $subfile);
			}
		} else {
			echo "Skipping directory $file\n";
		}
	} else {

		//never process this script
		if (stripos($file,"cleanforcommsuite.php") !== false) {
			echo "Refusing to process $file because it looks like a cleanforcommsuite.php script\n";
			return;
		}

		//read file contents, then strip text between the markers
		$data = file_get_contents($file);
		$newdata = preg_replace("/\/\*CSDELETEMARKER_START\*\/.*?\/\*CSDELETEMARKER_END\*\//s","",$data);
		$diffsize = strlen($data) - strlen($newdata);

		$fileschecked++;
		if ($diffsize) {
			echo "Done cleaning $file, stripped " . number_format($diffsize) . " bytes\n";
			if (!$opts['force']) {
				echo "Save changes?";
				//read, check result
				if (stripos(fgets(STDIN),"y") !== 0) {
					echo "Skipping.\n";
					return;
				}
			}

			if ($opts['backup']) {
				@unlink($file . ".bak");
				rename($file,$file. ".bak");
			}
			file_put_contents($file,$newdata);
			$fileschanged++;
			$bytesremoved += $diffsize;
		}
	}
}


//main loop
foreach ($files as $file) {
	processFile($file);
}

echo "Checked $fileschecked files and changed $fileschanged.\n";
echo number_format($bytesremoved) . " bytes stripped\n";

?>