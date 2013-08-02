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

		
		// Validate Message and Permisions 
		if (!$message) 
			exit();
		
		if (!userCanSee("message", $id)) {
			// Allow preview of message if message is associated with a job that is viewable
			if (isset($_REQUEST["jobid"])) {
				$isAssociated = QuickQuery("select 1 from job j inner join messagegroup mg on (mg.id = j.messagegroupid) where j.id=? and mg.id=?",false,array($_REQUEST["jobid"],$message->messagegroupid));
				$canViewMessage = $isAssociated && userCanSee("job", $_REQUEST["jobid"]);
				if (!$canViewMessage) {
					exit() ;
				}
			} else {
				exit();
			}
		}
		// End of Validate
		
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
				if (isset($_REQUEST["jobtypeid"])) {
					$jobtype = DBFind("JobType","from jobtype where id=?",false,array($_REQUEST["jobtypeid"]));
					if ($jobtype) {
						$jobpriority = $jobtype->systempriority;
					}
				}
				
				$imageparts = DBFindMany('MessagePart', "from messagepart where messageid=? and type='I'", false, array($message->id));
				foreach($imageparts as $part) {
					permitContent($part->imagecontentid);
				}
				
				switch ($message->subtype) {
					case "html":
						$modal->title = _L("%s HTML Email Message" , Language::getName($message->languagecode));
						$modal->text = "<iframe src=\"messageview.php?messageid={$message->id}" . (isset($_REQUEST["jobtypeid"])?"&jobtypeid={$_REQUEST["jobtypeid"]}":"") . "\"></iframe>";//$email->emailbody;
						break;
					case "plain":
						$modal->title = _L("%s Plain Email Message" , Language::getName($message->languagecode));
						$email = messagePreviewForPriority($message->id, $jobpriority); // returns commsuite_EmailMessageView object
						$modal->text = $modal->formatEmailHeader($email);
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
					case "feed":
						$message->readHeaders();
						$modal->text = "<b>Label:</b> $message->subject <hr />" . $message->renderSmsParts($modal->parts);
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
	
	/**
	 * Constructs and returns a PreviewModal object besed on sourcetext from agument or located in session.
	 * Make sure user has access to to messagegroup before passing in $messagegroupid
	 * @param $messagegroupid (optional)
	 */
	static function HandleRequestWithPhoneText($messagegroupid = false) {
		$modal = new PreviewModal();
		$modal->playable = true;
		$showmodal = false;
		if (isset($_REQUEST["previewmodal"]) && isset($_REQUEST["text"])  && isset($_REQUEST["language"]) && isset($_REQUEST["gender"])) {
			$showmodal = true;
			// save to session
			$modal->uid = uniqid();
			$voiceid = Voice::getPreferredVoice($_REQUEST["language"], $_REQUEST["gender"]);
			$_SESSION["previewmessagesource"] = array("uid" => $modal->uid, "source" => $_REQUEST["text"], "languagecode" => $_REQUEST["language"],"voiceid" => $voiceid);
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
		$modal->title = _L("%s Phone Message", Language::getName($_SESSION["previewmessagesource"]["languagecode"]));
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
	
	static function HandleRequestWithStationeryText() {
		if (!isset($_REQUEST["previewmodal"]) ||
				!isset($_REQUEST["subtype"]) ||
				!isset($_REQUEST["text"]))
			return;
			
		$modal = new PreviewModal();
		$message = new Message();
		$message->type = "email";
		$message->subtype = $_REQUEST["subtype"];
	
		$parts = Message::parse($_REQUEST["text"],$modal->errors);
		if (count($modal->errors) == 0) {
			$email = emailMessageViewForMessageParts($message,$parts,3);
		}
		switch ($_REQUEST["subtype"]) {
			case "html":
				$modal->title = _L("%s HTML Email Message", Language::getName($message->languagecode));
				$modal->text = $email->emailbody;
				break;
			case "plain":
				$modal->title = _L("%s Plain Email Message", Language::getName($message->languagecode));
				$modal->text = nl2br(escapehtml($email->emailbody));
				break;
		}
	
		$modal->includeModal();
	}
	
	
	
	// Includeds the javascript necessary to open the modal and renderes the form if there are any field insters
	function includeModal() {
		$modalcontent = $playercontent = $formdata = '';
		$isAjaxSubmit = false;
		if (!$this->playable) {
			$modalcontent = $this->text;
		}
		else if ($this->hasfieldinserts) {
			$modalcontent = $this->form->render();
			if ($this->form) {
				if ($isAjaxSubmit = $this->form->isAjaxSubmit()) {
					$formdata = $this->form->getFormdata();
				}
			}
		}

		$result = Array(
			"playable" => $this->playable,
			"title" => $this->title, 
			"hasinserts" => $this->hasfieldinserts, 
			"errors" => $this->errors, 
			"content" => $modalcontent, 
			"uid" => $this->uid, 
			"partscount" => count($this->parts),
			'ajax' => ($isAjaxSubmit ? 'true' : 'false')
		);

		if (is_array($formdata) && count($formdata)) {
			$result['formdata'] = $formdata;
		}

		header('Content-Type: application/json');
		echo json_encode($result);
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
		


		<script type='text/javascript' language='javascript'>
		
		function messagePrevewLoaded(area) {
			var $ = jQuery;
			var modal = $('#defaultmodal');
			modal.height("80%");
			modal.width("100%");
			centerModal(modal);
		}

		var centerModal = function(modal) {
			var $ = jQuery;
			$("div.default-modal").css("margin-top", -(modal.height()/2));
			$("div.default-modal").css("margin-left", -(modal.width()/2));

			// Hide modal on resize since it will no longer be centered.
			$(window).one('resize',function() {
				modal.modal('hide');
			});
		}
		var showPreview = function(post_parameters,get_parameters){
			var $ = jQuery;

			var modal = $('#defaultmodal');
			modal.modal();
			modal.height("auto");
			modal.width("600px");
			var header = $('#defaultmodal').find(".modal-header h3");
			var body = $('#defaultmodal').find(".modal-body");
			
			modal.one('hide',function() {
				body.html("");
			});
			
			modal.find(".modal-body").html('<img src="img/ajax-loader.gif" alt="Please Wait..."/> Loading...')
			new Ajax.Request('<?= $posturl?>' + (get_parameters?'&' + get_parameters:''), {
				'method': 'post',
				'parameters': post_parameters,
				'onSuccess': function(response) {
					header.html("");
					if (response.responseJSON) {
						var result = response.responseJSON;
						header.html(result.title);
						
						if (result.errors != undefined && result.errors.size() > 0) {
							header.html('Unable to Preview');
							
							body.html("The following error(s) occured:");
							var list = $('<ul/>');
							result.errors.each(function(error) {
								list.append('<li>' + error + '</li>');
							});
							body.append(list);
						} else if (result.playable == true) {
							body.html(result.content);
							$('<div/>', {
								id: 'previewplayer',
								style: 'text-align:center;'
							}).appendTo(body);

							$('<div/>', {
							    id: 'previewdownload',
							    style: 'text-align:center;'
							}).appendTo(body);
							
							if (result.hasinserts == false) {
								$('#previewdownload').html('<a href="previewaudio.mp3.php?download=true&uid=' + result.uid + '">Click here to download</a>');
								if (result.partscount != 0)
									embedPlayer('previewaudio.mp3.php?uid=' + result.uid,'previewplayer',result.partscount);
								else {
									embedPlayer('previewaudio.mp3.php?uid=' + result.uid,'previewplayer');
								}
							} else {
								$('#previewmessagefields').on('Form:Submitted',function(e,memo){
									embedPlayer('previewaudio.mp3.php?uid=' + memo,'previewplayer',result.partscount);
									$('#previewdownload').html('<a href="previewaudio.mp3.php?download=true&uid=' + memo + '">Click here to download</a>');
								});
							}
						} else {
							if (post_parameters && typeof(post_parameters) != 'undefined' && typeof(post_parameters.subtype) != 'undefined' ) {
								var iframe = $("<iframe/>", {src: "blank.html"});
								body.html(iframe);
								iframe.load(function(){
									var iframecontent = iframe.contents().find('body');
									iframecontent.append(result.content);
									modal.height("100%");
									modal.width("100%");
				 					centerModal(modal);
								});
							} else {
								body.html(result.content);
							}
						}
					} else {
						header.html('Error');
						body.html('Unable to preview this message 2');
					}
				},
				
				'onFailure': function() {
					header.html('Error');
					body.html('Unable to preview this message 1');
				},
				'onComplete': function() {
					centerModal(modal);
				}
			});
			
		};
			</script>
		<?
	}
}




?>
