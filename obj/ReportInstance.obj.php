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
		if(isset($paramarray['organizationids']) && is_array($paramarray['organizationids'])) {
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
	
	function generate ($forceupdate = false) {
		if (!$cachefiles = $this->checkFiles())
			$forceupdate = true;
		
		//see if we should use cache
		if (!$forceupdate) {
			//check time interval and see if we need to regenerate reports
			if ($this->refreshinterval > 0) {
				$query = "select date_add('$this->lastrun', interval $this->refreshinterval minute) >= now()"; 
				if (QuickQuery($query)) {
					return $cachefiles;
				}
			} else if ($this->refreshinterval == 0) {
				if ($this->lastrun != "0000-00-00 00:00:00") {
					return $cachefiles;
				}
			}
		}
		
		//generate report
		include_once($this->report->path);
		if (!class_exists($this->report->name)) {
			echo "cant find class: " . $this->report->name 
				. "<br>path:" . $this->report->path . "<br>\n";
			return false;
		}
		$report = new $this->report->name();
		$report->setOptionsString($this->parameters);
		$report->setBaseFileName($this->instancehash); 
		if (!$files = $report->generate($forceupdate))
			return false;
		//update the filelist
		$this->updateFileList($files);
		
		QuickUpdate("update reportinstance set lastrun=now() where id=$this->id");
		
		//return the list of files
		//or false if there are no files
		if (count($files) == 0)
			$files = false;
		return $files;
	}
	
	function checkFiles () {
		//checks that all of the files are still there
		//returns the list or false if some are missing
		if (!$filelist = $this->getFileList() )
			return false;
		
		foreach ($filelist as $curfile) {
			if (!file_exists(SM_ENTERPRISE_REPORT_CACHE . "/" . $curfile)) 
				return false;
		}
		
		return $filelist;
	}
	
	
	//returns an array of files from this report
	function getFileList () {
		$query = "select filename from reportinstancefile where id=$this->id order by fileorder asc";
		$result = Query($query);
		$files = array();
		while ($row = DBGetRow($result)) {
			$files[] = $row[0];
		}
		
		if (count($files) > 0)
			return $files;
		else
			return false;
	}
	
	//synchronizes the list of files
	//deletes files that are no longer in the report
	//and updates the DB
	function updateFileList ($updatedfiles) {
		if (!$curfiles = $this->getFileList())
			$curfiles = array();
		
		//get all of the files that no longer exist in this report
		$oldfiles = array_diff($curfiles, $updatedfiles);
		//get all of the files that do not already exist
		$newfiles = array_diff($updatedfiles, $curfiles);
		
		
		foreach ($oldfiles as $curfile) {
			QuickUpdate("delete from reportinstancefile "
						."where id=$this->id and filename='$curfile'");
			//see if this is the last reference to the file
			$more = QuickQuery("select count(*) from reportinstancefile where filename='$curfile'");
			if (!$more) {
				@unlink(SM_ENTERPRISE_REPORT_CACHE . "/" . $curfile);
			}
		}
		
		foreach ($newfiles as $curfile) {
			QuickUpdate("insert into reportinstancefile (id, filename) "
						."values ($this->id, '$curfile')");
		}
		
		//update the order of files
		foreach ($updatedfiles as $index => $curfile) {
			QuickUpdate("update reportinstancefile set fileorder='$index' 
						where id=$this->id and filename='$curfile'"); 
		}
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