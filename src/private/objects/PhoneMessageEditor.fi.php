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
	var $languages;
	var $defaultPhone;
	var $phoneMinDigits;
	var $phoneMaxDigits;
	var $messagegroupId;

	function PhoneMessageEditor($form, $name, $args) {
		parent::FormItem($form, $name, $args);
		global $USER;

		$this->languages = (isset($this->args['languages']) ? $this->args['languages'] : array("en" => "English"));
		$this->defaultPhone = (isset($this->args['phone'])) ? Phone::format(escapehtml($this->args['phone'])) : Phone::format($USER->phone);
		$this->phoneMinDigits = (isset($this->args['phonemindigits']) ? $this->args['phonemindigits'] : 10);
		$this->phoneMaxDigits = (isset($this->args['phonemaxdigits']) ? $this->args['phonemaxdigits'] : 10);
		$this->messagegroupId = (isset($this->args['messagegroupid'])?$this->args['messagegroupid']:false);
	}

	function render ($value) {
		global $USER;

		$n = $this->form->name."_".$this->name;

		// data fields may be disabled, but default to enabled
		$enableFieldInserts = true;
		if (isset($this->args['enablefieldinserts']))
			$enableFieldInserts = $this->args['enablefieldinserts'];

		// style - added into form.css in advanced message editor section

		// textarea for message bits
		$textarea = '
			<div>Text to speech</div>
			<div class="controlcontainer">
				<textarea id="'.$n.'" name="'.$n.'" class="messagearea"/>'.escapehtml($value).'</textarea>
			</div>';

		// this is the vertical seperator
		$seperator = '
			<img src="assets/img/icons/bullet_black.gif" />
			<img src="assets/img/icons/bullet_black.gif" />
			<img src="assets/img/icons/bullet_black.gif" />';

		// Voice recorder
		$voicerecorder = '
			<div id="'.$n.'-voicerecorder_fieldarea" name="'.$n.'-voicerecorder_fieldarea" class="controlcontainer">
				<div>'._L("Voice Recording").'</div>
				<input id="'.$n.'-easycall-widget" name="'.$n.'-easycall-widget" type="hidden" value="'.escapehtml($value).'" />
				<link rel="stylesheet" type="text/css" href="assets/css/easycall_widget.css" >
				<div id="'.$n.'-voicerecorder" name="'.$n.'-voicerecorder"></div>
				<div id="'.$n.'-voicerecorder_msg" name="'.$n.'-voicerecorder_msg"></div>
			</div>';

		// Audio upload control
		$audioupload = '
			<div class="controlcontainer">
				<div>'._L("Audio Upload").'</div>
				<div id="'.$n.'upload_process" style="display: none;"><img src="assets/img/ajax-loader.gif" /></div>
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
		if ($USER->authorize('starteasy') || $this->messagegroupId ) {
			$str .= '
				<div class="cf"></div>
				<div class="audiocontainer">
					'.($USER->authorize('starteasy')?$voicerecorder:"").'
					'.($this->messagegroupId?$audioupload:"").'
					'.($this->messagegroupId?$audiolibrary:"").'
				</div>';
		}
		$str .= '
			</div>';

		return $str;
	}

	function renderJavascript($value) {
		reset($this->languages);
		return "
			jQuery(function($){
				var form = $('#{$this->form->name}');
				var formItem = $('#{$this->form->name}_{$this->name}');
				var easyCallFormItem = $('#{$this->form->name}_{$this->name}-easycall-widget');
				var audioLibraryItem = $('#{$this->form->name}_{$this->name}-library');
				var audioLibraryWidget = null;

				// create and initialize the audio library if there is a spot for it in the dom
				if (audioLibraryItem) {
					audioLibraryWidget = setupAudioLibrary(formItem[0], audioLibraryItem[0], '{$this->messagegroupId}');
					setupAudioUpload(formItem[0], audioLibraryWidget);
				}

				// initialize the call me to record feature if there is a spot for it in the dom
				if (easyCallFormItem) {
					var easyCallOptions = {
						languages: ". json_encode($this->languages). ",
						defaultcode: '". key($this->languages). "',
						defaultphone: '{$this->defaultPhone}',
						phonemindigits: {$this->phoneMinDigits},
						phonemaxdigits: {$this->phoneMaxDigits}
					};

					setupAdvancedVoiceRecorder(form, formItem, easyCallFormItem, easyCallOptions, '{$this->messagegroupId}', audioLibraryWidget);
				}
			}(jQuery));
		";
	}

	function renderJavascriptLibraries() {
		return '
			<script type="text/javascript" src="assets/js/jquery.json-2.3.min.js"></script>
			<script type="text/javascript" src="assets/js/jquery.timer.js"></script>
			<script type="text/javascript" src="assets/js/jquery.easycall.js"></script>
			<script type="text/javascript" src="assets/js/audiolibrarywidget.js.php"></script>
			<script type="text/javascript" src="assets/js/phonemessageeditor.js"></script>';
	}
}
?>
