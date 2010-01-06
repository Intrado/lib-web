<?
class TargetedMessage extends DBMappedObject {
	var $messagekey;
	var $targetedmessagecategoryid;
	var $overridemessagegroupid;

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
