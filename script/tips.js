(function($) {
	// show full message text if user clicks on the 'Read More' links
	$(".tip-read-more").on("click", function(e) {
		e.preventDefault();
		var parent = $(this).parent();
		parent.fadeOut(200, function() {
			parent.next().fadeIn(200);
		});
	});
	// show descending carat to give user visual indication on how data is sorted (desc);
	// only show carat if 1 or more actual rows of data are present, not header only
	if ($("#tips-table tbody tr").length > 1) {
		$("#tips-table.list .listHeader th:nth-child(4)").append("<div id=\"carat\"></div>");
	}
	$(".pagetitle").parent().remove();
})(jQuery);