<?
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:jobs";
$TITLE = "";
$MESSAGESENDER = true;

include("nav.inc.php");
?>
    </div><!-- end for container starts in nav.inc.php -->
    </div><!-- end for content_wrap starts in nav.inc.php -->
    </div><!-- end for wrap starts in nav.inc.php -->

<script type="text/javascript" language="javascript">
	// set target for all anchors to top, so navigation isn't locked inside the iframe
    jQuery('a').attr('target', '_top');
	
	// resize the iframe so that the content fits
	jQuery(function($) {
		var height = $(".wrap").height();
		$(window.frameElement).height(height);
	}(jQuery));
</script>

</body>
</html>