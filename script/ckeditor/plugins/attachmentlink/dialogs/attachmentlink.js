CKEDITOR.dialog.add('attachmentlink', function (editor) {
	return {
		title: 'Attachment',
		minWidth: 400,
		minHeight: 200,
		onShow: function () {
			var editor = this.getParentEditor(),
				element = this.getParentEditor().getSelection().getSelectedElement();

			this.setupContent(element);
		},
		onOk: function () {
			var value = this.getContentElement('tab1', 'viewattachmenturl').getInputElement().getValue() || this.getContentElement('tab1', 'customurl').getInputElement().getValue();
			if (!value) {
				alert("Please upload an attachment or select a URL!");
				return (false);
			}
			var displayName = this.getContentElement('tab1', 'displayname').getInputElement().getValue(),
				url = this.getContentElement('tab1', 'customurl').getInputElement().getValue(),
				location = url,
				defaultDisplayName = url,
				data = {
					url: url,
					displayName: displayName,
					attachmentId: null
				};

			if (!url) {
				var attachment = eval('(' + value + ')');
				data.attachmentId = attachment.id;
				defaultDisplayName = attachment.filename;
				location = attachment.location;
			}

			if (displayName == null || displayName.trim() == "") {
				data.displayName = defaultDisplayName;
			}


			var content = '<!--' + JSON.stringify(data) + '-->';

			var tag = '<span class="message-attachment-placeholder" contenteditable=false><a href="' + location + '">' + content + data.displayName + ' </a></span>';

			editor.insertHtml(tag);

		},
		contents: [
			{
				id: 'tab1',
				label: 'Attachment',
				title: 'Attachment',
				filebrowser: 'uploadbutton',
				padding: 2,
				elements: [
					{
						id: 'instructions',
						type: 'html',
						html: 'Please either specify an document URL or upload an attachment file.'
					},
					{
						id: 'displayname',
						type: 'text',
						label: 'Display Name',
					},
					{
						id: 'customurl',
						type: 'text',
						label: 'Document URL',
						setup: function (url) {
							if (url && url.indexOf("emailattachment.php?id=") < 0) {
								this.setValue(url);
							}
						}
					},
					{
						id: 'switch',
						type: 'html',
						html: '<h3>Or</h3>'
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
						filebrowser: 'tab1:viewattachmenturl',
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
						id: 'viewattachmenturl',
						type: 'text',
						label: 'Blank',
						onLoad: function () {
							CKEDITOR.document.getById(this.domId).$.style.visibility = "hidden";
						},
						onChange: function (event) {
							this.getDialog().getContentElement('tab1', 'uploadstatus').getElement().setText('Attachment uploaded.');
						}
					}
				]
			}
		]
	};
});
