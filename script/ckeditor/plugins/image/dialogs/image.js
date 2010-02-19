/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.dialog.add( 'image', function( editor )
{
	return {
		title : 'Image',
		minWidth : 400,
		minHeight : 200,
		onShow: function () {
			var editor = this.getParentEditor(),
				element = this.getParentEditor().getSelection().getSelectedElement();
				
			if (element && element.getName() == 'img' && !element.getAttribute( '_cke_realelement' )) {
				this.imageElement = element;
				this.setupContent(this.imageElement.getAttribute('src'));
			} else {
				this.imageElement = null;
			}
		},
		onOk: function () {
			var value = this.getContentElement('tab1', 'viewimageurl').getInputElement().getValue() || this.getContentElement('tab1', 'customurl').getInputElement().getValue(),
				imageElement = this.imageElement || editor.document.createElement( 'img' );
				
			if (!value) {
				return;
			}
			
			imageElement.setAttribute('src', value);
			
			if (!this.imageElement) {
				editor.insertElement(imageElement);
			}
		},
		contents : [
			{
				id : 'tab1',
				label : 'Basic',
				title : 'Basic',
				filebrowser: 'uploadbutton',
				elements : [
					{
						id : 'instructions',
						type : 'html',
						html : 'Please either specify an image URL or upload an image file.'
					},
					{
						id : 'customurl',
						type : 'text',
						label : 'Image URL',
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
							CKEDITOR.document.getById(this._.frameId).on('load', function() {
								var inputelement = this.getInputElement();
								var uploadbutton = this.getDialog().getContentElement('tab1', 'uploadbutton');

								inputelement.on('change', function() {
									uploadbutton.click();
								});
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
						id : 'viewimageurl',
						type : 'text',
						label : 'Blank',
						onLoad: function () {
							CKEDITOR.document.getById(this.domId).hide();
						},
						onChange: function (event) {
							if (this.getValue().indexOf("viewimage.php?id=") >= 0) {
								this.getDialog().getButton('ok').click();
							}
						}
					}
				]
			}
		]
	};
} );
