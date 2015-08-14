/**
 * copied from  Image plugin
 */

(function () {

	CKEDITOR.plugins.add('attachmentlink', {
		requires: 'dialog',
		lang: 'af,ar,bg,bn,bs,ca,cs,cy,da,de,el,en,en-au,en-ca,en-gb,eo,es,et,eu,fa,fi,fo,fr,fr-ca,gl,gu,he,hi,hr,hu,id,is,it,ja,ka,km,ko,ku,lt,lv,mk,mn,ms,nb,nl,no,pl,pt,pt-br,ro,ru,si,sk,sl,sq,sr,sr-latn,sv,th,tr,tt,ug,uk,vi,zh,zh-cn', // %REMOVE_LINE_CORE%
		icons: 'attachmentlink',
		hidpi: true,
		init: function (editor) {
			var pluginName = 'attachmentlink';

			// Register the dialog.
			CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/attachmentlink.js');

			// Register the command.
			editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));


			// Register the toolbar button.
			editor.ui.addButton && editor.ui.addButton('AttachmentLink', {
				label: "Attach",
				command: pluginName,
				toolbar: 'attach'
			});

		},
		afterInit: function (editor) {

		}
	});


})();


