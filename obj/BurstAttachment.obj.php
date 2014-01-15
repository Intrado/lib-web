<?
class BurstAttachment extends DBMappedObject {
	var $contentid;
	var $filename;
	var $size;

	function ContentAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "burstattachment";
		$this->_fieldlist = array("burstid", "filename");
		DBMappedObject::DBMappedObject($id);
	}
}
?>