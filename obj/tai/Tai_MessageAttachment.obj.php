<?
class Tai_MessageAttachment extends DBMappedObject {
	var $messageid;
	var $contentid;
	var $filename;
	var $size;

	function Tai_MessageAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "tai_messageattachment";
		$this->_fieldlist = array("messageid", "contentid", "filename", "size");
		DBMappedObject::DBMappedObject($id);
	}

}
?>