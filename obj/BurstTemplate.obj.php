<?

class BurstTemplate extends DBMappedObject {

	var $name;
	var $x;
	var $y;
	var $created;
	var $deleted;

	function BurstTemplate ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "bursttemplate";
		$this->_fieldlist = array("name", "x", "y", "created", "deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
