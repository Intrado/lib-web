<?
class EmailAttach extends FormItem {
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
			<iframe id="'.$n.'my_attach" class="UploadIFrame" src="emailattachment.php?formname='.$this->form->name.'&itemname='.$n.'" style="border:0; padding: 0; margin:0"></iframe>
			<div id="uploaderror"></div>
			';
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '<script>
			function startUpload(){
				$(\'upload_process\').show();	
				return true;
			}
			
			function stopUpload(id,name,size,errormessage, formname, itemname) {
				if (!formname || !itemname) {
					return;
				}
				// stopUpload() is called automatically when the iframe is loaded, which may be before document.formvars is initialized by form_load().
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
				
				for(var contentid in values) {
					var content = values[contentid];
					
					var downloadlink = new Element("a", {"href": "emailattachment.php?id="  + contentid +  "&name=" + encodeURIComponent(encodeURIComponent(content.name))});
					
					downloadlink.update(content.name);
					
					var sizeinfo = "&nbsp;(Size: " + Math.round(content.size/1024) + "k)&nbsp;";
					
					var removelink = new Element("a", {"href":"#"});
					
					removelink.update("Remove");
					
					removelink.observe("click", function(event, contentid, formname, itemname) {
						event.stop();
						removeAttachment(contentid, formname, itemname);
					}.bindAsEventListener(uploadedfiles, contentid, formname, itemname));
					uploadedfiles.insert(downloadlink).insert(sizeinfo).insert(removelink).insert("<br/>");				 		
				}
			
				fieldelement.value = $H(values).toJSON();
				
				$("uploaderror").update(errormessage);
				form_do_validation($(formname), fieldelement);
				return true;
			}
			
			function removeAttachment(id, formname, itemname) {
				if (!formname || !itemname)
					return;
				var values = $(itemname).value.evalJSON();
				var uploadedfiles = $("uploadedfiles").update();
				Object.keys(values).each(function (contentid) {
					if(contentid != id) {
						var content = values[contentid];
						var contentname = content.name;
						
						uploadedfiles.insert(
							new Element("a", {"href": "emailattachment.php?id=" + contentid + "&name=" + encodeURIComponent(encodeURIComponent(contentname))}).insert(contentname)
						).insert(
							"&nbsp;(Size: " + Math.round(content.size / 1024) + "k)&nbsp;"
						).insert(
							new Element("a", {"href": "#"}).insert("'.addslashes(_L("Remove")).'").observe("click", function(event, contentid, formname, itemname) {
								event.stop();
								removeAttachment(contentid, formname, itemname);
							}.bindAsEventListener(uploadedfiles, contentid, formname, itemname))
						).insert(
							"<br/>"
						);
					} else {
						values[contentid] = undefined;
					}
				});
				$(itemname).value = Object.toJSON(values);
				form_do_validation($(formname), $(itemname));
			}
		</script>';
		return $str;
	}
}

?>
