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
		this.audiofiles = {};
		this.container = $(container);
		this.messagegroupid = messagegroupid;
		
		this.container.insert(new Element('table',{'style':'border-collapse:collapse; margin-top: 10px; width:100%'}).insert(new Element('tbody')));
		
		this.reload();
	},
	
	showAudioFiles: function(audiofiles) {
		var tbody = new Element('tbody');
		
		for (var audiofileid in audiofiles) {
			var audiofile = audiofiles[audiofileid];
			audiofile.id = audiofileid;
			var namelink = new Element('a', {'href': '#'});
			namelink.update(audiofile.name);
			namelink.observe('click', this.onClickName.bindAsEventListener(this, audiofile));
			var playbutton = icon_button('<?=_L("Play")?>','fugue/control', null);
			playbutton.observe('click', this.onClickPlay.bindAsEventListener(this, audiofile));
			
			var namediv = new Element('div', {'style': 'float:left'});
			namediv.insert(namelink);
			
			var tr = new Element('tr').insert(new Element('td').insert(namediv)).insert(new Element('td').insert(playbutton).insert('<div style="clear:both"></div>'));
			
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
				'type': 'AudioFiles',
				'messagegroupid': this.messagegroupid
			},
			
			'onSuccess': function(transport) {
				var audiofiles = transport.responseJSON;
				if (!audiofiles)
					return; // Do not show an error, this just means that there are no audiofiles found.
				
				this.showAudioFiles(audiofiles);
			}.bindAsEventListener(this),
			
			'onFailure': function() {
				alert('<?=_L("Sorry, there was a problem retrieving audio files.")?>');
			}
		});
	},
	
	onClickName: function(event, audiofile) {
		event.stop(); // Don't follow through with the link's href.
		this.container.fire('AudioLibraryWidget:ClickName', {'audiofile': audiofile});
	},
	
	onClickPlay: function(event, audiofile) {
		popup('previewaudio.php?close=1&id=' + audiofile.id, 400, 400);
	}
});