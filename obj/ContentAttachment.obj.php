<?
class ContentAttachment extends DBMappedObject {
	var $contentid;
	var $filename;
	var $size;

	function ContentAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "contentattachment";
		$this->_fieldlist = array("contentid", "filename", "size");
		DBMappedObject::DBMappedObject($id);
	}
}
?>