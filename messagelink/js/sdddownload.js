$(function() {

	(function() {
		var count = 5;
		var elem = $("#download-count");
		var counter = setInterval(countdownTimer, 1000);

		function countdownTimer() {
			count -= 1;
			if (count < 0) {
				clearInterval(counter);
				return;

				//TODO: start download now
			}
			elem.html(count);
		}
	})();
});