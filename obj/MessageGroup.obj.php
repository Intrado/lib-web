<?php
class MessageGroup extends DBMappedObject {
	var $userid;
	var $name;
	var $description;
	var $modified;
	var $lastused;
	var $permanent = 0;
	var $deleted = 0;

	function MessageGroup ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagegroup";
		$this->_fieldlist = array(
			"userid",
			"name",
			"description",
			"modified",
			"lastused",
			"permanent",
			"deleted"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
