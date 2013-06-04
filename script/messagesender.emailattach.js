// email attachments code for add email
/////////////////////////////////////////
window.startUpload = function () {
//	$('.attachmentPreloader').show();
	return true;
}

window.stopUpload = function (id, name, size, errormessage) {
//    $('.attachmentPreloader').hide();
	var elem = $('#msgsndr_emailmessageattachment');
	if (!elem)
		return;

	var values = {};
	var uploadedfiles = $("#uploadedfiles").empty();
	var field = elem.val();

	if(field != "")
		values = $.parseJSON(field);
	if(id && name && size && !errormessage)
	{
		values[id] = {"size":size,"name":name};
		elem.trigger('add:attachment', {"id":id, "size":size,"name":name});
	}

	elem.val(JSON.stringify(values));

	if (Object.keys(values).length > 0)
	{
		var attachLabel = $('#attachmentsLabel');
		uploadedfiles.show();
		attachLabel.show();
		for(var contentid in values) {
			var content = values[contentid];
			var downloadlinkContainer = $('<div class="downloadlinkContainer"></div>');
			var attachment = $('<a data-attachment-id="' + contentid + '" class="emailAttachment" href="../_emailattachment.php?id='  + contentid +  '&name=' + encodeURIComponent(encodeURIComponent(content.name)) + '" >' + content.name + '</a>');
			var filesize = " &nbsp; (" + Math.round(content.size/1024) + " KB) &nbsp; ";
			var removelink = $('<a class="removeAttachment" data-attachment-id="' + contentid + '" href="#" title="Remove attachment" rel="tooltip" data-placement="right"><span class="glyphicons remove_2 padR0"><i></i></span></a>');

			// register click event handlers on dynamically created 'Remove' links
			removelink.on('click', function(e) {
				e.preventDefault();
				var values = $.parseJSON(elem.val());
				var id = $(this).attr('data-attachment-id')
				delete values[id];
				elem.trigger('remove:attachment', id)
				elem.val(JSON.stringify(values));
				$(this).parent().remove();
				if ($(".downloadlinkContainer").length == 0) {
					attachLabel.hide();
				}
				$('.tooltip').remove();
			});
			removelink.tooltip();


			downloadlinkContainer.append('<span class="glyphicons paperclip lighten"><i></i></span>')
				.append(attachment)
				.append(filesize)
				.append(removelink);
			uploadedfiles.append(downloadlinkContainer);
		}

	}
	else
		uploadedfiles.hide();

	if (errormessage) {
		alert(errormessage);
	}
	return true;
}
