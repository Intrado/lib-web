<?php
class MessageGroup extends DBMappedObject {
	var $userid;
	var $defaultlanguagecode = 'en';
	var $name;
	var $description;
	var $modified;
	var $lastused;
	var $permanent = 0;
	var $deleted = 0;
	
	var $messages = false;

	function MessageGroup ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagegroup";
		$this->_fieldlist = array(
			"userid",
			"defaultlanguagecode",
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
	
	function getMessages() {
		if (!$this->id)
			return array();
		
		if ($this->messages === false)  {
			$this->messages = DBFindMany("Message", "from message where not deleted and messagegroupid=? order by id", false, array($this->id));
		}
		
		return $this->messages;
	}
	
	// Returns true/false; true if the user has a message with its defaultlanguagecode.
	function hasDefaultMessage($type, $subtype) {
		foreach ($this->getMessages() as $message) {
			if ($message->type == $type && 
				$message->subtype == $subtype && 
				$message->languagecode == $this->defaultlanguagecode)
				return true;
		}
		
		return false;
	}
	
	function hasMessage($type, $subtype = null, $languagecode = null) {
		foreach ($this->getMessages() as $message) {
			if ($message->type != $type)
				continue;
			if($subtype != null && $message->subtype != $subtype)
				continue;
			if($languagecode != null && $message->languagecode != $languagecode)
				continue;
			return true;
		}
		
		return false;
	}
	
	function getFirstMessageOfType($type) {
		foreach ($this->getMessages() as $message) {
			if ($message->type == $type)
				return $message;
		}
		return null;
	}

	function getGlobalPreferredGender() {
		if (!$phonemessage = $this->getFirstMessageOfType('phone'))
			return $default;
		$gender = QuickQuery('select v.gender from messagepart mp join ttsvoice v on mp.voiceid=v.id where mp.messageid=? and mp.voiceid is not null order by sequence limit 1', false, array($phonemessage->id));
		if ($gender)
			return $gender;
		else
			return $default;
	}

	function getGlobalEmailAttachments() {
		if (!$emailmessage = $this->getFirstMessageOfType('email'))
			return array();
		return DBFindMany('MessageAttachment', 'from messageattachment where not deleted and messageid=?', false, array($emailmessage->id));
	}
	
	function getGlobalEmailHeaders($default) {
		if (!$emailmessage = $this->getFirstMessageOfType('email'))
			return $default;
		$emailmessage->readHeaders();
		
		return array(
			'subject' => $emailmessage->subject,
			'fromname' => $emailmessage->fromname,
			'fromemail' => $emailmessage->fromemail
		);
	}
	
	function getMessageText($type, $subtype, $languagecode, $autotranslate) {
		$message = $this->getMessage($type, $subtype, $languagecode, $autotranslate);
		if (!$message)
			return '';
		
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=?', false, array($message->id));
		
		return $message->format($parts);
	}
	
	function getMessage($type, $subtype, $languagecode, $autotranslate) {
		static $messagecache = array();
		
		$key = $type . $subtype . $languagecode . $autotranslate;
		
		if (!isset($messagecache[$key])) {
			if (!$message = DBFind('Message', 'from message where not deleted and type=? and subtype=? and languagecode=? and messagegroupid=? and autotranslate=?', false, array($type, $subtype, $languagecode, $this->id, $autotranslate)))
				return null;
			
			$messagecache[$key] = $message;
		} else {
			$message = $messagecache[$key];
		}
	
		return $message;
	}
}

?>
