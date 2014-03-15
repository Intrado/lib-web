<?

class BurstTemplate extends DBMappedObject {

	var $name;
	var $x;
	var $y;
	var $pagesskipstart;
	var $pagesskipend;
	var $pagesperreport;
	var $created;
	var $deleted;
	var $identifiertextpattern;

	function BurstTemplate ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "bursttemplate";
		$this->_fieldlist = array("name", "x", "y", "pagesskipstart", "pagesskipend", "pagesperreport", "identifiertextpattern");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
