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

	function getAttachmentData() {
		$filename = "";
		$contentType = "";
		$data = null;
		switch ($this->type) {
			case 'content':
				$contentAttachment = new ContentAttachment($this->contentattachmentid);
				if ($c = contentGet($contentAttachment->contentid)) {
					$filename = $contentAttachment->filename;
					list($contentType,$data) = $c;
				}
				break;

			case 'burst':
				// TODO: Implement me
		}
		if ($contentType && $data)
			return array($filename, $contentType, $data);
		else
			return false;
	}
}

?>