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
	
	var $hasfieldinserts = false;
	var $uid;

	// private constructor to create modal object
	private function PreviewModal($type) {
		$this->type = $type;
	}
	
	// Cunstructs and returns a PreviewModal object based on the parts from messageid
	static function CreateModalForMessageId($messageid) {
		global $USER;
		
		// TODO make sure the user can preview subscribed messages
		if (!userOwns("message", $messageid)) {
			return null;
		}
		$message = new Message($messageid);
		$modal = new PreviewModal($message->type);
		if ($messageid) {
			switch($message->type) {
				case "phone":
					// Get message parts and save to session
					$modal->uid = uniqid();
					$modal->parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
					$modal->initializeContent();
					break;
				case "email":
					$modal->text = $message->renderEmailWithTemplate();
					break;
				case "sms":
					$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
					$modal->text = $message->renderSmsParts($parts);
					break;
			}
		}
		return $modal;
	}
	
	// Constructs and returns a PreviewModal object besed on sourcetext from agument or located in session.
	static function CreateModalForMessageText($messagegroupid,$type,$sourcetext = false,$languagecode = "en", $preferredgender = "female") {
		$modal = new PreviewModal($type);
		if ($sourcetext) {
			// save to session
			$modal->uid = uniqid();
			$voiceid = Voice::getPreferredVoice($languagecode, $preferredgender);
			$_SESSION["previewmessagesource"] = array("uid" => $modal->uid, "source" => $sourcetext, "voiceid" => $voiceid);
		} else if (isset($_SESSION["previewmessagesource"])) {
			$modal->uid = $_SESSION["previewmessagesource"]["uid"];
		} else {
			return $modal;
		}
		
		// Parse the source text into parts
		$errors = array();
		$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroupid);
		$modal->parts = Message::parse($_SESSION["previewmessagesource"]["source"],$errors,$_SESSION["previewmessagesource"]["voiceid"],$audiofileids);
		if (count($errors) != 0) {
			error_log("Error parsing message source");
		}
		
		$modal->initializeContent();
		return $modal;
	}
	
	// Includeds the javascript necessary to open the modal and renderes the form if there are any field insters
	function includeModal() {
		// TODO only phone is implemented continue with email 
		$modalcontent = "";
		$playercontent = "";
		if ($this->type != "phone") {
			$modalcontent = $this->text;
		} else {
			// Insert fields if they exists
			if ($this->hasfieldinserts) {
				$modalcontent = $this->form->render();
				$playercontent = "$('previewmessagefields').observe('Form:Submitted',function(e){
									embedPlayer('previewaudio.mp3.php?uid=' + e.memo,'player'," . count($this->parts) . ");
								});";
			} else {
				$playercontent = "embedPlayer('previewaudio.mp3.php?uid={$this->uid}','player'," . count($this->parts) . ");";
			}
			
		}
		// TODO move css and javascript includes out
		return "<div id='modalcontent' style='display:none;'>$modalcontent</div>
			<style type='text/css'>
			#control_overlay {
				background-color:#000;
			}
			.modal {
				background-color:#fff;
				padding:10px;
				border:10px solid #333;
			} 
			</style>
			<script src=\"script/livepipe/livepipe.js\" type=\"text/javascript\"></script>
			<script src=\"script/livepipe/window.js\" type=\"text/javascript\"></script>
			<script src=\"script/niftyplayer.js.php\" type=\"text/javascript\"></script>
			<script>
				var modal = new Control.Modal($('modalcontent').innerHTML,{
					overlayOpacity: 0.75,
					className: 'modal',
					fade: true,
					width: 300,
					afterOpen: function(){
						$('modalcontent').update('');
						$playercontent
					},
					afterClose: function(){
						this.destroy();
					}
				});
				modal.container.insert('<div id=\'player\'></div>');  
				modal.open();
			</script>";
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
	private function initializeContent() {
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
}




?>