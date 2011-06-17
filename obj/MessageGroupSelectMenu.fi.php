<?
class MessageGroupSelectMenu extends FormItem {
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$isstatic = isset($this->args['static']) && $this->args['static'] == true && $value;
		$str = '<select id='.$n.' name="'.$n.'" '.$size . ' ' . ($isstatic?'disabled':'') . ' >';
		foreach ($this->args['values'] as $selectvalue => $selectname) {
			$checked = $value == $selectvalue;
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>';
		}
		$str .= '</select>';

		$issurveytemplate = isset($this->args['surveytemplate']) && $this->args['surveytemplate'] == true;
		if (!$issurveytemplate)
			$str .= '<div id="'.$n.'_preview"></div>';
		
		return $str;
	}
		
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		// jobtype.systempriority used for email message preview
		if (isset($this->args['jobpriority']))
			$jobpriority = $this->args['jobpriority'];
		else
			$jobpriority = 3; // general
		$str = '
			$("'.$n.'").observe("change", loadMessageGroupPreview.curry("'.$n.'",'.$jobpriority.'));
			loadMessageGroupPreview("'.$n.'",'.$jobpriority.',null);
		';
		return $str;
		
	}
		
	function renderJavascriptLibraries() {
		$str = '
			<script type="text/javascript">
				function loadMessageGroupPreview(formitem, priority, event) {
					container = $(formitem + "_preview");
					formitem = $(formitem);
					
					// insert ajax loader icon
					container.update(new Element("img", { "src": "img/ajax-loader.gif" }));
					
					// ajaxrequest for messagegrid data
					var request = "ajax.php?ajax&type=messagegrid&id=" + formitem.value;
					cachedAjaxGet(request,function(result) {
						var response = result.responseJSON;
						var data = $H(response.data);
						var headers = $H(response.headers);
						var defaultlang = response.defaultlang;
						
						if(data.size() > 0) {
							// add the table to the form
							var table = new Element("tbody");
							container.update(new Element("table", { "class": "messagegrid" }).insert(table));
							
							// add all the headers to the table
							var row = new Element("tr");
							row.insert(new Element("th").insert("&nbsp;"));
							headers.each(function(header) {
								row.insert(new Element("th", { "class": "messagegridheader" }).insert(header.value));
							});
							table.insert(row);
							
							data.each(function(item) {
								var row = new Element("tr");
								// item key is language, value is the message type to id map
								row.insert(new Element("td", { "class": "messagegridlanguage" }).insert(item.key));
								
								// for each header type, get the message id
								headers.each(function(header) {
									// if the header key (type and subtype) is set
									var hasMessage = false;
									if (item.value[header.key]) {
										hasMessage = true;
										var icon = new Element("img", { "src": "img/icons/accept.png" });
									} else {
										// sms, fb, tw and page are a special case, we show - instead of an empty bulb
										if (item.key !== defaultlang && ["smsplain","postfacebook","posttwitter","postpage"].indexOf(header.key) != -1)
											var icon = "-";
										else
											var icon = new Element("img", { "src": "img/icons/diagona/16/160.png" });
									}
									row.insert(new Element("td").insert(icon));
									
									// observe clicks for preview
									if (hasMessage) {
										icon.observe("click", function (event) {
											showPreview(null,"jobpriority=" + priority + "&previewid=" + item.value[header.key]);
											return false;
										});
									}
								});
								table.insert(row);
							});
						} else {
							container.update();
						}
					});
				}
			</script>';
		return $str;
	}
}

class ValMessageTranslationExpiration extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args,$requiredvalues) {
		global $USER;
		if(!isset($requiredvalues['date']))
			return true;
		$modifydate = QuickQuery("select min(modifydate) from message where messagegroupid = ? and autotranslate = 'translated'", false, array($value));
		if($modifydate != false) {
			if(strtotime("today") - strtotime($modifydate) > (7*86400))
				return _L('The selected message contains auto-translated content older than 7 days. Regenerate translations to schedule a start date');
			if(strtotime($requiredvalues['date']) - strtotime($modifydate) > (7*86400))
				return _L("Can not schedule the job with a message containing auto-translated content older than 7 days from the Start Date");
		}
		return true;
	}
}
?>
