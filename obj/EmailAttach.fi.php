<?
class EmailAttach extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '
			<input id="' . $n . '" name="' . $n . '" type="hidden"></ input>  
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
				function stopUpload(success,transport,errormessage){
					setTimeout ("$(\'upload_process\').hide();", 500 );
					var result = transport.evalJSON();
					var str = "";
					var contentids = Array();
					var i = 0;
					for(var contentid in result) {
						var onclick = "removeAttachment(" + contentid + ");";	
						str += result[contentid][1] + "&nbsp;('.addslashes(_L('Size')).': " + Math.round(result[contentid][0]/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>'.addslashes(_L('Remove')).'</a><br />";
						contentids[i] = contentid;
						i++;
					}
					$("' . $n . '").value = contentids.toJSON();
					$("uploadedfiles").innerHTML = str;	
					$("uploaderror").innerHTML = errormessage;
					form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
					return true;
				}
				
				function removeAttachment(contentid) {
					new Ajax.Request(\'emailattachment.php?delete=\' + contentid, {
						method:\'get\',
						onSuccess: function (transport) {
							var result = transport.responseJSON;
							var str = "";
							var contentids = Array();
							var i = 0;
							for(var contentid in result) {
								var onclick = "removeAttachment(" + contentid + ");";
								str += result[contentid][1] + "&nbsp;(Size: " + Math.round(result[contentid][0]/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>Remove</a><br />";
								contentids[i] = contentid;
								i++;
							}
							$("' . $n . '").value = contentids.toJSON();
							$("uploadedfiles").innerHTML = str;			
							form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
						}
					});
				}
			</script>';
		return $str;
	}
}

?>
