<?

class BurstTemplate extends DBMappedObject {

	var $name;
	var $x;
	var $y;
	var $created;
	var $deleted;

	function BurstTemplate ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "burst_template";
		//$this->_fieldlist = array("name", "x", "y", "created", "deleted");
		$this->_fieldlist = array("name", "x", "y");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
