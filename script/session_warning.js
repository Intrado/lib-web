
// Global timer variable
sessionTimeout = 0;
sessionWarningTime = 0;

function sessionKeepAliveWarning(timeout) {
	if (timeout)
		sessionWarningTime = timeout;
	else if (sessionWarningTime == 0)
		return;

	// only set up the session warning timer if we are the top window capable of doing this job
	var parentHasSessionWarning = false;
	try {
		parentHasSessionWarning = (this != top) && isFunction(window.parent.sessionKeepAliveWarning);
	} catch (e) {
		// parent doesn't have the session, or isn't workable (same-origin restriction?)
		// we are the top authority in session warnings!
	}
	if (parentHasSessionWarning) {
		window.parent.kickSession();
		return;
	}

	// If there is already a timeout function running
	if (sessionTimeout !== 0) {
		// Then clear it so that we can reset it
		clearTimeout(sessionTimeout);
		sessionTimeout = 0;
	}

	sessionTimeout = setTimeout(function() {
		var $ = jQuery;
		$('.modal.in').modal('hide');
		
		var modal = $('#defaultmodal');
		modal.modal();
		modal.height("auto");
		modal.width("600px");
		var header = $('#defaultmodal').find(".modal-header h3");
		var body = $('#defaultmodal').find(".modal-body");

		header.html("Automatic Logout");
		var content = $('<div>',{'class' : 'keepalive'});
		content.append($('<img>',{src : 'img/icons/lock.png',alt : 'Warning' }));
		content.append($('<span>',{text : 'Your session is about to close due to inactivity.' }));

		var button = $(
			'<button class="btn" type="button">' +
			'	<div class="btn_wrap cf">'+
			'		<span class="btn_left"></span>'+
			'		<span class="btn_middle">'+
			'			<img class="btn_middle_icon" src="img/icons/tick.gif">'+
			'			<span class="btn_text">Refresh Session</span>'+
			'		</span>'+
			'		<span class="btn_right"></span>'+
			'	</div>'+
			'</button>');
		
		content.append($('<p>',{style : 'margin: 10px 0'}).html(button));

		body.html(content);
		$("div.default-modal").css("margin-left", -(modal.width()/2));
		$("div.default-modal").css("margin-top", -(modal.height()/2));

		// Hide modal on resize since it will no longer be centered.
		$(window).one('resize',function() {
			modal.modal('hide');
		});

		var refreshSession = function() {
			content.html($('<img>', {src:"img/ajax-loader.gif", alt: "Refreshing Session"}));
			$.ajax({
				url: 'ajax.php?type=keepalive',
				type:'GET',
				dataType:'json',
				success: function (response) {
					if (response === true){
						content.html("");
						content.append($('<img>', {src:"img/icons/accept.png", alt: "OK"}));
						content.append($('<span>', {text : 'Your session was refreshed successfully.'}));
						setTimeout(function() {
							modal.modal('hide')
						}, 4000);
					} else {
						content.html("");
						content.append($('<img>', {src:"img/icons/error.png", alt: "Error"}));
						content.append($('<span>', {text : 'Your session was not refreshed because your session has expired or logged out.'}));
					}
				},
				error: function () {
					content.html("An error occurred trying to refresh your session.");
				}
			});
		};

		// Dismissing the modal shows activity so do a request to keep session alive, and reset timer, logout if expired 
		modal.one('hide',function() {
			$.ajax({
				url: 'ajax.php?type=keepalive',
				type:'GET'
			});
			sessionKeepAliveWarning();
		});

		button.on('click',refreshSession);
	}, sessionWarningTime);
}

function kickSession() {
	var parentHasSessionWarning = false;
	try {
		parentHasSessionWarning = (this != top) && isFunction(window.parent.sessionKeepAliveWarning);
	} catch (e) {
		// parent doesn't have the session, or isn't workable (same-origin restriction?)
		// we are the top authority in session warnings!
	}
	if (parentHasSessionWarning) {
		parent.window.kickSession();
	} else {
		sessionKeepAliveWarning();
	}
}