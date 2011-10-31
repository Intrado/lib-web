<?

function formatAlert($name, $operation, $testvalue, $actualvalue) {
	switch($name) {
		case "daysold":
			$str = "Import is delayed. Expected import within {$testvalue} days. Triggered at {$actualvalue} days";
			break;
		case "size":
			$str = "File size is too " . ($operation=="gt"?"big":"small") .
						 ". Expected " . ($operation=="gt"?"less":"more") . " than {$testvalue} bytes. Actual size: {$actualvalue} bytes";
			break;
		case "importtime":
			$midnight_today = mktime(0,0,0);
			$testvalue = date("g:i a",$midnight_today + $testvalue);
			$actualvalue = date("g:i a",$midnight_today + $actualvalue);
			$str = "Imported too " . ($operation=="gt"?"late":"early") .
				". Expected import " . ($operation=="gt"?"before":"after") . " $testvalue. Imported at: $actualvalue";
			break;
		case "unconfigured":
			$str = "Customer is not configured with any rules";
			break;
	}
	return $str;
}

?>
