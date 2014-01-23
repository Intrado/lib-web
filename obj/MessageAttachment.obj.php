<?

class MessageAttachment extends DBMappedObject {

	var $messageid;
	var $type;
	var $contentattachmentid;
	var $burstattachmentid;

	// cached objects
	var $contentAttachment = null;
	var $burstAttachment = null;

	function MessageAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messageattachment";
		$this->_fieldlist = array("messageid", "type", "contentattachmentid", "burstattachmentid");
		DBMappedObject::DBMappedObject($id);
	}

	/**
	 * Gets the filename of the attachment, if available. false otherwise
	 *
	 * @return string|bool
	 */
	function getFilename() {
		$filename = false;
		switch ($this->type) {
			case 'content':
				if (!$this->contentAttachment)
					$this->contentAttachment = new ContentAttachment($this->contentattachmentid);
				$filename = $this->contentAttachment->filename;
				break;

			case 'burst':
				if (!$this->burstAttachment)
					$this->burstAttachment = new BurstAttachment($this->burstattachmentid);
				$filename = $this->burstAttachment->filename;
				break;
		}
		return $filename;
	}

	/**
	 * Retrieves the size, in bytes, of the attachment or false if no size is available
	 *
	 * @return int|bool
	 */
	function getSize() {
		$size = false;
		switch ($this->type) {
			case 'content':
				if (!$this->contentAttachment)
					$this->contentAttachment = new ContentAttachment($this->contentattachmentid);
				$size = $this->contentAttachment->size;
				break;
			// case 'burst' unknown burst portion size
		}
		return $size;
	}

	/**
	 * Gets the contents of the attached file and returns it with the name and type
	 *
	 * @return array(<filename>, <content type>, <data>)|bool
	 */
	function getAttachmentData($personid = 0) {
		$filename = "";
		$contentType = "";
		$data = null;
		switch ($this->type) {
			case 'content':
				if (!$this->contentAttachment)
					$this->contentAttachment = new ContentAttachment($this->contentattachmentid);
				$filename = $this->contentAttachment->filename;
				break;

			case 'burst':
				if (!$this->burstAttachment)
					$this->burstAttachment = new BurstAttachment($this->burstattachmentid);
				$filename = $this->burstAttachment->filename;
				break;
		}
		if ($filedata = commsuite_attachmentGet($this->id, $personid)) {
			$filename = $this->contentAttachment->filename;
			$contentType = $filedata->contenttype;
			$data = $filedata->data;
		}
		if ($contentType && $data)
			return array($filename, $contentType, $data);
		else
			return false;
	}
}

?>