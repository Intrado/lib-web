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
					var request = 'ajax.php?ajax&type=messagegrid&id=' + " . ($isstatic?"$messagegroupid":"$('$msgselect').value;") . "
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
									str += '<td><img src=\'img/icons/' + (item.phone!=0?'accept.gif\'':'diagona/16/160.gif') + '\' /></td>';
								if(response.headers['htmlemail'])
									str += '<td><img src=\'img/icons/' + (item.htmlemail!=0?'accept.gif\'':'diagona/16/160.gif') + '\' /></td>';
								if(response.headers['plainemail'])
									str += '<td><img src=\'img/icons/' + (item.plainemail!=0?'accept.gif\'':'diagona/16/160.gif') + '\' /></td>';
								if(response.headers['sms']) {
									if(item.languagecode == 'en')
										str += '<td><img src=\'img/icons/' + (item.sms!=0?'accept.gif\'':'diagona/16/160.gif') + '\' /></td>';
									else
										str += '<td>" . _L("N/A") . "</td>';
								}
								str += '</tr>';
							});
							str += '</table>';
							$('$n').update(str);
						}

					});
				}
			</script>";
		return $messageinfogrid;
}
?>
