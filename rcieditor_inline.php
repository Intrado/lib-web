<?
/**
 * This is the inline editor's iframed'd page invoked by rcieditor.js
 *
 * @todo Do we need to do anything special to session-protect this page? Seems
 * like not because it doesn't do anything useful without a parent window page
 * available to handshake with the javascript.
 *
 * Note that we use PHP's json_encode() to take query string data and convert it
 * into a nice, JS-safe string that we don't have to worry about code injection.
 *
 * SMK created soometime around 2013-01-05
 */
?>
<!DOCTYPE html>
<html>
	<meta charset="utf-8"/>
	<head>
		<style type="text/css">

			<?/*
			SMK notes that white space is needed at least above the
			first editable region in order for CKE inline toolbar to
			have some place to position itself other than over the
			top of the text area.
			*/?>
			html, body {
				background-color: white;
				padding: 15px;
				margin: 0px;
			}

			.editableBlock {
				border: none;
				padding: 1px;
			}

			.editableBlock:hover {
				cursor: pointer;
			}

			.editableBlock, div.cke_focus {
				margin: 0px;
				outline: #FFFFFF dashed 1px;
				border: 1px dashed #000000;
				padding: 0px;
			}

			<?/* Style a bit the inline editables. */?>
			.cke_editable.cke_editable_inline {
				cursor: pointer;
			}

			<?/*
			Once an editable element gets focused, the "cke_focus"
			class is added to it, so we can style it differently.
			*/?>
			.cke_editable.cke_editable_inline.cke_focus {
				outline: #FFFFFF dashed 1px;
				cursor: text;
				margin: 0px;
			}

			<?/* Avoid pre-formatted overflows inline editable. */?>
			.cke_editable_inline pre {
				white-space: pre-wrap;
				word-wrap: break-word;
			}
		</style>
		<script type="text/javascript">
		<?
			// Set the document.domain according to the d argument
			// passed on the query string so that the parent window can
			// access us and vice versa
			if ($domain = json_encode($_REQUEST['d'])) {
				// This should only end up setting the document.domain under
				// IE where this setting is not initialized (for some reason?)
				print "if (document.domain != {$domain}) {\ndocument.domain = {$domain};\nconsole.log('Setting document.domain to [{$domain}]'); \n}\n";
			}
		?>
		</script>
		<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
		<script type="text/javascript">
			<?/* Note that this hack is to allow us to see the TextColor and BGCOlor buttons in the inline editor's 
			     toolbar. If we do not call the setLoadingVisibility(false) then those two tools do not show up in
			     the toolbar. This call MUSt occur immediately before ckeditor.js is loaded. Even if the call appears
			     immediately after loading ckeditor.js, the toolbar items will not show up. So what voodoo exists within
			     this magic function that has some effect on the ckeditor constructor method even before the object exists?
			     little more than changing the visibile state of the iframe and the little ajax "please wait" loading
			     indicator. It appears that CKE requires the iframe to be visible in the DOM at the time it is instantiated
			     or else, it punishes us with having no access to these two items in the toolbar. This is probably
			     unintended behavior and will be turned into a bug report by SMK on CKE's site.

			     TODO: remove this hack if/when CKE fixes this issue (http://dev.ckeditor.com/ticket/9802)
			*/?>
			window.parent.rcieditor.setLoadingVisibility(false);
		</script>
		<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
		<script type="text/javascript" src="script/rcieditor_inline.js"></script>
		<script type="text/javascript">
			// On document loaded function (see jQuery $.ready() method)
			$(function () {
				rcieditorinline.init(<?= json_encode($_REQUEST['t']); ?>);
			});
		</script>
	</head>
	<body>
		<div class="guidebox">
			<div id="wysiwygpage"></div>
			<div id="wysiwygpresave" style="display: none;"></div>
		</div>
		<style type="text/css">
			<?/* SMK added 2013-03-07 to force this button's label to show in the toolbar */?>
			.cke_button__pastefromphone_label {
				display: inline-block;
			}
		</style>
	</body>
</html>

