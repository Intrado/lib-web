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
 * Requires the following:
 * 	TODO: requirements
 * 
 * Nickolas Heckman
 */

class PhoneMessageEditor extends FormItem {
	
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		
		// get the individual bits from the value
		$jsonvalue = json_decode($value);
		
		$text = $jsonvalue->text;
		$gender = $jsonvalue->gender;
		$messagegroupid = $jsonvalue->messagegroupid;
		$langcode = $jsonvalue->langcode;
		
		// hidden form item stores the message value
		// value = {text: "...", gender: "...", messagegroupid: "...", langcode: "..."}
		$str = '<input id="' . $n . '" name="' . $n . '" type="hidden" value="' . escapehtml($value) . '"/>';
		
		// style 
		$str .= '
			<style>
				.controlcontainer {
					margin-bottom: 10px;
					white-space: nowrap;
				}
				.controlcontainer .library {
					padding: 2px;
					border: 1px dotted gray;
				}
				.controlcontainer .datafields {
					font-size: 9px;
					float: left;
				}
				.controlcontainer .datafieldsinsert {
					font-size: 9px;
					float: left;
					margin-top: 8px;
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
					width:45%; 
					margin-right:10px;
				}
				.maincontainerseperator {
					float:left; 
					width:15px; 
					margin-top:60px;
				}
				.maincontainerright {
					float:left; width:45%; 
					margin-left:10px; 
					margin-top:15px; 
					padding:6px; 
					border: 1px solid #'.$_SESSION['colorscheme']['_brandtheme2'].';
				}
			</style>';
		
		// textarea for message bits
		$textarea = '
			<div class="controlcontainer">
				<div>'._L("Phone Message").'</div>
				<textarea id="'.$n.'-messagearea" name="'.$n.'" style="height: 150px; width: 100%"/>'.escapehtml($text).'</textarea>
			</div>';
		
		// gender selection
		// TODO: some languages don't support both genders
		$gender = '
			<div class="controlcontainer">
				<div>'._L("Text-to-speech gender").'</div>
				<div class="radiobox">
					<input id="'.$n.'-female" name="'.$n.'-gender" type="radio" '.(($gender != "male")?"checked":"").'/><label for="'.$n.'-female">'._L("Female").'</label><br />
					<input id="'.$n.'-male" name="'.$n.'-gender" type="radio" '.(($gender == "male")?"checked":"").'/><label for="'.$n.'-male">'._L("Male").'</label>
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
		foreach(FieldMap::getAuthorizedMapNames() as $field) {
			$datafieldinsert .= '<option value="' . $field . '">' . $field . '</option>';
		}
		$datafieldinsert .=	'</select>
					</div>
					<div class="datafieldsinsert">
						'. icon_button(_L("Insert"),"fugue/arrow_turn_180","
								sel = $('" . $n . "datafield');
								if (sel.options[sel.selectedIndex].value != '') { 
									 def = $('" . $n . "datadefault').value;
									 textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n-messagearea'));
								}"). '					
					</div>
				</div>
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
		$n = $this->form->name."_".$this->name;
		
		// get the message group id from the value
		$jsonvalue = json_decode($value);
		$messagegroupid = $jsonvalue->messagegroupid;
		$language = Language::getName($jsonvalue->langcode);
		
		$str = '
			var audiolibrarywidget = setupAudioLibrary("'.$n.'", "'.$messagegroupid.'");
			setupVoiceRecorder("'.$n.'", "'.$language.'", "'.$messagegroupid.'", audiolibrarywidget);
			';
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		global $USER;
		$str = '
			<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript" src="script/audiolibrarywidget.js.php"></script>
			<script type="text/javascript">
			
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
					
					var values = {};
					var fieldelement = $(itemname);
					if (!fieldelement)
						return;
					var field = fieldelement.value;
					if(field != "")
						values = field.evalJSON();
					if (audioid && audioname && !errormessage) {
						var newaudio = {"id":audioid, "name":audioname};
						values[audioid] = newaudio;
						fieldelement.fire("AudioUpload:AudioUploaded", newaudio);
					}
					
					fieldelement.value = $H(values).toJSON();
					
					$(itemname + "uploaderror").update(errormessage);
					form_do_validation($(formname), fieldelement);
					return true;
				}
			
				// create a new easycall in the provided form element. audiofiles have name "name"
				function setupVoiceRecorder (e, name, messagegroupid, audiolibrarywidget) {
					e = $(e);
					var recorder = $(e.id+"-voicerecorder");
					var library = $(e.id+"-library");
					var messagearea = $(e.id+"-messagearea");
					
					new EasyCall(e, recorder, "'.Phone::format($USER->phone).'", name+" - "+curDate());
		
					recorder.observe("EasyCall:update", function(event) {
						// dont observe any more events on this recorder
						Event.stopObserving(recorder,"EasyCall:update");
						
						// get the audiofile bits from the event
						var audiofilename = event.memo.audiofilename;
						var audiofileid = event.memo.audiofileid;
						
						// insert the audiofile into the message
						textInsert("{{" + audiofilename + "}}", messagearea);
						
						// create a new recorder
						setupVoiceRecorder(e, name);
						
						// assign the audiofile to this message group
						new Ajax.Request("ajaxaudiolibrary.php", {
							"method": "post",
							"parameters": {
								"action": "assignaudiofile",
								"id": audiofileid,
								"messagegroupid": messagegroupid
							},
							"onFailure": function() {
								alert("There was a problem assigning this audiofile to message group: "+messagegroupid);
							}
						});
						
						// reload the audio library
						audiolibrarywidget.reload();
					});
				}
				
				// set up the library of audio files for this message group
				function setupAudioLibrary(e, mgid) {
					e = $(e);
					var library = $(e.id+"-library");
					var messagearea = $(e.id+"-messagearea");
					
					var audiolibrarywidget = new AudioLibraryWidget(library, mgid);
					
					library.observe("AudioLibraryWidget:ClickInsert", function(event) {
						var audiofile = event.memo.audiofile;
						textInsert("{{" + audiofile.name + "}}", messagearea);
					});
					
					return audiolibrarywidget;
				}
			</script>';
		
		return $str;
	}
}
?>