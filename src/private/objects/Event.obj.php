<?
class Event extends DBMappedObject {
	var $userid;
	var $organizationid;
	var $sectionid;
	var $targetedmessageid;
	var $name;
	var $notes;
	var $occurence;

	function Event ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "event";
		$this->_fieldlist = array(
			"userid",
			"organizationid",
			"sectionid",
			"targetedmessageid",
			"name",
			"notes",
			"occurence"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>
