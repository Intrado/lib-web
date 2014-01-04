
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
			data: {id: burstID, delete: true},
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


jQuery(function() {
	
    // inject some verbage between form fields to provide users with some instuctions
	// jQuery("#pdfsendmail_template_fieldarea").after('<div '+ style +'><p>The following is a list of settings that will be used to split the PDF into separate documents, that will be emailed to the respective recipient.</p></div>');
	jQuery("#pdfsendmail_broadcasttype_fieldarea").after('<div class="pdfsendmail-instruction bold"><p><span class="secure-lock"></span>You have the option to password-protect all PDF reports, which will require the recipient to enter a password (i.e. individual ID#) to view their report.</p></div>');
	jQuery("#pdfsendmail_passwordprotected_fieldarea").after('<div class="pdfsendmail-instruction"></div>');



	// disable template-related input elements and reduce width of the skip * pages/report inputs
	// jQuery("#pdfsendmail_skipstart").attr('disabled', 'disabled');
	// jQuery("#pdfsendmail_skipend").attr('disabled', 'disabled');
	// jQuery("#pdfsendmail_pagesperreport").attr('disabled', 'disabled');
	// jQuery("#pdfsendmail_template").attr('disabled', 'disabled');

});



