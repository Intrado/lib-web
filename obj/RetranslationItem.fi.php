<?

/* Retranslation widget
 * Shows the translated text, giving the user the ability to disable the language and preview a re-translated
 * english version
 * 
 * Possible args
 *  langcode - Language code this message is being created for (REQUIRED)
 *  type - phone, voice, email?
 *  ishtml - is the source text html?
 *  gender - if this is audio, what gender should tts render in?
 *  subject - if it's an email. this is the subject
 *  fromname - if it's an email. this is the from address
 *  from - if it's an email. this is the name of who it is from
 *  
 * Supporting the following feature set
 *  view translated text
 *  view above text translated back into english
 *  preview formatted translated text (audio or email)
 * 
 * - Nickolas
 */

class RetranslationItem extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = false;
	
	function escapeFieldInserts($text) {
		return str_replace(">>", "&#062;&#062;", str_replace("<<", "&#060;&#060;", $text));
	}
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		// if the message has html content, set this flag. Otherwise the html won't get escaped properly.
		if (isset($this->args['ishtml']))
			$ishtml = $this->args['ishtml']?true:false;
		else
			$ishtml = false;
		
		// the language of the message text
		$langcode = $this->args['langcode'];
		
		// the message.
		$message = ($ishtml?$this->escapeFieldInserts($this->args['message']):escapehtml($this->args['message']));
		
		// What text to display if the selected language is disabled
		$disabledmessage = $this->args['disabledmessage'];
		
		// the type controls how preview works
		switch ($this->args['type']) {
			case "voice":
			case "phone":
				$previewname = escapehtml(_L("Play"));
				$previewicon = "fugue/control";
				break;
			case "email":
				$previewname = escapehtml(_L("Preview"));
				$previewicon = "email_open";
				break;
			default:
				$previewname = false;
		}

		$theme2 = "#". $_SESSION['colorscheme']['_brandtheme2'];
		$str = '
			<style type="text/css">
				.retranslateitems {
				}
				.retranslateitems .message {
					border: 1px solid '. $theme2 .';
					overflow: auto;
					width: 70%
					max-height: 150px;
					padding: 6px;
				}
				.retranslateitems .englishversion {
					border: 1px solid gray;
					color: gray;
					overflow: auto;
					width: 70%
					max-height: 150px;
					padding: 6px;
				}
				
			</style>
			<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value ? 'checked' : '').'
				onclick="showhidepreview(\''. $n .'\')"/>
			<div id="'.$n.'-checked" name="'.$n.'" class="retranslateitems" style="display: '. ($value ? 'block' : 'none') .'">
				<div id="'.$n.'-message" class="message">
					'. $message .'
				</div>
				'. icon_button(_L("Show in English"), "fugue/magnifier", "toEnglishButton('$n', '$langcode', ". ($ishtml?"true":"false") .")", null, 'id="'. $n . '-toenglishbutton"') .'
				'. ($previewname? 
					icon_button($previewname, $previewicon, null, null, 'id="'. $n . '-previewbutton"'): ""). '
				<div id="'.$n.'-retranslate" style="clear:both; display:none; margin-top:6px">
					<div>
						Re-translated English value:
					</div>
					
					<div id="'.$n.'-englishversion" class="englishversion">
						<img src="img/ajax-loader.gif" />
					</div>
				</div>
				
			</div>
			<div id="'.$n.'-unchecked" name="'.$n.'" style="display: '. ($value ? 'none' : 'block') .'">
				'. $disabledmessage .'
			</div>';
		
		return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
	
		// the type controls how preview works
		switch ($this->args['type']) {
			case "voice":
			case "phone":
				$parameters = json_encode(array(
					"language" => $this->args['langcode'], 
					"gender" => $this->args['gender'], 
					"text" => $this->args['message']));
				break;
			case "email":
				$parameters = json_encode(array(
					"fromname" => $this->args['fromname'], 
					"from" => $this->args['from'], 
					"subject" => $this->args['subject'], 
					"language" => $this->args['langcode'], 
					"subtype" => ($this->args['ishtml']?"html":"plain"), 
					"text" => $this->args['message']));
				break;
			default:
				$parameters = false;
		}
		
		if ($parameters) {
			return '
				$("'. $n . '-previewbutton").observe("click", function (event) {
					showPreview('.$parameters.');
				});';
		}
	}
	
	function renderJavascriptLibraries() {
		
		$str = '
			<script type="text/javascript">
				// show/hide the translated text based on the enabled status
				function showhidepreview(e) {
					e = $(e);
					if (e.checked) {
						$(e.id + "-checked").show();
						$(e.id + "-unchecked").hide();
					} else {
						$(e.id + "-checked").hide();
						$(e.id + "-unchecked").show();
					}
				}
				
				// show a text area indicating the re-translated back to english value
				function toEnglishButton(e, langcode, ishtml) {
					var button = $(e + "-toenglishbutton");
					var retranslate = $(e + "-retranslate");
					var englishversion = $(e + "-englishversion");
					var message = $(e + "-message");
					
					button.hide();
					retranslate.show();
					
					var text = makeTranslatableString(message.innerHTML);
					if(text != text.substring(0, 5000)){
						text = text.substring(0, 5000);
						alert("' . _L('The message is too long. Only the first 5000 characters are submitted for translation.') . '");
					}
					
					new Ajax.Request("translate.php", {
						method:"post",
						parameters: {"text": text, "language": langcode},
						onSuccess: function(result) {
								var data = result.responseJSON;
								if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
									englishversion.update("'. _L("Error getting English translation") .'");
								
								var translatedtext = data.responseData.translatedText;
								if (ishtml)
									translatedtext = translatedtext.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
								else
									translatedtext = translatedtext.escapeHTML();
								
								englishversion.update(translatedtext);
							},
						onFailure: function(result) {
								englishversion.update("'. _L("Error getting English translation") .'");
							}
					});
				}
			</script>';
		
			return $str;
	}
}
?>
