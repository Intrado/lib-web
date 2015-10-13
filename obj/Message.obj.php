<?

class Message extends DBMappedObject {
	var $userid;
	var $messagegroupid;
	var $name;
	var $description;
	var $data = ""; // Serialized header data.
	var $type; //enum('phone', 'email', 'print', 'sms', 'post')
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
	var $fromstationery = 0;
	
	function Message ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("userid", "messagegroupid", "name", "languagecode", "description", "type", "subtype", "data","modifydate", "autotranslate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function readHeaders () {
		//parse_str($this->data, $data);
		$data = sane_parsestr($this->data);
		foreach($data as $key => $value)
		{
			if ($key == 'overrideplaintext' || $key == 'fromstationery')
				$value = $value + 0;
				
			$this->$key = $value;
		}
	}

	function stuffHeaders () {
		if($this->type == 'email') {
			$this->data = 'subject=' . urlencode($this->subject) .
				'&fromname=' .  urlencode($this->fromname) .
				'&fromemail=' . urlencode($this->fromemail) .
				'&fromstationery=' . urlencode($this->fromstationery);
				
			if ($this->subtype == 'plain')
				$this->data .= '&overrideplaintext=' . urlencode($this->overrideplaintext);
		} elseif ($this->type == 'print') {
			$this->data = 'header1=' . urlencode($this->header1) . '&header2=' .  urlencode($this->header2) . '&header3=' . urlencode($this->header3) . '&fromaddress=' . urlencode($this->fromaddress);
		} elseif ($this->type == 'post' && $this->subtype == 'feed') {
			$this->data = 'subject=' . urlencode($this->subject);
		}
	}
	
	function copy($messagegroupid = null) {
		//copy the messages
		$newmessage = new Message($this->id);
		$newmessage->id = null;
		if (!is_null($messagegroupid))
			$newmessage->messagegroupid = $messagegroupid;
		$newmessage->create();
		
		// need a map of original attachmentID to copied attachmentID to set on the copied message parts of type MAL
		$attachmentIds = array(); // index is the original messageattachment.id the value is the copied id
		// fetch attachments and split by type so we can lookup the darn messageattachment ids (not the contentmessageattachments)
		$origMessageAttachments = $this->getMessageAttachments();
		$origContentMessageAttachmentLookup = array();
		$origBurstMessageAttachmentLookup = array();
		foreach ($origMessageAttachments as $ma) {
			if ($ma->type == 'content') {
				$origContentMessageAttachmentLookup[$ma->contentattachmentid] = $ma;
			} else if ($ma->type == 'burst') {
				$origBurstMessageAttachmentLookup[$ma->burstattachmentid] = $ma;
			}
		}

		// copy the attachments
		$contentAttachments = $this->getContentAttachments(false);
		foreach ($contentAttachments as $contentAttachment) {
			$origMessageAttachment = $origContentMessageAttachmentLookup[$contentAttachment->id];
			// call create to generate a new attachment record which is a copy of the existing one
			$contentAttachment->create();
			$messageAttachment = new MessageAttachment();
			$messageAttachment->messageid = $newmessage->id;
			$messageAttachment->type = 'content';
			$messageAttachment->contentattachmentid = $contentAttachment->id;
			$messageAttachment->displayName = $origMessageAttachment->displayName;
			$messageAttachment->create();
			$attachmentIds[$origMessageAttachment->id] = $messageAttachment->id;
		}
		$burstAttachments = $this->getBurstAttachments();
		foreach ($burstAttachments as $burstAttachment) {
			$origMessageAttachment = $origBurstMessageAttachmentLookup[$burstAttachment->id];
			// call create to generate a new attachment record which is a copy of the existing one
			$burstAttachment->create();
			$messageAttachment = new MessageAttachment();
			$messageAttachment->messageid = $newmessage->id;
			$messageAttachment->type = 'burst';
			$messageAttachment->burstattachmentid = $burstAttachment->id;
			$messageAttachment->create();
			$attachmentIds[$origMessageAttachment->id] = $messageAttachment->id;
		}
		
		// copy the parts
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$this->id");
		foreach ($parts as $part) {
			$part->id = null;
			$part->messageid = $newmessage->id;
			if ($part->type == 'MAL') {
				$part->messageattachmentid = $attachmentIds[$part->messageattachmentid]; // copied part must reference the copied messageattachment
			}
			$part->create();
		}
		
		return $newmessage;
	}

	/**
	 * @return MessageAttachment[]|bool
	 */
	function getMessageAttachments() {
		return DBFindMany("MessageAttachment", "from messageattachment where messageid = ?", null, array($this->id));
	}

	/**
	 * @param $filterMessageLinks filter out message attachments
	 * @return ContentAttachment[]|bool
	 */
	function getContentAttachments($filterMessageLinks = true) {
		$sql = "from messageattachment ma
				inner join contentattachment ca on (ma.contentattachmentid = ca.id)";
		$where = " where ma.messageid = ? and ma.type = 'content'";

		if ($filterMessageLinks) {
			$sql .= " left join messagepart mp on (ma.messageid = mp.messageid and ma.id = mp.messageattachmentid)";
			$where .= " and mp.type is NULL";
		}
		$sql .= $where;
		return DBFindMany("ContentAttachment", $sql, "ca", array($this->id));
	}

	/**
	 * @return BurstAttachment[]|bool
	 */
	function getBurstAttachments() {
		return DBFindMany("BurstAttachment", "from messageattachment ma
				inner join burstattachment ba on (ma.burstattachmentid = ba.id) where ma.messageid = ? and ma.type = 'burst'",
			"ba", array($this->id));
	}

	/**
	 * @param array() $emailattachments  looks like array(<contentid>: array("name": <filename>, "size": <file size>))
	 */
	function createContentAttachments($emailattachments) {
		if ($this->type != 'email' && $this->type != 'post')
			return;

		foreach ($emailattachments as $cid => $details) {
			$contentAttachment = new ContentAttachment();
			$contentAttachment->contentid = $cid;
			$contentAttachment->filename = $details['name'];
			$contentAttachment->size = $details['size'];
			$contentAttachment->create();

			$msgattachment = new MessageAttachment();
			$msgattachment->messageid = $this->id;
			$msgattachment->type = 'content';
			$msgattachment->contentattachmentid = $contentAttachment->id;
			$msgattachment->create();
		}
	}

	/**
	 * Replaces existing content based attachments with those passed into this method
	 * NOTE: removes only message attachments that are not attachment link.
	 * @param array() $attachments looks like array(<contentid>: array("name": <filename>, "size": <file size>))
	 */
	function replaceContentAttachments($attachments) {
		if ($this->type != 'email' && $this->type != 'post')
			return;

		$messageAttachments = $this->getMessageAttachments();
		$contentAttachments = $this->getContentAttachments();
		// remove existing attachments
		foreach ($contentAttachments as $id => $contentAttachment) {
			if (isset($attachments[$contentAttachment->contentid])) {
				// unset from the attachments array, it's already in the DB
				unset($attachments[$contentAttachment->contentid]);
			} else {
				// no longer a desired attachment, remove it and it's parent
				foreach ($messageAttachments as $ma_id => $messageAttachment){
					if ($messageAttachment->contentattachmentid == $id)
						$messageAttachment->destroy();
				}
				$contentAttachment->destroy();
			}
		}

		// create new attachments
		$this->createContentAttachments($attachments);
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
					error_log("ERROR: found phone message with voiceid null");
					if ($preferredgender == null) 
						$preferredgender = "female";
					$voiceid = Voice::getPreferredVoice($this->languagecode, $preferredgender);
				}
				$parts = $this->parse($body, $errors, $voiceid, $audiofileids, true);
			}
		}

		//remove existing hosted attachments that are no longer needed
		if (!is_null($this->id))
			QuickUpdate("delete from messageattachment where messageid=? and displayName is not null", false, array($this->id));

		if (is_array($parts)) {
			foreach ($parts as $part) {
				// VoiceID Sanity Check
				if (($part->type == "T" || $part->type == "V") && $this->type == 'phone' && $part->voiceid == null) {
					error_log("ERROR: found phone message part with voiceid null");
					if ($preferredgender == null) 
						$preferredgender = "female";
					$voiceid = Voice::getPreferredVoice($this->languagecode, $preferredgender);
					$part->voiceid = $voiceid;
				}
				//Hosted Attachment Links
				if($part->type=='HMAL'){
					$msgattachment = new MessageAttachment();
					$msgattachment->messageid = $this->id;
					$msgattachment->type = 'content';
					$msgattachment->contentattachmentid = $part->context["attachmentId"];
					$msgattachment->displayName = $part->context["displayName"];
					$msgattachment->create();

					$part->type = "MAL";
					$part->messageattachmentid = $msgattachment->id;
				}
				$part->messageid = $this->id;
				$part->create();
			}
		}
		
		return $parts;
	}
	
	static function parse ($data, &$errors = NULL, $defaultvoiceid=null, $audiofileids = null, $enableContentResizing = false) {
		global $USER;

		if ($errors == NULL)
			$errors = array();

		// validate that the data is a valid utf8 character stream
		if (!mb_check_encoding($data, "utf-8")) {
			error_log("message data contains illegal character stream");
			$errors[] = "Message data contains illegal character stream";
			return array();
		}

		// Strip off any control characters that will cause the rendered xml to barf
		$data = removeIllegalXmlChars($data);
		
		//make all fieldnames lower case so we can do a case-insensitive search later
		//FIXME manager assumes that there is no authorization checking (and $USER is not set) when editing templates
		//FIXME old version would not check authorization on parse, due to manager bug we dont use FieldMap::getAuthorizedFieldInsertNames()
		$insertfields = array_map("strtolower", FieldMap::getFieldInsertNames());
		
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
			$pos_mal = strpos($data,"<{");	// <{burst|content:#\d+}>
			
			// get imageupload tags
			$matches = array();
			$uploadimageurl = "";
			if (preg_match("/(\<img .*?src\=\"[^\=]*viewimage\.php\?id\=)/", strtolower($data), $matches)) {
				// we only care about the first match
				$uploadimageurl = $matches[1];
				$pos_i = stripos($data, $uploadimageurl);
			} else {
				$pos_i = false;
			}

			$matches = array();
			$uploadattachmenturl = "";
			if (stripos($data, 'emailattachment.php') !== false) {
				if (preg_match("/(\<a .*?href\=['\"]*\S*emailattachment\.php\?id\=)/", strtolower($data), $matches)) {
					// we only care about the first match
					$uploadattachmenturl = $matches[1];
					$pos_ma = stripos($data, $uploadattachmenturl);
				} else {
					$pos_ma = false;
					$ha_info_array = array(
						"message" => $data,
						"userAgent" => $_SERVER['HTTP_USER_AGENT'],
						"customerId" => getSystemSetting("_customerid"),
						"userId" => $USER->id
					);
					error_log("DEBUG_HA_JSON: " . print_r($ha_info_array, true));
				}
			} else {
				$pos_ma = false;
			}

			$poses = array();
			if ($pos_f !== false) $poses[] = $pos_f;
			if ($pos_a !== false) $poses[] = $pos_a;
			if ($pos_l !== false) $poses[] = $pos_l;
			if ($pos_i !== false) $poses[] = $pos_i;
			if ($pos_mal !== false) $poses[] = $pos_mal;
			if ($pos_ma !== false) $poses[] = $pos_ma;

			if (! count($poses)) break;

			$pos = min($poses);
			if ($pos !== false){
				if($pos === $pos_f) $type = 'V';
				if($pos === $pos_a) $type = 'A';
				if($pos === $pos_l) $type = 'newlang';
				if($pos === $pos_i) $type = 'I';
				if($pos === $pos_mal) $type = 'MAL';
				if($pos === $pos_ma) $type = 'HMAL';
			}

			//make a text part up to the pos of the field
			$txt = substr($data, 0, $pos);
			while (strlen($txt) > 0) {
				$part = new MessagePart();
				$part->type = "T";
				if (strlen($txt) <= 65535) {
					$part->txt = $txt;
					$txt = "";
				} else {
					$part->txt = substr($txt, 0, 65535);
					$txt = substr($txt, 65535);
				}
				//$part->messageid = $this->id; // assign ID afterwards so ID is set
				$part->sequence = $partcount++;
				if($currvoiceid !== null)
					$part->voiceid = $currvoiceid;
				$parts[] = $part;
			}

			// Skip ahead past the beginning of the token; images are bigger than the rest due to HTML markup
			if ($type == 'I') {
				$pos += mb_strlen($uploadimageurl);
			}
			else if ($type == 'HMAL') {
				$pos += mb_strlen($uploadattachmenturl);
			}
			else {
				$pos += 2;
			}

			// Assuming at least one char for audio/field name, find the end of the token
			switch ($type){
				case "A": $endtoken = "}}"; break;
				case "V": $endtoken = ">>"; break;
				case "newlang": $endtoken = "]]"; break;
				case "I": $endtoken = '">'; break;
				case "MAL": $endtoken = '}>'; break;
				case "HMAL": $endtoken = '</a>'; break;
			}
			$length = @stripos($data, $endtoken, $pos + 1);

			if ($length === false) {
				$errors[] = "Can't find end of field, was expecting '$endtoken'";
				$length = 0;
			} else {
				$length -= $pos;

				$token  = substr($data,$pos,$length);
				$part = new MessagePart();
				$part->type = $type;

				switch ($type) {

					// Message Attachment Links
					case 'MAL':

						// Look for the message attachment ID within the token
						if (strpos($token, ":#") !== false) {
							// Note $maltype is unused, it's available for convinience though!
							list($maltype, $malid) = explode(":#", $token);
						} else {
							$malid = false;
						}

						// if we have the message attachment ID, check for it
						if ($malid !== false) {
							$malidFound = QuickQuery('SELECT id FROM messageattachment WHERE id = ?', false, array($malid));

							// If we don't have a good message attachment ID to work with...
							if (! $malidFound) {
								// TODO - add support for discovering the message attachment ID automagically (?)
								error_log_helper("WARNING: automatic discovery of the message attachment ID is not supported; it must be provided in the field insert");
								$errors[] = "Can't find message attachment";
							}

							// Finish preparing the message part
							$part->sequence = $partcount++;
							$part->messageattachmentid = $malidFound;
							$parts[] = $part;
						}

						break;
					//Message Attachment link with different parsing logic
					case 'HMAL':
						$posi = stripos($token, "\">");
						$params = substr($token, 0, $posi);
						$decoded_parms = html_entity_decode($params, null, 'UTF-8');
						parse_str($decoded_parms, $parms);

						$displayName = trim(substr($token, $posi + 2));
						// Finish preparing the message part
						$part->sequence = $partcount++;
						$part->context = array("attachmentId" => $parms["caid"], "displayName" => $displayName);
						$parts[] = $part;
						break;

					case "A":
						$part->sequence = $partcount++;
						
						if (strpos($token, ":#") !== false) {
							list($afname,$afidtag) = explode(":#", $token);
						} else {
							$afname = $token;
							$afidtag = false;
						}
						
						//if we have the audio file ID, check for it
						$audioid = false;
						if ($afidtag)
							$audioid = QuickQuery("select id from audiofile where userid=? and not deleted and id=?", false, array($USER->id,$afidtag));
						
						//if we didn't find one by ID, fall back to other methods
						if (! $audioid) {
							//if we have an array of audiofileids, scan for the named af in them
							 if (is_array($audiofileids)) {
								if (count($audiofileids) > 0) {
									$query = "select id from audiofile where name=? and deleted = 0 and id in (".implode(",", $audiofileids).")";
									$audioid = QuickQuery($query, false, array($afname));
								}
							//otherwise search all audiofiles, preferring recent files
							} else {
								error_log_helper("WARNING: searching all audio files, may result in wrong results!"); 
								$query = "select id from audiofile where userid=? and name=? and deleted = 0 order by recorddate desc";
								$audioid = QuickQuery($query, false, array($USER->id, $afname));
							}
						}
						
						if ($audioid !== false) {
							//find an audiofile with this name
							$part->audiofileid = $audioid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find audio file named '$afname'";
						}

						break;

					case "V":
						$part->sequence = $partcount++;
						if (strpos($token, ":") !== false) {
							list($fieldname, $defvalue) = explode(":", $token);
						} else {
							$fieldname = $token;
							$defvalue = "";
						}
						
						//do case insensitive search for fieldname in field inserts array
						$fieldnum = array_search(strtolower($fieldname), $insertfields);

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
						if (isset($defaultvoice->gender)){
							$currvoiceid = QuickQuery("select id from ttsvoice where language = ? and gender = ? and enabled",
								false, array(strtolower($token), $defaultvoice->gender));
							if ($currvoiceid == false){
								$currvoiceid = QuickQuery("select id from ttsvoice where language = ? and gender = ? and enabled",false, array(strtolower($token),
									($defaultvoice->gender == "female" ? "male" : "female")));
							}
							if ($currvoiceid == false){
								$errors[] = "Can't find that language: " . $token . ".";
								$currvoiceid = null;
							}
						}
						break;

					case "I":
						$part->sequence = $partcount++;

						// Make the part, but also check if the image needs to be resized and change the content ID as needed...
						$contentId = intval($token); // Strip off the integer ID right at the token; might be more after it!

						$content = DBFind('Content', 'from content where id = ?', false, array($contentId));
						if ($content !== false) {

							// See if resizing is needed... (only when we're saving from f.recreateParts)
							if ($enableContentResizing) {

								$resized = false;

								// Reassemble the entire image tag to extract height/width attributes if present
								// Got [<img src="viewimage.php?id=] image token: [33" height="366" width="366]
								$imgTag = $uploadattachmenturl . $token;

								// Try a tag with style + width/height attributes on the IMG tag...
								if (preg_match('/width:\s*(\d+)px;\s*height:\s*(\d+)px;/i', $imgTag, $matches)) {
									$width = intval($matches[1]);
									$height = intval($matches[2]);

									// Do the sizes match those recorded for this content ID?
									if (($height != $content->height) || ($width != $content->width)) {
										$resized = true;
									}
								}

								// Try a tag with style + width/height attributes on the IMG tag...
								else if (preg_match('/height:\s*(\d+)px;\s*width:\s*(\d+)px;/i', $imgTag, $matches)) {
									$height = intval($matches[1]);
									$width = intval($matches[2]);

									// Do the sizes match those recorded for this content ID?
									if (($height != $content->height) || ($width != $content->width)) {
										$resized = true;
									}
								}

								// Try a tag with height/width HTML attributes on the IMG tag...
								else if (preg_match('/height="(\d+)/', $imgTag, $matches)) {
									$height = intval($matches[1]);
									if (preg_match('/width="(\d+)/', $imgTag, $matches)) {
										$width = intval($matches[1]);
										//error_log_helper("Got image [{$contentId}] width [{$width}] height [{$height}] from [{$imgTag}]");
										
										// Do the sizes match those recorded for this content ID?
										if (($height != $content->height) || ($width != $content->width)) {
											$resized = true;
										}
									}
								}

								if ($resized) {

									// Resize needed!

									// Is there an original image we should resize from?
									if ($content->originalcontentid) {
										$originalContent = DBFind('Content', 'from content where id = ?', false, array($content->originalcontentid));
									} else {
										// Nope! This one is the original, so use it
										$originalContent = $content; 
									}

									// Get the originalContent's image data stream
									if ($imageStream = contentGet($originalContent->id)) {
										list($type, $imageData) = $imageStream;

										// Resize the originalContent to the newly specified widthxheight
										$imageData = base64_encode(resizeImageStream($imageData, $width, $height, $type)); 
										if (strlen($imageData) > 0) {
											// Prepare a new content record for storage...
											$content = new Content();

											// Save the resized content as a new contentId
											// with a reference to the originalContent
											$content->data = $imageData;
											$content->contenttype = $originalContent->contenttype;
											$content->width = $width;
											$content->height = $height;
											$content->originalcontentid = $originalContent->id;
											$content->create();
											$content->refresh();
										} else {
											$errors[] = _L('Failed to resize image');
										}
									} else {
										$errors[] = _L('Failed to read image for resizing');
									}
								}
							}

							// Capture the current content ID
							$part->imagecontentid = $content->id;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find content with id '$token'";
						}
						break;
				}
			}

			//skip the end if we found it
			$skip = $pos + $length + ($length ? strlen($endtoken) : 0);
			$data = substr($data, $skip);
		}

		//get trailing txt part;
		while (strlen($data) > 0) {
			$part = new MessagePart();
			$part->type="T";
			
			if (strlen($data) <= 65535) {
				$part->txt = $data;
				$data = "";
			} else {
				$part->txt = substr($data, 0, 65535);
				$data = substr($data, 65535);
			}

			$part->sequence = $partcount++;
			if ($currvoiceid !== null) $part->voiceid = $currvoiceid;

			$parts[] = $part;
		}

		return $parts;
	}
	
	// used to prepare parts to display in editor (use renderXXX methods for preview)
	static function format ($parts) {
		$map = FieldMap::getFieldInsertNames();
		$data = "";
		$voices = DBFindMany("Voice", "from ttsvoice where enabled");
		$currvoiceid=null;
		foreach ($parts as $part) {
			if( $currvoiceid == null){
				$currvoiceid = $part->voiceid;
			} else if( $part->voiceid && $part->voiceid != $currvoiceid){
				$voicestr = "[[" . ucfirst($voices[$part->voiceid]->language) . "]]";
				$data .= $voicestr;
				$currvoiceid = $part->voiceid;
			}
			
			$partstr = '';
			switch ($part->type) {

				case 'MAL':
					// Find the message attachment for this message attachment link part
					$messageAttachment = new MessageAttachment($part->messageattachmentid);
					$contentAttachment = new ContentAttachment($messageAttachment->contentattachmentid);
					permitContent($contentAttachment->contentid);
					$mal = '<a class="message-attachment-placeholder" contenteditable="false" href="emailattachment.php?id=' . $contentAttachment->contentid . "&caid=" . $contentAttachment->id . "&name=" . $contentAttachment->filename . '">' . $messageAttachment->displayName . '</a>';
					$partstr .= ($messageAttachment->type == "burst") ? '<{' . $messageAttachment->type . ':#' . $part->messageattachmentid . '}>' : $mal;
					break;

				case 'A':
					$part->audiofile = new AudioFile($part->audiofileid);
					$partstr .= "{{" . $part->audiofile->name . ":#" . $part->audiofileid . "}}";
					break;

				case 'T':
					$partstr .= $part->txt;
					break;

				case 'V':
					$partstr .= "<<";
					//special field
					$partstr .= $map[$part->fieldnum];
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
	// DEPRECATED only manager/ inbound/ files are allowed to use this any more
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

	/**
	 * Constructs message parts from a provided message id and calls appserver to render the mp3 audio
	 * @param int $id message id
	 * @param array $fields fieldnum to value map
	 * @return object|null the rendered audio, contains fields contenttype and data
	 */
	static function getMp3AudioFull($id, $fields) {
		$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($id));

		// call appserver to render audio
		$messagepartdtos = array();
		foreach ($parts as $part) {
			$messagepartdto = new \commsuite\MessagePartDTO();

			switch($part->type) {
				case "T":
					$messagepartdto->type = \commsuite\MessagePartTypeDTO::T;
					$voice = new Voice($part->voiceid);
					$messagepartdto->name = $voice->name;
					$messagepartdto->gender = $voice->gender;
					$messagepartdto->languagecode = $voice->language;
					$messagepartdto->txt = $part->txt;
					break;
				case "A":
					$messagepartdto->type = \commsuite\MessagePartTypeDTO::A;
					$messagepartdto->contentid = QuickQuery("select contentid from audiofile where id=?",false,array($part->audiofileid)) + 0;
					break;
				case "V":
					if (!isset($fields[$part->fieldnum]) || !($value = $fields[$part->fieldnum])) {
						$value = $part->defaultvalue;
					}
					$messagepartdto->type = \commsuite\MessagePartTypeDTO::V;
					$voice = new Voice($part->voiceid);
					$messagepartdto->name = $voice->name;
					$messagepartdto->gender = $voice->gender;
					$messagepartdto->languagecode = $voice->language;
					$messagepartdto->defaultvalue = $value;
					$messagepartdto->fieldnum = $part->fieldnum;
					break;
			}

			$messagepartdtos[] = $messagepartdto;
		}

		// call appserver to render
		return phoneMessageGetMp3AudioFile($messagepartdtos);
	}
	
	// The only reliable way to check the message length is to render it. Return negative value on error.
	static function getAudioLength($id, $fields) {
		$size = -1;

		// Get the mp3 version of the message and write it to a temp file
		$mp3Audio = Message::getMp3AudioFull($id, $fields);
		$mp3Name = secure_tmpname("preview_parts",".mp3");
		if (file_put_contents($mp3Name,$mp3Audio->data));
		$mp3Audio = null;

		// Convert it to a wav
		$converter = new AudioConverter();
		$wavName = false;
		try {
			$wavName = $converter->getMono8kPcm($mp3Name);
			$size = filesize($wavName);
		} catch (Exception $e) {
			// There was a problem converting the audio file
		}
		@unlink($mp3Name);
		@unlink($wavName);

		return $size;
	}
}

?>
