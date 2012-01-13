<?

class FeedCategory extends DBMappedObject {
	var $name;
	var $description;
	var $deleted = 0;

	function FeedCategory ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "feedcategory";
		$this->_fieldlist = array("name", "description", "deleted");
		DBMappedObject::DBMappedObject($id);
	}

}

?>
