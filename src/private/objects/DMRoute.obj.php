<?

class DMRoute extends DBMappedObject {

	var $dmid;
	var $match = "";
	var $strip = 0;
	var $prefix = "";
	var $suffix = "";

	function DMRoute ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "dmroute";
		$this->_fieldlist = array("dmid", "match", "strip", "prefix", "suffix");
		DBMappedObject::DBMappedObject($id);
	}

}

?>