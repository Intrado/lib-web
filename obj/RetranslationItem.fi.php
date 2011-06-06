<?

/* Retranslation widget
 * Shows the translated text, giving the user the ability to disable the language and preview a re-translated
 * english version
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
		
		$theme2 = "#". $_SESSION['colorscheme']['_brandtheme2'];
		$str = '
			<style type="text/css">
				.retranslateitems {
				}
				.retranslateitems .message {
					border: 1px solid '. $theme2 .';
					overflow: auto;
					max-height: 150px;
					padding: 6px;
				}
				.retranslateitems .englishversion {
					border: 1px solid gray;
					color: gray;
					overflow: auto;
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
				<div id="'.$n.'-retranslate" style="display:none; margin-top:6px">
					<div>
						Re-translated English value:
					</div>
					
					<div id="'.$n.'-englishversion" class="englishversion">
						<img src="img/ajax-loader.gif" />
					</div>
				</div>
				'. icon_button(_L("Show in English"), "fugue/magnifier", "toEnglishButton('$n', '$langcode', ". ($ishtml?"true":"false") .")", null, 'id="'. $n . '-toenglishbutton"') .'
				
			</div>
			<div id="'.$n.'-unchecked" name="'.$n.'" style="display: '. ($value ? 'none' : 'block') .'">
				'. $disabledmessage .'
			</div>';
		
		return $str;
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
					
					new Ajax.Request("translate.php", {
						method:"post",
						parameters: {"text": makeTranslatableString(message.innerHTML), "language": langcode},
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
