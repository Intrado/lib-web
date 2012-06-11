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
				// subtype tells us if it's a plain or html email message
		
		$subtype = "html";
		if (isset($this->args['subtype']))
			$subtype = $this->args['subtype'];
		
		// style - added into form.css.php in advanced message editor section
			
		// textarea for message bits
		$textarea = '
			<div class="controlcontainer">
				<textarea id="'.$n.'" name="'.$n.'" class="messagearea"/>'.escapehtml($value).'</textarea>
				<div id="'.$n.'-htmleditor"></div>
		';
		if ($subtype == "plain" && isset($this->args['spellcheck']) && $this->args['spellcheck']) {
			$textarea .= '<div>' . action_link(_L("Spell Check"), "spellcheck", null, '(new spellChecker($(\''.$n.'\')) ).openChecker();') . '</div>';
		}
		$textarea .= '</div>';
		
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
									 var htmleditor = getHtmlEditorObject();
									 saveHtmlEditorContent(htmleditor);
								}"). '					
					</div>
				</div>
			</div>';
		
		// main containers
		$str = '
			<div class="email">
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
		$js = "";
		
		// subtype tells us if it's a plain or html email message
		$subtype = "html";
		if (isset($this->args['subtype']))
			$subtype = $this->args['subtype'];
		
		if ($subtype == "html") {
			// set up the controls in the form and initialize any event listeners
			$js .= '
					document.observe("dom:loaded", setupHtmlTextArea("'.$n.'"));';
		} else if ($subtype == "plain") {
			//plain
		}
		
		return $js;
	}
	
	function renderJavascriptLibraries() {
		global $USER;
		
		$subtype = "html";
		if (isset($this->args['subtype']))
			$subtype = $this->args['subtype'];
		
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
		
		if ($subtype == "plain" && isset($this->args['spellcheck']) && $this->args['spellcheck']) {
			$str .= '<script src="script/speller/spellChecker.js"></script>';
		}
		
		return $str;
	}
}
?>