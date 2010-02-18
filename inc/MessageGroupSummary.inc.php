<?

function makeMessageGroupSummaryTable($formname,$formitem,$isstatic = false, $messagegroupid = null) {
		$n = $formname."_messagegroupsummary";
		$msgselect = $formname."_".$formitem;
		$isstatic = $isstatic && $messagegroupid;
		$messageinfogrid = "
			<div id=\"$n\"></div>
			<script type=\"text/javascript\">
				document.observe('dom:loaded', function() {
					" . ($isstatic?"":"$('$msgselect').observe('change', load_messageinfo);") . "
					load_messageinfo();
				});
				function load_messageinfo() {

					var id = " . ($isstatic?"$messagegroupid":"$('$msgselect').value;") . ";
					if(id == '') {
						$('$n').update('');
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
							$('$n').update(str);
						} else {
							$('$n').update('');
						}
					});
				}
			</script>";
		return $messageinfogrid;
}
?>
