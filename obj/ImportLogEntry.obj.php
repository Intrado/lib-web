<?

class ImportLogEntry extends DBMappedObject {
	
	var $importid;
	var $severity;
	var $txt;
	var $linenum;

	function ImportLogEntry ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "importlogentry";
		$this->_fieldlist = array("importid", "severity","txt","linenum");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>