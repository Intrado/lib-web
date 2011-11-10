<?

// Requires previewfields.inc.php



// Preview modal canbe used with a messageid or raw text to produce a modal window for preview
// Each page that uses the modal will need to include the content with the includeModal() function 
// but also handle any potential requests that can come with field inserts.
// therefore each page is must also call the handleRequest()

class PreviewModal {
	var $form;
	var $playable = false;
	var $text;
	var $parts;
	var $valueparts;
	var $title;
	var $errors = array();
	var $hasfieldinserts = false;
	var $uid;

	// private constructor to create modal object
	private function PreviewModal() {}
	
	// Cunstructs and returns a PreviewModal object based on the parts from messageid
	static function HandleRequestWithId() {
		global $USER;
		
		if (!isset($_REQUEST["previewmodal"]) ||
			!isset($_REQUEST["previewid"]) ) {
				return;
		}
		$jobpriority = isset($_REQUEST["jobpriority"])?$_REQUEST["jobpriority"]:3; // Set jobpriority to be able to preview email with the appropriate template on the jobedit page
		
		$id = $_REQUEST["previewid"] + 0;
		$message = DBFind("Message", "from message where id = ?", false, array($id));
		$canviewmessage = userCanSee("message", $id);
		if(!$canviewmessage) {
			return;
		}
		
		$modal = new PreviewModal();
		switch($message->type) {
			case "phone":
				// Get message parts and save to session
				$modal->uid = uniqid();
				$modal->parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
				$modal->initializeFieldContent("phone");
				$modal->title = _L("%s Phone Message" , Language::getName($message->languagecode));
				$modal->playable = true;
				break;
			case "email":
				$email = messagePreviewForPriority($message->id, $jobpriority); // returns commsuite_EmailMessageView object
				$modal->text = $modal->formatEmailHeader($email);
				switch ($message->subtype) {
					case "html":
						$modal->title = _L("%s HTML Email Message" , Language::getName($message->languagecode));
						$modal->text .= $email->emailbody;
						break;
					case "plain":
						$modal->title = _L("%s Plain Email Message" , Language::getName($message->languagecode));
						$modal->text .= nl2br(escapehtml($email->emailbody));
						break;
				}
				break;
			case "sms":
				$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
				$modal->text = $message->renderSmsParts($parts);
				$modal->title = _L("%s SMS Message" , Language::getName($message->languagecode));
				break;
			case "post":
				$modal->parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
				switch ($message->subtype) {
					case "page":
						// Page preview is can use the same as the non templated html rendered message 
						$modal->text = $message->renderEmailHtmlParts($modal->parts);
						break;
					case "voice":
						$modal->playable = true;
						$modal->uid = uniqid();
						$modal->initializeFieldContent($message->type);
						break;
					default:
						// Other posts will only need first part, same as sms
						$modal->text = $message->renderSmsParts($modal->parts);
						break;
				}
				
				$modal->title = _L("%s %s Message" , Language::getName($message->languagecode), ucfirst($message->subtype));
				break;
			default:
				return;
		}
		
		$modal->handleRequest();
		$modal->includeModal();
		exit();
	}
	
	// Constructs and returns a PreviewModal object besed on sourcetext from agument or located in session.
	static function HandleRequestWithPhoneText($messagegroupid = false) {
		$modal = new PreviewModal();
		$modal->playable = true;
		$showmodal = false;
		if (isset($_REQUEST["previewmodal"]) && isset($_REQUEST["text"])  && isset($_REQUEST["language"]) && isset($_REQUEST["gender"])) {
			$showmodal = true;
			// save to session
			$modal->uid = uniqid();
			$voiceid = Voice::getPreferredVoice($_REQUEST["language"], $_REQUEST["gender"]);
			$_SESSION["previewmessagesource"] = array("uid" => $modal->uid, "source" => $_REQUEST["text"], "voiceid" => $voiceid);
		} else if (isset($_SESSION["previewmessagesource"])) {
			$modal->uid = $_SESSION["previewmessagesource"]["uid"];
		} else {
			return;
		}
		// Parse the source text into parts
		if ($messagegroupid)
			$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroupid);
		else
			$audiofileids = false;
		
		// Prevent preview of long sequences of digits
		if (preg_match("/[0-9]{100,}/",$_SESSION["previewmessagesource"]["source"])) {
			return;
		}
		
		$modal->parts = Message::parse($_SESSION["previewmessagesource"]["source"],$modal->errors,$_SESSION["previewmessagesource"]["voiceid"],$audiofileids);
		if (count($modal->errors) == 0) {
			$modal->initializeFieldContent("phone");
		}
		
		$voice = new Voice($_SESSION["previewmessagesource"]["voiceid"]);
		$modal->title = _L("%s Phone Message", Language::getName($voice->languagecode));
		if ($showmodal) {
			$modal->includeModal();
		} else if (count($modal->errors) == 0) {
			$modal->handleRequest();
		}
		return;
	}
	
	
	static function HandleRequestWithEmailText() {
		if (!isset($_REQUEST["previewmodal"]) || 
			!isset($_REQUEST["language"]) || 
			!isset($_REQUEST["subtype"]) || 
			!isset($_REQUEST["fromname"]) || 
			!isset($_REQUEST["from"]) || 
			!isset($_REQUEST["subject"]) || 
			!isset($_REQUEST["text"]))
			return;
			
		$modal = new PreviewModal();
		$message = new Message();
		$message->type = "email";
		$message->subtype = $_REQUEST["subtype"];
		$message->fromname = $_REQUEST["fromname"];
		$message->fromemail = $_REQUEST["from"];
		$message->subject = $_REQUEST["subject"];
		$message->languagecode = $_REQUEST["language"];
		$message->stuffHeaders();
		
		$parts = Message::parse($_REQUEST["text"],$modal->errors);
		if (count($modal->errors) == 0) {
			$email = emailMessageViewForMessageParts($message,$parts,3);
			$modal->text = $modal->formatEmailHeader($email);
		}
		switch ($_REQUEST["subtype"]) {
			case "html":
				$modal->title = _L("%s HTML Email Message", Language::getName($message->languagecode));
				$modal->text .= $email->emailbody;
				break;
			case "plain":
				$modal->title = _L("%s Plain Email Message", Language::getName($message->languagecode));
				$modal->text .= nl2br(escapehtml($email->emailbody));
				break;
		}
		
		$modal->includeModal();
	}
	
	
	
	// Includeds the javascript necessary to open the modal and renderes the form if there are any field insters
	function includeModal() {
		$modalcontent = "";
		$playercontent = "";
		if (!$this->playable) {
			$modalcontent = $this->text;
		} else if ($this->hasfieldinserts) {
			$modalcontent = $this->form->render();
		}
		header('Content-Type: application/json');
		echo json_encode(array("playable" => $this->playable,"title" => $this->title, "hasinserts" => $this->hasfieldinserts, "errors" => $this->errors, "form" => $modalcontent, "uid" => $this->uid, "partscount" => count($this->parts)));
		exit();
	}
	
	// Creates the messageparts and saves them in session for the messages contains field instersts 
	function handleRequest() {
		if ($this->hasfieldinserts) {
			$this->form->handleRequest();
			$datachange = false;
			$errors = false;
			//check for form submission
			if ($button = $this->form->getSubmit()) { //checks for submit and merges in post data
				$ajax = $this->form->isAjaxSubmit(); //whether or not this requires an ajax response	
				if ($this->form->checkForDataChange()) {
					$datachange = true;
				} else if (($errors = $this->form->validate()) === false) { //checks all of the items in this form
					
					$postdata = $this->form->getData(); //gets assoc array of all values {name:value,...}
					if (is_array($this->parts)) {
						$previewparts = array();
						
						foreach ($this->parts as $part) {
							$previewpart = array("type" => $part->type,"txt" => $part->txt,"audiofileid" => $part->audiofileid, "voiceid" => $part->voiceid);
							if ($part->type == "V") {
								$previewpart["type"] = "T";
								if (isset($postdata[$part->fieldnum])) {
									$previewpart["txt"] = $postdata[$part->fieldnum];
								} else {
									$previewpart["txt"] = $part->defaultvalue;
								}
							}
							$previewparts[] = $previewpart;
						}
						
						$_SESSION["previewmessage"] = array("uid" => $this->uid, "parts" => $previewparts);
						$this->form->fireEvent($this->uid);
					}
				}
			}
		}
	}
	
	
	// Private helper function to create from for field inserts or set the session to play intantly
	private function initializeFieldContent($type) {
		if ($this->parts) {
			// Get preview fields
			list($fields,$fielddata,$fielddefaults) = getpreviewfieldmapdatafromparts($this->parts);
			
			$messageformdata = getpreviewformdata($fields,$fielddata,$fielddefaults,$type);
			$buttons =  array(submit_button(_L('Play with Field(s)'),"submit","fugue/control"));
			$this->form = new Form("previewmessagefields",$messageformdata,null,$buttons);
			$this->hasfieldinserts = count($messageformdata) > 0;
			
			//Set or unset playable session depending on field inserts
			if (!$this->hasfieldinserts) {
				$previewparts = array();
				foreach ($this->parts as $part) {
					$previewpart = array("type" => $part->type,"txt" => $part->txt,"audiofileid" => $part->audiofileid, "voiceid" => $part->voiceid);
					$previewparts[] = $previewpart;
				}
				$_SESSION["previewmessage"] = array("uid" => $this->uid, "parts" => $previewparts);
			} else {
				unset($_SESSION["previewmessage"]);
			}
		}
	}
	
	//formates a commsuite_EmailMessageView object to html
	private function formatEmailHeader($email) {
		return "<b>From:</b> $email->emailfromname &lt;$email->emailfromaddress&gt;<br /><b>Subject:</b> $email->emailsubject<br /><hr />";
	}
	
	
	
	static function includePreviewScript() {
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "previewmodal=true";
		?>
		<script type="text/javascript" src="script/datepicker.js"></script>
		<script type='text/javascript' language='javascript'>
		var showPreview = function(post_parameters,get_parameters){
			var modal = new ModalWrapper("Loading...",false,false);
			modal.window_contents.update(new Element('img',{src: 'img/ajax-loader.gif'}));
			modal.open();
			
			new Ajax.Request('<?= $posturl?>' + (get_parameters?'&' + get_parameters:''), {
				'method': 'post',
				'parameters': post_parameters,
				'onSuccess': function(response) {
					modal.window_title.update("");
					if (response.responseJSON) {
						var result = response.responseJSON;
						modal.window_title.update(result.title);
						
						if (result.errors != undefined && result.errors.size() > 0) {
							modal.window_title.update('Unable to Preview');
							
							modal.window_contents.update("The following error(s) occured:");
							var list = new Element('ul');
							result.errors.each(function(error) {
								list.insert(new Element('li').update(error));
							});
							modal.window_contents.insert(list);
						} else if (result.playable == true) {
							modal.window_contents.update(result.form);
							
							var player = new Element('div',{
								id: 'player',
								style: 'text-align:center;'
							});
							var download = new Element('div',{
								id: 'download',
								style: 'text-align:center;'
							});
							modal.window_contents.insert(player);
							modal.window_contents.insert(download);
							
							
							if (result.hasinserts ==  false) {
								var downloadlink = new Element('a',{
									href: 'previewaudio.mp3.php?download=true&uid=' + result.uid
								}).update('Click here to download');
								
								$('download').update(downloadlink);
								embedPlayer('previewaudio.mp3.php?uid=' + result.uid,'player',result.partscount);
							} else {
								$('previewmessagefields').observe('Form:Submitted',function(e){
									embedPlayer('previewaudio.mp3.php?uid=' + e.memo,'player',result.partscount);
									var download = new Element('a',{
										href: 'previewaudio.mp3.php?download=true&uid=' + e.memo
									}).update('Click here to download');
									$('download').update(download);
								});
							}
						} else {
							modal.window_contents.update(result.form);
						}
					} else {
						modal.window_title.update('Error');
						modal.window_contents.update('Unable to preview this message');
					}
				},
				
				'onFailure': function() {
					modal.window_title.update('Error');
					modal.window_contents.update('Unable to preview this message');
				}
			});
			
		};
			</script>
		<?
	}
}




?>