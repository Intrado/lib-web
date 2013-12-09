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
		// TODO - how do you handle assignments to a function in SQL using DBMO such as '... created = NOW() ...'
		//$this->_fieldlist = array("name", "x", "y", "created", "deleted");
		$this->_fieldlist = array("name", "x", "y", "pagesskipstart", "pagesskipend", "pagesperreport");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
