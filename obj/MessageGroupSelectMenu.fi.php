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
						if(response.data.length > 0) {
							var headers = \$H(response.headers);

							var str = '<table style=\'border-spacing: 15px 5px;\'>';
							headers.each(function(title) {
								str += '<th class=\'Destination\'>' + title.value + '</th>';
							});

							response.data.each(function(item) {
								str += '<tr>';
									str += '<th class=\'Language\'>' + item.languagename + '</th>';
								if(response.headers['phone'])
									str += '<td>' + (item.phone>0?'<a href=\"#\" onclick=\"popup(\'messagegroupviewpopup.php?type=phone&subtype=voice&language=' + item.languagename + '&id=' + id + '\', 500, 500); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
								if(response.headers['htmlemail'])
									str += '<td>' + (item.htmlemail>0?'<a href=\"#\" onclick=\"popup(\'messagegroupviewpopup.php?type=email&subtype=html&language=' + item.languagename + '&id=' + id  + '\', 500, 500); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
								if(response.headers['plainemail'])
									str += '<td>' + (item.plainemail>0?'<a href=\"#\" onclick=\"popup(\'messagegroupviewpopup.php?type=email&subtype=plain&language=' + item.languagename + '&id=' + id +  '\', 500, 500); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
								if(response.headers['sms']) {
									if(item.languagecode == 'en')
										str += '<td>' + (item.sms>0?'<a href=\"#\" onclick=\"popup(\'messagegroupviewpopup.php?type=sms&subtype=plain&language=en&id=' + id  + '\', 500, 500); return false;\"><img src=\'img/icons/accept.gif\' /></a>':'<img src=\'img/icons/diagona/16/160.gif\' />') + '</td>';
									else
										str += '<td>" . _L("N/A") . "</td>';
								}
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
