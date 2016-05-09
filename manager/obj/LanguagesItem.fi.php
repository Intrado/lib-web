<?

class LanguagesItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		$str = "
				<input id='$n' name='$n' type='hidden' value='$value' />
				<div id='$n-removelang' style='display:none'>" . icon_button("Remove", "delete") . "</div>
				<div id='$n-disp'></div>
				<table>
					<tr><td style=\"border: 1px solid black;\">
						Language Lookup:<br />
						<table><tr><td>
							<select id='newlanginputselect' onchange='languageselect();'>
								<option value=0> -- Select Common Language -- </option>";
		foreach ($this->args['googlelangs'] as $code => $googlang) {
			$ttsLangSup = '';
			if (in_array($code, $this->args['ttslangs']))
				$ttsLangSup .= " (TTS Support)";
			$str .= "<option value='" . str_pad($code,3) . " $googlang' >$googlang $ttsLangSup</option>";
		}
		$str .= '
							</select>
							</td>
							<td>&nbsp;&nbsp;&nbsp;or&nbsp;&nbsp;&nbsp;</td>
							<td><input id="searchbox" type="text" size="30" /></td>
							<td>' . icon_button("Search", "magnifier","searchlanguages();") . '</td>
						</tr></table>
						<table id="searchresult" style=""><tr><td></td></tr></table>
						<table style="display:inline;"><tr><td>Code: 
							<div style="display:inline;font-weight: bold;" id="newlangcodedisp">N/A</div> Name: 
							<input id="newlangcode" type="hidden" maxlength="50" size="25" />
							<input id="newlanginput" type="text" maxlength="50" size="25" />
							</td>
							<td>' . icon_button("Add", "add","changelanguage('$n')") . '</td>
						</tr></table>
					</td></tr>
				</table>
				';
		return $str;
	}
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		$str = "
			function updatelanguage(code,name) {
				var langs = \$H($('$n').value.evalJSON(true));
				langs.set(code.strip(),name.strip());
				$('$n').value = Object.toJSON(langs);	
			}
			function removelanguage(code) {
				var langs = \$H($('$n').value.evalJSON(true));
				langs.unset(code.strip());
				$('$n').value = Object.toJSON(langs);
				renderlanguages();
			}
			function renderlanguages() {
				var ttslangs = " . json_encode($this->args['ttslangs']) . ";
				var smslangs = " . json_encode($this->args['smslangs']) . ";
				var googlelangs = " . json_encode(array_keys($this->args['googlelangs'])) . ";
				var langs = \$H($('$n').value.evalJSON(true));
				var table = new Element('table',{'style':'text-align:left;'});
				var tableheader = new Element('tr');
				tableheader.insert(new Element('th').insert('Code'));
				tableheader.insert(new Element('th',{'style':'border-left: 1px dashed black;border-right: 1px dashed black;padding: .15em .2em;'}).insert('TTS'));
				tableheader.insert(new Element('th',{'style':'border-left: 1px dashed black;border-right: 1px dashed black;padding: .15em .2em;'}).insert('SMS'));
				tableheader.insert(new Element('th',{'style':'border-right: 1px dashed black;'}).insert('Translatable'));
				tableheader.insert(new Element('th').insert('Name'));
				table.insert(tableheader);
				langs.each(function(lang) {

					var tablecontent = new Element('tr');
					var input = new Element('input', { 'type': 'text', 'value': lang.value});
					
					if (lang.key != 'en') {
						input.observe('change',function(e) {
							updatelanguage(lang.key,e.element().getValue());
						});
					} else {
						input.disabled = true;
					}
					tablecontent.insert(new Element('td',{'style':'text-align:right;'}).insert(lang.key));
					var tts = new Element('td',{'style':'text-align:center;border-left: 1px dashed black;border-right: 1px dashed black;'});
					if (ttslangs.indexOf(lang.key) != -1)
						tts.insert(new Element('img', {'src':'img/icons/accept.png'}));
					tablecontent.insert(tts);
					var smslng = new Element('td',{'style':'text-align:center;border-left: 1px dashed black;border-right: 1px dashed black;'});
					if (lang.key == 'en' || smslangs.indexOf(lang.key) != -1)
						smslng.insert(new Element('img', {'src':'img/icons/accept.png'}));
					tablecontent.insert(smslng);
					var translatable = new Element('td',{'style':'text-align:center;border-right: 1px dashed black;'});
					if (googlelangs.indexOf(lang.key) != -1)
						translatable.insert(new Element('img', {'src':'img/icons/accept.png'}));
					tablecontent.insert(translatable);
					tablecontent.insert(new Element('td').insert(input));

					if (lang.key != 'en') {
						var removebutton = new Element('div').update($('$n-removelang').innerHTML);
						removebutton.observe('click',function(e) {
							removelanguage(lang.key);
						});
						tablecontent.insert(new Element('td').insert(removebutton));		
					}
					table.insert(tablecontent);
				});	
				$('$n-disp').update(table);		
				form_do_validation($('{$this->form->name}'), $('$n'));
			}
			
			document.observe('dom:loaded', renderlanguages);
				
			function languageselect() {
				var s = $('newlanginputselect');
				if (s.selectedIndex !== 0) {
					var value = s.options[s.selectedIndex].value;
					$('newlanginput').value = value.substring(4);
					$('newlangcode').value = value.substring(0,3);
					$('newlangcodedisp').update(value.substring(0,3));
				}
			}
			
			function addlang(code,name) {
				$('newlangcode').value = code;
				$('newlanginput').value = name;
				$('newlanginputselect').selectedIndex = 0;
				$('searchresult').update('');
				$('newlangcodedisp').update(code);
			}
			
			function changelanguage(formitemid){
				var code = $('newlangcode').value;
				var language = $('newlanginput').value;
				if (code && language) {
					var langs = \$H($(formitemid).value.evalJSON(true));
					langs.set(code.strip(),language.strip());
					$(formitemid).value = Object.toJSON(langs);
				}
				$('newlanginputselect').selectedIndex = 0;
				$('searchresult').update('');
				$('newlangcodedisp').update('');
				$('newlangcode').value = '';
				$('newlanginput').value = '';
				renderlanguages();
			}	
	
			function searchlanguages() {
				var searchtxt = $('searchbox').value;
				new Ajax.Request('languagesearch.php',
				{
					method:'get',
					parameters: {searchtxt: searchtxt},
					onSuccess: function(response){
						var result = response.responseJSON;
						var items = new Element('tbody',{width:'100%'});
						var header = new Element('tr').addClassName('listHeader');
			
						if(result) {
							header.insert(new Element('th').update('Code'));
							header.insert(new Element('th',{align:'left'}).update('Language'));
			
							items.insert(header);
							var i = 0;
							\$H(result).each(function(itm) {
								var row = new Element('tr');
								if(i%2)
									row.addClassName('listAlt');
								row.insert(new Element('td',{align:'right'}).update(itm.key));
								row.insert(new Element('td').update('<a href=\"#\" onclick=\"addlang(\'' + itm.key + '\',\'' + itm.value + '\');return false;\">' + itm.value + '</a>'));
								items.insert(row);
								i++;
							});
						} else {
							header.insert(new Element('th').update('No Language Found containing the search sting \"' + searchtxt + '\"'));
							items.insert(header);
			
						}
						$('searchresult').update(items);
					}
				});
			}";
		return $str;
	}
}

class ValLanguages extends Validator {
	function validate ($value) {
		$languages = json_decode($value,true);
		if(!is_array($languages) || !isset($languages['en'])) {
			return 'English is required for ' . $this->label;
		}
		return true;
	}
	function getJSValidator () {
		return
		'function (name, label, value, args) {
			var langs = $H(value.evalJSON(true));
			if (langs.length == 0)
				return label + " is required";
			if (!langs.get("en"))
				return "English is required for " + label;

			return true;
		}';
	}
}
?>
