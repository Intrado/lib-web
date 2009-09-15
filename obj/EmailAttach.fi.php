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
			<iframe id="'.$n.'my_attach" src="emailattachment.php" style="width:100%;height:26px;border:0px;" FRAMEBORDER="0" MARGINWIDTH="0px" MARGINHEIGHT="0px"></iframe>
			<div id="uploaderror"></div>
			';
		$str .= '
			<script>
				function startUpload(){
					$(\'upload_process\').show();	
					return true;
				}
				function stopUpload(id,name,size,errormessage){
					setTimeout ("$(\'upload_process\').hide();", 500 );
					var values = {};
					var field = $("' . $n . '").value;
					if(field != "") 
						values = field.evalJSON();
					if(id && name && size && !errormessage) {
						values[id] = {"size":size,"name":name};
					}
					var str = "";
					for(var contentid in values) {
						var onclick = "removeAttachment(" + contentid + ");";
				 		str += "<a href=\"emailattachment.php?name=" + encodeURIComponent(values[contentid][\'name\']) + "&id=" + contentid + "\">" + values[contentid][\'name\'] + "</a>&nbsp;(Size: " + Math.round(values[contentid][\'size\']/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>Remove</a><br />";				
					}
				
					$("' . $n . '").value = $H(values).toJSON();			
					$("uploadedfiles").innerHTML = str;	
					$("uploaderror").innerHTML = errormessage;
					form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
					return true;
				}
				
				function removeAttachment(id) {
					var values = $("' . $n . '").value.evalJSON();
					var str = "";
					Object.keys(values).each(function (contentid) {
						if(contentid != id) {
							var onclick = "removeAttachment(" + contentid + ");";
				 			str += "<a href=\"emailattachment.php?name=" + encodeURIComponent(values[contentid][\'name\']) + "&id=" + contentid + "\">" + values[contentid][\'name\'] + "</a>&nbsp;(Size: " + Math.round(values[contentid][\'size\']/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>Remove</a><br />";									
						} else
							values[contentid] = undefined;
					});
					$("' . $n . '").value = Object.toJSON(values);
					$("uploadedfiles").innerHTML = str;			
					form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
				}
			</script>';
		return $str;
	}
}

?>
