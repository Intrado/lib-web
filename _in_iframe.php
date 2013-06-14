<html>
<head>
	<script type="text/javascript" src="script/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="script/jquery.json-2.3.min.js"></script>
	<script type="text/javascript" src="script/postmessagehandler.js"></script>
	<style>
		iframe.embedded {
			margin: 10px;
			overflow-y: hidden;
		}
	</style>
</head>
<body>
<div style="height: 110px; color: white; background: #002a80; font-size: 20px; padding: 20px">This is a fake header for the parent page</div>
<div>
	<div style="float: left; width: 201px; padding: 20px 0; color: white; background: #002a80; font-size: 16px">
		<ul>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
			<li>This is a fake sidebar</li>
		</ul>
	</div>
	<div style="height: 100%; position: relative; margin-left: 201px; background: #FFFFFF">
		<iframe id="theIframe" class="embedded" height="800px" width="98%" frameborder="0" scrolling="no" src="<?=$_GET['page']. "?iframe"?>"></iframe>
	</div>
</div>
<script type="text/javascript">
	var theIframe = $('#theIframe');
	var pmHandler = new PostMessageHandler(theIframe.contentWindow);

	// attach a message listener for communication cross domains
	pmHandler.attachListener(function(event) {
		var data = $.secureEvalJSON(event.data);

		if (data.resize != undefined && data.resize)
			theIframe.attr("width", "98%").attr("height",data.resize + "px");
	});
	
</script>
</body>
</html>