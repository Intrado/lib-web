
var ModalWrapper = Class.create({
	initialize: function(title,content,afterOpen) {
		this.window_header = new Element('div',{
			className: 'window_header'
		});
		this.window_title = new Element('div',{
			className: 'window_title'
		}).update(title);
		this.window_close = new Element('div',{
			className: 'window_close'
		});
	
		this.window_contents = new Element('div',{
			className: 'window_contents'
		});
		if (content) {
			this.window_contents.update(content);
			this.loader = false;
		} else {
			this.loader = new Element('a',{
				href: 'img/ajax-loader.gif'
			});
		}
		this.getTopOffset = function(){
			var viewport = document.viewport.getDimensions();
			return Math.floor(viewport.height/4);};
		this.getLeftOffset = function(){
			var viewport = document.viewport.getDimensions();
			return Math.floor(viewport.width/4);};
		this.getModalWidth = function(){
			var viewport = document.viewport.getDimensions();
			return Math.floor(viewport.width/2);};
		
		this.modal = new Control.Modal(this.loader,Object.extend({
			className: 'modalwindow',
			overlayOpacity: 0.75,
			position: [this.getLeftOffset(),this.getTopOffset()],
			fade: false,
			width: this.getModalWidth(),
			indicator:this.loader,
			insertRemoteContentAt:this.window_contents,
			afterOpen: afterOpen,
			afterClose: function(){
				this.destroy();
				this.parent.window_contents.remove();// remove since the player and download uses ids that is reused whe reopened
			}
		},{}));
		this.modal.afterOpen = function() {alert("opened");};
		
		this.modal.parent = this;

		this.modal.container.insert(this.window_header);
		this.window_header.insert(this.window_title);
		this.window_header.insert(this.window_close);
		this.modal.container.insert(this.window_contents);
		
		this.window_close.observe('click', function(event,modal) {
			modal.close();
		}.bindAsEventListener(this,this.modal));
	},
	removeContent: function() {
		this.window_contents.remove(); 
	},
	open: function() {
		this.modal.open();
	}
});

var audioPreviewModal = function(audiofileid) {
	var content = new Element('div',{
		id: 'modal_player',
		style: 'text-align: center;'
	});
	var afterOpen = function(){
		embedPlayer('audio.wav.php/mediaplayer_preview.wav?id=' + audiofileid,'modal_player');
	};
	var modalWrapper = new ModalWrapper("Audio Preview",content, afterOpen);
	modalWrapper.open();
}
