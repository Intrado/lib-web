<?
// $SETTINGS already defined in parent /messagelink/index.php page, thus just import global vs parsing ini again
global $SETTINGS;
?>

<? if (isset($SETTINGS['ga']['code']) && !empty($SETTINGS['ga']['code'])) :?>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', <?= "'{$SETTINGS['ga']['code']}'" ?>, 'schoolmessenger.com');
		ga('send', 'pageview');
	</script>
<? endif; ?>