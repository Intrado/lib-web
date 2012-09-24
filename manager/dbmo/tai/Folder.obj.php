<?
class Folder extends DBMappedObject{
	
	var $userid;
	var $name;
	var $type;

	function Folder($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "tai_folder";
		$this->_fieldlist = array("userid", "name", "type");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>