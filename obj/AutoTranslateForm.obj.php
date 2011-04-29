<?
class AutoTranslateForm extends Form {
	function escapeFieldInserts($text) {
		return str_replace(">>", "&#062;&#062;", str_replace("<<", "&#060;&#060;", $text));
	}
	
	// generate the form
	function AutoTranslateForm($name, $title, $sourcetext, $msgtype) {
		global $TRANSLATIONLANGUAGECODES;
		
		static $translations = false;
		static $translationlanguages = false;

		$warning = "";
		if(mb_strlen($sourcetext) > 4000) {
			$warning = _L('Warning. Only the first 4000 characters are translated.');
		}

		//Get available languages
		switch ($msgtype) {
			case "phone":
				$translationlanguages = Voice::getTTSLanguageMap();
				$voices = Voice::getTTSVoices();
				break;
			
			default:
				$alllanguages = Language::getLanguageMap();
				$translationlanguages = array_intersect_key($alllanguages, array_flip($TRANSLATIONLANGUAGECODES));
				$voices = array();
		}
		unset($translationlanguages['en']);
		$translationlanguagecodes = array_keys($translationlanguages);
		$translations = translate_fromenglish($sourcetext,$translationlanguagecodes);

		// Form Fields.
		$formdata = array($title);

		if ($warning)
			$formdata["warning"] = array(
				"label" => _L("Warning"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium; color: red">'.escapehtml($warning).'</div><br>'),
				"helpstep" => 1
			);

		$formdata["englishtext"] = array(
			"label" => _L("English"),
			"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'.$this->escapeFieldInserts($sourcetext).'</div><br>'),
			"helpstep" => 1
		);

		// Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
				"label" => _L("Info"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'._L('No Translations Available').'</div><br>'),
				"helpstep" => 2
			);
		} else {
			if(is_array($translations)){
				foreach($translations as $obj){
					$languagecode = array_shift($translationlanguagecodes);
					$formdata[] = Language::getName($languagecode);
					$formdata[$languagecode] = array(
						"label" => _L("Enabled"),
						"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
						"value" => 1,
						"validators" => array(),
						"control" => array("CheckBoxWithHtmlPreview", "checkedhtml" => $this->escapeFieldInserts($obj->responseData->translatedText), "uncheckedhtml" => addslashes(_L("People tagged with this language will receive the English version."))),
						"helpstep" => 2
					);
				}
			} else {
				$languagecode = reset($translationlanguagecodes);
					$formdata[] = Language::getName($languagecode);
				$formdata[$languagecode] = array(
						"label" => _L("Enabled"),
						"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
						"value" => 1,
						"validators" => array(),
						"control" => array("CheckBoxWithHtmlPreview", "checkedhtml" => $this->escapeFieldInserts($translations->translatedText), "uncheckedhtml" => addslashes(_L("People tagged with this language will receive the English version."))),
						"helpstep" => 2
					);
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding">
							<span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">
								'._L('Translation powered by').'<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="' . (isset($_SERVER['HTTPS'])?"https":"http") . '://www.google.com/uds/css/small-logo.png">
							</span>
						</div>
					</div>
				'),
				"helpstep" => 2
			);
		}

		$helpsteps = array(
			_L("This is the message that all contacts will receive if they do not have any other language message specified"),
			_L("This is an automated translation powered by Google Translate. Please note that although machine translation is always improving, it is not perfect yet. You can try reverse translation for an idea of how well your message was translated.")
		);

		parent::Form($name, $formdata, $helpsteps);
	}
}