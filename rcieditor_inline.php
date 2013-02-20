<?
/**
 * This is the inline editor's iframed'd page invoked by rcieditor.js
 *
 * @todo Do we need to do anything special to session-protect this page? Seems
 * like not because it doesn't do anything useful without a parent window page
 * available to handshake with the javascript.
 */

function scrub_ascii($string, $lower_accept = null, $upper_accept = null) {
	if (! strlen($string)) return('');
	if (is_null($lower_accept)) $lower_accept = 9;          // TAB
	if (is_null($upper_accept)) $upper_accept = 126;        // '~'

	$ascii = '';
	for ($i = 0; $i < strlen($string); $i++) {
		$chord = ord($string{$i});

		// is the character within our accepted range?
		if (($chord >= $lower_accept) && ($chord <= $upper_accept)) {
			// yep - just tack it on to the end of the output string
			$ascii .= $string{$i};
			continue;
		}
		$hex = str_pad(dechex($chord), 2, '0', STR_PAD_LEFT);

		// nope - encode the byte code
		$ascii .= "\x{$hex}";
	}

	return($ascii);
}

// The target argument is used within rcieditor_inline.js; scrub it for JS use
$parts = explode('-', $_REQUEST['t']);
$target = scrub_ascii($parts[0], ord('A'), ord('z'));
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

			div.editableBlock {
				border: none;
				padding: 1px;
			}

			div.editableBlock:hover {
				cursor: pointer;
			}
			/*div.editableBlock:hover, div.cke_focus {*/
			div.editableBlock, div.cke_focus {
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
		<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
		<script type="text/javascript">
			$.noConflict();
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
			window.top.rcieditor.setLoadingVisibility(false);
		</script>
		<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
		<script type="text/javascript" src="script/rcieditor_inline.js"></script>
		<script type="text/javascript">

			// On document loaded function (see jQuery $.ready() method)
			(function ($) {
				$(function () {
					rcieditorinline.init('<?= $target ?>');
				});
			}) (jQuery);
		</script>
	</head>
	<body>
		<div class="guidebox">
			<div id="wysiwygpage"></div>
			<div id="wysiwygpresave" style="display: none;"></div>
		</div>
	</body>
</html>

