<?
class EmailAttach extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '
			<input id="' . $n . '" name="' . $n . '" type="hidden"></ input>  
			<table>
			<tr>
				<td></td>
				<td valign="top">
					<div id="uploadedfiles"></div>
				</td>
				</tr>
				<tr><td valign="top">
					<div id="upload_process" style="display: none;"><img src="img/ajax-loader.gif" /></div>
				</td>
					<td align="top">
						<iframe id="my_attach" src="emailattachment.php" style="width:100%;height:60px;border:0px;"></iframe>	
					</td>
				</tr>
				<tr><td>
				</td>
					<td align="top">
						<div id="uploaderror"></div>
					</td>
				</tr>	
			</table>	';
		$str .= '<script>	
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
							str += result[contentid][1] + "&nbsp;(Size: " + Math.round(result[contentid][0]/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>Remove</a><br />";
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