<?

class MessagePart extends DBMappedObject {

	var $messageid;
	var $type;
	var $audiofileid;
	var $txt;
	var $fieldnum;
	var $defaultvalue;
	var $voiceid;
	var $sequence;
	var $maxlen;
	var $contentid;

	var $audiofile;

	function MessagePart ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "contentid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");
		$this->_childobjects = array("audiofile", "content");
		$this->_childclasses = array("AudioFile", "Content");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}



?>