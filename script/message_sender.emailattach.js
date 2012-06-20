// email attachments code for add email
/////////////////////////////////////////

function startUpload(){
	$('upload_process').show();	
	return true;
}

function stopUpload(id,name,size,errormessage, formname, itemname) {
	if (!formname || !itemname) {
		return;
	}
	// stopUpload() is called automatically when the iframe is loaded, which may be before document.formvars is initialized by form_load().
	// In that case, just return.
	if (!document.formvars || !document.formvars[formname])
		return;
		
	setTimeout ("var uploadprocess = $(\'upload_process\'); if (uploadprocess) uploadprocess.hide();", 500 );
	
	
	var values = {};
	var fieldelement = $(itemname);
	var uploadedfiles = $("uploadedfiles");
	
	if (!fieldelement)
		return;
	var field = fieldelement.value;
	if(field != "") 
		values = field.evalJSON();
	if(id && name && size && !errormessage) {
		values[id] = {"size":size,"name":name};
	}
	
	// if there are attachments display the div that shows them
	if (Object.keys(values).length > 0)
		uploadedfiles.setStyle({"display":"block"}).update();
	else
		uploadedfiles.update().setStyle({"display":"none"});
	
	var str = "";
	for(var contentid in values) {
		var content = values[contentid];
		
		var downloadlink = new Element("a", {"href": "emailattachment.php?id="  + contentid +  "&name=" + encodeURIComponent(encodeURIComponent(content.name))});
		
		downloadlink.update(content.name);
		
		var sizeinfo = "&nbsp;(Size: " + Math.round(content.size/1024) + "k)&nbsp;";
		
		var removelink = new Element("a", {"href":"#"});
		
		removelink.update("Remove");
		
		removelink.observe("click", function(event, contentid, formname, itemname) {
			event.stop();
			removeAttachment(contentid, formname, itemname);
		}.bindAsEventListener(uploadedfiles, contentid, formname, itemname));
		uploadedfiles.insert(downloadlink).insert(sizeinfo).insert(removelink).insert("<br/>");				 		
	}

	fieldelement.value = Object.toJSON( $H(values) );
	
	if (errormessage) {
		alert(errormessage);
	}
	return true;
}

function removeAttachment(id, formname, itemname) {
	if (!formname || !itemname)
		return;
	var values = $(itemname).value.evalJSON();
	delete values[id];
	
	// if there are attachments display the div that shows them
	var uploadedfiles = $("uploadedfiles");
	if (Object.keys(values).length > 0) {
		console.log('hi');
		uploadedfiles.setStyle({"display":"block"}).update();
	} else {
		uploadedfiles.update().setStyle({"display":"none"});
	}
	Object.keys(values).each(function (contentid) {
		var content = values[contentid];
		var contentname = content.name;
		
		uploadedfiles.insert(
			new Element("a", {"href": "emailattachment.php?id=" + contentid + "&name=" + encodeURIComponent(encodeURIComponent(contentname))}).insert(contentname)
		).insert(
			"&nbsp;(Size: " + Math.round(content.size / 1024) + "k)&nbsp;"
		).insert(
			new Element("a", {"href": "#"}).insert("Remove").observe("click", function(event, contentid, formname, itemname) {
				event.stop();
				removeAttachment(contentid, formname, itemname);
			}.bindAsEventListener(uploadedfiles, contentid, formname, itemname))
		).insert(
			"<br/>"
		);
	});
	$(itemname).value = Object.toJSON(values);
};