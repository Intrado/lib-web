<?

class MessageGroup_1_5_3 extends DBMappedObject {
	var $userid;
	var $type = 'notification'; // enum('notification','targetedmessage','classroomtemplate')
	var $defaultlanguagecode = 'en';
	var $name;
	var $description;
	var $data = '';
	var $modified;
	var $lastused;
	var $permanent = 0;
	var $deleted = 0;

	var $preferredgender = 'female'; // Part of the $data string.

	var $messages = false; // Local cache of messages.

	function MessageGroup_1_5_3 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagegroup";
		$this->_fieldlist = array(
			"userid",
			"type",
			"defaultlanguagecode",
			"name",
			"description",
			"data",
			"modified",
			"lastused",
			"permanent",
			"deleted"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

class Message_1_5_3 extends DBMappedObject {
	var $userid;
	var $messagegroupid;
	var $name;
	var $description;
	var $data = ""; // Serialized header data.
	var $type;
	var $subtype; // phone => 'voice'; email => 'html'; 'plain'; sms => 'plain'
	var $autotranslate; // 'none', 'source', 'translated', 'overridden'
	var $modifydate;
	var $languagecode;

	// For 'print' header data.
	var $header1;
	var $header2;
	var $header3;
	var $fromaddress; //???

	// For 'email' header data.
	var $subject;
	var $fromname;
	var $fromemail;
	var $overrideplaintext = 0; // When type === 'email' and subtype === 'plain', indicates message is custom.

	function Message_1_5_3 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("userid", "messagegroupid", "name", "languagecode", "description", "type", "subtype", "data","modifydate", "autotranslate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function stuffHeaders () {
		if($this->type == 'email') {
			$this->data = 'subject=' . urlencode($this->subject) .
				'&fromname=' .  urlencode($this->fromname) .
				'&fromemail=' . urlencode($this->fromemail);

			if ($this->subtype == 'plain')
				$this->data .= '&overrideplaintext=' . urlencode($this->overrideplaintext);
		} elseif ($this->type == 'print') {
			$this->data = 'header1=' . urlencode($this->header1) . '&header2=' .  urlencode($this->header2) . '&header3=' . urlencode($this->header3) . '&fromaddress=' . urlencode($this->fromaddress);
		} elseif ($this->type == 'post' && $this->subtype == 'feed') {
			$this->data = 'subject=' . urlencode($this->subject);
		}
	}
}

class MessagePart_1_5_3 extends DBMappedObject {
	var $messageid;
	var $type;
	var $audiofileid;
	var $txt;
	var $fieldnum;
	var $defaultvalue;
	var $voiceid;
	var $sequence;
	var $maxlen;
	var $imagecontentid;

	var $audiofile;

	function MessagePart_1_5_3 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "imagecontentid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>