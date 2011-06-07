<?

class Message extends DBMappedObject {
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
	var $deleted = 0;

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

	function Message ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("userid", "messagegroupid", "name", "languagecode", "description", "type", "subtype", "data", "deleted","modifydate", "autotranslate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function readHeaders () {
		//parse_str($this->data, $data);
		$data = sane_parsestr($this->data);
		foreach($data as $key => $value)
		{
			if ($key == 'overrideplaintext')
				$value = $value + 0;
				
			$this->$key = $value;
		}
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
		}
	}
	
	// If $forcedeleted is true, sets $newmessage->deleted = 1; otherwise, does not explicitly set $newmessage->deleted.
	function copy($messagegroupid = null, $forcedeleted = false) {
		//copy the messages
		$newmessage = new Message($this->id);
		$newmessage->id = null;
		if (!is_null($messagegroupid))
			$newmessage->messagegroupid = $messagegroupid;
		if ($forcedeleted)
			$newmessage->deleted = 1;
		$newmessage->create();

		// copy the parts
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$this->id");
		foreach ($parts as $part) {
			$part->id = null;
			$part->messageid = $newmessage->id;
			$part->create();
		}
		// copy the attachments
		QuickUpdate("insert into messageattachment (messageid,contentid,filename,size,deleted) " .
		"select $newmessage->id, ma.contentid, ma.filename, ma.size, 1 as deleted " .
		"from messageattachment ma where ma.messageid=$this->id");
		
		return $newmessage;
	}

	function createMessageAttachments($emailattachments) {
		if ($this->type != 'email')
			return null;
			
		$messageattachments = array();
		
		foreach($emailattachments as $contentid => $attachment) {
			$msgattachment = new MessageAttachment();
			$msgattachment->messageid = $this->id;
			$msgattachment->contentid = $contentid;
			$msgattachment->filename = $attachment['name'];
			$msgattachment->size = $attachment['size'];
			
			$msgattachment->create();
			
			$messageattachments[] = $msgattachment;
		}
		
		return $messageattachments;
	}
	
	// Updates the first message part with a voiceid, keeping the language the same, only changing the gender.
	function updatePreferredVoice($preferredgender) {
		if ($this->type != 'phone' || !$voicemessagepart = DBFind('MessagePart', 'from messagepart where voiceid is not null and messageid=? order by sequence', false, array($this->id)))
			return;
		$languagecode = QuickQuery('select languagecode from ttsvoice where id=?', false, array($voicemessagepart->voiceid));
		$voicemessagepart->voiceid = Voice::getPreferredVoice($languagecode, $preferredgender);
		$voicemessagepart->update();
	}
	
	// This will delete any existing message parts and recreate new ones.
	// There are 2 usage patterns: either $body is null, or $parts is null.
	function recreateParts($body, $parts, $preferredgender, $audiofileids = null) {
		global $USER;
		
		if (!is_null($this->id))
			QuickUpdate("delete from messagepart where messageid=?", false, array($this->id));
		else
			$this->update();
			
		$voiceid = $preferredgender ? Voice::getPreferredVoice($this->languagecode, $preferredgender) : null;
		
		if (is_string($body)) {
			if ($this->type == 'sms') {
				$part = new MessagePart();
				$part->messageid = $this->id;
				$part->txt = $body;
				$part->type = "T";
				$part->sequence = 0;
				$part->create();
			} else {
				$errors = array();
				// VoiceID Sanity Check
				if ($this->type == 'phone' && $voiceid == null) {
					error_log("ERROR: Fund phone message with voiceid null");
					if ($preferredgender == null) 
						$preferredgender = "female";
					$voiceid = Voice::getPreferredVoice($this->languagecode, $preferredgender);
				}
				$parts = $this->parse($body, $errors, $voiceid, $audiofileids);
			}
		}
		
		if (is_array($parts)) {
			foreach ($parts as $part) {
				// VoiceID Sanity Check
				if (($part->type == "T" || $part->type == "V") && $this->type == 'phone' && $part->voiceid == null) {
					error_log("ERROR: Fund phone message part with voiceid null");
					if ($preferredgender == null) 
						$preferredgender = "female";
					$voiceid = Voice::getPreferredVoice($this->languagecode, $preferredgender);
				}
				$part->messageid = $this->id;
				$part->create();
			}
		}
		
		return $parts;
	}
	
	static function parse ($data, &$errors = NULL, $defaultvoiceid=null, $audiofileids = null) {
		global $USER;

		if ($errors == NULL)
			$errors = array();

		$txtpart = "";
		$parts = array();
		$partcount = 0;
		$defaultvoice = new Voice($defaultvoiceid);
		$currvoiceid = $defaultvoiceid;
		while (true) {
			//get dist to next field and type of field
			$pos_f = strpos($data,"<<");
			$pos_a = strpos($data,"{{");
			$pos_l = strpos($data,"[[");
			
			// get imageupload tags
			$matches = array();
			$uploadimageurl = "";
			if (eregi("(\<img src\=\"[^\=]*viewimage\.php\?id\=)", $data, $matches) !== false) {
				// we only care about the first match, index 1 has this info
				$uploadimageurl = $matches[1];
				$pos_i = strpos($data, $uploadimageurl);
			} else {
				$pos_i = false;
			}
			
			$poses = array();
			if($pos_f !== false)
				$poses[] = $pos_f;
			if($pos_a !== false)
				$poses[] = $pos_a;
			if($pos_l !== false)
				$poses[] = $pos_l;
			if($pos_i !== false)
				$poses[] = $pos_i;

			if(!count($poses))
				break;

			$pos = min($poses);
			if($pos !== false){
				if($pos === $pos_f)
					$type = "V";
				if($pos === $pos_a)
					$type = "A";
				if($pos === $pos_l)
					$type = "newlang";
				if($pos === $pos_i)
					$type = "I";
			}

			//make a text part up to the pos of the field
			$txt = substr($data,0,$pos);
			if (strlen($txt) > 0) {
				$part = new MessagePart();
				$part->type="T";
				$part->txt = $txt;
				//$part->messageid = $this->id; // assign ID afterwards so ID is set
				$part->sequence = $partcount++;
				if($currvoiceid !== null)
					$part->voiceid = $currvoiceid;
				$parts[] = $part;
			}

			if ($type == 'I') {
				// find the start of the id
				$pos += mb_strlen($uploadimageurl);
			} else
				$pos += 2; // pass over the begintoken

			switch($type){
				case "A":
					$endtoken = "}}";
					break;
				case "V":
					$endtoken = ">>";
					break;
				case "newlang":
					$endtoken = "]]";
					break;
				case "I":
					$endtoken = '">';
			}
			//$endtoken = ($type == "A") ? "}}" : ">>";
			$length = @strpos($data,$endtoken,$pos+1); // assume at least one char for audio/field name

			if ($length === false) {
				$errors[] = "Can't find end of field, was expecting '$endtoken'";
				$length = 0;
			} else {
				$length -= $pos;

				$token  = substr($data,$pos,$length);
				$part = new MessagePart();
				$part->type = $type;
				//$part->messageid = $this->id; // assign ID afterwards so ID is set


				switch ($type) {
					case "A":
						$part->sequence = $partcount++;
						if (is_array($audiofileids)) {
							if (count($audiofileids) > 0) {
								$query = "select id from audiofile where userid=? and name=? and deleted = 0 and id in (".implode(",", $audiofileids).")";
								$audioid = QuickQuery($query, false, array($USER->id,$token));
							} else {
								$audioid = false;
							}
						} else {
							$query = "select id from audiofile where userid=? and name=? and deleted = 0";
							$audioid = QuickQuery($query, false, array($USER->id, $token));
						}
						
						if ($audioid !== false) {
							//find an audiofile with this name
							$part->audiofileid = $audioid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find audio file named '$token'";
						}

						break;
					case "V":
						$part->sequence = $partcount++;
						if (strpos($token,":") !== false) {
							list($fieldname,$defvalue) = explode(":",$token);
						} else {
							$fieldname = $token;
							$defvalue = "";
						}
						$query = "select fieldnum from fieldmap where name=? and fieldnum like 'f%'";

						$fieldnum = QuickQuery($query, false, array($fieldname));

						if ($fieldnum !== false) {
							$part->fieldnum = $fieldnum;
							$part->defaultvalue = $defvalue;
							if($currvoiceid !== null)
								$part->voiceid = $currvoiceid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find field named '$fieldname'";
						}
						break;
					case "newlang":
						if(isset($defaultvoice->gender)){
							$currvoiceid = QuickQuery("select id from ttsvoice where language = ? and gender = ?",
								false, array(strtolower($token), $defaultvoice->gender));
							if($currvoiceid == false){
								$currvoiceid = QuickQuery("select id from ttsvoice where language = ? and gender = ?",false, array(strtolower($token),
									($defaultvoice->gender=="female"?"male":"female")));
							}
							if($currvoiceid == false){
								$errors[] = "Can't find that language: " . $token . ".";
								$currvoiceid = null;
							}
						}
						break;
					case "I":
						$part->sequence = $partcount++;
						$query = "select id from content where id=?";

						$contentid = QuickQuery($query, false, array($token));
						if ($contentid !== false) {
							$part->imagecontentid = $contentid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find content with id '$token'";
						}
						break;
				}
			}
			//skip the end if we found it
			if ($length)
				$skip = $pos + $length + strlen($endtoken);
			else
				$skip = $pos + $length ;

			$data = substr($data,$skip );
		}

		//get trailing txt part;
		if (strlen($data) > 0) {
			$part = new MessagePart();
			$part->type="T";
			$part->txt = $data;

			$part->sequence = $partcount++;
			if($currvoiceid !== null)
				$part->voiceid = $currvoiceid;

			$parts[] = $part;
		}

		return $parts;
	}
	
	// used to prepare parts to display in editor (use renderXXX methods for preview)
	static function format ($parts) {
		$map = FieldMap::getMapNames();
		$data = "";
		$voices = DBFindMany("Voice", "from ttsvoice");
		$currvoiceid=null;
		foreach ($parts as $part) {
			if($currvoiceid == null){
				$currvoiceid = $part->voiceid;
			} else if($part->voiceid && $part->voiceid != $currvoiceid){
				$voicestr = "[[" . ucfirst($voices[$part->voiceid]->language) . "]]";
				$data .= $voicestr;
				$currvoiceid = $part->voiceid;
			}
			
			$partstr = '';
			switch ($part->type) {
			case 'A':
				$part->audiofile = new AudioFile($part->audiofileid);
				$partstr .= "{{" . $part->audiofile->name . "}}";
				break;
			case 'T':
				$partstr .= $part->txt;
				break;
			case 'V':
				$partstr .= "<<" . $map[$part->fieldnum];
				if ($part->defaultvalue !== null && strlen($part->defaultvalue) > 0)
					$partstr .= ":" . $part->defaultvalue;
				$partstr .= ">>";
				break;
			case 'I':
				$partstr .= '<img src="viewimage.php?id=' . $part->imagecontentid . '">';
				permitContent($part->imagecontentid);
				break;
			}
			
			if ($partstr != '')
				$data .= $partstr;
		}

		return $data;
	}

	// preview sms message
	static function renderSmsParts($parts) {
		$message = "";
		// only one part expected in sms message
		$firstpart = reset($parts);
		if ($firstpart && $firstpart->type == 'T')
			$message = $firstpart->txt;
	
		return nl2br(escapehtml($message));
	}
	
	// preview email message, call appserver to use common rendering logic to support custom templates
	function renderEmailWithTemplate($jobpriority = 3) {
		$view = messagePreviewForPriority($this->id, $jobpriority);
		foreach ($view->emailcontentids as $contentid) {
			permitContent($contentid);
		}	
		return $view->emailbody;
	}
	
	// preview plain email message
	static function renderEmailPlainParts($parts, $fields = array()) {
		$message = "";	
		foreach ($parts as $part) {
			switch ($part->type) {
			case 'T':
				$message .= $part->txt;
				break;
			case 'V':
				if (isset($fields[$part->fieldnum]))
					$d = $fields[$part->fieldnum];
				else
					$d = $part->defaultvalue;
				$message .= $d;			
				break;
			}
		}
	
		return nl2br(escapehtml($message));
	}
	
	// preview html email message
	static function renderEmailHtmlParts($parts, $fields = array()) {
		$message = "";	
		foreach ($parts as $part) {
			switch ($part->type) {
			case 'T':
				$message .= $part->txt;
				break;
			case 'V':
				if (isset($fields[$part->fieldnum]))
					$d = $fields[$part->fieldnum];
				else
					$d = $part->defaultvalue;
				$message .= $d;			
				break;
			case 'I':
				$message .= '<img src="viewimage.php?id=' . $part->imagecontentid . '">';
				permitContent($part->imagecontentid);
				break;
			}
		}
		$message = str_replace('<<', '&lt;&lt;', $message);
		$message = str_replace('>>', '&gt;&gt;', $message);
		return $message;
	}

	// preview phone message, returns array
	static function renderPhoneParts($parts, $fields) {
		// -- digest the message --
		$renderedparts = array();
		$curpart = 0;

		foreach ($parts as $part) {
			switch ($part->type) {
			case "A":
				$af = new AudioFile($part->audiofileid);
				$renderedparts[++$curpart] = array("a",$af->contentid);
				break;
			case "T":
				$renderedparts[++$curpart] = array("t",$part->txt,$part->voiceid);
				break;
			case "V":
				if (!isset($fields[$part->fieldnum]) || !($value = $fields[$part->fieldnum])) {
					$value = $part->defaultvalue;
				}
				$renderedparts[++$curpart] = array("t",$value,$part->voiceid);
				break;
			// NOTE: Case 'I' is skipped because images cannot be played through audio.
			}
		}
		return $renderedparts;
	}

	static function playAudio($id, $fields,$audioformat = "wav",$intro = "") {
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($id));
		$renderedparts = Message::renderPhoneParts($parts, $fields);
		Message::playParts($renderedparts,$audioformat,$intro);
	}
	static function playParts($renderedparts, $audioformat = "wav",$intro = "") {

		$voices = DBFindMany("Voice","from ttsvoice");

		// -- get the wav files --
		$wavfiles = array();

		foreach ($renderedparts as $part) {
			if ($part[0] == "a") {
				list($contenttype,$data) = contentGet($part[1]);
				$wavfiles[] = writeWav($data);
			} else if ($part[0] == "t") {
				$voice = $voices[$part[2]];
				list($contenttype,$data) = renderTts($part[1],$voice->language,$voice->gender);
				$wavfiles[] = writeWav($data);
			}
		}
		if($intro && file_exists($intro)){
			$intro = $intro?('"' . $intro . '" "media/2secondsilence.wav" '):'';
		}

		//finally, merge the wav files
		$outname = secure_tmpname("preview",".wav");

		$messageparts = empty($wavfiles)?'':'"' . implode('" "',$wavfiles) . '" ';
		$cmd = 'sox ' . $intro . $messageparts . '"' . $outname . '"';
		$result = exec($cmd, $res1, $res2);

		foreach ($wavfiles as $file)
			@unlink($file);

		if (!$res2 && file_exists($outname)) {
			if($audioformat == "mp3") {
				$outnamemp3 = secure_tmpname("preview",".mp3");
				$cmd = 'lame -S -b24 "' . $outname . '" "' . $outnamemp3 . '"';
				$result = exec($cmd, $res1, $res2);
				if (!$res2 && file_exists($outname)) {
					$data = file_get_contents ($outnamemp3); //readfile seems to cause problems
					header("HTTP/1.0 200 OK");
					if (isset($_GET['download']))
						header('Content-type: application/x-octet-stream');
					else {
						header("Content-Type: audio/mpeg");
					}
					header("Content-disposition: attachment; filename=message.$audioformat");
					header('Pragma: private');
					header('Cache-control: private, must-revalidate');
					header("Content-Length: " . strlen($data));
					header("Connection: close");
					echo $data;
				} else {
					echo _L("An error occurred trying to generate the preview file. Please try again.");
				}
				@unlink($outnamemp3);
			} else {
				$data = file_get_contents ($outname); // readfile seems to cause problems
				header("HTTP/1.0 200 OK");
				if (isset($_GET['download']))
					header('Content-type: application/x-octet-stream');
				else {
					header("Content-Type: audio/wav");
				}
				header("Content-disposition: attachment; filename=message.$audioformat");
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				header("Content-Length: " . strlen($data));
				header("Connection: close");
				echo $data;
			}
		} else {
			echo _L("An error occurred trying to generate the preview file. Please try again.");
		}

		@unlink($outname);
	}

	// The only reliable way to check the message length is to render it. Return negative value on error.
	static function getAudioLength($id, $fields) {
		$size = -1;
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($id));
		$renderedparts = Message::renderPhoneParts($parts, $fields);
		$voices = DBFindMany("Voice","from ttsvoice");

		// -- get the wav files --
		$wavfiles = array();

		foreach ($renderedparts as $part) {
			if ($part[0] == "a") {
				list($contenttype,$data) = contentGet($part[1]);
				$wavfiles[] = writeWav($data);
			} else if ($part[0] == "t") {
				$voice = $voices[$part[2]];
				list($contenttype,$data) = renderTts($part[1],$voice->language,$voice->gender);
				$wavfiles[] = writeWav($data);
			}
		}
		//finally, merge the wav files
		$outname = secure_tmpname("preview",".wav");

		$messageparts = empty($wavfiles)?'':'"' . implode('" "',$wavfiles) . '" ';
		$cmd = 'sox ' . $messageparts . '"' . $outname . '"';
		$result = exec($cmd, $res1, $res2);

		foreach ($wavfiles as $file)
			@unlink($file);

		if (!$res2 && file_exists($outname)) {
			$data = file_get_contents ($outname); // readfile seems to cause problems
			$size = strlen($data);
		}

		@unlink($outname);
		return $size;
	}
}

?>
