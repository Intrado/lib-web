<?

// Requires previewfields.inc.php



// Preview modal canbe used with a messageid or raw text to produce a modal window for preview
// Each page that uses the modal will need to include the content with the includeModal() function 
// but also handle any potential requests that can come with field inserts.
// therefore each page is must also call the handleRequest()

class PreviewModal {
	var $form;
	var $type;
	var $text;
	var $parts;
	var $valueparts;
	var $title;
	
	var $hasfieldinserts = false;
	var $uid;

	// private constructor to create modal object
	private function PreviewModal($type) {
		$this->type = $type;
	}
	
	// Cunstructs and returns a PreviewModal object based on the parts from messageid
	static function HandlePhoneMessageId() {
		global $USER;
		
		if (!isset($_REQUEST["previewmodal"]) ||
			!isset($_REQUEST["previewid"]) ) {
				return;
		}
		$jobpriority = isset($_REQUEST["jobpriority"])?$_REQUEST["jobpriority"]:3; // Set jobpriority to be able to preview email with the appropriate template on the jobedit page
		
		$id = $_REQUEST["previewid"] + 0;
		$message = DBFind("Message", "from message where id = ?", false, array($id));
		$canviewmessage = $message && (userOwns("message", $id) || $USER->authorize('managesystem') || (isPublished("messagegroup", $message->messagegroupid) && userCanSubscribe("messagegroup", $message->messagegroupid)));
		if(!$canviewmessage) {
			return;
		}
		
		$modal = new PreviewModal($message->type);
		switch($message->type) {
			case "phone":
				// Get message parts and save to session
				$modal->uid = uniqid();
				$modal->parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
				$modal->initializePhoneFieldContent();
				$modal->title = _L("%s Phone Message" , Language::getName($message->languagecode));
				break;
			case "email":
				$email = messagePreviewForPriority($message->id, $jobpriority); // returns commsuite_EmailMessageView object
				$modal->text = $modal->formatEmail($email);
				switch ($message->subtype) {
					case "html":
						$modal->title = _L("%s HTML Email Message" , Language::getName($message->languagecode));
						break;
					case "plain":
						$modal->title = _L("%s Plain Email Message" , Language::getName($message->languagecode));
						break;
				}
				break;
			case "sms":
				$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
				$modal->text = $message->renderSmsParts($parts);
				$modal->title = _L("%s SMS Message" , Language::getName($message->languagecode));
				break;
			default:
				return;
		}
		
		$modal->handleRequest();
		$modal->includeModal();
		exit();
	}
	
	// Constructs and returns a PreviewModal object besed on sourcetext from agument or located in session.
	static function HandlePhoneMessageText($messagegroupid = false) {
		$modal = new PreviewModal('phone');
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
		$errors = array();
		if ($messagegroupid)
			$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroupid);
		else
			$audiofileids = false;
		$modal->parts = Message::parse($_SESSION["previewmessagesource"]["source"],$errors,$_SESSION["previewmessagesource"]["voiceid"],$audiofileids);
		if (count($errors) != 0) {
			error_log("Error parsing message source");
		}
		
		$modal->initializePhoneFieldContent();
		
		$voice = new Voice($_SESSION["previewmessagesource"]["voiceid"]);
		$modal->title = _L("%s Phone Message", Language::getName($voice->languagecode));
		if ($showmodal) {
			$modal->includeModal();
		} else {
			$modal->handleRequest();
		}
		return;
	}
	
	
	static function HandleEmailMessageText($languagecode,$subtype) {
		if (!isset($_REQUEST["previewmodal"]) || 
			!isset($_REQUEST["fromname"]) || 
			!isset($_REQUEST["from"]) || 
			!isset($_REQUEST["subject"]) || 
			!isset($_REQUEST["text"]))
			return;
			
		$modal = new PreviewModal("email");
		$message = new Message();
		$message->type = "email";
		$message->subtype = $subtype;
		$message->fromname = $_REQUEST["fromname"];
		$message->fromemail = $_REQUEST["from"];
		$message->subject = $_REQUEST["subject"];
		$message->languagecode = $languagecode;
		$message->stuffHeaders();
		
		$parts = Message::parse($_REQUEST["text"]);
		$email = emailMessageViewForMessageParts($message,$parts,3);
		$modal->text = $modal->formatEmail($email);
		switch ($subtype) {
			case "html":
				$modal->title = _L("%s HTML Email Message", Language::getName($message->languagecode));
				break;
			case "plain":
				$modal->title = _L("%s Plain Email Message", Language::getName($message->languagecode));
				break;
		}
		
		$modal->includeModal();
	}
	
	
	
	// Includeds the javascript necessary to open the modal and renderes the form if there are any field insters
	function includeModal() {
		$modalcontent = "";
		$playercontent = "";
		if ($this->type != "phone") {
			$modalcontent = $this->text;
		} else if ($this->hasfieldinserts) {
			$modalcontent = $this->form->render();
		}
		header('Content-Type: application/json');
		echo json_encode(array("type" => $this->type,"title" => $this->title, "hasinserts" => $this->hasfieldinserts, "form" => $modalcontent, "uid" => $this->uid, "partscount" => count($this->parts)));
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
	private function initializePhoneFieldContent() {
		if ($this->parts) {
			// Get preview fields
			list($fields,$fielddata,$fielddefaults) = getpreviewfieldmapdatafromparts($this->parts);
			
			$messageformdata = getpreviewformdata($fields,$fielddata,$fielddefaults,$this->type);
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
	private function formatEmail($email) {
		return "<b>From:</b> $email->emailfromname &lt;$email->emailfromaddress&gt;<br /><b>Subject:</b> $email->emailsubject<br /><hr /> $email->emailbody";
	}
	
	
	
	static function includePreviewScript() {
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "previewmodal=true";
		?>
		<script type='text/javascript' language='javascript'>
		var showPreview = function(post_parameters,get_parameters){
			var window_header = new Element('div',{
			className: 'window_header'
			});
			var window_title = new Element('div',{
			className: 'window_title'
			}).update("Loading...");
			var window_close = new Element('div',{
			className: 'window_close'
			});
			var window_contents = new Element('div',{
				className: 'window_contents'
			});
			var loader = new Element('a',{
				href: 'img/ajax-loader.gif'
			});
			var w = new Control.Modal(loader,Object.extend({
				className: 'modalwindow',
				overlayOpacity: 0.75,
				fade: false,
				width: 750,
				indicator:loader,
				insertRemoteContentAt:window_contents,
				afterOpen: function(){
					
				},
				afterClose: function(){
					this.destroy();
					window_contents.remove(); // remove since the player and download uses ids that is reused whe reopened
				}
			},{}));
			new Ajax.Request('<?= $posturl?>' + (get_parameters?'&' + get_parameters:''), {
				'method': 'post',
				'parameters': post_parameters,
				'onSuccess': function(response) {
					if (response.responseJSON) {
						var result = response.responseJSON;
						window_title.update(result.title);
						if (result.type == "phone") {
							window_contents.update(result.form + '<div style=\'text-align:center;\' id=\'player\'></div><div style=\'text-align:center;\' id=\'download\'></div>');
							if (result.hasinserts ==  false) {
								$('download').update('<a href=\'previewaudio.mp3.php?download=true&uid=' + result.uid + '\'>Click here to download</a>');
								embedPlayer('previewaudio.mp3.php?uid=' + result.uid,'player',result.partscount);
							} else {
								$('previewmessagefields').observe('Form:Submitted',function(e){
									embedPlayer('previewaudio.mp3.php?uid=' + e.memo,'player',result.partscount);
									$('download').update('<a href=\'previewaudio.mp3.php?download=true&uid=' + e.memo +'\'>Click here to download</a>');
								});
							}
						} else {
							window_contents.update(result.form);
						}
					} else {
						window_title.update('Error');
						window_contents.update('Unable to preview this message');
					}
				},
				
				'onFailure': function() {
					window_title.update('Error');
					window_contents.update('Unable to preview this message');
				}
			});
			
			w.container.insert(window_header);
			window_header.insert(window_title);
			window_header.insert(window_close);
			w.container.insert(window_contents);
			
			window_close.observe('click', function(event,modal) {
				modal.close();
			}.bindAsEventListener(this,w));
			
			w.open();
			return w;
		};
			</script>
		<?
	}
}




?>