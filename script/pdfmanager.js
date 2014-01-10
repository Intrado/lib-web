
function deleteBurst(burstID) {
	var $ = jQuery;
	var pdfmanager = 'pdfmanager.php';

	var hiddenBurstEl = $("span[data-burst-id=" + burstID + "]");
	var rowEl = hiddenBurstEl.parents('.content_row');
	// fade out feed elements to be deleted
	rowEl.find(".feed_wrap, .actionlinks").css('opacity', '0.3');


	if (confirmDelete() && burstID) {
		$.ajax({
			url: pdfmanager,
			type: 'POST',
			data: {'id': burstID, 'delete': true},
			success: function(res) {
				window.location = pdfmanager;
			},
			error: function(res) {
				alert('An error occured. Please try again.');
				rowEl.find(".feed_wrap, .actionlinks").css('opacity', '1.0');
			}
		});
	} else {
		rowEl.find(".feed_wrap, .actionlinks").css('opacity', '1.0');
	}
}
