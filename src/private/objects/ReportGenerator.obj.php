<?

//abstract superclass for specific reports
abstract class ReportGenerator {

	const MAX_REPORT_PAGES = 33000;


	// Constructor
	public function __construct() {
		$this->_readonlyDB = readonlyDBConnect();
		if (!$this->_readonlyDB) {
			global $_dbcon;
			$this->_readonlyDB = $_dbcon;
		}
		$this->urlComponent = QuickQuery("select value from setting where name='urlcomponent'", $this->_readonlyDB);
	}

	// --------------------------------------------------------------------
	// Abstract methods that derived classes must implement
	abstract protected function generateQuery($hackPDF = false);


	// --------------------------------------------------------------------
	// public interface

	// FIXME - external reports are accessing these directly; make them use an accessor method, then turn these private!
	public $reportinstance;
	public $userid;
	public $format;
	public $query = '';
	public $testquery = '';
	public $params;
	public $reporttype;
	public $_readonlyDB;
	public $urlComponent;

	// TODO - privatize any methods used internally only
	public function testSize(){
		$result = "";
		$count = 0;
		$this->generateQuery();
		if ($this->testquery != ""){
			$count = QuickQuery($this->testquery, $this->_readonlyDB);
		}
		if ($count > self::MAX_REPORT_PAGES) {
			$result = "Report exceeds max page limit";
		}
		
		return $result;
	}

	public function generate($options = false){
		$result = "success";
		$hackPDF = ($this->format == "pdf"); // used for Gfields display
		$this->generateQuery($hackPDF);

		switch($this->format){
			case 'html':
				$this->runHtml();
				break;
			case 'csv':
				$this->runCSV($options);
				break;
			case 'pdf':
				$this->setReportFile();
				$this->runPDF($options);
				break;
		}
		
		return $result;
	}

	public function set_format($format) {
		$this->format = $format;
	}

	public function runPDF($options = false) {
		global $_DBHOST, $_DBNAME, $_DBUSER, $_DBPASS, $SETTINGS;
		$reportGraphBaseUrl=($SETTINGS["reportserver"]["graphBaseUrl"] ? $SETTINGS["reportserver"]["graphBaseUrl"] : "https://asp.schoolmessenger.com" );
		$instance = $this->reportinstance;
		$xmlparams = array();
		$xmlparams[] = new XML_RPC_Value($this->reportfile, 'string');
		$xmlparams[] = new XML_RPC_Value("jdbc:mysql://" . $_DBHOST . "/" . $_DBNAME . "?useServerPrepStmts=false&useUnicode=true&characterEncoding=UTF-8", 'string');
		$xmlparams[] = new XML_RPC_Value($_DBUSER, 'string');
		$xmlparams[] = new XML_RPC_Value($_DBPASS, 'string');
		$xmlparams[] = new XML_RPC_Value($this->query, 'string');

		$timeoffset = getSystemSetting("timezone");
		$timeoffsetquery = "set time_zone = '$timeoffset'";
		$xmlparams[] = new XML_RPC_Value($timeoffsetquery, 'string');

		$xmlparams[] = new XML_RPC_Value( $reportGraphBaseUrl . "/" . $this->urlComponent . "/report/graph_job.php", 'string');

		$params = $this->generateXmlParams();
		$xmlparams[] = new XML_RPC_Value($params, 'struct');

		$activefields = (isset($this->params['activefields']) && ($this->params['activefields'] != "")) ? explode(",", $this->params['activefields']) : array();

		$active = array();
		foreach($activefields as $index){
			$newindex = substr($index, 1);
			if (strpos($index, "g") === 0) {
				$newindex = 20 + substr($index, 1);
			}
			$active[$newindex] = new XML_RPC_VALUE("true", 'string');
		}
		if(count($active) == 0)
			$active["empty"] = new XML_RPC_VALUE("", 'string');

		$xmlparams[] = new XML_RPC_Value($active, 'struct');

		$method = "Resizer.render";
		$result = $this->reportxmlrpc($method, $xmlparams);
		
		if (isset($options['filename'])) {
			// save to local file
			$fp = fopen($options['filename'], "w");
			if (!$fp)
				return;
			fwrite($fp, $result);
			fclose($fp);
		} else {
		
			// stream back the file
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.pdf");
			header("Content-type: application/pdf");
			echo $result;
		}
	}
	
	public function generateXmlParams(){
		global $USER;
		$params = array();
		// Ffields
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		foreach($fieldlist as $index => $title){
			$newindex = preg_replace("{f}", "flex_title_", $index);
			$params[$newindex] = new XML_RPC_VALUE($title, 'string');
		}
		// Gfields
		$fields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		foreach($fieldlist as $index => $title){
			$index = 20 + substr($index,1); // strip the g and add twenty
			$newindex = "flex_title_".$index;
			$params[$newindex] = new XML_RPC_VALUE($title, 'string');
		}
		// more params
		$specificparams = $this->getReportSpecificParams();
		if(count($specificparams)){
			foreach($specificparams as $index => $value){
				$params[$index] = new XML_RPC_VALUE($value, 'string');
			}
		}
		// these are set within Resizer.java
		//$params["SUBREPORT_DIR"] = new XML_RPC_VALUE("res/jasper/reportserver/", 'string');
		//$params["iconLocation"] = new XML_RPC_VALUE("res/images/reportserver/", 'string');
		$reportname = report_name($this->params['reporttype']);

		$subname = isset($this->params['subname']) ? $this->params['subname'] : "";
		$description = isset($this->params['description']) ? $this->params['description'] : "";

		$params["reportname"] = new XML_RPC_VALUE($reportname, 'string');
		$params["subname"] = new XML_RPC_VALUE($subname, 'string');
		$params["username"] = new XML_RPC_VALUE($USER->login, 'string');
		$customer = getSystemSetting('displayname');
		if(!$customer)
			$customer = "";
		$params["accountname"] = new XML_RPC_VALUE($customer, 'string');
		$params["firstname"] = new XML_RPC_VALUE($USER->firstname, 'string');
		$params["lastname"] = new XML_RPC_VALUE($USER->lastname, 'string');
		$params["description"] = new XML_RPC_VALUE($description, 'string');
		$params["createdate"] = new XML_RPC_VALUE(date("M j, Y g:i a", strtotime("now")), 'string');
		if(isset($this->params['sorrymessage']))
			$params["sorrymessage"] = new XML_RPC_VALUE($this->params['sorrymessage'], 'string');
		return $params;
	}

	public function reportxmlrpc($method, $xmlparams){
		global $SETTINGS;
		$reporthost = (isset($SETTINGS['reportserver']['host'])) ? $SETTINGS['reportserver']['host'] : 'localhost:9086';
		$reportpath = (isset($SETTINGS['reportserver']['path'])) ? $SETTINGS['reportserver']['path'] : '/xmlrpc';
		
		$msg = new XML_RPC_Message($method, $xmlparams);
		$msg->setSendEncoding("UTF-8");
		$cli = new XML_RPC_Client($reportpath, $reporthost);

		$resp = $cli->send($msg, 600);

		if (!$resp) {
			error_log('ReportGenerator.obj.php::reportxmlrpc(): ' . $method . ' communication error: ' . $cli->errstr);
		}
		else if ($resp->faultCode()) {
			error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
		}
		else {
			$val = $resp->value();
			$data = XML_RPC_decode($val);
			return $data;
		}
		return ""; // failure
	}

	//setOptions
	//array of options
	public function setOptions ($options) {
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
	public function setOptionsString ($paramstring) {
		$newoptions = array();
		//parse this into an array
		parse_str($paramstring, $newoptions);
		$this->setOptions($newoptions);
	}

	//setOptionsCLI
	//get options from command line params
	//parses basic params ie "-school=1 -school=2 -someflag -format=csv"
	public function setOptionsCLI ($argvars, $argcount) {
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
