<?
class MessageGroup extends DBMappedObject {
	var $originalmessagegroupid;
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
			"originalmessagegroupid",
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
			$this->messages = DBFindMany("Message", "from message where messagegroupid=? and deleted=0 and autotranslate != 'source' order by id", false, array($this->id));
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
	
	// array of language codes for messages of the specified type (index and value both contain the languagecode)
	function getMessageLanguageCodesOfType($type) {
		$availableMessageLanguages = array();
		foreach ($this->getMessages() as $message) {
			if ($message->type == $type)
				$availableMessageLanguages[$message->languagecode] = $message->languagecode;
			// keyed with languagecode to avoid duplicates, in the case of plain and html email
		}
		return $availableMessageLanguages;
	}

	function getGlobalEmailAttachments($isDeletedOk = false) {
		if (!$emailmessage = $this->getFirstMessageOfType('email'))
			return array();
		if ($isDeletedOk)
			$appendDeleted = "";
		else
			$appendDeleted = " and not deleted";
		return DBFindMany('MessageAttachment', 'from messageattachment where messageid = ?' . $appendDeleted, false, array($emailmessage->id));
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
	
	function getMessage($type, $subtype, $languagecode, $autotranslate = false) {
		foreach ($this->getMessages() as $message) {
			if ($message->type == $type &&
				$message->subtype == $subtype &&
				$message->languagecode == $languagecode &&
				(!$autotranslate || $message->autotranslate == $autotranslate))
						return $message;
		}
		
		return null;
	}
	
	// get message, but not 'source' for any translations, there should only be one other
	function getMessageNotSource($type, $subtype, $languagecode) {
		foreach ($this->getMessages() as $message) {
			if ($message->type == $type &&
				$message->subtype == $subtype &&
				$message->languagecode == $languagecode &&
				($message->autotranslate != 'source'))
						return $message;
		}
		
		return null;
	}
	
	// helper to find a message for the language (not 'source') or default, used to playback message for a person via dmapi
	function getMessageOrDefault($type, $subtype, $languagecode) {
		$message = $this->getMessageNotSource($type, $subtype, $languagecode);
		if ($message == null)
			$message = $this->getMessageNotSource($type, $subtype, $this->defaultlanguagecode);
		return $message;
	}
		
	static function getSummary($messagegroupid) {
		global $USER;
		
		static $summaries = array();
		
		if (!isset($summaries[$messagegroupid]))
			$summaries[$messagegroupid] = QuickQueryMultiRow("select distinct type,subtype,languagecode,data from message where userid=? and messagegroupid=? order by type,subtype,languagecode", true, false, array($USER->id, $messagegroupid));
		
		return $summaries[$messagegroupid];
	}

	// Returns an array of audiofile ids for ones assigned
	// to this messagegroup plus ones that are referenced in message parts
	// for messages of this messagegroup.
	static function getReferencedAudioFileIDs($messagegroupid) {
		global $USER;
		
		static $audiofileids = array();
		
		if (!isset($audiofileids[$messagegroupid])) {
			// Merge with any audio files that are referenced in message parts.
			$audiofileids[$messagegroupid] = QuickQueryList('select id from audiofile where userid=? and messagegroupid=? and not deleted', false, false, array($USER->id, $messagegroupid));
			$audiofileids[$messagegroupid] = array_unique(array_merge($audiofileids[$messagegroupid],
				QuickQueryList('
					select distinct mp.audiofileid
					from messagepart mp inner join message m
						on (mp.messageid = m.id)
					where m.messagegroupid = ? and m.userid = ? and mp.audiofileid is not NULL',
					false, false, array($messagegroupid, $USER->id)
				))
			);
		}
		
		return $audiofileids[$messagegroupid];
	}
	
	// checks for attached auto translated messages that need re-translation, then re-translates them
	function reTranslate() {
		// get all the attached autotranslated messages that have mod dates more than six days ago
		$retranslatemessages = DBFindMany("Message", "from message where messagegroupid = ? and autotranslate = 'translated' and date_add(modifydate, interval 7 day) < now()", false, array($this->id));
		
		// if there are any messages that need retranslation
		if ($retranslatemessages) {
			
			// get all the langcodes used in the messages to retranslate
			$langcodes = array();
			foreach ($retranslatemessages as $message)
				$langcodes[] = $message->languagecode;
			
			// do a query to get the voiceid to gender map for these codes
			$voicemap = QuickQueryList("select id, gender from ttsvoice where languagecode in ('" . implode("','", $langcodes) . "')", true, false, array());
			
			
			// get the source message parts for each translated message
			foreach ($retranslatemessages as $message) {
				
				// look up all the message parts for the source message
				$sourceparts = DBFindMany("MessagePart", "
					from messagepart mp
						inner join message m on
							(mp.messageid = m.id)
					where m.messagegroupid = ? and m.autotranslate = 'source' and m.languagecode = ? and m.type = ? and m.subtype = ?
					order by mp.sequence", "mp", array($message->messagegroupid, $message->languagecode, $message->type, $message->subtype));
				
				if ($sourceparts) {
					// if parts returned, format them into a body string
					$sourcebody = Message::format($sourceparts);
					
					// try to figure out what the prefered gender was
					$firstpart = array_pop($sourceparts);
					$gender = isset($voicemap[$firstpart->voiceid])?$voicemap[$firstpart->voiceid]:'female';
					
					// get translated text from google
					$translated = translate_fromenglish(makeTranslatableString($sourcebody), array($message->languagecode));
					
					// refresh the message with the new data
					Query("BEGIN");
					$message->recreateParts($translated->translatedText, null, $gender);
					$message->modifydate = date("Y-m-d H:i:s");
					$message->update();
					Query("COMMIT");
				}
			}
		}
	}
}

?>
