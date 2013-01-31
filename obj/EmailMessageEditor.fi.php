<?

/**
 * Advanced Email Message Editor form item
 *
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
 *
 * @todo SMK notes 2013-01-02 prototype->jquery port is needed
 */
class EmailMessageEditor extends FormItem {
	
	function render ($value) {
		$n = $this->form->name . '_' . $this->name;

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

		// SMK added empty rcieditor_scratch div as a scratch space
		// for the RCIEditor to work with for DOM processing
		$textarea .= '
				<div id="rcieditor_scratch" style="display: none;"></div>
			</div>
		';
		
		// this is the vertical seperator
		$seperator = '
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />
			<img src="img/icons/bullet_black.gif" />';
				
// SMK replaced with CKE plugin "mkfield" 2013-01-04
/*
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
*/
		
		// main containers
		$str = '
			<div class="email">
				<div class="maincontainerleft">
					'.$textarea.'
				</div>
				<div class="maincontainerseperator">
					'.$seperator.'
				</div>
			</div>';
// SMK replaced with CKE plugin "mkfield" 2013-01-04
/*
				<div class="maincontainerright">
					'.$datafieldinsert.'
				</div>
*/
		return $str;
	}

	function renderJavascript($value) {
		global $USER;
		$n = $this->form->name."_".$this->name;
		$js = "";
		
		// subtype tells us if it's a plain or html email message
		$subtype = "html";
		if (isset($this->args['subtype']))
			$subtype = $this->args['subtype'];
		
		if ($subtype == "html") {
			// set up the controls in the form and initialize any event listeners
			$js .= 'setupHtmlTextArea(\''.$n.'\', '.$USER->getSetting("hideemailtools", "false").');';
		} else if ($subtype == "plain") {
			//plain
		}
		
		return $js;
	}
	
	function renderJavascriptLibraries() {
		global $USER;

		$subtype = (isset($this->args['subtype'])) ? $this->args['subtype'] : 'html';

		// SMK added 2013-01-02 to be able to switch modalities for any FI of this type
		$editor_mode = isset($this->args['editor_mode']) ? $this->args['editor_mode'] : 'plain';

		// SMK added 2013-01-03 to make field definitions available to JS (CKE plugin mkfield)
		if ($editor_mode != 'plain') {
			$rcidata_fields = '';
			foreach(FieldMap::getAuthorizeFieldInsertNames() as $field) {
				if (strlen($rcidata_fields)) $rcidata_fields .= ',';
				$rcidata_fields .= "\"{$field}\"";
			}

			$rcidataScript = <<<END
				<script type="text/javascript" src="script/rcidata.js"></script>
				<script type="text/javascript">
					rcidata.set('customer_field_defs', Array({$rcidata_fields}));
				</script>
END;
		}
		else {
			$rcidataScript = '';
		}

		$str = <<<END
			<!-- editor mode: [{$editor_mode}] -->
			<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
			<script type="text/javascript">
				jQuery.noConflict();
			</script>
			{$rcidataScript}
			<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
			<script type="text/javascript">
				// SMK added global var 2012-12-07 to selectively enable uploaded image reduction scaling
				var htmlEditorImageScale = 600; // Max dimension for scaling
			</script>

			<script type="text/javascript" src="script/json2.js"></script>
			<script type="text/javascript" src="script/rcieditor.js"></script>
			<script type="text/javascript">
				function setupHtmlTextArea(e, hidetoolbar) {
					e = $(e);
					
					// apply the ckeditor to the textarea
					RCIEditor.applyEditor('{$editor_mode}', e, e.id + '-htmleditor');
				}
			</script>
END;

		if ($subtype == "plain" && isset($this->args['spellcheck']) && $this->args['spellcheck']) {
			$str .= '<script src="script/speller/spellChecker.js"></script>';
		}
		
		return $str;
	}
}
?>
