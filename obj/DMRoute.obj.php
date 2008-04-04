<?

class DMRoute extends DBMappedObject {

	var $dmid;
	var $phonematch = "";
	var $strip = 0;
	var $prefix = "";
	var $suffix = "";

	function DMRoute ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "dmroute";
		$this->_fieldlist = array("dmid", "phonematch", "strip", "prefix", "suffix");
		DBMappedObject::DBMappedObject($id);
	}

}

?>