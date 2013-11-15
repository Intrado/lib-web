(function($) {
	
	// show full message text if user clicks on the 'Read More' links
	$(".tip-read-more").on("click", function(e) {
		toggleElem(e, $(this).parent());
	});

	// show contact info details if user clicks on 'View' links
	$(".tip-view-contact").on("click", function(e) {
		toggleElem(e, $(this));
	});

	var toggleElem = function(e, elem) {
		e.preventDefault();
		elem.fadeOut(200, function() {
			elem.next().fadeIn(200);
		});
	};

	// Tip attachments click event handler
	$("a.attachment").on('click', function(e) {
		var id = $(this).attr('data-image-id');
		var fileDetails = $(this).attr('title');

		// set attachment modal header text with file details (name, size)
		$('#attachment-details').html('Tip ' + fileDetails);
		
		// set img src in modal body to use viewimage.php?id=<id>, 
		// which displays the requested image in the modal body
		$("#attachment-image").attr('src', 'viewimage.php?id=' + id );
		
		// open modal now that its ready for viewing
		$("#tip-view-attachment").modal('show');
	});

	$(".pagetitle").parent().remove();
})(jQuery);