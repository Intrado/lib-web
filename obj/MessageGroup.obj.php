<?php
class MessageGroup extends DBMappedObject {
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
	
	function MessageGroup ($id = NULL) {
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
	
	function readHeaders () {
		$data = sane_parsestr($this->data);
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}

	function stuffHeaders () {
		$this->data = "preferredgender=" . urlencode($this->preferredgender);
	}
	
	function getMessages() {
		if (!$this->id)
			return array();
		
		if ($this->messages === false)  {
			$this->messages = DBFindMany("Message", "from message where messagegroupid=? order by id", false, array($this->id));
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
	
	function hasMessage($type, $subtype = null, $languagecode = null, $autotranslate = null) {
		foreach ($this->getMessages() as $message) {
			if ($message->type != $type)
				continue;
			if($subtype != null && $message->subtype != $subtype)
				continue;
			if($languagecode != null && $message->languagecode != $languagecode)
				continue;
			if ($autotranslate != null && $message->autotranslate != $autotranslate)
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
		if (!$message = $this->getMessage($type, $subtype, $languagecode, $autotranslate))
			return '';
		
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=?', false, array($message->id));
		
		return $message->format($parts);
	}
	
	function getMessage($type, $subtype, $languagecode, $autotranslate) {
		foreach ($this->getMessages() as $message) {
			if ($message->type == $type &&
				$message->subtype == $subtype &&
				$message->languagecode == $languagecode &&
				$message->autotranslate == $autotranslate)
				return $message;
		}
		
		return null;
	}
	
	static function getSummary($messagegroupid) {
		global $USER;
		
		static $summaries = array();
		
		if (!isset($summaries[$messagegroupid]))
			$summaries[$messagegroupid] = QuickQueryMultiRow("select distinct type,subtype,languagecode from message where userid=? and messagegroupid=? order by type,subtype,languagecode", true, false, array($USER->id, $messagegroupid));
		
		return $summaries[$messagegroupid];
	}
}

?>
