CKEDITOR.dialog.add('attachmentlink', function (editor) {
	return {
		title: 'AttachmentLink',
		minWidth: 400,
		minHeight: 200,
		onShow: function () {
			var editor = this.getParentEditor(),
				element = this.getParentEditor().getSelection().getSelectedElement();

			this.setupContent(element);//this.imageElement.getAttribute('src'));
		},
		onOk: function () {
			var value = this.getContentElement('tab1', 'viewimageurl').getInputElement().getValue() || this.getContentElement('tab1', 'customurl').getInputElement().getValue();
			var imageElement = this.imageElement || editor.document.createElement('img');

			if (!value) {
				alert("Please upload an image or select a URL!");
				return (false);
			}
			var displayName = this.getContentElement('tab1', 'displayname').getInputElement().getValue();
			var contentAttachment = eval('(' + value + ')');
			var fileName = contentAttachment.data.filename;

			contentAttachment.displayName = (displayName == null || displayName.trim() == "") ? fileName : displayName;

			var content = '<!--' + JSON.stringify(contentAttachment) + '-->';

			var location = contentAttachment.location;
			var tag = '<span class="message-attachment-placeholder" contenteditable=false><a href="' + location + '">' + content + contentAttachment.displayName + ' </a></span>';

			editor.insertHtml(tag);

		},
		contents: [
			{
				id: 'tab1',
				label: 'Basic',
				title: 'Basic',
				filebrowser: 'uploadbutton',
				elements: [
					{
						id: 'instructions',
						type: 'html',
						html: 'Please either specify an image URL or upload an image file.'
					},
					{
						id: 'displayname',
						type: 'text',
						label: 'Display Name',
					},
					{
						id: 'customurl',
						type: 'text',
						label: 'Image URL',
						setup: function (url) {
							if (url && url.indexOf("viewimage.php?id=") < 0) {
								this.setValue(url);
							}
						}
					},
					{
						id: 'upload',
						type: 'file',
						label: 'Upload',
						onLoad: function () {
							CKEDITOR.document.getById(this._.frameId).on('load', function () {
								var inputelement = this.getInputElement();
								var uploadbutton = this.getDialog().getContentElement('tab1', 'uploadbutton');
							}.bind(this));
						}
					},
					{
						id: 'uploadbutton',
						filebrowser: 'tab1:viewimageurl',
						'for': ['tab1', 'upload'],
						type: 'fileButton',
						label: 'Upload'
					},
					{
						id: 'uploadstatus',
						type: 'html',
						html: '',
					},
					{
						id: 'viewimageurl',
						type: 'text',
						label: 'Blank',
						onLoad: function () {
							CKEDITOR.document.getById(this.domId).$.style.visibility = "hidden";
						},
						onChange: function (event) {
							this.getDialog().getContentElement('tab1', 'uploadstatus').getElement().setText('Image uploaded.');
						}
					}
				]
			}
		]
	};
});
