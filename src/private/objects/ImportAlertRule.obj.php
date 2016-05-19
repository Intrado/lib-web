<?
class ImportAlertRule extends DBMappedObject {
	var $importid;
	var $categoryid;
	var $name;
	var $operation;
	var $testvalue;
	var $daysofweek;
	function ImportAlertRule ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "importalertrule";
		$this->_fieldlist = array(
			"importid",
			"categoryid",
			"name",
			"operation",
			"testvalue",
			"daysofweek"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>