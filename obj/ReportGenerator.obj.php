<?
/*

6. 
ReportGenerator Class is a generic class that specific
reports inherit from. defines basic IO to reportinstance so
that reports have a well defines API. this superclass handles
translating reportinstance options and commandline options
into a standard format that subclass reports can use. also
defines the interface to Converter based on the inputs. 

7.
report classes inherit from the reportgenerator class. takes
options and builds the SQL query to generate a report. Also
specifies/handles report specific stuff like header html,
links to images (a reference to another reportinstance).
passes generic output as an array of fields to superclass.
superclass takes this and outputs to specified destination.
also specifies options/config for converter based on report
specific options. ie option for converting numeric data to
descriptive.

*/

//abstract superclass for specific reports 
class ReportGenerator {
	
	var $options = array();
	var $format;
	var $output;
	var $basefilename;
	
	
	//meta reports return an array of paths
	//to each of the files of the report.
	//the first is the "main" report
	//and useualy the html or pdf
	//meta reports can combine several reports into
	//a single file (ie report with summary, pdf, etc)
	//uses the basefilename to construct its filename
	//but can modify it by adding extensions or even
	//using a different base
	function generate ($forceupdate = false) {
	}
	
	function doPDF ($forceupdate) {
		
		//find my report
		$ar = new Report();
		$ar->findByName(get_class($this));
		
		$ari = new ReportInstance ();
		$ari->setReport($ar);
		$params = $this->options;//copy the same options
		$params['format'] = "html";//but set format to html
		$ari->setParameters($params);
		$ari->findInstance();
		$files = $ari->generate($forceupdate);
		if (!$files)
			return false; //fail!
		
		$in = SM_ENTERPRISE_REPORT_CACHE . "/" . $files[0];
		$out = $this->getFullPath();
		convertHtmlToPdf($in,$out);
		$files = array($this->getFileName());
		
		return $files;
	}
		
	function setBaseFileName ($base) {
		$this->basefilename = $base;
	}
	
	//returns the .ext for this format
	function getExtension () {
		switch($this->format) {
			case "web":
			case "html":
				return ".html";
			default:
				return "." . $this->format;
		}
	}
	
	function getFileName () {
		return $this->basefilename . $this->getExtension();
	}
	
	function getFullPath () {
		return SM_ENTERPRISE_REPORT_CACHE 
				. "/" . $this->basefilename 
				. $this->getExtension();
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