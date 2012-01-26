
function getMessageGroupPreviewGrid(mgid, container, jobtypeid,jobid) {
	// Avoid sending ajax request if nothing is selected
	if (!mgid)
		return;

	container = $(container);
	
	// insert ajax loader icon
	container.update(new Element("img", { "src": "img/ajax-loader.gif" }));
	
	// ajaxrequest for messagegrid data
	var request = "ajax.php?ajax&type=messagegrid&id=" + mgid;
	
	if (jobid != undefined)
		request += "&jobid=" + jobid;
	
	cachedAjaxGet(request,function(result) {
		var response = result.responseJSON;
		var data = $H(response.data);
		var headers = $H(response.headers);
		var defaultlang = response.defaultlang;
		
		if(data.size() > 0) {
			// add the table to the form
			var table = new Element("tbody");
			container.update(new Element("table", { "class": "messagegrid" }).insert(table));
			
			// add all the headers to the table
			var row = new Element("tr");
			row.insert(new Element("th").insert("&nbsp;"));
			headers.each(function(header) {
				row.insert(new Element("th", { "class": "messagegridheader" }).insert(header.value));
			});
			table.insert(row);
			
			data.each(function(item) {
				var row = new Element("tr");
				// item key is language, value is the message type to id map
				row.insert(new Element("td", { "class": "messagegridlanguage" }).insert(item.key));
				
				// for each header type, get the message id
				headers.each(function(header) {
					// if the header key (type and subtype) is set
					var hasMessage = false;
					if (item.value[header.key]) {
						hasMessage = true;
						var icon = new Element("img", { "src": "img/icons/accept.png" });
					} else {
						// sms, fb, tw and page are a special case, we show - instead of an empty bulb
						if (item.key !== defaultlang && ["smsplain","postfacebook","posttwitter","postfeed","postpage","postvoice"].indexOf(header.key) != -1)
							var icon = "-";
						else
							var icon = new Element("img", { "src": "img/icons/diagona/16/160.png" });
					}
					row.insert(new Element("td").insert(icon));
					
					// observe clicks for preview
					if (hasMessage) {
						icon.observe("click", function (event) {
							if (jobid != undefined) {
								showPreview(null,"jobtypeid=" + jobtypeid + "&previewid=" + item.value[header.key] + "&jobid=" + jobid);
							} else {
								showPreview(null,"jobtypeid=" + jobtypeid + "&previewid=" + item.value[header.key]);
							}
							return false;
						});
					}
				});
				table.insert(row);
			});
		} else {
			container.update();
		}
	});
}