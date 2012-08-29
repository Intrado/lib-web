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
		if (isset($this->args['enablefieldinserts']) && !$this->args['enablefieldinserts'])
			$enableFieldInserts = false;
		
		$messagegroupid = (isset($this->args['messagegroupid'])?$this->args['messagegroupid']:false);
		
		// style - added into form.css.php in advanced message editor section
		
		// textarea for message bits
		$textarea = '
			<div class="controlcontainer">
				<div>Text to speech</div>
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
		foreach(FieldMap::getAuthorizeFieldInsertNames() as $field) {
			$datafieldinsert .= '<option value="' . $field . '">' . $field . '</option>';
		}
		$datafieldinsert .=	'</select>
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
		
		// main containers
		// NOTE: audio library and uploadaudio only work when a messagegroup id is provided
		$str = '
			<div class="phone">';
		
		// if there are additional tools available, show them first
		if ($USER->authorize('starteasy') || $messagegroupid ) {
			$str .= '
				<div class="maincontainerleft">
					'.($USER->authorize('starteasy')?$voicerecorder:"").'
					'.($messagegroupid?$audioupload:"").'
					'.($messagegroupid?$audiolibrary:"").'
				</div>';
		}
		
		// Drop in the tts textarea ...
		$str .= '<div class="maincontainerleft">
					'.$textarea.'
				</div>';

		// if fieldinserts are available, show them below the textarea ...
		if ($USER->authorize('starteasy') || $enableFieldInserts) {
			$str .= '
				<div class="maincontainerleft">
					'.($enableFieldInserts?$datafieldinsert:"").'
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
				$str .= 'setupVoiceRecorder("'.$n.'", "'.$language.'", '.$messagegroupid.', audiolibrarywidget);';
		} else if ($USER->authorize('starteasy')) {
			$str = 'setupVoiceRecorder("'.$n.'", "'.$language.'", false, null);';
		} else {
			$str = "";
		}
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		global $USER;
		$str = '
			<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript" src="script/audiolibrarywidget.js.php"></script>
			<script type="text/javascript">
				// Extend easycall class to use inner form item validator
				var VoiceRecorder = Class.create(EasyCall,{
					// update page based on ended tasks or validation errors
					handleStatus: function(type, msg) {
						var needsretry = false;
						// make sure the periodical executor is stopped
						if (this.pe)
							this.pe.stop();
				
						switch(type) {
							case "done":
								// complete the session
								this.completeSession();
								return;
				
							// clears form validation
							case "noerror":
								form_validation_display(this.containerid, "valid", "");
								break;
				
							case "notask":
								needsretry = true;
								form_validation_display(this.containerid, "error", "'.addslashes(_L('No valid request was found')).'");
								break;
				
							case "callended":
								needsretry = true;
								form_validation_display(this.containerid, "error", "'.addslashes(_L('Call ended early')).'");
								break;
				
							case "starterror":
								needsretry = true;
								form_validation_display(this.containerid, "error", "'.addslashes(_L('Couldn\'t initiate request')).'");
								break;
				
							case "saveerror":
								needsretry = true;
								form_validation_display(this.containerid, "error", "'.addslashes(_L('There was a problem saving your audio')).'");
								break;
				
							case "badphone":
								form_validation_display(this.containerid, "error", "'.addslashes(_L('Phone Number is invalid.')).'");
								break;
				
							default:
								form_validation_display(this.formitemname, "error", "Unknown end request:" + type + ", with message:" + msg);
						}
						// if we need a retry button
						if (needsretry)
							$(this.containerid).update(this.getRetryButton());
					}					
					
				});
				
				// Audio upload bits
				function startAudioUpload(itemname) {
					$(itemname + "uploaderror").update();
					$(itemname + "upload_process").show();	
					return true;
				}
				
				function stopAudioUpload(audioid, audioname, errormessage, formname, itemname) {
					if (!formname || !itemname) {
						return;
					}
					// stopAudioUpload() is called automatically when the iframe is loaded, which may be before document.formvars is initialized by form_load().
					// In that case, just return.
					if (!document.formvars || !document.formvars[formname])
						return;
						
					setTimeout ("var uploadprocess = $(\'"+itemname+ "upload_process\'); if (uploadprocess) uploadprocess.hide();", 500 );
					
					$(itemname + "uploaderror").update(errormessage);
					
					// fire an event in the parent form item so we can further handle objects in this form
					if (audioid)
						$(itemname+"-audioupload").fire("AudioUpload:complete", {"audiofileid": audioid, "audiofilename": audioname});
					
					return true;
				}
				
				// setup the event handler for new audio file uploads
				function setupAudioUpload(e, audiolibrarywidget) {
					e = $(e);
					var messagearea = e;
					var audioupload = $(e.id+"-audioupload");
					
					// listen for any new uploaded audio files so they can be inserted into the message and the library can be reloaded
					audioupload.observe("AudioUpload:complete", function(event) {
						var audiofilename = event.memo.audiofilename;
						var audiofileid = event.memo.audiofileid;
						
						// insert the audiofile into the message
						textInsert("{{" + audiofilename + ":#" + audiofileid +"}}", messagearea);
						
						// reload the audio library
						audiolibrarywidget.reload();
					});
					
				}
			
				// create a new easycall in the provided form element. audiofiles have name "name"
				function setupVoiceRecorder (e, name, messagegroupid, audiolibrarywidget) {
					e = $(e);
					var recorder = $(e.id+"-voicerecorder");
					var library = $(e.id+"-library");
					var messagearea = e;
					
					new VoiceRecorder(e, recorder, "'.Phone::format($USER->phone).'", name+" - "+curDate());
		
					// listen for any new recordings so we can insert them into the message, attach them to the message group and reload the library
					recorder.observe("EasyCall:update", function(event) {
						// dont observe any more events on this recorder
						Event.stopObserving(recorder,"EasyCall:update");
						
						// get the audiofile bits from the event
						var audiofilename = event.memo.audiofilename;
						var audiofileid = event.memo.audiofileid;

						// insert the audiofile into the message
						textInsert("{{" + audiofilename + ":#" + audiofileid +"}}", messagearea);
						
						// create a new recorder
						setupVoiceRecorder(e, name, messagegroupid, audiolibrarywidget);
						
						// assign the audiofile to this message group
						if (messagegroupid && audiolibrarywidget) {
							new Ajax.Request("ajaxaudiolibrary.php", {
								"method": "post",
								"parameters": {
									"action": "assignaudiofile",
									"id": audiofileid,
									"messagegroupid": messagegroupid
								},
								"onSuccess": function() {
									// reload the audio library
									audiolibrarywidget.reload();
								},
								"onFailure": function() {
									alert("There was a problem assigning this audiofile to this message group");
								}
							});
						}
					});
				}
				
				// set up the library of audio files for this message group, returns a reference to the widget
				function setupAudioLibrary(e, mgid) {
					e = $(e);
					var library = $(e.id+"-library");
					var messagearea = e;
					
					// keep a reference to the audio library widget so we can pass it to other methods which will call reload() on it
					var audiolibrarywidget = new AudioLibraryWidget(library, mgid);
					
					// If insert is clicked in the library, insert that audio file into the message
					library.observe("AudioLibraryWidget:ClickInsert", function(event) {
						var audiofile = event.memo.audiofile;

						// insert the audiofile into the message
						textInsert("{{" + audiofile.name + ":#" + audiofile.id +"}}", messagearea);
					});
					
					return audiolibrarywidget;
				}
			</script>';
		
		return $str;
	}
}
?>