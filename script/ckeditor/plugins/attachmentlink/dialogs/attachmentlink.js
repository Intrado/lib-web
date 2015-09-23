CKEDITOR.dialog.add('attachmentlink', function (editor) {
	return {
		title: 'Hosted Attachment',
		minWidth: 400,
		minHeight: 200,
		onShow: function () {
			var editor = this.getParentEditor(),
				element = this.getParentEditor().getSelection().getSelectedElement();

			this.setupContent(element);
			this.getContentElement('tab1', 'uploadstatus').getElement().setText('');
			this.getContentElement('tab1', 'upload').getElement().show();
			this.getContentElement('tab1', 'uploadbutton').getElement().show();
		},
		onOk: function () {
			var value = this.getContentElement('tab1', 'viewattachmenturl').getInputElement().getValue();
			if (!value) {
				alert("Please upload an attachment!");
				return (false);
			}
			var displayName = this.getContentElement('tab1', 'displayname').getInputElement().getValue();
			var attachment = JSON.parse(value);
			var displayName = (displayName == null || displayName.trim() == "") ? attachment.filename : displayName;
			var location = attachment.location + "?id=" + attachment.contentId + "&caid=" + attachment.attachmentId + "&name=" + attachment.filename;

			var tag = '<a class="message-attachment-placeholder" contenteditable="false" href="' + location + '">' + displayName + '</a>&nbsp;';

			editor.insertHtml(tag);
		},
		contents: [
			{
				id: 'tab1',
				label: 'Attachment',
				title: 'Hosted Attachment',
				filebrowser: 'uploadbutton',
				padding: 3,
				elements: [
					{
						id: 'instructions',
						type: 'html',
						html: 'Please upload an attachment'
					},
					{
						id: 'displayname',
						type: 'text',
						label: 'Optional: Choose a display name for your file. This name will appear as a link in your email.',
					},
					{
						id: 'upload',
						type: 'file',
						label: 'Select a file to upload',
						onLoad: function () {
							CKEDITOR.document.getById(this._.frameId).on('load', function () {
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
							if (event && event.data && event.data.value) {
								var attachment = JSON.parse(event.data.value);
								this.getDialog().getContentElement('tab1', 'uploadstatus').getElement().setHtml(
									"<em>Success!</em><br>" + CKEDITOR.tools.htmlEncode(attachment.filename) + " uploaded.");
								this.getDialog().getContentElement('tab1', 'upload').getElement().hide();
								this.getDialog().getContentElement('tab1', 'uploadbutton').getElement().hide();
							}
							else {
								this.getDialog().getContentElement('tab1', 'uploadstatus').getElement().setText('Failed to upload the attachment.');
							}
						}
					}
				]
			}
		]
	};
});
