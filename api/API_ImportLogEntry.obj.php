<?

class API_ImportLogEntry {
	var $severity;
	var $message;
	var $line;
	
	// param must be ImportLogEntry DBMO
	function API_ImportLogEntry($importlogentry) {
		$this->severity = $importlogentry->severity;
		$this->message = $importlogentry->txt;
		$this->line = $importlogentry->linenum;
	}
}

?>