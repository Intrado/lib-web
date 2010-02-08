<?
class TargetedMessage extends DBMappedObject {
	var $messagekey;
	var $targetedmessagecategoryid;
	var $overridemessagegroupid;
	var $enabled;
	var $deleted = 0;

	function TargetedMessage ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "targetedmessage";
		$this->_fieldlist = array(
			"messagekey",
			"targetedmessagecategoryid",
			"overridemessagegroupid"
		);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>
