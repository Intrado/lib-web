<?
/* Advanced Phone Message Editor form item
 * Purpose is to provide the user with all the tools
 * needed to create a message using all the features
 * they may need for constructed messaging.
 * 
 * Supporting the following feature set
 * 	Record audio
 * 	Upload audo files
 * 	Insert audio recordings
 * 	Insert fields
 * 	Text-to-speech
 * 
 * Nickolas Heckman
 */

class PhoneMessageEditor extends FormItem {
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		
		// hidden form item stores the message value
		$str = '<input id="' . $n . '" name="' . $n . '" type="hidden" value="' . escapehtml($value) . '"/>';
		
		// style 
		$str .= '
			<style>
				.controlcontainer {
					margin-bottom: 6px;
					white-space: nowrap;
				}
				.controlcontainer .content {
					border: 1px dotted black;
				}
				.uploadiframe {
					overflow: hidden;
					width: 100%;
					border: 0;
					margin: 0;
					padding: 0;
					height: 2em;
				}
				.maincontainerleft {
					float:left; 
					width:40%; 
					margin-right:10px;
				}
				.maincontainerseperator {
					float:left; 
					width:15px; 
					margin-top:60px;
				}
				.maincontainerright {
					float:left; width:40%; 
					margin-left:10px; 
					margin-top:15px; 
					padding:6px; 
					border:2px solid black
				}
			</style>';
		
		// textarea for message bits
		$textarea = '
			<div class="controlcontainer">
				<div>'._L("Phone Message").'</div>
				<textarea id="'.$n.'-messagearea" name="'.$n.'" style="height: 150px; width: 100%"/>'.escapehtml($value).'</textarea>
			</div>';
		
		// gender selection
		// TODO: some languages don't support both genders
		$gender = '
			<div class="controlcontainer">
				<div>'._L("Text-to-speech gender").'</div>
				<div class="radiobox">
					<input id="'.$n.'-female" name="'.$n.'-female" type="radio"/><label for="'.$n.'_female">'._L("Female").'</label><br />
					<input id="'.$n.'-male" name="'.$n.'-male" type="radio"/><label for="'.$n.'_male">'._L("Male").'</label>
				</div>
			</div>';
		
		// preview button
		$preview = '
			<div class="controlcontainer">
				'.icon_button(_L("Play"),"fugue/control",null,null,"id=\"".$n."-play\"").'
			</div>';
		
		// this is the vertical seperator
		$seperator = '
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />';
		
		// Voice recorder
		$voicerecorder = '
			<div class="controlcontainer">
				<div>'._L("Voice Recording").'</div>
				<div id="'.$n.'-voicerecorder" name="'.$n.'-voicerecorder"></div>
			</div>';
		
		// Audio upload control
		$audioupload = '
			<div class="controlcontainer">
				<div>'._L("Audio Upload").'</div>
				<div id="'.$n.'upload_process" style="display: none;"><img src="img/ajax-loader.gif" /></div>
				<iframe id="'.$n.'my_attach" 
					class="uploadiframe" 
					src="uploadaudio.php?formname='.$this->form->name.'&itemname='.$n.'-audioupload">
				</iframe>
				<div id="'.$n.'uploaderror"></div>
			</div>';
		
		// Audio library control
		$audiolibrary = '
			<div class="controlcontainer">
				<div>'._L("Audio Library").'</div>
				<div id="'.$n.'-library" name="'.$n.'-library" class="content">TODO: audio library here</div>
			</div>';
		
		// Data field inserts
		$datafieldinsert = '
			<div class="controlcontainer">
				<div>'._L("Data Fields").'</div>
				<div>TODO: data fields here</div>
			</div>';
		
		// main containers
		$str .= '
			<div>
				<div class="maincontainerleft">
					'.$textarea.'
					'.$gender.'
					'.$preview.'
				</div>
				<div class="maincontainerseperator">
					'.$seperator.'
				</div>
				<div class="maincontainerright">
					'.$voicerecorder.'
					'.$audioupload.'
					'.$audiolibrary.'
					'.$datafieldinsert.'
				</div>
			</div>';
		
		return $str;
	}

	function renderJavascript($value) {
		$str = '
			setupVoiceRecorder("'.$this->form->name."_".$this->name.'", "todo:name");';
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		global $USER;
		$str = AudioUpload::renderJavascriptLibraries();
		$str .= '
			<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript">
			
				function setupVoiceRecorder (e, name) {
					e = $(e);
					var recorder = $(e.id+"-voicerecorder");
					var library = $(e.id+"-library");
					var messagearea = $(e.id+"-messagearea");
					
					new EasyCall(e, recorder, "'.Phone::format($USER->phone).'", name);
		
					recorder.observe("EasyCall:update", function(event) {
						// TODO: insert the audiofile into the library and the message body
						
						alert(event.memo.audiofileid);
						Event.stopObserving(recorder,"EasyCall:update");
						
						setupVoiceRecorder(e, name);
					});
				}
			</script>';
		
		return $str;
	}
}
?>