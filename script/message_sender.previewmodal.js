showPreview = function(post_parameters,get_parameters){
	var modal = new ModalWrapper("Loading...",false,false);
	modal.window_contents.update(new Element('img',{src: 'img/ajax-loader.gif'}));
	modal.open();
	
	new Ajax.Request('jobwizard.php?step=/message/phone/text&previewmodal=true' + (get_parameters?'&' + get_parameters:''), {
		'method': 'post',
		'parameters': post_parameters,
		'onSuccess': function(response) {
			modal.window_title.update("");
			if (response.responseJSON) {
				var result = response.responseJSON;
				modal.window_title.update(result.title);
				
				if (result.errors != undefined && result.errors.size() > 0) {
					modal.window_title.update('Unable to Preview');
					
					modal.window_contents.update("The following error(s) occured:");
					var list = new Element('ul');
					result.errors.each(function(error) {
						list.insert(new Element('li').update(error));
					});
					modal.window_contents.insert(list);
				} else if (result.playable == true) {
					modal.window_contents.update(result.form);
					
					var player = new Element('div',{
						id: 'player',
						style: 'text-align:center;'
					});
					var download = new Element('div',{
						id: 'download',
						style: 'text-align:center;'
					});
					modal.window_contents.insert(player);
					modal.window_contents.insert(download);
					
					
					if (result.hasinserts ==  false) {
						var downloadlink = new Element('a',{
							href: 'previewaudio.mp3.php?download=true&uid=' + result.uid
						}).update('Click here to download');
						
						$('download').update(downloadlink);
						embedPlayer('previewaudio.mp3.php?uid=' + result.uid,'player',result.partscount);
					} else {
						$('previewmessagefields').observe('Form:Submitted',function(e){
							embedPlayer('previewaudio.mp3.php?uid=' + e.memo,'player',result.partscount);
							var download = new Element('a',{
								href: 'previewaudio.mp3.php?download=true&uid=' + e.memo
							}).update('Click here to download');
							$('download').update(download);
						});
					}
				} else {
					modal.window_contents.update(result.form);
				}
			} else {
				modal.window_title.update('Error');
				modal.window_contents.update('Unable to preview this message');
			}
		},
		
		'onFailure': function() {
			modal.window_title.update('Error');
			modal.window_contents.update('Unable to preview this message');
		}
	});
	
};
