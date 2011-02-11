<?
class AspAdminQuery extends DBMappedObject{

	var $name = "";
	var $notes = "";
	var $query = "";
	var $numargs = "";

	function AspAdminQuery($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "aspadminquery";
		$this->_fieldlist = array("name","notes", "query", "numargs");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
}

?>