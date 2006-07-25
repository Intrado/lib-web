<?

class Import extends DBMappedObject {

	var $customerid;
	var $userid;
	var	$listid;
	var $name;
	var $description;
	var $status;
	var $type;
	var $path;
	var $scheduleid;
	var $ownertype;
	var $updatemethod;
	var $lastrun;

	function Import ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "import";
		$this->_fieldlist = array("customerid", "userid", "listid", "name", "description", "status", "type", "path", "scheduleid", "ownertype", "updatemethod", "lastrun");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>