<? 

class Message_7_8_r2 extends DBMappedObject {

	var $messagegroupid;
	var $userid;
	var $name;
	var $description;
	var $data = ""; //for headers
	var $type;
	var $subtype;
	var $modifydate;
	var $deleted = 0;
	var $autotranslate;
	var $languagecode;


	function Message_7_8_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("messagegroupid", "userid", "name", "description", "type", "subtype", "data", "modifydate", "deleted", "autotranslate", "languagecode");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}


class MessagePart_7_8_r2 extends DBMappedObject {

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

	function MessagePart_7_8_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

class MessageGroup_7_8_r2 extends DBMappedObject {
	var $userid;
	var $type;
	var $name;
	var $description;
	var $modified;
	var $lastused;
	var $permanent = 0;
	var $deleted = 0;
	var $defaultlanguagecode;
	var $data = "";

	function MessageGroup_7_8_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagegroup";
		$this->_fieldlist = array(
			"userid",
			"name",
			"description",
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
