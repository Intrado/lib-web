<?

class ImportField extends DBMappedObject {

	var $importid;
	var $guardiansequence;
	var $mapto;
	var $action = "copy";
	var $mapfrom;
	var $val;

	function ImportField ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "importfield";
		$this->_fieldlist = array("importid", "guardiansequence", "mapto", "action", "mapfrom", "val");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>