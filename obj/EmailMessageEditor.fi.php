<?
/* Advanced Email Message Editor form item
 * Purpose is to provide the user with the tools
 * needed to create an email message containing
 * dynamic content.
 * 
 * Supporting the following feature set
 * 	Insert fields
 * 
 * Requires the following objects:
 * 	FieldMap
 * 	
 * Nickolas Heckman
 */

class EmailMessageEditor extends FormItem {
	
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		
		// style 
		$str = '
			<style>
				.controlcontainer {
					margin-bottom: 10px;
					white-space: nowrap;
				}
				.controlcontainer .messagearea {
					height: 205px; 
					width: 100%
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
				.maincontainerleft {
					float:left; 
					width:65%; 
					margin-right:10px;
				}
				.maincontainerseperator {
					float:left; 
					width:15px; 
					margin-top:50px;
				}
				.maincontainerright {
					float:left; width:20%; 
					margin-left:10px; 
					margin-top:15px; 
					padding:6px; 
					border: 1px solid #'.$_SESSION['colorscheme']['_brandtheme2'].';
				}
			</style>';
			
		// textarea for message bits
		$textarea = '
			<div class="controlcontainer">
				<div>'._L("Email Message").'</div>
				<textarea id="'.$n.'" name="'.$n.'" class="messagearea"/>'.escapehtml($value).'</textarea>
				<div id="'.$n.'-htmleditor"></div>
			</div>';
		
		// this is the vertical seperator
		$seperator = '
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />';
				
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
									 textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n'));
									 var htmleditor = getHtmlEditorObject();
									 saveHtmlEditorContent(htmleditor);
								}"). '					
					</div>
				</div>
			</div>';
		
		// main containers
		$str .= '
			<div>
				<div class="maincontainerleft">
					'.$textarea.'
				</div>
				<div class="maincontainerseperator">
					'.$seperator.'
				</div>
				<div class="maincontainerright">
					'.$datafieldinsert.'
				</div>
			</div>';
		
		return $str;
	}

	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		
		// set up the controls in the form and initialize any event listeners
		$str = '
				document.observe("dom:loaded", setupHtmlTextArea("'.$n.'"));';
		
		// subtype tells us if it's a plain or html email message
		$subtype = "html";
		if (isset($this->args['subtype']))
			$subtype = $this->args['subtype'];
			
		return ($subtype != "plain")?$str:"";
	}
	
	function renderJavascriptLibraries() {
		global $USER;
		$str = '
			<script type="text/javascript" src="script/ckeditor/ckeditor_basic.js"></script>
			<script type="text/javascript" src="script/htmleditor.js"></script>
			<script type="text/javascript">
				function setupHtmlTextArea(e) {
					e = $(e);
					
					// add the ckeditor to the textarea
					applyHtmlEditor(e, true, e.id+"-htmleditor");

					// set up a keytimer to save content and validate
					var htmlTextArea_keytimer = null;
					registerHtmlEditorKeyListener(function (event) {
						window.clearTimeout(htmlTextArea_keytimer);
						var htmleditor = getHtmlEditorObject();
						htmlTextArea_keytimer = window.setTimeout(function() {
							saveHtmlEditorContent(htmleditor);
							form_do_validation(htmleditor.currenttextarea.up("form"), htmleditor.currenttextarea);
						}, 500);
					});
				}
			</script>';
		
		return $str;
	}
}
?>