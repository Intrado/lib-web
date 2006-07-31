<?

class Content extends DBMappedObject {

	var $contenttype;
	var $data;

	function Content ($id = NULL) {
		$this->_tablename = "content";
		$this->_fieldlist = array("contenttype", "data");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>