<?
class Topic extends DBMappedObject {
	var $name;

	function Topic($id = NULL) {
		$this->_tablename = "tai_topic";
		$this->_fieldlist = array("name");

		DBMappedObject::DBMappedObject($id);
	}
}
?>