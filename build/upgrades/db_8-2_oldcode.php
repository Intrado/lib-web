<? 

class Message_8_2 extends DBMappedObject {
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
	
	function Message_8_2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("userid", "messagegroupid", "name", "languagecode", "description", "type", "subtype", "data","modifydate", "autotranslate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}


class MessagePart_8_2 extends DBMappedObject {
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
	
	function MessagePart_8_2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "imagecontentid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");
	
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

class MessageGroup_8_2 extends DBMappedObject {
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
	
	function MessageGroup_8_2 ($id = NULL) {
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


?>
