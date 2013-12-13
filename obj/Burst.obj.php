<?

// For PDF "bursting"
class Burst extends DBMappedObject {

	var $userid;
	var $contentid;
	var $name;
	var $status;
	var $filename;
	var $bytes;
	var $deleted;

	function Burst ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "burst";
		$this->_fieldlist = array("userid", "contentid", "name", "status", "filename", "bytes", "deleted", "bursttemplateid", "uploaddatems", "totalpagesfound", "actualreportscount");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
