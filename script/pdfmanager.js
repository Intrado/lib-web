
function deleteBurst(burstID) {
	if (confirmDelete()) {
		var pdfmanager = 'pdfmanager.php';
		jQuery.ajax({
			url: pdfmanager,
			type: 'POST',
			data: {id: burstID, delete: true},
			success: function(res) {
				window.location = pdfmanager;
			},
			error: function(res) {
				alert('An error occured. Please try again.');
			}
		});
	}
}



