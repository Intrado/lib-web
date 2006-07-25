<?

class ImportField extends DBMappedObject {

	var $importid;
	var $mapto;
	var $mapfrom;

	function ImportField ($id = NULL) {
		$this->_tablename = "importfield";
		$this->_fieldlist = array("importid", "mapto", "mapfrom");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>