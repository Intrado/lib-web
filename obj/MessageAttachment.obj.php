<?

class MessageAttachment extends DBMappedObject {

	var $messageid;
	var $contentid;
	var $filename;
	var $size;

	function MessageAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messageattachment";
		$this->_fieldlist = array("messageid", "contentid", "filename", "size");
		DBMappedObject::DBMappedObject($id);
	}

}

?>