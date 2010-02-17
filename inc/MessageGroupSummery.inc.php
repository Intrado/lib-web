<?

function makeMessageGroupSummaryTable($formname,$formitem,$isstatic = false, $messagegroupid = null) {
		$n = $formname."_messagegroupsummery";
		$msgselect = $formname."_".$formitem;
		$isstatic = $isstatic && $messagegroupid;
		error_log($isstatic . " " . $messagegroupid);
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

						var str = '<table style=\'border-width:1px;\'>';
						response.headers.each(function(title) {
							str += '<th>' + title + '</th>';
						});
						response.data.each(function(item) {
							str += '<tr>';
							//console.info(item.languagecode +item.Phone + item.Email);
								str += '<td>' + item.language + '</td>';
							if(response.headers[item.Phone])
								str += '<td>' + (item.Phone!=0?'<img src=\'img/icons/accept.gif\' />':'') + '</td>';
							if(response.headers[item.Email])
								str += '<td>' + (item.Email!=0?'<img src=\'img/icons/accept.gif\' />':'') + '</td>';
							if(response.headers[item.SMS])
								str += '<td>' + (item.SMS!=0?'<img src=\'img/icons/accept.gif\' />':'') + '</td>';
							str += '</tr>';
						});
						str += '</table>';
						$('$n').update(str);
					});
				}
			</script>";
		return $messageinfogrid;
}
?>
