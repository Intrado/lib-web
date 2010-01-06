<?
class AudioUpload extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		if(!is_array($value) || empty($value)) {
			$value = "";
		} else {
			$value = json_encode($value);
		}
		
		$str = '
			<input id="' . $n . '" name="' . $n . '" type="hidden" value="' . escapehtml($value) . '"/>
			<div id="uploadedfiles"></div>
			<div id="upload_process" style="display: none;"><img src="img/ajax-loader.gif" /></div>
			<iframe id="'.$n.'my_attach" src="uploadaudio.php?formname='.$this->form->name.'&itemname='.$n.'" style="border:0;"></iframe>
			<div id="uploaderror"></div>
			';
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$isaudio = isset($this->args['type']) && $this->args['type'] == 'audio' ? 'true' : 'false';
		
		$str = '<script>
			function startAudioUpload(){
				$(\'upload_process\').show();	
				return true;
			}
			
			function stopAudioUpload(id,name,size,errormessage, formname, itemname) {
				if (!formname || !itemname) {
					return;
				}
				// stopAudioUpload() is called automatically when the iframe is loaded, which may be before document.formvars is initialized by form_load().
				// In that case, just return.
				if (!document.formvars || !document.formvars[formname])
					return;
					
				setTimeout ("var uploadprocess = $(\'upload_process\'); if (uploadprocess) uploadprocess.hide();", 500 );
				
				var values = {};
				var fieldelement = $(itemname);
				if (!fieldelement)
					return;
				var field = fieldelement.value;
				if(field != "") 
					values = field.evalJSON();
				if(id && name && size && !errormessage) {
					values[id] = {"size":size,"name":name};
				}
				
				
				var str = "";
				var uploadedfiles = $("uploadedfiles").update();
				
				fieldelement.value = $H(values).toJSON();
				
				$("uploaderror").update(errormessage);
				form_do_validation($(formname), fieldelement);
				return true;
			}
		</script>';
		return $str;
	}
}

?>
