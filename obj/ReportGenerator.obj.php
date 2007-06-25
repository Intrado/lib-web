<?

//abstract superclass for specific reports 
class ReportGenerator {
	
	var $userid;
	var $reportinstance;
	var $format;
	var $query="";
	var $params;
	var $reporttype;
	
	function generate($options = null){
		$this->generateQuery();
		switch($this->format){
			case 'html':
				$this->runHtml($options);
				break;
			case 'csv':
				$this->runCSV($options);
				break;
			case 'pdf':
				$this->setReportFile();
				$this->runPDF($options);
				break;
		}
	}
	
	function runPDF($options){
		
		$instance = $this->reportinstance;
		$xmlparams = array();
		$xmlparams[] = new XML_RPC_Value($this->reportfile, 'string');
		$xmlparams[] = new XML_RPC_Value($options['host'], 'string');
		$xmlparams[] = new XML_RPC_Value($options['user'], 'string');
		$xmlparams[] = new XML_RPC_Value($options['pass'], 'string');
		$xmlparams[] = new XML_RPC_Value($this->query, 'string');
		
		$timeoffset = QuickQuery("select value from setting where name = 'timezone'");
		$timeoffsetquery = "set time_zone = '$timeoffset'";
		$xmlparams[] = new XML_RPC_Value($timeoffsetquery, 'string');
		
		$fieldlist = $instance->getFields();
		$fieldarray = array();
		foreach($fieldlist as $index => $title){
			$newindex = preg_replace("{f}", "flex_title_", $index);
			$fieldarray[$newindex] = new XML_RPC_VALUE($title, 'string');
		}
		$xmlparams[] = new XML_RPC_Value($fieldarray, 'struct');
		
		$activefields = $instance->getActiveFields();
		$active = array();
		foreach($activefields as $index){
			$newindex = preg_replace("{f}", "", $index);
			$active[$newindex] = new XML_RPC_VALUE("true", 'string');
		}
		$xmlparams[] = new XML_RPC_Value($active, 'struct');
		
		$xmlparams[] = new XML_RPC_Value($options['filename'], 'string');
		$method = "Resizer.render";
		$result = $this->reportxmlrpc($method, $xmlparams);
		return $result;
	}
	
	function reportxmlrpc($method, $xmlparams){
		$msg = new XML_RPC_Message($method, $xmlparams);

		$cli = new XML_RPC_Client('/xmlrpc', 'localhost:8089');
	
		$resp = $cli->send($msg);
	
		if (!$resp) {
	    	error_log($method . ' communication error: ' . $cli->errstr);
		} else if ($resp->faultCode()) {
			error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
		} else {
			$val = $resp->value();
	    	$data = XML_RPC_decode($val);
			if ($data != "success") {
				error_log($method . " " .$data);
			} else {
				// success;
				return $data;
			}
		}
		return "failure";
	}

	//setOptions
	//array of options
	function setOptions ($options) {
		$this->options = $options;
		
		
		//get format
		if (isset($this->options['format'])) {
			$this->format = $this->options['format'];
		} 
		
		//get output
		if (isset($this->options['output'])) {
			$this->output = $this->options['output'];
		} else {
			$this->output = "file";
		}
	}
	
	//converts a string of options
	//ie from a reportinstance parameter string
	//or a GET query string
	function setOptionsString ($paramstring) {
		$newoptions = array();
		//parse this into an array
		parse_str($paramstring, $newoptions);
		$this->setOptions($newoptions);
	}
	
	//setOptionsCLI
	//get options from command line params
	//parses basic params ie "-school=1 -school=2 -someflag -format=csv"
	function setOptionsCLI ($argvars, $argcount) {
		$options = array();
		for ($x = 1; $x < $argcount ; $x++) {
			if (strpos($argvars[$x], "-") === 0 ) {
				$arg = substr($argvars[$x], 1);	//get everything after the -
				if (strpos($arg, "=")) {
					list($name, $value) = explode("=", $arg);
				} else {
					$name = $arg;
					$value = 1;
				}
				
				//see if we have something set for this already
				if (isset($options[$name])) {
					//if it is an array
					if (is_array($options[$name])) {
						//then just add to it
						$options[$name][] = $value;
					} else {
						//otherwise convert it to one
						//and add its old and new values to the array
						$options[$name] = array($options[$name], $value);
					}
				} else {
					//just set the value as a single
					$options[$name] = $value;
				}
			}
		}
		
		$this->setOptions($options);
	}
}

?>