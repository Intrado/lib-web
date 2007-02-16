<?

class ImportJob extends DBMappedObject {

	var $importid;
	var $jobid;
	
	function ImportJob ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "importjob";
		$this->_fieldlist = array("importid", "jobid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}