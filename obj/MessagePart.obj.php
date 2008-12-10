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

	var $audiofile;

	function MessagePart ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");
		$this->_childobjects = array("audiofile");
		$this->_childclasses = array("AudioFile");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function copyNew() {
		$newpart = new MessagePart($this->id);
		$newpart->id = null;
		$newpart->create();
		return $newpart;
	}
}



?>