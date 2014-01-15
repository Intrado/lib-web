<?
class MessageAttachment extends DBMappedObject {

	var $messageid;
	var $contentid;
	var $filename;
	var $size;
	var $type;
	var $contentattachmentid;
	var $burstattachmentid;

	function MessageAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messageattachment";
		$this->_fieldlist = array("messageid", "contentid", "filename", "size", "type", "contentattachmentid", "burstattachmentid");
		DBMappedObject::DBMappedObject($id);
	}
}

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