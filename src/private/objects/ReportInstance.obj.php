<?
class ReportInstance extends DBMappedObject {
	//table vars
	
	var $parameters;
	var $instancehash;

	//related parent object
	var $report;

	function ReportInstance ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "reportinstance";
		$this->_fieldlist = array("parameters", "instancehash" );
		DBMappedObject::DBMappedObject($id);
	}
		
	function  findInstance () {
		if ($this->reportid == NULL)
			return false;
		
		//see if we can find a reportinstance with the same hash and report
		$query = "select id from reportinstance "
				."where instancehash='$this->instancehash' "
				."and reportid='" . $this->report->id . "' ";
		$newid = QuickQuery($query);
		
		if ($newid) {
			$this->id = $newid;
			$this->refresh();
		} else {
			//create one
			$fieldlist = array("reportid","metareport","parameters","instancehash");
			if ($newid = $this->create($fieldlist))
				$this->refresh();
		}
		
		return $newid;
	}
	
	function setParameterString ($paramstring) {
		$params = explode("&", $paramstring);
		sort($params);
		$this->parameters = implode("&", $params);	
		//$this->rehash();
	}
	
	//takes an input string formatted like http get query
	function setParameters ($paramarray, $keepnumericindex = false) {
		//resort the params by name, value
		//explaination if we have name[]=2&abc=123&name[]=1 then
		//it will break into "name=value2" "abc=123" "name=value1" 
		//then sort to "abc=123" "name[]=1" "name[]=2"
		//this way we can reorder randomly ordered parameters so that
		//the hash will check out ok.
		if(isset($paramarray['rules'])){
			$paramarray['rules'] = $this->ruleArraytoString($paramarray['rules']);	
		}
		
		if(isset($paramarray['sectionids']) && is_array($paramarray['sectionids']) && count($paramarray['sectionids']) > 0) {
			$paramarray['sectionids'] = implode(';', $paramarray['sectionids']);
			unset($paramarray['organizationids']);
		} else {
			unset($paramarray['sectionids']);
		}
		
		if(isset($paramarray['organizationids']) && is_array($paramarray['organizationids']) && count($paramarray['organizationids']) > 0) {
			$paramarray['organizationids'] = implode(';', $paramarray['organizationids']);
		} else {
			unset($paramarray['organizationids']);
		}
		
		$paramstring = http_build_query($paramarray, false, "&");
		$this->setParameterString($paramstring);
			
	}
	
	function setString ($paramstring) {
		$params = explode("&", $paramstring);
		sort($params);
		return implode("&", $params);
		//$this->rehash();
	}
		
	function getParameters () {
		$paramarray = array();
		$paramarray = sane_parsestr($this->parameters);
		if(isset($paramarray['rules']))
			$paramarray['rules'] = $this->ruleStringtoArray($paramarray['rules']);
			
		if(isset($paramarray['sectionids']))
			$paramarray['sectionids'] = explode(';', $paramarray['sectionids']);
			
		if(isset($paramarray['organizationids']))
			$paramarray['organizationids'] = explode(';', $paramarray['organizationids']);
			
		return $paramarray;
	}
	
	function rehash () {
		$this->instancehash = md5($this->report->name . $this->report->path . $this->parameters);
	}
	
	function setReport ($reportobj) {
		$this->report = $reportobj;
		$this->reportid = $reportobj->id;
	}
	
	function setReportID ($id) {
		$this->reportid = $id;
		$this->report = new Report($id);
	}
	
	
	// takes the rules string and converts it to an array of rules
	function ruleStringtoArray($rules = ""){
		$finalarray = array();
		if($rules != ""){
			$rulearray = explode("||", $rules);
			foreach($rulearray as $rule){
				if($rule != ""){
					$rule = explode(";", $rule);
					$newrule = new Rule();
					$newrule->logical = $rule[0];
					$newrule->op = $rule[1];
					$newrule->fieldnum = $rule[2];
					$newrule->val = $rule[3];
					$finalarray[] = $newrule;
					$newrule->id = array_search($newrule, $finalarray);
					$finalarray[$newrule->id] = $newrule;
				}
			}
		}
		return $finalarray;
	}
	
	//converts an array of rule objects to a big string
	function ruleArraytoString($rules = array()){
		$rulestringarray = array();
		foreach($rules as $rule){
			$rulestringarray[] = implode(";", array($rule->logical, $rule->op, $rule->fieldnum, $rule->val));
		}
		return implode("||", $rulestringarray);
	
	}
	
}

?>