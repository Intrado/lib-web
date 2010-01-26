<?
class Organization extends DBMappedObject {
	var $orgkey;
	var $deleted;
	

	function Organization ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "organization";
		$this->_fieldlist = array(
			"orgkey",
			"deleted"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>
