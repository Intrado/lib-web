<?php
class MessageGroup extends DBMappedObject {
	var $userid;
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
	
	function hasMessage($type, $subtype = null, $languagecode = null) {
		global $USER;
		
		$query = 'select count(id) from message where userid=? and messagegroupid=? and not deleted and type=? ';
		$args = array($USER->id, $this->id, $type);
		
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
		return DBFind('Message2', 'from message where not deleted and type=? and messagegroupid=?', false, array($type, $this->id));
	}

	function getGlobalPreferredGender($default) {
		if (!$phonemessage = $this->getOneEnabledMessage('phone'))
			return $default;
		return QuickQuery('select v.gender from messagepart mp join ttsvoice v on mp.voiceid=v.id where mp.messageid=? limit 1', false, array($phonemessage->id));
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
		if (!$message = DBFind('Message2', 'from message where not deleted and type=? and subtype=? and languagecode=? and messagegroupid=? and autotranslate=?', false, array($type, $subtype, $languagecode, $this->id, $autotranslate)))
			return '';
		
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=?', false, array($message->id));
		
		return $message->format($parts);
	}
}

?>
