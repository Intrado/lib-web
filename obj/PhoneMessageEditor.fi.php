<?
/* Advanced Phone Message Editor form item
 * Purpose is to provide the user with all the tools
 * needed to create a message using all the features
 * they may need for constructed messaging.
 *
 * Possible args
 *  langcode - Language code this message is being created for
 *  messagegroupid - message group id this message belongs to
 *  enablefieldinserts - enable or disable field inserts (defaults to enabled)
 *
 * Supporting the following feature set
 * 	Record audio
 * 	Upload audo files
 * 	Insert audio recordings
 * 	Insert fields
 * 	Text-to-speech
 *
 * Requires the following objects:
 * 	FieldMap
 * 	Phone
 * 	Language
 *
 * Nickolas Heckman
 */

class PhoneMessageEditor extends FormItem {

	function render ($value) {
		global $USER;

		$n = $this->form->name."_".$this->name;

		// data fields may be disabled, but default to enabled
		$enableFieldInserts = true;
		if (isset($this->args['enablefieldinserts']))
			$enableFieldInserts = $this->args['enablefieldinserts'];

		$messagegroupid = (isset($this->args['messagegroupid'])?$this->args['messagegroupid']:false);

		// style - added into form.css in advanced message editor section

		// textarea for message bits
		$textarea = '
			<div>Text to speech</div>
			<div class="controlcontainer">
				<textarea id="'.$n.'" name="'.$n.'" class="messagearea"/>'.escapehtml($value).'</textarea>
			</div>';

		// this is the vertical seperator
		$seperator = '
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />';

		// Voice recorder
		$voicerecorder = '
			<div id="'.$n.'-voicerecorder_fieldarea" name="'.$n.'-voicerecorder_fieldarea" class="controlcontainer">
				<div>'._L("Voice Recording").'</div>
				<input id="'.$n.'-easycall-widget" name="'.$n.'-easycall-widget" type="hidden" value="'.escapehtml($value).'" />
				<link rel="stylesheet" type="text/css" href="css/easycall_widget.css" >
				<div id="'.$n.'-voicerecorder" name="'.$n.'-voicerecorder"></div>
				<div id="'.$n.'-voicerecorder_msg" name="'.$n.'-voicerecorder_msg"></div>
			</div>';

		// Audio upload control
		$audioupload = '
			<div class="controlcontainer">
				<div>'._L("Audio Upload").'</div>
				<div id="'.$n.'upload_process" style="display: none;"><img src="img/ajax-loader.gif" /></div>
				<iframe id="'.$n.'-audioupload"
					class="uploadiframe"
					src="uploadaudio.php?formname='.$this->form->name.'&itemname='.$n.'">
				</iframe>
				<div id="'.$n.'uploaderror" class="error"></div>
			</div>';

		// Audio library control
		$audiolibrary = '
			<div class="controlcontainer">
				<div>'._L("Audio Library").'</div>
				<div id="'.$n.'-library" name="'.$n.'-library" class="library"></div>
			</div>';



		// main containers
		// NOTE: audio library and uploadaudio only work when a messagegroup id is provided
		$str = '
			<div class="phone">
				<div class="maincontainerleft">
					'.$textarea.'
				</div>';

		// load date inserts if allowed
		if ($USER->authorize('starteasy') || $enableFieldInserts) {
			$str .= '<div class="fieldscontainer">';
			if ($enableFieldInserts) {
				// Data field inserts
				$datafieldinsert = '
					<div class="controlcontainer">
					<div>'._L("Data Fields").'</div>
					<div>
					<div class="datafields">
					Default&nbsp;Value:<br />
					<input id="'.$n.'datadefault" type="text" size="10" value=""/>
					</div>
					<div class="datafields">
					Data&nbsp;Field:<br />
					<select id="'.$n.'datafield">
						<option value="">-- Select a Field --</option>';

				if ($enableFieldInserts === "limited") {
					foreach(FieldMap::getAuthorizedMapNamesLike('f') as $field) {
						$datafieldinsert .= '<option value="' . $field . '">' . $field . '</option>';
					}
				} else {
					foreach(FieldMap::getAuthorizeFieldInsertNames() as $field) {
						$datafieldinsert .= '<option value="' . $field . '">' . $field . '</option>';
					}
				}
				$datafieldinsert .=	'
					</select>
					</div>
					<div class="datafieldsinsert">
					'. icon_button(_L("Insert"),"fugue/arrow_turn_180","
							sel = $('" . $n . "datafield');
							if (sel.options[sel.selectedIndex].value != '') {
							def = $('" . $n . "datadefault').value;
							textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n'));
					}"). '
					</div>
					</div>
					</div>';
				$str .= $datafieldinsert;
			}
			$str .= '</div>';
		}

		// if there are additional tools available, show them to the right
		if ($USER->authorize('starteasy') || $messagegroupid ) {
			$str .= '
				<div class="cf"></div>
				<div class="audiocontainer">
					'.($USER->authorize('starteasy')?$voicerecorder:"").'
					'.($messagegroupid?$audioupload:"").'
					'.($messagegroupid?$audiolibrary:"").'
				</div>';
		}
		$str .= '
			</div>';

		return $str;
	}

	function renderJavascript($value) {
		global $USER;
		$n = $this->form->name."_".$this->name;

		// langcode and messagegroupid should be passed as args
		$langcode = (isset($this->args['langcode'])?$this->args['langcode']:Language::getDefaultLanguageCode());
		$messagegroupid = (isset($this->args['messagegroupid'])?$this->args['messagegroupid']:false);
		$language = Language::getName($langcode);

		// set up the controls in the form and initialize any event listeners
		// NOTE: the audio upload and audio library require a messagegroup id be set
		if ($messagegroupid) {
			$str = 'var audiolibrarywidget = setupAudioLibrary("'.$n.'", "'.$messagegroupid.'");
						setupAudioUpload("'.$n.'", audiolibrarywidget);
					';
			if ($USER->authorize('starteasy'))
				$str .= 'setupAdvancedVoiceRecorder("'.$n.'", "'.$langcode.'","'.$language.'", "'.Phone::format($USER->phone).'", '.$messagegroupid.', audiolibrarywidget);';
		} else if ($USER->authorize('starteasy')) {
			$str = 'setupAdvancedVoiceRecorder("'.$n.'", "'.$langcode.'","'.$language.'", "'.Phone::format($USER->phone).'", false, null);';
		} else {
			$str = "";
		}

		return $str;
	}

	function renderJavascriptLibraries() {
		global $USER;
		$str = '
			<script type="text/javascript" src="script/audiolibrarywidget.js.php"></script>
			<script type="text/javascript" src="script/phonemessageeditor.js"></script>';

		return $str;
	}
}
?>