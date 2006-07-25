<?


/*


#
# Table structure for table `reportinstance`
#

CREATE TABLE reportinstance (
  ID int(11) NOT NULL auto_increment,
  ReportID smallint(6) NOT NULL default '0',
  LastRun datetime NOT NULL default '0000-00-00 00:00:00',
  RefreshInterval int(11) NOT NULL default '5',
  InProgress tinyint(4) NOT N[ULL default '0',
  MetaReport tinyint(4) NOT NULL default '0',
  Parameters text NOT NULL,
  InstanceHash varchar(32) NOT NULL default '',
  PRIMARY KEY  (ID),
  KEY refresh (LastRun,RefreshInterval),
  KEY InstanceHash (InstanceHash)
) TYPE=MyISAM;



*/




//this goes through and deletes all the old files from reportcache
function CleanReportCache () {
	//get a list of main files for each reportinstance that has expired
	$count = 0;

	
	$query= "
	select  ri.id, a.filename
	from 		reportinstance ri, reportinstancefile a
	where 		a.id=ri.id 
	and			a.fileorder=0
	and 		(date_add(ri.lastrun, interval ri.refreshinterval minute) < now()
	or			ri.lastrun = '0000-00-00 00:00:00')
	";
	
	if ($result = mysql_query($query)) {
		while ($row = mysql_fetch_row($result)) {
			//echo SM_ENTERPRISE_REPORT_CACHE . "/" . $row[1] . "\n";
			@unlink(SM_ENTERPRISE_REPORT_CACHE . "/" . $row[1]);
			$query = "delete from reportinstancefile where id='$row[0]'";
			QuickUpdate($query);
			$count++;
		}
	}
	
	return $count;
}



class ReportInstance extends DBMappedObject {
	//table vars
	var $id;
	var $reportid;
	var $lastrun;
	var $refreshinterval;
	var $inprogress;
	var $metareport;
	var $parameters;
	var $instancehash;

	//related parent object
	var $report;

	function ReportInstance ($id = NULL) {
		$this->_tablename = "reportinstance";
		$this->_fieldlist = array("reportid", "lastrun","refreshinterval",
					"inprogress","metareport","parameters","instancehash");
		$this->id = $id;
		$this->refresh();
	}
	
	//override the refresh function
	//add a part to refresh the report object based on reportid
	function refresh ($loadparams = false) {
		$isrefreshed = false;
		
		//call parent class' refresh
		//(get_parent_class(get_class($this)))
		if ( DBMappedObject::refresh()) {
			$this->report = new Report($this->reportid);
			//TODO get filelist
			$isreisrefreshed = true;
		}
		
		return $isrefreshed;
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
		$this->rehash();
	}
	
	//takes an input string formatted like http get query
	function setParameters ($paramarray, $keepnumericindex = false) {
		//resort the params by name, value
		//explaination if we have name[]=2&abc=123&name[]=1 then
		//it will break into "name=value2" "abc=123" "name=value1" 
		//then sort to "abc=123" "name[]=1" "name[]=2"
		//this way we can reorder randomly ordered parameters so that
		//the hash will check out ok.
		$paramstring = my_http_build_query($paramarray, $keepnumericindex);
		$this->setParameterString($paramstring);			
	}
	
	function getParameters () {
		$paramarray = array();
		parse_str($this->parameters, $paramarray);
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
		$result = mysql_query($query);
		$files = array();
		while ($row = mysql_fetch_row($result)) {
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
	
	
}

?>