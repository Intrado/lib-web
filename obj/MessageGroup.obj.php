<?php
class MessageGroup extends DBMappedObject {
	var $userid;
	var $defaultlanguagecode;
	var $name;
	var $description;
	var $modified;
	var $lastused;
	var $permanent = 0;
	var $deleted = 0;

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
	
	// Returns true/false; true if the user has a message with its defaultlanguagecode.
	function hasDefaultMessage($type, $subtype) {
		$query = 'select count(id) from message where not deleted and messagegroupid=? and type=? and subtype=? and languagecode=?';
		return QuickQuery($query, false, array($this->id, $type, $subtype, $this->defaultlanguagecode)) ? true : false;
	}
	
	function hasMessage($type, $subtype = null, $languagecode = null) {
		$query = 'select count(id) from message where not deleted and messagegroupid=? and type=? ';
		$args = array($this->id, $type);
		
		if (is_string($subtype)) {
			$query .= ' and subtype=? ';
			$args[] = $subtype;
		}
		if (is_string($languagecode)) {
			$query .= ' and languagecode=? ';
			$args[] = $languagecode;
		}
		
		return QuickQuery($query, false, $args) ? true : false;
	}
	
	function getOneEnabledMessage($type) {
		return DBFind('Message', 'from message where not deleted and type=? and messagegroupid=?', false, array($type, $this->id));
	}

	function getGlobalPreferredGender($default) {
		if (!$phonemessage = $this->getOneEnabledMessage('phone'))
			return $default;
		return QuickQuery('select v.gender from messagepart mp join ttsvoice v on mp.voiceid=v.id where mp.messageid=? and mp.voiceid is not null order by sequence limit 1', false, array($phonemessage->id));
	}

	function getGlobalEmailAttachments() {
		if (!$emailmessage = $this->getOneEnabledMessage('email'))
			return array();
		return DBFindMany('MessageAttachment', 'from messageattachment where not deleted and messageid=?', false, array($emailmessage->id));
	}
	
	function getGlobalEmailHeaders($default) {
		if (!$emailmessage = $this->getOneEnabledMessage('email'))
			return $default;
		$emailmessage->readHeaders();
		
		return array(
			'subject' => $emailmessage->subject,
			'fromname' => $emailmessage->fromname,
			'fromemail' => $emailmessage->fromemail
		);
	}
	
	function getMessageText($type, $subtype, $languagecode, $autotranslate) {
		if (!$message = DBFind('Message', 'from message where not deleted and type=? and subtype=? and languagecode=? and messagegroupid=? and autotranslate=?', false, array($type, $subtype, $languagecode, $this->id, $autotranslate)))
			return '';
		
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=?', false, array($message->id));
		
		return $message->format($parts);
	}
}

?>
