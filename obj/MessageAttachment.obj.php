<?

class MessageAttachment extends DBMappedObject {

	var $messageid;
	var $type;
	var $contentattachmentid;
	var $burstattachmentid;

	function MessageAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messageattachment";
		$this->_fieldlist = array("messageid", "type", "contentattachmentid", "burstattachmentid");
		DBMappedObject::DBMappedObject($id);
	}

}

?>