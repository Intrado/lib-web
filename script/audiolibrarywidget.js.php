<?
require_once("../inc/subdircommon.inc.php");
require_once("../inc/html.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

var AudioLibraryWidget = Class.create({
	initialize: function(container, messagegroupid) {
		this.audiofiles = [];
		this.container = $(container);
		this.messagegroupid = messagegroupid;
		
		this.container.insert(new Element('table',{'style':'border-collapse:collapse; width:100%'}).insert(new Element('tbody')));
		
		this.reload();
	},
	
	showAudioFiles: function(audiofiles) {
		var tbody = new Element('tbody');
		var i = 0;
		var count = audiofiles.length;
		for (i = 0; i < count; ++i) {
			var audiofile = audiofiles[i];
			
			var namelink = new Element('a', {'href': '#'});
			namelink.update(audiofile.name.escapeHTML());
			namelink.observe('click', this.onClickName.bindAsEventListener(this, audiofile));
			
			var playbutton = icon_button('<?=_L("Play")?>','fugue/control', null);
			playbutton.observe('click', this.onClickPlay.bindAsEventListener(this, audiofile));
			
			var insertbutton = icon_button('<?=_L("Insert")?>','fugue/arrow_turn_180', null);
			insertbutton.observe('click', this.onClickInsert.bindAsEventListener(this, audiofile));
			
			var tr = new Element('tr')
				.insert(
					new Element('td').insert(namelink)
				).insert(
					new Element('td')
						.insert(playbutton)
						.insert(insertbutton)
						.insert('<div style="clear:both"></div>')
				);
			
			if (audiofile.messagegroupid)
				tr.addClassName('MessageGroupAudioFile');
			else
				tr.addClassName('GlobalAudioFile');
			tbody.insert(tr);
		}
		
		this.audiofiles = audiofiles;
		this.container.down('tbody').replace(tbody);
	},
	
	reload: function() {
		new Ajax.Request('ajax.php', {
			'method': 'get',
			'parameters': {
				'type': 'getaudiolibrary',
				'messagegroupid': this.messagegroupid
			},
			
			'onSuccess': function(transport) {
				var audiofiles = transport.responseJSON;
				if (!audiofiles)
					return; // Do not show an error, this just means that there are no audiofiles found.
				this.showAudioFiles(audiofiles);
			}.bindAsEventListener(this),
			
			'onFailure': function() {
				alert('<?=addslashes(_L("Sorry, there was a problem retrieving audio files."))?>');
			}
		});
	},
	
	alertConnectionProblem: function() {
		alert('Sorry, there was a connection problem.');
	},
	
	onClickName: function(event, audiofile) {
		var audiofilelink = event.element();
		
		if (!audiofilelink.match('a'))
			return;
		
		var audiofilelinktablecell = audiofilelink.up('td');
		
		event.stop(); // Don't follow through with the link's href.
		
		// Hide the link, then show an input whose value is the audiofile's current name.
		audiofilelink.hide();
		
		var renametextbox = new Element('input', {'style': 'display:block; float:left', 'type': 'text', 'value': audiofile.name});
		
		var renamebutton = icon_button('<?=_L("Rename")?>','tick', null);
		renamebutton.observe('click', this.onClickRename.bindAsEventListener(this, audiofilelink, renametextbox, audiofile));
		
		var deletebutton = icon_button('<?=_L("Delete")?>','cross', null);
		deletebutton.observe('click', this.onClickDelete.bindAsEventListener(this, audiofile));
		
		audiofilelinktablecell.insert(
			renametextbox
		).insert(
			'<div style="clear:both"></div>'
		).insert(
			renamebutton
		).insert(
			deletebutton
		).insert(
			'<div style="clear:both"></div>'
		);
	},
	
	onClickRename: function(event, audiofilelink, renametextbox, audiofile) {
		var newname = renametextbox.value.strip();
		
		if (newname == '') {
			alert('The audio file name cannot be blank.');
			return;
		}
		
		// Resets the contents of the table cell that contains this audio file's link so that
		// only the audio file link is in it.
		// Also updates the cached audio file's name.
		var resetAudioFile = function(newname) {
			var audiofilelinktablecell = audiofilelink.up('td');
			
			// Reset the table cell, keeping only the audio file link.
			audiofilelinktablecell.update(
				audiofilelink.update(newname.escapeHTML()).show()
			);
			
			// Update the cached audio file's name.
			audiofile.name = newname;
		};
		
		new Ajax.Request('ajaxaudiolibrary.php?action=renameaudiofile', {
			'method': 'post',
			'parameters': {
				'id': audiofile.id,
				'newname': newname,
				'messagegroupid': this.messagegroupid
			},
			'onSuccess': function(transport) {
				var data = transport.responseJSON;
				
				if (!data) {
					alert('Sorry, there was a problem renaming this audio file.');
					return;
				} else if (data.error) {
					alert(data.error);
					return;
				}
				
				resetAudioFile(newname);
			},
			'onFailure': this.alertConnectionProblem
		});
	},
	
	onClickPlay: function(event, audiofile) {
			var window_header = new Element('div',{
			className: 'window_header'
			});
			var window_title = new Element('div',{
			className: 'window_title'
			}).update("Audio Preview");
			var window_close = new Element('div',{
			className: 'window_close'
			});
			var window_contents = new Element('div',{
				className: 'window_contents',
				id: 'player',
				style: 'text-align: center;'
			});
			var loader = new Element('a',{
				href: 'img/ajax-loader.gif'
			});
			var modal = new Control.Modal(loader,Object.extend({
				className: 'modalwindow',
				overlayOpacity: 0.75,
				fade: false,
				width: 250,
				insertRemoteContentAt:window_contents,
				afterOpen: function(){
					embedPlayer('audio.wav.php/mediaplayer_preview.wav?id=' + audiofile.id,'player')
				},
				afterClose: function(){
					this.destroy();
					window_contents.remove(); // remove since the player and download uses ids that is reused whe reopened
				}
			},{}));
			modal.container.insert(window_header);
			window_header.insert(window_title);
			window_header.insert(window_close);
			modal.container.insert(window_contents);
			
			window_close.observe('click', function(event,modal) {
				modal.close();
			}.bindAsEventListener(this,modal));
			modal.open();
	},
	
	onClickInsert: function(event, audiofile) {
		this.container.fire('AudioLibraryWidget:ClickInsert', {'audiofile': audiofile});
	},
	
	onClickDelete: function(event, audiofile) {
		var deletebutton = event.element();
		
		if (!deletebutton.match('button'))
			return;
		
		if (!confirm("Are you sure you want to delete this audio file?"))
			return;
			
		new Ajax.Request('ajaxaudiolibrary.php?action=deleteaudiofile', {
			'method': 'post',
			'parameters': {
				'id': audiofile.id
			},
			'onSuccess': function(transport) {
				var data = transport.responseJSON;
				
				if (!data) {
					alert('Sorry, there was a problem deleting this audio file.');
					return;
				}
				
				// Remove the table row for this audio file.
				deletebutton.up('tr').remove();
			},
			'onFailure': this.alertConnectionProblem
		});
	}
});