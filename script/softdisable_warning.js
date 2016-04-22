function sessionDisable() {

	var pathArray = location.href.split('/');

	if(!location.origin) {
		location.origin = location.protocol + '//' + location.host;
	}

	var goTo = location.origin + '/' + pathArray[3] + '/' + 'index.php?logout=1';

	var $ = jQuery;
	$('.modal.in').modal('hide');

	var modal = $('#defaultmodal');
	modal.modal();
	modal.height('auto');
	modal.width('600px');
	var header = $('#defaultmodal').find('.modal-header h3');
	var body = $('#defaultmodal').find('.modal-body');

	header.html('Customer Temporarily Disabled');
	var content = $('<div>',{'class' : 'softdisablecustomer'});
	content.append($('<img>',{src : 'img/icons/lock.png',alt : 'Warning' }));
	content.append($('<span>',{text : 'NOTE: Your access to this application will be unavailable temporarily while we perform some important system maintenance. Please contact Support if access is not restored.' }));

	var button = $(
			'<button class="btn" type="button">' +
			'	<div class="btn_wrap cf">'+
			'		<span class="btn_left"></span>'+
			'		<span class="btn_middle">'+
			'			<img class="btn_middle_icon" src="img/icons/tick.gif">'+
			'			<span class="btn_text">I Understand</span>'+
			'		</span>'+
			'		<span class="btn_right"></span>'+
			'	</div>'+
			'</button>');


	content.append($('<p>',{style : 'margin: 10px 0'}).html(button));

	body.html(content);
	$("div.default-modal").css("margin-left", -(modal.width()/2));
	$("div.default-modal").css("margin-top", -(modal.height()/2));

	var kickToHomepage = function() {
		window.location = goTo;
	};

	button.on('click', kickToHomepage);
}