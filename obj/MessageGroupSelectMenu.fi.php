<?
class MessageGroupSelectMenu extends FormItem {
	function render ($value) {

		// jobtype.systempriority used for email message preview
		if (isset($this->args['jobpriority']))
			$jobpriority = $this->args['jobpriority'];
		else
			$jobpriority = 3; // general
		
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
		if (!$issurveytemplate) {
			$nn = $n."_preview";
			$str .= "
			<div id=\"$nn\"></div>
			<script type=\"text/javascript\">
				document.observe('dom:loaded', function() {
					" . ($isstatic?"":"$('$n').observe('change', load_messageinfo);") . "
					load_messageinfo();
				});
				function load_messageinfo() {
					var id = " . ($isstatic?"$value":"$('$n').value;") . ";
					if(id == '') {
						$('$nn').update('');
						return;
					}

					var request = 'ajax.php?ajax&type=messagegrid&id=' + id;
					cachedAjaxGet(request,function(result) {
						var response = result.responseJSON;
						var data = \$H(response.data);
						if(data.size() > 0) {
							var headers = \$H(response.headers);

							var str = '<table class=\'messagegrid\'>';
							headers.each(function(title) {
								str += '<th class=\'messagegridheader\'>' + title.value + '</th>';
							});

							data.each(function(item) {
								str += '<tr>';
									str += '<th class=\'messagegridlanguage\'>' + item.value.languagename + '</th>';
								if(response.headers['voicephone'])
									str += '<td>' + (item.value.voicephone?'<a href=\"#\" onclick=\"showPreview(null,\'jobpriority=$jobpriority&previewid=' + item.value.voicephone + '\'); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
								if(response.headers['plainsms']) {
									if(item.key == 'en')
										str += '<td>' + (item.value.plainsms?'<a href=\"#\" onclick=\"showPreview(null,\'jobpriority=$jobpriority&previewid=' + item.value.plainsms + '\'); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
									else
										str += '<td>-</td>';
								}
								if(response.headers['htmlemail'])
									str += '<td>' + (item.value.htmlemail?'<a href=\"#\" onclick=\"showPreview(null,\'jobpriority=$jobpriority&previewid=' + item.value.htmlemail + '\'); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
								if(response.headers['plainemail'])
									str += '<td>' + (item.value.plainemail?'<a href=\"#\" onclick=\"showPreview(null,\'jobpriority=$jobpriority&previewid=' + item.value.plainemail + '\'); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';

								str += '</tr>';
							});
							str += '</table>';
							$('$nn').update(str);
						} else {
							$('$nn').update('');
						}
					});
				}
			</script>";
		}
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
