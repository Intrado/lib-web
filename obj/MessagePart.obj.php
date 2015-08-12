<?

class MessagePart extends DBMappedObject {

	var $messageid;
	var $type;
	var $audiofileid;
	var $imagecontentid;
	var $messageattachmentid;
	var $txt;
	var $fieldnum;
	var $defaultvalue;
	var $voiceid;
	var $sequence;
	var $maxlen;

	var $audiofile;
	var $context; //a transient object for storing extra data

	function MessagePart ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "imagecontentid", "messageattachmentid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}



?>
