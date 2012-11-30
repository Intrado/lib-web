<?
class TargetedMessage extends DBMappedObject {
	var $messagekey;
	var $targetedmessagecategoryid;
	var $overridemessagegroupid;
	var $enabled = 1;
	var $deleted = 0;

	function TargetedMessage ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "targetedmessage";
		$this->_fieldlist = array(
			"messagekey",
			"targetedmessagecategoryid",
			"overridemessagegroupid",
			"enabled",
			"deleted"
		);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>
