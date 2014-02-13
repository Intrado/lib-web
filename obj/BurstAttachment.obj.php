<?
class BurstAttachment extends DBMappedObject {
	var $burstid;
	var $filename;
	var $secretfield;

	function BurstAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "burstattachment";
		$this->_fieldlist = array("burstid", "filename", "secretfield");
		DBMappedObject::DBMappedObject($id);
	}
}
?>